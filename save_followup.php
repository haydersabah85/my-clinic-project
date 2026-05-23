<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$patient_id = (int)($_POST['patient_id'] ?? 0);
$followup_date = trim($_POST['followup_date'] ?? '');
$reason = trim($_POST['followup_reason'] ?? '');
$note = trim($_POST['note'] ?? '');
$return_to = $_POST['return_to'] ?? 'patient-file';
$syncFields = $IS_LOCAL ? ", sync_status" : "";
$syncValues = $IS_LOCAL ? ", 0" : "";

if ($patient_id <= 0 || $followup_date === '' || $reason === '') {
    echo "<script>
        alert('يرجى اختيار المريض وتاريخ المراجعة وسبب المراجعة');
        window.history.back();
    </script>";
    exit;
}

$stmt = mysqli_prepare($con, "
    INSERT INTO followups (patient_id, followup_date, followup_reason, note, updated_at $syncFields)
    VALUES (?, ?, ?, ?, NOW() $syncValues)
");
mysqli_stmt_bind_param($stmt, "isss", $patient_id, $followup_date, $reason, $note);
mysqli_stmt_execute($stmt);
clinic_audit($con, 'create', 'followups', mysqli_insert_id($con), null, [
    'patient_id' => $patient_id,
    'followup_date' => $followup_date,
    'followup_reason' => $reason,
]);

if ($return_to === 'patient-data') {
    header("Location: patient-data.php?id=$patient_id");
} elseif ($return_to === 'appointment-page') {
    header("Location: followup-appointment.php?id=$patient_id&saved=1");
} else {
    header("Location: patient-file.php?id=$patient_id");
}
exit;
?>
