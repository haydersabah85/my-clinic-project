<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['laser_btn'])) {
    $patient_id = intval($_POST['id']);
    $eye = $_POST['eye'];
    $laser_type = $_POST['laser_type'];
    $notes = $_POST['notes'];
    $date = $_POST['date'];

    $insert_query = "INSERT INTO laser (patient_id, eye, laser_type, notes, date) 
    VALUES ('$patient_id', '$eye', '$laser_type', '$notes', '$date')";
    mysqli_query($con, $insert_query);

    $delete_query = "DELETE FROM laser_appointment WHERE patient_id = '$patient_id'";
    mysqli_query($con, $delete_query);

    header("Location: operation-by-date.php?date=" . urlencode($date));
    exit();
}
?>