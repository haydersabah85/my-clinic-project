<?php
session_start();

include_once 'config.php';
include_once 'clinic_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit;
}

if (isset($con) && isset($IS_LOCAL)) {
    clinic_enforce_runtime_write_policy($con, (bool) $IS_LOCAL);
}
