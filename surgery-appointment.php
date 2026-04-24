<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

?>

<?php
// process_add_operation.php أو نفس الملف إذا كنت تستخدم POST في نفس الصفحة
if($_SERVER['REQUEST_METHOD'] == "POST") {
    // تأكد من تعريف قاعدة البيانات $conn قبل هذا الجزء
    $patient_name = $_POST['patient_name'];
    $phone = $_POST['phone'];
    $eye_type = $_POST['eye_type'];
    $operation_type = $_POST['operation_type'];
    $lens_type = $_POST['lens_type'];
    $operation_date = $_POST['operation_date'];
    $notes = $_POST['notes'];

    // استعلام لإدخال البيانات
    $sql = "INSERT INTO operations (patient_name, phone, eye_type, operation_type, lens_type, operation_date, notes)
            VALUES ('$patient_name', '$phone', '$eye_type', '$operation_type', '$lens_type', '$operation_date', '$notes')";

    if(mysqli_query($conn, $sql)){
        echo "<script>alert('تم حفظ العملية بنجاح');</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء الحفظ');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موعد عملية للمريض <?= htmlspecialchars($row['full_name']) ?></title>

 

    <style>
    
   body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 0;
    padding: 30px 15px;
    background: linear-gradient(135deg, #1f2933, #111827);
}

/* العناوين */
h1, h2 {
    color: #e5f0ff;
    text-align: center;
    margin-bottom: 25px;
    letter-spacing: 1px;
}

/* الفورم */
form {
    max-width: 540px;
    margin: auto;
    background: #ffffff;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
    position: relative;
}

/* شريط علوي جمالي */
form::before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, #2563eb, #22c55e, #a855f7);
    border-radius: 16px 16px 0 0;
}

/* العناوين */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: 700;
    color: #1e293b;
    font-size: 14px;
}

/* الحقول */
input[type="text"],
input[type="tel"],
input[type="date"],
select,
textarea {
    width: 95%;
    padding: 13px 15px;
    margin-bottom: 20px;
    margin-left: 5%;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    font-size: 14px;
    transition: all 0.3s ease;
}

/* ============================= */
/* إبراز الحقول المهمة */
/* ============================= */

/* أول حقل نصي (اسم المريض غالبًا) */
input[type="text"]:first-of-type {
    border-color: #2563eb;
    background: #eef2ff;
}

/* حقل التاريخ */
input[type="date"] {
    border-color: #22c55e;
    background: #ecfdf5;
}

/* الحقول المطلوبة */
input:required,
select:required,
textarea:required {
    border-color: #a855f7;
    background: #faf5ff;
}

/* ============================= */
/* تأثير التركيز */
/* ============================= */
input:focus,
select:focus,
textarea:focus {
    outline: none;
    background: #ffffff;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.25);
}

/* النصوص الطويلة */
textarea {
    resize: vertical;
    min-height: 100px;
}

/* زر الحفظ */
input[type="submit"] {
    width: 100%;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #ffffff;
    padding: 14px;
    border: none;
    border-radius: 14px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

/* hover */
input[type="submit"]:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(37, 99, 235, 0.4);
    background: linear-gradient(135deg, #1d4ed8, #6d28d9);
}

/* active */
input[type="submit"]:active {
    transform: translateY(0);
    box-shadow: none;
}
/* شاشات كبيرة */
@media screen and (max-width: 1200px) {
    form {
        padding: 30px 25px;
    }
    h1, h2 {
        font-size: 24px;
    }
}

/* شاشات صغيرة */
@media screen and (max-width: 768px) {
    form {
        padding: 25px 20px;
    }

    h1, h2 {
        font-size: 20px;
    }
}


    
    </style>

</head>
<body>
     
    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>حجز موعد عملية</h2>
    <form action="surgery-appointment2.php?id=<?php echo $id; ?>" method="POST">
        
        <label for="name">الاسم الكامل:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row['full_name']); ?>"
  
        ><br><br>

        <label for="surgery_type">نوع العملية:</label><br>
        <select id="surgery_type" name="surgery_type" required>
            <option value="">اختر نوع العملية</option>
            <option value="Phaco">Phaco</option>
            <option value="Vitrectomy">Vitrectomy</option>
            <option value="Phaco and Vitrectomy">Phaco and Vitrectomy</option>
            <option value="SOR">SOR</option>
            <option value="Phaco and SOR">Phaco and SOR</option>
            <option value="Squint">Squint</option>
            <option value="ECCE">ECCE</option>
            <option value="ICCE">ICCE</option>
            <option value="EUA">EUA</option>
            <option value="Probing">Probing</option>
            <option value="SMILE">SMILE</option>
            <option value="PRK">PRK</option>
            <option value="Secondary IOL">Secondary IOL</option>
            <option value="Anterior Vitrectomy">Anterior Vitrectomy</option>
            
        </select><br><br>

        <label for="eye">العين:</label><br>
        <select id="eye" name="eye" required>
            <option value="">اختر العين</option>
            <option value="OD">OD</option>
            <option value="OS">OS</option>
            <option value="OU">OU</option>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="text" id="phone" name="phone" value="<?php echo $row['phone_no'] ?>" pattern="[0-9]+" placeholder="07xxxxxxxxx" required>
        <br><br>
        <input type="text" id="phone_alt" name="phone_alt" value="<?php echo $row['phone_no_alt'] ?>" pattern="[0-9]+" placeholder="رقم هاتف بديل">
        <br><br>

        <label for="date">موعد العملية:</label><br>
        <input type="date" id="date" name="date" ><br><br>

        

        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"></textarea><br><br>

        <input type="submit" name="submit_surgery" value="حجز الموعد">

    </form>
</body>
</html>