<?php

include 'auto_backup.php';

include 'auth.php';
?>



<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>عيادة الدكتور حيدر صباح الربيعي</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="assets/script.js" defer></script>

  <style>
   
/* ===== Root Variables (Modern Palette) ===== */
:root {
  --primary: #2563eb;      /* Blue */
  --secondary: #0f766e;    /* Teal */
  --accent: #f59e0b;       /* Amber */
  --danger: #dc2626;
  --success: #16a34a;
  --bg: #f1f5f9;
  --card: #ffffff;
  --text: #1e293b;
  --muted: #64748b;
  --radius: 14px;
  --shadow: 0 10px 25px rgba(0,0,0,0.08);
}

header {
  background-color: var(--primary);
  color: #fff;
  padding: 15px 20px;
  text-align: right;
  font-size: 18px;
  font-weight: 600;
}
/* ===== Global ===== */
body {
  font-family: "Segoe UI", Tahoma, Arial, sans-serif;
  margin: 0;
  background: linear-gradient(180deg, #eef2ff, var(--bg));
  color: var(--text);
}

/* ===== Page Title ===== */
h1 {
  font-size: 34px;
  text-align: center;
  margin: 25px 0;
  color: #b24433;
  font-weight: 800;
  letter-spacing: 0.5px;
}

/* ===== Navigation / Shortcuts ===== */
.sidenav {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 14px;
  margin: 20px;
}

.sidenav a {
  background: linear-gradient(135deg, var(--primary), #4f46e5);
  color: #fff;
  padding: 10px 22px;
  border-radius: var(--radius);
  text-decoration: none;
  font-weight: 700;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.sidenav a::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(255,255,255,0.15);
  opacity: 0;
  transition: 0.3s;
}

.sidenav a:hover::after {
  opacity: 1;
}

.sidenav a:hover {
  transform: translateY(-3px) scale(1.05);
}
#import {
  background: linear-gradient(135deg, #f97316, #ea580c);

}
/* ===== Search Bar ===== */
   /* Search Bar */
    .search-bar {
      background: #c68f8f;
      margin: 20px auto;
      padding: 15px;
      border-radius: 10px;
      width: 80%;
      max-width: 800px;
      text-align: center;
      box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
    }

    .search-bar input {
      width: 60%;
      padding: 8px 10px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
/* ===== Table Container ===== */
.table-scroll {
  max-height: 65vh;
  overflow-y: auto;
  margin: 15px auto 50px;
  width: 96%;
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

/* ===== Table ===== */
table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 14px 12px;
  text-align: center;
  font-size: 16px;
}

th {
  background: linear-gradient(135deg, var(--secondary), #0d9488);
  color: #fff;
  font-weight: 800;
  position: sticky;
  top: 0;
  z-index: 2;
}

tr {
  transition: 0.25s;
}

tr:nth-child(even) {
  background: #f8fafc;
}

tr:hover {
  background: #e0f2fe;
  transform: scale(1.01);
}

/* ===== Buttons ===== */
.open-btn {
  background: linear-gradient(135deg, var(--success), #22c55e);
  color: #fff;
  padding: 7px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  transition: 0.3s;
  display: inline-block;
}

.open-btn:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 18px rgba(22,163,74,0.4);
}

.delete-btn {
  background: linear-gradient(135deg, var(--danger), #ef4444);
  color: #fff;
  padding: 7px 16px;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.3s;
}

.delete-btn:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 18px rgba(220,38,38,0.4);
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  th, td {
    font-size: 14px;
    padding: 10px;
  }

  .search-bar input {
    width: 85%;
  }
}

@media (max-width: 768px) {
  table, thead, tbody, th, td, tr {
    display: block;
  }

  thead {
    display: none;
  }

  tr {
    margin: 12px 0;
    border-radius: var(--radius);
    padding: 12px;
    background: var(--card);
    box-shadow: var(--shadow);
  }

  td {
    text-align: right;
    padding: 10px 10px 10px 50%;
    position: relative;
  }

  td::before {
    content: attr(data-label);
    position: absolute;
    left: 14px;
    font-weight: 700;
    color: var(--muted);
  }

  .table-scroll {
    max-height: none;
  }
}
/* ===== Dark Mode Variables ===== */
body.dark {
  --bg: #020617;
  --card: #0f172a;
  --text: #e5e7eb;
  --muted: #94a3b8;

  --primary: #60a5fa;
  --secondary: #2dd4bf;
  --accent: #fbbf24;
}

/* ===== Dark Background ===== */
body.dark {
  background: linear-gradient(180deg, #020617, #020617);
}

/* ===== Dark Table ===== */
body.dark tr:nth-child(even) {
  background: #020617;
}

body.dark tr:hover {
  background: #1e293b;
}

/* ===== Dark Search Input ===== */
body.dark .search-bar {
  background: #1e293b;
}
body.dark .search-bar input {
  background: #020617;
  color: #e5e7eb;
  border-color: #334155;
}
body.dark .search-bar input::placeholder {
  color: #94a3b8;
}

/* ===== Toggle Button ===== */
.theme-toggle {
  position: fixed;
  top: 60px;
  right: 20px;
  z-index: 999;
  border: none;
  padding: 10px 14px;
  border-radius: 50%;
  font-size: 20px;
  cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--secondary));
  color: #fff;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  transition: 0.3s;
}

.theme-toggle:hover {
  transform: rotate(20deg) scale(1.1);
}

/* ===== Animations ===== */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

h1,
.sidenav a,
.search-bar,
.table-scroll {
  animation: fadeUp 0.6s ease both;
}

tr {
  animation: fadeUp 0.4s ease both;
}

/* Hover pulse for buttons */
.open-btn:hover,
.delete-btn:hover {
  animation: pulse 0.4s ease;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.08); }
  100% { transform: scale(1); }
}


  </style>
</head>
<header>
  مرحبا <?= $_SESSION['name'] ?> 👋

</header>

<body>
<button id="themeToggle" class="theme-toggle">🌙</button>

  <h1>عيادة الدكتور حيدر صباح الربيعي</h1>

  <div class="sidenav">

    <a id="import" href="import_expected.php">استيراد المواعيد المتوقعة</a>
  <a href="expected_appointments.php">المواعيد المتوقعة</a>
    <a href="visits.php">زيارات اليوم</a>
    <a href="add-patient.php">إضافة مريض جديد</a>
    <a href="confirmed-list.php">قوائم العمليات</a>
    <a href="operation-by-date.php">مواعيد العمليات</a>
    <a href="settings.php">الإعدادات</a>
    <a href="logout.php">تسجيل الخروج</a>
  </div>

  <div class="search-bar">
    <input type="text" id="searchInput" placeholder="ابحث عن مريض بالاسم أو الرقم التسلسلي" onkeyup="filterTable()">
  </div>

  <div class="table-scroll">
    <table id="patients_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>اسم المريض</th>
          <th>العمر</th>
          <th>الجنس</th>
          <th>رقم الهاتف</th>
          <th>العنوان</th>
          <th>فتح الملف</th>
          <th>حذف البيانات</th>
        </tr>
      </thead>
      <tbody>
        <?php
        include 'config.php';
        $select_query = "
SELECT 
add_patient.id,
add_patient.full_name,
add_patient.age,
add_patient.gender,
add_patient.phone_no,
add_patient.address,
surgery_appointment.id AS surgery_id,
surgery_appointment.status
FROM add_patient
LEFT JOIN surgery_appointment ON surgery_appointment.id = (
    SELECT id FROM surgery_appointment 
    WHERE surgery_appointment.patient_id = add_patient.id 
    ORDER BY surgery_appointment.id DESC 
    LIMIT 1
)

ORDER BY add_patient.id ASC";
        $result = mysqli_query($con, $select_query);
        while ($row = mysqli_fetch_assoc($result)) {
          // Determine row color based on surgery status

          $color = "";
          if ($row['status'] == 'done') {
            $color = "green";
          } elseif ($row['status'] == 'discharged') {
            $color = "red";
          } elseif ($row['status'] == 'pending') {
            $color = "orange";
          }

        ?>
          <tr>
            <td data-label="ID"><?= $row['id']; ?></td>
            <td data-label="اسم المريض" style="color:<?= $color; ?>; font-weight:bold;"><?= $row['full_name']; ?></td>
            <td data-label="العمر"><?= $row['age']; ?></td>
            <td data-label="الجنس"><?= $row['gender']; ?></td>
            <td data-label="رقم الهاتف"><?= $row['phone_no']; ?></td>
            <td data-label="العنوان"><?= $row['address']; ?></td>
            <td data-label="فتح الملف"><a class="open-btn" href="patient-data.php?id_open=<?= $row['id']; ?>">فتح الملف</a></td>
            <td data-label="حذف البيانات"><button class="delete-btn" onclick="confirmDelete(<?= $row['id']; ?>)">حذف</button></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

  <script>
    function filterTable() {
      const input = document.getElementById("searchInput");
      const filter = input.value.toLowerCase();
      const table = document.getElementById("patients_table");
      const tr = table.getElementsByTagName("tr");

      for (let i = 1; i < tr.length; i++) {
        const rowText = tr[i].textContent.toLowerCase();
        tr[i].style.display = rowText.indexOf(filter) > -1 ? "" : "none";
      }
    }

    function confirmDelete(id) {
      if (confirm("هل أنت متأكد من حذف بيانات هذا المريض؟")) {
        window.location.href = "delete-patient.php?id_delete=" + id;
      }
    }
  </script>

<script>
  const toggle = document.getElementById("themeToggle");

  // Load saved mode
  if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark");
    toggle.textContent = "☀️";
  }

  toggle.addEventListener("click", () => {
    document.body.classList.toggle("dark");

    const isDark = document.body.classList.contains("dark");
    toggle.textContent = isDark ? "☀️" : "🌙";
    localStorage.setItem("theme", isDark ? "dark" : "light");
  });
</script>


</body>

</html>