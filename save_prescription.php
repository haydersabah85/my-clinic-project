<?php
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);


$patient_id = (int) ($_POST['patient_id'] ?? 0);
$diagnosis = trim($_POST['diagnosis'] ?? '');
$followup_date = trim($_POST['followup_date'] ?? '');
$followup_reason = trim($_POST['followup_reason'] ?? '');
$followup_note = trim($_POST['followup_note'] ?? '');
$syncFields = $IS_LOCAL ? ", sync_status" : "";
$syncValues = $IS_LOCAL ? ", 0" : "";


if (empty($patient_id)) {
    die("خطأ: لم يتم تحديد المريض");
}

$check_stmt = mysqli_prepare($con, "SELECT id FROM add_patient WHERE id = ?");
mysqli_stmt_bind_param($check_stmt, "i", $patient_id);
mysqli_stmt_execute($check_stmt);
$check = mysqli_stmt_get_result($check_stmt);
if (mysqli_num_rows($check) == 0) {
    die("خطأ: المريض غير موجود في قاعدة البيانات");
}

mysqli_begin_transaction($con);

try {
    $insert_prescription = mysqli_prepare($con, "
        INSERT INTO prescriptions (patient_id, diagnosis, followup_id, next_followup_date, next_followup_reason, next_followup_note, updated_at $syncFields)
        VALUES (?, ?, NULL, NULL, NULL, NULL, NOW() $syncValues)
    ");
    mysqli_stmt_bind_param($insert_prescription, "is", $patient_id, $diagnosis);
    mysqli_stmt_execute($insert_prescription);

    $prescription_id = mysqli_insert_id($con);
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $doses = $_POST['dose'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $eyes = $_POST['eye'] ?? [];
    $instructions_list = $_POST['instructions'] ?? [];

    if (!empty($IS_LOCAL)) {
        $insert_item = mysqli_prepare($con, "
            INSERT INTO prescription_items
                (prescription_id, medicine_id, dose, frequency, duration, eye, instructions, updated_at, sync_status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW(), 0)
        ");
    } else {
        $insert_item = mysqli_prepare($con, "
            INSERT INTO prescription_items
                (prescription_id, medicine_id, dose, frequency, duration, eye, instructions, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
    }

    $has_items = false;
    foreach ($medicine_ids as $index => $medicine_id) {
        $medicine_id = (int) $medicine_id;
        if ($medicine_id <= 0) {
            continue;
        }

        $dose = trim($doses[$index] ?? '');
        $frequency = trim($frequencies[$index] ?? '');
        $duration = trim($durations[$index] ?? '');
        $eye = trim($eyes[$index] ?? '');
        $instructions = trim($instructions_list[$index] ?? '');

        mysqli_stmt_bind_param(
            $insert_item,
            "iisssss",
            $prescription_id,
            $medicine_id,
            $dose,
            $frequency,
            $duration,
            $eye,
            $instructions
        );
        mysqli_stmt_execute($insert_item);
        $has_items = true;
    }

    if (!$has_items) {
        throw new InvalidArgumentException("يجب اختيار دواء واحد على الأقل");
    }

    $followup_id = clinic_sync_prescription_followup(
        $con,
        $patient_id,
        $prescription_id,
        0,
        $followup_date,
        $followup_reason,
        $followup_note,
        !empty($IS_LOCAL)
    );

    if (!empty($IS_LOCAL)) {
        $update_prescription = mysqli_prepare($con, "
            UPDATE prescriptions
            SET followup_id = ?, next_followup_date = ?, next_followup_reason = ?, next_followup_note = ?, updated_at = NOW(), sync_status = 0
            WHERE id = ?
        ");
    } else {
        $update_prescription = mysqli_prepare($con, "
            UPDATE prescriptions
            SET followup_id = ?, next_followup_date = ?, next_followup_reason = ?, next_followup_note = ?, updated_at = NOW()
            WHERE id = ?
        ");
    }
    $followup_id_or_null = $followup_id > 0 ? $followup_id : null;
    $followup_date_or_null = $followup_date !== '' ? $followup_date : null;
    $followup_reason_or_null = $followup_reason !== '' ? $followup_reason : null;
    $followup_note_or_null = $followup_note !== '' ? $followup_note : null;
    mysqli_stmt_bind_param(
        $update_prescription,
        "isssi",
        $followup_id_or_null,
        $followup_date_or_null,
        $followup_reason_or_null,
        $followup_note_or_null,
        $prescription_id
    );
    mysqli_stmt_execute($update_prescription);

    clinic_audit($con, 'create', 'prescriptions', $prescription_id, null, [
        'patient_id' => $patient_id,
        'diagnosis' => $diagnosis,
        'next_followup_date' => $followup_date_or_null,
        'next_followup_reason' => $followup_reason_or_null,
    ]);

    mysqli_commit($con);
} catch (Throwable $e) {
    mysqli_rollback($con);
    die("حدث خطأ أثناء حفظ الوصفة: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

header("Location: view_prescription.php?id=" . $prescription_id);
exit;
