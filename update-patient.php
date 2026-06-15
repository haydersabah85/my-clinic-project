<?php

include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    clinic_require_csrf();

    $id = isset($_GET['id_edit']) ? (int) $_GET['id_edit'] : 0;
    $full_name = trim($_POST['full_name'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $phone_no_alt = trim($_POST['phone_no_alt'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

    if ($id <= 0 || $full_name === '') {
        clinic_set_flash('error', 'بيانات المريض غير مكتملة.');
        header('Location: main.php');
        exit;
    }

    $oldStmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($oldStmt, "i", $id);
    mysqli_stmt_execute($oldStmt);
    $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($oldStmt));

    $update_query = "UPDATE add_patient SET
        full_name = ?,
        age = ?,
        date_of_birth = ?,
        gender = ?,
        phone_no = ?,
        phone_no_alt = ?,
        address = ?,
        notes = ?,
        updated_at = NOW() $syncPart
        WHERE id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssi",
        $full_name,
        $age,
        $date_of_birth,
        $gender,
        $phone_no,
        $phone_no_alt,
        $address,
        $notes,
        $id
    );
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        clinic_audit($con, 'update', 'add_patient', $id, $oldRow, [
            'full_name' => $full_name,
            'phone_no' => $phone_no,
        ]);
        clinic_set_flash('success', 'تم تحديث بيانات المريض بنجاح.');
        header('Location: patient-data.php?id=' . $id);
        exit;
    }

    clinic_set_flash('error', 'تعذر تحديث بيانات المريض.');
    header('Location: edit-patient.php?id_edit=' . $id);
    exit;
}


?>
