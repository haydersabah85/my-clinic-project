<?php
include 'config.php';
include_once 'clinic_helpers.php';

if (isset($_GET['id_delete'])) {
    $visit_id = $_GET['id_delete'];

    // First, get the visit date and daily_serial before deletion
    $getVisitStmt = $con->prepare("SELECT visit_date, daily_serial FROM visits WHERE visit_id = ?");
    $getVisitStmt->bind_param("i", $visit_id);
    $getVisitStmt->execute();
    $visitResult = $getVisitStmt->get_result();
    $visitData = $visitResult->fetch_assoc();
    $getVisitStmt->close();

    if ($visitData) {
        $visit_date = $visitData['visit_date'];
        $deleted_serial = $visitData['daily_serial'];

        // Delete the visit
        $stmt = $con->prepare("DELETE FROM visits WHERE visit_id = ?");
        $stmt->bind_param("i", $visit_id);

        if ($stmt->execute()) {
            clinic_log_deleted_record($con, 'visits', (int) $visit_id);

            // Re-sequence the daily_serial for remaining visits on the same date
            // Get all visits after the deleted one, ordered by daily_serial
            $resequenceStmt = $con->prepare("
                SELECT visit_id FROM visits 
                WHERE visit_date = ? AND daily_serial > ? 
                ORDER BY daily_serial ASC
            ");
            $resequenceStmt->bind_param("si", $visit_date, $deleted_serial);
            $resequenceStmt->execute();
            $resequenceResult = $resequenceStmt->get_result();

            // Update each visit's daily_serial to close the gap
            $new_serial = $deleted_serial;
            while ($row = $resequenceResult->fetch_assoc()) {
                $updateStmt = $con->prepare("UPDATE visits SET daily_serial = ? WHERE visit_id = ?");
                $updateStmt->bind_param("ii", $new_serial, $row['visit_id']);
                $updateStmt->execute();
                $updateStmt->close();
                $new_serial++;
            }
            $resequenceStmt->close();

            // Redirect back to the visits page after deletion
            header("Location: visits.php");
            exit();
        } else {
            echo "Error deleting record: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Visit not found";
        exit();
    }
}
