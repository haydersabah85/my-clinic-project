<?php
include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_visit_type_support($con);

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    $today = date('Y-m-d');
    $type = (string) ($_GET['visit_type'] ?? 'repeat');
    $allowedTypes = ['first', 'repeat', 'free', 'charity'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'repeat';
    }

    $isNoFeeVisit = in_array($type, ['free', 'charity'], true);
    $isPaid = $isNoFeeVisit ? 1 : 0;
    $paidAt = $isNoFeeVisit ? date('Y-m-d H:i:s') : null;
    $paidBy = $isNoFeeVisit ? 'NO_FEE' : null;
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    $sql_serial = "SELECT MAX(daily_serial) AS max_serial FROM visits WHERE visit_date = '$today'";
    $result_serial = mysqli_query($con, $sql_serial);
    $row_serial = mysqli_fetch_assoc($result_serial);

    if ($row_serial['max_serial']) {
        $daily_serial = $row_serial['max_serial'] + 1;
    } else {
        $daily_serial = 1;
    }

    clinic_ensure_column($con, 'visits', 'is_paid', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'visits', 'paid_at', 'DATETIME NULL');
    clinic_ensure_column($con, 'visits', 'paid_by', 'VARCHAR(120) NULL');

    $insert_query = "INSERT into visits (patient_id, visit_date, visit_type, daily_serial, is_paid, paid_at, paid_by, updated_at $syncFields) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW() $syncValues)";

    $stmt = $con->prepare($insert_query);
    $stmt->bind_param("issiiss", $patient_id, $today, $type, $daily_serial, $isPaid, $paidAt, $paidBy);

    try {
        $stmt->execute();
        echo
        "<script>alert('تم اضافة الزيارة بنجاح.');</script>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    $stmt->close();
} else {
    echo "Error: patient_id not provided.";
}
mysqli_close($con);

header("Location: visits.php?id=$patient_id");
exit();
