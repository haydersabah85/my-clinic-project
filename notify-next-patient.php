<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

clinic_require_csrf();

$action = trim((string) ($_POST['action'] ?? 'set'));
$back = trim((string) ($_POST['back'] ?? 'work-queue.php'));

if ($back === '') {
    $back = 'work-queue.php';
}

$backPath = basename((string) (parse_url($back, PHP_URL_PATH) ?: 'work-queue.php'));
if (!in_array($backPath, ['work-queue.php', 'dashboard.php'], true)) {
    $backPath = 'work-queue.php';
}
$safeBack = $backPath;
$backQuery = [];
parse_str((string) parse_url($back, PHP_URL_QUERY), $backQuery);
$backDate = trim((string) ($backQuery['date'] ?? ''));
if ($backPath === 'work-queue.php' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $backDate)) {
    $safeBack .= '?date=' . rawurlencode($backDate);
}

if ($action === 'clear') {
    clinic_set_app_setting($con, 'doctor_next_patient_alert', '');
    clinic_audit($con, 'clear_next_patient_alert', 'app_settings', null, null, ['cleared_by' => clinic_current_user()]);
    clinic_set_flash('success', 'تم مسح تنبيه المريض القادم.');
    header('Location: ' . $safeBack);
    exit;
}

$patientId = (int) ($_POST['patient_id'] ?? 0);
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

$queue = trim((string) ($_POST['queue'] ?? 'العيادة'));
$meta = trim((string) ($_POST['meta'] ?? ''));

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
clinic_set_flash('success', 'تم إرسال تنبيه المريض القادم إلى الطبيب.');

header('Location: ' . $safeBack);
exit;
