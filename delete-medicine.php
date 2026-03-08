<?php

include 'config.php';
include 'auth.php';

if (isset($_GET['id_medicine'])) {
    $id_medicine = $_GET['id_medicine'];

    // Delete the medicine from the database
    $delete_query = "DELETE FROM medicines WHERE id = '$id_medicine'";
    if (mysqli_query($con, $delete_query)) {
        // Redirect back to the common medicines page after deletion
        header("Location: common-medicines.php");
        exit();
    } else {
        echo "Error deleting medicine: " . mysqli_error($con);
    }
} else {
    echo "No medicine ID provided.";
}