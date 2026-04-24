<?php

include "config.php";
include "auth.php";


/* ===== إحصائيات ===== */

// إجمالي المرضى
$totalPatients = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM add_patient")
)['total'];

// زيارات اليوم
$todayVisits = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE()")
)['total'] ?? 0;



// مراجعات اليوم

$followups = mysqli_fetch_assoc(
  mysqli_query($con, "SELECT COUNT(*) total FROM followups WHERE followup_date = CURDATE()")
)['total'] ?? 0;

// عمليات هذا الشهر
$monthOperations = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM surgery
    WHERE MONTH(date)=MONTH(CURDATE())
    AND YEAR(date)=YEAR(CURDATE()) 
  ")
)['total'] ?? 0;

// عمليات قادمة
$pendingOperations = mysqli_fetch_assoc(
  mysqli_query($con, "
    SELECT COUNT(*) total FROM surgery_appointment 
    WHERE status='pending' AND date >= CURDATE()
  ")
)['total'] ?? 0;

/* ===== جدول العمليات القادمة ===== */
$upcoming = mysqli_query($con, "
  SELECT 
    add_patient.full_name,
    surgery_appointment.patient_id,
    surgery_appointment.date,
    surgery_appointment.eye,
    surgery_appointment.surgery_type,
    surgery_appointment.id,
    surgery_appointment.status
  FROM surgery_appointment
  JOIN add_patient ON add_patient.id = surgery_appointment.patient_id
  WHERE surgery_appointment.status='pending' AND surgery_appointment.date >= CURDATE()
  ORDER BY surgery_appointment.date ASC
  LIMIT 5
");

/* ===== تنبيهات ===== */
$alerts = [];
// حالات حرجة 
$critical = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM add_patient 
  WHERE is_critical=1 
"));
if ($critical > 0) $alerts[] = "<div class='alert alert-danger'>🚨 يوجد $critical حالات حرجة</div>";

// عمليات متأخرة
$late = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date < CURDATE()
"));
if ($late > 0) // متأخرة
  $alerts[] = "<div class='alert alert-danger'>🔴 يوجد $late عملية متأخرة</div>";

// عمليات قريبة
$soon = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 5 DAY)
"));
if ($soon > 0) // قريبة
  $alerts[] = "<div class='alert alert-warning'>⚠️ يوجد $soon عمليات خلال 5 أيام</div>";

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- نستخدم نفس CSS تبعك -->

</head>

<style>
  :root {
    --primary: #2563eb;
    --secondary: #0f766e;
    --accent: #f59e0b;
    --danger: #dc2626;
    --success: #16a34a;
    --bg: #f1f5f9;
    --card: #ffffffff;
    --text: #1e293b;
    --muted: #f87171;
    --box: #e2e8f0;
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
    --box: #334155;
  }

  /* ===== Dashboard ===== */

  /* ===== Layout ===== */
  .content {
    max-width: 1200px;
    margin: 10px auto;
  }

  /* ===== Header ===== */
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--card);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    border-radius: var(--radius);
    color: var(--text);
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


  /* ===== Search ===== */
  .search-box {
    margin-bottom: 20px;
    text-align: center;
  }

  .search-box input {
    width: 50%;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #88c8d8;
    font-size: 16px;
    outline: none;
    transition: .3s;
  }

  .search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 8px rgba(37, 99, 235, .3);
  }

  /* نتائج البحث */
  #results {
    background: var(--card);
    border-radius: var(--radius);
    margin-top: 10px;
    box-shadow: var(--shadow);
    text-align: center;
    width: 35%;
    margin: auto;

  }


  .result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f4f4f4;
    margin-bottom: 8px;
    border-radius: 10px;
  }

  .result-item span {
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
  }

  .result-item span:hover {
    color: var(--primary);
  }

  .delete-btn {
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
  }

  .delete-btn:hover {
    transform: scale(1.3) rotate(-10deg);
  }

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;


  }

  .card {
    background: linear-gradient(135deg, var(--card), #f8fafc);
    backdrop-filter: blur(6px);
    box-shadow: var(--shadow);
    border-radius: var(--radius);
    padding: 20px;
    text-align: center;
    transition: .3s;
  }

  .card:hover {
    transform: translateY(-5px);
  }

  .card span {
    display: block;
    font-size: 32px;
    font-weight: 800;
    margin: 10px 0;
    color: var(--primary);
  }

  .card p {
    font-weight: 700;
    color: var(--muted);
  }

  /* ===== Box ===== */
  .box {
    background: var(--box);
    padding: 20px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin: 20px 0;
    transition: .3s;

  }

  .box h3 {
    margin-bottom: 15px;
    color: var(--text);
  }


  /* ===== Table ===== */
  .table-scroll {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    max-height: 65vh;
    overflow: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    border-radius: var(--radius);
    overflow: hidden;

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
    border-radius: 5px;
  }

  td {
    border-bottom: 1px solid #e2e8f0;
    color: var(--text);
  }

  .open-btn {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    padding: 8px 14px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    box-shadow: var(--shadow);
    transition: .3s;
  }

  tr:nth-child(even) {
    background: #f8fafc
  }


  body.dark tr:nth-child(even) {
    background: #020617
  }

  tr:hover {
    transform: scale(1.01);
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
  }


  /* ===== Alerts Container ===== */
  .alerts {
    margin: 20px 0;
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    gap: 15px;
  }

  /* ===== Base Alert ===== */
  .alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: var(--shadow);
    background: var(--card);
    border-right: 5px solid;
    transition: .3s;
    animation: fadeIn 0.4s ease;
    width: fit-content;
  }



  .alert:hover {
    transform: translateY(-2px);
  }

  /* ===== أنواع التنبيهات ===== */

  /* 🔴 خطر */
  .alert-danger {
    border-color: #dc2626;
    background: rgba(220, 38, 38, 0.08);
    color: #b91c1c;
  }

  /* ⚠️ تحذير */
  .alert-warning {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
  }

  /* 🟢 نجاح */
  .alert-success {
    border-color: #16a34a;
    background: rgba(22, 163, 74, 0.1);
    color: #065f46;
  }

  /* 🔵 معلومات */
  .alert-info {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.1);
    color: #1e3a8a;
  }

  /* أيقونة */
  .alert i {
    font-size: 18px;
  }

  /* ===== زر الحالات الحرجة ===== */
  .danger-card {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(220, 38, 38, 0.1);
    color: #b91c1c;
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 700;
    border: 2px solid #dc2626;
    text-decoration: none;
    transition: .3s;
  }

  .danger-card:hover {
    background: #dc2626;
    color: #fff;
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



  @media (max-width: 1024px) {
    .search-box input {
      width: 70%;
    }
  }

  @media (max-width: 768px) {
    .search-box input {
      width: 90%;
    }

    .cards-grid {
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
  }

  @media (max-width: 500px) {
    header {
      flex-direction: column;
      gap: 15px;
    }

    .layout {
      flex-direction: column;
    }

    .sidebar {
      width: 100%;
    }
  }

  @media (max-width: 400px) {
    .toggle-sidebar {
      padding: 6px 12px;
      font-size: 14px;
    }

    .theme-toggle {
      padding: 6px 10px;
      font-size: 16px;
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
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
        <a href="add-patient.php">➕ إضافة مريض</a>
        <a href="main.php">👥 بيانات المرضى</a>
        <a href="followups.php">🔄 المتابعة</a>
      </div>


      <div class="menu-group">
        <span>📅 المواعيد</span>
        <a href="visits.php">📅 زيارات اليوم</a>
        <a href="import_expected.php">📥 استيراد المواعيد</a>
        <a href="expected_appointments.php">📅 المواعيد المتوقعة</a>

      </div>

      <div class="menu-group">
        <span>💉 العمليات</span>
        <a href="operation-by-date.php">🏥 مواعيد العمليات</a>
        <a href="confirmed-list.php">📋 قوائم العمليات</a>
        <a href="import_surgery_excel.php">📥 استيراد العمليات</a>
      </div>


      <div class="menu-group">
        <span>⚙️ النظام</span>
        <a href="reports.php">📊 التقارير</a>
        <a href="common-medicines.php">💊 الأدوية الأكثر استعمالًا</a>
        <a href="settings.php">⚙️ الإعدادات</a>
        <a href="logout.php" class="danger">🚪 تسجيل الخروج</a>
      </div>
    </aside>

    <main class="content">

      <div class="search-box">
        <input type="text" id="search" placeholder="🔍 ابحث عن مريض..." onkeyup="searchPatient()">
      </div>

      <div id="results"></div>



      <h1>لوحة التحكم</h1>

      <!-- ===== Cards ===== -->
      <div class="cards-grid">

        <div class="card">👥<span><?= $totalPatients ?></span>
          <p>إجمالي المرضى</p>
        </div>

        <div class="card">📅<span><?= $todayVisits ?></span>
          <p>زيارات اليوم</p>
        </div>

        <div class="card">📲<span><?= $followups ?></span>
          <p>مراجعات اليوم</p>
        </div>

        <div class="card">🏥<span><?= $monthOperations ?></span>
          <p>العمليات المنجزة هذا الشهر</p>
        </div>

        <div class="card">⏳<span><?= $pendingOperations ?></span>
          <p>العمليات القادمة</p>
        </div>

      </div>

      <!-- ===== Upcoming Operations ===== -->
      <section class="box">
        <h3>المرضى الذين لديهم عمليات قادمة</h3>

        <table>
          <tr>
            <th>الاسم</th>
            <th>التاريخ</th>
            <th>العملية</th>
            <th>العين</th>
            <th>فتح</th>
          </tr>

          <?php while ($r = mysqli_fetch_assoc($upcoming)) { ?>
            <tr>
              <td><?= $r['full_name'] ?></td>
              <td><?= $r['date'] ?></td>
              <td><?= $r['surgery_type'] ?></td>
              <td><?= $r['eye'] ?></td>
              <td><a class="open-btn" href="patient-file.php?id_open=<?= $r['patient_id'] ?>">فتح</a></td>
            </tr>
          <?php } ?>

        </table>
        <a href="operation-by-date.php">عرض الكل →</a>

      </section>

      <!-- ===== Alerts ===== -->
      <section class="alerts">



        <a href="critical_patients.php" class="danger-card">
          🚨 المرضى الحرِجون


        </a>

        <?php if (empty($alerts)) { ?>
          <p>لا توجد تنبيهات حالياً</p>
        <?php } ?>

        <?php foreach ($alerts as $a) { ?>
          <div class="alert"><?= $a ?></div>
        <?php } ?>

      </section>

    </main>

  </div>


  <script>
    /* Sidebar Toggle */
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const btn = document.querySelector(".toggle-sidebar");

      sidebar.classList.toggle("hidden");

      if (sidebar.classList.contains("hidden")) {
        btn.innerHTML = " ⬅️إظهار القائمة";
      } else {
        btn.innerHTML = " ➡️إخفاء القائمة";
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
    };


    function searchPatient() {
      let q = document.getElementById("search").value;

      if (q.length < 2) {
        document.getElementById("results").innerHTML = "";
        return;
      }

      fetch("search-patient.php?q=" + q)
        .then(res => res.text())
        .then(data => {
          document.getElementById("results").innerHTML = data;
        });
    }

    function deletePatient(id) {

      if (confirm("⚠️ هل أنت متأكد من حذف هذا المريض؟")) {

        fetch("delete-patient.php?id_delete=" + id)
          .then(res => res.text())
          .then(data => {
            alert("تم الحذف بنجاح");

            // إعادة البحث لتحديث النتائج
            document.getElementById("search").dispatchEvent(new Event('keyup'));
          });
      }
    }
  </script>

  <script>
    function syncData() {
      fetch('sync_to_online.php')
        .then(response => response.text())
        .then(data => console.log(data))
        .catch(err => console.log("Sync error:", err));
    }

    // أول تشغيل عند فتح الصفحة
    syncData();

    // تشغيل كل 3 دقائق
    setInterval(syncData, 180000);
  </script>


</body>

</html>