<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id_delete'])) {
    $id_delete = $_GET['id_delete'];

    // Prepare the DELETE statement
    $delete_query = "DELETE FROM surgery WHERE id = ?";
    $stmt = mysqli_prepare($con, $delete_query);
    mysqli_stmt_bind_param($stmt, 'i', $id_delete);
    
    // Get the patient ID before deletion for redirection
    $patient_id_query = "SELECT patient_id FROM surgery WHERE id = ?";
    $patient_id_stmt = mysqli_prepare($con, $patient_id_query);
    mysqli_stmt_bind_param($patient_id_stmt, 'i', $id_delete);
    mysqli_stmt_execute($patient_id_stmt);
    mysqli_stmt_bind_result($patient_id_stmt, $patient_id);
    mysqli_stmt_fetch($patient_id_stmt);
    mysqli_stmt_close($patient_id_stmt);
    

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        // Redirect back to the patient file or visits page after deletion
        header("Location: patient-file.php?id=" . $patient_id);
        exit();
    } else {
        echo "Error deleting record: " . mysqli_error($con);
    }

    // Close the statement
    mysqli_stmt_close($stmt);
}
// Close the database connection
mysqli_close($con);
?>