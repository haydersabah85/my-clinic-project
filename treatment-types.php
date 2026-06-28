<?php
include 'config.php';
include 'auth.php';

clinic_ensure_treatment_type_tables($con);

$tabs = [
    'surgery' => ['table' => 'surgery_types', 'label' => 'أنواع العمليات'],
    'laser' => ['table' => 'laser_types', 'label' => 'أنواع الليزر'],
    'injection' => ['table' => 'injection_types', 'label' => 'أنواع الإبر'],
    'iol' => ['table' => 'iol_types', 'label' => 'أنواع العدسات'],
];

$tab = strtolower((string) ($_GET['tab'] ?? 'surgery'));
if (!isset($tabs[$tab])) {
    $tab = 'surgery';
}

$current = $tabs[$tab];
$table = $current['table'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    clinic_require_csrf();

    $postedTab = strtolower((string) ($_POST['tab'] ?? $tab));
    if (isset($tabs[$postedTab])) {
        $tab = $postedTab;
        $current = $tabs[$tab];
        $table = $current['table'];
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = trim((string) ($_POST['type_name'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 100);
        if ($sortOrder < 0) {
            $sortOrder = 0;
        }

        if ($name === '') {
            clinic_set_flash('error', 'يرجى إدخال اسم النوع.');
        } else {
            $stmt = mysqli_prepare($con, "INSERT IGNORE INTO `$table` (type_name, is_active, sort_order, sync_status) VALUES (?, 1, ?, 0)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $name, $sortOrder);
                mysqli_stmt_execute($stmt);
                $affected = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);

                if ($affected > 0) {
                    clinic_set_flash('success', 'تمت إضافة النوع بنجاح.');
                } else {
                    clinic_set_flash('error', 'هذا النوع موجود مسبقاً أو لم تتم إضافته.');
                }
            } else {
                clinic_set_flash('error', 'تعذر إضافة النوع حالياً.');
            }
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['type_name'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 100);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || $name === '') {
            clinic_set_flash('error', 'بيانات التعديل غير مكتملة.');
        } else {
            $stmt = mysqli_prepare($con, "UPDATE `$table` SET type_name = ?, sort_order = ?, is_active = ?, sync_status = 0 WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'siii', $name, $sortOrder, $isActive, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                clinic_set_flash('success', 'تم تحديث النوع بنجاح.');
            } else {
                clinic_set_flash('error', 'تعذر تحديث النوع.');
            }
        }
    }

    header('Location: treatment-types.php?tab=' . urlencode($tab));
    exit;
}

$flash = clinic_take_flash();
$result = mysqli_query($con, "SELECT id, type_name, is_active, sort_order, updated_at FROM `$table` ORDER BY sort_order ASC, type_name ASC");
$rows = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأنواع العلاجية</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        :root {
            --bg: #f3f6fb;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #dbe4ef;
            --brand: #2563eb;
            --brand-2: #0891b2;
            --ok: #16a34a;
            --warn: #b45309;
            --danger: #dc2626;
            --shadow: 0 18px 35px rgba(15, 23, 42, 0.10);
        }

        body[data-theme="dark"] {
            --bg: #07111d;
            --panel: #101c2d;
            --text: #e6edf5;
            --muted: #a8bdd1;
            --line: rgba(148, 163, 184, 0.25);
            --shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            background: radial-gradient(circle at 80% -20%, rgba(37, 99, 235, .2), transparent 40%), var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }

        .page {
            width: min(1100px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 14px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 14px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .top h1 {
            margin: 0;
            font-size: clamp(22px, 3vw, 30px);
        }

        .sub {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .actions,
        .tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 10px;
            min-height: 38px;
            padding: 0 12px;
            text-decoration: none;
            color: var(--text);
            background: var(--panel);
            font-weight: 700;
            cursor: pointer;
        }

        .btn.primary {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            border: none;
        }

        .tab {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 14px;
            text-decoration: none;
            color: var(--text);
            background: var(--panel);
            font-size: 13px;
            font-weight: 700;
        }

        .tab.active {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            border: none;
        }

        .flash {
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
        }

        .flash.success {
            background: rgba(22, 163, 74, 0.15);
            color: var(--ok);
        }

        .flash.error {
            background: rgba(220, 38, 38, 0.15);
            color: var(--danger);
        }

        form.inline {
            display: grid;
            grid-template-columns: 2.2fr 120px auto;
            gap: 8px;
            align-items: end;
        }

        .field {
            display: grid;
            gap: 5px;
        }

        .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }

        .field input {
            min-height: 38px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--panel);
            color: var(--text);
            padding: 8px 10px;
            font: inherit;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 10px 8px;
            text-align: right;
            font-size: 13px;
            vertical-align: middle;
        }

        th {
            color: var(--muted);
            font-size: 12px;
        }

        .row-form {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) 110px 88px 90px;
            gap: 8px;
            align-items: center;
        }

        .row-form input[type="text"],
        .row-form input[type="number"] {
            min-height: 36px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            color: var(--text);
            padding: 6px 9px;
            font: inherit;
        }

        .row-form .save {
            min-height: 36px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-on {
            color: var(--ok);
            background: rgba(22, 163, 74, .14);
        }

        .status-off {
            color: var(--warn);
            background: rgba(180, 83, 9, .16);
        }

        @media (max-width: 820px) {
            form.inline {
                grid-template-columns: 1fr;
            }

            .row-form {
                grid-template-columns: 1fr;
            }

            table,
            thead,
            tbody,
            tr,
            td,
            th {
                display: block;
                width: 100%;
            }

            thead {
                display: none;
            }

            tr {
                border-bottom: 1px solid var(--line);
                margin-bottom: 8px;
                padding-bottom: 8px;
            }

            td {
                border-bottom: none;
                padding: 5px 0;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <section class="card top">
            <div>
                <h1>إدارة أنواع الإجراءات</h1>
                <p class="sub">إضافة أو تحديث أنواع العمليات والليزر والإبر بدون تعديل الكود.</p>
            </div>
            <div class="actions">
                <a class="btn" href="operation-by-date.php">العودة للمواعيد</a>
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
            </div>
        </section>

        <section class="card tabs">
            <?php foreach ($tabs as $key => $info): ?>
                <a class="tab <?= $key === $tab ? 'active' : '' ?>" href="treatment-types.php?tab=<?= h($key) ?>"><?= h($info['label']) ?></a>
            <?php endforeach; ?>
        </section>

        <?php if ($flash): ?>
            <div class="flash <?= h($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?> card"><?= h($flash['message'] ?? '') ?></div>
        <?php endif; ?>

        <section class="card">
            <h3 style="margin:0 0 12px;"><?= h($current['label']) ?></h3>
            <form class="inline" method="post">
                <?= clinic_csrf_input() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="tab" value="<?= h($tab) ?>">

                <div class="field">
                    <label for="type_name">اسم النوع الجديد</label>
                    <input id="type_name" type="text" name="type_name" required>
                </div>

                <div class="field">
                    <label for="sort_order">الترتيب</label>
                    <input id="sort_order" type="number" name="sort_order" value="100" min="0">
                </div>

                <button class="btn primary" type="submit">إضافة نوع</button>
            </form>
        </section>

        <section class="card">
            <table>
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>الترتيب</th>
                        <th>الحالة</th>
                        <th>آخر تحديث</th>
                        <th>حفظ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5">لا توجد أنواع بعد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td colspan="5">
                                    <form class="row-form" method="post">
                                        <?= clinic_csrf_input() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="tab" value="<?= h($tab) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

                                        <input type="text" name="type_name" value="<?= h($row['type_name'] ?? '') ?>" required>
                                        <input type="number" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 100) ?>" min="0">

                                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;">
                                            <input type="checkbox" name="is_active" value="1" <?= (int) ($row['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                                            <span class="status-pill <?= (int) ($row['is_active'] ?? 1) === 1 ? 'status-on' : 'status-off' ?>">
                                                <?= (int) ($row['is_active'] ?? 1) === 1 ? 'فعال' : 'موقوف' ?>
                                            </span>
                                        </label>

                                        <div style="font-size:11px;color:var(--muted);">
                                            <?= h((string) ($row['updated_at'] ?? '')) ?>
                                        </div>

                                        <button class="save" type="submit">حفظ</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>

</html>