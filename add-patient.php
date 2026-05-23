<?php
include "config.php";
include "auth.php";


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>إضافة مريض</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


  <!-- خطوط Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">


  <script>
    document.addEventListener("DOMContentLoaded", function() {
      if (localStorage.getItem("theme") === "dark") {
        document.body.setAttribute("data-theme", "dark");
      }
    });
  </script>

</head>

<style>
  :root {
    --bg-main: #f6f6ef;
    --bg-soft: #fffdf6;
    --text-main: #1f2d2a;
    --text-muted: #546764;
    --card-bg: rgba(255, 255, 255, 0.88);
    --card-border: rgba(126, 169, 126, 0.25);
    --nav-bg: rgba(240, 246, 232, 0.8);
    --input-bg: #fdfef9;
    --input-border: #c9d8c3;
    --input-focus: #2f8f63;
    --title-grad: linear-gradient(135deg, #2b6f6f, #3e9a6d 55%, #d4a84f);
    --btn-grad: linear-gradient(135deg, #1b8f63, #2eaa76);
    --btn-grad-hover: linear-gradient(135deg, #157552, #248a62);
    --shadow-soft: 0 12px 30px rgba(31, 45, 42, 0.12);
    --shadow-strong: 0 18px 38px rgba(31, 45, 42, 0.2);
  }

  body[data-theme="dark"] {
    --bg-main: #0c1716;
    --bg-soft: #121f1d;
    --text-main: #dcebe5;
    --text-muted: #9db5ae;
    --card-bg: rgba(15, 31, 29, 0.9);
    --card-border: rgba(110, 157, 141, 0.35);
    --nav-bg: rgba(18, 35, 33, 0.78);
    --input-bg: #102421;
    --input-border: #2e4d47;
    --input-focus: #47c08d;
    --title-grad: linear-gradient(135deg, #154141, #1f684f 55%, #987434);
    --btn-grad: linear-gradient(135deg, #1a9b6b, #35ba84);
    --btn-grad-hover: linear-gradient(135deg, #16855b, #2aa370);
    --shadow-soft: 0 14px 34px rgba(0, 0, 0, 0.32);
    --shadow-strong: 0 22px 46px rgba(0, 0, 0, 0.38);
  }

  body {
    margin: 0;
    font-family: 'Cairo', sans-serif;
    color: var(--text-main);
    background:
      radial-gradient(circle at 90% 10%, rgba(212, 168, 79, 0.2), transparent 30%),
      radial-gradient(circle at 10% 0%, rgba(62, 154, 109, 0.18), transparent 28%),
      linear-gradient(180deg, var(--bg-main), var(--bg-soft));
    min-height: 100vh;
  }

  header h1 {
    margin: 0;
    padding: 22px 18px;
    font-size: clamp(22px, 4vw, 34px);
    text-align: center;
    color: #fefefe;
    background: var(--title-grad);
    border-bottom-left-radius: 24px;
    border-bottom-right-radius: 24px;
    box-shadow: var(--shadow-strong);
    letter-spacing: 0.3px;
  }

  .sidenav {
    max-width: 1100px;
    margin: 26px auto 0;
    padding: 10px;
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    background: var(--nav-bg);
    border: 1px solid var(--card-border);
    border-radius: 18px;
    box-shadow: var(--shadow-soft);
    backdrop-filter: blur(8px);
  }

  .sidenav a {
    text-decoration: none;
    color: var(--text-main);
    font-weight: 700;
    padding: 10px 16px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.5);
    transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
  }

  .sidenav a:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.84);
    border-color: rgba(62, 154, 109, 0.35);
  }

  h2 {
    text-align: center;
    margin: 34px 0 18px;
    color: var(--text-main);
    font-size: clamp(24px, 3.6vw, 31px);
  }

  .add-patient {
    max-width: 860px;
    margin: 0 auto 48px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 24px;
    padding: 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 20px;
    box-shadow: var(--shadow-strong);
    backdrop-filter: blur(6px);
  }

  .patient-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .patient-info:last-of-type {
    grid-column: 1 / -1;
  }

  .patient-info label {
    font-weight: 700;
    color: var(--text-muted);
    font-size: 14px;
  }

  .patient-info input,
  .patient-info select,
  .patient-info textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    color: var(--text-main);
    border-radius: 12px;
    padding: 11px 12px;
    font-size: 15px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
  }

  .patient-info textarea {
    min-height: 100px;
    resize: vertical;
  }

  .patient-info input:focus,
  .patient-info select:focus,
  .patient-info textarea:focus {
    outline: none;
    border-color: var(--input-focus);
    box-shadow: 0 0 0 4px rgba(47, 143, 99, 0.15);
    transform: translateY(-1px);
  }

  #add-patient-btn {
    grid-column: 1 / -1;
    margin-top: 8px;
    border: none;
    border-radius: 14px;
    padding: 14px;
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
    background: var(--btn-grad);
    cursor: pointer;
    box-shadow: 0 12px 24px rgba(27, 143, 99, 0.25);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  }

  #add-patient-btn:hover {
    transform: translateY(-2px);
    background: var(--btn-grad-hover);
    box-shadow: 0 16px 28px rgba(27, 143, 99, 0.32);
  }

  @media (max-width: 760px) {
    .add-patient {
      grid-template-columns: 1fr;
      margin: 0 12px 34px;
      padding: 18px;
      border-radius: 18px;
    }

    .patient-info:last-of-type {
      grid-column: auto;
    }

    .sidenav {
      margin: 18px 10px 0;
      border-radius: 14px;
    }

    .sidenav a {
      width: calc(50% - 10px);
      text-align: center;
      padding: 9px 10px;
      font-size: 14px;
    }
  }

  @media (max-width: 480px) {
    .sidenav a {
      width: 100%;
    }
  }
</style>


<body>
  <header>
    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
  </header>

  <div class="sidenav">
    <a href="dashboard.php">الصفحة الرئيسية</a>
    <a href="visits.php">زيارات اليوم</a>
    <a href="appointment.php">المواعيد</a>
    <a href="vouchers.php">الفواتير</a>
    <a href="reports.php">التقارير</a>
    <a href="logout.php">تسجيل الخروج</a>
  </div>

  <h2>إضافة مريض جديد</h2>

  <form class="add-patient" action="add-patient2.php" method="post">
    <div class="patient-info">
      <label for="full_name">الاسم الرباعي</label>
      <input type="text" id="full_name" name="full_name" required>
    </div>

    <div class="patient-info">
      <label for="age">العمر</label>
      <input type="text" id="age" name="age" required>
    </div>

    <div class="patient-info">
      <label for="date_of_birth">تاريخ الميلاد</label>
      <input type="date" id="date_of_birth" name="date_of_birth">
    </div>

    <div class="patient-info">
      <label for="gender">الجنس</label>
      <select id="gender" name="gender">
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
      </select>
    </div>

    <div class="patient-info">
      <label for="phone_no">الموبايل</label>
      <input type="text" id="phone_no" name="phone_no" pattern="[0-9]+" placeholder="07xxxxxxxxx">
    </div>


    <div class="patient-info">
      <label for="phone_no_alt">موبايل بديل</label>
      <input type="text" id="phone_no_alt" name="phone_no_alt">
    </div>


    <div class="patient-info">
      <label for="address">العنوان</label>
      <input type="text" id="address" name="address">
    </div>

    <div class="patient-info">
      <label for="notes">الملاحظات</label>
      <textarea id="notes" name="notes"></textarea>
    </div>

    <button id="add-patient-btn" type="submit" name="submit"> 🧑‍⚕️ إضافة المريض</button>
  </form>
</body>

</html>