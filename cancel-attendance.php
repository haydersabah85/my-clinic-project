<?php

include "config.php";

include 'auth.php';

if (!isset($_GET['id'])) {
    die("معرف غير صالح.");
}
$id = $_GET['id'];
$date = $_GET['date'];

// الغاء تأكيد حضور المريض في جدول المواعيد الجراحية
$select_date_query = "SELECT date FROM surgery_appointment WHERE id = $id LIMIT 1";
$select_date_result = mysqli_query($con, $select_date_query);
$select_date_row = mysqli_fetch_assoc($select_date_result);
$date = $select_date_row['date'];
$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
$update_query = "UPDATE surgery_appointment 
                   SET  attendance_status = 0, updated_at = NOW() $syncPart
                   WHERE id = $id";
$update_result = mysqli_query($con, $update_query);
if ($update_result) {
    echo "<script>alert('تم إلغاء تأكيد حضور المريض بنجاح.');</script>";
    echo "<script>window.location.href = 'operation-by-date.php?date=$date';</script>";
} else {
    echo "<script>alert('حدث خطأ أثناء إلغاء تأكيد الحضور.');</script>";
    echo "<script>window.location.href = 'confirmed-list.php';</script>";
}




// الغاء تأكيد حضور المريض في جدول مواعيد الليزر

$select_date_query = "SELECT date FROM laser_appointment WHERE id = $id LIMIT 1";
$select_date_result = mysqli_query($con, $select_date_query);
$select_date_row = mysqli_fetch_assoc($select_date_result);
$date = $select_date_row['date'];
$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

$update_laser_query = "UPDATE laser_appointment 
                   SET  attendance_status = 0, updated_at = NOW() $syncPart
                   WHERE id = $id";
$update_laser_result = mysqli_query($con, $update_laser_query);
if ($update_laser_result) {
    echo "<script>alert('تم إلغاء تأكيد حضور المريض في مواعيد الليزر بنجاح.');</script>";
    echo "<script>window.location.href = 'operation-by-date.php?date=$date';</script>";
} else {
    echo "<script>alert('حدث خطأ أثناء إلغاء تأكيد الحضور في مواعيد الليزر.');</script>";
    echo "<script>window.location.href = 'confirmed-list.php';</script>";
}




// الغاء تأكيد حضور المريض في جدول مواعيد الحقن
$select_date_query = "SELECT date FROM injection_appointment WHERE id = $id LIMIT 1";
$select_date_result = mysqli_query($con, $select_date_query);
$select_date_row = mysqli_fetch_assoc($select_date_result);
$date = $select_date_row['date'];
$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

$update_injection_query = "UPDATE injection_appointment
                     SET attendance_status = 0, updated_at = NOW() $syncPart
                     WHERE id = $id";
$update_injection_result = mysqli_query($con, $update_injection_query);
if ($update_injection_result) {
    echo "<script>alert('تم إلغاء تأكيد حضور المريض في مواعيد الحقن بنجاح.');</script>";
    echo "<script>window.location.href = 'operation-by-date.php?date=$date';</script>";
} else {
    echo "<script>alert('حدث خطأ أثناء إلغاء تأكيد الحضور في مواعيد الحقن.');</script>";
    echo "<script>window.location.href = 'confirmed-list.php';</script>";
}
?>
