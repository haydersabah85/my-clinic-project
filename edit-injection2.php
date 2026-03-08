<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['edit_injection'])) {
   $injection_id = $_GET['id_update'];
   $patient_id = $_POST['patient_id'];
   $eye = $_POST['eye'];
   $injection_type = $_POST['injection_type'];
   $date = $_POST['date'];
   $notes = $_POST['notes'];

   $update_query = "UPDATE injection SET 
        patient_id='$patient_id', 
        eye='$eye', 
        injection_type='$injection_type',
         date='$date',
         notes='$notes'
         WHERE id=$injection_id";
   $result = mysqli_query($con, $update_query);


   if ($result) {
      echo "<script>alert('تم تحديث معلومات الحقن بنجاح.');</script>";
      echo "<script>window.location.href = 'patient-file.php?id=" . $patient_id . "';</script>";
   } else {
      echo "خطأ: " . mysqli_error($con);
   }
}
