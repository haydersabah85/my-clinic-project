<?php
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_visit_type_support($con);

if (isset($_GET['visit_id'])) {
    $visit_id = $_GET['visit_id'];
    $date = date("Y-m-d");
    $visit_type = (string) ($_GET['visit_type'] ?? 'repeat');
    $allowedTypes = ['first', 'repeat', 'free', 'charity'];
    if (!in_array($visit_type, $allowedTypes, true)) {
        $visit_type = 'repeat';
    }

    clinic_ensure_column($con, 'visits', 'is_paid', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'visits', 'paid_at', 'DATETIME NULL');
    clinic_ensure_column($con, 'visits', 'paid_by', 'VARCHAR(120) NULL');

    $isNoFeeVisit = in_array($visit_type, ['free', 'charity'], true);
    $isPaidTarget = $isNoFeeVisit ? 1 : 0;
    $paidByTarget = $isNoFeeVisit ? 'NO_FEE' : null;
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    $update_visit_type_query = "
        UPDATE visits SET 
        visit_type='$visit_type', 
        visit_date='$date',
        is_paid = ?,
        paid_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END,
        paid_by = ?,
        updated_at=NOW()$syncPart 
        WHERE visit_id='$visit_id'";

    $stmt = mysqli_prepare($con, $update_visit_type_query);
    $result = false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iis', $isPaidTarget, $isPaidTarget, $paidByTarget);
        $result = mysqli_stmt_execute($stmt);
    }

    if ($result) {
        echo "<script>alert('تم تحديث نوع الزيارة بنجاح.');</script>";
        echo "<script>window.location.href = 'visits.php?id=" . $visit_id . "';</script>";
    } else {
        echo "خطأ: " . mysqli_error($con);
    }
} else {
    echo "Error: visit_id not provided.";
}
