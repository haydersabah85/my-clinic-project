<?php
include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_column($con, 'va', 'iop_od', 'VARCHAR(50) NULL');
clinic_ensure_column($con, 'va', 'iop_os', 'VARCHAR(50) NULL');

if (isset($_POST['update_va'])) {
   $va_id = (int)$_GET['id_update'];
   $patient_id = intval($_POST['patient_id']);
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
   $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

   $getPatient = mysqli_query($con, "
    SELECT uuid
    FROM add_patient
    WHERE id = '$patient_id'
");

$patientData = mysqli_fetch_assoc($getPatient);
$patient_uuid = $patientData['uuid'];

   $update_query = "UPDATE va SET 
      patient_id='$patient_id', 
      va_od='$va_od', 
      va_os='$va_os', 
      bcva_od='$bcva_od', 
      bcva_os='$bcva_os', 
      iop_od='$iop_od',
      iop_os='$iop_os',
      old_glasses_od='$old_glasses_od', 
      old_glasses_os='$old_glasses_os', 
      ref_od='$ref_od', 
      ref_os='$ref_os',
    
      updated_at = NOW() $syncPart
      WHERE va_id=$va_id";

   $result = mysqli_query($con, $update_query);

   if ($result) {
      echo "<script>alert('تم تحديث فحص النظر بنجاح.');</script>";
      echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
   } else {
      echo "خطأ: " . mysqli_error($con);
   }
}
