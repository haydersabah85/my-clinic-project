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


  <script src="assets/js/theme.js"></script>

</head>

<style>
  /* ======= DARK MODE ======= */

  body[data-theme="dark"] {
    background: #121212;
    color: #e0e0e0;
  }

  body[data-theme="dark"] .add-patient {
    background: #1e1e1e;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.7);
  }

  body[data-theme="dark"] header h1 {
    background-color: #b73232;
    color: #ffd700;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.7);
  }

  body[data-theme="dark"] .sidenav a {
    background: #3b8552;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
  }

  body[data-theme="dark"] .sidenav a:hover {
    background: #2e6b3f;
  }

  body[data-theme="dark"] .patient-info input,
  body[data-theme="dark"] .patient-info select,
  body[data-theme="dark"] .patient-info textarea {
    background: #2c2c2c;
    border: 1px solid #555;
    color: #e0e0e0;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.5);
  }

  body[data-theme="dark"] .patient-info input:focus,
  body[data-theme="dark"] .patient-info select:focus,
  body[data-theme="dark"] .patient-info textarea:focus {
    border-color: #3b8552;
    box-shadow: 0 0 5px rgba(59, 133, 82, 0.7);
  }

  body[data-theme="dark"] #add-patient-btn {
    background: #3b8552;
  }

  body[data-theme="dark"] #add-patient-btn:hover {
    background: #2e6b3f;
  }




  /* الخط العام */
  body {
    font-family: 'Cairo', sans-serif;
    margin: 0;
    background: #f0f4f8;
    color: #333;
  }

  /* الرأس */
  header h1 {
    background-color: #ffd700;
    color: #b73232;
    text-align: center;
    padding: 20px 0;
    margin: 0;
    font-size: 30px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    border-bottom-left-radius: 15px;
    border-bottom-right-radius: 15px;
  }

  /* القائمة العلوية */
  .sidenav {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin: 20px;
  }

  .sidenav a {
    background: #4aa96c;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: bold;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
  }

  .sidenav a:hover {
    background: #3b8552;
    transform: scale(1.05);
  }

  /* العنوان الفرعي */
  h2 {
    text-align: center;
    color: #b73232;
    margin-top: 30px;
    margin-bottom: 20px;
    font-size: 26px;
  }

  /* النموذج */
  .add-patient {
    background: #ffffff;
    max-width: 700px;
    margin: 0 auto 50px auto;
    padding: 30px 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* حقول النموذج */
  .patient-info {
    display: flex;
    flex-direction: column;
  }

  .patient-info label {
    font-weight: bold;
    margin-bottom: 8px;
    color: #4a4a4a;

  }

  .patient-info input,
  .patient-info select,
  .patient-info textarea {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 16px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    width: 95%;
    resize: vertical;
    transition: all 0.3s ease;
  }

  .patient-info input:focus,
  .patient-info select:focus,
  .patient-info textarea:focus {
    border-color: #4aa96c;
    box-shadow: 0 0 5px rgba(74, 169, 108, 0.5);
    outline: none;
  }

  .patient-info textarea {
    min-height: 80px;
  }

  /* زر الإضافة */
  #add-patient-btn {
    background: #4aa96c;
    color: white;
    border: none;
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  #add-patient-btn:hover {
    background: #3b8552;
    transform: scale(1.05);
  }

  /* Responsive */
  @media (max-width: 600px) {
    .sidenav {
      flex-direction: column;
      align-items: center;
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