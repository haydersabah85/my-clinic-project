<?php
include "config.php";
include "auth.php";

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$stmt = mysqli_prepare($con, "
    SELECT
        f.id,
        f.patient_id,
        f.followup_date,
        f.followup_reason,
        f.note,
        f.status,
        p.full_name,
        p.phone_no AS phone
    FROM followups f
    JOIN add_patient p ON f.patient_id = p.id
    WHERE f.followup_date = ?
    AND f.status = 'pending'
    ORDER BY p.full_name ASC
");
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$appointments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مراجعات اليوم المتوقعة</title>

<style>
* { box-sizing: border-box; }
body {
    font-family: Cairo, Tahoma, Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
    margin: 0;
    padding: 24px;
}

.page {
    max-width: 1180px;
    margin: 0 auto;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}

h2 {
    margin: 0;
    color: #0d6efd;
    font-size: 26px;
}

.links,
.filters {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.links a,
.filters button {
    border: 0;
    border-radius: 8px;
    padding: 10px 14px;
    text-decoration: none;
    color: #fff;
    background: #0d6efd;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
}

.filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 18px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
}

.filters label {
    font-weight: 800;
}

.filters input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 9px 10px;
    font-family: inherit;
}

.table-wrap {
    overflow-x: auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, .1);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
    direction: rtl;
}

th,
td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
}

th {
    background: #0d6efd;
    color: #fff;
}

tr:hover {
    background: #f8fafc;
}

.actions {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.actions a {
    border-radius: 8px;
    padding: 8px 12px;
    color: #fff;
    text-decoration: none;
    font-weight: 800;
}

.done { background: #198754; }
.open { background: #475569; }

.empty {
    padding: 28px;
    text-align: center;
    color: #64748b;
    font-weight: 800;
}

body[data-theme="dark"] {
    background: #07111d;
    color: #e6edf5;
}

body[data-theme="dark"] .filters,
body[data-theme="dark"] .table-wrap {
    background: #0f1b2a;
    border-color: rgba(148, 163, 184, .18);
}

body[data-theme="dark"] tr:hover {
    background: #111f30;
}

body[data-theme="dark"] td {
    border-bottom-color: rgba(148, 163, 184, .16);
}

body[data-theme="dark"] .filters input {
    background: #0b1220;
    color: #e6edf5;
    border-color: rgba(148, 163, 184, .28);
}

@media (max-width: 760px) {
    body { padding: 12px; }
    .topbar { align-items: stretch; flex-direction: column; }
    h2 { font-size: 22px; }
    .links a,
    .filters button,
    .filters input { width: 100%; text-align: center; }
}
</style>

<link rel="stylesheet" href="assets/dark-mode.css">
<script src="assets/theme.js" defer></script>
</head>
<body>
<div class="page">
    <div class="topbar">
        <h2>مراجعات اليوم المتوقعة</h2>
        <div class="links">
            <a href="dashboard.php">الصفحة الرئيسية</a>
            <a href="followups.php">كل المراجعات</a>
            <a href="followup-appointment.php">إعطاء موعد</a>
        </div>
    </div>

    <form class="filters" method="GET" action="expected_appointments.php">
        <label for="date">التاريخ</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">عرض</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>التاريخ</th>
                    <th>الهاتف</th>
                    <th>سبب المراجعة</th>
                    <th>ملاحظات</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($appointments): ?>
                    <?php foreach ($appointments as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['followup_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['followup_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>متوقع</td>
                            <td>
                                <div class="actions">
                                    <a class="done" href="mark_done.php?id=<?= (int) $row['id'] ?>&patient_id=<?= (int) $row['patient_id'] ?>">تمت المراجعة</a>
                                    <a class="open" href="patient-data.php?id=<?= (int) $row['patient_id'] ?>">فتح الملف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td class="empty" colspan="8">لا توجد مراجعات متوقعة في هذا اليوم</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
