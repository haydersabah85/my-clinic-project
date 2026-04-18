<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['update_va'])) {
   $va_id = $_GET['id_update'];
   $patient_id = intval($_POST['patient_id']);
   $va_od = $_POST['va_od'];
   $va_os = $_POST['va_os'];
   $bcva_od = $_POST['bcva_od'];
   $bcva_os = $_POST['bcva_os'];
   $old_glasses_od = $_POST['old_glasses_od'];
   $old_glasses_os = $_POST['old_glasses_os'];
   $ref_od = $_POST['ref_od'];
   $ref_os = $_POST['ref_os'];
   $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";

   $update_query = "UPDATE va SET 
      patient_id='$patient_id', 
      va_od='$va_od', 
      va_os='$va_os', 
      bcva_od='$bcva_od', 
      bcva_os='$bcva_os', 
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