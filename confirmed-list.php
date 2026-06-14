<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

$date = $_GET['date'] ?? date('Y-m-d');

$operationConfig = [
    'surgery' => [
        'title' => 'العمليات',
        'singular' => 'عملية',
        'table' => 'surgery_appointment',
        'type_column' => 'surgery_type',
        'tone' => 'surgery',
    ],
    'laser' => [
        'title' => 'الليزر',
        'singular' => 'ليزر',
        'table' => 'laser_appointment',
        'type_column' => 'laser_type',
        'tone' => 'laser',
    ],
    'injection' => [
        'title' => 'الحقن',
        'singular' => 'حقن',
        'table' => 'injection_appointment',
        'type_column' => 'injection_type',
        'tone' => 'injection',
    ],
];

function fetch_confirmed_operations(mysqli $con, string $date, array $config): array
{
    $table = $config['table'];
    $typeColumn = $config['type_column'];

    $stmt = $con->prepare("
        SELECT
            a.id,
            a.patient_id,
            a.serial_no,
            p.full_name,
            a.eye,
            a.$typeColumn AS operation_type,
            a.notes,
            a.phone,
            a.phone_alt,
            a.date
        FROM $table a
        JOIN add_patient p ON a.patient_id = p.id
        WHERE DATE(a.date) = ? AND a.attendance_status = 1
        ORDER BY a.$typeColumn ASC, a.serial_no ASC, a.id ASC
    ");
    $stmt->bind_param('s', $date);
    $stmt->execute();

    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function group_operations_by_type(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $type = trim((string)($row['operation_type'] ?? ''));
        if ($type === '') {
            $type = 'غير محدد';
        }
        $groups[$type][] = $row;
    }

    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
    return $groups;
}

function render_confirmed_section(string $kind, array $config, array $rows, string $date): void
{
    $groups = group_operations_by_type($rows);
    $total = count($rows);

    echo "<section class='confirmed-section " . h($config['tone']) . "'>";
    echo "<div class='section-head'>";
    echo "<div><h2>" . h($config['title']) . "</h2></div>";
    echo "<strong>" . h($total) . "</strong>";
    echo "</div>";

    if ($total === 0) {
        echo "<div class='empty-state'>لا توجد مواعيد مؤكدة لهذا القسم في التاريخ المحدد</div>";
        echo "</section>";
        return;
    }

    echo "<div class='type-groups'>";
    foreach ($groups as $typeName => $groupRows) {
        usort($groupRows, static function (array $a, array $b): int {
            $aSerial = isset($a['serial_no']) ? (int)$a['serial_no'] : 0;
            $bSerial = isset($b['serial_no']) ? (int)$b['serial_no'] : 0;
            if ($aSerial === $bSerial) {
                return ((int)$a['id']) <=> ((int)$b['id']);
            }
            return $aSerial <=> $bSerial;
        });

        echo "<article class='type-group'>";
        echo "<div class='type-head'><div><h3>" . h($typeName) . "</h3></div><strong>" . count($groupRows) . "</strong></div>";
        echo "<div class='table-wrap'>";
        echo "<table>";
        echo "<thead><tr>";
        echo "<th class='col-serial'>#</th><th class='col-name'>اسم المريض</th><th class='col-eye'>العين</th><th class='col-notes'>الملاحظات</th><th class='col-phone'>الهاتف</th><th class='col-phone-alt'>هاتف بديل</th><th class='col-postop-note'>ملاحظات بعد الإجراء</th><th class='col-action'>إجراء</th>";
        echo "</tr></thead><tbody>";

        $counter = 1;
        foreach ($groupRows as $row) {
            $eye = strtoupper(trim((string)$row['eye']));
            $eyeClass = '';
            if ($eye === 'OD') {
                $eyeClass = ' eye-od';
            } elseif ($eye === 'OS') {
                $eyeClass = ' eye-os';
            } elseif ($eye === 'OU') {
                $eyeClass = ' eye-ou';
            }

            $cancelUrl = 'cancel-attendance.php?id=' . urlencode((string)$row['id'])
                . '&kind=' . urlencode($kind)
                . '&date=' . urlencode($date);

            echo "<tr>";
            echo "<td class='col-serial'><span class='serial'>" . h((string)$counter) . "</span></td>";
            echo "<td class='patient-name'>" . h($row['full_name']) . "</td>";
            echo "<td class='col-eye'><span class='eye-badge" . h($eyeClass) . "'>" . h($eye ?: '-') . "</span></td>";
            echo "<td class='notes'>" . nl2br(h($row['notes'] ?: '-')) . "</td>";
            echo "<td class='col-phone' dir='ltr'>" . h($row['phone'] ?: '-') . "</td>";
            echo "<td class='col-phone-alt' dir='ltr'>" . h($row['phone_alt'] ?: '-') . "</td>";
            echo "<td class='postop-note-cell'><div class='postop-note-line'>&nbsp;</div></td>";
            echo "<td class='col-action'><a class='cancel-btn' href='" . h($cancelUrl) . "' onclick=\"return confirm('هل تريد إلغاء تأكيد هذا المريض؟');\">إلغاء التأكيد</a></td>";
            echo "</tr>";
            $counter++;
        }

        echo "</tbody></table>";
        echo "</div>";
        echo "</article>";
    }
    echo "</div>";
    echo "</section>";
}

$confirmedRows = [];
$totalConfirmed = 0;
foreach ($operationConfig as $kind => $config) {
    $confirmedRows[$kind] = fetch_confirmed_operations($con, $date, $config);
    $totalConfirmed += count($confirmedRows[$kind]);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>القوائم المؤكدة</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap');

        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --text: #172033;
            --muted: #64748b;
            --border: #dbe7ef;
            --blue: #2563eb;
            --teal: #0f766e;
            --amber: #d97706;
            --red: #dc2626;
            --green: #047857;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 14px;
        }

        .page {
            max-width: 1680px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 16px;
        }

        .title-block span,
        .section-head span,
        .type-head span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .title-block h1 {
            margin: 3px 0 0;
            font-size: 30px;
            line-height: 1.2;
            font-weight: 900;
        }

        .title-block p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .top-actions,
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .nav-btn,
        .tool-btn,
        .cancel-btn {
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 13px;
            font: inherit;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            background: var(--panel);
            color: var(--text);
            white-space: nowrap;
        }

        .tool-btn.primary {
            background: var(--teal);
            border-color: var(--teal);
            color: #ffffff;
        }

        .nav-btn.success {
            background: var(--green);
            border-color: var(--green);
            color: #ffffff;
        }

        .control-panel,
        .summary-card,
        .confirmed-section,
        .type-group {
            border: 1px solid var(--border);
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .control-panel {
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 16px;
        }

        input[type="date"] {
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--panel-soft);
            color: var(--text);
            padding: 6px 11px;
            font: inherit;
            font-weight: 800;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .summary-card {
            border-radius: 14px;
            padding: 13px 15px;
        }

        .summary-card span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .summary-card strong {
            display: block;
            margin-top: 3px;
            font-size: 28px;
            line-height: 1;
            font-weight: 900;
        }

        .sections-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            align-items: start;
        }

        .confirmed-section {
            min-width: 0;
            border-radius: 16px;
            padding: 14px;
        }

        .section-head,
        .type-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .section-head {
            padding: 12px 14px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(135deg, #ffffff, #f8fbff);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        }

        .section-head h2,
        .type-head h3 {
            margin: 2px 0 0;
            color: var(--text);
            font-size: 19px;
            font-weight: 900;
        }

        .section-head h2 {
            font-size: 23px;
            letter-spacing: 0.2px;
        }

        .section-head strong,
        .type-head strong {
            min-width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
        }

        .surgery .section-head strong,
        .surgery .type-head strong {
            background: var(--blue);
        }

        .surgery .section-head {
            border-color: rgba(37, 99, 235, 0.24);
            background: linear-gradient(135deg, #dbeafe, #eff6ff 58%, #ffffff);
        }

        .surgery .section-head h2,
        .surgery .section-head span {
            color: #1d4ed8;
        }

        .laser .section-head strong,
        .laser .type-head strong {
            background: var(--amber);
        }

        .laser .section-head {
            border-color: rgba(245, 158, 11, 0.28);
            background: linear-gradient(135deg, #fef3c7, #fff7ed 58%, #ffffff);
        }

        .laser .section-head h2,
        .laser .section-head span {
            color: #b45309;
        }

        .injection .section-head strong,
        .injection .type-head strong {
            background: var(--teal);
        }

        .injection .section-head {
            border-color: rgba(5, 150, 105, 0.24);
            background: linear-gradient(135deg, #d1fae5, #ecfdf5 58%, #ffffff);
        }

        .injection .section-head h2,
        .injection .section-head span {
            color: #047857;
        }

        .type-groups {
            display: grid;
            gap: 12px;
        }

        .type-group {
            border-radius: 14px;
            box-shadow: none;
            overflow: hidden;
        }

        .type-head {
            background: linear-gradient(135deg, #eff6ff, #ffffff 58%, #ecfeff);
            border-bottom: 1px solid rgba(37, 99, 235, 0.14);
            padding: 12px 14px;
            box-shadow: inset 0 -1px 0 rgba(37, 99, 235, 0.06);
        }

        .type-head h3 {
            color: #0f172a;
            font-size: 20px;
            letter-spacing: 0.2px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 11px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
            font-size: 13px;
        }

        .col-serial {
            width: 54px;
            min-width: 54px;
            max-width: 54px;
            text-align: center;
        }

        .col-eye {
            width: 78px;
            text-align: center;
        }

        .col-phone,
        .col-phone-alt {
            width: 118px;
            text-align: left;
        }

        .col-postop-note {
            width: 240px;
            min-width: 240px;
            display: none;
        }

        .postop-note-cell {
            min-width: 240px;
            background: #ffffff;
            display: none;
        }

        .postop-note-line {
            min-height: 38px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
        }

        th {
            color: var(--muted);
            background: var(--panel);
            font-size: 12px;
            font-weight: 900;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .patient-name {
            color: var(--text);
            font-weight: 900;
            min-width: 150px;
        }

        .notes {
            min-width: 180px;
            line-height: 1.65;
            color: var(--muted);
        }

        .serial,
        .eye-badge {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            padding: 4px 7px;
            font-size: 12px;
            font-weight: 900;
        }

        .serial {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .eye-badge {
            background: linear-gradient(135deg, #64748b, #475569);
            color: #ffffff;
        }

        .eye-od {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
        }

        .eye-os {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
        }

        .eye-ou {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #ffffff;
        }

        .cancel-btn {
            min-height: 34px;
            border-color: var(--red);
            background: var(--red);
            color: #ffffff;
            padding: 6px 10px;
        }

        .empty-state {
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: var(--panel-soft);
            color: var(--muted);
            padding: 22px;
            text-align: center;
            font-size: 14px;
            font-weight: 900;
        }

        body[data-theme="dark"] {
            --bg: #0f1412;
            --panel: #17211d;
            --panel-soft: #111a17;
            --text: #edf4ef;
            --muted: #a8b8af;
            --border: rgba(167, 190, 177, 0.2);
            --blue: #79a8ff;
            --green: #34d399;
            --red: #ef6666;
            --shadow: 0 18px 45px rgba(0, 0, 0, 0.38);
        }

        body[data-theme="dark"] .control-panel,
        body[data-theme="dark"] .summary-card,
        body[data-theme="dark"] .confirmed-section,
        body[data-theme="dark"] .type-group {
            background: rgba(23, 33, 29, 0.94);
            border-color: rgba(167, 190, 177, 0.2);
            box-shadow: var(--shadow);
        }

        body[data-theme="dark"] input[type="date"],
        body[data-theme="dark"] .type-head,
        body[data-theme="dark"] .empty-state {
            background: rgba(17, 26, 23, 0.96);
            border-color: rgba(167, 190, 177, 0.18);
            color: var(--text);
        }

        body[data-theme="dark"] .type-head {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.96) 55%, rgba(8, 47, 73, 0.88));
            border-bottom-color: rgba(96, 165, 250, 0.22);
            box-shadow: inset 0 -1px 0 rgba(96, 165, 250, 0.12);
        }

        body[data-theme="dark"] .section-head {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.96));
            border-color: rgba(148, 163, 184, 0.22);
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.24);
        }

        body[data-theme="dark"] .surgery .section-head {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.38), rgba(15, 23, 42, 0.96) 60%, rgba(14, 165, 233, 0.18));
            border-color: rgba(96, 165, 250, 0.42);
        }

        body[data-theme="dark"] .laser .section-head {
            background: linear-gradient(135deg, rgba(180, 83, 9, 0.38), rgba(15, 23, 42, 0.96) 60%, rgba(251, 191, 36, 0.16));
            border-color: rgba(251, 191, 36, 0.38);
        }

        body[data-theme="dark"] .injection .section-head {
            background: linear-gradient(135deg, rgba(4, 120, 87, 0.42), rgba(15, 23, 42, 0.96) 60%, rgba(52, 211, 153, 0.16));
            border-color: rgba(52, 211, 153, 0.38);
        }

        body[data-theme="dark"] .section-head h2 {
            color: #f8fafc;
        }

        body[data-theme="dark"] .surgery .section-head span {
            color: #bfdbfe;
        }

        body[data-theme="dark"] .laser .section-head span {
            color: #fde68a;
        }

        body[data-theme="dark"] .injection .section-head span {
            color: #a7f3d0;
        }

        body[data-theme="dark"] .type-head h3 {
            color: #f8fafc;
        }

        body[data-theme="dark"] th {
            background: rgba(10, 17, 15, 0.86);
            color: var(--muted);
        }

        body[data-theme="dark"] td {
            border-bottom-color: rgba(167, 190, 177, 0.16);
        }

        body[data-theme="dark"] tbody tr:hover {
            background: rgba(95, 209, 183, 0.08);
        }

        body[data-theme="dark"] .serial {
            background: rgba(121, 168, 255, 0.16);
            color: #b9d0ff;
        }

        body[data-theme="dark"] .notes {
            color: var(--muted);
        }

        @media (max-width: 1250px) {
            .sections-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            body {
                padding: 14px;
            }

            .page-header {
                display: grid;
            }

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .title-block h1 {
                font-size: 24px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            html,
            body,
            body[data-theme="dark"] {
                --bg: #ffffff;
                --panel: #ffffff;
                --panel-soft: #ffffff;
                --text: #000000;
                --muted: #333333;
                --border: #000000;
                --blue: #000000;
                --teal: #000000;
                --amber: #000000;
                --red: #000000;
                --green: #000000;
                --shadow: none;
                background: #ffffff !important;
                color: #000000 !important;
            }

            body {
                background: #ffffff;
                padding: 0;
                color: #000000;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page-header,
            .control-panel,
            .summary-grid,
            .cancel-btn {
                display: none;
            }

            .print-header {
                display: block;
                margin-bottom: 8px;
            }

            .print-header h1 {
                margin: 0 0 4px;
                font-size: 15px;
            }

            .print-header p {
                margin: 0;
                font-size: 11px;
            }

            .page {
                max-width: none;
            }

            .sections-grid {
                display: block;
            }

            .confirmed-section,
            .type-group {
                box-shadow: none;
                background: #ffffff !important;
                border-color: #000000;
                break-inside: auto;
                margin-bottom: 6px;
                padding: 6px;
            }

            .section-head {
                margin-bottom: 6px;
                padding: 6px 8px;
                background: #ececec !important;
                border: 1px solid #000000;
                box-shadow: none;
            }

            .section-head h2,
            .type-head h3 {
                font-size: 12px;
                margin: 0;
            }

            .section-head span,
            .type-head span {
                font-size: 10px;
            }

            .section-head strong,
            .type-head strong {
                min-width: 24px;
                height: 24px;
                font-size: 10px;
            }

            .type-head {
                padding: 6px 8px;
                background: #f3f3f3 !important;
                border: 1px solid #000000;
                box-shadow: none;
            }

            table {
                min-width: 0;
                table-layout: fixed;
            }

            .col-postop-note,
            .postop-note-cell {
                display: table-cell;
            }

            body[data-theme="dark"] .confirmed-section,
            body[data-theme="dark"] .type-group,
            body[data-theme="dark"] th,
            body[data-theme="dark"] td,
            body[data-theme="dark"] .empty-state,
            body[data-theme="dark"] .serial,
            body[data-theme="dark"] .eye-badge,
            body[data-theme="dark"] .notes,
            body[data-theme="dark"] .patient-name,
            body[data-theme="dark"] .section-head h2,
            body[data-theme="dark"] .section-head span,
            body[data-theme="dark"] .type-head h3,
            body[data-theme="dark"] .type-head span {
                background: #ffffff !important;
                color: #000000 !important;
                border-color: #000000 !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            body[data-theme="dark"] .section-head,
            body[data-theme="dark"] .surgery .section-head,
            body[data-theme="dark"] .laser .section-head,
            body[data-theme="dark"] .injection .section-head {
                background: #ececec !important;
                color: #000000 !important;
                border-color: #000000 !important;
            }

            body[data-theme="dark"] .type-head {
                background: #f3f3f3 !important;
                color: #000000 !important;
                border-color: #000000 !important;
            }

            .section-head strong,
            .type-head strong {
                background: #d9d9d9 !important;
                color: #000000 !important;
                border: 1px solid #000000;
            }

            .section-head span,
            .type-head span,
            .notes {
                color: #222222 !important;
            }

            th,
            td {
                border-color: #000000;
                color: #000000;
                padding: 4px 5px;
                font-size: 10px;
                line-height: 1.25;
                word-break: break-word;
            }

            th:nth-child(8),
            td:nth-child(8) {
                display: none;
            }

            .notes {
                min-width: 0;
            }

            .serial,
            .eye-badge {
                min-width: 24px;
                padding: 2px 5px;
                font-size: 10px;
                border: 1px solid #000000;
                background: #ffffff !important;
                color: #000000 !important;
            }

            .postop-note-line {
                border: 1px solid #000000;
                min-height: 34px;
                border-radius: 0;
            }

            tr,
            td,
            th {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="print-header">
        <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
        <p>قائمة العمليات</p>
        <p>التاريخ: <?= h($date) ?></p>
    </div>

    <main class="page">
        <header class="page-header">
            <div class="title-block">
                <span>القوائم المؤكدة</span>
                <h1>مواعيد العمليات والليزر والحقن</h1>
                <p>مرتبة حسب القسم، ومقسمة داخل كل قسم حسب النوع.</p>
            </div>

            <nav class="top-actions" aria-label="روابط الصفحة">
                <a class="nav-btn" href="dashboard.php">الصفحة الرئيسية</a>
                <a class="nav-btn" href="operation-by-date.php?date=<?= urlencode($date) ?>">عرض العمليات حسب التاريخ</a>
                <a class="nav-btn success" href="export_surgery_excel.php?date=<?= urlencode($date) ?>">Export Surgery List</a>
            </nav>
        </header>

        <section class="control-panel">
            <form class="filter-form" method="GET">
                <input type="date" name="date" value="<?= h($date) ?>">
                <button class="tool-btn primary" type="submit">عرض</button>
                <button class="tool-btn" type="button" onclick="window.print()">طباعة</button>
            </form>
        </section>

        <section class="summary-grid" aria-label="ملخص القوائم المؤكدة">
            <div class="summary-card"><span>المجموع</span><strong><?= $totalConfirmed ?></strong></div>
            <div class="summary-card"><span>العمليات</span><strong><?= count($confirmedRows['surgery']) ?></strong></div>
            <div class="summary-card"><span>الليزر</span><strong><?= count($confirmedRows['laser']) ?></strong></div>
            <div class="summary-card"><span>الحقن</span><strong><?= count($confirmedRows['injection']) ?></strong></div>
        </section>

        <div class="sections-grid">
            <?php foreach ($operationConfig as $kind => $config): ?>
                <?php render_confirmed_section($kind, $config, $confirmedRows[$kind], $date); ?>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>