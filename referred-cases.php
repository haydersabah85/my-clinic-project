<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
$flash = clinic_take_flash();

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$search = trim((string) ($_GET['q'] ?? ''));
$fromDate = trim((string) ($_GET['from'] ?? ''));
$toDate = trim((string) ($_GET['to'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(patient_full_name LIKE ? OR referring_doctor_name LIKE ? OR surgery_type LIKE ? OR patient_phone LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
    $where[] = 'surgery_date >= ?';
    $params[] = $fromDate;
    $types .= 's';
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
    $where[] = 'surgery_date <= ?';
    $params[] = $toDate;
    $types .= 's';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalCases = 0;
$monthCases = 0;
$doctorsCount = 0;

$totalRow = mysqli_fetch_assoc(mysqli_query($con, 'SELECT COUNT(*) total FROM referred_surgery_cases'));
$totalCases = (int) ($totalRow['total'] ?? 0);

$monthRow = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) total
    FROM referred_surgery_cases
    WHERE MONTH(surgery_date) = MONTH(CURDATE())
      AND YEAR(surgery_date) = YEAR(CURDATE())
"));
$monthCases = (int) ($monthRow['total'] ?? 0);

$doctorRow = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(DISTINCT referring_doctor_name) total
    FROM referred_surgery_cases
    WHERE TRIM(referring_doctor_name) <> ''
"));
$doctorsCount = (int) ($doctorRow['total'] ?? 0);

$sql = "
    SELECT id, patient_full_name, patient_age, patient_phone, patient_city,
           referring_doctor_name, referring_doctor_clinic, referring_doctor_phone,
           referral_date, surgery_date, surgery_type, eye, surgeon_name,
           followup_destination, created_by, created_at
    FROM referred_surgery_cases
    $whereSql
    ORDER BY surgery_date DESC, id DESC
    LIMIT 300
";

$rows = [];
$stmt = mysqli_prepare($con, $sql);
if ($stmt) {
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
}

function followup_label(string $value): string
{
    if ($value === 'clinic') {
        return 'في المركز';
    }
    if ($value === 'referrer') {
        return 'عودة للطبيب المحول';
    }
    return 'غير محدد';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>الحالات المحولة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --text: #172033;
            --muted: #64748b;
            --border: #dbe7ef;
            --primary: #2563eb;
            --teal: #0f766e;
            --red: #dc2626;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            --radius: 12px;
        }

        body[data-theme="dark"],
        body.dark {
            --bg: #07111d;
            --panel: #101c2d;
            --panel-soft: #0c1625;
            --text: #e6edf5;
            --muted: #a8bdd1;
            --border: rgba(148, 163, 184, 0.2);
            --shadow: 0 20px 45px rgba(0, 0, 0, 0.32);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            width: min(1200px, calc(100% - 32px));
            margin: 0 auto;
            padding: 22px 0 34px;
        }

        .topbar,
        .stats,
        .toolbar,
        .table-wrap,
        .notice {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .topbar,
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .title h1 {
            margin: 2px 0 0;
            font-size: 27px;
        }

        .title span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn,
        button {
            min-height: 38px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--panel-soft);
            color: var(--text);
            padding: 8px 11px;
            font: inherit;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.primary {
            background: var(--teal);
            border-color: var(--teal);
            color: #fff;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            padding: 12px;
            margin-bottom: 14px;
        }

        .stat {
            background: var(--panel-soft);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px;
        }

        .stat span {
            display: block;
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 2px;
        }

        .stat p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .toolbar form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            width: 100%;
        }

        .toolbar input {
            min-height: 38px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--panel-soft);
            color: var(--text);
            padding: 8px 10px;
            font: inherit;
        }

        .toolbar .search {
            flex: 1 1 260px;
        }

        .table-wrap {
            overflow: hidden;
        }

        .table-scroll {
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        th,
        td {
            border-bottom: 1px solid var(--border);
            padding: 9px 10px;
            text-align: right;
            vertical-align: top;
            font-size: 13px;
        }

        th {
            background: var(--panel-soft);
            font-size: 12px;
            color: var(--muted);
            font-weight: 900;
        }

        .notice {
            margin-bottom: 14px;
            padding: 10px 12px;
            font-weight: 800;
        }

        .notice.success {
            border-right: 5px solid #16a34a;
        }

        .notice.error {
            border-right: 5px solid var(--red);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 4px 8px;
            background: var(--panel-soft);
            font-weight: 800;
            font-size: 12px;
        }

        @media (max-width: 840px) {
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="topbar">
            <div class="title">
                <span>قاعدة بيانات مستقلة</span>
                <h1>الحالات المحولة للعمليات</h1>
            </div>
            <div class="actions">
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
                <a class="btn primary" href="add-referred-case.php">إضافة حالة محولة</a>
                <button class="btn" type="button" id="themeToggle" aria-label="تبديل الوضع">◐</button>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= e($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><span><?= $totalCases ?></span>
                <p>إجمالي الحالات المحولة</p>
            </div>
            <div class="stat"><span><?= $monthCases ?></span>
                <p>حالات هذا الشهر</p>
            </div>
            <div class="stat"><span><?= $doctorsCount ?></span>
                <p>عدد الأطباء المُحيلين</p>
            </div>
        </section>

        <section class="toolbar">
            <form method="get" action="referred-cases.php">
                <input class="search" type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث بالاسم أو الطبيب أو نوع العملية أو الهاتف">
                <input type="date" name="from" value="<?= e($fromDate) ?>">
                <input type="date" name="to" value="<?= e($toDate) ?>">
                <button type="submit">تصفية</button>
                <a class="btn" href="referred-cases.php">إعادة ضبط</a>
            </form>
        </section>

        <section class="table-wrap">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المريض</th>
                            <th>الاتصال</th>
                            <th>الطبيب المُحوِّل</th>
                            <th>بيانات العملية</th>
                            <th>المتابعة</th>
                            <th>التسجيل</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="8">لا توجد بيانات مطابقة.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= (int) $row['id'] ?></td>
                                    <td>
                                        <strong><?= e($row['patient_full_name']) ?></strong><br>
                                        العمر: <?= e($row['patient_age'] !== '' ? $row['patient_age'] : '-') ?><br>
                                        المدينة: <?= e($row['patient_city'] !== '' ? $row['patient_city'] : '-') ?>
                                    </td>
                                    <td>
                                        المريض: <span dir="ltr"><?= e($row['patient_phone'] !== '' ? $row['patient_phone'] : '-') ?></span><br>
                                        الطبيب: <span dir="ltr"><?= e($row['referring_doctor_phone'] !== '' ? $row['referring_doctor_phone'] : '-') ?></span>
                                    </td>
                                    <td>
                                        <strong><?= e($row['referring_doctor_name']) ?></strong><br>
                                        <?= e($row['referring_doctor_clinic'] !== '' ? $row['referring_doctor_clinic'] : '-') ?><br>
                                        تحويل: <?= e($row['referral_date'] ?: '-') ?>
                                    </td>
                                    <td>
                                        <strong><?= e($row['surgery_type']) ?></strong>
                                        <span class="pill"><?= e($row['eye'] ?: 'غير محدد') ?></span><br>
                                        تاريخ: <?= e($row['surgery_date']) ?><br>
                                        الجراح: <?= e($row['surgeon_name'] !== '' ? $row['surgeon_name'] : '-') ?>
                                    </td>
                                    <td><?= e(followup_label((string) ($row['followup_destination'] ?? 'unknown'))) ?></td>
                                    <td>
                                        بواسطة: <?= e($row['created_by'] !== '' ? $row['created_by'] : '-') ?><br>
                                        <?= e($row['created_at']) ?>
                                    </td>
                                    <td>
                                        <a class="btn" href="edit-referred-case.php?id=<?= (int) $row['id'] ?>">تعديل</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>

</html>