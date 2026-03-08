<?php

include 'config.php';

include 'auth.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the DELETE statement
    $stmt = $con->prepare("DELETE FROM injection_appointment WHERE id = ?");

    // Bind the parameter
    $stmt->bind_param("i", $id);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to the appointments list page after deletion
        echo "<script>alert('تم حذف الموعد بنجاح');</script>";
        echo "<script>window.location.href = 'operation-by-date.php';</script>";
    } else {
        echo "Error deleting record: " . $con->error;
    }

    // Close the statement
    $stmt->close();
}
$con->close();
?>