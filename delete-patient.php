<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if (!isset($_GET['id_delete'])) {
    echo "<script>window.location.href='main.php';</script>";
    exit;
}

$id_delete = (int) $_GET['id_delete'];

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
    echo "<script>alert('تم نقل المريض إلى الأرشيف بنجاح');</script>";
    echo "<script>window.location.href='main.php';</script>";
} else {
    echo "<script>alert('فشل حذف بيانات المريض');</script>";
    echo "<script>window.location.href='main.php';</script>";
}
