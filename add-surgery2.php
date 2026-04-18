<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['surgery_btn'])) {
   
    $patient_id = intval($_POST['id']);
    $eye = $_POST['eye'];
    $surgery_type = $_POST['surgery_type'];
    $iol_type = $_POST['iol_type'];
    $notes = $_POST['notes'];
    $date = $_POST['date'];

    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";


    $insert_query = "INSERT INTO surgery (patient_id, eye, surgery_type, iol_type, notes, date, updated_at $syncFields)
     VALUES ('$patient_id', '$eye', '$surgery_type', '$iol_type', '$notes', '$date', NOW() $syncValues)";
    mysqli_query($con, $insert_query);
    


    // Update the surgery_appointment table to mark the operation as done
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

    $update_query = "UPDATE surgery_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE patient_id = '$patient_id' AND date = '$date'";
    mysqli_query($con, $update_query);

    header("Location: operation-by-date.php?date=" . urlencode($date));

    exit();
}
