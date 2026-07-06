<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_referred_case'])) {
    header('Location: referred-cases.php');
    exit;
}

clinic_require_csrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    clinic_set_flash('error', 'معرف الحالة غير صالح.');
    header('Location: referred-cases.php');
    exit;
}

$patientFullName = trim((string) ($_POST['patient_full_name'] ?? ''));
$patientAge = trim((string) ($_POST['patient_age'] ?? ''));
$patientPhone = trim((string) ($_POST['patient_phone'] ?? ''));
$patientCity = trim((string) ($_POST['patient_city'] ?? ''));

$referringDoctorName = trim((string) ($_POST['referring_doctor_name'] ?? ''));
$referringDoctorClinic = trim((string) ($_POST['referring_doctor_clinic'] ?? ''));
$referringDoctorPhone = trim((string) ($_POST['referring_doctor_phone'] ?? ''));
$referralDate = trim((string) ($_POST['referral_date'] ?? ''));

$surgeryDate = trim((string) ($_POST['surgery_date'] ?? ''));
$surgeryType = trim((string) ($_POST['surgery_type'] ?? ''));
$eye = trim((string) ($_POST['eye'] ?? ''));
$surgeonName = trim((string) ($_POST['surgeon_name'] ?? ''));
$anesthesiaType = trim((string) ($_POST['anesthesia_type'] ?? ''));
$materialsUsed = trim((string) ($_POST['materials_used'] ?? ''));
$operationNotes = trim((string) ($_POST['operation_notes'] ?? ''));
$postopInstructions = trim((string) ($_POST['postop_instructions'] ?? ''));
$followupPlan = trim((string) ($_POST['followup_plan'] ?? ''));
$followupDestination = trim((string) ($_POST['followup_destination'] ?? 'unknown'));

if ($patientFullName === '' || $referringDoctorName === '' || $surgeryDate === '' || $surgeryType === '') {
    clinic_set_flash('error', 'يرجى ملء الحقول الأساسية المطلوبة.');
    header('Location: edit-referred-case.php?id=' . $id);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $surgeryDate)) {
    clinic_set_flash('error', 'تاريخ العملية غير صحيح.');
    header('Location: edit-referred-case.php?id=' . $id);
    exit;
}

if ($referralDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $referralDate)) {
    $referralDate = '';
}

$allowedFollowup = ['clinic', 'referrer', 'unknown'];
if (!in_array($followupDestination, $allowedFollowup, true)) {
    $followupDestination = 'unknown';
}

$checkStmt = mysqli_prepare($con, 'SELECT id FROM referred_surgery_cases WHERE id = ? LIMIT 1');
if (!$checkStmt) {
    clinic_set_flash('error', 'تعذر التحقق من الحالة.');
    header('Location: referred-cases.php');
    exit;
}
mysqli_stmt_bind_param($checkStmt, 'i', $id);
mysqli_stmt_execute($checkStmt);
$checkRes = mysqli_stmt_get_result($checkStmt);
$exists = $checkRes && mysqli_fetch_assoc($checkRes);
mysqli_stmt_close($checkStmt);

if (!$exists) {
    clinic_set_flash('error', 'الحالة غير موجودة.');
    header('Location: referred-cases.php');
    exit;
}

$eyeValue = $eye !== '' ? $eye : null;
$referralDateValue = $referralDate !== '' ? $referralDate : null;
$syncStatus = $IS_LOCAL ? 0 : 1;

$update = mysqli_prepare(
    $con,
    'UPDATE referred_surgery_cases
     SET patient_full_name = ?,
         patient_age = ?,
         patient_phone = ?,
         patient_city = ?,
         referring_doctor_name = ?,
         referring_doctor_clinic = ?,
         referring_doctor_phone = ?,
         referral_date = ?,
         surgery_date = ?,
         surgery_type = ?,
         eye = ?,
         surgeon_name = ?,
         anesthesia_type = ?,
         materials_used = ?,
         operation_notes = ?,
         postop_instructions = ?,
         followup_plan = ?,
         followup_destination = ?,
         sync_status = ?,
         updated_at = NOW()
     WHERE id = ?'
);

if (!$update) {
    clinic_set_flash('error', 'تعذر تجهيز التعديل.');
    header('Location: edit-referred-case.php?id=' . $id);
    exit;
}

mysqli_stmt_bind_param(
    $update,
    'ssssssssssssssssssii',
    $patientFullName,
    $patientAge,
    $patientPhone,
    $patientCity,
    $referringDoctorName,
    $referringDoctorClinic,
    $referringDoctorPhone,
    $referralDateValue,
    $surgeryDate,
    $surgeryType,
    $eyeValue,
    $surgeonName,
    $anesthesiaType,
    $materialsUsed,
    $operationNotes,
    $postopInstructions,
    $followupPlan,
    $followupDestination,
    $syncStatus,
    $id
);

if (!mysqli_stmt_execute($update)) {
    mysqli_stmt_close($update);
    clinic_set_flash('error', 'فشل حفظ التعديلات. حاول مرة أخرى.');
    header('Location: edit-referred-case.php?id=' . $id);
    exit;
}
mysqli_stmt_close($update);

clinic_audit($con, 'update', 'referred_surgery_cases', $id, null, [
    'patient_full_name' => $patientFullName,
    'referring_doctor_name' => $referringDoctorName,
    'surgery_type' => $surgeryType,
    'surgery_date' => $surgeryDate,
]);

clinic_set_flash('success', 'تم تحديث الحالة المحولة بنجاح.');
header('Location: referred-cases.php');
exit;
