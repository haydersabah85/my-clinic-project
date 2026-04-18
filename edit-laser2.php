<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['edit_laser'])) {
    $laser_id = $_GET['id_update'];
    $patient_id = $_POST['patient_id'];
    $eye = $_POST['eye'];
    $laser_type = $_POST['laser_type'];
    $date = $_POST['date'];
    $laser_notes = $_POST['notes'];
    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
   
    
    $update_query = "UPDATE laser SET 
        patient_id='$patient_id', 
        eye='$eye', 
        laser_type='$laser_type' ,
        notes='$laser_notes', 
        date='$date',
        updated_at = NOW() $syncPart
        WHERE id=$laser_id";
    $result = mysqli_query($con, $update_query);


   if ($result) {
      echo "<script>alert('تم تحديث معلومات الليزر بنجاح.');</script>";
      echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
   } else {
      echo "خطأ: " . mysqli_error($con);
   }
}
?>