<?php

include "config.php";
include "auth.php";
include_once "clinic_helpers.php";

clinic_ensure_infrastructure($con);
clinic_ensure_sync_conflicts($con);
$flash = clinic_take_flash();
clinic_ensure_runtime_controls($con);

$unreadStaffMessages = 0;
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
if ($currentUserId > 0 && clinic_table_exists($con, 'staff_messages')) {
  $unreadStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM staff_messages WHERE recipient_user_id = ? AND is_read = 0");
  if ($unreadStmt) {
    mysqli_stmt_bind_param($unreadStmt, 'i', $currentUserId);
    mysqli_stmt_execute($unreadStmt);
    $unreadResult = mysqli_stmt_get_result($unreadStmt);
    $unreadStaffMessages = (int) (($unreadResult ? mysqli_fetch_assoc($unreadResult)['total'] : 0) ?? 0);
    mysqli_stmt_close($unreadStmt);
  }
}

$nextPatientAlert = null;
$nextPatientRaw = clinic_get_app_setting($con, 'doctor_next_patient_alert', '');
if ($nextPatientRaw) {
  $decodedNextPatient = json_decode($nextPatientRaw, true);
  if (is_array($decodedNextPatient) && !empty($decodedNextPatient['patient_id']) && !empty($decodedNextPatient['full_name'])) {
    $nextPatientAlert = $decodedNextPatient;
  }
}


/* ===== إحصائيات ===== */

// إجمالي المرضى
$totalPatients = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM add_patient WHERE " . clinic_active_patient_where($con, 'add_patient'))
)['total'];

// زيارات اليوم
$todayVisits = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE()")
)['total'] ?? 0;

$todayDoneVisits = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE() AND is_done = 1")
)['total'] ?? 0;

$todayPendingVisits = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE() AND is_done = 0")
)['total'] ?? 0;

// مراجعات اليوم

$followups = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM followups WHERE followup_date = CURDATE() AND status='pending'")
)['total'] ?? 0;

$upcomingFollowups = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM followups
    WHERE status='pending'
    AND followup_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
  ")
)['total'] ?? 0;

$expectedVisitsToday = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM expected_appointments
    WHERE expected_date = CURDATE()
    AND status = 'expected'
  ")
)['total'] ?? 0;

$expectedVisitsUpcoming = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM expected_appointments
    WHERE expected_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND status = 'expected'
  ")
)['total'] ?? 0;

// عمليات هذا الشهر
$monthOperations = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM surgery
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE()) 
  ")
)['total'] ?? 0;

$monthInjections = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM injection
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE())
  ")
)['total'] ?? 0;

$monthLasers = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM laser
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE())
  ")
)['total'] ?? 0;

$topLaserRow = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT laser_type, COUNT(*) total
    FROM laser
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE())
    GROUP BY laser_type
    ORDER BY total DESC
    LIMIT 1

  ")
);
$topLaserType = $topLaserRow['laser_type'] ?? 'لا يوجد';
$topLaserCount = (int)($topLaserRow['total'] ?? 0);

$topInjectionRow = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT injection_type, COUNT(*) total
    FROM injection
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE())
    GROUP BY injection_type
    ORDER BY total DESC
    LIMIT 1
  ")
);
$topInjectionType = $topInjectionRow['injection_type'] ?? 'لا يوجد';
$topInjectionCount = (int)($topInjectionRow['total'] ?? 0);

// عمليات قادمة
$pendingOperations = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM surgery_appointment 
    WHERE status='pending' AND date >= CURDATE()
  ")
)['total'] ?? 0;

/* ===== Analytics Charts ===== */
$dailyVisitLabels = [];
$dailyVisitCounts = [];
$dailyVisitMap = [];
$clinicDays = [1, 2, 3, 4, 6]; // Monday, Tuesday, Wednesday, Thursday, Saturday.
$dailyVisitResult = mysqli_query($con, "
  SELECT visit_date, COUNT(*) total
  FROM visits
  WHERE visit_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND CURDATE()
  GROUP BY visit_date
");
while ($row = mysqli_fetch_assoc($dailyVisitResult)) {
  $dailyVisitMap[$row['visit_date']] = (int) $row['total'];
}
$clinicDates = [];
$cursor = new DateTime();
while (count($clinicDates) < 7) {
  if (in_array((int) $cursor->format('N'), $clinicDays, true)) {
    $clinicDates[] = $cursor->format('Y-m-d');
  }
  $cursor->modify('-1 day');
}
$clinicDates = array_reverse($clinicDates);
foreach ($clinicDates as $date) {
  $dailyVisitLabels[] = date('D d/m', strtotime($date));
  $dailyVisitCounts[] = $dailyVisitMap[$date] ?? 0;
}

$monthLabels = [];
$monthKeys = [];
for ($i = 5; $i >= 0; $i--) {
  $monthKey = date('Y-m', strtotime("first day of -$i months"));
  $monthKeys[] = $monthKey;
  $monthLabels[] = date('M Y', strtotime($monthKey . '-01'));
}

$surgeryMap = [];
$surgeryResult = mysqli_query($con, "
  SELECT DATE_FORMAT(date, '%Y-%m') month_key, COUNT(*) total
  FROM surgery
  WHERE date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
  GROUP BY DATE_FORMAT(date, '%Y-%m')
");
while ($row = mysqli_fetch_assoc($surgeryResult)) {
  $surgeryMap[$row['month_key']] = (int) $row['total'];
}
$sixMonthSurgeryCounts = [];
foreach ($monthKeys as $monthKey) {
  $sixMonthSurgeryCounts[] = $surgeryMap[$monthKey] ?? 0;
}

$injectionTypeLabels = [];
$injectionTypeCounts = [];
$injectionTypeResult = mysqli_query($con, "
  SELECT COALESCE(NULLIF(TRIM(injection_type), ''), 'غير محدد') injection_type, COUNT(*) total
  FROM injection
  WHERE date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
  GROUP BY COALESCE(NULLIF(TRIM(injection_type), ''), 'غير محدد')
  ORDER BY total DESC
");
while ($row = mysqli_fetch_assoc($injectionTypeResult)) {
  $injectionTypeLabels[] = $row['injection_type'];
  $injectionTypeCounts[] = (int) $row['total'];
}

/* ===== جدول العمليات القادمة ===== */
$upcoming = mysqli_query($con, "
  SELECT 
    add_patient.full_name,
    surgery_appointment.patient_id,
    surgery_appointment.date,
    surgery_appointment.eye,
    surgery_appointment.surgery_type,
    surgery_appointment.id,
    surgery_appointment.status
  FROM surgery_appointment
  JOIN add_patient ON add_patient.id = surgery_appointment.patient_id
  WHERE surgery_appointment.status='pending' AND surgery_appointment.date >= CURDATE()
  ORDER BY surgery_appointment.date ASC
  LIMIT 5
");

/* ===== تنبيهات ===== */
$alerts = [];
// حالات حرجة 
$critical = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM add_patient 
  WHERE is_critical=1 
"));
if ($critical > 0) $alerts[] = "<div class='alert alert-danger'>🚨 يوجد $critical حالات حرجة</div>";

// عمليات متأخرة
$late = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date < CURDATE()
"));
if ($late > 0) // متأخرة
  $alerts[] = "<div class='alert alert-danger'>🔴 يوجد $late عملية متأخرة</div>";

// عمليات قريبة
$soon = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 5 DAY)
"));
if ($soon > 0) // قريبة
  $alerts[] = "<div class='alert alert-warning'>⚠️ يوجد $soon عمليات خلال 5 أيام</div>";

$openSyncConflicts = 0;
$openSyncConflictsRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status = 'open'"));
$openSyncConflicts = (int) ($openSyncConflictsRow['total'] ?? 0);
$pendingImageSync = 0;
if (clinic_table_exists($con, 'patient_images') && clinic_column_exists($con, 'patient_images', 'sync_status')) {
  $pendingImageSyncRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM patient_images WHERE sync_status = 0"));
  $pendingImageSync = (int) ($pendingImageSyncRow['total'] ?? 0);
}
$latestBackupAt = null;
$backupFiles = is_dir('C:/clinic_backups') ? (glob('C:/clinic_backups/*.sql') ?: []) : [];
if ($backupFiles) {
  usort($backupFiles, static fn($a, $b) => filemtime($b) <=> filemtime($a));
  $backupMtime = filemtime($backupFiles[0]);
  $latestBackupAt = $backupMtime ? date('Y-m-d H:i', $backupMtime) : null;
}

if ($openSyncConflicts > 0) {
  $alerts[] = "<div class='alert alert-danger'>⛔ يوجد $openSyncConflicts تعارض مزامنة مفتوح - <a href='sync_conflicts.php'>إدارة التعارضات</a></div>";
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- نستخدم نفس CSS تبعك -->

</head>

<style>
  :root {
    --primary: #2563eb;
    --secondary: #0f766e;
    --accent: #f59e0b;
    --danger: #dc2626;
    --success: #16a34a;
    --bg: #f1f5f9;
    --card: #ffffffff;
    --text: #1e293b;
    --muted: #f87171;
    --box: #e2e8f0;
    --radius: 14px;
    --shadow: 0 10px 25px rgba(0, 0, 0, .08);
  }

  /* ===== Dark Mode ===== */
  body.dark {
    --bg: #020617;
    --card: #0f172a;
    --text: #e5e7eb;
    --muted: #f87171;
    --primary: #60a5fa;
    --secondary: #2dd4bf;
    --box: #334155;
  }

  /* ===== Dashboard ===== */

  /* ===== Layout ===== */
  .content {
    max-width: 1200px;
    margin: 10px auto;
  }

  /* ===== Header ===== */
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--card);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    border-radius: var(--radius);
    color: var(--text);
  }

  /* ===== Layout ===== */
  .layout {
    display: flex;
    min-height: calc(100vh - 60px);
  }


  /* ===== Sidebar ===== */
  .sidebar {
    width: 180px;
    background: var(--card);
    box-shadow: var(--shadow);
    padding: 20px;
    transition: .3s;
  }

  .sidebar.hidden {
    width: 0;
    padding: 0;
    overflow: hidden;
  }

  .sidebar h3 {
    color: var(--primary);
    margin-bottom: 20px;
    font-weight: bold;
    font-size: 24px;
  }

  .menu-group {
    margin-bottom: 25px;
  }

  .menu-group span {
    display: block;
    font-weight: bold;
    font-size: 18px;
    color: var(--muted);
    margin-bottom: 10px;
  }

  .menu-group a {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 6px;
    text-decoration: none;
    color: var(--text);
    transition: .3s;
  }

  .menu-group a:hover {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    transform: translateX(-5px);
  }

  .menu-group a.danger:hover {
    background: linear-gradient(135deg, var(--danger), #ef4444);
  }

  /* ===== Content ===== */
  .content {
    flex: 1;
    padding: 15px;
    transition: .3s;
  }

  /* ===== Buttons ===== */

  .toggle-sidebar {
    border: none;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    box-shadow: var(--shadow);
    transition: .3s;
  }

  .toggle-sidebar:hover {
    transform: translateY(-2px);
  }


  /* ===== Search ===== */
  .search-box {
    margin-bottom: 20px;
    text-align: center;
  }

  .search-box input {
    width: 55%;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #88c8d8;
    font-size: 16px;
    outline: none;
    transition: .3s;
  }

  .search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 8px rgba(37, 99, 235, .3);
  }

  /* نتائج البحث */
  #results {
    background: var(--card);
    border-radius: var(--radius);
    margin-top: 10px;
    box-shadow: var(--shadow);
    text-align: center;
    width: 45%;
    margin: auto;

  }


  .result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f4f4f4;
    margin-bottom: 8px;
    border-radius: 10px;
  }

  .result-item span {
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
  }

  .result-item span:hover {
    color: var(--primary);
  }

  .delete-btn {
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
  }

  .delete-btn:hover {
    transform: scale(1.3) rotate(-10deg);
  }

  .section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 24px 0 14px;
  }

  .section-heading h2 {
    margin: 0;
    color: var(--text);
    font-size: 22px;
  }

  .section-heading span {
    color: var(--primary);
    font-size: 13px;
    font-weight: 800;
  }

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
  }

  .health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
  }

  .health-grid .card {
    padding: 16px;
  }

  .health-grid .card span {
    display: block;
    color: var(--primary);
    font-size: 19px;
    font-weight: 900;
    overflow-wrap: anywhere;
  }

  .health-grid .card p {
    margin: 7px 0 0;
    color: var(--text);
    font-weight: 800;
  }

  .patient-overview {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) 2fr;
    gap: 18px;
    margin-bottom: 18px;
  }

  .patient-total-card {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
  }

  .patient-total-card .patient-icon {
    font-size: 42px;
  }

  .patient-total-card span {
    display: block;
    font-size: 42px;
    font-weight: 900;
    line-height: 1;
  }

  .patient-total-card p {
    margin: 8px 0 0;
    font-weight: 800;
    color: rgba(255, 255, 255, .9);
  }

  .overview-note {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid rgba(37, 99, 235, .08);
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .overview-note strong {
    color: var(--text);
    font-size: 18px;
  }

  .overview-note span {
    color: #64748b;
    font-weight: 700;
    line-height: 1.6;
  }

  .overview-note a {
    flex: 0 0 auto;
    padding: 10px 14px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    text-decoration: none;
    font-weight: 800;
  }

  .cards-grid.workload-cards {
    grid-template-columns: repeat(6, minmax(130px, 1fr));
  }

  .card {
    background: linear-gradient(135deg, var(--card), #f8fafc);
    backdrop-filter: blur(6px);
    box-shadow: var(--shadow);
    border-radius: var(--radius);
    border: 1px solid rgba(37, 99, 235, .08);
    border-top: 4px solid var(--primary);
    padding: 20px;
    text-align: center;
    transition: .3s;
  }

  .card.visits-card {
    border-top-color: #0f766e;
  }

  .card.followup-card {
    border-top-color: #f59e0b;
  }

  .card.surgery-card {
    border-top-color: #16a34a;
  }

  .card.pending-card {
    border-top-color: #dc2626;
  }

  .card.injection-card {
    border-top-color: #8b5cf6;
  }

  .card.laser-card {
    border-top-color: #0891b2;
  }

  .card:hover {
    transform: translateY(-5px);
  }

  .card span {
    display: block;
    font-size: 32px;
    font-weight: 800;
    margin: 10px 0;
    color: var(--primary);
  }

  .card p {
    font-weight: 700;
    color: var(--muted);
  }

  .card small {
    display: block;
    min-height: 20px;
    margin-top: 8px;
    color: #64748b;
    font-weight: 700;
    line-height: 1.5;
  }

  body.dark .section-heading h2 {
    color: var(--text);
  }

  body.dark .card {
    background: linear-gradient(145deg, var(--card), #020617);
    border-color: rgba(96, 165, 250, .16);
  }

  body.dark .card small {
    color: #94a3b8;
  }

  body.dark .overview-note {
    background: linear-gradient(145deg, var(--card), #020617);
    border-color: rgba(96, 165, 250, .16);
  }

  body.dark .overview-note span {
    color: #94a3b8;
  }

  /* ===== Analytics ===== */
  .analytics-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
  }

  .chart-card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 18px;
    border: 1px solid rgba(37, 99, 235, .08);
  }

  .chart-card.wide {
    grid-column: 1 / -1;
  }

  .chart-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }

  .chart-head h3 {
    margin: 0;
    color: var(--text);
    font-size: 18px;
  }

  .chart-head span {
    color: var(--primary);
    font-weight: 800;
    font-size: 13px;
  }

  .chart-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: -4px 0 12px;
  }

  .chart-summary span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(37, 99, 235, .08);
    color: var(--text);
    font-size: 12px;
    font-weight: 800;
  }

  body.dark .chart-summary span {
    background: rgba(96, 165, 250, .12);
    color: #dbeafe;
  }

  .chart-wrap {
    position: relative;
    height: 260px;
  }

  .chart-wrap canvas {
    display: block;
    width: 100%;
    height: 100%;
  }

  .chart-empty {
    height: 260px;
    display: none;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-weight: 700;
    background: rgba(148, 163, 184, .08);
    border-radius: 12px;
  }

  body.dark .chart-card {
    background: linear-gradient(145deg, var(--card), #020617);
    border-color: rgba(96, 165, 250, .18);
  }

  body.dark .chart-empty {
    color: #94a3b8;
    background: rgba(15, 23, 42, .7);
  }

  /* ===== Box ===== */
  .box {
    background: var(--box);
    padding: 20px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin: 20px 0;
    transition: .3s;

  }

  .box h3 {
    margin-bottom: 15px;
    color: var(--text);
  }


  /* ===== Table ===== */
  .table-scroll {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    max-height: 65vh;
    overflow: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    border-radius: var(--radius);
    overflow: hidden;

  }

  th,
  td {
    padding: 14px;
    text-align: center;
  }

  th {
    background: linear-gradient(135deg, var(--secondary), #0d9488);
    color: #fff;
    position: sticky;
    top: 0;
    border-radius: 5px;
  }

  td {
    border-bottom: 1px solid #e2e8f0;
    color: var(--text);
  }

  .open-btn {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    padding: 8px 14px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    box-shadow: var(--shadow);
    transition: .3s;
  }

  tr:nth-child(even) {
    background: #f8fafc
  }


  body.dark tr:nth-child(even) {
    background: #020617
  }

  tr:hover {
    transform: scale(1.01);
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
  }


  /* ===== Alerts Container ===== */
  .alerts {
    margin: 20px 0;
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    gap: 15px;
  }

  /* ===== Base Alert ===== */
  .alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: var(--shadow);
    background: var(--card);
    border-right: 5px solid;
    transition: .3s;
    animation: fadeIn 0.4s ease;
    width: fit-content;
  }



  .alert:hover {
    transform: translateY(-2px);
  }

  /* ===== أنواع التنبيهات ===== */

  /* 🔴 خطر */
  .alert-danger {
    border-color: #dc2626;
    background: rgba(220, 38, 38, 0.08);
    color: #b91c1c;
  }

  /* ⚠️ تحذير */
  .alert-warning {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
  }

  /* 🟢 نجاح */
  .alert-success {
    border-color: #16a34a;
    background: rgba(22, 163, 74, 0.1);
    color: #065f46;
  }

  /* 🔵 معلومات */
  .alert-info {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.1);
    color: #1e3a8a;
  }

  /* أيقونة */
  .alert i {
    font-size: 18px;
  }

  .alert a {
    color: inherit;
    font-weight: 800;
    text-decoration: underline;
  }

  .next-patient-panel {
    margin: 16px 0;
    background: linear-gradient(120deg, #fff7ed, #ffedd5);
    border: 1px solid #fdba74;
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: var(--shadow);
  }

  .next-patient-title {
    font-weight: 900;
    color: #9a3412;
    margin-bottom: 4px;
  }

  .next-patient-meta {
    color: #7c2d12;
    font-weight: 700;
    font-size: 14px;
  }

  .next-patient-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .next-patient-actions a {
    text-decoration: none;
    color: #fff;
    background: #c2410c;
    border-radius: 10px;
    padding: 8px 12px;
    font-weight: 800;
  }

  .next-patient-actions .open {
    background: #2563eb;
  }

  /* ===== زر الحالات الحرجة ===== */
  .danger-card {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(220, 38, 38, 0.1);
    color: #b91c1c;
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 700;
    border: 2px solid #dc2626;
    text-decoration: none;
    transition: .3s;
  }

  .danger-card:hover {
    background: #dc2626;
    color: #fff;
  }



  .theme-toggle {

    border: none;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 50%;
    font-size: 18px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    box-shadow: var(--shadow);
    transition: .3s;
  }


  .theme-toggle:hover {
    transform: scale(1.1);
  }



  @media (max-width: 1024px) {
    .search-box input {
      width: 70%;
    }
  }

  @media (max-width: 768px) {
    .search-box input {
      width: 90%;
    }

    .cards-grid {
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .patient-overview {
      grid-template-columns: 1fr;
    }

    .cards-grid.workload-cards {
      grid-template-columns: repeat(2, minmax(150px, 1fr));
    }

    .analytics-grid {
      grid-template-columns: 1fr;
    }

    .chart-wrap,
    .chart-empty {
      height: 230px;
    }
  }

  @media (max-width: 500px) {
    header {
      flex-direction: column;
      gap: 15px;
    }

    .layout {
      flex-direction: column;
    }

    .sidebar {
      width: 100%;
    }

    .overview-note {
      align-items: stretch;
      flex-direction: column;
    }
  }

  @media (max-width: 400px) {
    .toggle-sidebar {
      padding: 6px 12px;
      font-size: 14px;
    }

    .theme-toggle {
      padding: 6px 10px;
      font-size: 16px;
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>



<body>
  <header>

    <div>
      <button class="toggle-sidebar" onclick="toggleSidebar()">
        ⬅️ القائمة
      </button>

      <button class="theme-toggle" id="themeToggle">🌙</button>

    </div>

    مرحبا <?= $_SESSION['name'] ?> 👋
  </header>


  <div class="layout">
    <!-- ===== Sidebar ===== -->
    <aside class="sidebar hidden" id="sidebar">
      <h3>القائمة</h3>
      <div class="menu-group">

        <a href="dashboard.php">📊 لوحة التحكم</a>

      </div>

      <div class="menu-group">
        <span>👤 المرضى</span>
        <a href="add-patient.php">➕ إضافة مريض</a>
        <a href="main.php">👥 بيانات المرضى</a>
        <a href="archived-patients.php">أرشيف المرضى</a>
        <a href="data-quality.php">جودة البيانات</a>
        <a href="followups.php">🔄 المتابعة</a>
      </div>


      <div class="menu-group">
        <span>📅 المواعيد</span>
        <a href="work-queue.php">قائمة عمل اليوم</a>
        <a href="visits.php">📅 زيارات اليوم</a>
        <a href="followup-appointment.php">📌 إعطاء موعد مراجعة</a>
        <a href="import_expected.php">📥 استيراد المواعيد</a>
        <a href="expected_appointments.php">📅 المواعيد المتوقعة</a>

      </div>

      <div class="menu-group">
        <span>💉 العمليات</span>
        <a href="operation-by-date.php">🏥 مواعيد العمليات</a>
        <a href="confirmed-list.php">📋 قوائم العمليات</a>
        <a href="import_surgery_excel.php">📥 استيراد العمليات</a>
      </div>


      <div class="menu-group">
        <span>⚙️ النظام</span>
        <a href="treatment-types.php">🧬 إدارة الاجراءات</a>
        <a href="reports.php">📊 التقارير</a>
        <a href="common-medicines.php">💊 إدارة الأدوية</a>
        <a href="treatment-templates.php">قوالب العلاج</a>
        <a href="staff-messages.php">📩 رسائل داخلية<?php if ($unreadStaffMessages > 0): ?> (<?= $unreadStaffMessages ?>)<?php endif; ?></a>
        <a href="audit-log.php">سجل العمليات</a>
        <a href="settings.php">⚙️ الإعدادات</a>
        <a href="logout.php" class="danger">🚪 تسجيل الخروج</a>
      </div>
    </aside>

    <main class="content">
      <?php if ($flash): ?>
        <div class="alert <?= ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-danger' ?>">
          <?= h($flash['message'] ?? '') ?>
        </div>
      <?php endif; ?>

      <div class="search-box">
        <input type="text" id="search" placeholder="🔍 ابحث عن مريض..." onkeyup="searchPatient()">
      </div>

      <div id="results"></div>



      <h1>لوحة التحكم</h1>

      <div class="section-heading">
        <h2>ملخص اليوم</h2>
        <span><?= date('d/m/Y') ?></span>
      </div>

      <div class="patient-overview">
        <div class="patient-total-card">
          <div>
            <span><?= $totalPatients ?></span>
            <p>إجمالي المرضى</p>
          </div>
          <div class="patient-icon">👥</div>
        </div>

        <div class="overview-note">
          <div>
            <strong>نظرة سريعة على حركة العيادة</strong><br>
            <span>نبذة مختصرة عن النشاط اليومي والشهري للعيادة من زيارات المرضى والمواعيد والعمليات.</span>
          </div>
          <a href="add-patient.php">➕ إضافة مريض</a>
        </div>
      </div>

      <section class="box" aria-label="System health">
        <h3><?= h(clinic_t('system_health')) ?></h3>
        <div class="health-grid">
          <div class="card"><span id="healthBackup"><?= h($latestBackupAt ?: clinic_t('no_backup')) ?></span>
            <p><?= h(clinic_t('last_local_backup')) ?></p>
          </div>
          <div class="card"><span id="healthConflicts"><?= $openSyncConflicts ?></span>
            <p><?= h(clinic_t('open_conflicts')) ?></p>
          </div>
          <div class="card"><span id="healthImages"><?= $pendingImageSync ?></span>
            <p><?= h(clinic_t('pending_images')) ?></p>
          </div>
        </div>
      </section>

      <!-- ===== Cards ===== -->
      <div class="cards-grid workload-cards">

        <div class="card visits-card">📅<span><?= $todayVisits ?></span>
          <p>زيارات اليوم</p>
          <small>تمت <?= $todayDoneVisits ?> / انتظار <?= $todayPendingVisits ?></small>
        </div>

        <div class="card followup-card">📲<span><?= $followups ?></span>
          <p>المراجعات + الزيارات المتوقعة اليوم</p>
          <small>خلال 7 أيام: <?= $upcomingFollowups ?></small>
        </div>


        <div class="card surgery-card">🏥<span><?= $monthOperations ?></span>
          <p>العمليات المنجزة هذا الشهر</p>
          <small>حسب جدول العمليات المنجزة</small>
        </div>

        <div class="card pending-card">⏳<span><?= $pendingOperations ?></span>
          <p>العمليات القادمة</p>
          <small>مواعيد قيد الانتظار</small>
        </div>

        <div class="card injection-card">💉<span><?= $monthInjections ?></span>
          <p>حقن هذا الشهر</p>
          <small><?= htmlspecialchars($topInjectionType) ?><?= $topInjectionCount ? " ({$topInjectionCount})" : "" ?></small>
        </div>

        <div class="card laser-card">🔦<span><?= $monthLasers ?></span>
          <p>ليزر هذا الشهر</p>
          <small><?= htmlspecialchars($topLaserType) ?><?= $topLaserCount ? " ({$topLaserCount})" : "" ?></small>
        </div>

      </div>

      <div class="section-heading">
        <h2>الإحصائيات</h2>
        <span>آخر نشاط العيادة</span>
      </div>

      <!-- ===== Analytics ===== -->
      <section class="analytics-grid">
        <div class="chart-card">
          <div class="chart-head">
            <h3>زيارات آخر 7 أيام عمل</h3>
            <span>Clinic days only</span>
          </div>
          <div class="chart-summary">
            <span>المجموع: <?= array_sum($dailyVisitCounts) ?></span>
            <span>الأعلى: <?= max($dailyVisitCounts ?: [0]) ?></span>
          </div>
          <div class="chart-wrap">
            <canvas id="dailyVisitsChart"></canvas>
          </div>
          <div class="chart-empty" id="dailyVisitsEmpty">لا توجد زيارات خلال الفترة</div>
        </div>

        <div class="chart-card">
          <div class="chart-head">
            <h3>عمليات آخر 6 أشهر</h3>
            <span>Surgeries</span>
          </div>
          <div class="chart-summary">
            <span>المجموع: <?= array_sum($sixMonthSurgeryCounts) ?></span>
            <span>الأعلى شهرياً: <?= max($sixMonthSurgeryCounts ?: [0]) ?></span>
          </div>
          <div class="chart-wrap">
            <canvas id="sixMonthSurgeriesChart"></canvas>
          </div>
          <div class="chart-empty" id="sixMonthSurgeriesEmpty">لا توجد عمليات خلال الفترة</div>
        </div>

        <div class="chart-card wide">
          <div class="chart-head">
            <h3>الحقن حسب النوع خلال آخر 6 أشهر</h3>
            <span>Injection types</span>
          </div>
          <div class="chart-summary">
            <span>المجموع: <?= array_sum($injectionTypeCounts) ?></span>
            <span>الأكثر: <?= htmlspecialchars($injectionTypeLabels[0] ?? 'لا يوجد') ?></span>
          </div>
          <div class="chart-wrap">
            <canvas id="injectionTypesChart"></canvas>
          </div>
          <div class="chart-empty" id="injectionTypesEmpty">لا توجد حقن خلال الفترة</div>
        </div>
      </section>

      <!-- ===== Upcoming Operations ===== -->
      <section class="box">
        <h3>المرضى الذين لديهم عمليات قادمة</h3>

        <table>
          <tr>
            <th>الاسم</th>
            <th>التاريخ</th>
            <th>العملية</th>
            <th>العين</th>
            <th>فتح</th>
          </tr>

          <?php while ($r = mysqli_fetch_assoc($upcoming)) { ?>
            <tr>
              <td><?= $r['full_name'] ?></td>
              <td><?= $r['date'] ?></td>
              <td><?= $r['surgery_type'] ?></td>
              <td><?= $r['eye'] ?></td>
              <td><a class="open-btn" href="patient-file.php?id=<?= $r['patient_id'] ?>">فتح</a></td>
            </tr>
          <?php } ?>

        </table>
        <a href="operation-by-date.php">عرض الكل →</a>

      </section>

      <!-- ===== Alerts ===== -->
      <section class="alerts">



        <a href="critical_patients.php" class="danger-card">
          🚨 المرضى الحرِجون


        </a>

        <?php if (empty($alerts)) { ?>
          <p>لا توجد تنبيهات حالياً</p>
        <?php } ?>

        <?php foreach ($alerts as $a) { ?>
          <div class="alert"><?= $a ?></div>
        <?php } ?>

      </section>

      <?php if ($nextPatientAlert): ?>
        <section class="next-patient-panel" id="nextPatientPanel">
          <div>
            <div class="next-patient-title">المريض القادم للطبيب</div>
            <div class="next-patient-meta">
              <?= h($nextPatientAlert['full_name']) ?>
              | القسم: <?= h($nextPatientAlert['queue'] ?? 'العيادة') ?>
              <?php if (!empty($nextPatientAlert['meta'])): ?>
                | تفاصيل: <?= h($nextPatientAlert['meta']) ?>
              <?php endif; ?>
              | أرسلها: <?= h($nextPatientAlert['notified_by'] ?? '-') ?>
              | الوقت: <?= h($nextPatientAlert['notified_at'] ?? '-') ?>
            </div>
          </div>
          <div class="next-patient-actions">
            <a class="open" href="patient-data.php?id=<?= (int) $nextPatientAlert['patient_id'] ?>">فتح ملف المريض</a>
            <a href="notify-next-patient.php?action=clear&back=dashboard.php">تم استدعاؤه / مسح التنبيه</a>
          </div>
        </section>
      <?php endif; ?>

    </main>

  </div>

  <script src="assets/lang.js" data-clinic-lang defer></script>
  <script>
    const dashboardCharts = {
      dailyVisits: {
        labels: <?= json_encode($dailyVisitLabels, JSON_UNESCAPED_UNICODE) ?>,
        values: <?= json_encode($dailyVisitCounts, JSON_UNESCAPED_UNICODE) ?>
      },
      surgeries: {
        labels: <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE) ?>,
        values: <?= json_encode($sixMonthSurgeryCounts, JSON_UNESCAPED_UNICODE) ?>
      },
      injectionTypes: {
        labels: <?= json_encode($injectionTypeLabels, JSON_UNESCAPED_UNICODE) ?>,
        values: <?= json_encode($injectionTypeCounts, JSON_UNESCAPED_UNICODE) ?>
      }
    };

    const chartColors = ["#2563eb", "#0f766e", "#f59e0b", "#dc2626", "#8b5cf6", "#0891b2", "#16a34a", "#db2777"];

    function chartTextColor() {
      return document.body.classList.contains("dark") ? "#cbd5e1" : "#334155";
    }

    function showEmpty(canvasId, emptyId, values) {
      const canvas = document.getElementById(canvasId);
      const empty = document.getElementById(emptyId);
      const hasData = values.some(value => Number(value) > 0);

      canvas.parentElement.style.display = hasData ? "block" : "none";
      empty.style.display = hasData ? "none" : "flex";
      return !hasData;
    }

    function setupCanvas(canvas) {
      const dpr = window.devicePixelRatio || 1;
      const rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      const ctx = canvas.getContext("2d");
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      return {
        ctx,
        width: rect.width,
        height: rect.height
      };
    }

    function roundedRect(ctx, x, y, width, height, radius) {
      if (ctx.roundRect) {
        ctx.roundRect(x, y, width, height, radius);
        return;
      }

      const r = Math.min(radius, Math.abs(width) / 2, Math.abs(height) / 2);
      ctx.moveTo(x + r, y);
      ctx.lineTo(x + width - r, y);
      ctx.quadraticCurveTo(x + width, y, x + width, y + r);
      ctx.lineTo(x + width, y + height - r);
      ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
      ctx.lineTo(x + r, y + height);
      ctx.quadraticCurveTo(x, y + height, x, y + height - r);
      ctx.lineTo(x, y + r);
      ctx.quadraticCurveTo(x, y, x + r, y);
    }

    function hexToRgba(hex, alpha) {
      const value = hex.replace("#", "");
      const bigint = parseInt(value, 16);
      const r = (bigint >> 16) & 255;
      const g = (bigint >> 8) & 255;
      const b = bigint & 255;
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    function drawLineChart(canvasId, emptyId, labels, values, color) {
      if (showEmpty(canvasId, emptyId, values)) return;

      const canvas = document.getElementById(canvasId);
      const {
        ctx,
        width,
        height
      } = setupCanvas(canvas);
      const max = Math.max(...values, 1);
      const padding = {
        top: 22,
        right: 22,
        bottom: 42,
        left: 38
      };
      const chartWidth = width - padding.left - padding.right;
      const chartHeight = height - padding.top - padding.bottom;
      const textColor = chartTextColor();
      const points = values.map((value, index) => {
        const x = padding.left + (labels.length === 1 ? chartWidth / 2 : (chartWidth / (labels.length - 1)) * index);
        const y = padding.top + chartHeight - (Number(value) / max) * chartHeight;
        return {
          x,
          y,
          value: Number(value),
          label: labels[index]
        };
      });

      ctx.clearRect(0, 0, width, height);
      ctx.font = "12px Cairo, Segoe UI, Arial";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";

      ctx.strokeStyle = document.body.classList.contains("dark") ? "rgba(148,163,184,.18)" : "rgba(100,116,139,.18)";
      ctx.lineWidth = 1;
      for (let i = 0; i <= 4; i++) {
        const y = padding.top + chartHeight - (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(width - padding.right, y);
        ctx.stroke();
      }

      const fillGradient = ctx.createLinearGradient(0, padding.top, 0, height - padding.bottom);
      fillGradient.addColorStop(0, hexToRgba(color, .22));
      fillGradient.addColorStop(1, "rgba(15, 118, 110, 0)");

      ctx.beginPath();
      points.forEach((point, index) => {
        if (index === 0) ctx.moveTo(point.x, point.y);
        else ctx.lineTo(point.x, point.y);
      });
      ctx.lineTo(points[points.length - 1].x, padding.top + chartHeight);
      ctx.lineTo(points[0].x, padding.top + chartHeight);
      ctx.closePath();
      ctx.fillStyle = fillGradient;
      ctx.fill();

      ctx.beginPath();
      points.forEach((point, index) => {
        if (index === 0) ctx.moveTo(point.x, point.y);
        else ctx.lineTo(point.x, point.y);
      });
      ctx.strokeStyle = color;
      ctx.lineWidth = 3;
      ctx.lineJoin = "round";
      ctx.lineCap = "round";
      ctx.stroke();

      points.forEach((point) => {
        ctx.fillStyle = document.body.classList.contains("dark") ? "#020617" : "#ffffff";
        ctx.strokeStyle = color;
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.arc(point.x, point.y, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();

        ctx.fillStyle = textColor;
        ctx.fillText(point.value, point.x, Math.max(12, point.y - 16));
        ctx.fillText(point.label, point.x, height - 18);
      });
    }

    function drawHorizontalBarChart(canvasId, emptyId, labels, values) {
      if (showEmpty(canvasId, emptyId, values)) return;

      const canvas = document.getElementById(canvasId);
      const {
        ctx,
        width,
        height
      } = setupCanvas(canvas);
      const max = Math.max(...values, 1);
      const textColor = chartTextColor();
      const rows = labels.length;
      const padding = {
        top: 12,
        right: 46,
        bottom: 12,
        left: 130
      };
      const rowHeight = Math.min(34, (height - padding.top - padding.bottom) / Math.max(rows, 1));
      const barMaxWidth = width - padding.left - padding.right;

      ctx.clearRect(0, 0, width, height);
      ctx.font = "12px Cairo, Segoe UI, Arial";
      ctx.textBaseline = "middle";

      labels.forEach((label, index) => {
        const y = padding.top + index * rowHeight + rowHeight / 2;
        const barWidth = (Number(values[index]) / max) * barMaxWidth;
        const color = chartColors[index % chartColors.length];

        ctx.fillStyle = textColor;
        ctx.textAlign = "right";
        ctx.fillText(label, padding.left - 12, y);

        ctx.fillStyle = document.body.classList.contains("dark") ? "rgba(148,163,184,.12)" : "rgba(148,163,184,.18)";
        ctx.beginPath();
        roundedRect(ctx, padding.left, y - 9, barMaxWidth, 18, 9);
        ctx.fill();

        ctx.fillStyle = color;
        ctx.beginPath();
        roundedRect(ctx, padding.left, y - 9, Math.max(3, barWidth), 18, 9);
        ctx.fill();

        ctx.fillStyle = textColor;
        ctx.textAlign = "left";
        ctx.fillText(values[index], padding.left + barMaxWidth + 12, y);
      });
    }

    function renderDashboardCharts() {
      drawLineChart("dailyVisitsChart", "dailyVisitsEmpty", dashboardCharts.dailyVisits.labels, dashboardCharts.dailyVisits.values, "#2563eb");
      drawLineChart("sixMonthSurgeriesChart", "sixMonthSurgeriesEmpty", dashboardCharts.surgeries.labels, dashboardCharts.surgeries.values, "#0f766e");
      drawHorizontalBarChart("injectionTypesChart", "injectionTypesEmpty", dashboardCharts.injectionTypes.labels, dashboardCharts.injectionTypes.values);
    }

    window.addEventListener("load", renderDashboardCharts);
    window.addEventListener("resize", renderDashboardCharts);

    /* Sidebar Toggle */
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const btn = document.querySelector(".toggle-sidebar");

      sidebar.classList.toggle("hidden");

      if (sidebar.classList.contains("hidden")) {
        btn.innerHTML = " ⬅️إظهار القائمة";
      } else {
        btn.innerHTML = " ➡️إخفاء القائمة";
      }
    }

    /* Dark Mode */
    const t = document.getElementById("themeToggle");
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
      t.textContent = "☀️";
    }
    t.onclick = () => {
      document.body.classList.toggle("dark");
      let d = document.body.classList.contains("dark");
      t.textContent = d ? "☀️" : "🌙";
      localStorage.setItem("theme", d ? "dark" : "light");
      renderDashboardCharts();
    };


    function searchPatient() {
      let q = document.getElementById("search").value;

      if (q.length < 2) {
        document.getElementById("results").innerHTML = "";
        return;
      }

      fetch("search-patient.php?q=" + q)
        .then(res => res.text())
        .then(data => {
          document.getElementById("results").innerHTML = data;
        });
    }

    function deletePatient(id) {

      if (confirm("⚠️ هل أنت متأكد من حذف هذا المريض؟")) {
        const body = new URLSearchParams({
          id_delete: String(id),
          csrf_token: <?= json_encode(clinic_csrf_token()) ?>
        });

        fetch("delete-patient.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
              "Accept": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            body: body.toString()
          })
          .then(res => res.json())
          .then(data => {
            if (!data.success) throw new Error(data.message || "تعذر حذف المريض");
            alert(data.message);
            document.getElementById("search").dispatchEvent(new Event('keyup'));
          })
          .catch(error => alert(error.message));
      }
    }

    async function refreshDashboardStatus() {
      try {
        const response = await fetch("dashboard-status.php", {
          headers: {
            Accept: "application/json"
          },
          cache: "no-store"
        });
        if (!response.ok) return;
        const status = await response.json();
        document.getElementById("healthBackup").textContent = status.latest_backup_at || "لا توجد نسخة";
        document.getElementById("healthConflicts").textContent = status.open_conflicts;
        document.getElementById("healthImages").textContent = status.pending_images;
      } catch (error) {
        // Keep the last visible values when the local server is temporarily busy.
      }
    }

    setInterval(refreshDashboardStatus, 60000);
  </script>

</body>

</html>