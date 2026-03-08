<?php
include "config.php";

$id = $_GET['id'];

$stmt = $con->prepare("
    UPDATE expected_appointments
    SET status = 'not_arrived'
    WHERE id = ?
");
$stmt->execute([$id]);

header("Location: expected_appointments.php");
