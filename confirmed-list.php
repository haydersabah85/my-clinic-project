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
    echo "<div><span>" . h($config['singular']) . "</span><h2>" . h($config['title']) . "</h2></div>";
    echo "<strong>" . h($total) . "</strong>";
    echo "</div>";

    if ($total === 0) {
        echo "<div class='empty-state'>لا توجد مواعيد مؤكدة لهذا القسم في التاريخ المحدد</div>";
        echo "</section>";
        return;
    }

    echo "<div class='type-groups'>";
    foreach ($groups as $typeName => $groupRows) {
        echo "<article class='type-group'>";
        echo "<div class='type-head'><div><span>النوع</span><h3>" . h($typeName) . "</h3></div><strong>" . count($groupRows) . "</strong></div>";
        echo "<div class='table-wrap'>";
        echo "<table>";
        echo "<thead><tr>";
        echo "<th>#</th><th>اسم المريض</th><th>العين</th><th>الملاحظات</th><th>الهاتف</th><th>هاتف بديل</th><th>إجراء</th>";
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
            echo "<td><span class='serial'>" . h($row['serial_no'] ?: $counter) . "</span></td>";
            echo "<td class='patient-name'>" . h($row['full_name']) . "</td>";
            echo "<td><span class='eye-badge" . h($eyeClass) . "'>" . h($eye ?: '-') . "</span></td>";
            echo "<td class='notes'>" . nl2br(h($row['notes'] ?: '-')) . "</td>";
            echo "<td dir='ltr'>" . h($row['phone'] ?: '-') . "</td>";
            echo "<td dir='ltr'>" . h($row['phone_alt'] ?: '-') . "</td>";
            echo "<td><a class='cancel-btn' href='" . h($cancelUrl) . "' onclick=\"return confirm('هل تريد إلغاء تأكيد هذا المريض؟');\">إلغاء التأكيد</a></td>";
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
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .section-head h2,
        .type-head h3 {
            margin: 2px 0 0;
            color: var(--text);
            font-size: 19px;
            font-weight: 900;
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

        .laser .section-head strong,
        .laser .type-head strong {
            background: var(--amber);
        }

        .injection .section-head strong,
        .injection .type-head strong {
            background: var(--teal);
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
            background: var(--panel-soft);
            border-bottom: 1px solid var(--border);
            padding: 11px 12px;
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
            text-align: right;
            vertical-align: top;
            font-size: 13px;
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
            min-width: 38px;
            padding: 4px 8px;
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
            body {
                background: #ffffff;
                padding: 0;
                color: #000000;
            }

            .page-header,
            .control-panel,
            .summary-grid,
            .cancel-btn {
                display: none;
            }

            .print-header {
                display: block;
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
                border-color: #000000;
                break-inside: avoid;
                margin-bottom: 12px;
            }

            table {
                min-width: 0;
            }

            th,
            td {
                border-color: #000000;
                color: #000000;
            }
        }
    </style>
</head>

<body>
    <div class="print-header">
        <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
        <p>القوائم المؤكدة حسب النوع</p>
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
