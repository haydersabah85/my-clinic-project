<?php
include "config.php";
$id = $_GET['id'];

$con->query("UPDATE add_patient SET is_critical=1 WHERE id=$id");

header("Location: patient-file.php?id=$id");
