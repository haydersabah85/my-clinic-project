<?php

include 'config.php';
include 'auth.php';

if (isset($_POST['injection_btn'])) {
    $patient_id = intval($_POST['id']);
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $eye = $_POST['eye'];
    $injection_type = $_POST['injection_type'];
    $notes = $_POST['notes'];
    $date = $_POST['date'];
    $injection_uuid = bin2hex(random_bytes(16));
    $getPatient = mysqli_query($con, "
    SELECT uuid
    FROM add_patient
    WHERE id = '$patient_id'
");

    $patientData = mysqli_fetch_assoc($getPatient);
    $patient_uuid = $patientData['uuid'];

    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    $insert_query = "INSERT INTO injection (patient_id, patient_uuid, injection_uuid, eye, injection_type, notes, date, updated_at $syncFields) 
    VALUES ('$patient_id', '$patient_uuid', '$injection_uuid', '$eye', '$injection_type', '$notes', '$date', NOW() $syncValues)";
    mysqli_query($con, $insert_query);

    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    if ($appointment_id > 0) {
        $update_query = "UPDATE injection_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE id = '$appointment_id' AND patient_id = '$patient_id'";
    } else {
        $update_query = "UPDATE injection_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE patient_id = '$patient_id' AND date = '$date'";
    }
    mysqli_query($con, $update_query);

    $redirect_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date) ? $appointment_date : $date;
    header("Location: operation-by-date.php?date=" . urlencode($redirect_date));
    exit();
}
