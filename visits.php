<?php
include 'auth.php';
include 'config.php';

$today = date('Y-m-d');
$stats = ['total' => 0, 'free' => 0, 'done' => 0, 'pending' => 0];
$last_visit_date = null;

$stmt = mysqli_prepare($con, "
    SELECT
        v.daily_serial,
        v.visit_type,
        v.visit_date,
        v.is_done,
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

    $stats['total']++;
    if (isset($stats[$row['visit_type']])) {
        $stats[$row['visit_type']]++;
    }
    if ($row['is_done']) {
        $stats['done']++;
    } else {
        $stats['pending']++;
    }
    // جلب تاريخ آخر زيارة للمريض

    $last_visit_date_query = "SELECT visit_date FROM visits WHERE patient_id = {$row['patient_id']} AND visit_date < '{$row['visit_date']}' ORDER BY visit_date DESC LIMIT 1";
    $last_visit_date_result = mysqli_query($con, $last_visit_date_query);
    if ($last_visit_date_result && mysqli_num_rows($last_visit_date_result) > 0) {
        $last_visit_date_row = mysqli_fetch_assoc($last_visit_date_result);
        $row['last_visit_date'] = $last_visit_date_row['visit_date'];
    } else {
        $row['last_visit_date'] = null;
    }
    $visits[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>زيارات اليوم </title>

    <link rel="stylesheet" href="assets/theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="assets/theme.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --bg-main: #f7f3eb;
            --bg-alt: #efe6d7;
            --ink: #243022;
            --muted: #66756a;
            --panel: rgba(255, 252, 246, 0.9);
            --panel-border: rgba(163, 141, 102, 0.26);
            --head: #23443b;
            --accent: #c58c41;
            --accent-2: #2c8c77;
            --danger: #b85a54;
            --ok: #3f8d63;
            --shadow: 0 16px 34px rgba(54, 49, 35, 0.12);
            --shadow-strong: 0 26px 52px rgba(54, 49, 35, 0.18);
        }

        body[data-theme="dark"],
        body.dark {
            --bg-main: #101713;
            --bg-alt: #17231d;
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
            background:
                radial-gradient(circle at 88% 8%, rgba(197, 140, 65, 0.18), transparent 28%),
                radial-gradient(circle at 10% 0%, rgba(44, 140, 119, 0.16), transparent 25%),
                linear-gradient(180deg, var(--bg-main), var(--bg-alt));
            min-height: 100vh;
        }

        h1 {
            margin: 0;
            padding: 20px 16px;
            text-align: center;
            font-size: clamp(24px, 4vw, 34px);
            font-weight: 800;
            color: #ffffff;
            background: linear-gradient(120deg, #23443b, #3a665b 55%, #c58c41);
            border-bottom: 1px solid rgba(255, 255, 255, 0.25);
            letter-spacing: 0.4px;
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

        .toggle-sidebar,
        .theme-toggle {
            border: 1px solid rgba(35, 68, 59, 0.2);
            border-radius: 12px;
            min-height: 42px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.8);
            color: #23443b;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .toggle-sidebar:hover,
        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
            background: rgba(255, 255, 255, 0.96);
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
            border-radius: 18px;
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

        .menu-group {
            margin-bottom: 18px;
        }

        .menu-group span {
            display: block;
            margin-bottom: 7px;
            color: var(--muted);
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .menu-group a {
            display: block;
            text-decoration: none;
            color: var(--ink);
            border: 1px solid transparent;
            border-radius: 11px;
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
            grid-template-columns: repeat(auto-fit, minmax(176px, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 14px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            backdrop-filter: blur(8px);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-strong);
        }

        .card .num {
            font-size: 33px;
            font-weight: 800;
            color: #23443b;
            line-height: 1;
        }

        .card .label {
            margin-top: 7px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .search-box {
            margin-bottom: 12px;
        }

        .search-box input {
            width: min(100%, 580px);
            border: 1px solid var(--panel-border);
            background: var(--panel);
            color: var(--ink);
            border-radius: 12px;
            padding: 11px 13px;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            box-shadow: var(--shadow);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-box input::placeholder {
            color: var(--muted);
        }

        .search-box input:focus {
            border-color: var(--accent-2);
            box-shadow: 0 0 0 4px rgba(44, 140, 119, 0.16);
        }

        .table-responsive {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: auto;
            max-height: calc(150vh - 162px);
            transition: box-shadow 0.2s ease;
            backdrop-filter: blur(8px);
        }

        table {
            width: 100%;
            min-width: 940px;
            border-collapse: separate;
            border-spacing: 0;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(120deg, #23443b, #3a665b 58%, #c58c41);
            color: #ffffff;
            font-size: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.26);
        }

        th,
        td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid rgba(106, 114, 130, 0.24);
        }

        tbody tr:nth-child(even) {
            background: rgba(197, 140, 65, 0.05);
        }

        tbody tr:hover {
            background: rgba(44, 140, 119, 0.12);
        }

        .badge {
            display: inline-block;
            padding: 6px 13px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.2px;
        }

        .first {
            background: linear-gradient(120deg, #2c8c77, #3eb39a);
        }

        .repeat {
            background: linear-gradient(120deg, #5566a8, #7286d8);
        }

        .free {
            background: linear-gradient(120deg, #c58c41, #dfab68);
        }

        .status-done {
            background: linear-gradient(120deg, #3f8f60, #5bb67f);
        }

        .status-pending {
            background: linear-gradient(120deg, #c34f4d, #d96c67);
        }

        .name-link {
            text-decoration: none;
            color: #2b6d7a;
            font-weight: 800;
            transition: color 0.2s ease;
        }

        .name-link:hover {
            color: #c58c41;
        }

        .last-visit-text {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin: 0 2px;
            border-radius: 10px;
            color: #ffffff;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.24);
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .actions a:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .enter {
            background: #3f8d63;
        }

        .edit {
            background: #2b6d7a;
        }

        .delete {
            background: #b85a54;
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
</head>

<body>

    <h1>🏥 زيارات اليوم <?php echo date('d/m/Y'); ?></h1>

    <header>
        <button class="toggle-sidebar" onclick="toggleSidebar()">➡️ إظهار القائمة</button>
        <button class="theme-toggle" id="themeToggle" type="button">🌙</button>
    </header>

    <div class="container" id="layoutContainer">

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
                <div class="card">
                    <div class="num"><?= $stats['total'] ?></div>
                    <div class="label">إجمالي الزيارات</div>
                </div>
                <div class="card">
                    <div class="num"><?= $stats['free'] ?></div>
                    <div class="label">زيارة مجانية</div>
                </div>
                <div class="card">
                    <div class="num"><?= $stats['pending'] ?></div>
                    <div class="label">قيد الانتظار ⏳</div>
                </div>
                <div class="card">
                    <div class="num"><?= $stats['done'] ?></div>
                    <div class="label">تمت المعاينة ✅</div>
                </div>

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
                            <th>اخر زيارة</th>
                            <th>نوع الزيارة</th>
                            <th>الإجراء</th>
                            <th>حالة الزيارة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visits)): ?>
                            <tr>
                                <td colspan="7" class="empty">لا توجد زيارات اليوم</td>
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


                                    <td>
                                        <?php

                                        if ($row['last_visit_date'] !== null) {
                                            echo "<small class='last-visit-text'>آخر زيارة: " . $row['last_visit_date'] . "</small>";
                                        } else {
                                            echo "<small class='last-visit-text'>لا توجد زيارة سابقة</small>";
                                        }
                                        ?>
                                    </td>
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
                                    <td>
                                        <?php
                                        $visit_id = $row['visit_id'];
                                        $is_done_query = "SELECT is_done FROM visits WHERE visit_id = $visit_id";
                                        $is_done_result = mysqli_query($con, $is_done_query);
                                        $is_done_row = mysqli_fetch_assoc($is_done_result);
                                        if ($is_done_row['is_done'] == 1) {
                                            echo '<span class="badge status-done">تمت المعاينة</span>';
                                        } else {
                                            echo '<span class="badge status-pending">قيد الانتظار</span>';
                                        }
                                        ?>
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