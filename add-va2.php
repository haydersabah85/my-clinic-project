<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_column($con, 'va', 'iop_od', 'VARCHAR(50) NULL');
clinic_ensure_column($con, 'va', 'iop_os', 'VARCHAR(50) NULL');

if (isset($_POST['submit_bt'])) {

    // =========================
    // البيانات الأساسية
    // =========================
    $patient_id = (int)$_POST['patient_id'];

    $va_od = mysqli_real_escape_string($con, $_POST['va_od'] ?? '');
    $va_os = mysqli_real_escape_string($con, $_POST['va_os'] ?? '');

    $bcva_od = mysqli_real_escape_string($con, $_POST['bcva_od'] ?? '');
    $bcva_os = mysqli_real_escape_string($con, $_POST['bcva_os'] ?? '');

    $iop_od = mysqli_real_escape_string($con, $_POST['iop_od'] ?? '');
    $iop_os = mysqli_real_escape_string($con, $_POST['iop_os'] ?? '');

    $old_glasses_od = mysqli_real_escape_string($con, $_POST['old_glasses_od'] ?? '');
    $old_glasses_os = mysqli_real_escape_string($con, $_POST['old_glasses_os'] ?? '');

    $ref_od = mysqli_real_escape_string($con, $_POST['ref_od'] ?? '');
    $ref_os = mysqli_real_escape_string($con, $_POST['ref_os'] ?? '');

    $exam_date = date('Y-m-d');

    // UUID خاص بسجل فحص النظر (يُنشأ مرة واحدة عند الإضافة)
  

    // إعداد حقول المزامنة
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";

    // =========================
    // التحقق من patient_id
    // =========================
    if ($patient_id <= 0) {
        die("خطأ: لم يتم تحديد المريض.");
    }

    // =========================
    // جلب patient_uuid
    // =========================
    $getPatient = mysqli_query($con, "
        SELECT uuid
        FROM add_patient
        WHERE id = '$patient_id'
        LIMIT 1
    ");

    if (!$getPatient || mysqli_num_rows($getPatient) == 0) {
        die("خطأ: المريض غير موجود.");
    }

    $patientData = mysqli_fetch_assoc($getPatient);
    $patient_uuid = mysqli_real_escape_string($con, $patientData['uuid']);

    // =========================
    // منع تكرار الفحص في نفس اليوم
    // =========================
    $check_query = "
        SELECT va_id
        FROM va
        WHERE patient_id = '$patient_id'
          AND exam_date = '$exam_date'
        LIMIT 1
    ";

    $check_result = mysqli_query($con, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {

        echo "<script>alert('تم إجراء فحص النظر لهذا المريض في نفس اليوم بالفعل.');</script>";
        echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
        exit;
    }

    // =========================
    // إدخال سجل فحص النظر
    // =========================
    $sql = "
        INSERT INTO va (
         
            patient_id,
          
            va_od,
            va_os,
            bcva_od,
            bcva_os,
            iop_od,
            iop_os,
            old_glasses_od,
            old_glasses_os,
            ref_od,
            ref_os,
            exam_date,
            updated_at
            $syncFields
        )
        VALUES (
      
            '$patient_id',
           
            '$va_od',
            '$va_os',
            '$bcva_od',
            '$bcva_os',
            '$iop_od',
            '$iop_os',
            '$old_glasses_od',
            '$old_glasses_os',
            '$ref_od',
            '$ref_os',
            '$exam_date',
            NOW()
            $syncValues
        )
    ";

    $result = mysqli_query($con, $sql);

    // =========================
    // النتيجة
    // =========================
    if ($result) {

        echo "<script>alert('تم إضافة فحص النظر بنجاح.');</script>";
        echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
        exit;

    } else {

        echo "خطأ: " . mysqli_error($con);
    }
}
?>
