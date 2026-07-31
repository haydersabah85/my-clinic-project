<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_runtime_controls($con);
clinic_ensure_daily_revenue($con);
clinic_ensure_visit_type_support($con);
clinic_ensure_column($con, 'visits', 'is_paid', 'TINYINT(1) NOT NULL DEFAULT 0');
clinic_ensure_column($con, 'visits', 'paid_at', 'DATETIME NULL');
clinic_ensure_column($con, 'visits', 'paid_by', 'VARCHAR(120) NULL');

$today = date('Y-m-d');

$isAdminUser = (($_SESSION['role'] ?? '') === 'admin');
$todayRevenue = null;

if ($isAdminUser) {
    $revStmt = mysqli_prepare($con, "
        SELECT visit_income, procedures_income, other_income, service_staff_due
        FROM daily_revenue
        WHERE revenue_date = ?
        LIMIT 1
    ");

    if ($revStmt) {
        mysqli_stmt_bind_param($revStmt, 's', $today);
        mysqli_stmt_execute($revStmt);
        $revResult = mysqli_stmt_get_result($revStmt);
        $todayRevenue = $revResult ? mysqli_fetch_assoc($revResult) : null;
    }
}

$stats = ['total' => 0, 'free' => 0, 'charity' => 0, 'done' => 0, 'pending' => 0, 'paid' => 0, 'unpaid' => 0, 'no_fee' => 0];
$last_visit_date = null;
$status_filter = $_GET['status'] ?? 'all';
$allowed_status_filters = ['all', 'pending', 'done'];

if (!in_array($status_filter, $allowed_status_filters, true)) {
    $status_filter = 'all';
}

$stmt = mysqli_prepare($con, "
    SELECT
        v.daily_serial,
        v.visit_type,
        v.visit_date,
        v.is_done,
        v.is_paid,
        v.visit_id,
        p.id AS patient_id,
        p.full_name,
        p.age,
        (
            SELECT MAX(v2.visit_date)
            FROM visits v2
            WHERE v2.patient_id = v.patient_id
            AND v2.visit_date < v.visit_date
        ) AS last_visit_date
    FROM visits v
    INNER JOIN add_patient p ON v.patient_id = p.id
    WHERE v.visit_date = ?
    ORDER BY v.daily_serial ASC
");

mysqli_stmt_bind_param($stmt, 's', $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$visits = [];
while ($row = mysqli_fetch_assoc($result)) {

    $stats['total']++;
    if (isset($stats[$row['visit_type']])) {
        $stats[$row['visit_type']]++;
    }
    if ($row['is_done']) {
        $stats['done']++;
    } else {
        $stats['pending']++;
    }
    $isNoFeeVisit = in_array((string) ($row['visit_type'] ?? ''), ['free', 'charity'], true);
    if ($isNoFeeVisit) {
        $stats['no_fee']++;
    } elseif (!empty($row['is_paid'])) {
        $stats['paid']++;
    } else {
        $stats['unpaid']++;
    }
    if (
        $status_filter === 'all' ||
        ($status_filter === 'done' && $row['is_done']) ||
        ($status_filter === 'pending' && !$row['is_done'])
    ) {
        $visits[] = $row;
    }
}

$nextPatientAlert = null;
$nextPatientRaw = clinic_get_app_setting($con, 'doctor_next_patient_alert', '');
if ($nextPatientRaw) {
    $decodedAlert = json_decode($nextPatientRaw, true);
    if (is_array($decodedAlert) && !empty($decodedAlert['patient_id']) && !empty($decodedAlert['full_name'])) {
        $nextPatientAlert = $decodedAlert;
    }
}

$nextPatientId = (int) ($nextPatientAlert['patient_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="90">
    <title>زيارات اليوم | عيادة الدكتور حيدر صباح الربيعي</title>

    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
    <link rel="stylesheet" href="assets/branding/branding.css">
    <link rel="stylesheet" href="assets/theme.css">
    <link rel="stylesheet" href="assets/clinic-ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="assets/theme.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg-main: #f6f7f4;
            --bg-alt: #e9eee8;
            --surface: #fffefa;
            --surface-soft: #f9f6ef;
            --ink: #1f2c25;
            --muted: #6a756f;
            --panel: rgba(255, 254, 250, 0.94);
            --panel-border: rgba(48, 75, 65, 0.13);
            --head: #183c34;
            --accent: #b88a44;
            --accent-2: #277968;
            --danger: #b75a52;
            --warning: #c6813a;
            --ok: #357f5a;
            --shadow: 0 10px 26px rgba(31, 44, 37, 0.1);
            --shadow-strong: 0 18px 42px rgba(31, 44, 37, 0.16);
        }

        body[data-theme="dark"],
        body.dark {
            --bg-main: #101713;
            --bg-alt: #17231d;
            --surface: #162019;
            --surface-soft: #1d2a22;
            --ink: #e7efe7;
            --muted: #9ab0a4;
            --panel: rgba(20, 30, 25, 0.9);
            --panel-border: rgba(150, 171, 155, 0.22);
            --head: #dbe8d4;
            --accent: #d1a15e;
            --accent-2: #55b39a;
            --danger: #db7b73;
            --ok: #63c089;
            --shadow: 0 18px 40px rgba(0, 0, 0, 0.34);
            --shadow-strong: 0 30px 58px rgba(0, 0, 0, 0.44);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            direction: rtl;
            color: var(--ink);
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(180deg, var(--bg-main) 0%, var(--bg-alt) 100%);
            min-height: 100vh;
        }

        h1 {
            margin: 0;
            padding: 18px 16px;
            text-align: center;
            font-size: clamp(23px, 4vw, 32px);
            font-weight: 800;
            color: #ffffff;
            background: linear-gradient(120deg, #183c34, #277968 72%, #b88a44);
            border-bottom: 1px solid rgba(255, 255, 255, 0.25);
            letter-spacing: 0;
            box-shadow: var(--shadow-strong);
        }

        header {
            max-width: 1500px;
            margin: 14px auto 0;
            padding: 0 14px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
        }

        .visits-page-header {
            position: relative;
            flex-direction: row-reverse;
            overflow: hidden;
            padding: 20px 22px;
            border-color: rgba(39, 121, 104, 0.24);
            background:
                radial-gradient(circle at 8% 15%, rgba(184, 138, 68, 0.18), transparent 34%),
                linear-gradient(135deg, var(--panel), rgba(39, 121, 104, 0.1));
            box-shadow: var(--shadow-strong);
        }

        .visits-page-header::before {
            content: "";
            position: absolute;
            inset-block: 0;
            inset-inline-start: 0;
            width: 5px;
            background: linear-gradient(180deg, var(--accent-2), var(--accent));
        }

        .visits-page-header>div:first-child {
            flex: 1;
            min-width: 0;
        }

        .visits-page-header .clinic-page-title {
            color: var(--head);
            text-align: right;
        }

        .visits-page-header .clinic-page-subtitle {
            color: var(--muted);
            text-align: right;
        }

        .visits-page-header .clinic-actions {
            position: relative;
            z-index: 1;
            flex: 0 0 auto;
        }

        .toggle-sidebar,
        .theme-toggle {
            border: 1px solid rgba(35, 68, 59, 0.2);
            border-radius: 8px;
            min-height: 42px;
            padding: 10px 16px;
            background: var(--panel);
            color: #23443b;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        body[data-theme="dark"] .visits-page-header,
        body.dark .visits-page-header {
            border-color: rgba(85, 179, 154, 0.3);
            background:
                radial-gradient(circle at 8% 15%, rgba(209, 161, 94, 0.14), transparent 34%),
                linear-gradient(135deg, var(--panel), rgba(85, 179, 154, 0.09));
        }

        body[data-theme="dark"] .toggle-sidebar,
        body[data-theme="dark"] .theme-toggle,
        body.dark .toggle-sidebar,
        body.dark .theme-toggle {
            color: var(--ink);
            border-color: var(--panel-border);
        }

        .toggle-sidebar:hover,
        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
            background: var(--surface);
        }

        .theme-toggle {
            width: 46px;
            padding: 0;
            font-size: 18px;
        }

        .container {
            max-width: 1500px;
            margin: 12px auto 22px;
            padding: 0 14px;
            display: grid;
            grid-template-columns: 0 minmax(0, 1fr);
            gap: 18px;
            min-height: calc(100vh - 162px);
            align-items: stretch;
            transition: grid-template-columns 0.28s ease;
            height: fit-content;
        }

        .container.sidebar-open {
            grid-template-columns: 290px minmax(0, 1fr);
        }

        .sidebar {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 14px;
            overflow-y: auto;
            max-height: calc(180vh - 162px);
            transition: opacity 0.24s ease, transform 0.24s ease;
            backdrop-filter: blur(8px);
        }

        .sidebar.hidden {
            opacity: 0;
            transform: translateX(16px);
            pointer-events: none;
        }

        .sidebar h3 {
            margin: 0 0 12px;
            font-size: 22px;
            color: var(--head);
        }

        .sidebar-brand-meta {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .menu-group {
            margin-bottom: 12px;
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            overflow: hidden;
            background: rgba(44, 140, 119, 0.06);
        }

        .menu-title,
        .menu-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 10px;
            margin: 0;
            padding: 10px 12px;
            color: var(--ink);
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.3px;
            background: linear-gradient(135deg, rgba(44, 140, 119, 0.16), rgba(15, 118, 110, 0.12));
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
            transition: transform 0.2s ease;
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
            color: var(--ink);
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 9px 11px;
            margin-bottom: 6px;
            font-weight: 700;
            transition: border-color 0.2s ease, transform 0.2s ease, background-color 0.2s ease;
        }

        .menu-group a:hover {
            border-color: rgba(44, 140, 119, 0.4);
            transform: translateX(-2px);
            background: rgba(44, 140, 119, 0.1);
        }

        .main-content {
            min-width: 0;
            min-height: 100%;
            overflow: visible;
            transition: transform 0.28s ease;
        }

        .container.sidebar-open .main-content {
            transform: translateX(-12px);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .finance-admin-card {
            margin-bottom: 12px;
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.14), rgba(31, 120, 87, 0.1));
            box-shadow: var(--shadow);
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .finance-admin-card .meta {
            font-size: 14px;
            color: var(--ink);
            font-weight: 700;
            line-height: 1.8;
        }

        .finance-admin-card .meta strong {
            color: #0f5132;
        }

        .finance-admin-card .open-revenue {
            text-decoration: none;
            border-radius: 8px;
            padding: 9px 12px;
            color: #fff;
            font-weight: 800;
            background: linear-gradient(135deg, #1e40af, #0f766e);
            border: 1px solid rgba(255, 255, 255, 0.34);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            padding: 14px 15px;
            text-align: right;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            backdrop-filter: blur(8px);
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            overflow: hidden;
            position: relative;
        }

        .card::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent-2);
        }

        .card.free-card::before {
            background: var(--accent);
        }

        .card.pending-card::before {
            background: var(--danger);
        }

        .card.done-card::before {
            background: var(--ok);
        }

        .card.paid-card::before {
            background: #16a34a;
        }

        .card.unpaid-card::before {
            background: #d97706;
        }

        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(39, 121, 104, 0.12);
            color: var(--accent-2);
            font-size: 18px;
        }

        .free-card .card-icon {
            background: rgba(184, 138, 68, 0.14);
            color: var(--accent);
        }

        .pending-card .card-icon {
            background: rgba(183, 90, 82, 0.14);
            color: var(--danger);
        }

        .done-card .card-icon {
            background: rgba(53, 127, 90, 0.14);
            color: var(--ok);
        }

        .paid-card .card-icon {
            background: rgba(22, 163, 74, 0.14);
            color: #16a34a;
        }

        .unpaid-card .card-icon {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
        }

        .card:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-strong);
        }

        .card .num {
            font-size: 30px;
            font-weight: 800;
            color: var(--head);
            line-height: 1;
        }

        .card .label {
            margin-top: 7px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .visit-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            padding: 10px;
            box-shadow: var(--shadow);
        }

        .search-box {
            flex: 1 1 320px;
        }

        .helper-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .helper-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(44, 140, 119, 0.1);
            color: var(--accent-2);
            border: 1px solid rgba(44, 140, 119, 0.24);
        }

        .search-box input {
            width: min(100%, 580px);
            border: 1px solid var(--panel-border);
            background: var(--surface);
            color: var(--ink);
            border-radius: 8px;
            padding: 11px 13px;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-box input::placeholder {
            color: var(--muted);
        }

        .search-box input:focus {
            border-color: var(--accent-2);
            box-shadow: 0 0 0 4px rgba(44, 140, 119, 0.16);
        }

        .status-filters {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 3px;
            border-radius: 8px;
            background: var(--surface-soft);
        }

        .status-filter {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 42px;
            padding: 9px 13px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            color: var(--ink);
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            box-shadow: none;
            transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
        }

        .status-filter:hover,
        .status-filter.active {
            transform: translateY(-1px);
            border-color: rgba(44, 140, 119, 0.45);
            background: rgba(44, 140, 119, 0.12);
        }

        .status-filter.active {
            color: #ffffff;
            background: linear-gradient(120deg, #23443b, #2c8c77);
        }

        .table-responsive {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: auto;
            max-height: calc(150vh - 162px);
            transition: box-shadow 0.2s ease;
            backdrop-filter: blur(8px);
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #183c34;
            color: #ffffff;
            font-size: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        th,
        td {
            padding: 13px 11px;
            text-align: center;
            border-bottom: 1px solid rgba(106, 114, 130, 0.16);
        }

        tbody tr:hover {
            background: rgba(39, 121, 104, 0.06);
        }

        tbody tr.row-unpaid {
            background: rgba(217, 119, 6, 0.09);
            box-shadow: inset 4px 0 0 rgba(217, 119, 6, 0.35);
        }

        .badge {
            display: inline-block;
            padding: 6px 13px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0;
        }

        .first {
            background: linear-gradient(135deg, #2f8d6b, #22c55e);
        }

        .repeat {
            background: linear-gradient(135deg, #1e4ed8, #0ea5e9);
        }

        .free {
            background: linear-gradient(120deg, #b0602c, #d5823c);
        }

        .charity {
            background: linear-gradient(120deg, #6d28d9, #8b5cf6);
        }

        .status-done {
            background: linear-gradient(120deg, #15803d, #22c55e);
        }

        .status-pending {
            background: linear-gradient(120deg, #b45309, #f59e0b);
        }

        .status-paid {
            background: linear-gradient(120deg, #334155, #475569);
        }

        .status-unpaid {
            background: linear-gradient(120deg, #b91c1c, #ef4444);
        }

        .status-no-fee {
            background: linear-gradient(120deg, #334155, #475569);
        }

        .payment-toggle-form {
            display: inline;
            margin: 0;
            padding: 0;
        }

        .payment-toggle {
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .name-link {
            text-decoration: none;
            color: #2b6d7a;
            font-weight: 800;
            transition: color 0.2s ease;
        }

        .name-cell {
            display: block;
        }

        .patient-col {
            text-align: right;
            min-width: 230px;
        }

        .patient-meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            display: block;
        }

        .next-patient-banner {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            background: linear-gradient(120deg, #fff7ed, #ffedd5);
            border: 1px solid #fdba74;
            color: #7c2d12;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 800;
        }

        .next-patient-banner .banner-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .next-patient-banner .banner-actions a {
            text-decoration: none;
            background: #b45309;
            color: #fff;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .inline-post-form {
            display: inline;
            margin: 0;
            padding: 0;
        }

        .next-patient-banner .banner-actions .inline-post-submit {
            border: 0;
            cursor: pointer;
            text-decoration: none;
            background: #b45309;
            color: #fff;
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 800;
        }

        .name-link.next-patient-name {
            color: #b45309;
            font-weight: 900;
            text-shadow: 0 0 4px rgba(180, 83, 9, 0.4), 0 0 12px rgba(180, 83, 9, 0.38);
            animation: nextPatientPulse 1s infinite ease-in-out;
        }

        .notify-next {
            background: linear-gradient(135deg, #b45309, #d97706);
            border-color: rgba(255, 214, 170, 0.55);
        }

        .notify-next.is-active {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            border-color: rgba(255, 237, 213, 0.95);
            box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.24), 0 12px 26px rgba(180, 83, 9, 0.38);
        }

        .actions a i {
            line-height: 1;
        }

        .actions .notify-next i {
            animation: notifyBell 1.6s ease-in-out infinite;
            transform-origin: top center;
        }

        @keyframes notifyBell {

            0%,
            100% {
                transform: rotate(0deg);
            }

            20% {
                transform: rotate(-10deg);
            }

            40% {
                transform: rotate(9deg);
            }

            60% {
                transform: rotate(-6deg);
            }

            80% {
                transform: rotate(5deg);
            }
        }

        @keyframes nextPatientPulse {
            0% {
                opacity: 0.55;
                text-shadow: 0 0 2px rgba(180, 83, 9, 0.32), 0 0 6px rgba(180, 83, 9, 0.3);
            }

            50% {
                opacity: 1;
                text-shadow: 0 0 7px rgba(180, 83, 9, 0.75), 0 0 18px rgba(180, 83, 9, 0.65);
            }

            100% {
                opacity: 0.55;
                text-shadow: 0 0 2px rgba(180, 83, 9, 0.32), 0 0 6px rgba(180, 83, 9, 0.3);
            }
        }

        .name-link:hover {
            color: #c58c41;
        }

        .actions {
            white-space: nowrap;
        }

        .is-hidden {
            display: none !important;
        }

        .actions a,
        .actions .inline-post-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 34px;
            height: 34px;
            margin: 0 2px;
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.18);
            transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
            padding: 0;
        }

        .actions a:hover,
        .actions .inline-post-submit:hover {
            transform: translateY(-1px) scale(1.03);
            filter: brightness(1.06);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.25);
        }

        .edit {
            background: linear-gradient(135deg, #0f766e, #0d9488);
        }

        .delete {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
        }

        .empty {
            padding: 28px;
            color: var(--muted);
            font-size: 17px;
            font-weight: 800;
        }

        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .container.sidebar-open {
                grid-template-columns: 1fr;
            }

            .sidebar.hidden {
                display: none;
            }

            .container.sidebar-open .main-content {
                transform: none;
            }

            .table-responsive {
                max-height: none;
            }
        }

        @media (max-width: 700px) {

            header,
            .container {
                padding: 0 10px;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }

            .visits-page-header {
                flex-direction: column;
                align-items: stretch;
                padding: 16px;
            }

            .visits-page-header .clinic-actions {
                justify-content: flex-start;
            }
        }

        @media (prefers-reduced-motion: no-preference) {

            .card,
            .sidebar,
            .table-responsive {
                animation: riseIn 0.4s ease;
            }
        }

        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <link rel="stylesheet" href="assets/ui-unified.css">
</head>

<body class="ui-unified clinic-polished">

    <header class="clinic-page-header visits-page-header">
        <div>
            <h1 class="clinic-page-title">🏥 زيارات اليوم</h1>
            <p class="clinic-page-subtitle"><?php echo date('d/m/Y'); ?> — إدارة وصول المرضى وحالة الزيارة من مكان واحد</p>
        </div>
        <div class="clinic-actions">
            <button class="toggle-sidebar" onclick="toggleSidebar()">☰ إظهار القائمة</button>
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="تبديل الوضع">🌙</button>
        </div>
    </header>

    <div class="container" id="layoutContainer">

        <aside class="sidebar hidden" id="sidebar">
            <div class="brand-with-logo">
                <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
                <div class="brand-text">
                    <span class="brand-title">عيادة الدكتور حيدر صباح الربيعي</span>
                    <span class="brand-subtitle">لوحة زيارات اليوم</span>
                </div>
            </div>
            <p class="sidebar-brand-meta">تنقل سريع لإدارة الزيارات والإجراءات</p>

            <div class="menu-group">
                <div class="menu-title">📊 الرئيسية</div>
                <div class="menu-links">
                    <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> لوحة التحكم</a>
                </div>
            </div>

            <details class="menu-group" data-menu-key="patients" open>
                <summary>👤 المرضى</summary>
                <div class="menu-links">
                    <a href="add-patient.php"><i class="fa-solid fa-user-plus"></i> إضافة مريض</a>
                    <a href="confirmed-list.php"><i class="fa-solid fa-bed-pulse"></i> قوائم العمليات</a>
                    <a href="followups.php"><i class="fa-solid fa-stethoscope"></i> المتابعة</a>
                </div>
            </details>

            <details class="menu-group" data-menu-key="appointments">
                <summary>📅 المواعيد</summary>
                <div class="menu-links">
                    <a href="visits.php"><i class="fa-solid fa-calendar-day"></i> زيارات اليوم</a>
                    <a href="procedure-entries.php"><i class="fa-solid fa-camera-retro"></i> إدخال الإجراءات</a>
                    <a href="operation-by-date.php"><i class="fa-solid fa-calendar-check"></i> مواعيد العمليات</a>
                    <a href="expected_appointments.php"><i class="fa-solid fa-clock"></i> المواعيد المتوقعة</a>
                </div>
            </details>

            <details class="menu-group" data-menu-key="system">
                <summary>⚙️ النظام</summary>
                <div class="menu-links">
                    <a href="reports.php"><i class="fa-solid fa-chart-line"></i> التقارير</a>
                    <?php if ($isAdminUser): ?>
                        <a href="daily-revenue.php"><i class="fa-solid fa-sack-dollar"></i> الإيراد اليومي</a>
                    <?php endif; ?>
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> الإعدادات</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
                </div>
            </details>
        </aside>

        <div class="main-content">

            <div class="stats">
                <div class="card total-card">
                    <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    <div>
                        <div class="num"><?= $stats['total'] ?></div>
                        <div class="label">إجمالي الزيارات</div>
                    </div>
                </div>
                <div class="card free-card">
                    <div class="card-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    <div>
                        <div class="num"><?= $stats['free'] + $stats['charity'] ?></div>
                        <div class="label">مراجعة + مجانية</div>
                    </div>
                </div>
                <div class="card pending-card">
                    <div class="card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div>
                        <div class="num"><?= $stats['pending'] ?></div>
                        <div class="label">قيد الانتظار</div>
                    </div>
                </div>
                <div class="card done-card">
                    <div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="num"><?= $stats['done'] ?></div>
                        <div class="label">تمت المعاينة</div>
                    </div>
                </div>
                <div class="card unpaid-card">
                    <div class="card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <div class="num"><?= $stats['unpaid'] ?></div>
                        <div class="label">غير مدفوع</div>
                    </div>
                </div>

            </div>

            <?php if ($isAdminUser): ?>
                <?php
                $visitIncome = (float) ($todayRevenue['visit_income'] ?? 0);
                $proceduresIncome = (float) ($todayRevenue['procedures_income'] ?? 0);
                $otherIncome = (float) ($todayRevenue['other_income'] ?? 0);
                $serviceDue = (float) ($todayRevenue['service_staff_due'] ?? 0);
                $totalIncome = $visitIncome + $proceduresIncome + $otherIncome;
                $netIncome = $totalIncome - $serviceDue;
                ?>
                <div class="finance-admin-card">
                    <div class="meta">
                        ملخص الإيراد لليوم: <strong><?php echo number_format($totalIncome, 0); ?></strong>
                        | مستحقات الخدمة: <strong><?php echo number_format($serviceDue, 0); ?></strong>
                        | الصافي: <strong><?php echo number_format($netIncome, 0); ?></strong>
                    </div>
                    <a class="open-revenue" href="daily-revenue.php?date=<?php echo urlencode($today); ?>">فتح شاشة الإيراد</a>
                </div>
            <?php endif; ?>

            <div class="visit-tools">
                <?php if ($nextPatientAlert): ?>
                    <div class="next-patient-banner">
                        <div>
                            المريض القادم الآن:
                            <strong class="clinic-user-content" data-no-translate><?= htmlspecialchars($nextPatientAlert['full_name']) ?></strong>
                            | القسم: <?= htmlspecialchars($nextPatientAlert['queue'] ?? 'زيارات اليوم') ?>
                            | الوقت: <?= htmlspecialchars($nextPatientAlert['notified_at'] ?? '-') ?>
                        </div>
                        <div class="banner-actions">
                            <a href="patient-file.php?id=<?= (int) $nextPatientAlert['patient_id'] ?>">فتح الملف</a>
                            <form class="inline-post-form" action="notify-next-patient.php" method="post">
                                <?= clinic_csrf_input() ?>
                                <input type="hidden" name="action" value="clear">
                                <input type="hidden" name="back" value="<?= h('visits.php?status=' . $status_filter) ?>">
                                <button type="submit" class="inline-post-submit">تم الاستدعاء</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 ابحث باسم المريض أو نوع الزيارة أو الرقم التسلسلي...">
                </div>

                <div class="helper-strip">
                    <span class="helper-pill"><i class="fa-solid fa-magnifying-glass"></i> بحث مباشر</span>
                </div>

                <div class="status-filters" aria-label="فلترة حالة الزيارة">
                    <a class="status-filter <?= $status_filter === 'all' ? 'active' : '' ?>" href="visits.php">
                        <i class="fa-solid fa-list"></i>
                        الكل
                    </a>
                    <a class="status-filter <?= $status_filter === 'pending' ? 'active' : '' ?>" href="visits.php?status=pending">
                        <i class="fa-solid fa-hourglass-half"></i>
                        قيد الانتظار
                    </a>
                    <a class="status-filter <?= $status_filter === 'done' ? 'active' : '' ?>" href="visits.php?status=done">
                        <i class="fa-solid fa-circle-check"></i>
                        تمت المعاينة
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>التسلسل</th>
                            <th style="text-align: right;">اسم المريض</th>
                            <th>نوع الزيارة</th>
                            <th>حالة الزيارة</th>
                            <th>الدفع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visits)): ?>
                            <tr>
                                <td colspan="6" class="empty">لا توجد زيارات مطابقة لهذا الفلتر</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visits as $row): ?>
                                <?php
                                switch ($row['visit_type']) {
                                    case 'first':
                                        $visit_text = 'زيارة أول مرة';
                                        $visit_class = 'first';
                                        break;
                                    case 'repeat':
                                        $visit_text = 'زيارة متكررة';
                                        $visit_class = 'repeat';
                                        break;
                                    case 'free':
                                        $visit_text = 'زيارة مراجعة';
                                        $visit_class = 'free';
                                        break;
                                    case 'charity':
                                        $visit_text = 'زيارة مجانية';
                                        $visit_class = 'charity';
                                        break;
                                    default:
                                        $visit_text = 'غير معروف';
                                        $visit_class = '';
                                }

                                $isNoFeeVisit = in_array((string) ($row['visit_type'] ?? ''), ['free', 'charity'], true);
                                $isPaid = $isNoFeeVisit || ((int) ($row['is_paid'] ?? 0) === 1);
                                $lastVisitText = $row['last_visit_date'] !== null ? ('آخر زيارة: ' . $row['last_visit_date']) : 'لا توجد زيارة سابقة';
                                ?>
                                <tr data-patient-id="<?= (int) $row['patient_id'] ?>" data-search="<?= h($row['full_name'] . ' ' . $visit_text . ' ' . $row['daily_serial'] . ' ' . $row['visit_id']) ?>" class="<?= (!$isNoFeeVisit && !$isPaid) ? 'row-unpaid' : 'row-paid' ?>">
                                    <td><?= $row['daily_serial'] ?></td>
                                    <td class="patient-col">
                                        <div class="name-cell">
                                            <a class="name-link clinic-user-content <?= ((int) $row['patient_id'] === $nextPatientId) ? 'next-patient-name' : '' ?>" data-no-translate href="patient-file.php?id=<?= $row['patient_id'] ?>">
                                                <?= htmlspecialchars($row['full_name']) ?>
                                            </a>
                                        </div>
                                        <small class="patient-meta">العمر: <?= htmlspecialchars((string) ($row['age'] ?? '-')) ?> | <?= htmlspecialchars($lastVisitText) ?></small>
                                    </td>
                                    <td><span class="badge <?= $visit_class ?>"><?= $visit_text ?></span></td>
                                    <td>
                                        <?php
                                        if ($row['is_done'] == 1) {
                                            echo '<span class="badge status-done">تمت المعاينة</span>';
                                        } else {
                                            echo '<span class="badge status-pending">قيد الانتظار</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($isNoFeeVisit): ?>
                                            <span class="badge status-no-fee">بدون كشف</span>
                                        <?php elseif ($isPaid): ?>
                                            <form class="payment-toggle-form" action="toggle-visit-payment.php" method="post">
                                                <?= clinic_csrf_input() ?>
                                                <input type="hidden" name="visit_id" value="<?= (int) $row['visit_id'] ?>">
                                                <input type="hidden" name="is_paid" value="0">
                                                <input type="hidden" name="back" value="<?= h('visits.php?status=' . $status_filter) ?>">
                                                <button
                                                    class="badge status-paid payment-toggle"
                                                    title="اضغط للتغيير إلى غير واصل"
                                                    type="submit">واصل</button>
                                            </form>
                                        <?php else: ?>
                                            <form class="payment-toggle-form" action="toggle-visit-payment.php" method="post">
                                                <?= clinic_csrf_input() ?>
                                                <input type="hidden" name="visit_id" value="<?= (int) $row['visit_id'] ?>">
                                                <input type="hidden" name="is_paid" value="1">
                                                <input type="hidden" name="back" value="<?= h('visits.php?status=' . $status_filter) ?>">
                                                <button
                                                    class="badge status-unpaid payment-toggle"
                                                    title="اضغط للتغيير إلى واصل"
                                                    type="submit">غير واصل</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">

                                        <a class="edit" data-label="تعديل الزيارة" title="تعديل الزيارة" href="edit-visit.php?id_edit=<?= $row['visit_id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form class="inline-post-form" action="notify-next-patient.php" method="post">
                                            <?= clinic_csrf_input() ?>
                                            <input type="hidden" name="action" value="set">
                                            <input type="hidden" name="patient_id" value="<?= (int) $row['patient_id'] ?>">
                                            <input type="hidden" name="queue" value="<?= h('زيارات اليوم') ?>">
                                            <input type="hidden" name="meta" value="<?= h('التسلسل: ' . ((string) ($row['daily_serial'] ?? '-'))) ?>">
                                            <input type="hidden" name="back" value="<?= h('visits.php?status=' . $status_filter) ?>">
                                            <button
                                                class="inline-post-submit notify-next <?= ((int) $row['patient_id'] === $nextPatientId) ? 'is-active' : '' ?>"
                                                data-label="تنبيه الطبيب"
                                                title="تنبيه الطبيب بالمريض القادم"
                                                type="submit">
                                                <i class="fa-solid fa-bell-concierge"></i>
                                            </button>
                                        </form>
                                        <a class="delete" data-label="حذف الزيارة" title="حذف الزيارة"
                                            href="delete-visits.php?id_delete=<?= $row['visit_id'] ?>"
                                            onclick="return confirm('هل أنت متأكد من حذف هذه الزيارة؟');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const container = document.getElementById('layoutContainer');
            const btn = document.querySelector('.toggle-sidebar');

            sidebar.classList.toggle('hidden');
            container.classList.toggle('sidebar-open');

            btn.textContent = sidebar.classList.contains('hidden') ?
                '➡️ إظهار القائمة' :
                '⬅️ إخفاء القائمة';
        }

        function setupSidebarAccordion() {
            const groups = Array.from(document.querySelectorAll('#sidebar details.menu-group[data-menu-key]'));
            if (!groups.length) return;

            const storageKey = 'visits_sidebar_open_group';
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

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            const rows = Array.from(document.querySelectorAll('tbody tr[data-search]'));
            const updateRows = () => {
                const filter = searchInput.value.trim().toLowerCase();
                rows.forEach(row => {
                    const haystack = (row.getAttribute('data-search') || '').toLowerCase();
                    const matches = !filter || haystack.includes(filter);
                    row.classList.toggle('is-hidden', !matches);
                });
            };

            searchInput.addEventListener('input', updateRows);
            updateRows();
        }

        setupSidebarAccordion();
    </script>

</body>

</html>