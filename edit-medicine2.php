<?php

include 'config.php';
include 'auth.php';

if (isset($_POST['edit_medicine'])) {
    $id_medicine = $_GET['id_medicine'];
    $medicine_name = $_POST['medicine_name'];
    $medicine_form = $_POST['medicine_form'];
    $strength = $_POST['strength'];
    $category = $_POST['category'];

    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    $update = "UPDATE medicines 
    SET medicine_name='$medicine_name', medicine_form='$medicine_form', strength='$strength', category='$category' , updated_at = NOW() $syncPart
    WHERE id='$id_medicine'";
    if (mysqli_query($con, $update)) {
        header("Location: common-medicines.php");
        exit;
    } else {
        echo "Error updating record: " . mysqli_error($con);
    }
} else {
    echo "No medicine ID provided.";
    exit;
}