<?php

include 'config.php';
include 'auth.php';

$followup_id = $_GET['id'];
$patient_id = $_GET['patient_id'];
mysqli_query($con, "UPDATE followups SET status = 'done' WHERE id = '$followup_id'");
header("Location: patient-data.php?id=$patient_id");
exit;
?>