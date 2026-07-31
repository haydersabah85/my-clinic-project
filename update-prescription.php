<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$prescription_id = isset($_POST['prescription_id']) ? (int) $_POST['prescription_id'] : 0;
$patient_id = isset($_POST['patient_id']) ? (int) $_POST['patient_id'] : 0;
$diagnosis = trim($_POST['diagnosis'] ?? '');
$followup_date = trim($_POST['followup_date'] ?? '');
$followup_reason = trim($_POST['followup_reason'] ?? '');
$followup_note = trim($_POST['followup_note'] ?? '');
$followup_type = strtolower(trim((string) ($_POST['followup_type'] ?? 'review')));

if ($prescription_id <= 0 || $patient_id <= 0) {
    die("خطأ: بيانات الوصفة غير مكتملة");
}

$check_stmt = mysqli_prepare($con, "SELECT id, followup_id FROM prescriptions WHERE id = ? AND patient_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $prescription_id, $patient_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    die("خطأ: الوصفة غير موجودة");
}

$existing_prescription = mysqli_fetch_assoc($check_result);

mysqli_begin_transaction($con);

try {
    $followup_id = clinic_sync_prescription_followup(
        $con,
        $patient_id,
        $prescription_id,
        (int) ($existing_prescription['followup_id'] ?? 0),
        $followup_date,
        $followup_reason,
        $followup_note,
        $followup_type,
        !empty($IS_LOCAL)
    );

    if (!empty($IS_LOCAL)) {
        $prescription_stmt = mysqli_prepare($con, "
            UPDATE prescriptions
            SET diagnosis = ?, followup_id = ?, next_followup_date = ?, next_followup_reason = ?, next_followup_note = ?, updated_at = NOW(), sync_status = 0
            WHERE id = ? AND patient_id = ?
        ");
        $followup_id_or_null = $followup_id > 0 ? $followup_id : null;
        $followup_date_or_null = $followup_date !== '' ? $followup_date : null;
        $followup_reason_or_null = $followup_reason !== '' ? $followup_reason : null;
        $followup_note_or_null = $followup_note !== '' ? $followup_note : null;
        mysqli_stmt_bind_param($prescription_stmt, "sisssii", $diagnosis, $followup_id_or_null, $followup_date_or_null, $followup_reason_or_null, $followup_note_or_null, $prescription_id, $patient_id);
    } else {
        $prescription_stmt = mysqli_prepare($con, "
            UPDATE prescriptions
            SET diagnosis = ?, followup_id = ?, next_followup_date = ?, next_followup_reason = ?, next_followup_note = ?, updated_at = NOW()
            WHERE id = ? AND patient_id = ?
        ");
        $followup_id_or_null = $followup_id > 0 ? $followup_id : null;
        $followup_date_or_null = $followup_date !== '' ? $followup_date : null;
        $followup_reason_or_null = $followup_reason !== '' ? $followup_reason : null;
        $followup_note_or_null = $followup_note !== '' ? $followup_note : null;
        mysqli_stmt_bind_param($prescription_stmt, "sisssii", $diagnosis, $followup_id_or_null, $followup_date_or_null, $followup_reason_or_null, $followup_note_or_null, $prescription_id, $patient_id);
    }
    mysqli_stmt_execute($prescription_stmt);

    $delete_stmt = mysqli_prepare($con, "DELETE FROM prescription_items WHERE prescription_id = ?");
    mysqli_stmt_bind_param($delete_stmt, "i", $prescription_id);
    mysqli_stmt_execute($delete_stmt);

    $medicine_ids = $_POST['medicine_id'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $doses = $_POST['dose'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $eyes = $_POST['eye'] ?? [];
    $instructions_list = $_POST['instructions'] ?? [];

    if (!empty($IS_LOCAL)) {
        $insert_stmt = mysqli_prepare($con, "
            INSERT INTO prescription_items
                (prescription_id, medicine_id, dose, frequency, duration, eye, instructions, updated_at, sync_status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW(), 0)
        ");
    } else {
        $insert_stmt = mysqli_prepare($con, "
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
            $insert_stmt,
            "iisssss",
            $prescription_id,
            $medicine_id,
            $dose,
            $frequency,
            $duration,
            $eye,
            $instructions
        );
        mysqli_stmt_execute($insert_stmt);
        $has_items = true;
    }

    if (!$has_items) {
        throw new Exception("يجب اختيار دواء واحد على الأقل");
    }

    mysqli_commit($con);
    header("Location: view_prescription.php?id=" . $prescription_id);
    exit;
} catch (Throwable $e) {
    mysqli_rollback($con);
    die("حدث خطأ أثناء تحديث الوصفة: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
