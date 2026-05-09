<?php
include "config.php";

include 'auth.php';

if (!isset($_GET['id'])) {
    die("معرف غير صالح.");
}
$id = (int) $_GET['id'];


$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";



// التعديل على حالة الحضور مع التحقق من أن المعرّف موجود في جدول واحد فقط

$updatedRows = 0;

$update_surgical = "UPDATE surgery_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_surgical = $con->prepare($update_surgical);
$stmt_surgical->bind_param("i", $id);
$stmt_surgical->execute();
$updatedRows += $stmt_surgical->affected_rows;

$update_laser = "UPDATE laser_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_laser = $con->prepare($update_laser);
$stmt_laser->bind_param("i", $id);
$stmt_laser->execute();
$updatedRows += $stmt_laser->affected_rows;

$update_injection = "UPDATE injection_appointment SET attendance_status = 1, updated_at = NOW() $syncPart WHERE id = ?";
$stmt_injection = $con->prepare($update_injection);
$stmt_injection->bind_param("i", $id);
$stmt_injection->execute();
$updatedRows += $stmt_injection->affected_rows;

if ($updatedRows === 0) {
    die("لم يتم العثور على الموعد المطلوب.");
}

if ($updatedRows > 1) {
    die("تم العثور على أكثر من موعد بنفس المعرّف. يرجى مراجعة البيانات.");
}
?>

<script>
    alert('تم تأكيد حضور المريض بنجاح.');
    // إعادة التوجيه إلى صفحة العمليات في التاريخ المحدد مع فتح قائمة العمليات بعد التأكيد
    window.location.href = 'operation-by-date.php?date=<?php echo urlencode($_GET['date'] ?? ''); ?>';
</script>