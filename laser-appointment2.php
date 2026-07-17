<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
$patientId = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit_laser'])) {
    http_response_code(405);
    exit('Method not allowed.');
}

clinic_require_csrf();
$eye = trim((string) ($_POST['eye'] ?? ''));
$type = trim((string) ($_POST['laser_type'] ?? ''));
$phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
$phoneAlt = preg_replace('/\D+/', '', (string) ($_POST['phone_alt'] ?? ''));
$date = trim((string) ($_POST['date'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));
$validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;

if ($patientId <= 0 || !in_array($eye, ['OD', 'OS', 'OU'], true) || $type === '' || $phone === '' || !$validDate) {
    clinic_set_flash('error', 'يرجى إكمال نوع الليزر والعين والهاتف والتاريخ بصورة صحيحة.');
    header('Location: laser-appointment.php?id=' . $patientId);
    exit;
}

mysqli_begin_transaction($con);
try {
    $serialStmt = mysqli_prepare($con, 'SELECT COALESCE(MAX(serial_no), 0) + 1 next_serial FROM laser_appointment WHERE date = ? FOR UPDATE');
    mysqli_stmt_bind_param($serialStmt, 's', $date);
    mysqli_stmt_execute($serialStmt);
    $serial = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($serialStmt))['next_serial'] ?? 1);

    $syncPart = $IS_LOCAL ? ', sync_status = 0' : '';
    $phoneStmt = mysqli_prepare($con, "UPDATE add_patient SET phone_no = ?, phone_no_alt = ?, updated_at = NOW() $syncPart WHERE id = ?");
    mysqli_stmt_bind_param($phoneStmt, 'ssi', $phone, $phoneAlt, $patientId);
    mysqli_stmt_execute($phoneStmt);

    $syncField = $IS_LOCAL ? ', sync_status' : '';
    $syncValue = $IS_LOCAL ? ', 0' : '';
    $stmt = mysqli_prepare($con, "INSERT INTO laser_appointment (patient_id, eye, laser_type, phone, phone_alt, date, notes, serial_no, updated_at $syncField) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() $syncValue)");
    mysqli_stmt_bind_param($stmt, 'issssssi', $patientId, $eye, $type, $phone, $phoneAlt, $date, $notes, $serial);
    mysqli_stmt_execute($stmt);
    $appointmentId = mysqli_insert_id($con);
    clinic_audit($con, 'create', 'laser_appointment', $appointmentId, null, ['patient_id' => $patientId, 'date' => $date, 'eye' => $eye, 'laser_type' => $type]);
    mysqli_commit($con);
    clinic_set_flash('success', 'تم حجز موعد الليزر بنجاح.');
    header('Location: patient-file.php?id=' . $patientId);
} catch (Throwable $e) {
    mysqli_rollback($con);
    clinic_set_flash('error', 'تعذر حجز موعد الليزر. يرجى المحاولة مرة أخرى.');
    header('Location: laser-appointment.php?id=' . $patientId);
}
exit;
