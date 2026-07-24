<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

$requiredPermissions = ['reports'];
include 'admin-only.php';

clinic_ensure_infrastructure($con);
clinic_ensure_procedure_entries($con);
clinic_ensure_procedure_types($con);

function hsafe(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function valid_date(string $v, string $fallback): string
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $fallback;
}

function visit_type_label(string $type): string
{
    if ($type === 'first') return 'أول مرة';
    if ($type === 'repeat') return 'متكررة';
    if ($type === 'free') return 'مراجعة';
    if ($type === 'charity') return 'مجانية';
    return 'غير محدد';
}

$today = date('Y-m-d');
$from = valid_date((string) ($_GET['from'] ?? date('Y-m-01')), date('Y-m-01'));
$to = valid_date((string) ($_GET['to'] ?? $today), $today);
if ($from > $to) {
    $tmp = $from;
    $from = $to;
    $to = $tmp;
}

$visitStatus = (string) ($_GET['visit_status'] ?? 'all');
if (!in_array($visitStatus, ['all', 'done', 'pending'], true)) {
    $visitStatus = 'all';
}

$visitType = (string) ($_GET['visit_type'] ?? 'all');
if (!in_array($visitType, ['all', 'first', 'repeat', 'free', 'charity'], true)) {
    $visitType = 'all';
}

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) > 120) {
    $q = mb_substr($q, 0, 120);
}

$activeTab = (string) ($_GET['tab'] ?? 'visits');
if (!in_array($activeTab, ['visits', 'procedures', 'operations', 'followups'], true)) {
    $activeTab = 'visits';
}

$escapedFrom = mysqli_real_escape_string($con, $from);
$escapedTo = mysqli_real_escape_string($con, $to);
$escapedQ = mysqli_real_escape_string($con, $q);

$visitWhere = ["v.visit_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'"];
if ($visitStatus === 'done') {
    $visitWhere[] = 'v.is_done = 1';
} elseif ($visitStatus === 'pending') {
    $visitWhere[] = 'v.is_done = 0';
}
if ($visitType !== 'all') {
    $safeType = mysqli_real_escape_string($con, $visitType);
    $visitWhere[] = "v.visit_type = '{$safeType}'";
}
if ($escapedQ !== '') {
    $visitWhere[] = "(p.full_name LIKE '%{$escapedQ}%' OR p.phone_no LIKE '%{$escapedQ}%' OR CAST(v.patient_id AS CHAR) LIKE '%{$escapedQ}%')";
}
$visitWhereSql = implode(' AND ', $visitWhere);

$summary = [
    'visits_total' => 0,
    'visits_done' => 0,
    'visits_pending' => 0,
    'visits_first' => 0,
    'visits_repeat' => 0,
    'visits_free' => 0,
    'visits_charity' => 0,
    'patients_distinct' => 0,
    'procedures_count' => 0,
    'procedures_income' => 0.0,
    'surgeries_count' => 0,
    'laser_count' => 0,
    'injection_count' => 0,
    'followups_count' => 0,
    'followups_pending' => 0,
    'appointments_count' => 0,
    'appointments_not_attend' => 0,
    'referred_cases_count' => 0,
    'referred_doctors_count' => 0,
];

$visitStatsSql = "
    SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN v.is_done = 1 THEN 1 ELSE 0 END) AS done_count,
        SUM(CASE WHEN v.is_done = 0 THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN v.visit_type = 'first' THEN 1 ELSE 0 END) AS first_count,
        SUM(CASE WHEN v.visit_type = 'repeat' THEN 1 ELSE 0 END) AS repeat_count,
        SUM(CASE WHEN v.visit_type = 'free' THEN 1 ELSE 0 END) AS free_count,
        SUM(CASE WHEN v.visit_type = 'charity' THEN 1 ELSE 0 END) AS charity_count,
        COUNT(DISTINCT v.patient_id) AS patient_count
    FROM visits v
    LEFT JOIN add_patient p ON p.id = v.patient_id
    WHERE {$visitWhereSql}
";
$visitStatsRes = mysqli_query($con, $visitStatsSql);
if ($visitStatsRes) {
    $row = mysqli_fetch_assoc($visitStatsRes);
    if ($row) {
        $summary['visits_total'] = (int) ($row['total_count'] ?? 0);
        $summary['visits_done'] = (int) ($row['done_count'] ?? 0);
        $summary['visits_pending'] = (int) ($row['pending_count'] ?? 0);
        $summary['visits_first'] = (int) ($row['first_count'] ?? 0);
        $summary['visits_repeat'] = (int) ($row['repeat_count'] ?? 0);
        $summary['visits_free'] = (int) ($row['free_count'] ?? 0);
        $summary['visits_charity'] = (int) ($row['charity_count'] ?? 0);
        $summary['patients_distinct'] = (int) ($row['patient_count'] ?? 0);
    }
    mysqli_free_result($visitStatsRes);
}

$procStatsSql = "
    SELECT COUNT(*) AS total_count, COALESCE(SUM(total_cost), 0) AS total_income
    FROM procedure_entries
    WHERE procedure_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
";
$procStatsRes = mysqli_query($con, $procStatsSql);
if ($procStatsRes) {
    $row = mysqli_fetch_assoc($procStatsRes);
    if ($row) {
        $summary['procedures_count'] = (int) ($row['total_count'] ?? 0);
        $summary['procedures_income'] = (float) ($row['total_income'] ?? 0);
    }
    mysqli_free_result($procStatsRes);
}

$singleCount = function (string $sql) use ($con): int {
    $res = mysqli_query($con, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return (int) ($row['total'] ?? 0);
};

$summary['surgeries_count'] = $singleCount("SELECT COUNT(*) AS total FROM surgery WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['laser_count'] = $singleCount("SELECT COUNT(*) AS total FROM laser WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['injection_count'] = $singleCount("SELECT COUNT(*) AS total FROM injection WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['followups_count'] = $singleCount("SELECT COUNT(*) AS total FROM followups WHERE followup_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['followups_pending'] = $singleCount("SELECT COUNT(*) AS total FROM followups WHERE status = 'pending' AND followup_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['appointments_count'] = $singleCount("SELECT COUNT(*) AS total FROM surgery_appointment WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['appointments_not_attend'] = $singleCount("SELECT COUNT(*) AS total FROM surgery_appointment WHERE status = 'discharged' AND date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['referred_cases_count'] = $singleCount("SELECT COUNT(*) AS total FROM referred_surgery_cases WHERE surgery_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'");
$summary['referred_doctors_count'] = $singleCount("SELECT COUNT(DISTINCT referring_doctor_name) AS total FROM referred_surgery_cases WHERE surgery_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}' AND TRIM(referring_doctor_name) <> ''");

$visitTrend = [];
$trendRes = mysqli_query($con, "
    SELECT
        v.visit_date,
        COUNT(*) AS total_count,
        SUM(CASE WHEN v.is_done = 1 THEN 1 ELSE 0 END) AS done_count,
        SUM(CASE WHEN v.is_done = 0 THEN 1 ELSE 0 END) AS pending_count
    FROM visits v
    LEFT JOIN add_patient p ON p.id = v.patient_id
    WHERE {$visitWhereSql}
    GROUP BY v.visit_date
    ORDER BY v.visit_date DESC
    LIMIT 60
");
while ($trendRes && ($row = mysqli_fetch_assoc($trendRes))) {
    $visitTrend[] = $row;
}
if ($trendRes) {
    mysqli_free_result($trendRes);
}

$visitDetails = [];
$detailsRes = mysqli_query($con, "
    SELECT
        v.visit_date,
        v.daily_serial,
        v.visit_type,
        v.is_done,
        v.patient_id,
        COALESCE(p.full_name, 'غير متوفر') AS full_name,
        COALESCE(p.phone_no, '-') AS phone_no,
        COALESCE(p.age, '-') AS age
    FROM visits v
    LEFT JOIN add_patient p ON p.id = v.patient_id
    WHERE {$visitWhereSql}
    ORDER BY v.visit_date DESC, v.daily_serial ASC
    LIMIT 800
");
while ($detailsRes && ($row = mysqli_fetch_assoc($detailsRes))) {
    $visitDetails[] = $row;
}
if ($detailsRes) {
    mysqli_free_result($detailsRes);
}

$procedureRows = [];
$procRes = mysqli_query($con, "
    SELECT
        procedure_date,
        category,
        procedure_type_name,
        patient_id,
        patient_name,
        qty,
        unit_cost,
        total_cost
    FROM procedure_entries
    WHERE procedure_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    ORDER BY procedure_date DESC, id DESC
    LIMIT 500
");
while ($procRes && ($row = mysqli_fetch_assoc($procRes))) {
    $procedureRows[] = $row;
}
if ($procRes) {
    mysqli_free_result($procRes);
}

$topSurgeryTypes = [];
$topSurgeryRes = mysqli_query($con, "
    SELECT COALESCE(NULLIF(TRIM(surgery_type), ''), 'غير محدد') AS name, COUNT(*) AS total
    FROM surgery
    WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    GROUP BY COALESCE(NULLIF(TRIM(surgery_type), ''), 'غير محدد')
    ORDER BY total DESC
    LIMIT 8
");
while ($topSurgeryRes && ($row = mysqli_fetch_assoc($topSurgeryRes))) {
    $topSurgeryTypes[] = $row;
}
if ($topSurgeryRes) {
    mysqli_free_result($topSurgeryRes);
}

$topLaserTypes = [];
$topLaserRes = mysqli_query($con, "
    SELECT COALESCE(NULLIF(TRIM(laser_type), ''), 'غير محدد') AS name, COUNT(*) AS total
    FROM laser
    WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    GROUP BY COALESCE(NULLIF(TRIM(laser_type), ''), 'غير محدد')
    ORDER BY total DESC
    LIMIT 8
");
while ($topLaserRes && ($row = mysqli_fetch_assoc($topLaserRes))) {
    $topLaserTypes[] = $row;
}
if ($topLaserRes) {
    mysqli_free_result($topLaserRes);
}

$topInjectionTypes = [];
$topInjectionRes = mysqli_query($con, "
    SELECT COALESCE(NULLIF(TRIM(injection_type), ''), 'غير محدد') AS name, COUNT(*) AS total
    FROM injection
    WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    GROUP BY COALESCE(NULLIF(TRIM(injection_type), ''), 'غير محدد')
    ORDER BY total DESC
    LIMIT 8
");
while ($topInjectionRes && ($row = mysqli_fetch_assoc($topInjectionRes))) {
    $topInjectionTypes[] = $row;
}
if ($topInjectionRes) {
    mysqli_free_result($topInjectionRes);
}

$topIolPowers = [];
$topIolPowersRes = mysqli_query($con, "
    SELECT iol_power, COUNT(*) AS total
    FROM surgery
    WHERE date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
      AND iol_power IS NOT NULL
    GROUP BY iol_power
    ORDER BY iol_power ASC
    LIMIT 30
");
while ($topIolPowersRes && ($row = mysqli_fetch_assoc($topIolPowersRes))) {
    $topIolPowers[] = $row;
}
if ($topIolPowersRes) {
    mysqli_free_result($topIolPowersRes);
}

$followupRows = [];
$followupRes = mysqli_query($con, "
    SELECT f.followup_date, f.status, f.followup_reason, COALESCE(p.full_name, 'غير متوفر') AS full_name, p.id AS patient_id
    FROM followups f
    LEFT JOIN add_patient p ON p.id = f.patient_id
    WHERE f.followup_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    ORDER BY f.followup_date DESC, f.id DESC
    LIMIT 500
");
while ($followupRes && ($row = mysqli_fetch_assoc($followupRes))) {
    $followupRows[] = $row;
}
if ($followupRes) {
    mysqli_free_result($followupRes);
}

$topReferringDoctors = [];
$topReferringRes = mysqli_query($con, "
    SELECT COALESCE(NULLIF(TRIM(referring_doctor_name), ''), 'غير محدد') AS name, COUNT(*) AS total
    FROM referred_surgery_cases
    WHERE surgery_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    GROUP BY COALESCE(NULLIF(TRIM(referring_doctor_name), ''), 'غير محدد')
    ORDER BY total DESC
    LIMIT 10
");
while ($topReferringRes && ($row = mysqli_fetch_assoc($topReferringRes))) {
    $topReferringDoctors[] = $row;
}
if ($topReferringRes) {
    mysqli_free_result($topReferringRes);
}

$referredRows = [];
$referredRes = mysqli_query($con, "
    SELECT
        id,
        patient_full_name,
        patient_phone,
        referring_doctor_name,
        surgery_type,
        surgery_date,
        eye,
        followup_destination
    FROM referred_surgery_cases
    WHERE surgery_date BETWEEN '{$escapedFrom}' AND '{$escapedTo}'
    ORDER BY surgery_date DESC, id DESC
    LIMIT 300
");
while ($referredRes && ($row = mysqli_fetch_assoc($referredRes))) {
    $referredRows[] = $row;
}
if ($referredRes) {
    mysqli_free_result($referredRes);
}

if (isset($_GET['export']) && $_GET['export'] === 'visits_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="visits-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['date', 'serial', 'patient_id', 'patient_name', 'phone', 'age', 'visit_type', 'status']);
    foreach ($visitDetails as $row) {
        fputcsv($out, [
            (string) ($row['visit_date'] ?? ''),
            (string) ($row['daily_serial'] ?? ''),
            (string) ($row['patient_id'] ?? ''),
            (string) ($row['full_name'] ?? ''),
            (string) ($row['phone_no'] ?? ''),
            (string) ($row['age'] ?? ''),
            visit_type_label((string) ($row['visit_type'] ?? '')),
            ((int) ($row['is_done'] ?? 0) === 1) ? 'done' : 'pending',
        ]);
    }
    fclose($out);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'procedures_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="procedures-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['date', 'category', 'procedure_type', 'patient_id', 'patient_name', 'qty', 'unit_cost', 'total_cost']);
    foreach ($procedureRows as $row) {
        fputcsv($out, [
            (string) ($row['procedure_date'] ?? ''),
            (string) ($row['category'] ?? ''),
            (string) ($row['procedure_type_name'] ?? ''),
            (string) ($row['patient_id'] ?? ''),
            (string) ($row['patient_name'] ?? ''),
            (string) ($row['qty'] ?? ''),
            (string) ($row['unit_cost'] ?? ''),
            (string) ($row['total_cost'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'referred_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="referred-cases-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['id', 'patient_name', 'patient_phone', 'referring_doctor', 'surgery_type', 'eye', 'surgery_date', 'followup_destination']);
    foreach ($referredRows as $row) {
        fputcsv($out, [
            (string) ($row['id'] ?? ''),
            (string) ($row['patient_full_name'] ?? ''),
            (string) ($row['patient_phone'] ?? ''),
            (string) ($row['referring_doctor_name'] ?? ''),
            (string) ($row['surgery_type'] ?? ''),
            (string) ($row['eye'] ?? ''),
            (string) ($row['surgery_date'] ?? ''),
            (string) ($row['followup_destination'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

$queryBase = [
    'from' => $from,
    'to' => $to,
    'visit_status' => $visitStatus,
    'visit_type' => $visitType,
    'q' => $q,
    'tab' => $activeTab,
];

$trendMax = 0;
foreach ($visitTrend as $row) {
    $trendMax = max($trendMax, (int) ($row['total_count'] ?? 0));
}
if ($trendMax < 1) {
    $trendMax = 1;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير العيادة | عيادة الدكتور حيدر صباح الربيعي</title>
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <style>
        :root {
            --bg: #f3f7ff;
            --card: #ffffff;
            --muted: #64748b;
            --text: #0f172a;
            --border: #d9e4f3;
            --primary: #0f766e;
            --accent: #1d4ed8;
            --danger: #b91c1c;
            --ok: #166534;
            --shadow: 0 16px 38px rgba(2, 6, 23, .08);
        }

        body[data-theme="dark"],
        body.dark {
            --bg: #081120;
            --card: #101b31;
            --muted: #94a3b8;
            --text: #e2e8f0;
            --border: #22324d;
            --primary: #34d399;
            --accent: #60a5fa;
            --danger: #fca5a5;
            --ok: #86efac;
            --shadow: 0 20px 44px rgba(0, 0, 0, .35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: radial-gradient(circle at top right, rgba(29, 78, 216, .11), transparent 40%), var(--bg);
            color: var(--text);
        }

        .app-shell {
            min-height: 100vh;
            display: block;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            /* right side for RTL */
            height: 100vh;
            width: 270px;
            background: var(--card);
            border-left: 1px solid var(--border);
            box-shadow: -4px 0 24px rgba(2, 6, 23, .14);
            padding: 14px;
            overflow-y: auto;
            z-index: 200;
            transition: transform .25s ease, opacity .2s ease;
            transform: translateX(0);
        }

        /* LTR: sidebar on left */
        [dir="ltr"] .sidebar {
            right: auto;
            left: 0;
            border-left: none;
            border-right: 1px solid var(--border);
            box-shadow: 4px 0 24px rgba(2, 6, 23, .14);
        }

        .sidebar.hidden {
            transform: translateX(110%);
            opacity: 0;
            pointer-events: none;
        }

        [dir="ltr"] .sidebar.hidden {
            transform: translateX(-110%);
        }

        /* Overlay behind sidebar on mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .38);
            z-index: 199;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Dark mode toggle inside sidebar */
        .dark-mode-btn {
            width: 100%;
            margin-top: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            border-radius: 9px;
            min-height: 38px;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .dark-mode-btn:hover {
            background: rgba(37, 99, 235, .1);
            border-color: rgba(37, 99, 235, .26);
            color: var(--accent);
        }

        .brand {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .brand strong {
            display: block;
            font-size: 18px;
            color: var(--accent);
        }

        .brand span {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .menu-group {
            margin-bottom: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(37, 99, 235, .03);
        }

        .menu-title,
        .menu-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 0;
            width: 100%;
            padding: 10px 12px;
            font-size: 12px;
            color: var(--text);
            font-weight: 800;
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
            color: var(--muted);
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
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 8px 10px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .menu-group a:hover,
        .menu-group a.active {
            background: rgba(37, 99, 235, .1);
            border-color: rgba(37, 99, 235, .26);
            color: var(--accent);
        }

        .main-area {
            min-width: 0;
        }

        .top-tools {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
            padding: 0 12px;
        }

        .sidebar-toggle {
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text);
            border-radius: 10px;
            min-height: 40px;
            padding: 8px 12px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .wrap {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 12px 24px;
        }

        .head,
        .panel,
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
        }

        .head {
            padding: 16px;
            margin-bottom: 12px;
        }

        .head h1 {
            margin: 0 0 6px;
            color: var(--accent);
            font-size: 28px;
        }

        .head p {
            margin: 0;
            color: var(--muted);
            line-height: 1.8;
        }

        .filters {
            padding: 14px;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        input,
        select {
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 8px 10px;
            background: transparent;
            color: var(--text);
        }

        .buttons {
            grid-column: 1 / -1;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: 9px;
            min-height: 40px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            cursor: pointer;
            background: var(--accent);
            color: #fff;
        }

        .btn.secondary {
            background: transparent;
            color: var(--text);
            border-color: var(--border);
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .card {
            padding: 12px;
        }

        .card .title {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .card .value {
            margin-top: 4px;
            font-size: 30px;
            font-weight: 900;
            color: var(--accent);
        }

        .tabs {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px;
        }

        .tab-btn {
            border: 1px solid var(--border);
            background: #f8fbff;
            color: var(--text);
            border-radius: 999px;
            min-height: 38px;
            padding: 8px 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .panel {
            padding: 14px;
            margin-bottom: 12px;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 19px;
        }

        .trend-list {
            display: grid;
            gap: 8px;
        }

        .trend-item {
            display: grid;
            gap: 6px;
        }

        .trend-meta {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 13px;
        }

        .bar {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .bar>span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, var(--accent), var(--primary));
        }

        .split {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border-top: 1px solid #e8eef7;
            padding: 8px;
            text-align: right;
        }

        th {
            background: #f8fbff;
            color: #1e3a8a;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .ok {
            color: var(--ok);
            background: rgba(22, 101, 52, .12);
        }

        .pending {
            color: var(--danger);
            background: rgba(185, 28, 28, .12);
        }

        .list-table {
            max-height: 520px;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        @media (max-width: 980px) {
            .filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .split {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 620px) {
            .filters {
                grid-template-columns: 1fr;
            }

            .buttons .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/ui-unified.css">
</head>

<body class="ui-unified">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
    <link rel="stylesheet" href="assets/branding/branding.css">
    <div class="app-shell" id="appShell">
        <aside class="sidebar hidden" id="reportsSidebar" aria-label="التنقل">
            <div class="brand">
                <div class="brand-with-logo">
                    <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
                    <div class="brand-text">
                        <span class="brand-title">عيادة الدكتور حيدر صباح الربيعي</span>
                        <span class="brand-subtitle">تقارير شاملة وتحليلات</span>
                    </div>
                </div>
            </div>

            <details class="menu-group" data-menu-key="main">
                <summary>📊 الرئيسية</summary>
                <div class="menu-links">
                    <a href="dashboard.php">لوحة التحكم</a>
                    <a href="main.php">المرضى</a>
                    <a href="add-referred-case.php">إضافة حالة محولة</a>
                    <a href="referred-cases.php">الحالات المحولة</a>
                    <a href="visits.php">زيارات اليوم</a>
                </div>
            </details>

            <details class="menu-group" data-menu-key="reports" open>
                <summary>📈 التقارير</summary>
                <div class="menu-links">
                    <a class="active" href="reports.php">تقارير شاملة</a>
                    <a href="daily-revenue.php?date=<?php echo urlencode($to); ?>">الإيراد اليومي</a>
                    <a href="dashboard-status.php">حالة الداشبورد</a>
                    <a href="data-quality.php">جودة البيانات</a>
                </div>
            </details>

            <details class="menu-group" data-menu-key="appointments">
                <summary>📅 المواعيد والإجراءات</summary>
                <div class="menu-links">
                    <a href="followups.php">المراجعات</a>
                    <a href="operation-by-date.php">مواعيد العمليات</a>
                    <a href="procedure-entries.php?date=<?php echo urlencode($to); ?>">إدخال الإجراءات</a>
                </div>
            </details>

            <details class="menu-group" data-menu-key="system">
                <summary>⚙️ النظام</summary>
                <div class="menu-links">
                    <a href="settings.php">الإعدادات</a>
                    <a href="audit-log.php">سجل التدقيق</a>
                    <a href="logout.php">تسجيل الخروج</a>
                </div>
            </details>

            <div class="menu-group">
                <div class="menu-title">🔧 الإعدادات السريعة</div>
                <div class="menu-links">
                    <button class="dark-mode-btn" id="darkModeBtn" type="button">
                        <span id="darkModeIcon">🌙</span>
                        <span id="darkModeLabel">الوضع الداكن</span>
                    </button>
                </div>
            </div>
        </aside>

        <div class="main-area">
            <div class="top-tools">
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-expanded="false">☰ القائمة</button>
            </div>

            <main class="wrap">
                <section class="head">
                    <h1>تقارير العيادة الشاملة</h1>
                    <p>عرض موحد وتفاعلي لكل التقارير المهمة: زيارات، إجراءات، عمليات، ليزر، إبر، مواعيد، ومتابعات حسب الفترة المختارة.</p>
                </section>

                <form class="panel filters" method="get">
                    <div class="field">
                        <label for="from">من تاريخ</label>
                        <input id="from" type="date" name="from" value="<?php echo hsafe($from); ?>" required>
                    </div>
                    <div class="field">
                        <label for="to">إلى تاريخ</label>
                        <input id="to" type="date" name="to" value="<?php echo hsafe($to); ?>" required>
                    </div>
                    <div class="field">
                        <label for="visit_status">حالة الزيارة</label>
                        <select id="visit_status" name="visit_status">
                            <option value="all" <?php echo $visitStatus === 'all' ? 'selected' : ''; ?>>الكل</option>
                            <option value="done" <?php echo $visitStatus === 'done' ? 'selected' : ''; ?>>منجزة</option>
                            <option value="pending" <?php echo $visitStatus === 'pending' ? 'selected' : ''; ?>>بانتظار</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="visit_type">نوع الزيارة</label>
                        <select id="visit_type" name="visit_type">
                            <option value="all" <?php echo $visitType === 'all' ? 'selected' : ''; ?>>الكل</option>
                            <option value="first" <?php echo $visitType === 'first' ? 'selected' : ''; ?>>أول مرة</option>
                            <option value="repeat" <?php echo $visitType === 'repeat' ? 'selected' : ''; ?>>متكررة</option>
                            <option value="free" <?php echo $visitType === 'free' ? 'selected' : ''; ?>>مراجعة</option>
                            <option value="charity" <?php echo $visitType === 'charity' ? 'selected' : ''; ?>>مجانية</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="q">بحث بالمريض</label>
                        <input id="q" type="text" name="q" placeholder="اسم / هاتف / رقم" value="<?php echo hsafe($q); ?>">
                    </div>
                    <div class="field">
                        <label for="tab">القسم الافتراضي</label>
                        <select id="tab" name="tab">
                            <option value="visits" <?php echo $activeTab === 'visits' ? 'selected' : ''; ?>>الزيارات</option>
                            <option value="procedures" <?php echo $activeTab === 'procedures' ? 'selected' : ''; ?>>الإجراءات</option>
                            <option value="operations" <?php echo $activeTab === 'operations' ? 'selected' : ''; ?>>عمليات/ليزر/إبر</option>
                            <option value="followups" <?php echo $activeTab === 'followups' ? 'selected' : ''; ?>>المتابعات/المواعيد</option>
                        </select>
                    </div>
                    <div class="buttons">
                        <button class="btn" type="submit">تحديث التقرير</button>
                        <a class="btn secondary" href="reports.php">إعادة الضبط</a>
                        <a class="btn secondary" href="reports.php?<?php echo hsafe(http_build_query(array_merge($queryBase, ['export' => 'visits_csv']))); ?>">تصدير زيارات CSV</a>
                        <a class="btn secondary" href="reports.php?<?php echo hsafe(http_build_query(array_merge($queryBase, ['export' => 'procedures_csv']))); ?>">تصدير إجراءات CSV</a>
                        <a class="btn secondary" href="reports.php?<?php echo hsafe(http_build_query(array_merge($queryBase, ['export' => 'referred_csv']))); ?>">تصدير الحالات المحولة CSV</a>
                    </div>
                </form>

                <section class="cards">
                    <article class="card">
                        <div class="title">إجمالي الزيارات</div>
                        <div class="value"><?php echo number_format($summary['visits_total']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">إجمالي الإجراءات</div>
                        <div class="value"><?php echo number_format($summary['procedures_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">إيراد الإجراءات</div>
                        <div class="value"><?php echo number_format($summary['procedures_income'], 0); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">العمليات</div>
                        <div class="value"><?php echo number_format($summary['surgeries_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">الليزر</div>
                        <div class="value"><?php echo number_format($summary['laser_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">الإبر</div>
                        <div class="value"><?php echo number_format($summary['injection_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">المتابعات</div>
                        <div class="value"><?php echo number_format($summary['followups_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">مواعيد العمليات</div>
                        <div class="value"><?php echo number_format($summary['appointments_count']); ?></div>
                    </article>
                    <article class="card">
                        <div class="title">الحالات المحولة</div>
                        <div class="value"><?php echo number_format($summary['referred_cases_count']); ?></div>
                    </article>
                </section>

                <section class="panel tabs">
                    <button class="tab-btn" data-tab="visits">تقارير الزيارات</button>
                    <button class="tab-btn" data-tab="procedures">تقارير الإجراءات</button>
                    <button class="tab-btn" data-tab="operations">عمليات / ليزر / إبر</button>
                    <button class="tab-btn" data-tab="followups">المتابعات والمواعيد</button>
                </section>

                <section class="tab-panel panel" data-panel="visits">
                    <h2>الزيارات اليومية التاريخية</h2>
                    <div class="split">
                        <div>
                            <div class="list-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>التسلسل</th>
                                            <th>المريض</th>
                                            <th>النوع</th>
                                            <th>الحالة</th>
                                            <th>فتح</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($visitDetails)): ?>
                                            <tr>
                                                <td colspan="6">لا توجد زيارات ضمن الفلاتر.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($visitDetails as $row): ?>
                                                <?php $isDone = ((int) ($row['is_done'] ?? 0) === 1); ?>
                                                <tr>
                                                    <td><?php echo hsafe((string) ($row['visit_date'] ?? '')); ?></td>
                                                    <td><?php echo (int) ($row['daily_serial'] ?? 0); ?></td>
                                                    <td><?php echo hsafe((string) ($row['full_name'] ?? '')); ?></td>
                                                    <td><?php echo hsafe(visit_type_label((string) ($row['visit_type'] ?? ''))); ?></td>
                                                    <td><span class="badge <?php echo $isDone ? 'ok' : 'pending'; ?>"><?php echo $isDone ? 'منجزة' : 'بانتظار'; ?></span></td>
                                                    <td><a href="patient-file.php?id=<?php echo (int) ($row['patient_id'] ?? 0); ?>">فتح</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <div class="card" style="margin-bottom:10px;">
                                <div class="title">تفصيل الزيارات</div>
                                <div style="margin-top:8px; font-weight:700; line-height:1.9;">
                                    منجزة: <?php echo number_format($summary['visits_done']); ?><br>
                                    بانتظار: <?php echo number_format($summary['visits_pending']); ?><br>
                                    أول مرة: <?php echo number_format($summary['visits_first']); ?><br>
                                    متكررة: <?php echo number_format($summary['visits_repeat']); ?><br>
                                    مراجعة: <?php echo number_format($summary['visits_free']); ?><br>
                                    مجانية: <?php echo number_format($summary['visits_charity']); ?><br>
                                    مرضى مختلفون: <?php echo number_format($summary['patients_distinct']); ?>
                                </div>
                            </div>
                            <div class="panel" style="padding:10px;">
                                <h2 style="font-size:16px; margin:0 0 8px;">اتجاه الزيارات</h2>
                                <div class="trend-list">
                                    <?php if (empty($visitTrend)): ?>
                                        <div style="color:var(--muted);">لا توجد بيانات اتجاه.</div>
                                    <?php else: ?>
                                        <?php foreach ($visitTrend as $row): ?>
                                            <?php $w = (int) round(((int) ($row['total_count'] ?? 0) / $trendMax) * 100); ?>
                                            <div class="trend-item">
                                                <div class="trend-meta"><span><?php echo hsafe((string) ($row['visit_date'] ?? '')); ?></span><span><?php echo (int) ($row['total_count'] ?? 0); ?></span></div>
                                                <div class="bar"><span style="width: <?php echo $w; ?>%"></span></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-panel panel" data-panel="procedures">
                    <h2>تقارير الإجراءات (شبكية / ليزر / أخرى)</h2>
                    <div class="list-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الفئة</th>
                                    <th>نوع الإجراء</th>
                                    <th>المريض</th>
                                    <th>الكمية</th>
                                    <th>سعر الوحدة</th>
                                    <th>المجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($procedureRows)): ?>
                                    <tr>
                                        <td colspan="7">لا توجد إجراءات ضمن الفترة.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($procedureRows as $row): ?>
                                        <tr>
                                            <td><?php echo hsafe((string) ($row['procedure_date'] ?? '')); ?></td>
                                            <td><?php echo hsafe((string) ($row['category'] ?? 'other')); ?></td>
                                            <td><?php echo hsafe((string) ($row['procedure_type_name'] ?? '')); ?></td>
                                            <td><?php echo hsafe((string) ($row['patient_name'] ?? '')); ?></td>
                                            <td><?php echo (int) ($row['qty'] ?? 0); ?></td>
                                            <td><?php echo number_format((float) ($row['unit_cost'] ?? 0), 0); ?></td>
                                            <td><?php echo number_format((float) ($row['total_cost'] ?? 0), 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="tab-panel panel" data-panel="operations">
                    <h2>تحليل العمليات والليزر والإبر</h2>
                    <div class="split">
                        <div class="card">
                            <div class="title">أكثر أنواع العمليات</div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>النوع</th>
                                        <th>العدد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topSurgeryTypes)): ?>
                                        <tr>
                                            <td colspan="2">لا توجد بيانات.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topSurgeryTypes as $row): ?>
                                            <tr>
                                                <td><?php echo hsafe((string) ($row['name'] ?? '')); ?></td>
                                                <td><?php echo (int) ($row['total'] ?? 0); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card">
                            <div class="title">أكثر أنواع الليزر</div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>النوع</th>
                                        <th>العدد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topLaserTypes)): ?>
                                        <tr>
                                            <td colspan="2">لا توجد بيانات.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topLaserTypes as $row): ?>
                                            <tr>
                                                <td><?php echo hsafe((string) ($row['name'] ?? '')); ?></td>
                                                <td><?php echo (int) ($row['total'] ?? 0); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="split" style="margin-top:10px;">
                        <div class="card">
                            <div class="title">أكثر الأطباء المُحيلين</div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>الطبيب</th>
                                        <th>عدد الحالات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topReferringDoctors)): ?>
                                        <tr>
                                            <td colspan="2">لا توجد بيانات.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topReferringDoctors as $row): ?>
                                            <tr>
                                                <td><?php echo hsafe((string) ($row['name'] ?? '')); ?></td>
                                                <td><?php echo (int) ($row['total'] ?? 0); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card">
                            <div class="title">ملخص الحالات المحولة</div>
                            <div style="margin-top:8px; font-weight:700; line-height:1.9;">
                                إجمالي الحالات بالفترة: <?php echo number_format($summary['referred_cases_count']); ?><br>
                                عدد الأطباء المُحيلين: <?php echo number_format($summary['referred_doctors_count']); ?><br>
                                <a href="referred-cases.php">فتح قائمة الحالات المحولة</a>
                            </div>
                        </div>
                    </div>
                    <div class="card" style="margin-top:10px;">
                        <div class="title">أكثر أنواع الإبر</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>النوع</th>
                                    <th>العدد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topInjectionTypes)): ?>
                                    <tr>
                                        <td colspan="2">لا توجد بيانات.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topInjectionTypes as $row): ?>
                                        <tr>
                                            <td><?php echo hsafe((string) ($row['name'] ?? '')); ?></td>
                                            <td><?php echo (int) ($row['total'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card" style="margin-top:10px;">
                        <div class="title">توزيع قوة العدسات (IOL Power)</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>قوة العدسة</th>
                                    <th>العدد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topIolPowers)): ?>
                                    <tr>
                                        <td colspan="2">لا توجد بيانات.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topIolPowers as $row): ?>
                                        <tr>
                                            <td><?php echo hsafe(clinic_format_iol_power($row['iol_power'] ?? null)); ?></td>
                                            <td><?php echo (int) ($row['total'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card" style="margin-top:10px;">
                        <div class="title">آخر الحالات المحولة ضمن الفترة</div>
                        <div class="list-table" style="margin-top:8px; max-height:360px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>المريض</th>
                                        <th>الطبيب المُحيل</th>
                                        <th>العملية</th>
                                        <th>العين</th>
                                        <th>الهاتف</th>
                                        <th>تعديل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($referredRows)): ?>
                                        <tr>
                                            <td colspan="7">لا توجد حالات محولة ضمن الفترة.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($referredRows as $row): ?>
                                            <tr>
                                                <td><?php echo hsafe((string) ($row['surgery_date'] ?? '')); ?></td>
                                                <td><?php echo hsafe((string) ($row['patient_full_name'] ?? '')); ?></td>
                                                <td><?php echo hsafe((string) ($row['referring_doctor_name'] ?? '')); ?></td>
                                                <td><?php echo hsafe((string) ($row['surgery_type'] ?? '')); ?></td>
                                                <td><?php echo hsafe((string) (($row['eye'] ?? '') !== '' ? $row['eye'] : '-')); ?></td>
                                                <td><?php echo hsafe((string) (($row['patient_phone'] ?? '') !== '' ? $row['patient_phone'] : '-')); ?></td>
                                                <td><a href="edit-referred-case.php?id=<?php echo (int) ($row['id'] ?? 0); ?>">تعديل</a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="tab-panel panel" data-panel="followups">
                    <h2>المتابعات ومواعيد العمليات</h2>
                    <div class="cards" style="margin-top:0;">
                        <article class="card">
                            <div class="title">إجمالي المتابعات</div>
                            <div class="value"><?php echo number_format($summary['followups_count']); ?></div>
                        </article>
                        <article class="card">
                            <div class="title">المتابعات المعلقة</div>
                            <div class="value"><?php echo number_format($summary['followups_pending']); ?></div>
                        </article>
                        <article class="card">
                            <div class="title">إجمالي مواعيد العمليات</div>
                            <div class="value"><?php echo number_format($summary['appointments_count']); ?></div>
                        </article>
                        <article class="card">
                            <div class="title">لم يحضروا المواعيد</div>
                            <div class="value"><?php echo number_format($summary['appointments_not_attend']); ?></div>
                        </article>
                    </div>
                    <div class="list-table" style="margin-top:10px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الحالة</th>
                                    <th>سبب/ملاحظة</th>
                                    <th>فتح</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($followupRows)): ?>
                                    <tr>
                                        <td colspan="5">لا توجد متابعات في الفترة.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($followupRows as $row): ?>
                                        <tr>
                                            <td><?php echo hsafe((string) ($row['followup_date'] ?? '')); ?></td>
                                            <td><?php echo hsafe((string) ($row['full_name'] ?? '')); ?></td>
                                            <td><?php echo hsafe((string) ($row['status'] ?? '-')); ?></td>
                                            <td><?php echo hsafe((string) ($row['followup_reason'] ?? '')); ?></td>
                                            <td>
                                                <?php if ((int) ($row['patient_id'] ?? 0) > 0): ?>
                                                    <a href="patient-file.php?id=<?php echo (int) $row['patient_id']; ?>">فتح</a>
                                                    <?php else: ?>-
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        (function() {
            const active = <?php echo json_encode($activeTab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const btns = document.querySelectorAll('.tab-btn');
            const panels = document.querySelectorAll('.tab-panel');
            const shell = document.getElementById('appShell');
            const sidebar = document.getElementById('reportsSidebar');
            const toggle = document.getElementById('sidebarToggle');

            function setTab(tab) {
                btns.forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
                panels.forEach((p) => p.classList.toggle('active', p.dataset.panel === tab));
                const tabInput = document.getElementById('tab');
                if (tabInput) {
                    tabInput.value = tab;
                }
            }

            btns.forEach((b) => {
                b.addEventListener('click', () => {
                    setTab(b.dataset.tab || 'visits');
                });
            });

            const overlay = document.getElementById('sidebarOverlay');

            function applySidebarState(collapsed) {
                shell.classList.toggle('sidebar-collapsed', collapsed);
                sidebar.classList.toggle('hidden', collapsed);
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                if (overlay) overlay.classList.toggle('active', !collapsed);
            }

            function setupSidebarAccordion() {
                const groups = Array.from(document.querySelectorAll('#reportsSidebar details.menu-group[data-menu-key]'));
                if (!groups.length) return;

                const storageKey = 'reports_sidebar_open_group';
                const saved = localStorage.getItem(storageKey);
                const defaultGroup = groups.find(group => group.hasAttribute('open'));

                groups.forEach(group => {
                    group.open = false;
                });

                const initialGroup = groups.find(group => group.dataset.menuKey === saved) || defaultGroup || groups[0];
                if (initialGroup) initialGroup.open = true;

                groups.forEach(group => {
                    group.addEventListener('toggle', () => {
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

            /* Hidden by default – only open if user explicitly opened before */
            const stored = localStorage.getItem('reports_sidebar_collapsed');
            applySidebarState(stored !== '0'); /* default: collapsed (hidden) */

            toggle.addEventListener('click', () => {
                const collapsed = !shell.classList.contains('sidebar-collapsed');
                applySidebarState(collapsed);
                localStorage.setItem('reports_sidebar_collapsed', collapsed ? '1' : '0');
            });

            if (overlay) {
                overlay.addEventListener('click', () => {
                    applySidebarState(true);
                    localStorage.setItem('reports_sidebar_collapsed', '1');
                });
            }

            /* ── Dark-mode toggle ── */
            const darkBtn = document.getElementById('darkModeBtn');
            const darkIcon = document.getElementById('darkModeIcon');
            const darkLabel = document.getElementById('darkModeLabel');

            function isDark() {
                return document.body.dataset.theme === 'dark' || document.body.classList.contains('dark');
            }

            function updateDarkBtn() {
                if (!darkBtn) return;
                darkIcon.textContent = isDark() ? '☀️' : '🌙';
                darkLabel.textContent = isDark() ? 'الوضع الفاتح' : 'الوضع الداكن';
            }

            if (darkBtn) {
                darkBtn.addEventListener('click', () => {
                    if (isDark()) {
                        document.body.removeAttribute('data-theme');
                        document.body.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    } else {
                        document.body.dataset.theme = 'dark';
                        document.body.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                    updateDarkBtn();
                });

                updateDarkBtn();
            }

            setTab(active || 'visits');
            setupSidebarAccordion();
        })();
    </script>
</body>

</html>