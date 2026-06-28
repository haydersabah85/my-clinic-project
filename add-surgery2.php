<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['surgery_btn'])) {
    clinic_ensure_column($con, 'surgery', 'iol_power', 'DECIMAL(4,1) NULL');

    $patient_id = intval($_POST['id']);
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $eye = $_POST['eye'];
    $surgery_type = $_POST['surgery_type'];
    $iol_type = $_POST['iol_type'];
    $iol_power_raw = trim((string) ($_POST['iol_power'] ?? ''));
    $iol_power = null;
    if ($iol_type !== '' && $iol_power_raw !== '' && is_numeric($iol_power_raw)) {
        $iol_power = round(((float) $iol_power_raw) * 2) / 2;
        if ($iol_power < -40) {
            $iol_power = -40;
        }
        if ($iol_power > 40) {
            $iol_power = 40;
        }
    }
    $iol_power_sql = $iol_power === null ? "NULL" : "'" . number_format($iol_power, 1, '.', '') . "'";
    $notes = $_POST['notes'];
    $date = $_POST['date'];
    $surgery_uuid = bin2hex(random_bytes(16));
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    $getPatient = mysqli_query($con, "
    SELECT uuid
    FROM add_patient
    WHERE id = '$patient_id'
");

    $patientData = mysqli_fetch_assoc($getPatient);
    $patient_uuid = $patientData['uuid'];

    $insert_query = "INSERT INTO surgery (patient_id, patient_uuid, surgery_uuid, eye, surgery_type, iol_type, iol_power, notes, date, updated_at $syncFields)
     VALUES ('$patient_id', '$patient_uuid', '$surgery_uuid', '$eye', '$surgery_type', '$iol_type', $iol_power_sql, '$notes', '$date', NOW() $syncValues)";
    mysqli_query($con, $insert_query);



    // Update the surgery_appointment table to mark the operation as done
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

    if ($appointment_id > 0) {
        $update_query = "UPDATE surgery_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE id = '$appointment_id' AND patient_id = '$patient_id'";
    } else {
        $update_query = "UPDATE surgery_appointment SET status = 'done', updated_at = NOW() $syncPart WHERE patient_id = '$patient_id' AND date = '$date'";
    }
    mysqli_query($con, $update_query);

    $redirect_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date) ? $appointment_date : $date;
    header("Location: operation-by-date.php?date=" . urlencode($redirect_date));

    exit();
}
