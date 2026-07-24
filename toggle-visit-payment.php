<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: visits.php');
    exit();
}

clinic_require_csrf();

clinic_ensure_visit_type_support($con);
clinic_ensure_column($con, 'visits', 'is_paid', 'TINYINT(1) NOT NULL DEFAULT 0');
clinic_ensure_column($con, 'visits', 'paid_at', 'DATETIME NULL');
clinic_ensure_column($con, 'visits', 'paid_by', 'VARCHAR(120) NULL');

$visitId = (int) ($_POST['visit_id'] ?? 0);
$isPaid = ((int) ($_POST['is_paid'] ?? 0) === 1) ? 1 : 0;
$back = trim((string) ($_POST['back'] ?? 'visits.php'));

if ($visitId <= 0) {
    header('Location: visits.php');
    exit();
}

$paidBy = clinic_current_user();
$syncPart = !empty($IS_LOCAL) ? ', sync_status = 0' : '';

$typeStmt = mysqli_prepare($con, "SELECT visit_type FROM visits WHERE visit_id = ? LIMIT 1");
$visitType = '';
if ($typeStmt) {
    mysqli_stmt_bind_param($typeStmt, 'i', $visitId);
    mysqli_stmt_execute($typeStmt);
    $typeResult = mysqli_stmt_get_result($typeStmt);
    $typeRow = $typeResult ? mysqli_fetch_assoc($typeResult) : null;
    $visitType = (string) ($typeRow['visit_type'] ?? '');
}

$isNoFeeVisit = in_array($visitType, ['free', 'charity'], true);
if ($isNoFeeVisit) {
    $isPaid = 1;
    $paidBy = 'NO_FEE';
}

$stmt = mysqli_prepare($con, "
    UPDATE visits
    SET is_paid = ?,
        paid_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END,
        paid_by = CASE WHEN ? = 1 THEN ? ELSE NULL END,
        updated_at = NOW()
        $syncPart
    WHERE visit_id = ?
    LIMIT 1
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'iiisi', $isPaid, $isPaid, $isPaid, $paidBy, $visitId);
    mysqli_stmt_execute($stmt);
}

if ($back === '' || strpos($back, 'visits.php') !== 0) {
    $back = 'visits.php';
}

header('Location: ' . $back);
exit();
