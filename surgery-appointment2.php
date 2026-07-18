<?php
include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_surgery'])) {
    clinic_require_csrf();
    $patient_id = (int) ($_GET['id'] ?? 0);

    $eye = trim((string) ($_POST['eye'] ?? ''));
    $surgery_type = trim((string) ($_POST['surgery_type'] ?? ''));
    $phone = clinic_sanitize_phone((string) ($_POST['phone'] ?? ''));
    $phone_alt = clinic_sanitize_phone((string) ($_POST['phone_alt'] ?? ''));
    $date = clinic_normalize_digits(trim((string) ($_POST['date'] ?? '')));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    if ($patient_id <= 0 || !in_array($eye, ['OD', 'OS', 'OU'], true) || $surgery_type === '' || $phone === '' || !$validDate) {
        clinic_set_flash('error', 'يرجى إكمال نوع العملية والعين والهاتف والتاريخ بصورة صحيحة.');
        header('Location: surgery-appointment.php?id=' . $patient_id);
        exit;
    }
    $readinessKeys = [
        'patient_verified',
        'eye_verified',
        'procedure_verified',
        'consent_ready',
        'iol_ready',
        'allergy_checked',
        'investigations_ready',
        'payment_reviewed'
    ];
    $readiness = [];
    foreach ($readinessKeys as $key) {
        $readiness[$key] = !empty($_POST['readiness'][$key]);
    }
    $readinessJson = json_encode($readiness, JSON_UNESCAPED_UNICODE);
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    $serialStmt = mysqli_prepare($con, "SELECT MAX(serial_no) AS max_serial FROM surgery_appointment WHERE date = ?");
    mysqli_stmt_bind_param($serialStmt, 's', $date);
    mysqli_stmt_execute($serialStmt);
    $result_serial = mysqli_stmt_get_result($serialStmt);
    $row_serial = mysqli_fetch_assoc($result_serial);

    if ($row_serial['max_serial']) {
        $serial_no = $row_serial['max_serial'] + 1;
    } else {
        $serial_no = 1;
    }

    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    $phoneStmt = mysqli_prepare($con, "UPDATE add_patient SET phone_no = ?, phone_no_alt = ?, updated_at = NOW() $syncPart WHERE id = ?");
    mysqli_stmt_bind_param($phoneStmt, 'ssi', $phone, $phone_alt, $patient_id);
    mysqli_stmt_execute($phoneStmt);

    $insert_query = "INSERT INTO surgery_appointment
        (patient_id, eye, surgery_type, phone, phone_alt, date, notes, serial_no, readiness_json, updated_at $syncFields)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() $syncValues)";
    $stmt = mysqli_prepare($con, $insert_query);
    mysqli_stmt_bind_param(
        $stmt,
        'issssssis',
        $patient_id,
        $eye,
        $surgery_type,
        $phone,
        $phone_alt,
        $date,
        $notes,
        $serial_no,
        $readinessJson
    );

    if (mysqli_stmt_execute($stmt)) {
        $appointmentId = mysqli_insert_id($con);
        clinic_audit($con, 'create', 'surgery_appointment', $appointmentId, null, [
            'patient_id' => $patient_id,
            'date' => $date,
            'readiness' => $readiness,
        ]);
        clinic_set_flash('success', 'تم حجز موعد العملية بنجاح.');
        header('Location: patient-file.php?id=' . $patient_id);
        exit;
    } else {
        clinic_set_flash('error', 'تعذر حجز موعد العملية.');
        header('Location: surgery-appointment.php?id=' . $patient_id);
        exit;
    }
}
