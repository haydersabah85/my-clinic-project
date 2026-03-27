<?php

include 'config.php';

include 'auth.php';

if (isset($_GET['id_edit'])) {
    $id = $_GET['id_edit'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en" dir="auto">
  <head>
    <meta charset="UTF-8" />
    <title>تعديل بيانات المريض: <?php echo htmlspecialchars($row['full_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>


<style>


/* ====== إعدادات عامة ====== */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    background: linear-gradient(135deg, #f4f7fb, #eef2f6);
}

/* ====== العنوان ====== */
h1 {
    text-align: center;
    color: #b73232;
    margin: 20px 0 10px;
    font-weight: 700;
}

h2 {
    text-align: center;
    color: #b73232;
    margin: 20px 0;
    font-weight: 600;
}

/* ====== شريط التنقل ====== */
.sidenav {
    background: linear-gradient(135deg, #a0d450, #7fbf3f);
    padding: 12px;
    margin: 15px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 6px 14px rgba(0,0,0,0.25);
}

.sidenav a {
    display: inline-block;
    background: linear-gradient(135deg, #e8d79a, #d7c588);
    color: #6a2ca0;
    text-decoration: none;
    padding: 6px 14px;
    margin: 6px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 3px 6px rgba(0,0,0,0.25);
}

.sidenav a:hover {
    background: #ffffff;
    color: #333;
    transform: translateY(-2px);
}

/* ====== الفورم ====== */
.add-patient {
    background: #ffffff;
    margin: 30px auto;
    padding: 30px 25px;
    border-radius: 20px;
    box-shadow: 0 10px 24px rgba(0,0,0,0.15);
    width: 80%;
    max-width: 900px;
    direction: rtl;
}

/* ====== صفوف البيانات ====== */
.patient-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.patient-info label {
    width: 22%;
    min-width: 140px;
    background: linear-gradient(135deg, #a7dd77, #8ccf5c);
    color: #2f4f4f;
    font-weight: bold;
    padding: 8px 10px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 3px 6px rgba(0,0,0,0.25);
}

/* ====== الحقول ====== */
.patient-info input,
#gender {
    flex: 1;
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
    transition: all 0.25s ease;
}

.patient-info input:focus,
#gender:focus {
    outline: none;
    border-color: #0ab370;
    box-shadow: 0 0 0 3px rgba(10,179,112,0.25);
    background: #f9fffc;
}

/* ====== زر التحديث ====== */
#update-patient-btn {
    background: linear-gradient(135deg, #ca280b, #e04b2f);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 14px;
    font-size: 16px;
    cursor: pointer;
    margin: 20px auto 0;
    display: block;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 6px 14px rgba(0,0,0,0.35);
}

#update-patient-btn:hover {
    background: linear-gradient(135deg, #789bce, #567eb6);
    color: #fff;
    transform: translateY(-2px);
}

#update-patient-btn:active {
    transform: scale(0.96);
}

/* ====== تحسين العرض للموبايل ====== */
@media (max-width: 768px) {

    .add-patient {
        width: 90%;
        padding: 20px;
    }

    .patient-info {
        flex-direction: column;
        align-items: stretch;
    }

    .patient-info label {
        width: 100%;
    }

    .sidenav {
        margin: 10px;
    }

    .sidenav a {
        font-size: 14px;
        padding: 6px 10px;
    }
}



</style>



  <body>

    <header>
    <h1>الدكتور حيدر صباح الربيعي</h1>
  </header>
  <body>
    <div class="sidenav">
      <a href="dashboard.php">الصفحة الرئيسية</a>
      <a href="add-patient.html">أضافة مريض جديد</a>
      <a href="appointment.html">المواعيد</a>
      <a href="vouchers.html">الفواتير</a>
      <a href="reports.html">التقارير</a>
      <a href="logout.php"> تسجيل الخروج</a>
    </div>



    <h2 style= "text-align: center; color: rgb(183, 50, 50)">تعديل بيانات المريض</h2>
  
    <form class="add-patient" action="update-patient.php?id_edit=<?php echo $id; ?>" method="post">
       
        <div class="patient-info">
            <label for="full_name">الاسم الرباعي</label>
            <input type="text" id="full_name" name="full_name" required value="<?php echo $row['full_name']; ?>">
        </div>  
        
        <div class="patient-info">
            <label for="age">العمر</label>
            <input type="text" id="age" name="age" required value="<?php echo $row['age']; ?>">
        </div>
        
        <div class="patient-info">
            <label for="date_of_birth">تاريخ الميلاد</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $row['date_of_birth']; ?>">
        </div>  
        
        <div class="patient-info">
            <label for="gender">الجنس</label>
            <select id="gender" name="gender" value="<?php echo $row['gender']; ?>">
                <?php
                $genders = ['ذكر', 'أنثى'];
                foreach ($genders as $gender) {
                    $selected = ($row['gender'] === $gender) ? 'selected' : '';
                    echo "<option value=\"$gender\" $selected>$gender</option>";
                }
                ?>
            </select>   
        </div>

        <div class="patient-info">
            <label for="phone_no">الموبايل</label>
            <input type="text" id="phone_no" name="phone_no"  value="<?php echo $row['phone_no']; ?>">
        </div>
        
        <div class="patient-info">
            <label for="phone_no_alt">موبايل بديل</label>
            <input type="text" id="phone_no_alt" name="phone_no_alt"  value="<?php echo $row['phone_no_alt']; ?>">
        </div>

        <div class="patient-info">
            <label for="address">العنوان</label>
            <input type="text" id="address" name="address"  value="<?php echo $row['address']; ?>">
        </div>
        <div class="patient-info">  
            <label for="notes">الملاحظات</label>
            <input type="textarea" id="notes" name="notes" value="<?php echo $row['notes']; ?>">
        </div>

        
    
    
    <br></br>
        <button id="update-patient-btn" type="submit" name="update_patient">تعديل البيانات</button>
        </form>
        

    </body>


</html>
