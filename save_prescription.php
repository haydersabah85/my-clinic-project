<?php
include 'config.php';

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

$getVisit = mysqli_query($con, "
    SELECT visit_uuid
    FROM patient_visits
    WHERE id = '$visit_id'
");

$visitData = mysqli_fetch_assoc($getVisit);
$visit_uuid = $visitData['visit_uuid'];


if (empty($patient_id)) {
    die("خطأ: لم يتم تحديد المريض");
}

$check = mysqli_query($con, "SELECT id FROM add_patient WHERE id = '$patient_id'");
if (mysqli_num_rows($check) == 0) {
    die("خطأ: المريض غير موجود في قاعدة البيانات");
}

mysqli_query($con, "INSERT INTO prescriptions (patient_id, diagnosis, prescription_uuid, visit_uuid, patient_uuid, updated_at $syncFields) 
VALUES ('$patient_id','$diagnosis','$prescription_uuid','$visit_uuid','$patient_uuid', NOW() $syncValues)");

$prescription_id = mysqli_insert_id($con);

foreach ($_POST['medicine_id'] as $index => $medicine_id) {

    $dose = $_POST['dose'][$index];
    $frequency = $_POST['frequency'][$index];
    $duration = $_POST['duration'][$index];
    $eye = $_POST['eye'][$index];
    $instructions = $_POST['instructions'][$index];
    $item_uuid = bin2hex(random_bytes(16));
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";
    $getPrescription = mysqli_query($con, "
    SELECT prescription_uuid
    FROM prescriptions
    WHERE id = '$prescription_id'
");

    $prescriptionData = mysqli_fetch_assoc($getPrescription);
    $prescription_uuid = $prescriptionData['prescription_uuid'];

    $getMedicine = mysqli_query($con, "
    SELECT medicine_uuid
    FROM medicines
    WHERE id = '$medicine_id'
");

    $medicineData = mysqli_fetch_assoc($getMedicine);
    $medicine_uuid = $medicineData['medicine_uuid'];



    mysqli_query($con, "INSERT INTO prescription_items 
    (prescription_id, medicine_id, dose, frequency, duration, eye, instructions, item_uuid, prescription_uuid, medicine_uuid, updated_at $syncFields)
    VALUES 
    ('$prescription_id','$medicine_id','$dose','$frequency','$duration','$eye','$instructions','$item_uuid','$prescription_uuid','$medicine_uuid', NOW() $syncValues)");
}

header("Location: view_prescription.php?id=" . $prescription_id);
exit;
