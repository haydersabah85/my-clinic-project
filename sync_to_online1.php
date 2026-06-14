<?php
// Legacy endpoint kept for backward compatibility only.
// Route all usage to the maintained safe sync flow.
header('Location: sync_to_online_safe.php?legacy=1', true, 302);
exit;

include 'config.php';

$secret = "MY_SECRET_KEY";

// ================== FUNCTION ==================
function syncTable($con, $url, $dataKey, $tableName)
{

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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 300);
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
        echo "<pre>";
        echo htmlspecialchars($response);
        echo "</pre><br><br>";
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




// ================== MEDICINES ==================
$result = mysqli_query($con, "
    SELECT * FROM medicines
    WHERE sync_status = 0
    ORDER BY updated_at ASC
    LIMIT 50
");

$medicines = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $medicines[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_medicines.php",
    ["key" => "medicines", "data" => $medicines],
    "medicines"
);


// ================== PRESCRIPTIONS ==================
$result = mysqli_query($con, "
    SELECT pr.*,
           ap.uuid AS patient_uuid,
           pv.visit_uuid
    FROM prescriptions pr
    LEFT JOIN add_patient ap ON pr.patient_id = ap.id
    LEFT JOIN patient_visits pv ON pr.visit_id = pv.id
    WHERE pr.sync_status = 0
    ORDER BY pr.updated_at ASC
    LIMIT 50
");

$prescriptions = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $prescriptions[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_prescriptions.php",
    ["key" => "prescriptions", "data" => $prescriptions],
    "prescriptions"
);



// ================== PRESCRIPTION ITEMS ==================
$result = mysqli_query($con, "
    SELECT pi.*,
           p.prescription_uuid,
           m.medicine_uuid
    FROM prescription_items pi
    LEFT JOIN prescriptions p ON pi.prescription_id = p.id
    LEFT JOIN medicines m ON pi.medicine_id = m.id
    WHERE pi.sync_status = 0
    ORDER BY pi.updated_at ASC
    LIMIT 50
");

$prescriptionItems = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $prescriptionItems[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_prescription_items.php",
    ["key" => "prescription_items", "data" => $prescriptionItems],
    "prescription_items"
);



// ================== VA ==================
$result = mysqli_query($con, "
    SELECT *
    FROM va
    WHERE sync_status = 0
    ORDER BY updated_at ASC
    LIMIT 50
");

$vaRecords = [];

while ($row = mysqli_fetch_assoc($result)) {
    unset($row['sync_status']);
    $vaRecords[] = $row;
}

syncTable(
    $con,
    "https://hayder-sabah-clinic.com/api/sync_va.php",
    ["key" => "va_records", "data" => $vaRecords],
    "va"
);
