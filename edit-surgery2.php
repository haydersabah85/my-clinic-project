<?php
include 'config.php';

include 'auth.php';


if (isset($_POST['edit_surgery'])) {
    $surgery_id = $_GET['id_update'];
    $patient_id = $_POST['patient_id'];
    $eye = $_POST['eye'];
    $surgery_type = $_POST['surgery_type'];
    $iol_type = $_POST['iol_type'];
    $date = $_POST['date'];
    $notes = $_POST['notes'];
      $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    
    $update_query = "UPDATE surgery SET 
        patient_id='$patient_id', 
        eye='$eye', 
        surgery_type='$surgery_type', 
        iol_type='$iol_type',
         notes='$notes',
         date='$date',
         updated_at = NOW() $syncPart
         WHERE id='$surgery_id'";
    $result = mysqli_query($con, $update_query);



   if ($result) {
      echo "<script>alert('تم تحديث معلومات العملية بنجاح.');</script>";
      echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
   } else {
      echo "خطأ: " . mysqli_error($con);
   }
}
?>
