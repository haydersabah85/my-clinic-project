<?php
include 'config.php';
include 'auth.php';

if (isset($_POST['add_medicine'])) {
    $medicine_name = $_POST['medicine_name'];
    $medicine_form = $_POST['medicine_form'];
    $category = $_POST['category'];
    $strength = $_POST['strength'];

    $sql = "INSERT INTO medicines (medicine_name, medicine_form, category, strength) VALUES ('$medicine_name', '$medicine_form', '$category', '$strength')";
    if (mysqli_query($con, $sql)) {
        echo "<script>alert('Medicine added successfully.'); window.location.href='common-medicines.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($con) . "'); window.location.href='common-medicines.php';</script>";
    }
}
?>