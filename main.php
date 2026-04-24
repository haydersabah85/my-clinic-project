<?php

include 'config.php';
include 'auth.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>بيانات المرضى</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="assets/theme.js" defer></script>

</head>
<style>
    /* ===== Root Variables ===== */
    :root {
        --primary: #2563eb;
        --secondary: #0f766e;
        --accent: #f59e0b;
        --danger: #dc2626;
        --success: #16a34a;
        --bg: #f1f5f9;
        --card: #ffffff;
        --text: #1e293b;
        --muted: #f87171;
        --radius: 14px;
        --shadow: 0 10px 25px rgba(0, 0, 0, .08);
    }

    /* ===== Dark Mode ===== */
    body.dark {
        --bg: #020617;
        --card: #0f172a;
        --text: #e5e7eb;
        --muted: #f87171;
        --primary: #60a5fa;
        --secondary: #2dd4bf;
    }

    /* ===== Global ===== */
    body {
        margin: 0;
        font-family: "Segoe UI", Tahoma;
        background: var(--bg);
        color: var(--text);
    }

    /* ===== Header ===== */
    header {
        background: var(--primary);
        color: #fff;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 18px;
    }

    /* ===== Layout ===== */
    .layout {
        display: flex;
        min-height: calc(100vh - 60px);
    }

    /* ===== Sidebar ===== */
    .sidebar {
        width: 180px;
        background: var(--card);
        box-shadow: var(--shadow);
        padding: 20px;
        transition: .3s;
    }

    .sidebar.hidden {
        width: 0;
        padding: 0;
        overflow: hidden;
    }

    .sidebar h3 {
        color: var(--primary);
        margin-bottom: 20px;
        font-weight: bold;
        font-size: 24px;
    }

    .menu-group {
        margin-bottom: 25px;
    }

    .menu-group span {
        display: block;
        font-weight: bold;
        font-size: 18px;
        color: var(--muted);
        margin-bottom: 10px;
    }

    .menu-group a {
        display: block;
        padding: 10px 14px;
        border-radius: 10px;
        margin-bottom: 6px;
        text-decoration: none;
        color: var(--text);
        transition: .3s;
    }

    .menu-group a:hover {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        transform: translateX(-5px);
    }

    .menu-group a.danger:hover {
        background: linear-gradient(135deg, var(--danger), #ef4444);
    }

    /* ===== Content ===== */
    .content {
        flex: 1;
        padding: 15px;
        transition: .3s;
    }

    /* ===== Buttons ===== */

    .toggle-sidebar {
        border: none;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        box-shadow: var(--shadow);
        transition: .3s;
    }

    .toggle-sidebar:hover {
        transform: translateY(-2px);
    }


    .theme-toggle {
        border: none;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 50%;
        font-size: 18px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
        box-shadow: var(--shadow);
        transition: .3s;
    }


    .theme-toggle:hover {
        transform: scale(1.1);
    }

    /* ===== Title ===== */
    h1 {
        text-align: center;
        margin: 15px 0 25px;
        font-size: 30px;
        color: #b24433;
    }

    /* ===== Search ===== */
    .search-bar {
        background: var(--card);
        padding: 15px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
        margin-bottom: 20px;
    }

    .search-bar input {
        width: 60%;
        padding: 10px;
        font-size: 16px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    /* ===== Table ===== */
    .table-scroll {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        max-height: 85vh;
        overflow: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    #timeline {
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        color: var(--primary);
    }

    th,
    td {
        padding: 14px;
        text-align: center;
    }

    th {
        background: linear-gradient(135deg, var(--secondary), #0d9488);
        color: #fff;
        position: sticky;
        top: 0;
    }

    tr:nth-child(even) {
        background: #f8fafc
    }

    body.dark tr:nth-child(even) {
        background: #020617
    }

    tr:hover {
        background: var(--bg);
    }

    /* ===== Action Buttons ===== */
    .open-btn {
        background: linear-gradient(135deg, var(--success), #22c55e);
        color: #fff;
        padding: 7px 16px;
        border-radius: 10px;
        text-decoration: none;
    }

    .delete-btn {
        background: linear-gradient(135deg, var(--danger), #ef4444);
        color: #fff;
        padding: 7px 16px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
    }

    /* ===== Responsive ===== */
    @media(max-width:900px) {
        .sidebar {
            display: none
        }

        .search-bar input {
            width: 90%
        }
    }
</style>


<body>

    <header>

        <div>
            <button class="toggle-sidebar" onclick="toggleSidebar()">
                ⬅️ القائمة
            </button>

            <button class="theme-toggle" id="themeToggle">🌙</button>

        </div>

        مرحبا <?= $_SESSION['name'] ?> 👋
    </header>

    <div class="layout">

        <!-- ===== Sidebar ===== -->
        <aside class="sidebar hidden" id="sidebar">
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
                <a href="visits.php">زيارات اليوم</a>
                <a href="operation-by-date.php">مواعيد العمليات</a>
                <a href="import_expected.php">استيراد المواعيد</a>
                <a href="expected_appointments.php">المواعيد المتوقعة</a>

            </div>


            <div class="menu-group">
                <span>⚙️ النظام</span>
                <a href="reports.php">التقارير</a>
                <a href="common-medicines.php">الأدوية الأكثر استعمالًا</a>
                <a href="settings.php">الإعدادات</a>
                <a href="logout.php" class="danger">تسجيل الخروج</a>
            </div>
        </aside>

        <!-- ===== Content ===== -->
        <main class="content">

            <h1>عيادة الدكتور حيدر صباح الربيعي</h1>

            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="ابحث عن مريض" onkeyup="filterTable()">
            </div>

            <div class="table-scroll">
                <table id="patients_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>اسم المريض</th>
                            <th>الملاحظات</th>
                            <th>العمر</th>
                            <th>الجنس</th>
                            <th>الهاتف</th>
                            <th>العنوان</th>
                            <th>فتح</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = mysqli_query($con, "
SELECT add_patient.*,
surgery_appointment.status
FROM add_patient
LEFT JOIN surgery_appointment
ON surgery_appointment.id=(
  SELECT id FROM surgery_appointment
  WHERE patient_id=add_patient.id
  ORDER BY id DESC LIMIT 1
)
ORDER BY add_patient.id ASC
");

                        while ($r = mysqli_fetch_assoc($q)) {
                            $color = "";
                            if ($r['status'] == "done") $color = "green";
                            elseif ($r['status'] == "discharged") $color = "red";
                            elseif ($r['status'] == "pending") $color = "orange";
                        ?>
                            <tr>
                                <td><?= $r['id'] ?></td>
                                <td><a id="timeline" href="patient_timeline.php?id=<?= $r['id'] ?>" style="color:<?= $color ?>;font-weight:bold"><?= $r['full_name'] ?></a></td>
                                <td><span style="color:<?= $color ?>; font-weight:bold"><?= $r['notes'] ?></span></td>
                                <td><?= $r['age'] ?></td>
                                <td><?= $r['gender'] ?></td>
                                <td><?= $r['phone_no'] ?></td>
                                <td><?= $r['address'] ?></td>
                                <td><a class="open-btn" href="patient-data.php?id_open=<?= $r['id'] ?>">فتح</a></td>
                                <td><button class="delete-btn" onclick="confirmDelete(<?= $r['id'] ?>)">حذف</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script>
        function filterTable() {
            let f = document.getElementById("searchInput").value.toLowerCase();
            document.querySelectorAll("#patients_table tbody tr").forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(f) ? "" : "none";
            });
        }

        function confirmDelete(id) {
            if (confirm("هل أنت متأكد من الحذف؟")) {
                location.href = "delete-patient.php?id_delete=" + id;
            }
        }

        /* Sidebar Toggle */
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const btn = document.querySelector(".toggle-sidebar");

            sidebar.classList.toggle("hidden");

            if (sidebar.classList.contains("hidden")) {
                btn.innerHTML = "➡️ إظهار القائمة";
            } else {
                btn.innerHTML = "⬅️ إخفاء القائمة";
            }
        }

        /* Dark Mode */
        const t = document.getElementById("themeToggle");
        if (localStorage.getItem("theme") === "dark") {
            document.body.classList.add("dark");
            t.textContent = "☀️";
        }
        t.onclick = () => {
            document.body.classList.toggle("dark");
            let d = document.body.classList.contains("dark");
            t.textContent = d ? "☀️" : "🌙";
            localStorage.setItem("theme", d ? "dark" : "light");
        }
    </script>

</body>

</html>
