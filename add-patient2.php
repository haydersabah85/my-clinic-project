<?php

include 'config.php';
include 'auth.php';

if (isset($_POST['submit'])) {

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


    $insert = "INSERT INTO add_patient (full_name, age, date_of_birth, gender, phone_no, phone_no_alt, address, notes, updated_at $syncFields) 
    VALUES('$full_name','$age','$date_of_birth','$gender','$phone_no','$phone_no_alt','$address','$notes', NOW() $syncValues)";
    $result = mysqli_query($con, $insert);

    header('Location: main.php');
}
