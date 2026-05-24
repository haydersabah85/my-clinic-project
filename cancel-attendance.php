<?php
include 'auth.php';
include 'config.php';

if (!isset($_GET['id'])) {
    die('معرف غير صالح.');
}

$id = (int) $_GET['id'];
$kind = $_GET['kind'] ?? '';
$fallbackDate = $_GET['date'] ?? date('Y-m-d');

$tables = [
    'surgery' => 'surgery_appointment',
    'laser' => 'laser_appointment',
    'injection' => 'injection_appointment',
];

if (!isset($tables[$kind])) {
    die('نوع الموعد غير صالح.');
}

$table = $tables[$kind];
$selectStmt = $con->prepare("SELECT date FROM $table WHERE id = ? LIMIT 1");
$selectStmt->bind_param('i', $id);
$selectStmt->execute();
$row = $selectStmt->get_result()->fetch_assoc();

if (!$row) {
    die('لم يتم العثور على الموعد المطلوب.');
}

$date = $row['date'] ?: $fallbackDate;
$syncPart = $IS_LOCAL ? ', sync_status = 0' : '';

$updateStmt = $con->prepare("UPDATE $table SET attendance_status = 0, updated_at = NOW() $syncPart WHERE id = ?");
$updateStmt->bind_param('i', $id);
$ok = $updateStmt->execute();
$target = 'confirmed-list.php?date=' . urlencode($date);
?>

<script>
    alert('<?= $ok ? 'تم إلغاء التأكيد بنجاح.' : 'حدث خطأ أثناء إلغاء التأكيد.' ?>');
    window.location.href = '<?= $target ?>';
</script>
