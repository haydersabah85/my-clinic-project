<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['submit_bt'])) {
   $patient_id = intval($_POST['patient_id']);
   $va_od = $_POST['va_od'];
   $va_os = $_POST['va_os'];
   $bcva_od = $_POST['bcva_od'];
   $bcva_os = $_POST['bcva_os'];
   $old_glasses_od = $_POST['old_glasses_od'];
   $old_glasses_os = $_POST['old_glasses_os'];
   $ref_od = $_POST['ref_od'];
   $ref_os = $_POST['ref_os'];
   $exam_date = date('Y-m-d');

   $insert_id = " SELECT id FROM add_patient WHERE id = $patient_id ";
   $insert_result = mysqli_query($con, $insert_id);


 //عدم تكرار الفحص لنفس المريض في نفس اليوم //

   $check_query = "SELECT * FROM va WHERE patient_id = $patient_id AND exam_date = '$exam_date'";
   $check_result = mysqli_query($con, $check_query);

   if (mysqli_num_rows($check_result) > 0) {
      echo "<script>alert('تم إجراء فحص النظر لهذا المريض في نفس اليوم بالفعل.');</script>";
      echo "<script>window.location.href = 'patient-data.php?id=" . $patient_id . "';</script>";
      exit;
   }
   $sql = "INSERT INTO va (patient_id, va_od, va_os, bcva_od, bcva_os, old_glasses_od, old_glasses_os, ref_od, ref_os, exam_date) 
    VALUES ('$patient_id', '$va_od', '$va_os', '$bcva_od', '$bcva_os', '$old_glasses_od', '$old_glasses_os', '$ref_od', '$ref_os', '$exam_date')";

   $result = mysqli_query($con, $sql);

  
 if ($result) {
      echo "<script>alert('تم إضافة فحص النظر بنجاح.');</script>";
      echo "<script>window.location.href = 'patient-data.php?id=" . $patient_id . "';</script>";
   } else {
      echo "خطأ: " . mysqli_error($con);
   }
  
}
