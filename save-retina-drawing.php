<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_retina_drawings($con);

$patient_id = (int)($_POST['patient_id'] ?? 0);
$drawing_id = (int)($_POST['drawing_id'] ?? 0);
$eye = $_POST['eye'] ?? 'OD';
$allowedEyes = ['OD', 'OS', 'OU'];
$eye = in_array($eye, $allowedEyes, true) ? $eye : 'OD';
$postedDate = $_POST['drawing_date'] ?? date('Y-m-d');
$drawing_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $postedDate) ? $postedDate : date('Y-m-d');
$title = trim((string)($_POST['title'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));
$drawing_data = (string)($_POST['drawing_data'] ?? '');
$drawing_image = (string)($_POST['drawing_image'] ?? '');

if ($patient_id <= 0) {
    die('Invalid patient.');
}

if ($drawing_image !== '' && !preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $drawing_image)) {
    die('Invalid drawing image.');
}

$patient_uuid = '';
$patientStmt = mysqli_prepare($con, "SELECT uuid FROM add_patient WHERE id = ?");
mysqli_stmt_bind_param($patientStmt, "i", $patient_id);
mysqli_stmt_execute($patientStmt);
$patientResult = mysqli_stmt_get_result($patientStmt);
if ($patientRow = mysqli_fetch_assoc($patientResult)) {
    $patient_uuid = $patientRow['uuid'] ?? '';
}

if ($drawing_id > 0) {
    $stmt = mysqli_prepare($con, "
        UPDATE retina_drawings
        SET eye = ?, drawing_date = ?, title = ?, notes = ?, drawing_data = ?, drawing_image = ?,
            patient_uuid = ?, updated_at = NOW(), sync_status = 0
        WHERE id = ? AND patient_id = ?
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "sssssssii",
        $eye,
        $drawing_date,
        $title,
        $notes,
        $drawing_data,
        $drawing_image,
        $patient_uuid,
        $drawing_id,
        $patient_id
    );
    mysqli_stmt_execute($stmt);
} else {
    $stmt = mysqli_prepare($con, "
        INSERT INTO retina_drawings
            (patient_id, patient_uuid, eye, drawing_date, title, notes, drawing_data, drawing_image, updated_at, sync_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "isssssss",
        $patient_id,
        $patient_uuid,
        $eye,
        $drawing_date,
        $title,
        $notes,
        $drawing_data,
        $drawing_image
    );
    mysqli_stmt_execute($stmt);
    $drawing_id = mysqli_insert_id($con);
}

header("Location: retina-chart.php?patient_id=$patient_id&drawing_id=$drawing_id&saved=1");
exit();
