<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    $select_patient = "SELECT * FROM add_patient WHERE id = $id";
    $result_patient = mysqli_query($con, $select_patient);
    $row_patient = mysqli_fetch_assoc($result_patient);

    $update_injection_query = "UPDATE injection_appointment SET status = 'discharged' WHERE patient_id = '$id'";
    mysqli_query($con, $update_injection_query);


    // Optionally, you can add more logic here, such as logging the discharge or notifying staff

    // Redirect back to the main page or appointments page
    header("Location: edit-patient.php?id_edit=$id");
    exit();
} else {
    echo "No appointment ID provided.";
    exit();
}
    