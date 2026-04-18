<?php
include "config.php";
$id = $_GET['id'];
$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

$con->query("UPDATE add_patient SET is_critical=1, updated_at = NOW() $syncPart WHERE id=$id");

header("Location: patient-file.php?id=$id");
