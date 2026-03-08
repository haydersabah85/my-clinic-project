<?php


include 'config.php';
include 'auth.php';

$patient_id = $_POST['patient_id'];
$followup_date = $_POST['followup_date'];
$reason = $_POST['followup_reason'];


mysqli_query($con,"
INSERT INTO followups (patient_id, followup_date, followup_reason)
VALUES ('$patient_id', '$followup_date', '$reason')
");

header("Location: patient-file.php?id=$patient_id");
exit;
?>



