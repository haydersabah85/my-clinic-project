<?php
include 'config.php';          // قاعدة بيانات العيادة المحلية
include 'online-config.php';   // قاعدة بيانات الموقع الأونلاين

// آخر وقت مزامنة
$getSync = mysqli_query($con, "SELECT last_sync FROM sync_status WHERE id = 1");
$syncRow = mysqli_fetch_assoc($getSync);
$lastSync = $syncRow['last_sync'];
$lastUpdated = null;


// جلب المرضى المعدلين بعد آخر مزامنة
$result = mysqli_query($con, "
    SELECT * FROM add_patient
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

echo "Rows found: " . mysqli_num_rows($result) . "<br>";

while ($row = mysqli_fetch_assoc($result)) {

    $id = (int)$row['id'];
    $name = mysqli_real_escape_string($online, $row['full_name']);
    $age = mysqli_real_escape_string($online, $row['age']);
    $gender = mysqli_real_escape_string($online, $row['gender']);
    $address = mysqli_real_escape_string($online, $row['address']);
    $phone = mysqli_real_escape_string($online, $row['phone_no']);
    $phone_no_alt = mysqli_real_escape_string($online, $row['phone_no_alt']);
    $date_of_birth = mysqli_real_escape_string($online, $row['date_of_birth']);
    $notes = mysqli_real_escape_string($online, $row['notes']);
    $is_critical = mysqli_real_escape_string($online, $row['is_critical']);
    $updated = mysqli_real_escape_string($online, $row['updated_at']);

    mysqli_query($online, "
        INSERT INTO add_patient (
            id,
            full_name,
            age,
            gender,
            address,
            phone_no,
            phone_no_alt,
            date_of_birth,
            notes,
            is_critical,
            updated_at
        )
        VALUES (
            '$id',
            '$name',
            '$age',
            '$gender',
            '$address',
            '$phone',
            '$phone_no_alt',
            '$date_of_birth',
            '$notes',
            '$is_critical',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            full_name = '$name',
            age = '$age',
            gender = '$gender',
            address = '$address',
            phone_no = '$phone',
            phone_no_alt = '$phone_no_alt',
            date_of_birth = '$date_of_birth',
            notes = '$notes',
            is_critical = '$is_critical',
            updated_at = '$updated'
    ");
echo "Patient ID: $id - " . mysqli_affected_rows($online) . "<br>";

// تحديث وقت آخر مزامنة

$lastUpdated = $row['updated_at'];
}


      
if($lastUpdated !== null){
    mysqli_query($con, "
        UPDATE sync_status
        SET last_sync = '$lastUpdated'
        WHERE id = 1
    ");
}

echo "Sync completed";