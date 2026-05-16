<?php
include 'auth.php';
include 'config.php';

$today = date('Y-m-d');
$stats = ['total' => 0, 'first' => 0, 'repeat' => 0, 'free' => 0];

$stmt = mysqli_prepare($con, "
    SELECT
        v.daily_serial,
        v.visit_type,
        v.visit_date,
        v.visit_id,
        p.id AS patient_id,
        p.full_name,
        p.age
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
    $visits[] = $row;
    $stats['total']++;
    if (isset($stats[$row['visit_type']])) {
        $stats[$row['visit_type']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="60">
<title>زيارات اليوم الاحترافية</title>

<link rel="stylesheet" href="assets/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
* { box-sizing: border-box; }
body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    margin: 0;
    background: #eef4f8;
    color: #333;
    direction: rtl;
}

h1 {
    text-align: center;
    color: #7a1f1f;
    margin: 25px 0;
}

header {
    padding: 0 20px;
}

.toggle-sidebar {
    border: none;
    cursor: pointer;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    background: linear-gradient(135deg, #2d89b5, #3fa7d6);
    color: #fff;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.container {
    display: flex;
    max-width: 1500px;
    margin: auto;
    padding: 15px;
    gap: 20px;
    height: calc(100vh - 110px);
}

.sidebar {
    width: 220px;
    background: #fff;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
    padding: 20px;
    border-radius: 18px;
    overflow-y: auto;
}

.sidebar.hidden {
    width: 0;
    padding: 0;
    overflow: hidden;
}

.sidebar h3 {
    color: #2d89b5;
    margin-top: 0;
    font-size: 24px;
}

.menu-group { margin-bottom: 25px; }

.menu-group span {
    display: block;
    font-weight: bold;
    color: #666;
    margin-bottom: 10px;
}

.menu-group a {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 6px;
    text-decoration: none;
    color: #333;
    transition: .3s;
}

.menu-group a:hover {
    background: linear-gradient(135deg, #2d89b5, #3fa7d6);
    color: #fff;
}

.main-content {
    flex: 1;
    overflow: hidden;
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

.card .num {
    font-size: 30px;
    font-weight: bold;
    color: #2d89b5;
}

.card .label {
    margin-top: 8px;
    font-weight: 700;
    color: #555;
}

.search-box {
    margin-bottom: 15px;
}

.search-box input {
    width: 100%;
    max-width: 500px;
    padding: 12px 16px;
    border: 2px solid #dbe4ea;
    border-radius: 12px;
    font-size: 16px;
    outline: none;
}

.search-box input:focus {
    border-color: #2d89b5;
    box-shadow: 0 0 0 4px rgba(45,137,181,.12);
}

.table-responsive {
    background: #fff;
    border-radius: 16px;
    overflow: auto;
    max-height: calc(100vh - 320px);
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

thead th {
    position: sticky;
    top: 0;
    background: linear-gradient(135deg, #3fa7d6, #2d89b5);
    color: #fff;
    z-index: 2;
}

th, td {
    padding: 12px 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tbody tr:hover {
    background: #f5fbff;
}

.badge {
    padding: 6px 14px;
    border-radius: 20px;
    color: #fff;
    font-weight: bold;
}

.first { background: #6fbf73; }
.repeat { background: #3fa7d6; }
.free { background: #b3396d; }

.name-link {
    text-decoration: none;
    color: #2d89b5;
    font-weight: 600;
}

.actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    margin: 0 3px;
    border-radius: 50%;
    text-decoration: none;
    font-size: 17px;
    color: #fff;
    transition: .3s;
}

.actions a:hover {
    transform: translateY(-2px) scale(1.08);
}

.enter { background: #43a047; }
.edit { background: #2d89b5; }
.delete { background: #e74c3c; }

.empty {
    padding: 30px;
    font-size: 18px;
    color: #777;
}

@media (max-width: 992px) {
    .container { flex-direction: column; height: auto; }
    .sidebar { width: 100%; }
    .table-responsive { max-height: 500px; }
}

@media (max-width: 1200px) {
    .stats { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
    .container { padding: 10px; }
    .sidebar { width: 100%; }
    .table-responsive { max-height: 600px; }
}
</style>
</head>
<body>

<h1>🏥 زيارات اليوم</h1>

<header>
    <button class="toggle-sidebar" onclick="toggleSidebar()">⬅️ القائمة</button>
</header>

<div class="container">

    <aside class="sidebar hidden" id="sidebar">
        <h3>القائمة</h3>

        <div class="menu-group">
            <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> لوحة التحكم</a>
        </div>

        <div class="menu-group">
            <span>👤 المرضى</span>
            <a href="add-patient.php"><i class="fa-solid fa-user-plus"></i> إضافة مريض</a>
            <a href="confirmed-list.php"><i class="fa-solid fa-bed-pulse"></i> قوائم العمليات</a>
            <a href="followups.php"><i class="fa-solid fa-stethoscope"></i> المتابعة</a>
        </div>

        <div class="menu-group">
            <span>📅 المواعيد</span>
            <a href="visits.php"><i class="fa-solid fa-calendar-day"></i> زيارات اليوم</a>
            <a href="operation-by-date.php"><i class="fa-solid fa-calendar-check"></i> مواعيد العمليات</a>
            <a href="expected_appointments.php"><i class="fa-solid fa-clock"></i> المواعيد المتوقعة</a>
        </div>

        <div class="menu-group">
            <span>⚙️ النظام</span>
            <a href="reports.php"><i class="fa-solid fa-chart-line"></i> التقارير</a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i> الإعدادات</a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
        </div>
    </aside>

    <div class="main-content">

        <div class="stats">
            <div class="card"><div class="num"><?= $stats['total'] ?></div><div class="label">إجمالي الزيارات</div></div>
            <div class="card"><div class="num"><?= $stats['first'] ?></div><div class="label">زيارة أول مرة</div></div>
            <div class="card"><div class="num"><?= $stats['repeat'] ?></div><div class="label">زيارة متكررة</div></div>
            <div class="card"><div class="num"><?= $stats['free'] ?></div><div class="label">زيارة مراجعة</div></div>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 ابحث باسم المريض أو نوع الزيارة أو الرقم التسلسلي...">
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>التسلسل</th>
                        <th>اسم المريض</th>
                        <th>العمر</th>
                        <th>التاريخ</th>
                        <th>نوع الزيارة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($visits)): ?>
                    <tr><td colspan="6" class="empty">لا توجد زيارات اليوم</td></tr>
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
                            default:
                                $visit_text = 'غير معروف';
                                $visit_class = '';
                        }
                        ?>
                        <tr>
                            <td><?= $row['daily_serial'] ?></td>
                            <td>
                                <a class="name-link" href="patient-file.php?id=<?= $row['patient_id'] ?>">
                                    <?= htmlspecialchars($row['full_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= date('Y/m/d', strtotime($row['visit_date'])) ?></td>
                            <td><span class="badge <?= $visit_class ?>"><?= $visit_text ?></span></td>
                            <td class="actions">
                                <a class="enter" title="دخول الملف" href="patient-file.php?id=<?= $row['patient_id'] ?>">
                                    <i class="fa-solid fa-folder-open"></i>
                                </a>
                                <a class="edit" title="تعديل الزيارة" href="edit-visit.php?id_edit=<?= $row['visit_id'] ?>">
                                    <i class="fa-solid fa-user-pen"></i>
                                </a>
                                <a class="delete" title="حذف الزيارة"
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
    const btn = document.querySelector('.toggle-sidebar');

    sidebar.classList.toggle('hidden');

    btn.textContent = sidebar.classList.contains('hidden')
        ? '➡️ إظهار القائمة'
        : '⬅️ إخفاء القائمة';
}

document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
