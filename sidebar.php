  
  <?php
  include "config.php";
  ?>
  
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
  </head>

<style>


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


</style>



  <body>
    


  
  <!-- ===== Sidebar ===== -->
        <aside class="sidebar hidden" id="sidebar">
            <h3>القائمة</h3>
            <div class="menu-group">

                <a href="dashboard.php">📊 لوحة التحكم</a>

            </div>
            <div class="menu-group">
                <span>📅 المواعيد</span>
                <a href="import_expected.php">استيراد المواعيد</a>
                <a href="expected_appointments.php">المواعيد المتوقعة</a>
                <a href="visits.php">زيارات اليوم</a>
                <a href="operation-by-date.php">مواعيد العمليات</a>
            </div>

            <div class="menu-group">
                <span>👤 المرضى</span>
                <a href="add-patient.php">إضافة مريض</a>
                <a href="confirmed-list.php">قوائم العمليات</a>
            </div>

            <div class="menu-group">
                <span>⚙️ النظام</span>
                <a href="settings.php">الإعدادات</a>
                <a href="logout.php" class="danger">تسجيل الخروج</a>
            </div>
        </aside>

<script>

    function toggleSidebar(){
  const sidebar = document.getElementById("sidebar");
  const btn = document.querySelector(".toggle-sidebar");

  sidebar.classList.toggle("hidden");

  const hidden = sidebar.classList.contains("hidden");
  localStorage.setItem("sidebar", hidden ? "hidden" : "show");

  btn.textContent = hidden ? "➡️ إظهار القائمة" : "⬅️ إخفاء القائمة";
}

</script>

  </body>
  </html>
  

  