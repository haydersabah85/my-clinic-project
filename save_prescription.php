<?php
include 'config.php';

$patient_id = $_POST['patient_id'];
$diagnosis = $_POST['diagnosis'];


if(empty($patient_id)){
    die("خطأ: لم يتم تحديد المريض");
}

$check = mysqli_query($con,"SELECT id FROM add_patient WHERE id = '$patient_id'");
if(mysqli_num_rows($check) == 0){
    die("خطأ: المريض غير موجود في قاعدة البيانات");
}

mysqli_query($con,"INSERT INTO prescriptions (patient_id, diagnosis) 
VALUES ('$patient_id','$diagnosis')");

$prescription_id = mysqli_insert_id($con);

foreach($_POST['medicine_id'] as $index => $medicine_id){

    $dose = $_POST['dose'][$index];
    $frequency = $_POST['frequency'][$index];
    $duration = $_POST['duration'][$index];
    $eye = $_POST['eye'][$index];
    $instructions = $_POST['instructions'][$index];

    mysqli_query($con,"INSERT INTO prescription_items 
    (prescription_id, medicine_id, dose, frequency, duration, eye, instructions)
    VALUES 
    ('$prescription_id','$medicine_id','$dose','$frequency','$duration','$eye','$instructions')");
}

header("Location: view_prescription.php?id=".$prescription_id);
exit;