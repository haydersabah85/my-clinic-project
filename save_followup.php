<?php


include 'config.php';
include 'auth.php';

$patient_id = $_POST['patient_id'];
$followup_date = $_POST['followup_date'];
$reason = $_POST['followup_reason'];
$syncFields = $IS_LOCAL ? ", sync_status" : "";
$syncValues = $IS_LOCAL ? ", 0" : "";


// حساب عدد المتابعات في نفس اليوم
$query = "SELECT COUNT(*) as total 
          FROM followups 
          WHERE followup_date = '$followup_date'";

$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

if ($row['total'] >= 3) {

    // ❌ منع الإضافة
    echo "<script>
        alert('تم الوصول للحد الأقصى من المتابعات لهذا اليوم (3)');
        window.history.back();
    </script>";
    exit();
}

mysqli_query($con,"
INSERT INTO followups (patient_id, followup_date, followup_reason, updated_at $syncFields)
VALUES ('$patient_id', '$followup_date', '$reason', NOW() $syncValues)
");

header("Location: patient-file.php?id=$patient_id");
exit;
?>



