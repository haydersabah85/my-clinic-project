<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);

$action = trim((string) ($_GET['action'] ?? 'set'));
$back = trim((string) ($_GET['back'] ?? 'work-queue.php'));

if ($back === '') {
    $back = 'work-queue.php';
}

$backPath = parse_url($back, PHP_URL_PATH) ?: 'work-queue.php';
$backQuery = parse_url($back, PHP_URL_QUERY);
$safeBack = $backPath . ($backQuery ? ('?' . $backQuery) : '');

if ($action === 'clear') {
    clinic_set_app_setting($con, 'doctor_next_patient_alert', '');
    clinic_audit($con, 'clear_next_patient_alert', 'app_settings', null, null, ['cleared_by' => clinic_current_user()]);
    header('Location: ' . $safeBack);
    exit;
}

$patientId = (int) ($_GET['patient_id'] ?? 0);
if ($patientId <= 0) {
    header('Location: ' . $safeBack);
    exit;
}

$stmt = mysqli_prepare($con, "SELECT id, full_name, phone_no FROM add_patient WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $patientId);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$patient) {
    header('Location: ' . $safeBack);
    exit;
}

$queue = trim((string) ($_GET['queue'] ?? 'العيادة'));
$meta = trim((string) ($_GET['meta'] ?? ''));

$payload = [
    'patient_id' => (int) $patient['id'],
    'full_name' => (string) ($patient['full_name'] ?? ''),
    'phone_no' => (string) ($patient['phone_no'] ?? ''),
    'queue' => $queue,
    'meta' => $meta,
    'notified_at' => date('Y-m-d H:i:s'),
    'notified_by' => clinic_current_user(),
];

clinic_set_app_setting($con, 'doctor_next_patient_alert', json_encode($payload, JSON_UNESCAPED_UNICODE));
clinic_audit($con, 'set_next_patient_alert', 'app_settings', (int) $patient['id'], null, $payload);

header('Location: ' . $safeBack);
exit;
