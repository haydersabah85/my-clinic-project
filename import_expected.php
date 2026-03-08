<?php
include "config.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if(isset($_POST['import'])){
    $file = $_FILES['excel']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    foreach($rows as $i => $row){
        if($i == 0) continue; // تخطي العناوين

        $name  = $row[0];
        $phone = $row[1];
        $date  = date('Y-m-d', strtotime($row[2]));
        $time  = date('H:i:s', strtotime($row[3]));
        $visit_type = $row[4];
        $notes = $row[5];

        $stmt = $con->prepare("
            INSERT INTO expected_appointments
            (full_name, phone, expected_date, expected_time, visit_type, notes)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$name,$phone,$date,$time,$visit_type,$notes]);
    }

    echo "<script>alert('تم استيراد المواعيد بنجاح');</script>";
    echo "<script>window.location.href='expected_appointments.php?date=".date('Y-m-d')."';</script>";
    exit;
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>استيراد المواعيد المتوقعة</title>
<style>
body{
    font-family: Cairo, Tahoma;
    background:#f5f7fa;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
form{
    background:#fff;
    padding:30px;
    border-radius:14px;
    width:380px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
}
h3{text-align:center;margin-bottom:20px;color:#0d6efd}
input,button{
    width:100%;
    padding:12px;
    margin-top:12px;
}
button{
    background:#0d6efd;
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
</style>
</head>
<body>

<form method="post" enctype="multipart/form-data">
    <h3>رفع ملف المواعيد المتوقعة</h3>
    <input type="file" name="excel" accept=".xlsx,.xls" required>
    <button name="import">استيراد</button>
</form>

</body>
</html>
