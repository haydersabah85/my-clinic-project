<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['laser_btn'])) {
  
    $patient_id = intval($_POST['id']);
    $eye = $_POST['eye'];
    $laser_type = $_POST['laser_type'];
    $notes = $_POST['notes'];
    $date = $_POST['date'];

    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    $insert_query = "INSERT INTO laser (patient_id, eye, laser_type, notes, date, updated_at $syncFields) 
    VALUES ('$patient_id', '$eye', '$laser_type', '$notes', '$date', NOW() $syncValues)";
    mysqli_query($con, $insert_query);

    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

    $update_query = "UPDATE laser_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE patient_id = '$patient_id' AND date = '$date'";
    mysqli_query($con, $update_query);

    header("Location: operation-by-date.php?date=" . urlencode($date));
    exit();
}
?>