<?php
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زيارات اليوم 🏥</title>

    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>

    <style>
        * {
            box-sizing: border-box;
           
        }

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            margin: 0;
            background: #eef4f8;
            color: #333;
            direction: rtl;
        }

        /* ===== Header ===== */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: var(--card);
            box-shadow: var(--shadow);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }

        h1 {
            font-size: 22px;
            color: #7a1f1f;
            margin: 0;
        }

        .toggle-sidebar {
            border: none;
            cursor: pointer;
            padding: 8px 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-weight: bold;
        }

      /* ===== Layout ===== */
.layout {
    display: flex;
}

        /* ===== Sidebar ===== */
        .sidebar {
    width: 200px;
    background: var(--card);
    padding: 20px;
    position: fixed;
    top: 0;
    right: -200px; /* مخفي بالبداية */
    height: 100%;
    transition: 0.3s;
    z-index: 1000;
}

.sidebar.show {
    right: 0;
}
        .sidebar h3 {
            margin-bottom: 20px;
            color: var(--primary);
        }

        .menu-group {
            margin-bottom: 20px;
        }

        .menu-group span {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #777;
        }

        .menu-group a {
            display: block;
            padding: 8px 10px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text);
            margin-bottom: 5px;
            transition: 0.3s;
        }

        .menu-group a:hover,
        .menu-group a.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
        }

        .danger:hover {
            background: red !important;
        }

     
      

       /* ===== Content ===== */
.content {
    transition: 0.3s;
    width: 100%;
    padding: 20px;
}

/* عند ظهور السايدبار */
.content.shift {
    margin-right: 200px;
}

/* عند الإخفاء */
.content.full {
    margin-right: 0;
}
        /* ===== Table ===== */
        .table-card {
            background: #fff;
            border-radius: 14px;
            overflow: auto;
            max-height: 75vh;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, #3fa7d6, #2d89b5);
            color: #fff;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
        }

        tbody tr:hover {
            background: #f1f5f9;
        }

        a {
            text-decoration: none;
            color: #2d89b5;
        }

        /* ===== Buttons ===== */
        .enter-btn {
            background: #ffb703;
            padding: 6px 12px;
            border-radius: 14px;
            font-weight: bold;
            color: #333;
        }

        .enter-btn:hover {
            background: #ffa200;
        }

        .edit-btn {
            font-size: 18px;
        }

        /* ===== Badges ===== */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            color: #fff;
            font-size: 13px;
        }

        .first {
            background: #4caf50;
        }

        .repeat {
            background: #2196f3;
        }

        .free {
            background: #9c27b0;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            h1 {
                font-size: 18px;
            }

            .content {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- ===== Header ===== -->
    <header class="topbar">
        <button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>
        <h1>زيارات اليوم</h1>
    </header>

    <div class="layout">

        <!-- ===== Sidebar ===== -->
        <aside class="sidebar" id="sidebar">
            <h3>القائمة</h3>

            <div class="menu-group">
                <a href="dashboard.php">📊 لوحة التحكم</a>
            </div>

            <div class="menu-group">
                <span>👤 المرضى</span>
                <a href="add-patient.php">إضافة مريض</a>
                <a href="confirmed-list.php">قوائم العمليات</a>
                <a href="import_surgery_excel.php">استيراد العمليات</a>
                <a href="followups.php">المتابعة</a>
            </div>

            <div class="menu-group">
                <span>📅 المواعيد</span>
                <a href="visits.php" class="active">زيارات اليوم</a>
                <a href="operation-by-date.php">مواعيد العمليات</a>
                <a href="import_expected.php">استيراد المواعيد</a>
                <a href="expected_appointments.php">المواعيد المتوقعة</a>
            </div>

            <div class="menu-group">
                <span>⚙️ النظام</span>
                <a href="reports.php">التقارير</a>
                <a href="common-medicines.php">الأدوية</a>
                <a href="settings.php">الإعدادات</a>
                <a href="logout.php" class="danger">تسجيل الخروج</a>
            </div>
        </aside>

        

        <!-- ===== Content ===== -->
       <main class="content full" id="content">

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المريض</th>
                            <th>العمر</th>
                            <th>التاريخ</th>
                            <th>نوع الزيارة</th>
                            <th>تعديل</th>
                            <th>الدخول</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        include 'config.php';

                        $today = date("Y-m-d");

                        $sql = "
                        SELECT
                        visits.daily_serial,
                        visits.visit_type,
                        visits.visit_date,
                        visits.visit_id,
                        add_patient.id,
                        add_patient.full_name,
                        add_patient.age
                        FROM visits
                        JOIN add_patient ON visits.patient_id = add_patient.id
                        WHERE visits.visit_date = '$today'
                        ORDER BY visits.daily_serial ASC
                        ";

                        $result_select = mysqli_query($con, $sql);

                        while ($row = mysqli_fetch_assoc($result_select)) {

                            switch ($row['visit_type']) {
                                case 'first':
                                    $visit_text = 'أول مرة';
                                    $visit_class = 'first';
                                    break;
                                case 'repeat':
                                    $visit_text = 'متكررة';
                                    $visit_class = 'repeat';
                                    break;
                                case 'free':
                                    $visit_text = 'مراجعة';
                                    $visit_class = 'free';
                                    break;
                                default:
                                    $visit_text = 'غير معروف';
                                    $visit_class = '';
                            }
                        ?>

                            <tr>
                                <td><?= $row['daily_serial']; ?></td>

                                <td>
                                    <a href="patient-file.php?id=<?= $row['id']; ?>">
                                        <?= htmlspecialchars($row['full_name']); ?>
                                    </a>
                                </td>

                                <td><?= $row['age']; ?></td>
                                <td><?= $row['visit_date']; ?></td>

                                <td>
                                    <span class="badge <?= $visit_class; ?>">
                                        <?= $visit_text; ?>
                                    </span>
                                </td>

                                <td>
                                    <a class="edit-btn"
                                        href="edit-visit.php?id_edit=<?= $row['visit_id']; ?>">
                                        ✏️
                                    </a>
                                </td>

                                <td>
                                    <a class="enter-btn"
                                        href="patient-file.php?id=<?= $row['id']; ?>">
                                        دخول
                                    </a>
                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>
                </table>
            </div>

        </main>

    </div>

    <script>

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('show');
            content.classList.toggle('shift');
            content.classList.toggle('full');
        }
        
    </script>

</body>

</html>