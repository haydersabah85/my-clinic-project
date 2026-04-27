<?php
include 'config.php';

if (isset($_GET['visit_id'])) {
    $visit_id = $_GET['visit_id'];
    $date = date("Y-m-d");
    $visit_type = $_GET['visit_type'];
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
        $update_visit_type_query = "
        UPDATE visits SET 
        visit_type='$visit_type', 
        visit_date='$date',
        updated_at=NOW()$syncPart 
        WHERE visit_id='$visit_id'";
        
        $result= mysqli_query($con, $update_visit_type_query);

if ($result) {
            echo "<script>alert('تم تحديث نوع الزيارة بنجاح.');</script>";
            echo "<script>window.location.href = 'visits.php?id=" . $visit_id . "';</script>";
        } else {
            echo "خطأ: " . mysqli_error($con);
        }
    }
    else {
        echo "Error: visit_id not provided.";
    }