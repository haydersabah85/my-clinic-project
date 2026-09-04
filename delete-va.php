<?php
include 'config.php';
include 'auth.php';

if (!isset($_GET['id_delete'])) {
    header('Location: patient-file.php');
    exit;
}

$id_delete = (int) $_GET['id_delete'];
$patient_id = isset($_GET['patient_id']) ? (int) $_GET['patient_id'] : 0;

if ($id_delete <= 0) {
    header('Location: patient-file.php' . ($patient_id > 0 ? '?id=' . $patient_id : ''));
    exit;
}

$patient_query = mysqli_query($con, "SELECT patient_id FROM va WHERE va_id = $id_delete LIMIT 1");
if ($patient_query && $patient_row = mysqli_fetch_assoc($patient_query)) {
    $patient_id = (int) ($patient_row['patient_id'] ?? $patient_id);
}

mysqli_query($con, "DELETE FROM va WHERE va_id = $id_delete");
clinic_log_deleted_record($con, 'va', $id_delete);

header('Location: patient-file.php?id=' . $patient_id);
exit;
