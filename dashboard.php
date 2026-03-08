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
if ($critical > 0) $alerts[] = "🚨 يوجد $critical حالات حرجة  <br>";

// عمليات متأخرة
$late = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date < CURDATE()
"));
if ($late > 0) $alerts[] = "🔴 يوجد $late عملية متأخرة عن موعدها";

// عمليات قريبة
$soon = mysqli_num_rows(mysqli_query($con, "
  SELECT id FROM surgery_appointment 
  WHERE status='pending' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 5 DAY)
"));
if ($soon > 0) $alerts[] = "⚠️ يوجد $soon عمليات خلال 5 أيام";

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

  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
  
  }

  .card {
    background: var(--card);
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
    margin-bottom: 25px;
    width: 70%;
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
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
  }



  /* ===== Alerts ===== */
  .alerts {
    margin-bottom: 25px;
  }

  .alert {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff;
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 10px;
    font-weight: 700;
    box-shadow: var(--shadow);
    width: fit-content;
  }
  .danger-card {
    display: inline-block;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 10px;
    font-weight: 700;
    box-shadow: var(--shadow);
    text-decoration: none;
  }
  /* ===== Quick Actions ===== */
  .quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
  }

  .qa {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    text-decoration: none;
    padding: 22px;
    border-radius: 18px;
    font-size: 18px;
    font-weight: 800;
    text-align: center;
    box-shadow: var(--shadow);
    transition: .3s;
  }

  .qa:hover {
    transform: translateY(-4px) scale(1.03);
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
       
</style>



<body>
<header>

        <div>
            

            <button class="theme-toggle" id="themeToggle">🌙</button>

        </div>

        مرحبا <?= $_SESSION['name'] ?> 👋
    </header>



  <!-- إذا تحب أو انسخ السايد بار مباشرة -->

  <main class="content">

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
      <h3>🚨 تنبيهات مهمة</h3>
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

    <!-- ===== Quick Actions ===== -->
    <div class="quick-actions">

      <a href="add-patient.php" class="qa">➕ إضافة مريض</a>
      <a href="import_expected.php" class="qa">📥 استيراد مواعيد</a>
      <a href="visits.php" class="qa">📅 زيارات اليوم</a>
      <a href="main.php" class="qa">  الصفحة الرئيسية</a>

    </div>

  </main>


  <script>

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

  </script>
</body>

</html>