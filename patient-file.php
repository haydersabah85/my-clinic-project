<?php
include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_column($con, 'va', 'iop_od', 'VARCHAR(50) NULL');
clinic_ensure_column($con, 'va', 'iop_os', 'VARCHAR(50) NULL');
clinic_ensure_retina_drawings($con);
$flash = clinic_take_flash();

$row = null;
$id = (int)($_GET['id'] ?? $_GET['id_open'] ?? $_GET['patient_id'] ?? 0);

if ($id > 0) {
    $patientWhere = clinic_active_patient_where($con, 'add_patient');
    $stmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ? AND $patientWhere");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

if (!$row) {
    http_response_code(404);
    die('لم يتم العثور على المريض المطلوب.');
}

if (!function_exists('pf_format_visit_note')) {
    function pf_format_visit_note(string $note): string
    {
        $safeText = nl2br(h($note));

        return preg_replace_callback('/\b(OD|OS|OU)\b/u', function ($matches) {
            $eye = strtoupper((string)$matches[1]);
            $classMap = [
                'OD' => 'eye-od',
                'OS' => 'eye-os',
                'OU' => 'eye-ou',
            ];
            $eyeClass = $classMap[$eye] ?? 'eye-ou';
            return "<span class='eye-badge {$eyeClass}'>$eye</span>";
        }, $safeText) ?? $safeText;
    }
}

if (!function_exists('pf_extract_first_visit_summary')) {
    function pf_extract_first_visit_summary(string $note): array
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $note));
        if ($normalized === '') {
            return [
                'diagnosis' => '',
                'plan' => '',
                'preview' => '',
            ];
        }

        $lines = preg_split('/\n+/u', $normalized) ?: [];
        $diagnosis = '';
        $plan = '';

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if ($diagnosis === '' && preg_match('/^(?:التشخيص(?:\s+الأولي)?|تشخيص|dx|diagnosis|impression)\s*[:\-]?\s*(.*)$/iu', $line, $m)) {
                $diagnosis = trim((string) ($m[1] ?? ''));
                continue;
            }

            if ($plan === '' && preg_match('/^(?:الخطة(?:\s+العلاجية)?|خطة\s*العلاج|العلاج|التوصيات?|plan|recommendation|rx)\s*[:\-]?\s*(.*)$/iu', $line, $m)) {
                $plan = trim((string) ($m[1] ?? ''));
            }
        }

        $preview = $normalized;
        if (mb_strlen($preview) > 260) {
            $preview = mb_substr($preview, 0, 260) . '...';
        }

        return [
            'diagnosis' => $diagnosis,
            'plan' => $plan,
            'preview' => $preview,
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📁 <?php echo htmlspecialchars($row['full_name'] ?? 'ملف مريض'); ?></title>
    <script src="assets/theme.js" defer></script>
    <style>
        /* ====== الخط والخلفية العامة ====== */
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');


        /* ==================================================
   MODERN PATIENT DASHBOARD - PHASE 1
   ================================================== */

        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;

            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;

            --shadow-sm: 0 4px 12px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 10px 30px rgba(15, 23, 42, 0.08);
            --shadow-lg: 0 20px 50px rgba(15, 23, 42, 0.12);

            --radius: 20px;
            --radius-sm: 12px;

            --space-1: 8px;
            --space-2: 12px;
            --space-3: 16px;
            --space-4: 24px;
            --space-5: 32px;

            --text-soft: #334155;
            --easing-standard: cubic-bezier(0.4, 0, 0.2, 1);
            --motion-fast: 0.2s;
            --motion-normal: 0.25s;
        }

        /* ==================================================
   GENERAL
================================================== */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 0;
            padding: 24px;
            line-height: 1.55;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.06), transparent 35%),
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.05), transparent 30%),
                linear-gradient(180deg, #f8fafc, #eef4fb);
            color: var(--text);
        }

        /* Dark Mode */
        body[data-theme="dark"] {
            --primary: #60a5fa;
            --primary-dark: #93c5fd;
            --secondary: #38bdf8;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #fb7185;
            --purple: #c4b5fd;

            --bg: #07111d;
            --card: #0f1b2a;
            --text: #e6edf5;
            --muted: #9fb0c2;
            --border: rgba(148, 163, 184, 0.18);

            --shadow-sm: 0 8px 20px rgba(0, 0, 0, 0.24);
            --shadow-md: 0 18px 45px rgba(0, 0, 0, 0.32);
            --shadow-lg: 0 24px 65px rgba(0, 0, 0, 0.42);

            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 34%),
                radial-gradient(circle at top left, rgba(20, 184, 166, 0.12), transparent 30%),
                linear-gradient(180deg, #07111d, #0b1220 58%, #08111d);
            color: var(--text);
        }

        /* ==================================================
   PAGE CONTAINER
================================================== */
        .page-container {
            max-width: 1800px;
            margin: 0 auto;
        }

        /* ==================================================
   HERO HEADER
================================================== */
        header {
            max-width: 1800px;
            margin: 0 auto 24px;
            display: flex;
            align-items: stretch;
            gap: 14px;
        }

        header h1 {
            flex: 1;
            margin: 0;
            padding: 24px 32px;
            border-radius: 28px;
            background: linear-gradient(135deg, #1d4ed8, #2563eb, #0ea5e9);
            color: #ffffff;
            text-align: center;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
            box-shadow: 0 20px 45px rgba(37, 99, 235, 0.22);
        }

        /* ==================================================
   PATIENT HERO CARD
================================================== */
        .patient_info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-md);

            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: stretch;
            justify-content: space-between;

        }

        .patient_info p {
            margin: 0;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.9;
            white-space: nowrap;
            flex: 1 1 200px;

        }

        .patient_info span:first-child {
            font-weight: 700;
            color: var(--muted);
            margin-left: 6px;
        }

        .patient_info span:last-child {
            color: var(--purple);
            font-weight: 800;
        }

        @media (max-width: 768px) {
            .patient_info {
                flex-direction: column;
                align-items: stretch;
            }

            .patient_info p {
                width: 100%;
            }

        }

        /* ==================================================
   GLOBAL BUTTONS
================================================== */
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 10px 16px;
            border-radius: 14px;
            border: none;

            background: linear-gradient(135deg, var(--success), #059669);
            color: #ffffff;
            text-decoration: none;

            font-size: 14px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;

            box-shadow: var(--shadow-sm);
            transition: transform var(--motion-normal) var(--easing-standard), box-shadow var(--motion-normal) var(--easing-standard), background var(--motion-normal) var(--easing-standard);
        }

        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }

        /* أزرار بطاقة المريض */
        .patient_info>a {
            min-height: 54px;
        }



        /* ==================================================
   QUICK ACTION TOOLBAR
================================================== */


        .nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            padding: 18px 24px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-md);

            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
        }

        /* ==================================================
   ICON BUTTONS
================================================== */
        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 56px;
            height: 56px;
            border-radius: 18px;

            font-size: 24px;
            padding: 0;
            position: relative;
            cursor: pointer;

            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            transition: all 0.25s ease;
        }

        .icon-btn:hover {
            transform: translateY(-3px) scale(1.05);
        }

        .edit-icon {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            color: #fff;
        }

        .delete-icon {
            background: linear-gradient(135deg, #ef4444, #991b1b);
            color: #fff;
        }

        .warning-icon {
            background: linear-gradient(135deg, #facc15, #eab308);
            color: #111827;
        }

        .visits-icon {
            background: linear-gradient(135deg, #a855f7, #7e22ce);
            color: #fff;
        }

        .retina-icon {
            background: linear-gradient(135deg, #0f766e, #0891b2);
            color: #fff;
        }

        .home-icon {
            background: linear-gradient(135deg, #6b7280, #374151);
            color: #fff;
        }

        .followup-btn {
            background: linear-gradient(135deg, #38bdf8, #0ea5e9);
            color: #fff;
        }

        .recipe-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
        }

        /* Tooltips */
        .icon-btn[data-title]::after {
            content: attr(data-title);
            position: absolute;
            bottom: 120%;
            right: 50%;
            transform: translateX(50%);
            background: #0f172a;
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        .icon-btn[data-title]:hover::after {
            opacity: 1;
        }

        /* Critical Blink */
        .critical-blink {
            animation: blink 1s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                box-shadow: 0 0 0 rgba(239, 68, 68, 0);
            }

            50% {
                box-shadow: 0 0 18px rgba(239, 68, 68, 0.8);
            }
        }

        /* ==================================================
   RESPONSIVE
================================================== */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }

            header h1 {
                font-size: 24px;
                padding: 18px;
                border-radius: 20px;
            }

            header {
                gap: 10px;
            }

            .header-theme-toggle {
                width: 56px;
                min-width: 56px;
                border-radius: 18px;
                font-size: 20px;
            }

            .patient_info {
                padding: 18px;
                border-radius: 20px;
            }

            .nav {
                padding: 14px;
                gap: 12px;
                border-radius: 20px;
            }

            .icon-btn {
                width: 48px;
                height: 48px;
                font-size: 20px;
                border-radius: 14px;
            }

            .patient_info>a {
                width: 100%;
            }
        }

        /* ==================================================
   MEDICAL DASHBOARD GRID
================================================== */
        .previous_data {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            align-items: start;
        }

        .previous_visits {
            grid-column: 1 / -1;
        }

        /* بطاقات الأقسام */
        .previous_visits,
        .previous_va,
        .previous_surgeries,
        .previous_lasers,
        .previous_injections,
        .previous_medicines,
        .patient_visits {
            width: 100%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            padding: 22px;
            box-shadow: var(--shadow-md);
            overflow: auto;
            direction: ltr;
        }

        /* ارتفاعات مناسبة */
        .previous_visits,
        .previous_va {
            max-height: none;
        }

        .previous_surgeries,
        .previous_lasers,
        .previous_injections {
            max-height: 520px;

        }

        .patient_visits {
            min-height: 300px;
        }

        /* ==================================================
   PREVIOUS MEDICINES
================================================== */
        .previous_medicines {
            width: 100%;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 24px;
            padding: 22px;
            box-shadow: var(--shadow-md);
            overflow: auto;
            max-height: 320px;
            direction: ltr;
        }

        /* ==================================================
   SECTION TITLES
================================================== */
        .section-title {
            margin: 0 0 16px;
            font-size: 21px;
            font-weight: 800;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.2px;
        }

        /* ==================================================
   TABLES
================================================== */
        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            box-shadow: none;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        th {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 12px 10px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        th:first-child {
            border-top-right-radius: 12px;
        }

        th:last-child {
            border-top-left-radius: 12px;
        }

        td {
            padding: 10px 8px;
            font-size: 13px;
            text-align: center;
            border-bottom: 1px solid #edf2f7;
            white-space: nowrap;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .procedure-list {
            display: grid;
            gap: 12px;
        }

        .procedure-card {
            border: 1px solid #dbe7ef;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px;
            box-shadow: var(--shadow-sm);
        }

        .procedure-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .procedure-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .procedure-title strong {
            color: var(--text);
            font-size: 16px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .procedure-title span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .procedure-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .procedure-meta div {
            border: 1px solid #edf2f7;
            border-radius: 12px;
            background: #f8fafc;
            padding: 8px 10px;
        }

        .procedure-meta span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
        }

        .procedure-meta strong {
            display: block;
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .procedure-note {
            margin: 0;
            border-top: 1px solid #edf2f7;
            padding-top: 10px;
            color: #334155;
            font-size: 13px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .procedure-actions {
            display: flex;
            gap: 8px;
            flex: 0 0 auto;
        }

        .section-subtitle {
            margin: -6px 0 14px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.7;
        }

        .visit-timeline {
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: none;
            overflow: visible;
            padding-left: 4px;
        }

        .timeline-summary {
            margin: 0;
            padding: 10px 12px;
            border: 1px solid #dbe7ef;
            border-radius: 12px;
            background: #f8fafc;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.8;
            direction: rtl;
        }

        .first-visit-summary {
            position: sticky;
            top: 12px;
            z-index: 5;
            margin: 0 auto;
            width: min(920px, 100%);
            padding: 10px 12px;
            border: 1px solid #86efac;
            border-radius: 14px;
            background: linear-gradient(180deg, #f0fdf4, #ecfdf5);
            color: #14532d;
            box-shadow: 0 8px 22px rgba(22, 101, 52, 0.12);
            direction: rtl;
        }

        .first-visit-summary strong {
            display: inline-block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 900;
        }

        .first-visit-summary .summary-date {
            font-weight: 800;
        }

        .first-visit-summary .summary-note {
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.9;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .first-visit-layout {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 8px;

        }

        .summary-col {
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.66);
            padding: 8px 9px;
            direction: ltr;
        }

        .summary-col-title {
            font-size: 12px;
            font-weight: 900;
            color: #166534;
            margin-bottom: 7px;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .summary-item-label {
            font-size: 11px;
            font-weight: 900;
            color: #166534;
            margin-bottom: 4px;
        }

        .summary-item-value {
            color: #14532d;
            line-height: 1.85;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .summary-va {
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 6px 8px;
            background: rgba(255, 255, 255, 0.65);
        }

        .summary-va-title {
            font-size: 12px;
            font-weight: 900;
            color: #166534;
            margin-bottom: 6px;
        }

        .summary-va-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            font-size: 12px;
            color: #14532d;
        }

        .summary-empty {
            font-size: 12px;
            color: #166534;
            line-height: 1.7;
        }

        .visit-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #ffffff;
            padding: 16px;
            box-shadow: var(--shadow-sm);
        }

        .visit-card-header,
        .visit-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .visit-card-header {
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .visit-date {
            color: var(--primary-dark);
            font-size: 18px;
            font-weight: 800;
        }

        .visit-note-block {
            margin: 0 0 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            color: var(--text);
            line-height: 1.9;
            white-space: pre-wrap;
            text-align: right;
        }

        .visit-va-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .eye-panel {
            border: 1px solid #e5edf5;
            border-radius: 14px;
            padding: 12px;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
        }

        .eye-panel h4 {
            margin: 0 0 10px;
            color: var(--primary-dark);
            font-size: 16px;
            font-weight: 800;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
        }

        .metric-row:last-child {
            border-bottom: 0;
        }

        .metric-row span:first-child {
            color: var(--muted);
            font-weight: 700;
        }

        .metric-row span:last-child {
            color: var(--text);
            font-weight: 800;
            text-align: left;
        }

        .empty-state {
            margin: 0;
            padding: 18px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            color: var(--muted);
            text-align: center;
            background: #f8fafc;
            direction: rtl;
        }

        @media (max-width: 768px) {
            .visit-va-grid {
                grid-template-columns: 1fr;
            }

            .first-visit-summary {
                position: static;
                width: 100%;
            }

            .first-visit-layout {
                grid-template-columns: 1fr;
            }

            .previous_data,
            .procedure-meta {
                grid-template-columns: 1fr;
            }
        }

        .chart-board {
            display: grid;
            gap: 16px;
        }

        .chart-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #f8fafc;
            position: sticky;
            top: 12px;
            z-index: 3;
            direction: rtl;
        }

        .chart-toolbar strong {
            color: var(--text);
            font-size: 15px;
        }

        .chart-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chart-filter {
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #ffffff;
            color: var(--muted);
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 13px;
            transition: all 0.2s ease;
        }

        .chart-filter.active,
        .chart-filter:hover {
            background: #0f766e;
            border-color: #0f766e;
            color: #ffffff;
        }

        .encounter-card {
            position: relative;
            border: 1px solid #dbe7ef;
            border-radius: 18px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--motion-fast) var(--easing-standard), transform var(--motion-fast) var(--easing-standard);
        }

        .encounter-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }

        .encounter-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: #0f766e;
        }

        .encounter-card.no-va::before {
            background: #f59e0b;
        }

        .encounter-card.first-visit {
            border-color: #0f766e;
            box-shadow: 0 14px 28px rgba(15, 118, 110, 0.16);
        }

        .first-visit-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            font-size: 11px;
            font-weight: 900;
            padding: 5px 9px;
        }

        .encounter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 18px 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .encounter-head-main {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .encounter-toggle {
            min-height: 34px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #0f172a;
            font-family: 'Cairo', sans-serif;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            padding: 6px 11px;
            transition: all 0.2s ease;
        }

        .encounter-toggle:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .encounter-date {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .encounter-date span:first-child {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .encounter-date span:last-child {
            color: #0f766e;
            font-size: 20px;
            font-weight: 900;
        }

        .encounter-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            font-size: 12px;
            font-weight: 900;
            padding: 7px 11px;
        }

        .encounter-status.missing {
            background: #fff7ed;
            color: #c2410c;
        }

        .encounter-body {
            display: grid;
            grid-template-columns: minmax(260px, 0.95fr) minmax(360px, 1.45fr);
            gap: 16px;
            padding: 16px 18px 18px;
        }

        .encounter-main {
            display: block;
        }

        .encounter-main.is-collapsed {
            display: none;
        }

        .clinical-note {
            min-height: 100%;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e5edf5;
            padding: 14px;
        }

        .clinical-note h4,
        .exam-panel h4 {
            margin: 0 0 10px;
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
        }

        .clinical-note p {
            margin: 0;
            color: #334155;
            line-height: 2;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .visit-note-item {
            margin: 0;
            padding: 11px 12px;
            border: 1px solid #dbe7ef;
            border-radius: 12px;
            background: #ffffff;
            color: var(--text-soft);
            font-size: 14px;
            font-weight: 700;
            line-height: 2;
            overflow-wrap: anywhere;
        }

        .stat-value,
        .encounter-date span:last-child,
        .procedure-title span,
        .timeline-summary {
            font-variant-numeric: tabular-nums;
        }

        .visit-note-item+.visit-note-item {
            margin-top: 10px;
        }

        .clinical-note .eye-badge {
            font-size: 11px;
            padding: 3px 9px;
            margin: 0 2px;
            vertical-align: middle;
        }

        .clinical-note p+p {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #dbe7ef;
        }

        .exam-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .exam-panel {
            border: 1px solid #dbe7ef;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            padding: 14px;
        }

        .exam-panel.od {
            border-top: 4px solid #2563eb;
        }

        .exam-panel.os {
            border-top: 4px solid #0f766e;
        }

        .exam-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .exam-metric {
            min-height: 62px;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            background: #ffffff;
            padding: 9px 10px;
        }

        .exam-metric span {
            display: block;
        }

        .exam-metric span:first-child {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
        }

        .exam-metric span:last-child {
            color: var(--text);
            font-size: 16px;
            font-weight: 900;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .iop-normal span:last-child {
            color: #047857;
        }

        .iop-alert span:last-child {
            color: #dc2626;
        }

        .encounter-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            padding: 0 18px 16px;
            border-top: 1px solid #edf2f7;
            margin-top: 2px;
        }

        .encounter-actions .text-action {
            min-height: 36px;
            border-radius: 10px;
            padding: 8px 12px;
            color: #ffffff;
            background: #0f766e;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .encounter-actions .text-action.secondary {
            background: #2563eb;
        }

        .encounter-actions .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 18px;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .encounter-actions .icon-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.04);
        }

        .encounter-actions .edit-icon {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .encounter-actions .delete-icon {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        .encounter-actions .add-note-btn {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
        }

        .encounter-actions .retina-action {
            background: linear-gradient(135deg, #0891b2, #0f766e);
        }

        .retina-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .retina-preview {
            display: block;
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 10px;
            color: #0f172a;
            text-decoration: none;
        }

        .retina-preview img {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: contain;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .retina-preview span {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 900;
            color: #0f766e;
        }

        @media (max-width: 1100px) {
            .encounter-body {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {

            .exam-grid,
            .exam-metrics {
                grid-template-columns: 1fr;
            }

            .encounter-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        /* ==================================================
   NOTE TOOLTIP
================================================== */
        .visit-note,
        .surgery-notes {
            position: relative;
            cursor: pointer;
            direction: ltr;
        }

        .visit-note::after,
        .surgery-notes::after {
            content: attr(data-note);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            width: 260px;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1.7;
            white-space: normal;
            box-shadow: var(--shadow-md);
            display: none;
            z-index: 20;
        }

        .visit-note:hover::after,
        .surgery-notes:hover::after {
            display: block;
        }

        /* ==================================================
   SMALL ICONS INSIDE TABLES
================================================== */
        .previous_data .icon-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 15px;
            box-shadow: 0 4px 8px rgba(15, 23, 42, 0.12);
        }

        /* ==================================================
   VISIT FORM
================================================== */
        .patient_visits form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            height: 100%;
        }

        #notes {
            width: 100%;
            min-height: 120px;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #f8fafc;
            font-size: 15px;
            line-height: 1.8;
            direction: ltr;
            resize: vertical;
            font-family: 'Cairo', sans-serif;
            transition: all 0.2s ease;
        }

        #notes:focus {
            outline: none;
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        #add_visit {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.22);
            transition: all 0.2s ease;
        }

        #add_visit:hover {
            transform: translateY(-2px);
        }

        /* ==================================================
   QUICK LINKS
================================================== */

        /* ==================================================
   QUICK LINKS - SINGLE ROW
================================================== */
        .links {
            margin-top: 28px;
            display: flex;
            flex-wrap: nowrap;
            /* صف واحد */
            justify-content: center;
            gap: 14px;
            overflow-x: auto;
            /* يسمح بالتمرير الأفقي إذا لم تكفِ المساحة */
            padding-bottom: 6px;
            scrollbar-width: thin;
        }

        /* تحسين شريط التمرير في المتصفحات المعتمدة على WebKit */
        .links::-webkit-scrollbar {
            height: 6px;
        }

        .links::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 999px;
        }

        .links a {
            flex: 0 0 auto;
            /* يمنع تمدد الأزرار */
            min-width: 170px;
            min-height: 58px;
            padding: 14px 18px;
            border-radius: 16px;
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 10px 20px rgba(234, 88, 12, 0.18);
            transition: all 0.2s ease;
        }

        .links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(234, 88, 12, 0.24);
        }

        /* على الشاشات الصغيرة نسمح بالالتفاف لتبقى القراءة مريحة */
        @media (max-width: 768px) {
            .links {
                flex-wrap: wrap;
                overflow-x: visible;
                justify-content: stretch;
            }

            .links a {
                flex: 1 1 calc(50% - 14px);
                min-width: 0;
            }
        }

        /* ==========================================
   MODAL BACKDROP
========================================== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;

            /* توسيط المحتوى */
            display: none;
            align-items: center;
            justify-content: center;

            /* خلفية داكنة خلف النافذة */
            background: rgba(15, 23, 42, 0.55);

            padding: 20px;
        }

        /* عند فتح النافذة عبر JavaScript */
        .modal.show {
            display: flex;
        }

        /* ==========================================
   MODAL WINDOW
========================================== */
        .modal-content {
            background: #ffffff;
            /* خلفية بيضاء كاملة */
            opacity: 1;
            /* غير شفافة */
            width: min(420px, 100%);
            padding: 28px;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
            position: relative;
            animation: modalPop 0.25s ease;
        }

        /* حركة الظهور */
        @keyframes modalPop {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* ==================================================
   RESPONSIVE
================================================== */
        @media (max-width: 1200px) {
            .previous_data {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {

            .previous_visits,
            .previous_va,
            .previous_surgeries,
            .previous_lasers,
            .previous_injections,
            .patient_visits {
                padding: 16px;
                border-radius: 18px;
            }

            .section-title {
                font-size: 18px;
            }

            td,
            th {
                font-size: 12px;
            }

            .links {
                grid-template-columns: 1fr;
            }
        }

        /* ==================================================
   STATISTICS CARDS
================================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 22px;
            padding: 20px 16px;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            display: block;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-value {
            display: block;
            font-size: 30px;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.2;
        }

        .stat-label {
            display: block;
            margin-top: 4px;
            font-size: 14px;
            font-weight: 700;
            color: var(--muted);
        }

        /* تثبيت نموذج الزيارة أثناء التمرير */
        @media (min-width: 1201px) {
            .patient_visits {
                position: sticky;
                top: 20px;
            }
        }

        /* ==================================================
   EYE BADGES
================================================== */
        .eye-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            color: #ffffff;
        }

        .eye-od {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .eye-os {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .eye-ou {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }


        /* ==================================================
   PRESCRIPTION CARDS
================================================== */
        .prescription-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 16px;
            width: 100%;
        }

        .prescription-header {
            margin-bottom: 10px;
        }

        .prescription-date {
            font-weight: 800;
            color: var(--primary-dark);
            font-size: 15px;
        }

        .prescription-diagnosis {
            margin-bottom: 12px;
            color: #475569;
            font-weight: 600;
            line-height: 1.8;
        }

        .prescription-list {
            margin: 0;
            padding-right: 20px;
            line-height: 2;
        }

        .prescription-list li {
            margin-bottom: 6px;
        }

        .prescription-footer {
            margin-top: 14px;
            text-align: left;
        }

        .view-prescription-btn {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .theme-toggle {
            border: 0;
            font-family: 'Cairo', sans-serif;
        }

        .header-theme-toggle {
            width: 72px;
            min-width: 72px;
            border-radius: 24px;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .header-theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        body[data-theme="dark"] header h1 {
            background: linear-gradient(135deg, #0f2d5c, #155e9f, #0f766e);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.36);
            border: 1px solid rgba(147, 197, 253, 0.22);
        }

        body[data-theme="dark"] .header-theme-toggle {
            background: linear-gradient(135deg, #075985, #0f766e);
            border: 1px solid rgba(147, 197, 253, 0.22);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.32);
        }

        body[data-theme="dark"] .patient_info,
        body[data-theme="dark"] .nav,
        body[data-theme="dark"] .previous_visits,
        body[data-theme="dark"] .previous_va,
        body[data-theme="dark"] .previous_surgeries,
        body[data-theme="dark"] .previous_lasers,
        body[data-theme="dark"] .previous_injections,
        body[data-theme="dark"] .previous_medicines,
        body[data-theme="dark"] .patient_visits,
        body[data-theme="dark"] .stat-card {
            background: linear-gradient(145deg, rgba(15, 27, 42, 0.96), rgba(11, 18, 32, 0.96));
            border-color: rgba(148, 163, 184, 0.18);
            box-shadow: var(--shadow-md);
        }

        body[data-theme="dark"] .visit-card {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.16);
        }

        body[data-theme="dark"] .visit-note-block,
        body[data-theme="dark"] .empty-state {
            background: rgba(15, 23, 42, 0.82);
            border-color: rgba(147, 197, 253, 0.16);
            color: #dce7f3;
        }

        body[data-theme="dark"] .eye-panel {
            background: rgba(2, 6, 23, 0.24);
            border-color: rgba(147, 197, 253, 0.16);
        }

        body[data-theme="dark"] .metric-row {
            border-bottom-color: rgba(148, 163, 184, 0.14);
        }

        body[data-theme="dark"] .chart-toolbar,
        body[data-theme="dark"] .clinical-note,
        body[data-theme="dark"] .exam-panel,
        body[data-theme="dark"] .exam-metric,
        body[data-theme="dark"] .encounter-card,
        body[data-theme="dark"] .procedure-card,
        body[data-theme="dark"] .procedure-meta div {
            background: rgba(15, 23, 42, 0.78);
            border-color: rgba(147, 197, 253, 0.16);
        }

        body[data-theme="dark"] .encounter-head {
            border-bottom-color: rgba(148, 163, 184, 0.14);
        }

        body[data-theme="dark"] .procedure-note {
            border-top-color: rgba(148, 163, 184, 0.14);
        }

        body[data-theme="dark"] .clinical-note p,
        body[data-theme="dark"] .procedure-note {
            color: #dce7f3;
        }

        body[data-theme="dark"] .visit-note-item {
            background: rgba(15, 23, 42, 0.82);
            border-color: rgba(147, 197, 253, 0.16);
            color: #dce7f3;
        }

        body[data-theme="dark"] .timeline-summary {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.16);
            color: #dce7f3;
        }

        body[data-theme="dark"] .first-visit-summary {
            background: rgba(22, 101, 52, 0.18);
            border-color: rgba(74, 222, 128, 0.5);
            color: #dcfce7;
        }

        body[data-theme="dark"] .summary-item,
        body[data-theme="dark"] .summary-va,
        body[data-theme="dark"] .summary-col {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(74, 222, 128, 0.35);
        }

        body[data-theme="dark"] .summary-item-label,
        body[data-theme="dark"] .summary-va-title {
            color: #86efac;
        }

        body[data-theme="dark"] .summary-item-value,
        body[data-theme="dark"] .summary-va-grid {
            color: #dcfce7;
        }

        body[data-theme="dark"] .clinical-note p+p {
            border-top-color: rgba(148, 163, 184, 0.25);
        }

        body[data-theme="dark"] .retina-preview {
            background: rgba(15, 23, 42, 0.82);
            border-color: rgba(147, 197, 253, 0.18);
            color: #dce7f3;
        }

        body[data-theme="dark"] .retina-preview img {
            background: rgba(2, 6, 23, 0.45);
            border-color: rgba(147, 197, 253, 0.18);
        }

        body[data-theme="dark"] .encounter-actions {
            border-top-color: rgba(148, 163, 184, 0.18);
        }

        body[data-theme="dark"] .chart-filter {
            background: rgba(2, 6, 23, 0.42);
            border-color: rgba(147, 197, 253, 0.18);
            color: #a8bdd1;
        }

        body[data-theme="dark"] .chart-filter.active,
        body[data-theme="dark"] .chart-filter:hover {
            background: #0f766e;
            color: #ffffff;
        }

        body[data-theme="dark"] .encounter-toggle {
            background: rgba(15, 23, 42, 0.82);
            border-color: rgba(147, 197, 253, 0.22);
            color: #dce7f3;
        }

        body[data-theme="dark"] .encounter-toggle:hover {
            background: rgba(30, 41, 59, 0.92);
        }

        body[data-theme="dark"] .patient_info,
        body[data-theme="dark"] .nav {
            backdrop-filter: blur(14px);
        }

        body[data-theme="dark"] .patient_info p {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.16);
            color: var(--text);
        }

        body[data-theme="dark"] .patient_info span:first-child {
            color: #a8bdd1;
        }

        body[data-theme="dark"] .patient_info span:last-child {
            color: #d8b4fe !important;
        }

        body[data-theme="dark"] .section-title {
            color: #93c5fd;
        }

        body[data-theme="dark"] .stat-card {
            position: relative;
            overflow: hidden;
        }

        body[data-theme="dark"] .stat-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, #60a5fa, #2dd4bf);
        }

        body[data-theme="dark"] .stat-card:hover {
            box-shadow: var(--shadow-lg);
        }

        body[data-theme="dark"] .stat-value {
            color: #93c5fd;
            text-shadow: 0 0 18px rgba(147, 197, 253, 0.22);
        }

        body[data-theme="dark"] .stat-label,
        body[data-theme="dark"] .prescription-diagnosis {
            color: #a8bdd1;
        }

        body[data-theme="dark"] th {
            background: linear-gradient(135deg, #1d4ed8, #075985);
            color: #ffffff;
        }

        body[data-theme="dark"] td {
            border-bottom-color: rgba(148, 163, 184, 0.14);
            color: #dce7f3;
        }

        body[data-theme="dark"] tbody tr:hover {
            background: rgba(96, 165, 250, 0.12);
        }

        body[data-theme="dark"] #notes,
        body[data-theme="dark"] .modal-content input[type="date"],
        body[data-theme="dark"] .modal-content input[type="text"] {
            background: rgba(15, 23, 42, 0.82);
            color: var(--text);
            border-color: rgba(147, 197, 253, 0.18);
        }

        body[data-theme="dark"] #notes:focus,
        body[data-theme="dark"] .modal-content input:focus {
            background: #0f1b2a;
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
        }

        body[data-theme="dark"] #notes::placeholder,
        body[data-theme="dark"] .modal-content input::placeholder {
            color: #71849a;
        }

        body[data-theme="dark"] .prescription-card {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        body[data-theme="dark"] .prescription-date {
            color: #93c5fd;
        }

        body[data-theme="dark"] .prescription-list {
            color: #dce7f3;
        }

        body[data-theme="dark"] .links a {
            background: linear-gradient(135deg, #0f766e, #0e7490);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);
        }

        body[data-theme="dark"] .modal {
            background: rgba(2, 6, 23, 0.72);
        }

        body[data-theme="dark"] .modal-content {
            background: #0f1b2a;
            border-color: rgba(147, 197, 253, 0.2);
            color: var(--text);
            box-shadow: var(--shadow-lg);
        }

        body[data-theme="dark"] .modal-content h3,
        body[data-theme="dark"] .modal-content label {
            color: var(--text);
        }

        body[data-theme="dark"] .icon-btn::after,
        body[data-theme="dark"] .visit-note::after,
        body[data-theme="dark"] .surgery-notes::after {
            background: #020617;
            border: 1px solid rgba(147, 197, 253, 0.2);
        }

        .app-sidebar-toggle {
            position: fixed;
            top: 14px;
            right: 14px;
            z-index: 1300;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 800;
            font-family: 'Cairo', sans-serif;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.26);
            cursor: pointer;
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 286px;
            max-width: 88vw;
            height: 100vh;
            background: var(--card);
            border-left: 1px solid var(--border);
            box-shadow: -18px 0 42px rgba(15, 23, 42, 0.24);
            padding: 20px 15px;
            overflow-y: auto;
            transform: translateX(102%);
            transition: transform 0.24s ease;
            z-index: 1250;
        }

        .app-sidebar.is-open {
            transform: translateX(0);
        }

        .app-sidebar h3 {
            margin: 0 0 16px;
            color: var(--primary);
            font-size: 21px;
        }

        .app-sidebar .menu-group {
            margin-bottom: 14px;
        }

        .app-sidebar .menu-group span {
            display: block;
            margin-bottom: 7px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 900;
        }

        .app-sidebar .menu-group a {
            display: block;
            margin-bottom: 6px;
            padding: 9px 11px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            background: rgba(148, 163, 184, 0.09);
            font-weight: 800;
            transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        }

        .app-sidebar .menu-group a:hover {
            border-color: rgba(37, 99, 235, 0.35);
            background: rgba(37, 99, 235, 0.11);
            transform: translateX(-2px);
        }

        .app-sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.36);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1200;
        }

        .app-sidebar-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        body[data-theme="dark"] .app-sidebar {
            box-shadow: -18px 0 40px rgba(0, 0, 0, 0.45);
        }

        body[data-theme="dark"] .app-sidebar .menu-group a {
            background: rgba(15, 27, 42, 0.92);
        }

        body[data-theme="dark"] .app-sidebar .menu-group a:hover {
            border-color: rgba(96, 165, 250, 0.38);
            background: rgba(30, 58, 95, 0.58);
        }
    </style>
</head>

<body>
    <?php if ($flash): ?>
        <div style="max-width:1100px;margin:12px auto;padding:12px 16px;border-radius:12px;font-weight:700;background:<?= ($flash['type'] ?? '') === 'success' ? '#dcfce7' : '#fee2e2' ?>;color:<?= ($flash['type'] ?? '') === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= h($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>
    <button type="button" class="app-sidebar-toggle" id="appSidebarToggle" aria-controls="appSidebar" aria-expanded="false">➡️ القائمة</button>

    <aside class="app-sidebar" id="appSidebar" aria-label="القائمة الجانبية">
        <h3>القائمة</h3>
        <div class="menu-group">
            <a href="dashboard.php">📊 لوحة التحكم</a>
        </div>
        <div class="menu-group">
            <span>👤 المرضى</span>
            <a href="add-patient.php">➕ إضافة مريض</a>
            <a href="main.php">👥 كل المرضى</a>
            <a href="patient-data.php?id_open=<?= $id ?>">📁 بيانات المريض</a>
        </div>
        <div class="menu-group">
            <span>📅 المواعيد</span>
            <a href="visits.php">زيارات اليوم</a>
            <a href="followup-appointment.php?id=<?= $id ?>">موعد مراجعة</a>
            <a href="operation-by-date.php">مواعيد العمليات</a>
        </div>
        <div class="menu-group">
            <span>⚙️ النظام</span>
            <a href="treatment-types.php">🧬 إدارة الاجراءات</a>
            <a href="reports.php">التقارير</a>
            <a href="settings.php">الإعدادات</a>
            <a href="logout.php">تسجيل الخروج</a>
        </div>
    </aside>
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop"></div>

    <header>
        <h1> ملف المريض: <?php echo htmlspecialchars($row['full_name']); ?></h1>
        <button class="theme-toggle header-theme-toggle" id="themeToggle" type="button" title="تبديل المظهر">🌙</button>
    </header>

    <div class="page-container">

        <div class="patient_info"></span>

            <p><span>ID:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['id']); ?></span>
            </p>

            <p><span>الاسم:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['full_name']); ?></span>
            </p>

            <p><span>العمر:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['age']); ?></span>
            </p>

            <p><span>رقم الموبايل:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['phone_no']); ?></span>
            </p>
            <a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a>
            <a href="patient-data.php?id_open=<?php echo $row['id']; ?>"
                style="background: linear-gradient(135deg, #e85a27, #bb4c18); 
        color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none;">📁 بيانات المريض</a>
        </div>

        <div class="nav">
            <?php

            $critical_class = (($row['is_critical'] ?? 0) == 1) ? 'critical-blink' : '';
            ?>

            <a href="marked_as_done.php?id=<?= $id ?>"
                class="icon-btn done-icon"
                data-title="تمت الزيارة">✅</a>

            <a href="mark_critical.php?id=<?= $id ?>"
                class="icon-btn warning-icon <?= $critical_class ?>"
                data-title="تعليم كمريض حرج">
                🚨
            </a>

            <a href="#" class="icon-btn followup-btn"
                data-title="إضافة متابعة"
                onclick="openFollowup(event)">
                📌
            </a>

            <a href="visits.php"
                class="icon-btn visits-icon"
                data-title="زيارات اليوم">
                🏥 </a>

            <a href="retina-chart.php?patient_id=<?php echo $row['id']; ?>"
                class="icon-btn retina-icon"
                data-title="رسم الشبكية">
                ◎ </a>

            <a href="dashboard.php"
                class="icon-btn home-icon"
                data-title="الصفحة الرئيسية">
                🏠 </a>

            <a href="treatment.php?patient_id=<?php echo $row['id']; ?>"
                class="icon-btn recipe-btn"
                data-title="وصفة العلاج">
                💊
            </a>
        </div>


        <?php
        $visits_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM patient_visits WHERE patient_id = $id"));
        $va_count = mysqli_num_rows(mysqli_query($con, "SELECT va_id FROM va WHERE patient_id = $id"));
        $surgery_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM surgery WHERE patient_id = $id"));
        $laser_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM laser WHERE patient_id = $id"));
        $injection_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM injection WHERE patient_id = $id"));
        $retina_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM retina_drawings WHERE patient_id = $id"));
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📝</span>
                <span class="stat-value"><?= $visits_count ?></span>
                <span class="stat-label">الزيارات</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">👁️</span>
                <span class="stat-value"><?= $va_count ?></span>
                <span class="stat-label">فحص النظر</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🩺</span>
                <span class="stat-value"><?= $surgery_count ?></span>
                <span class="stat-label">العمليات</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🔦</span>
                <span class="stat-value"><?= $laser_count ?></span>
                <span class="stat-label">الليزر</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">💉</span>
                <span class="stat-value"><?= $injection_count ?></span>
                <span class="stat-label">الحقن</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">◎</span>
                <span class="stat-value"><?= $retina_count ?></span>
                <span class="stat-label">رسومات الشبكية</span>
            </div>
        </div>
        <div class="previous_data">
            <div class="previous_visits">
                <h3 class="section-title">التسلسل السريري</h3>
                <div class="chart-board">
                    <div class="chart-toolbar">
                        <strong>عرض الزيارات مجمعة حسب التاريخ</strong>
                        <div class="chart-filters" aria-label="فلترة التسلسل">
                            <button type="button" class="chart-filter active" data-filter="all">الكل</button>
                            <button type="button" class="chart-filter" data-filter="with-va">مع VA / IOP</button>
                            <button type="button" class="chart-filter" data-filter="no-va">تحتاج VA</button>
                        </div>
                    </div>
                    <div class="visit-timeline" id="clinicalTimeline">
                        <?php
                        if (!empty($id)) {
                            $id = (int)$id;
                            $visitRows = [];
                            $vaRowsByDate = [];
                            $retinaRowsByDate = [];
                            $dates = [];

                            $visitsResult = mysqli_query($con, "SELECT * FROM patient_visits WHERE patient_id = $id ORDER BY date DESC, id DESC");
                            while ($visit_row = mysqli_fetch_assoc($visitsResult)) {
                                $visitRows[$visit_row['date']][] = $visit_row;
                                $dates[$visit_row['date']] = true;
                            }

                            $vaResult = mysqli_query($con, "SELECT * FROM va WHERE patient_id = $id ORDER BY exam_date DESC, va_id DESC");
                            while ($va_row = mysqli_fetch_assoc($vaResult)) {
                                $vaRowsByDate[$va_row['exam_date']][] = $va_row;
                                $dates[$va_row['exam_date']] = true;
                            }

                            $retinaResult = mysqli_query($con, "SELECT id, eye, drawing_date, title, notes, drawing_image FROM retina_drawings WHERE patient_id = $id ORDER BY drawing_date DESC, id DESC");
                            while ($retina_row = mysqli_fetch_assoc($retinaResult)) {
                                $retinaRowsByDate[$retina_row['drawing_date']][] = $retina_row;
                                $dates[$retina_row['drawing_date']] = true;
                            }

                            $dateKeys = array_keys($dates);
                            rsort($dateKeys);
                            $withVaCount = 0;
                            $withoutVaCount = 0;
                            $withRetinaCount = 0;

                            if (empty($dateKeys)) {
                                echo "<p class='empty-state'>لا توجد زيارات أو فحوصات نظر أو رسومات شبكية مسجلة حتى الآن</p>";
                            } else {
                                foreach ($dateKeys as $timelineDate) {
                                    if (!empty($vaRowsByDate[$timelineDate])) {
                                        $withVaCount++;
                                    } else {
                                        $withoutVaCount++;
                                    }
                                    if (!empty($retinaRowsByDate[$timelineDate])) {
                                        $withRetinaCount++;
                                    }
                                }
                                echo "<p class='timeline-summary'>إجمالي الأيام المسجلة: " . count($dateKeys) . " | مع VA: " . $withVaCount . " | رسومات شبكية: " . $withRetinaCount . " | تحتاج VA: " . $withoutVaCount . "</p>";
                            }

                            $firstVisitDate = '';
                            if (!empty($dateKeys)) {
                                $firstVisitDate = (string) end($dateKeys);
                                reset($dateKeys);

                                $firstVisitRows = $visitRows[$firstVisitDate] ?? [];
                                $firstVisitNote = '';
                                if (!empty($firstVisitRows)) {
                                    foreach ($firstVisitRows as $firstVisitRow) {
                                        $rawFirstNote = trim((string) ($firstVisitRow['notes'] ?? ''));
                                        if ($rawFirstNote !== '') {
                                            $firstVisitNote = $rawFirstNote;
                                            break;
                                        }
                                    }
                                }

                                $firstVisitSummary = pf_extract_first_visit_summary($firstVisitNote);
                                $firstVisitVaRows = $vaRowsByDate[$firstVisitDate] ?? [];

                                echo "<div class='first-visit-summary'>";
                                echo "<strong>ملخص الزيارة الأولى</strong> <span class='summary-date'>(" . h($firstVisitDate) . ")</span>";
                                echo "<div class='first-visit-layout'>";

                                echo "<div class='summary-col summary-col-va'>";
                                echo "<div class='summary-col-title'>فحص النظر بنفس الزيارة</div>";
                                if (!empty($firstVisitVaRows)) {
                                    $firstVa = $firstVisitVaRows[0];
                                    $ucvaOd = trim((string) ($firstVa['va_od'] ?? ''));
                                    $ucvaOs = trim((string) ($firstVa['va_os'] ?? ''));
                                    $bcvaOd = trim((string) ($firstVa['bcva_od'] ?? ''));
                                    $bcvaOs = trim((string) ($firstVa['bcva_os'] ?? ''));

                                    echo "<div class='summary-va'>";
                                    echo "<div class='summary-va-grid'>";
                                    echo "<div>UCVA (OD): " . h($ucvaOd !== '' ? $ucvaOd : '-') . "</div>";
                                    echo "<div>UCVA (OS): " . h($ucvaOs !== '' ? $ucvaOs : '-') . "</div>";
                                    echo "<div>BCVA (OD): " . h($bcvaOd !== '' ? $bcvaOd : '-') . "</div>";
                                    echo "<div>BCVA (OS): " . h($bcvaOs !== '' ? $bcvaOs : '-') . "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                } else {
                                    echo "<div class='summary-empty'>لا يوجد VA مسجل بنفس تاريخ الزيارة الأولى.</div>";
                                }
                                echo "</div>";

                                echo "<div class='summary-col summary-col-notes'>";
                                echo "<div class='summary-col-title'>ملاحظات الزيارة الأولى</div>";
                                if ($firstVisitNote !== '') {
                                    if (($firstVisitSummary['diagnosis'] ?? '') !== '') {
                                        echo "<div class='summary-item'><div class='summary-item-label'>التشخيص الأولي</div><div class='summary-item-value clinic-user-content' data-user-content data-no-translate>" . h((string) $firstVisitSummary['diagnosis']) . "</div></div>";
                                    }
                                    if (($firstVisitSummary['plan'] ?? '') !== '') {
                                        echo "<div class='summary-item'><div class='summary-item-label'>الخطة / التوصيات</div><div class='summary-item-value clinic-user-content' data-user-content data-no-translate>" . h((string) $firstVisitSummary['plan']) . "</div></div>";
                                    }
                                    if (($firstVisitSummary['diagnosis'] ?? '') === '' && ($firstVisitSummary['plan'] ?? '') === '') {
                                        echo "<div class='summary-note clinic-user-content' data-user-content data-no-translate>" . pf_format_visit_note((string) ($firstVisitSummary['preview'] ?? '')) . "</div>";
                                    }
                                } else {
                                    echo "<div class='summary-empty'>لا توجد ملاحظات نصية محفوظة في الزيارة الأولى.</div>";
                                }
                                echo "</div>";

                                echo "</div>";
                                echo "</div>";
                            }

                            $cardIndex = 0;
                            foreach ($dateKeys as $visitDate) {
                                $visitsForDate = $visitRows[$visitDate] ?? [];
                                $vaForDate = $vaRowsByDate[$visitDate] ?? [];
                                $retinaForDate = $retinaRowsByDate[$visitDate] ?? [];
                                $hasVa = !empty($vaForDate);
                                $isFirstVisitDate = ($firstVisitDate !== '' && (string) $visitDate === $firstVisitDate);
                                $cardClass = $hasVa ? 'encounter-card with-va' : 'encounter-card no-va';
                                if ($isFirstVisitDate) {
                                    $cardClass .= ' first-visit';
                                }
                                $statusClass = $hasVa ? 'encounter-status' : 'encounter-status missing';
                                $statusText = $hasVa ? 'تم تسجيل VA / IOP' : 'تحتاج فحص VA';
                                $panelId = 'encounter-panel-' . $cardIndex;
                                $defaultCollapsed = ($cardIndex > 0);
                                $toggleText = $defaultCollapsed ? 'عرض التفاصيل' : 'إخفاء التفاصيل';
                                $mainClass = $defaultCollapsed ? 'encounter-main is-collapsed' : 'encounter-main';

                                echo "<article class='$cardClass' data-has-va='" . ($hasVa ? "1" : "0") . "'>";
                                echo "<div class='encounter-head'>";
                                echo "<div class='encounter-head-main'>";
                                echo "<div class='encounter-date'><span>تاريخ الزيارة</span><span>" . h($visitDate) . "</span></div>";
                                echo "<span class='$statusClass'>$statusText</span>";
                                if ($isFirstVisitDate) {
                                    echo "<span class='first-visit-badge'>الزيارة الأولى</span>";
                                }
                                echo "</div>";
                                echo "<button type='button' class='encounter-toggle' data-target='$panelId' aria-expanded='" . ($defaultCollapsed ? "false" : "true") . "'>$toggleText</button>";
                                echo "</div>";
                                echo "<div id='$panelId' class='$mainClass'>";
                                echo "<div class='encounter-body'>";
                                echo "<section class='clinical-note'><h4>ملاحظات الزيارة</h4>";

                                if (!empty($visitsForDate)) {
                                    foreach ($visitsForDate as $visit_row) {
                                        $rawNote = trim((string)($visit_row['notes'] ?? ''));
                                        if ($rawNote === '') {
                                            echo "<div class='visit-note-item'>لا توجد ملاحظات</div>";
                                        } else {
                                            echo "<div class='visit-note-item clinic-user-content' data-user-content data-no-translate>" . pf_format_visit_note($rawNote) . "</div>";
                                        }
                                    }
                                } else {
                                    echo "<div class='visit-note-item'>لا توجد ملاحظة زيارة في هذا التاريخ</div>";
                                }
                                echo "</section>";
                                echo "<section>";

                                if (!empty($vaForDate)) {
                                    foreach ($vaForDate as $va_row) {
                                        $iopOdValue = trim((string)($va_row['iop_od'] ?? ''));
                                        $iopOsValue = trim((string)($va_row['iop_os'] ?? ''));
                                        $iopOdNumber = is_numeric($iopOdValue) ? (float)$iopOdValue : null;
                                        $iopOsNumber = is_numeric($iopOsValue) ? (float)$iopOsValue : null;
                                        $iopOdClass = ($iopOdNumber !== null && ($iopOdNumber > 21 || $iopOdNumber < 8)) ? 'iop-alert' : 'iop-normal';
                                        $iopOsClass = ($iopOsNumber !== null && ($iopOsNumber > 21 || $iopOsNumber < 8)) ? 'iop-alert' : 'iop-normal';

                                        echo "<div class='exam-grid'>";
                                        echo "<section class='exam-panel od'>";
                                        echo "<h4><span class='eye-badge eye-od'>OD</span></h4>";
                                        echo "<div class='exam-metrics'>";
                                        echo "<div class='exam-metric'><span>UCVA</span><span>" . h($va_row['va_od'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>BCVA</span><span>" . h($va_row['bcva_od'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric $iopOdClass'><span>IOP</span><span>" . h($iopOdValue ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>Old Glasses</span><span>" . h($va_row['old_glasses_od'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>Refraction</span><span>" . h($va_row['ref_od'] ?: '-') . "</span></div>";
                                        echo "</div>";
                                        echo "</section>";
                                        echo "<section class='exam-panel os'>";
                                        echo "<h4><span class='eye-badge eye-os'>OS</span></h4>";
                                        echo "<div class='exam-metrics'>";
                                        echo "<div class='exam-metric'><span>UCVA</span><span>" . h($va_row['va_os'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>BCVA</span><span>" . h($va_row['bcva_os'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric $iopOsClass'><span>IOP</span><span>" . h($iopOsValue ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>Old Glasses</span><span>" . h($va_row['old_glasses_os'] ?: '-') . "</span></div>";
                                        echo "<div class='exam-metric'><span>Refraction</span><span>" . h($va_row['ref_os'] ?: '-') . "</span></div>";
                                        echo "</div>";
                                        echo "</section>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p class='empty-state'>لا يوجد فحص VA / IOP مرتبط بهذا التاريخ</p>";
                                }
                                echo "</section>";

                                echo "<section class='clinical-note'>";
                                echo "<h4>رسم الشبكية</h4>";
                                if (!empty($retinaForDate)) {
                                    echo "<div class='retina-preview-grid'>";
                                    foreach ($retinaForDate as $retina_row) {
                                        $retinaTitle = trim((string)($retina_row['title'] ?? ''));
                                        $label = ($retinaTitle !== '' ? $retinaTitle : 'Retina chart') . " - " . h($retina_row['eye']);
                                        echo "<a class='retina-preview' href='retina-chart.php?patient_id=" . h($id) . "&drawing_id=" . h($retina_row['id']) . "'>";
                                        if (!empty($retina_row['drawing_image'])) {
                                            echo "<img src='" . h($retina_row['drawing_image']) . "' alt='Retina drawing'>";
                                        }
                                        echo "<span>" . h($label) . "</span>";
                                        if (!empty($retina_row['notes'])) {
                                            echo "<p class='retina-user-note clinic-user-content' data-no-translate>" . nl2br(h($retina_row['notes'])) . "</p>";
                                        }
                                        echo "</a>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<p class='empty-state'>لا يوجد رسم شبكية مرتبط بهذا التاريخ</p>";
                                }
                                echo "</section>";

                                echo "</div>";
                                echo "<div class='encounter-actions'>";
                                echo "<a class='text-action secondary' href='add-va.php?id=" . h($id) . "'>إضافة VA / IOP</a>";
                                echo "<a class='icon-btn retina-action' href='retina-chart.php?patient_id=" . h($id) . "&date=" . h($visitDate) . "' data-title='رسم الشبكية'>◎</a>";
                                if (!empty($vaForDate)) {
                                    echo "<a class='text-action' href='edit-va.php?id_edit=" . h($vaForDate[0]['va_id']) . "'>تعديل VA</a>";
                                }
                                foreach ($visitsForDate as $visit_row) {
                                    echo "<button type='button' class='icon-btn edit-icon edit-btn' data-note='" . h($visit_row['notes']) . "' data-id='" . h($visit_row['id']) . "' data-title='تعديل الزيارة'>✏️</button>";
                                    echo "<a class='icon-btn delete-icon' href='delete-visit.php?id_delete=" . h($visit_row['id']) . "' onclick=\"return confirm('هل تريد حذف الزيارة؟');\" data-title='حذف الزيارة'>🗑️</a>";
                                }
                                if (empty($visitsForDate)) {
                                    echo "<button type='button' class='icon-btn add-note-btn' data-date='" . h($visitDate) . "' data-title='إضافة ملاحظة زيارة'>📝</button>";
                                }

                                echo "</div>";
                                echo "</div>";

                                echo "</article>";
                                $cardIndex++;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>



            <div class="previous_lasers">
                <h3 class="section-title">🔦 جلسات الليزر</h3>

                <div class="procedure-list">
                    <?php
                    if (!empty($id)) {
                        $patientId = (int)$id;
                        $result = mysqli_query($con, "SELECT * FROM laser WHERE patient_id = $patientId ORDER BY date DESC, id DESC");
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($laser_row = mysqli_fetch_assoc($result)) {
                                $eye = strtoupper(trim($laser_row['eye']));
                                $eye_class = '';
                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<article class='procedure-card'>";
                                echo "<div class='procedure-head'>";
                                echo "<div class='procedure-title'><span>" . h($laser_row['date'] ?: '-') . "</span><strong>" . h($laser_row['laser_type'] ?: 'ليزر') . "</strong></div>";
                                echo "<div class='procedure-actions'><a class='icon-btn edit-icon' href='edit-laser.php?id_edit=" . h($laser_row['id']) . "' title='تعديل الليزر'>✏️</a></div>";
                                echo "</div>";
                                echo "<div class='procedure-meta'>";
                                echo "<div><span>العين</span><strong><span class='eye-badge $eye_class'>" . h($eye ?: '-') . "</span></strong></div>";
                                echo "<div><span>نوع الجلسة</span><strong>" . h($laser_row['laser_type'] ?: '-') . "</strong></div>";
                                echo "</div>";
                                if (!empty(trim((string)$laser_row['notes']))) {
                                    echo "<p class='procedure-note clinic-user-content' data-user-content data-no-translate>" . nl2br(h($laser_row['notes'])) . "</p>";
                                } else {
                                    echo "<p class='procedure-note'>لا توجد ملاحظات.</p>";
                                }
                                echo "</article>";
                            }
                        } else {
                            echo "<p class='empty-state'>لا توجد جلسات ليزر مسجلة</p>";
                        }
                    }
                    ?>
                </div>
            </div>




            <div class="previous_surgeries">
                <h3 class="section-title">🩺 سجل العمليات</h3>

                <div class="procedure-list">
                    <?php
                    if (!empty($id)) {
                        $patientId = (int)$id;
                        $result = mysqli_query($con, "SELECT * FROM surgery WHERE patient_id = $patientId ORDER BY date DESC, id DESC");
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($surgery_row = mysqli_fetch_assoc($result)) {
                                $eye = strtoupper(trim($surgery_row['eye']));
                                $eye_class = '';
                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<article class='procedure-card'>";
                                echo "<div class='procedure-head'>";
                                echo "<div class='procedure-title'><span>" . h($surgery_row['date'] ?: '-') . "</span><strong>" . h($surgery_row['surgery_type'] ?: 'عملية') . "</strong></div>";
                                echo "<div class='procedure-actions'>";
                                echo "<a class='icon-btn edit-icon' href='edit-surgery.php?id_edit=" . h($surgery_row['id']) . "' title='تعديل العملية'>✏️</a>";
                                echo "<a class='icon-btn delete-icon' href='delete-surgery.php?id_delete=" . h($surgery_row['id']) . "' onclick=\"return confirm('هل تريد حذف هذه العملية؟');\" title='حذف العملية'>🗑️</a>";
                                echo "</div>";
                                echo "</div>";
                                $iolType = trim((string) ($surgery_row['iol_type'] ?? ''));
                                $iolPowerText = clinic_format_iol_power($surgery_row['iol_power'] ?? null);
                                $iolDisplay = $iolType === '' ? '-' : $iolType . ($iolPowerText !== '-' ? ' (' . $iolPowerText . ')' : '');
                                echo "<div class='procedure-meta'>";
                                echo "<div><span>العين</span><strong><span class='eye-badge $eye_class'>" . h($eye ?: '-') . "</span></strong></div>";
                                echo "<div><span>العدسة / القوة</span><strong>" . h($iolDisplay) . "</strong></div>";
                                echo "</div>";
                                if (!empty(trim((string)$surgery_row['notes']))) {
                                    echo "<p class='procedure-note clinic-user-content' data-user-content data-no-translate>" . nl2br(h($surgery_row['notes'])) . "</p>";
                                } else {
                                    echo "<p class='procedure-note'>لا توجد ملاحظات.</p>";
                                }
                                echo "</article>";
                            }
                        } else {
                            echo "<p class='empty-state'>لا توجد عمليات مسجلة</p>";
                        }
                    }
                    ?>
                </div>
            </div>









            <div class="previous_injections">
                <h3 class="section-title">💉 سجل الحقن</h3>

                <div class="procedure-list">
                    <?php
                    if (!empty($id)) {
                        $patientId = (int)$id;
                        $result = mysqli_query($con, "SELECT * FROM injection WHERE patient_id = $patientId ORDER BY date DESC, id DESC");
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($injection_row = mysqli_fetch_assoc($result)) {
                                $eye = strtoupper(trim($injection_row['eye']));
                                $eye_class = '';
                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<article class='procedure-card'>";
                                echo "<div class='procedure-head'>";
                                echo "<div class='procedure-title'><span>" . h($injection_row['date'] ?: '-') . "</span><strong>" . h($injection_row['injection_type'] ?: 'حقن') . "</strong></div>";
                                echo "<div class='procedure-actions'><a class='icon-btn edit-icon' href='edit-injection.php?id_edit=" . h($injection_row['id']) . "' title='تعديل الحقن'>✏️</a></div>";
                                echo "</div>";
                                echo "<div class='procedure-meta'>";
                                echo "<div><span>العين</span><strong><span class='eye-badge $eye_class'>" . h($eye ?: '-') . "</span></strong></div>";
                                echo "<div><span>نوع الحقنة</span><strong>" . h($injection_row['injection_type'] ?: '-') . "</strong></div>";
                                echo "</div>";
                                if (!empty(trim((string)$injection_row['notes']))) {
                                    echo "<p class='procedure-note clinic-user-content' data-user-content data-no-translate>" . nl2br(h($injection_row['notes'])) . "</p>";
                                } else {
                                    echo "<p class='procedure-note'>لا توجد ملاحظات.</p>";
                                }
                                echo "</article>";
                            }
                        } else {
                            echo "<p class='empty-state'>لا توجد جلسات حقن مسجلة.</p>";
                        }
                    }
                    ?>
                </div>
            </div>



            <div class="patient_visits">
                <h3 class="section-title">📝 إضافة أو تعديل زيارة</h3>
                <form action="patient-visits.php?id=<?php echo $id ?>" method="POST">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="visit_date" name="visit_date">
                    <textarea spellcheck="false" id="notes" name="notes" rows="4" cols="43" placeholder="اكتب ملاحظات الزيارة هنا..."></textarea>

                    <button type="submit" id="add_visit" name="add_visit"> 📝 إضافة زيارة</button>
                </form>


            </div>


            <div class="previous_medicines">
                <h3 class="section-title">💊 الأدوية المصروفة</h3>




                <?php

                $patient_id = (int)$id;
                $previous_medicines = mysqli_query($con, "
SELECT 

    p.id as prescription_id,
    p.prescription_date,
    p.diagnosis,
    m.medicine_name,
    m.medicine_form,
    pi.dose,
    pi.frequency,
    pi.duration,
    pi.eye
FROM prescriptions p
JOIN prescription_items pi ON p.id = pi.prescription_id
JOIN medicines m ON pi.medicine_id = m.id
WHERE p.patient_id = $patient_id
ORDER BY p.prescription_date DESC
LIMIT 20
");

                if (mysqli_num_rows($previous_medicines) > 0) {

                    $current_prescription_id = null;
                    $last_prescription_id = null;

                    while ($prescription_row = mysqli_fetch_assoc($previous_medicines)) {

                        // عند بدء وصفة جديدة
                        if ($current_prescription_id != $prescription_row['prescription_id']) {

                            // إغلاق البطاقة السابقة
                            if ($current_prescription_id !== null) {
                                echo "</ul>";
                                echo "<div class='prescription-footer'>";
                                echo "<a class='view-prescription-btn' href='view_prescription.php?id={$last_prescription_id}'>📄 عرض الوصفة</a>";
                                echo "<a class='edit-prescription-btn' href='edit-prescription.php?id={$last_prescription_id}' style='margin-left: 10px; background: linear-gradient(135deg, #059669, #047857);'>✏️ تعديل الوصفة</a>";
                                echo "</div>";
                                echo "</div>";
                            }

                            $current_prescription_id = $prescription_row['prescription_id'];
                            $last_prescription_id = $prescription_row['prescription_id'];

                            // فتح بطاقة جديدة
                            echo "<div class='prescription-card'>";

                            echo "<div class='prescription-header'>";
                            echo "<span class='prescription-date'>📅 " . ($prescription_row['prescription_date'] ?? '-') . "</span>";
                            echo "</div>";

                            if (!empty($prescription_row['diagnosis'])) {
                                echo "<div class='prescription-diagnosis clinic-user-content' data-no-translate>🩺 " . htmlspecialchars($prescription_row['diagnosis']) . "</div>";
                            }

                            echo "<ul class='prescription-list clinic-user-content' data-no-translate>";
                        }

                        // تحديد لون شارة العين
                        $eye = strtoupper(trim($prescription_row['eye']));
                        $eye_class = '';

                        if ($eye == 'RIGHT') $eye_class = 'eye-od';
                        elseif ($eye == 'LEFT') $eye_class = 'eye-os';
                        elseif ($eye == 'BOTH') $eye_class = 'eye-ou';

                        // اسم الدواء
                        $medicine_name = trim($prescription_row['medicine_name'] . ' ' . $prescription_row['medicine_form']);

                        // عرض الدواء
                        echo "<li>";
                        echo "<strong>" . htmlspecialchars($medicine_name) . "</strong>";

                        if (!empty($prescription_row['frequency'])) {
                            echo " — " . htmlspecialchars($prescription_row['frequency']);
                        }

                        if (!empty($prescription_row['dose'])) {
                            echo " — " . htmlspecialchars($prescription_row['dose']);
                        }

                        if (!empty($eye)) {
                            echo " — <span class='eye-badge {$eye_class}'>" . htmlspecialchars($eye) . "</span>";
                        }

                        if (!empty($prescription_row['duration'])) {
                            echo " — " . htmlspecialchars($prescription_row['duration']);
                        }

                        echo "</li>";
                    }

                    // إغلاق آخر بطاقة
                    echo "</ul>";
                    echo "<div class='prescription-footer'>";
                    echo "<a class='view-prescription-btn' href='view_prescription.php?id={$last_prescription_id}'>📄 عرض الوصفة</a>";
                    echo "<a class='edit-prescription-btn' href='edit-prescription.php?id={$last_prescription_id}' style='margin-left: 10px; background: linear-gradient(135deg, #059669, #047857);'>✏️ تعديل الوصفة</a>";
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "<p style='text-align:center;color:#64748b;'>لا توجد وصفات سابقة</p>";
                }
                ?>
            </div>





        </div>

        <script>
            function collapseAllEncounterPanels(exceptTargetId = null) {
                document.querySelectorAll('.encounter-toggle').forEach(btn => {
                    const panelId = btn.dataset.target;
                    const panel = document.getElementById(panelId);
                    if (!panel || panelId === exceptTargetId) return;

                    panel.classList.add('is-collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.textContent = 'عرض التفاصيل';
                });
            }

            document.querySelectorAll('.encounter-toggle').forEach(toggleButton => {
                toggleButton.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const panel = document.getElementById(targetId);
                    if (!panel) return;

                    const isCurrentlyCollapsed = panel.classList.contains('is-collapsed');
                    if (isCurrentlyCollapsed) {
                        collapseAllEncounterPanels(targetId);
                    }

                    const isCollapsed = panel.classList.toggle('is-collapsed');
                    this.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                    this.textContent = isCollapsed ? 'عرض التفاصيل' : 'إخفاء التفاصيل';
                });
            });

            document.querySelectorAll('.chart-filter').forEach(filterButton => {
                filterButton.addEventListener('click', function() {
                    const filter = this.dataset.filter;

                    document.querySelectorAll('.chart-filter').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    document.querySelectorAll('.encounter-card').forEach(card => {
                        const hasVa = card.dataset.hasVa === '1';
                        const show =
                            filter === 'all' ||
                            (filter === 'with-va' && hasVa) ||
                            (filter === 'no-va' && !hasVa);

                        card.style.display = show ? '' : 'none';
                    });
                });
            });
        </script>

        <script>
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notes = this.dataset.note;
                    const visitId = this.dataset.id;

                    const notesField = document.getElementById('notes');
                    const visitIdField = document.getElementById('id');
                    const visitDateField = document.getElementById('visit_date');
                    const submitBtn = document.getElementById('add_visit');

                    if (!notesField || !visitIdField || !submitBtn) return;

                    notesField.value = notes;
                    visitIdField.value = visitId;
                    if (visitDateField) visitDateField.value = '';
                    submitBtn.innerText = 'تحديث الزيارة';

                    notesField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    setTimeout(() => {
                        notesField.focus();
                        const textLength = notesField.value.length;
                        notesField.setSelectionRange(textLength, textLength);
                    }, 220);

                });
            });

            document.querySelectorAll('.add-note-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const visitDate = this.dataset.date;

                    const notesField = document.getElementById('notes');
                    const visitIdField = document.getElementById('id');
                    const visitDateField = document.getElementById('visit_date');
                    const submitBtn = document.getElementById('add_visit');

                    if (!notesField || !visitIdField || !visitDateField || !submitBtn) return;

                    notesField.value = '';
                    visitIdField.value = '';
                    visitDateField.value = visitDate;
                    submitBtn.innerText = 'إضافة زيارة بتاريخ ' + visitDate;

                    notesField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    setTimeout(() => {
                        notesField.focus();
                    }, 220);
                });
            });
        </script>

        <script>
            window.onload = function() {
                const notes = document.getElementById('notes');
                const visitId = document.getElementById('id');
                const visitDate = document.getElementById('visit_date');

                if (notes) notes.value = '';
                if (visitId) visitId.value = '';
                if (visitDate) visitDate.value = '';

                const btn = document.getElementById('add_visit');
                if (btn) btn.innerText = ' 📝 إضافة زيارة';
                // إخفاء نافذة المتابعة عند تحميل الصفحة
                const modal = document.getElementById('followupModal');
                if (modal) modal.style.display = 'none';
            };
        </script>

        <div id="followupModal" class="modal">

            <div class="modal-content">

                <span class="close-btn" onclick="closeFollowup()">×</span>

                <h3>📅 تحديد موعد المراجعة</h3>

                <form method="POST" action="save_followup.php?id=<?php echo $row['id']; ?>">

                    <input type="hidden" name="patient_id" value="<?php echo $row['id']; ?>">

                    <label>تاريخ المراجعة</label>
                    <input type="date" name="followup_date" required>

                    <label>سبب المراجعة</label>
                    <input type="text" name="followup_reason" placeholder="مثال: مراجعة ضغط العين">

                    <button type="submit">💾 حفظ المتابعة</button>

                </form>

            </div>

        </div>

        <script>
            function openFollowup(e) {
                e.preventDefault();
                document.getElementById("followupModal").style.display = "block";
            }

            function closeFollowup() {
                document.getElementById("followupModal").style.display = "none";
            }

            /* اغلاق عند الضغط خارج الصندوق */

            window.onclick = function(event) {

                let modal = document.getElementById("followupModal");

                if (event.target == modal) {
                    modal.style.display = "none";
                }

            }
        </script>


        <div class="links">
            <a href="surgery-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد عملية</a>
            <a href="laser-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد ليزر</a>
            <a href="injection-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد حقن</a>
            <a href="add-va.php?id=<?php echo htmlspecialchars($row['id']); ?>">اضافة فحص النظر</a>
            <a href="show-image.php?id=<?php echo htmlspecialchars($row['id']); ?>"> عرض الصور</a>
            <a href="patient_reports.php?id=<?php echo htmlspecialchars($row['id']); ?>">التقارير الطبية</a>
        </div>

        <script>
        </script>
        <script src="assets/patient-file-sidebar.js" defer></script>

    </div>
</body>

</html>