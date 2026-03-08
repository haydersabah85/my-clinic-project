<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>عيادة الدكتور حيدر صباح الربيعي</title>
</head>

<style>
  body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #cfeef8;
  }

  center {

    font-size: 36px;
    font-weight: bold;
    color: #b24433;

  }

  .container {

    background: #962c2c;
    background: linear-gradient(90deg, #962c2c 0%, #f45c43 100%);
    padding: 20px;
    border-radius: 10px;
    margin: 20px auto;
    max-width: 75%;
    position: relative;
    font-size: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;



  }

  .info {
    background: rgb(201, 211, 186);
    direction: rtl;
    margin-right: 30px;
    margin-bottom: 50px;
    margin-top: 50px;
    padding: 20px;
    border-radius: 8px;
    width: 40%;
    display: flex;
    flex-direction: column;
    gap: 10px;
    color: #333;
    font-size: 20px;
    text-align: right;


  }

  .info p {

    margin: 5px 0;
    text-align: right;

  }

  nav {
    margin: 20px 45px;
    padding: 10px;
    border-radius: 8px;
    height: fit-content;

  }

  nav ul {
    list-style-type: none;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    text-align: center;

  }

  nav ul li:hover {
    background: orange;
    cursor: pointer;
    transform: scale(1.1);
  }

  nav ul li {
    background: bisque;

    padding: 8px;
    border-radius: 5px;


  }

  nav ul li a {
    text-decoration: none;
    color: blue;
  }

  .visit_type {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 20px;
    
  }

  .visit_type a {
    padding: 10px 20px;
    border-radius: 5px;
    color: white;
    text-decoration: none;
    font-size: 20px;
    font-weight: bold;
  }

  .visit_type a:hover {
    opacity: 0.8;
    transform: scale(1.1);
  }

  #a {
    background: yellowgreen;

  }

  #b {
    background: #49cda7ff;
  }

  #c {
    background: #92034dff;
  }
</style>

<body>
  <center>بيانات المريض</center>
  <div class="container">
   
      <nav>
        <ul>
          <li><a href="main.php">الصفحة الرئيسية</a></li>
          <li><a href="patient-file.php?id=<?php echo $row['id']; ?>">الزيارات</a></li>
          <li><a href="add-va.php?id=<?php echo $row['id']; ?>">اضافة فحص النظر</a></li>
          <li><a href="add-operation.php?id=<?php echo $row['id']; ?>">اضافة عملية</a></li>
          <li><a href="add-photo.html">اضافة صور</a></li>
          <li><a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a></li>
        </ul>
      </nav>


      <div class="info">

        <p><span>الرقم التسلسلي:</span>
          <span><?php echo htmlspecialchars($row['id']); ?></span>
        </p>

        <p><span>الاسم:</span>
          <span><?php echo htmlspecialchars($row['full_name']); ?></span>
        </p>

        <p><span>العمر:</span>
          <span><?php echo htmlspecialchars($row['age']); ?></span>
        </p>

        <p><span>الجنس:</span>
          <span><?php echo htmlspecialchars($row['gender']); ?></span>
        </p>

        <p><span>الموبايل:</span>
          <span><?php echo htmlspecialchars($row['phone_no']); ?></span>
        </p>

        <p><span>العنوان:</span>
          <span><?php echo htmlspecialchars($row['address']); ?></span>
        </p>

      </div>
   
    <div class="visit_type">
      <a id="a" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=first"> زيارة اول مرة </a>
      <a id="b" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=repeat"> زيارة متكررة </a>
      <a id="c" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=free">زيارة مراجعة </a>
    </div>
  </div>
</body>

</html>