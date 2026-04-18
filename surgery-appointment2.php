<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['submit_surgery'])) {
    $patient_id = $_GET['id'];
    
    $eye = $_POST['eye'];
    $surgery_type = $_POST['surgery_type'];
    $phone = $_POST['phone'];
    $phone_alt = $_POST['phone_alt'];
    $date = $_POST['date'];
    $notes = $_POST['notes'];
    $syncFields = $IS_LOCAL ? ", sync_status" : "";
    $syncValues = $IS_LOCAL ? ", 0" : "";
 
 $sql_serial = "SELECT MAX(serial_no) AS max_serial FROM surgery_appointment WHERE date = '$date'";
   $result_serial = mysqli_query($con,$sql_serial);
   $row_serial = mysqli_fetch_assoc($result_serial);

   if ($row_serial['max_serial']) {
    $serial_no = $row_serial['max_serial'] + 1;
   } else {
    $serial_no = 1;
   }

    $insert_query = "INSERT INTO surgery_appointment
    (patient_id, eye, surgery_type, phone, phone_alt, date, notes, serial_no, updated_at $syncFields) 
    VALUES ('$patient_id', '$eye', '$surgery_type', '$phone', '$phone_alt', '$date', '$notes', '$serial_no', NOW() $syncValues)";


    $syncPart = $IS_LOCAL ? ", sync_status = 0" : "";
    $insert_phone = "UPDATE add_patient SET phone_no = '$phone', phone_no_alt = '$phone_alt', updated_at = NOW() $syncPart WHERE id = '$patient_id'";
    mysqli_query($con, $insert_phone);
    
    if (mysqli_query($con, $insert_query)) {
        echo "<script>alert('تم حجز موعد العملية بنجاح.');</script>";
        echo "<script>window.location.href = 'patient-file.php?id=$patient_id';</script>";
    } else {
        echo "خطأ: " . mysqli_error($con);
    }
}
?>