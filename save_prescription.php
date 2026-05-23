<?php
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$patient_id = $_POST['patient_id'];
$diagnosis = $_POST['diagnosis'];
$prescription_uuid = bin2hex(random_bytes(16));
$syncFields = $IS_LOCAL ? ", sync_status" : "";
$syncValues = $IS_LOCAL ? ", 0" : "";
$getPatient = mysqli_query($con, "
    SELECT uuid
    FROM add_patient
    WHERE id = '$patient_id'
");
$patientData = mysqli_fetch_assoc($getPatient);
$patient_uuid = $patientData['uuid'];
$visit_uuid = bin2hex(random_bytes(16));


if (empty($patient_id)) {
    die("خطأ: لم يتم تحديد المريض");
}

$check = mysqli_query($con, "SELECT id FROM add_patient WHERE id = '$patient_id'");
if (mysqli_num_rows($check) == 0) {
    die("خطأ: المريض غير موجود في قاعدة البيانات");
}

mysqli_query($con, "INSERT INTO prescriptions (patient_id, diagnosis, updated_at $syncFields) 
VALUES ('$patient_id','$diagnosis', NOW() $syncValues)");

$prescription_id = mysqli_insert_id($con);
clinic_audit($con, 'create', 'prescriptions', $prescription_id, null, [
    'patient_id' => $patient_id,
    'diagnosis' => $diagnosis,
]);

foreach ($_POST['medicine_id'] as $index => $medicine_id) {

    $dose = $_POST['dose'][$index];
    $frequency = $_POST['frequency'][$index];
    $duration = $_POST['duration'][$index];
    $eye = $_POST['eye'][$index];
    $instructions = $_POST['instructions'][$index];
    $item_uuid = bin2hex(random_bytes(16));
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";
   


    mysqli_query($con, "INSERT INTO prescription_items 
    (prescription_id, medicine_id, dose, frequency, duration, eye, instructions, updated_at $syncFields)
    VALUES 
    ('$prescription_id','$medicine_id','$dose','$frequency','$duration','$eye','$instructions', NOW() $syncValues)");
}

header("Location: view_prescription.php?id=" . $prescription_id);
exit;
