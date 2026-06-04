<?php

include 'config.php';

include 'auth.php';

$visit_id = $_POST['id'];
$visit_uuid = bin2hex(random_bytes(16));
$notes = mysqli_real_escape_string($con, $_POST['notes'] ?? '');
$patient_id = (int)$_GET['id'];
$posted_visit_date = $_POST['visit_date'] ?? '';
$visit_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $posted_visit_date) ? $posted_visit_date : date('Y-m-d');
// 🔥 جلب UUID الخاص بالمريض
$getPatient = mysqli_query($con, "
    SELECT uuid
    FROM add_patient
    WHERE id = '$patient_id'
");

$patientData = mysqli_fetch_assoc($getPatient);

$patient_uuid = $patientData['uuid'] ?? '';

$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";




if (!empty($visit_id)) {
    // Update existing visit
      $visit_id = (int)$visit_id;

    $update_query = "UPDATE patient_visits SET notes='$notes',
     patient_uuid = '$patient_uuid',
    updated_at = NOW() $syncPart WHERE id='$visit_id'";

    mysqli_query($con, $update_query);

    header("Location: patient-file.php?id=$patient_id");
    exit();
} else {
    // Insert new visit
    $visit_uuid = bin2hex(random_bytes(16));
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";
    $insert_query = "INSERT INTO patient_visits (
    patient_id, patient_uuid, visit_uuid, date, notes, updated_at $syncFields)
    
     VALUES ('$patient_id', '$patient_uuid', '$visit_uuid', '$visit_date', '$notes', NOW() $syncValues)";
    mysqli_query($con, $insert_query);

    header("Location: patient-file.php?id=$patient_id");
    exit();
}
