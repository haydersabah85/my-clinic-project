<?php

include 'config.php';
include 'auth.php';

if (isset($_POST['injection_btn'])) {
    $patient_id = intval($_POST['id']);
    $eye = $_POST['eye'];
    $injection_type = $_POST['injection_type'];
    $notes = $_POST['notes'];
    $date = $_POST['date'];

    $insert_query = "INSERT INTO injection (patient_id, eye, injection_type, notes, date) 
    VALUES ('$patient_id', '$eye', '$injection_type', '$notes', '$date')";
    mysqli_query($con, $insert_query);

    $update_query = "UPDATE injection_appointment SET status = 'done' WHERE patient_id = '$patient_id' AND date = '$date'";
    mysqli_query($con, $update_query);

    header("Location: operation-by-date.php?date=" . urlencode($date));
    exit();
}
