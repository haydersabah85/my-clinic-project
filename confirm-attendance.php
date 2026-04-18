<?php
include "config.php";

include 'auth.php';

if (!isset($_GET['id'])) {
    die("معرف غير صالح.");
}
$id = $_GET['id'];

$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";



// التعديل على حالة الحضور في جداول المواعيد الثلاثة

$update_surgical = "UPDATE surgery_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_surgical = $con->prepare($update_surgical);
$stmt_surgical->bind_param("i", $id);
$stmt_surgical->execute();

$update_laser = "UPDATE laser_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_laser = $con->prepare($update_laser);
$stmt_laser->bind_param("i", $id);
$stmt_laser->execute();

$update_injection = "UPDATE injection_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_injection = $con->prepare($update_injection);
$stmt_injection->bind_param("i", $id);
$stmt_injection->execute();
?>

<script>
    alert('تم تأكيد حضور المريض بنجاح.');
    window.location.href = 'operation-by-date.php';
</script>