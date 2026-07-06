<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_referred_case'])) {
    header('Location: add-referred-case.php');
    exit;
}

clinic_require_csrf();

$caseUuid = bin2hex(random_bytes(16));
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
$createdBy = clinic_current_user();

if ($patientFullName === '' || $referringDoctorName === '' || $surgeryDate === '' || $surgeryType === '') {
    clinic_set_flash('error', 'يرجى ملء الحقول الأساسية: اسم المريض، الطبيب المحول، نوع العملية، تاريخ العملية.');
    header('Location: add-referred-case.php');
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $surgeryDate)) {
    clinic_set_flash('error', 'تاريخ العملية غير صحيح.');
    header('Location: add-referred-case.php');
    exit;
}

if ($referralDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $referralDate)) {
    $referralDate = '';
}

$allowedFollowup = ['clinic', 'referrer', 'unknown'];
if (!in_array($followupDestination, $allowedFollowup, true)) {
    $followupDestination = 'unknown';
}

$syncStatus = $IS_LOCAL ? 0 : 1;
$insert = mysqli_prepare(
    $con,
    'INSERT INTO referred_surgery_cases (
        case_uuid,
        patient_full_name,
        patient_age,
        patient_phone,
        patient_city,
        referring_doctor_name,
        referring_doctor_clinic,
        referring_doctor_phone,
        referral_date,
        surgery_date,
        surgery_type,
        eye,
        surgeon_name,
        anesthesia_type,
        materials_used,
        operation_notes,
        postop_instructions,
        followup_plan,
        followup_destination,
        created_by,
        sync_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

if (!$insert) {
    clinic_set_flash('error', 'تعذر تجهيز حفظ البيانات.');
    header('Location: add-referred-case.php');
    exit;
}

$referralDateValue = $referralDate !== '' ? $referralDate : null;
$eyeValue = $eye !== '' ? $eye : null;

mysqli_stmt_bind_param(
    $insert,
    'ssssssssssssssssssssi',
    $caseUuid,
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
    $createdBy,
    $syncStatus
);

if (!mysqli_stmt_execute($insert)) {
    mysqli_stmt_close($insert);
    clinic_set_flash('error', 'فشل حفظ الحالة المحولة. حاول مرة أخرى.');
    header('Location: add-referred-case.php');
    exit;
}

$newId = (int) mysqli_insert_id($con);
mysqli_stmt_close($insert);

clinic_audit($con, 'create', 'referred_surgery_cases', $newId, null, [
    'patient_full_name' => $patientFullName,
    'referring_doctor_name' => $referringDoctorName,
    'surgery_type' => $surgeryType,
    'surgery_date' => $surgeryDate,
]);

clinic_set_flash('success', 'تم حفظ الحالة المحولة بنجاح.');
header('Location: referred-cases.php');
exit;
