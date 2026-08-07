<?php
include "config.php";
include "auth.php";
include_once "clinic_helpers.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$from = trim((string) ($_GET['from'] ?? ''));
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

$stmt = $con->prepare("SELECT is_critical FROM add_patient WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    $currentCritical = !empty($row['is_critical']);
    $newCritical = $currentCritical ? 0 : 1;

    $updateStmt = $con->prepare("UPDATE add_patient SET is_critical = ?, updated_at = NOW() $syncPart WHERE id = ?");
    $updateStmt->bind_param('ii', $newCritical, $id);
    $updateStmt->execute();
}

$redirectTo = 'patient-file.php?id=' . $id;
if ($from === 'critical_list') {
    $redirectTo = 'critical_patients.php';
}

header('Location: ' . $redirectTo);
