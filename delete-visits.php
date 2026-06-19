<?php
include 'config.php';
include_once 'clinic_helpers.php';

if (isset($_GET['id_delete'])) {
    $visit_id = $_GET['id_delete'];

    // Prepare and execute the delete statement
    $stmt = $con->prepare("DELETE FROM visits WHERE visit_id = ?");
    $stmt->bind_param("i", $visit_id);

    if ($stmt->execute()) {
        clinic_log_deleted_record($con, 'visits', (int) $visit_id);
        // Redirect back to the visits page after deletion
        header("Location: visits.php");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
}
