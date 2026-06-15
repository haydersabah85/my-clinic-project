<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$name = trim($_GET['name'] ?? '');
$phone = preg_replace('/\D+/', '', $_GET['phone'] ?? '');
$age = trim($_GET['age'] ?? '');

if ($name === '' && $phone === '') {
    echo json_encode(['matches' => []]);
    exit;
}

$activeWhere = clinic_active_patient_where($con, 'add_patient');
$sql = "
    SELECT id, full_name, age, phone_no
    FROM add_patient
    WHERE $activeWhere
      AND (
        (? <> '' AND REPLACE(REPLACE(phone_no, ' ', ''), '-', '') = ?)
        OR
        (? <> '' AND full_name = ? AND (? = '' OR age = ?))
      )
    ORDER BY id DESC
    LIMIT 5
";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, 'ssssss', $phone, $phone, $name, $name, $age, $age);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$matches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $matches[] = [
        'id' => (int) $row['id'],
        'full_name' => $row['full_name'],
        'age' => $row['age'],
        'phone_no' => $row['phone_no'],
    ];
}

echo json_encode(['matches' => $matches], JSON_UNESCAPED_UNICODE);
