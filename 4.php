<!DOCTYPE html>
<html lang="en" dir="auto">
  <head>
    <meta charset="UTF-8" />
    <title>عيادة الدكتور حيدر صباح الربيعي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>

  <body>

    <style>
      h1 {
        background-color: rgb(255, 221, 0);
        text-align: center;
        color: rgb(183, 50, 50);
        margin: 10px 20px 10px 20px;
      }

      .sidenav {
        height: 75px;
        position: relative;
        background: #a0d450;
        box-shadow: 4px 4px 4px rgba(0, 0, 0, 0.3);
        padding: 10px 10px 10px 10px;
        text-align: center;
        margin: 10px 10px 10px 10px;
        place-content: center;
        border-radius: 10px;
        overflow:hidden

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
      }
    </style>

    <header>
    <h1>الدكتور حيدر صباح الربيعي</h1>
  </header>
  <body>
    <div class="sidenav">
      <a href="main.php">الصفحة الرئيسية</a>
      <a href="visits.php">زيارات اليوم</a>
      <a href="appointment.php">المواعيد</a>
      <a href="vouchers.php">الفواتير</a>
      <a href="reports.php">التقارير</a>
      <a href="log-out.php"> تسجيل الخروج</a>
    </div>


  <style>
    .add-patient {
        background: #a0ceda;
        margin: 25px 60px 10px 60px;
        padding: 25px;
        border-radius: 4px;
        box-shadow: #a0d450 2px 2px 2px;
        font-family: Arial, Helvetica, sans-serif;
        width: 80%;
        display: inline-block;
        position: relative;
        text-align: center;
        direction: rtl; 
        overflow: hidden;

        
    }
    
    .patient-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        align-items: center;
        overflow: hidden; 
        
    }
    .patient-info label {
        color: rgb(118, 86, 83);
        font-weight: bold;
        background: #a7dd77;
        padding: 5px;
        border-radius: 5px;
        margin: 5px 8px 5px 15px;
        width: 20%;
        float: right;
         border: 1px solid #030303;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);

    }
    .patient-info input {
        margin: 5px 8px 5px 15px;
        padding: 5px;
        width: 80%;
        float: left;
        border-radius: 5px;
        border: 1px solid #030303;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
    }

    #add-patient-btn {
        background: rgb(202, 40, 11);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        position: relative;
        margin: 1px 60px 10px 20px;
        transition: 0.3s;
        font-weight: bold;
        font-family: Arial, Helvetica, sans-serif;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        display: inline-block;

    }
    #add-patient-btn:hover {
        background-color: #789bce;
        color: black;
    }
    #add-patient-btn:active {
        transform: scale(0.95);
    }
    #gender {
        margin: 5px 8px 5px 15px;
        padding: 5px;
        width: 82%;
        float: left;
        border-radius: 5px;
        border: 1px solid #030303;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
    }
  </style>

    <h2 style= "text-align: center; color: rgb(183, 50, 50)">أضافة مريض جديد</h2>
  
    <form class="add-patient" action="add-patient.php" method="post">
       
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
            <input type="number" id="phone_no" name="phone_no" >
        </div>
        
        <div class="patient-info">
            <label for="address">العنوان</label>
            <input type="text" id="address" name="address" >
        </div>
        <div class="patient-info">  
            <label for="notes">الملاحظات</label>
            <input type="textarea" id="notes" name="notes">
        </div>

        
    
    
    <br></br>
        <button id="add-patient-btn" type="submit" name="submit">أضافة المريض</button>
        </form>
        

    </body>


</html>
