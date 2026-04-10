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


// جلب الزيارات المعدلة بعد آخر مزامنة
$resultVisits = mysqli_query($con, "
    SELECT * FROM visits
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($visit = mysqli_fetch_assoc($resultVisits)) {

    $visit_id = (int)$visit['id'];
    $patient_id = (int)$visit['patient_id'];
    $visit_type = mysqli_real_escape_string($online, $visit['visit_type']);
    $visit_date = mysqli_real_escape_string($online, $visit['visit_date']);
    $daily_serial = mysqli_real_escape_string($online, $visit['daily_serial']);
    $updated = mysqli_real_escape_string($online, $visit['updated_at']);

    mysqli_query($online, "
        INSERT INTO visits (
            id,
            patient_id,
            visit_type,
            visit_date,
            daily_serial,
            updated_at
        )
        VALUES (
            '$visit_id',
            '$patient_id',
            '$visit_type',
            '$visit_date',
            '$daily_serial',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            visit_type = '$visit_type',
            visit_date = '$visit_date',
            daily_serial = '$daily_serial',
            updated_at = '$updated'
    ");
echo "Visit ID: $visit_id - " . mysqli_affected_rows($online) . "<br>";
}



// جلب زيارات المريض المعدلة بعد آخر مزامنة
$resultPatientVisits = mysqli_query($con, "
    SELECT * FROM patient_visits
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($patientVisit = mysqli_fetch_assoc($resultPatientVisits)) {

    $patient_visit_id = (int)$patientVisit['id'];
    $patient_id = (int)$patientVisit['patient_id'];
   $date = mysqli_real_escape_string($online, $patientVisit['date']);
    $notes = mysqli_real_escape_string($online, $patientVisit['notes']);
    $updated = mysqli_real_escape_string($online, $patientVisit['updated_at']);

    mysqli_query($online, "
        INSERT INTO patient_visits (
            id,
            patient_id,
            date,
            notes,
            updated_at
        )
        VALUES (
            '$patient_visit_id',
            '$patient_id',
            '$date',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$date',
            notes = '$notes',
            updated_at = '$updated'
    ");
echo "Patient Visit ID: $patient_visit_id - " . mysqli_affected_rows($online) . "<br>";
}


//جلب مواعيد العمليات المعدلة بعد آخر مزامنة
$resultOperations = mysqli_query($con, "
    SELECT * FROM surgery_appointment
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($operation = mysqli_fetch_assoc($resultOperations)) {

    $operation_id = (int)$operation['id'];
    $patient_id = (int)$operation['patient_id'];
    $surgery_date = mysqli_real_escape_string($online, $operation['date']);
    $surgery_type = mysqli_real_escape_string($online, $operation['surgery_type']);
    $eye = mysqli_real_escape_string($online, $operation['eye']);
    $phone = mysqli_real_escape_string($online, $operation['phone']);
    $phone_alt = mysqli_real_escape_string($online, $operation['phone_alt']);
    $serial_no = mysqli_real_escape_string($online, $operation['serial_no']);
    $attendance_status = mysqli_real_escape_string($online, $operation['attendance_status']);
   $status = mysqli_real_escape_string($online, $operation['status']);
    $notes = mysqli_real_escape_string($online, $operation['notes']);
    $updated = mysqli_real_escape_string($online, $operation['updated_at']);

    mysqli_query($online, "
        INSERT INTO surgery_appointment (
            id,
            patient_id,
            date,
            surgery_type,
            eye,
            phone,
            phone_alt,
            serial_no,
            attendance_status,
            status,
            notes,
            updated_at
        )
        VALUES (
            '$operation_id',
            '$patient_id',
            '$surgery_date',
            '$surgery_type',
            '$eye',
            '$phone',
            '$phone_alt',
            '$serial_no',
            '$attendance_status',
            '$status',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$surgery_date',
            surgery_type = '$surgery_type',
            eye = '$eye',
            phone = '$phone',
            phone_alt = '$phone_alt',
            serial_no = '$serial_no',
            attendance_status = '$attendance_status',
            status = '$status',
            notes = '$notes',
            updated_at = '$updated'
    ");

echo "Operation ID: $operation_id - " . mysqli_affected_rows($online) . "<br>";
}


//جلب مواعيد الليزر المعدلة بعد آخر مزامنة
$resultLasers = mysqli_query($con, "
    SELECT * FROM laser_appointment
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($laser = mysqli_fetch_assoc($resultLasers)) {

    $laser_id = (int)$laser['id'];
    $patient_id = (int)$laser['patient_id'];
    $laser_date = mysqli_real_escape_string($online, $laser['date']);
    $laser_type = mysqli_real_escape_string($online, $laser['laser_type']);
    $eye = mysqli_real_escape_string($online, $laser['eye']);
    $phone = mysqli_real_escape_string($online, $laser['phone']);
    $phone_alt = mysqli_real_escape_string($online, $laser['phone_alt']);
    $serial_no = mysqli_real_escape_string($online, $laser['serial_no']);
    $attendance_status = mysqli_real_escape_string($online, $laser['attendance_status']);
   $status = mysqli_real_escape_string($online, $laser['status']);
    $notes = mysqli_real_escape_string($online, $laser['notes']);
    $updated = mysqli_real_escape_string($online, $laser['updated_at']);

    mysqli_query($online, "
        INSERT INTO laser_appointment (
            id,
            patient_id,
            date,
            laser_type,
            eye,
            phone,
            phone_alt,
            serial_no,
            attendance_status,
            status,
            notes,
            updated_at
        )
        VALUES (
            '$laser_id',
            '$patient_id',
            '$laser_date',
            '$laser_type',
            '$eye',
            '$phone',
            '$phone_alt',
            '$serial_no',
            '$attendance_status',
            '$status',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$laser_date',
            laser_type = '$laser_type',
            eye = '$eye',
            phone = '$phone',
            phone_alt = '$phone_alt',
            serial_no = '$serial_no',
            attendance_status = '$attendance_status',
            status = '$status',
            notes = '$notes',
            updated_at = '$updated'
    ");
echo "Laser ID: $laser_id - " . mysqli_affected_rows($online) . "<br>";
}



//جلب مواعيد الحقن المعدلة بعد آخر مزامنة
$resultInjections = mysqli_query($con, "
    SELECT * FROM injection_appointment
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($injection = mysqli_fetch_assoc($resultInjections)) {

    $injection_id = (int)$injection['id'];
    $patient_id = (int)$injection['patient_id'];
    $injection_date = mysqli_real_escape_string($online, $injection['date']);
    $injection_type = mysqli_real_escape_string($online, $injection['injection_type']);
    $eye = mysqli_real_escape_string($online, $injection['eye']);
    $phone = mysqli_real_escape_string($online, $injection['phone']);
    $phone_alt = mysqli_real_escape_string($online, $injection['phone_alt']);
    $serial_no = mysqli_real_escape_string($online, $injection['serial_no']);
    $attendance_status = mysqli_real_escape_string($online, $injection['attendance_status']);
   $status = mysqli_real_escape_string($online, $injection['status']);
    $notes = mysqli_real_escape_string($online, $injection['notes']);
    $updated = mysqli_real_escape_string($online, $injection['updated_at']);

    mysqli_query($online, "
        INSERT INTO injection_appointment (
            id,
            patient_id,
            date,
            injection_type,
            eye,
            phone,
            phone_alt,
            serial_no,
            attendance_status,
            status,
            notes,
            updated_at
        )
        VALUES (
            '$injection_id',
            '$patient_id',
            '$injection_date',
            '$injection_type',
            '$eye',
            '$phone',
            '$phone_alt',
            '$serial_no',
            '$attendance_status',
            '$status',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$injection_date',
            injection_type = '$injection_type',
            eye = '$eye',
            phone = '$phone',
            phone_alt = '$phone_alt',
            serial_no = '$serial_no',
            attendance_status = '$attendance_status',
            status = '$status',
            notes = '$notes',
            updated_at = '$updated'
    ");
echo "Injection ID: $injection_id - " . mysqli_affected_rows($online) . "<br>";
}


// جلب العمليات المعدلة بعد آخر مزامنة
$resultSurgery = mysqli_query($con, "
    SELECT * FROM surgery
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($surgery = mysqli_fetch_assoc($resultSurgery)) {

    $surgery_id = (int)$surgery['id'];
    $patient_id = (int)$surgery['patient_id'];
    $surgery_date = mysqli_real_escape_string($online, $surgery['date']);
    $surgery_type = mysqli_real_escape_string($online, $surgery['surgery_type']);
    $eye = mysqli_real_escape_string($online, $surgery['eye']);
    $iol_type = mysqli_real_escape_string($online, $surgery['iol_type']);
    $notes = mysqli_real_escape_string($online, $surgery['notes']);
    $updated = mysqli_real_escape_string($online, $surgery['updated_at']);

    mysqli_query($online, "
        INSERT INTO surgery (
            id,
            patient_id,
            date,
            surgery_type,
            eye,
            iol_type,
            notes,
            updated_at
        )
        VALUES (
            '$surgery_id',
            '$patient_id',
            '$surgery_date',
            '$surgery_type',
            '$eye',
            '$iol_type',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$surgery_date',
            surgery_type = '$surgery_type',
            eye = '$eye',
            iol_type = '$iol_type',
            notes = '$notes',
            updated_at = '$updated'
    ");

echo "Surgery ID: $surgery_id - " . mysqli_affected_rows($online) . "<br>";
}



//جلب الليزر المعدلة بعد آخر مزامنة
$resultLaser = mysqli_query($con, "
    SELECT * FROM laser
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($laser = mysqli_fetch_assoc($resultLaser)) {

    $laser_id = (int)$laser['id'];
    $patient_id = (int)$laser['patient_id'];
    $laser_date = mysqli_real_escape_string($online, $laser['date']);
    $laser_type = mysqli_real_escape_string($online, $laser['laser_type']);
    $eye = mysqli_real_escape_string($online, $laser['eye']);
    $notes = mysqli_real_escape_string($online, $laser['notes']);
    $updated = mysqli_real_escape_string($online, $laser['updated_at']);

    mysqli_query($online, "
        INSERT INTO laser (
            id,
            patient_id,
            date,
            laser_type,
            eye,
            notes,
            updated_at
        )
        VALUES (
            '$laser_id',
            '$patient_id',
            '$laser_date',
            '$laser_type',
            '$eye',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$laser_date',
            laser_type = '$laser_type',
            eye = '$eye',
            notes = '$notes',
            updated_at = '$updated'
    ");
echo "Laser ID: $laser_id - " . mysqli_affected_rows($online) . "<br>";
}


// جلب الحقن المعدلة بعد آخر مزامنة
$resultInjection = mysqli_query($con, "
    SELECT * FROM injection
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($injection = mysqli_fetch_assoc($resultInjection)) {

    $injection_id = (int)$injection['id'];
    $patient_id = (int)$injection['patient_id'];
    $injection_date = mysqli_real_escape_string($online, $injection['date']);
    $injection_type = mysqli_real_escape_string($online, $injection['injection_type']);
    $eye = mysqli_real_escape_string($online, $injection['eye']);
    $notes = mysqli_real_escape_string($online, $injection['notes']);
    $updated = mysqli_real_escape_string($online, $injection['updated_at']);

    mysqli_query($online, "
        INSERT INTO injection (
            id,
            patient_id,
            date,
            injection_type,
            eye,
            notes,
            updated_at
        )
        VALUES (
            '$injection_id',
            '$patient_id',
            '$injection_date',
            '$injection_type',
            '$eye',
            '$notes',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            date = '$injection_date',
            injection_type = '$injection_type',
            eye = '$eye',
            notes = '$notes',
            updated_at = '$updated'
    ");
echo "Injection ID: $injection_id - " . mysqli_affected_rows($online) . "<br>";
}


//جلب المتابعة المعدلة بعد آخر مزامنة
$resultFollowUp = mysqli_query($con, "
    SELECT * FROM followups
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($followUp = mysqli_fetch_assoc($resultFollowUp)) {

    $followup_id = (int)$followUp['id'];
    $patient_id = (int)$followUp['patient_id'];
    $followup_date = mysqli_real_escape_string($online, $followUp['followup_date']);
    $followup_reason = mysqli_real_escape_string($online, $followUp['followup_reason']);
    $status = mysqli_real_escape_string($online, $followUp['status']);
    $notes = mysqli_real_escape_string($online, $followUp['note']);
    $created_at = mysqli_real_escape_string($online, $followUp['created_at']);
    $updated = mysqli_real_escape_string($online, $followUp['updated_at']);

    mysqli_query($online, "
        INSERT INTO followups (
            id,
            patient_id,
            followup_date,
            followup_reason,
            status,
            note,
            created_at,
            updated_at
        )
        VALUES (
            '$followup_id',
            '$patient_id',
            '$followup_date',
            '$followup_reason',
            '$status',
            '$notes',
            '$created_at',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            followup_date = '$followup_date',
            followup_reason = '$followup_reason',
            status = '$status',
            note = '$notes',
            created_at = '$created_at',
            updated_at = '$updated'
    ");

echo "Follow-up ID: $followup_id - " . mysqli_affected_rows($online) . "<br>";
}



//جلب الادوية المعدلة بعد آخر مزامنة
$resultMedication = mysqli_query($con, "
    SELECT * FROM medicines
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($medication = mysqli_fetch_assoc($resultMedication)) {

    $medicine_id = (int)$medication['id'];
    $medicine_name = mysqli_real_escape_string($online, $medication['medicine_name']);
   $medicine_form = mysqli_real_escape_string($online, $medication['medicine_form']);
   $strength = mysqli_real_escape_string($online, $medication['strength']);
   $category = mysqli_real_escape_string($online, $medication['category']);
   $created_at = mysqli_real_escape_string($online, $medication['created_at']);
    $updated = mysqli_real_escape_string($online, $medication['updated_at']);

    mysqli_query($online, "
        INSERT INTO medicines (
            id,
            medicine_name,
            medicine_form,
            strength,
            category,
            created_at,
            updated_at
        )
        VALUES (
            '$medicine_id',
            '$medicine_name',
            '$medicine_form',
            '$strength',
            '$category',
            '$created_at',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            medicine_name = '$medicine_name',
            medicine_form = '$medicine_form',
            strength = '$strength',
            category = '$category',
            created_at = '$created_at',
            updated_at = '$updated'
    ");
echo "Medicine ID: $medicine_id - " . mysqli_affected_rows($online) . "<br>";
}


//جلب الوصفات المعدلة بعد آخر مزامنة
$resultPrescriptions = mysqli_query($con, "
    SELECT * FROM prescriptions
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($prescription = mysqli_fetch_assoc($resultPrescriptions)) {

    $prescription_id = (int)$prescription['id'];
    $patient_id = (int)$prescription['patient_id'];
   $visit_id = (int)$prescription['visit_id'];
    $prescription_date = mysqli_real_escape_string($online, $prescription['prescription_date']);
    $diagnosis = mysqli_real_escape_string($online, $prescription['diagnosis']);
    $notes = mysqli_real_escape_string($online, $prescription['notes']);
    $next_visit_date = mysqli_real_escape_string($online, $prescription['next_visit_date']);
    $status = mysqli_real_escape_string($online, $prescription['status ENUM']);
    $updated = mysqli_real_escape_string($online, $prescription['updated_at']);

    mysqli_query($online, "
        INSERT INTO prescriptions (
            id,
            patient_id,
            visit_id,
            prescription_date,
            diagnosis,
            notes,
            next_visit_date,
            status ENUM,
            updated_at
        )
        VALUES (
            '$prescription_id',
            '$patient_id',
            '$visit_id',
            '$prescription_date',
            '$diagnosis',
            '$notes',
            '$next_visit_date',
            '$status',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            visit_id = '$visit_id',
            prescription_date = '$prescription_date',
            diagnosis = '$diagnosis',
            notes = '$notes',
            next_visit_date = '$next_visit_date',
            status ENUM = '$status',
            updated_at = '$updated'
    ");

echo "Prescription ID: $prescription_id - " . mysqli_affected_rows($online) . "<br>";
}



//جلب فقرات العلاج المعدلة بعد آخر مزامنة
$resultTreatmentItems = mysqli_query($con, "
    SELECT * FROM prescription_items
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($item = mysqli_fetch_assoc($resultTreatmentItems)) {

    $item_id = (int)$item['id'];
    $prescription_id = (int)$item['prescription_id'];
    $medicine_id = (int)$item['medicine_id'];
    $dose = mysqli_real_escape_string($online, $item['dose']);
    $frequency = mysqli_real_escape_string($online, $item['frequency']);
    $duration = mysqli_real_escape_string($online, $item['duration']);
    $eye = mysqli_real_escape_string($online, $item['eye']);
   $instructions = mysqli_real_escape_string($online, $item['instructions']);
    $updated = mysqli_real_escape_string($online, $item['updated_at']);

    mysqli_query($online, "
        INSERT INTO prescription_items (
            id,
            prescription_id,
            medicine_id,
            dose,
            frequency,
            duration,
            eye,
            instructions,
            updated_at
        )
        VALUES (
            '$item_id',
            '$prescription_id',
            '$medicine_id',
            '$dose',
            '$frequency',
            '$duration',
            '$eye',
            '$instructions',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            prescription_id = '$prescription_id',
            medicine_id = '$medicine_id',
            dose = '$dose',
            frequency = '$frequency',
            duration = '$duration',
            eye = '$eye',
            instructions = '$instructions',
            updated_at = '$updated'
    ");

echo "Treatment Item ID: $item_id - " . mysqli_affected_rows($online) . "<br>";
}



//جلب فحص النظر المعدلة بعد آخر مزامنة
$resultEyeExams = mysqli_query($con, "
    SELECT * FROM va
    WHERE updated_at > '$lastSync'
    ORDER BY updated_at ASC
    LIMIT 50
");

while ($exam = mysqli_fetch_assoc($resultEyeExams)) {

    $va_id = (int)$exam['va_id'];
    $patient_id = (int)$exam['patient_id'];
   $va_od = mysqli_real_escape_string($online, $exam['va_od']);
    $va_os = mysqli_real_escape_string($online, $exam['va_os']);
    $bcva_od = mysqli_real_escape_string($online, $exam['bcva_od']);
    $bcva_os = mysqli_real_escape_string($online, $exam['bcva_os']);
    $old_glasses_od = mysqli_real_escape_string($online, $exam['old_glasses_od']);
    $old_glasses_os = mysqli_real_escape_string($online, $exam['old_glasses_os']);
    $ref_od = mysqli_real_escape_string($online, $exam['ref_od']);
    $ref_os = mysqli_real_escape_string($online, $exam['ref_os']);
    $exam_date = mysqli_real_escape_string($online, $exam['exam_date']);
    $updated = mysqli_real_escape_string($online, $exam['updated_at']);

    mysqli_query($online, "
        INSERT INTO va (
           va_id,
            patient_id,
           va_od,
            va_os,
            bcva_od,
            bcva_os,
            old_glasses_od,
            old_glasses_os,
            ref_od,
            ref_os,
            exam_date,
            updated_at
        )
        VALUES (
            '$va_id',
            '$patient_id',
            '$va_od',
            '$va_os',
            '$bcva_od',
            '$bcva_os',
            '$old_glasses_od',
            '$old_glasses_os',
            '$ref_od',
            '$ref_os',
            '$exam_date',
            '$updated'
        )
        ON DUPLICATE KEY UPDATE
            patient_id = '$patient_id',
            va_od = '$va_od',
            va_os = '$va_os',
            bcva_od = '$bcva_od',
            bcva_os = '$bcva_os',
            old_glasses_od = '$old_glasses_od',
            old_glasses_os = '$old_glasses_os',
            ref_od = '$ref_od',
            ref_os = '$ref_os',
            exam_date = '$exam_date',
            updated_at = '$updated'
    ");

echo "Eye Exam ID: $va_id - " . mysqli_affected_rows($online) . "<br>";
}


if($lastUpdated !== null){
    mysqli_query($con, "
        UPDATE sync_status
        SET last_sync = '$lastUpdated'
        WHERE id = 1
    ");
}

echo "Sync completed";
?>


