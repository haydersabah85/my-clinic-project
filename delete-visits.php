<?php
include 'config.php';

if (isset($_GET['id_delete'])) {
    $visit_id = $_GET['id_delete'];

    // Prepare and execute the delete statement
    $stmt = $con->prepare("DELETE FROM visits WHERE visit_id = ?");
    $stmt->bind_param("i", $visit_id);
    
    if ($stmt->execute()) {
        // Redirect back to the visits page after deletion
        header("Location: visits.php");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }
    
    $stmt->close();
}
