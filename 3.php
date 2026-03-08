<!DOCTYPE html>
<html lang="en" dir="auto">

<head>
  <meta charset="UTF-8" />
  <title>عيادة الدكتور حيدر صباح الربيعي</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <style>
    h1 {
      font-size: 28px;
      text-align: center;
      color: rgb(183, 50, 50);
      margin: 10px 20px 10px 20px;
    }

    .sidenav {
      height: 100%;
      width: 100%;
      position: relative;
      top: 0;
      left: 0;
      background-color: #f1f1f1;
      overflow-x: hidden;
      padding: 20px 0;
      text-align: center;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
      border-radius: 10px;
      margin: 10px;
    
    }

    .sidenav a {
      
     display: inline;
      color: rgb(161, 29, 222);
      text-decoration: none;
      padding: 4px 10px 4px 10px;
      font-size: 18px;
      line-height: 25px;
      border-radius: 10px;
      background-color: rgb(215, 197, 136);
      margin: 4px;
      position: relative;
      transition: 0.3s;
      font-weight: bold;
      font-family: Arial, Helvetica, sans-serif;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
      display: inline-block;
    }

    .sidenav a:hover {
      background-color: #ddd;
      color: black;
      transition: 1s;
      transform: scale(1.1);
    }
  </style>
</head>

<header>
  <h1>أهلا وسهلا بك في عيادة الدكتور حيدر صباح الربيعي</h1>
</header>

<body>
  <div class="sidenav">
    <a href="main.php">الصفحة الرئيسية</a>
    <a href="visits.php">زيارات اليوم</a>
    <a href="add-patient.html">أضافة مريض جديد</a>
    <a href="appointment.php">المواعيد</a>
    <a href="operation-by-date.php">مواعيد العمليات</a>
    <a href="settings.php">الاعدادات</a>
    
    <a href="log-out.php"> تسجيل الخروج</a>
  </div>

  <style>
    .search-bar {
      background: #c68f8f;
      position: relative;
      margin: 20px auto;
      width: 70%;
      text-align: center;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
    }

    .search-bar label {
      font-size: 18px;
      margin-right: 10px;
      padding: 5px;
      font-weight: bold;
      color: #5e0303;
    }

    .search-bar input {
      padding: 5px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ccc;
      width: 50%;
      margin: 5px;
      overflow: hidden;
      position: relative;
      box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    }

    .search-bar button {
      text-align: center;
      background: #3498db;
      color: white;
      border: none;
      padding: 5px 15px;
      font-size: 15px;
      border-radius: 4px;
      cursor: pointer;
      width: 80px;
    }

    .search-bar button:hover {
      background: #2980b9;
      color: #c2a0a0;
      transform: scale(1.1);
      transition: 0.5s;
    }
    .table-scroll {
      max-height: 54vh;
      overflow-x: auto;
      overflow-y: auto;
      margin: 5px 10px 20px 10px;
      border-radius: 10px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);

    }
  </style>
  
    <div class="search-bar">
      <label for="search">البحث عن مريض:</label>
      <input
        id="searchInput"
        type="text"
        name="search"
        placeholder="ادخل اسم المريض او الرقم التسلسلي"
        onkeyup="filterTable()" />
      
        <button id="search" type="submit">بحث</button>
    </div>

 <div class="table-scroll">
  <table id="patients_table"
    style="
        width: 90%;
        margin-left: auto;
        margin-right: auto;
        position: relative;
        text-align: center;
        border-radius: 10px;
      
      ">
    <thead>
      <tr
        style="
          background: #82bbd7;
          box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
          font-weight: bold;
          font-size: 18px;
          color: rgb(80, 12, 12);
          border-radius: 10px;
          text-align: center;
          position: relative;
          margin: 10px;
          
        ">
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
      $select = "SELECT * FROM add_patient";
      $result = mysqli_query($con, $select);
      while ($row = mysqli_fetch_array($result)) {
      ?>

      
        <tr
          style="
              background: #f2f2f2;
              box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
              font-size: 16px;
              color: #333;
              text-align: center;
              position: relative;
              margin: 5px;
              border-bottom: 1px solid #ddd;
            ">
          <td><?php echo $row['id']; ?></td>
          <td><?php echo $row['full_name']; ?></td>
          <td><?php echo $row['age']; ?></td>
          <td><?php echo $row['gender']; ?></td>
          <td><?php echo $row['phone_no']; ?></td>
          <td><?php echo $row['address']; ?></td>
          
          <td><a id="open" href="patient-data.php?id_open=<?php echo $row['id']; ?>">فتح الملف</a></td>
          <td><button id="delete" onclick="confirmDelete(<?= $row['id'] ?>)">حذف</button></td>
<script>
function confirmDelete(id) {
  if (confirm("هل أنت متأكد من حذف بيانات هذا المريض؟")) {
    window.location.href = "delete-patient.php?id_delete=" + id;
  }
}
</script>
        </tr>
       
       <?php
      }
      ?>
</div>

      <style>
        th,
        td {
          padding: 6px;
        }

        

        #open {
          background-color: #27ae60;
          color: white;
          padding: 5px 10px;
          text-decoration: none;
          border-radius: 5px;
          font-size: 16px;
        }
        #delete {
          background-color: #e74c3c;
          color: white;
          padding: 5px 15px;
          border: none;
          border-radius: 5px;
          font-size: 16px;
          cursor: pointer;
        }
      </style>
    </tbody>
     
  </table>

  <script>
    function filterTable() {
      var input = document.getElementById("searchInput");
      var filter = input.value.toLowerCase();
      var table = document.getElementById("patients_table");
      var tr = table.getElementsByTagName("tr");

      for (var i = 1; i<tr.length; i++) {
        var rowText = 
        tr[i].textContent.toLowerCase();
        if (rowText.indexOf(filter)>-1) {
          tr[i].style.display = "";
        } else {
          tr[i].style.display = "none";
        }
      }

    }
  </script>
</body>

</html>