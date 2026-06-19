<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = '%' . $q . '%';
$activeWhere = clinic_active_patient_where($con, 'p');

$stmt = mysqli_prepare($con, "
    SELECT p.id, p.full_name, p.phone_no, p.age
    FROM add_patient p
    WHERE $activeWhere
      AND (
        CAST(p.id AS CHAR) LIKE ?
        OR p.full_name LIKE ?
        OR p.phone_no LIKE ?
      )
    ORDER BY p.full_name ASC
    LIMIT 15
");

if (!$stmt) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($result && ($row = mysqli_fetch_assoc($result))) {
    $items[] = [
        'id' => (int) ($row['id'] ?? 0),
        'full_name' => (string) ($row['full_name'] ?? ''),
        'phone_no' => (string) ($row['phone_no'] ?? ''),
        'age' => (string) ($row['age'] ?? ''),
    ];
}

echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
