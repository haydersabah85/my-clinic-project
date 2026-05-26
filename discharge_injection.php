<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $appointment_id = isset($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : 0;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

    $select_patient = "SELECT * FROM add_patient WHERE id = $id";
    $result_patient = mysqli_query($con, $select_patient);
    $row_patient = mysqli_fetch_assoc($result_patient);

    if ($appointment_id > 0) {
        $update_injection_query = "UPDATE injection_appointment SET status = 'discharged', updated_at = NOW() $syncPart WHERE id = '$appointment_id' AND patient_id = '$id'";
    } else {
        $update_injection_query = "UPDATE injection_appointment SET status = 'discharged', updated_at = NOW() $syncPart WHERE patient_id = '$id'";
    }
    mysqli_query($con, $update_injection_query);


    // Optionally, you can add more logic here, such as logging the discharge or notifying staff

    // Redirect back to the main page or appointments page
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        header("Location: operation-by-date.php?date=" . urlencode($appointment_date));
    } else {
        header("Location: edit-patient.php?id_edit=$id");
    }
    exit();
} else {
    echo "No appointment ID provided.";
    exit();
}
    
