<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    clinic_require_csrf();

    $uuid = bin2hex(random_bytes(16));
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone_no = $_POST['phone_no'];
    $phone_no_alt = $_POST['phone_no_alt'];
    $address = $_POST['address'];
    $notes = $_POST['notes'];
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";


    $insert = "INSERT INTO add_patient (uuid, full_name, age, date_of_birth, gender, phone_no, phone_no_alt, address, notes, updated_at $syncFields)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() $syncValues)";
    $stmt = mysqli_prepare($con, $insert);
    mysqli_stmt_bind_param($stmt, "sssssssss", $uuid, $full_name, $age, $date_of_birth, $gender, $phone_no, $phone_no_alt, $address, $notes);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        clinic_audit($con, 'create', 'add_patient', mysqli_insert_id($con), null, [
            'full_name' => $full_name,
            'phone_no' => $phone_no,
        ]);
    }

    header('Location: dashboard.php');
}
