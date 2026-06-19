<?php
session_start();

include_once 'config.php';
include_once 'clinic_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit;
}

$currentScript = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$requiredPermissions = clinic_required_permissions_for_script($currentScript);
if (!empty($requiredPermissions)) {
    clinic_require_permissions($requiredPermissions);
}

if (isset($con) && isset($IS_LOCAL)) {
    clinic_enforce_runtime_write_policy($con, (bool) $IS_LOCAL);
    clinic_auto_pull_tick($con, (bool) $IS_LOCAL);
}
