<?php
include 'config.php';

$secret = "MY_SECRET_KEY";

// ================== FUNCTION ==================
function syncTable($con, $url, $dataKey, $tableName) {

    echo "<b>=== Sync $tableName ===</b><br>";

    if (empty($dataKey['data'])) {
        echo "No data<br><br>";
        return;
    }

    $data = [
        "auth" => "MY_SECRET_KEY",
        $dataKey['key'] => $dataKey['data']
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);   // مهلة الاتصال
curl_setopt($ch, CURLOPT_TIMEOUT, 120);         // المهلة الكلية

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // 🔁 Retry
    $maxRetries = 3;
    $attempt = 0;
    $response = false;

    while ($attempt < $maxRetries) {

        $response = curl_exec($ch);

        if ($response !== false) {
            break;
        }

        $attempt++;
        sleep(2);
    }

    if ($response === false) {
        echo "❌ Failed after retries: " . curl_error($ch) . "<br><br>";
        return;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $httpCode<br>";
    echo $response . "<br>";

    if ($httpCode != 200) {
        echo "Request failed<br><br>";
        return;
    }

    $responseData = json_decode($response, true);

    if (!$responseData || $responseData['status'] !== 'success') {
        echo "API Error<br><br>";
        return;
    }

    // ✅ تحديث sync_status
    $ids = array_column($dataKey['data'], 'id');

    if (!empty($ids)) {

        $idsList = implode(",", $ids);

        mysqli_query($con, "
            UPDATE $tableName
            SET sync_status = 1
            WHERE id IN ($idsList)
        ");
    }

    echo "✅ Synced: " . count($ids) . "<br><br>";
}


// =====================================================
// ================== PATIENTS =========================
// =====================================================

$result = mysqli_query($con, "
    SELECT * FROM add_patient
    WHERE sync_status = 0
    ORDER BY updated_at ASC
    LIMIT 50
");

$patients = [];

while ($row = mysqli_fetch_assoc($result)) {

    // ❌ لا نرسل sync_status
    unset($row['sync_status']);

    // 🔥 توليد UUID إذا غير موجود
    if (empty($row['uuid'])) {

        $newUUID = bin2hex(random_bytes(16));

        mysqli_query($con, "
            UPDATE add_patient
            SET uuid = '$newUUID'
            WHERE id = {$row['id']}
        ");

        $row['uuid'] = $newUUID;
    }

    $patients[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_patients.php",
    ["key" => "patients", "data" => $patients],
    "add_patient"
);




// =====================================================
// ================== PATIENT_VISITS ===================
// =====================================================

$result = mysqli_query($con, "
    SELECT pv.*, ap.uuid AS patient_uuid
    FROM patient_visits pv
    LEFT JOIN add_patient ap
    ON pv.patient_id = ap.id
    WHERE pv.sync_status = 0
    ORDER BY pv.updated_at ASC
    LIMIT 50
");

$patientVisits = [];

while ($row = mysqli_fetch_assoc($result)) {

    unset($row['sync_status']);

    // 🔥 مهم جداً
    // نرسل patient_uuid بدل الاعتماد على patient_id فقط
    $patientVisits[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_patient_visits.php",
    ["key" => "patient_visits", "data" => $patientVisits],
    "patient_visits"
);



// ================== SURGERY ==================


$result = mysqli_query($con, "
    SELECT s.*, ap.uuid AS patient_uuid
    FROM surgery s
    LEFT JOIN add_patient ap ON s.patient_id = ap.id
    WHERE s.sync_status = 0
    ORDER BY s.updated_at ASC
    LIMIT 50
");

$surgeries = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $surgeries[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_surgery.php",
    ["key" => "surgeries", "data" => $surgeries],
    "surgery"
);



// ================== INJECTION ==================
$result = mysqli_query($con, "
    SELECT i.*, ap.uuid AS patient_uuid
    FROM injection i
    LEFT JOIN add_patient ap ON i.patient_id = ap.id
    WHERE i.sync_status = 0
    ORDER BY i.updated_at ASC
    LIMIT 50
");

$injections = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $injections[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_injection.php",
    ["key" => "injections", "data" => $injections],
    "injection"
);




// ================== LASER ==================
$result = mysqli_query($con, "
    SELECT l.*, ap.uuid AS patient_uuid
    FROM laser l
    LEFT JOIN add_patient ap ON l.patient_id = ap.id
    WHERE l.sync_status = 0
    ORDER BY l.updated_at ASC
    LIMIT 50
");

$lasers = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $lasers[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_laser.php",
    ["key" => "lasers", "data" => $lasers],
    "laser"
);



?>