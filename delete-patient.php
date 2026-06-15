<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

clinic_require_csrf();

if (!isset($_POST['id_delete'])) {
    http_response_code(400);
    exit('Missing patient id.');
}

$id_delete = (int) $_POST['id_delete'];
if ($id_delete <= 0) {
    http_response_code(400);
    exit('Invalid patient id.');
}

$wantsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

function delete_patient_response(bool $success, string $message, bool $wantsJson): void
{
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    clinic_set_flash($success ? 'success' : 'error', $message);
    header('Location: main.php');
    exit;
}

$old_stmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ?");
mysqli_stmt_bind_param($old_stmt, "i", $id_delete);
mysqli_stmt_execute($old_stmt);
$old_row = mysqli_fetch_assoc(mysqli_stmt_get_result($old_stmt));

$deleted_by = clinic_current_user();
$stmt = mysqli_prepare($con, "UPDATE add_patient SET is_deleted = 1, deleted_at = NOW(), deleted_by = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $deleted_by, $id_delete);
$result_delete = mysqli_stmt_execute($stmt);

if ($result_delete) {
    clinic_audit($con, 'soft_delete', 'add_patient', $id_delete, $old_row, [
        'is_deleted' => 1,
        'deleted_by' => $deleted_by,
    ]);
    delete_patient_response(true, 'تم نقل المريض إلى الأرشيف بنجاح', $wantsJson);
} else {
    delete_patient_response(false, 'فشل حذف بيانات المريض', $wantsJson);
}
