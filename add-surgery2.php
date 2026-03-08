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

    $insert_query = "INSERT INTO surgery (patient_id, eye, surgery_type, iol_type, notes, date)
     VALUES ('$patient_id', '$eye', '$surgery_type', '$iol_type', '$notes', '$date')";
    mysqli_query($con, $insert_query);
    

   

    // Update the surgery_appointment table to mark the operation as done

    $update_query = "UPDATE surgery_appointment SET status = 'done' WHERE patient_id = '$patient_id'";
    mysqli_query($con, $update_query);


    header("Location: operation-by-date.php?date=" . urlencode($date));

    exit();
}
