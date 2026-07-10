<?php

include "config.php";
include "auth.php";
include_once "clinic_helpers.php";

$isEnglish = clinic_language() === 'en';
$tr = static function (string $ar, string $en) use ($isEnglish): string {
  return $isEnglish ? $en : $ar;
};

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

$tomorrowFollowups = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM followups WHERE followup_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND status='pending'")
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

$totalReferredCases = 0;
$monthReferredCases = 0;
if (clinic_table_exists($con, 'referred_surgery_cases')) {
  $totalReferredCases = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM referred_surgery_cases"))['total'] ?? 0));
  $monthReferredCases = (int) ((mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) total FROM referred_surgery_cases
    WHERE MONTH(surgery_date)=MONTH(CURDATE())
      AND YEAR(surgery_date)=YEAR(CURDATE())
  "))['total'] ?? 0));
}

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
$topLaserType = $topLaserRow['laser_type'] ?? $tr('لا يوجد', 'None');
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
$topInjectionType = $topInjectionRow['injection_type'] ?? $tr('لا يوجد', 'None');
$topInjectionCount = (int)($topInjectionRow['total'] ?? 0);

// عمليات قادمة
$pendingOperations = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM surgery_appointment 
    WHERE status='pending' AND date >= CURDATE()
  ")
)['total'] ?? 0;

// مقارنات يومية وشهرية للبطاقات
$yesterdayVisits = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"))['total'] ?? 0));
$yesterdayDoneVisits = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND is_done = 1"))['total'] ?? 0));
$yesterdayPendingVisits = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND is_done = 0"))['total'] ?? 0));
$yesterdayFollowups = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM followups WHERE followup_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status='pending'"))['total'] ?? 0));
$yesterdayExpectedVisits = (int) ((mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM expected_appointments WHERE expected_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status='expected'"))['total'] ?? 0));

$previousMonthOperations = (int) ((mysqli_fetch_assoc(mysqli_query($con, "
  SELECT COUNT(*) total FROM surgery
  WHERE MONTH(date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    AND YEAR(date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
"))['total'] ?? 0));

$previousMonthInjections = (int) ((mysqli_fetch_assoc(mysqli_query($con, "
  SELECT COUNT(*) total FROM injection
  WHERE MONTH(date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    AND YEAR(date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
"))['total'] ?? 0));

$previousMonthLasers = (int) ((mysqli_fetch_assoc(mysqli_query($con, "
  SELECT COUNT(*) total FROM laser
  WHERE MONTH(date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    AND YEAR(date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
"))['total'] ?? 0));

$previousMonthReferredCases = 0;
if (clinic_table_exists($con, 'referred_surgery_cases')) {
  $previousMonthReferredCases = (int) ((mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) total FROM referred_surgery_cases
    WHERE MONTH(surgery_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
      AND YEAR(surgery_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
  "))['total'] ?? 0));
}

$buildTrendMeta = static function (int $current, int $previous, string $compareText) use ($tr): array {
  if ($current === $previous) {
    return ['class' => 'trend-flat', 'label' => $tr('• بدون تغيير', '• No change') . " $compareText"];
  }

  if ($previous <= 0) {
    return [
      'class' => $current > 0 ? 'trend-up' : 'trend-flat',
      'label' => $current > 0
        ? $tr('↗ جديد', '↗ New') . " $compareText"
        : $tr('• بدون تغيير', '• No change') . " $compareText"
    ];
  }

  $delta = $current - $previous;
  $pct = (int) round((abs($delta) / $previous) * 100);

  if ($delta > 0) {
    return ['class' => 'trend-up', 'label' => "↗ +{$pct}% $compareText"];
  }

  return ['class' => 'trend-down', 'label' => "↘ -{$pct}% $compareText"];
};

$compareDayText = $tr('عن أمس', 'vs yesterday');
$compareMonthText = $tr('عن الشهر الماضي', 'vs last month');

$visitsTrend = $buildTrendMeta((int) $todayVisits, $yesterdayVisits, $compareDayText);
$pendingTrend = $buildTrendMeta((int) $todayPendingVisits, $yesterdayPendingVisits, $compareDayText);
$doneTrend = $buildTrendMeta((int) $todayDoneVisits, $yesterdayDoneVisits, $compareDayText);
$followupTrend = $buildTrendMeta((int) $followups, $yesterdayFollowups, $compareDayText);
$expectedTrend = $buildTrendMeta((int) $expectedVisitsToday, $yesterdayExpectedVisits, $compareDayText);

$operationsTrend = $buildTrendMeta((int) $monthOperations, $previousMonthOperations, $compareMonthText);
$injectionsTrend = $buildTrendMeta((int) $monthInjections, $previousMonthInjections, $compareMonthText);
$lasersTrend = $buildTrendMeta((int) $monthLasers, $previousMonthLasers, $compareMonthText);
$referredTrend = $buildTrendMeta((int) $monthReferredCases, $previousMonthReferredCases, $compareMonthText);

$workloadPressureRate = $todayVisits > 0 ? (int) round(($todayPendingVisits / max(1, $todayVisits)) * 100) : 0;
$workloadPressureRate = max(0, min(100, $workloadPressureRate));
$workloadPressureClass = $workloadPressureRate > 45 ? 'pressure-high' : ($workloadPressureRate >= 25 ? 'pressure-medium' : 'pressure-low');

$todayCompletionRate = $todayVisits > 0 ? (int) round(($todayDoneVisits / max(1, $todayVisits)) * 100) : 0;
$todayCompletionRate = max(0, min(100, $todayCompletionRate));

$followupLoadRate = ($followups + $expectedVisitsToday) > 0
  ? (int) round(($followups / max(1, ($followups + $expectedVisitsToday))) * 100)
  : 0;

$expectedLoadRate = ($followups + $expectedVisitsToday) > 0
  ? (int) round(($expectedVisitsToday / max(1, ($followups + $expectedVisitsToday))) * 100)
  : 0;

$monthMetricMax = max(1, (int) $monthOperations, (int) $monthInjections, (int) $monthLasers, (int) $monthReferredCases);
$monthOperationsRate = (int) round(($monthOperations / $monthMetricMax) * 100);
$monthInjectionsRate = (int) round(($monthInjections / $monthMetricMax) * 100);
$monthLasersRate = (int) round(($monthLasers / $monthMetricMax) * 100);
$monthReferredRate = (int) round(($monthReferredCases / $monthMetricMax) * 100);

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
$unspecifiedInjectionType = $tr('غير محدد', 'Unspecified');
$injectionTypeResult = mysqli_query($con, "
  SELECT COALESCE(NULLIF(TRIM(injection_type), ''), '" . mysqli_real_escape_string($con, $unspecifiedInjectionType) . "') injection_type, COUNT(*) total
  FROM injection
  WHERE date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
  GROUP BY COALESCE(NULLIF(TRIM(injection_type), ''), '" . mysqli_real_escape_string($con, $unspecifiedInjectionType) . "')
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
$isSecretaryUser = strtolower((string) ($_SESSION['role'] ?? '')) === 'secretary';
$tomorrowFollowupDate = date('Y-m-d', strtotime('+1 day'));
$tomorrowFollowupLink = 'followups.php?date_from=' . urlencode($tomorrowFollowupDate) . '&date_to=' . urlencode($tomorrowFollowupDate);
// حالات حرجة 
$critical = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM add_patient 
  WHERE is_critical=1 
"));
if ($critical > 0) {
  $criticalText = $tr("يوجد $critical حالات حرجة", "There are $critical critical cases");
  $alerts[] = "<div class='alert alert-danger'>🚨 " . h($criticalText) . "</div>";
}

// عمليات متأخرة
$late = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date < CURDATE()
"));
if ($late > 0) // متأخرة
  $alerts[] = "<div class='alert alert-danger'>🔴 "
    . h($tr("يوجد $late عملية متأخرة", "There are $late overdue operations"))
    . "</div>";

// عمليات قريبة
$soon = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 5 DAY)
"));
if ($soon > 0) // قريبة
  $alerts[] = "<div class='alert alert-warning'>⚠️ "
    . h($tr("يوجد $soon عمليات خلال 5 أيام", "There are $soon operations within 5 days"))
    . "</div>";

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
  $alerts[] = "<div class='alert alert-danger'>⛔ "
    . h(clinic_t('alert_open_sync_conflicts', ['count' => $openSyncConflicts]))
    . " - <a href='sync_conflicts.php'>" . h(clinic_t('manage_conflicts')) . "</a></div>";
}

$backupAgeHours = null;
if ($latestBackupAt && !empty($backupFiles)) {
  $latestBackupTimestamp = filemtime($backupFiles[0]);
  if ($latestBackupTimestamp) {
    $backupAgeHours = (int) floor((time() - $latestBackupTimestamp) / 3600);
  }
}

if ($backupAgeHours === null) {
  $alerts[] = "<div class='alert alert-danger'>🧯 " . h(clinic_t('alert_no_recent_backup')) . "</div>";
} elseif ($backupAgeHours >= 48) {
  $alerts[] = "<div class='alert alert-warning'>🕒 " . h(clinic_t('alert_backup_age_hours', ['hours' => $backupAgeHours])) . "</div>";
}

if ($todayVisits > 0 && $todayPendingVisits > $todayDoneVisits) {
  $alerts[] = "<div class='alert alert-warning'>⌛ " . h(clinic_t('alert_waiting_higher_than_done')) . "</div>";
}

if ($pendingImageSync >= 20) {
  $alerts[] = "<div class='alert alert-warning'>🖼️ " . h(clinic_t('alert_pending_images_count', ['count' => $pendingImageSync])) . "</div>";
}

$alertsSummary = [
  'critical' => (int) $critical,
  'late' => (int) $late,
  'soon' => (int) $soon,
  'openSyncConflicts' => (int) $openSyncConflicts,
  'pendingImageSync' => (int) $pendingImageSync,
  'tomorrowFollowups' => (int) $tomorrowFollowups,
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>لوحة التحكم | عيادة الدكتور حيدر صباح الربيعي</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
  <link rel="stylesheet" href="assets/branding/branding.css">

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

  .header-identity {
    display: inline-flex;
    align-items: center;
    gap: 10px;
  }

  .header-identity .meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .header-identity .meta strong {
    color: var(--text);
    font-size: 15px;
    font-weight: 900;
    line-height: 1.2;
  }

  .header-identity .meta span {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
  }

  .header-user {
    font-weight: 800;
    color: var(--text);
  }

  /* ===== Layout ===== */
  .layout {
    display: flex;
    min-height: calc(100vh - 60px);
  }


  /* ===== Sidebar ===== */
  .sidebar {
    width: 260px;
    background: var(--card);
    border-left: 1px solid rgba(148, 163, 184, .26);
    box-shadow: var(--shadow);
    padding: 16px;
    transition: .3s;
    overflow-y: auto;
  }

  .sidebar.hidden {
    width: 0;
    padding: 0;
    overflow: hidden;
  }

  .sidebar h3 {
    color: var(--primary);
    margin: 0;
    font-weight: bold;
    font-size: 22px;
  }

  .sidebar-brand {
    border: 1px solid rgba(37, 99, 235, .2);
    border-radius: 14px;
    padding: 12px;
    margin-bottom: 12px;
    background: linear-gradient(135deg, rgba(37, 99, 235, .14), rgba(15, 118, 110, .1));
  }

  .sidebar-meta {
    margin-top: 6px;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
  }

  .sidebar-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin: 12px 0;
  }

  .kpi-chip {
    border: 1px solid rgba(148, 163, 184, .32);
    border-radius: 10px;
    padding: 8px;
    background: rgba(248, 250, 252, .9);
    text-align: center;
  }

  .kpi-chip strong {
    display: block;
    color: var(--primary);
    font-size: 18px;
  }

  .kpi-chip span {
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
  }

  .menu-group {
    margin-bottom: 12px;
    border: 1px solid rgba(148, 163, 184, .28);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(37, 99, 235, .03);
  }

  .menu-title,
  .menu-group summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    padding: 12px 14px;
    font-weight: bold;
    font-size: 16px;
    color: var(--text);
    background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(15, 118, 110, .08));
    border: 0;
    list-style: none;
    cursor: pointer;
  }

  .menu-group summary::-webkit-details-marker {
    display: none;
  }

  .menu-group summary::after {
    content: "▸";
    font-size: 13px;
    color: #475569;
    transition: transform .2s ease;
  }

  .menu-group[open] summary::after {
    transform: rotate(90deg);
  }

  .menu-links {
    padding: 8px;
  }

  .menu-group a {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 6px;
    text-decoration: none;
    color: var(--text);
    transition: .2s;
  }

  .menu-group a.is-current {
    background: linear-gradient(135deg, rgba(37, 99, 235, .22), rgba(15, 118, 110, .16));
    border: 1px solid rgba(37, 99, 235, .45);
    color: #1e3a8a;
    font-weight: 900;
    box-shadow: 0 8px 16px rgba(37, 99, 235, .15);
  }

  .menu-group a:hover {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    transform: translateX(-5px);
  }

  .menu-group a.danger:hover {
    background: linear-gradient(135deg, var(--danger), #ef4444);
  }

  body.dark .menu-group {
    border-color: rgba(96, 165, 250, .22);
    background: rgba(2, 6, 23, .55);
  }

  body.dark .sidebar {
    border-left-color: rgba(96, 165, 250, .2);
  }

  body.dark .sidebar-brand {
    border-color: rgba(96, 165, 250, .28);
    background: linear-gradient(135deg, rgba(30, 64, 175, .34), rgba(13, 148, 136, .22));
  }

  body.dark .sidebar-meta {
    color: #cbd5e1;
  }

  body.dark .kpi-chip {
    border-color: rgba(96, 165, 250, .24);
    background: rgba(15, 23, 42, .86);
  }

  body.dark .kpi-chip span {
    color: #94a3b8;
  }

  body.dark .menu-title,
  body.dark .menu-group summary {
    background: linear-gradient(135deg, rgba(96, 165, 250, .18), rgba(45, 212, 191, .12));
  }

  body.dark .menu-group a.is-current {
    color: #dbeafe;
    border-color: rgba(96, 165, 250, .65);
    background: linear-gradient(135deg, rgba(30, 64, 175, .48), rgba(13, 148, 136, .3));
    box-shadow: 0 8px 16px rgba(15, 23, 42, .42);
  }

  body.dark .header-identity .meta span {
    color: #a8bdd1;
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

  .quick-links {
    margin-top: -8px;
    margin-bottom: 14px;
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .quick-link {
    display: inline-flex;
    align-items: center;
    min-height: 38px;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    background: var(--card);
    color: var(--text);
    text-decoration: none;
    font-weight: 800;
    box-shadow: var(--shadow);
  }

  .quick-link:hover {
    background: rgba(37, 99, 235, .1);
    color: var(--primary);
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

  .workload-panel {
    margin-bottom: 24px;
  }

  .workload-stack {
    display: grid;
    gap: 16px;
  }

  .workload-block {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid rgba(37, 99, 235, .12);
    box-shadow: var(--shadow);
    padding: 14px;
  }

  .workload-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
  }

  .workload-head h3 {
    margin: 0;
    color: var(--text);
    font-size: 17px;
  }

  .workload-head span {
    color: var(--primary);
    font-size: 12px;
    font-weight: 800;
  }

  .workload-grid {
    display: grid;
    gap: 12px;
  }

  .workload-grid.today-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .workload-grid.month-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .workload-card {
    background: linear-gradient(145deg, var(--card), #f8fafc);
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 12px;
    padding: 14px;
    min-height: 152px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .workload-card.is-priority {
    border-color: rgba(220, 38, 38, .36);
    box-shadow: 0 10px 22px rgba(220, 38, 38, .12);
  }

  .workload-label {
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
  }

  .workload-value {
    color: var(--text);
    font-size: 30px;
    font-weight: 900;
    line-height: 1.1;
  }

  .workload-sub {
    color: #475569;
    font-size: 12px;
    font-weight: 700;
  }

  .workload-progress {
    margin-top: auto;
    height: 8px;
    background: rgba(148, 163, 184, .24);
    border-radius: 999px;
    overflow: hidden;
  }

  .workload-progress>span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
  }

  .workload-progress.warn>span {
    background: linear-gradient(135deg, #f59e0b, #f97316);
  }

  .workload-progress.danger>span {
    background: linear-gradient(135deg, #dc2626, #ef4444);
  }

  .workload-trend {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    width: fit-content;
  }

  .trend-up {
    background: rgba(22, 163, 74, .14);
    color: #166534;
  }

  .trend-down {
    background: rgba(220, 38, 38, .14);
    color: #991b1b;
  }

  .trend-flat {
    background: rgba(100, 116, 139, .16);
    color: #334155;
  }

  .pressure-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    width: fit-content;
  }

  .pressure-low {
    background: rgba(22, 163, 74, .14);
    color: #166534;
  }

  .pressure-medium {
    background: rgba(245, 158, 11, .18);
    color: #92400e;
  }

  .pressure-high {
    background: rgba(220, 38, 38, .16);
    color: #991b1b;
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

  body.dark .workload-block {
    background: linear-gradient(145deg, var(--card), #020617);
    border-color: rgba(96, 165, 250, .18);
  }

  body.dark .workload-card {
    background: linear-gradient(145deg, var(--card), #020617);
    border-color: rgba(96, 165, 250, .2);
  }

  body.dark .workload-label,
  body.dark .workload-sub {
    color: #94a3b8;
  }

  body.dark .workload-value {
    color: #e2e8f0;
  }

  body.dark .trend-up {
    color: #86efac;
    background: rgba(22, 163, 74, .25);
  }

  body.dark .trend-down {
    color: #fca5a5;
    background: rgba(220, 38, 38, .26);
  }

  body.dark .trend-flat {
    color: #cbd5e1;
    background: rgba(71, 85, 105, .4);
  }

  body.dark .pressure-low {
    color: #86efac;
    background: rgba(22, 163, 74, .25);
  }

  body.dark .pressure-medium {
    color: #fcd34d;
    background: rgba(245, 158, 11, .3);
  }

  body.dark .pressure-high {
    color: #fca5a5;
    background: rgba(220, 38, 38, .28);
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
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
    align-items: stretch;
  }

  .alerts-head {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
  }

  .alert-stat {
    background: var(--card);
    border: 1px solid rgba(37, 99, 235, .14);
    border-radius: 12px;
    box-shadow: var(--shadow);
    padding: 10px 12px;
  }

  .alert-stat strong {
    display: block;
    color: var(--text);
    font-size: 20px;
  }

  .alert-stat span {
    color: #64748b;
    font-weight: 800;
    font-size: 12px;
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
    width: 100%;
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

  .secretary-alert-banner {
    margin: 14px 0 12px;
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    border: 1px solid rgba(37, 99, 235, 0.28);
    background: linear-gradient(120deg, rgba(37, 99, 235, 0.12), rgba(14, 165, 233, 0.12));
    box-shadow: var(--shadow);
  }

  .secretary-alert-banner.is-urgent {
    border-color: rgba(220, 38, 38, 0.48);
    background: linear-gradient(120deg, rgba(248, 113, 113, 0.2), rgba(245, 158, 11, 0.18));
    animation: secretaryPulse 1.4s ease-in-out infinite;
  }

  .secretary-alert-banner.is-calm {
    border-color: rgba(22, 163, 74, 0.35);
    background: linear-gradient(120deg, rgba(22, 163, 74, 0.14), rgba(16, 185, 129, 0.12));
  }

  .secretary-alert-text strong {
    display: block;
    font-size: 16px;
    color: var(--text);
  }

  .secretary-alert-text span {
    display: block;
    margin-top: 2px;
    font-size: 13px;
    font-weight: 800;
    color: #475569;
  }

  .secretary-alert-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #0f766e);
    box-shadow: var(--shadow);
  }

  .secretary-alert-banner.is-urgent .secretary-alert-btn {
    background: linear-gradient(135deg, #dc2626, #ea580c);
  }

  body.dark .secretary-alert-banner {
    border-color: rgba(96, 165, 250, 0.34);
    background: linear-gradient(120deg, rgba(30, 64, 175, 0.28), rgba(14, 116, 144, 0.2));
  }

  body.dark .secretary-alert-banner.is-urgent {
    border-color: rgba(248, 113, 113, 0.55);
    background: linear-gradient(120deg, rgba(127, 29, 29, 0.46), rgba(154, 52, 18, 0.34));
  }

  body.dark .secretary-alert-banner.is-calm {
    border-color: rgba(74, 222, 128, 0.4);
    background: linear-gradient(120deg, rgba(5, 46, 22, 0.55), rgba(6, 78, 59, 0.45));
  }

  body.dark .secretary-alert-text span {
    color: #cbd5e1;
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

  body.dark #results,
  body.dark .alert-stat {
    border-color: rgba(96, 165, 250, .18);
    background: linear-gradient(145deg, var(--card), #020617);
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

    .workload-grid.today-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .workload-grid.month-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .sidebar-kpis {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .overview-note {
      align-items: stretch;
      flex-direction: column;
    }

    .workload-grid.today-grid,
    .workload-grid.month-grid {
      grid-template-columns: 1fr;
    }

    .workload-card {
      min-height: 140px;
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

  @keyframes secretaryPulse {
    0% {
      box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.28), var(--shadow);
    }

    70% {
      box-shadow: 0 0 0 14px rgba(220, 38, 38, 0), var(--shadow);
    }

    100% {
      box-shadow: 0 0 0 0 rgba(220, 38, 38, 0), var(--shadow);
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

    <div class="header-identity">
      <img class="clinic-logo-mark" src="assets/branding/logo-mark.svg" alt="شعار العيادة">
      <div class="meta">
        <strong>عيادة الدكتور حيدر صباح الربيعي</strong>
        <span class="header-user">مرحبا <?= $_SESSION['name'] ?> 👋</span>
      </div>
    </div>
  </header>


  <div class="layout">
    <!-- ===== Sidebar ===== -->
    <aside class="sidebar hidden" id="sidebar">
      <div class="sidebar-brand">
        <div class="brand-with-logo">
          <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
          <div class="brand-text">
            <span class="brand-title">عيادة الدكتور حيدر صباح الربيعي</span>
            <span class="brand-subtitle">لوحة التنقل السريع</span>
          </div>
        </div>
        <div class="sidebar-meta">وصول مباشر لأهم صفحات النظام اليومية</div>
      </div>

      <div class="sidebar-kpis" aria-label="ملخص سريع">
        <div class="kpi-chip">
          <strong><?= (int) $todayVisits ?></strong>
          <span>زيارات اليوم</span>
        </div>
        <div class="kpi-chip">
          <strong><?= (int) $todayPendingVisits ?></strong>
          <span>قيد الانتظار</span>
        </div>
        <div class="kpi-chip">
          <strong><?= (int) $followups ?></strong>
          <span>متابعات اليوم</span>
        </div>
        <div class="kpi-chip">
          <strong><?= (int) $pendingOperations ?></strong>
          <span>عمليات قادمة</span>
        </div>
      </div>

      <div class="menu-group">
        <div class="menu-title">🏠 رئيسية</div>
        <div class="menu-links">
          <a href="dashboard.php">لوحة التحكم</a>
        </div>
      </div>

      <details class="menu-group" data-menu-key="patients" open>
        <summary>👤 المرضى</summary>
        <div class="menu-links">
          <a href="add-patient.php">إضافة مريض</a>
          <a href="main.php">بيانات المرضى</a>
          <a href="add-referred-case.php">إضافة حالة محولة</a>
          <a href="referred-cases.php">الحالات المحولة</a>
          <a href="archived-patients.php">أرشيف المرضى</a>
          <a href="data-quality.php">جودة البيانات</a>
          <a href="followups.php">المتابعة</a>
        </div>
      </details>


      <details class="menu-group" data-menu-key="appointments">
        <summary>📅 المواعيد</summary>
        <div class="menu-links">
          <a href="work-queue.php">قائمة عمل اليوم</a>
          <a href="visits.php">زيارات اليوم</a>
          <a href="followup-appointment.php">إعطاء موعد مراجعة</a>
          <a href="import_expected.php">استيراد المواعيد</a>
          <a href="expected_appointments.php">المواعيد المتوقعة</a>
        </div>

      </details>

      <details class="menu-group" data-menu-key="operations">
        <summary>💉 العمليات</summary>
        <div class="menu-links">
          <a href="operation-by-date.php">مواعيد العمليات</a>
          <a href="confirmed-list.php">قوائم العمليات</a>
          <a href="import_surgery_excel.php">استيراد العمليات</a>
        </div>
      </details>

      <details class="menu-group" data-menu-key="quick-actions">
        <summary>⚡ إجراءات سريعة</summary>
        <div class="menu-links">
          <a href="add-patient.php">➕ إضافة مريض جديد</a>
          <a href="procedure-entries.php">🧾 إدخال إجراءات اليوم</a>
          <a href="patient_reports.php">📄 تقارير المرضى</a>
          <a href="sync_conflicts.php">🔁 تعارضات المزامنة</a>
        </div>
      </details>


      <details class="menu-group" data-menu-key="system">
        <summary>⚙️ النظام</summary>
        <div class="menu-links">
          <a href="treatment-types.php">إدارة الاجراءات</a>
          <a href="reports.php">التقارير</a>
          <a href="common-medicines.php">إدارة الأدوية</a>
          <a href="treatment-templates.php">قوالب العلاج</a>
          <a href="staff-messages.php">رسائل داخلية<?php if ($unreadStaffMessages > 0): ?> (<?= $unreadStaffMessages ?>)<?php endif; ?></a>
          <a href="audit-log.php">سجل العمليات</a>
          <a href="settings.php">الإعدادات</a>
          <a href="logout.php" class="danger">تسجيل الخروج</a>
        </div>
      </details>
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

      <?php if ($isSecretaryUser): ?>
        <section
          class="secretary-alert-banner <?= ((int) $tomorrowFollowups > 0) ? 'is-urgent' : 'is-calm' ?>"
          id="secretaryTomorrowAlert"
          data-tomorrow-count="<?= (int) $tomorrowFollowups ?>">
          <div class="secretary-alert-text">
            <strong>📣 <?= h(clinic_t('secretary_tomorrow_alert_title')) ?></strong>
            <span>
              <?php if ((int) $tomorrowFollowups > 0): ?>
                <?= h(clinic_t('secretary_tomorrow_alert_has', ['count' => (int) $tomorrowFollowups, 'date' => $tomorrowFollowupDate])) ?>
              <?php else: ?>
                <?= h(clinic_t('secretary_tomorrow_alert_none', ['date' => $tomorrowFollowupDate])) ?>
              <?php endif; ?>
            </span>
          </div>
          <a class="secretary-alert-btn" href="<?= h($tomorrowFollowupLink) ?>"><?= h(clinic_t('open_tomorrow_followups')) ?></a>
        </section>
      <?php endif; ?>

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
          <a href="add-patient.php">➕ إضافة مريض </a>
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

      <!-- ===== Workload ===== -->
      <section class="workload-panel" aria-label="Workload cards">
        <div class="workload-stack">
          <div class="workload-block">
            <div class="workload-head">
              <h3>ملخص اليوم التشغيلي</h3>
              <span>مقارنة مباشرة مع أمس</span>
            </div>
            <div class="workload-grid today-grid">
              <article class="workload-card">
                <div class="workload-label">زيارات اليوم</div>
                <div class="workload-value"><?= (int) $todayVisits ?></div>
                <div class="workload-sub">تمت <?= (int) $todayDoneVisits ?> / انتظار <?= (int) $todayPendingVisits ?></div>
                <div class="workload-trend <?= $visitsTrend['class'] ?>"><?= h($visitsTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= $todayCompletionRate ?>%"></span></div>
              </article>

              <article class="workload-card is-priority">
                <div class="workload-label">ضغط اليوم</div>
                <div class="workload-value"><?= (int) $workloadPressureRate ?>%</div>
                <div class="workload-sub">نسبة قيد الانتظار من إجمالي زيارات اليوم</div>
                <div class="pressure-badge <?= h($workloadPressureClass) ?>">
                  <?= $workloadPressureRate > 45 ? 'مرتفع' : ($workloadPressureRate >= 25 ? 'متوسط' : 'منخفض') ?>
                </div>
                <div class="workload-progress <?= $workloadPressureRate > 45 ? 'danger' : ($workloadPressureRate >= 25 ? 'warn' : '') ?>"><span style="width: <?= $workloadPressureRate ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">المعاينات المنجزة</div>
                <div class="workload-value"><?= (int) $todayDoneVisits ?></div>
                <div class="workload-sub">نسبة الإنجاز: <?= (int) $todayCompletionRate ?>%</div>
                <div class="workload-trend <?= $doneTrend['class'] ?>"><?= h($doneTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= $todayCompletionRate ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">قيد الانتظار</div>
                <div class="workload-value"><?= (int) $todayPendingVisits ?></div>
                <div class="workload-sub">الهدف: إبقاءها أقل من 25% من الزيارات</div>
                <div class="workload-trend <?= $pendingTrend['class'] ?>"><?= h($pendingTrend['label']) ?></div>
                <div class="workload-progress warn"><span style="width: <?= $workloadPressureRate ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">متابعات اليوم</div>
                <div class="workload-value"><?= (int) $followups ?></div>
                <div class="workload-sub">خلال 7 أيام: <?= (int) $upcomingFollowups ?></div>
                <div class="workload-trend <?= $followupTrend['class'] ?>"><?= h($followupTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $followupLoadRate)) ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">المواعيد المتوقعة اليوم</div>
                <div class="workload-value"><?= (int) $expectedVisitsToday ?></div>
                <div class="workload-sub">خلال 7 أيام: <?= (int) $expectedVisitsUpcoming ?></div>
                <div class="workload-trend <?= $expectedTrend['class'] ?>"><?= h($expectedTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $expectedLoadRate)) ?>%"></span></div>
              </article>
            </div>
          </div>

          <div class="workload-block">
            <div class="workload-head">
              <h3>ملخص هذا الشهر</h3>
              <span>مقارنة مع الشهر الماضي</span>
            </div>
            <div class="workload-grid month-grid">
              <article class="workload-card">
                <div class="workload-label">العمليات المنجزة</div>
                <div class="workload-value"><?= (int) $monthOperations ?></div>
                <div class="workload-sub">حسب جدول العمليات المنجزة</div>
                <div class="workload-trend <?= $operationsTrend['class'] ?>"><?= h($operationsTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $monthOperationsRate)) ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">الحقن</div>
                <div class="workload-value"><?= (int) $monthInjections ?></div>
                <div class="workload-sub"><?= htmlspecialchars($topInjectionType) ?><?= $topInjectionCount ? " ({$topInjectionCount})" : "" ?></div>
                <div class="workload-trend <?= $injectionsTrend['class'] ?>"><?= h($injectionsTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $monthInjectionsRate)) ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">الليزر</div>
                <div class="workload-value"><?= (int) $monthLasers ?></div>
                <div class="workload-sub"><?= htmlspecialchars($topLaserType) ?><?= $topLaserCount ? " ({$topLaserCount})" : "" ?></div>
                <div class="workload-trend <?= $lasersTrend['class'] ?>"><?= h($lasersTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $monthLasersRate)) ?>%"></span></div>
              </article>

              <article class="workload-card">
                <div class="workload-label">الحالات المحولة</div>
                <div class="workload-value"><?= (int) $monthReferredCases ?></div>
                <div class="workload-sub">الإجمالي: <?= (int) $totalReferredCases ?></div>
                <div class="workload-trend <?= $referredTrend['class'] ?>"><?= h($referredTrend['label']) ?></div>
                <div class="workload-progress"><span style="width: <?= max(0, min(100, (int) $monthReferredRate)) ?>%"></span></div>
              </article>
            </div>
          </div>
        </div>
      </section>

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
      <div class="alerts-head" aria-label="ملخص التنبيهات">
        <div class="alert-stat"><strong><?= (int) $alertsSummary['critical'] ?></strong><span><?= h(clinic_t('critical_cases')) ?></span></div>
        <div class="alert-stat"><strong><?= (int) $alertsSummary['late'] ?></strong><span><?= h(clinic_t('late_operations')) ?></span></div>
        <div class="alert-stat"><strong><?= (int) $alertsSummary['soon'] ?></strong><span><?= h(clinic_t('operations_in_5_days')) ?></span></div>
        <div class="alert-stat"><strong><?= (int) $alertsSummary['openSyncConflicts'] ?></strong><span><?= h(clinic_t('open_sync_conflicts_label')) ?></span></div>
        <div class="alert-stat"><strong><?= (int) $alertsSummary['pendingImageSync'] ?></strong><span><?= h(clinic_t('pending_sync_images_label')) ?></span></div>
        <?php if ($isSecretaryUser): ?>
          <div class="alert-stat"><strong><?= (int) $alertsSummary['tomorrowFollowups'] ?></strong><span><?= h(clinic_t('tomorrow_followups_label')) ?></span></div>
        <?php endif; ?>
      </div>

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

    function setupSidebarAccordion() {
      const groups = Array.from(document.querySelectorAll("#sidebar details.menu-group[data-menu-key]"));
      if (!groups.length) return;

      const storageKey = "dashboard_sidebar_open_group";
      const saved = localStorage.getItem(storageKey);
      const defaultGroup = groups.find(group => group.hasAttribute("open"));

      groups.forEach(group => {
        group.open = false;
      });

      const initialGroup = groups.find(group => group.dataset.menuKey === saved) || defaultGroup || groups[0];
      if (initialGroup) initialGroup.open = true;

      groups.forEach(group => {
        group.addEventListener("toggle", () => {
          if (!group.open) return;

          groups.forEach(other => {
            if (other !== group) other.open = false;
          });

          if (group.dataset.menuKey) {
            localStorage.setItem(storageKey, group.dataset.menuKey);
          }
        });
      });
    }

    function markCurrentSidebarLink() {
      const currentPath = window.location.pathname.split("/").pop().toLowerCase();
      const links = Array.from(document.querySelectorAll("#sidebar a[href]"));

      links.forEach(link => {
        const href = (link.getAttribute("href") || "").split("?")[0].toLowerCase();
        if (!href) return;
        if (href === currentPath) {
          link.classList.add("is-current");
        }
      });
    }

    function updateSidebarToggleLabel() {
      const sidebar = document.getElementById("sidebar");
      const btn = document.querySelector(".toggle-sidebar");
      if (!sidebar || !btn) return;
      btn.textContent = sidebar.classList.contains("hidden") ? "➡️ إظهار القائمة" : "⬅️ إخفاء القائمة";
    }

    function restoreSidebarState() {
      const sidebar = document.getElementById("sidebar");
      if (!sidebar) return;
      const saved = localStorage.getItem("dashboard_sidebar_state");
      if (saved === "show") {
        sidebar.classList.remove("hidden");
      }
      updateSidebarToggleLabel();
    }

    window.addEventListener("load", renderDashboardCharts);
    window.addEventListener("load", setupSidebarAccordion);
    window.addEventListener("load", markCurrentSidebarLink);
    window.addEventListener("load", restoreSidebarState);
    window.addEventListener("resize", renderDashboardCharts);

    /* Sidebar Toggle */
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      if (!sidebar) return;

      sidebar.classList.toggle("hidden");
      localStorage.setItem("dashboard_sidebar_state", sidebar.classList.contains("hidden") ? "hidden" : "show");
      updateSidebarToggleLabel();
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

    function playSecretaryAlertSound() {
      const audioContext = new(window.AudioContext || window.webkitAudioContext)();
      const tones = [880, 740, 980];

      tones.forEach((frequency, index) => {
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        const startAt = audioContext.currentTime + index * 0.17;
        const stopAt = startAt + 0.13;

        osc.type = "triangle";
        osc.frequency.setValueAtTime(frequency, startAt);
        gain.gain.setValueAtTime(0.0001, startAt);
        gain.gain.exponentialRampToValueAtTime(0.18, startAt + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.0001, stopAt);

        osc.connect(gain);
        gain.connect(audioContext.destination);
        osc.start(startAt);
        osc.stop(stopAt);
      });

      setTimeout(() => {
        audioContext.close().catch(() => {});
      }, 900);
    }

    window.addEventListener("load", () => {
      const secretaryAlert = document.getElementById("secretaryTomorrowAlert");
      if (!secretaryAlert) return;

      const tomorrowCount = Number(secretaryAlert.dataset.tomorrowCount || 0);
      if (tomorrowCount <= 0) return;

      const todayKey = new Date().toISOString().slice(0, 10);
      const storageKey = `clinic_secretary_tomorrow_alert_${todayKey}`;
      if (localStorage.getItem(storageKey) === "shown") return;

      try {
        playSecretaryAlertSound();
      } catch (error) {
        // Some browsers may block autoplayed audio without interaction.
      }

      localStorage.setItem(storageKey, "shown");
    });
  </script>

</body>

</html>