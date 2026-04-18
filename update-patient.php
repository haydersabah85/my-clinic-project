<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['update_patient'])) {
    $id = $_GET['id_edit'];
    $full_name = $_POST['full_name'];
    $age = $_POST['age'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone_no = $_POST['phone_no']; 
    $phone_no_alt = $_POST['phone_no_alt'];
    $address = $_POST['address'];
    $notes = $_POST['notes'];

    $update_query = "UPDATE add_patient SET 
        full_name='$full_name', 
        age='$age', 
        date_of_birth='$date_of_birth',
        gender='$gender',
        phone_no='$phone_no',   
        phone_no_alt='$phone_no_alt',
        address='$address',
        notes='$notes'
        WHERE id=$id";
        $result = mysqli_query($con, $update_query);
        if ($result) {
            header('location:dashboard.php');
        } else {
            die(mysqli_error($con));
        }
}


?>