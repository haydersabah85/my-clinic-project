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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عيادة الدكتور حيدر صباح الربيعي</title>
</head>


<style>

body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 0;
    padding: 30px 15px;
    background: linear-gradient(135deg, #0f172a, #020617);
}

/* العناوين */
h1, h2 {
    color: #e0f2fe;
    text-align: center;
    margin-bottom: 25px;
    letter-spacing: 1px;
}

/* الفورم */
form {
    max-width: 540px;
    margin: auto;
    background: linear-gradient(180deg, #ffffff, #f8fafc);
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
    position: relative;
    overflow: hidden;
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
}

/* العناوين داخل الفورم */
label {
    display: block;
    margin-bottom: 6px;
    font-weight: 700;
    color: #0f172a;
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
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    font-size: 14px;
    transition: all 0.3s ease;
}

/* ============================= */
/* إبراز الحقول المهمة */
/* ============================= */

/* أول حقل نصي (اسم المريض غالبًا) */
input[type="text"]:first-of-type {
    border-color: #2563eb;
    background: #eff6ff;
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
    box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.3);
}

/* النصوص الطويلة */
textarea {
    resize: vertical;
    min-height: 110px;
}

/* زر الحفظ */
input[type="submit"] {
    width: 100%;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #ffffff;
    padding: 14px;
    border: none;
    border-radius: 16px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.35s ease;
}

/* hover */
input[type="submit"]:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 30px rgba(37, 99, 235, 0.45);
    background: linear-gradient(135deg, #1d4ed8, #6d28d9);
}

/* active */
input[type="submit"]:active {
    transform: translateY(0);
    box-shadow: none;
}

/* الشاشات الكبيرة */
@media screen and (min-width: 1200px) {
    form {
        padding: 40px 50px;
    }

    h1, h2 {
        font-size: 28px;
    }
}

/* الشاشات الصغيرة */
@media screen and (max-width: 768px) {
    form {
        padding: 25px 20px;
    }

    h1, h2 {
        font-size: 20px;
    }
}



</style>
<body>

    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>حجز موعد حقن</h2>
    <form action="injection-appointment2.php?id=<?php echo $id; ?>" method="POST">
        <label for="name">الاسم الكامل:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row['full_name']); ?>"><br><br>

        <label for="injection_type">نوع الحقن:</label><br>
        <select id="injection_type" name="injection_type" required>
            <option value="">اختر نوع الحقن</option>
            <option value="Avastin">Avastin</option>
            <option value="Eylea 2mg">Eylea 2mg</option>
            <option value="Vabysmo">Vabysmo</option>
            <option value="Eylea 8mg">Eylea 8mg</option>
            <option value="Triamcinolone">Triamcinolone</option>
            <option value="Lucentis">Lucentis</option>
        </select><br><br>



        <label for="eye">العين:</label><br>
        <select id="eye" name="eye" required>
            <option value="">اختر العين</option>
            <option value="OD">OD</option>
            <option value="OS">OS</option>
            <option value="OU">OU</option>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="tel" id="phone" name="phone" value="<?php echo $row['phone_no'] ?>" pattern="[0-9]+" placeholder="07xxxxxxxxx" required>
        
        <input type="tel" id="phone_alt" name="phone_alt" value="<?php echo $row['phone_no_alt'] ?>" pattern="[0-9]+" placeholder="رقم هاتف بديل">
        
        <br><br>

        <label for="date">موعد الحقن:</label><br>
        <input type="date" id="date" name="date"><br><br>



        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"></textarea><br><br>

        <input type="submit" name="submit_injection" value="حجز الموعد">
    </form>
</body>

</html>