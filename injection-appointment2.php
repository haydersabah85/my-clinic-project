<?php
include 'config.php';

include 'auth.php';

if (isset($_POST['submit_injection'])) {
    $patient_id = $_GET['id'];
    $eye = $_POST['eye'];
    $injection_type = $_POST['injection_type'];
    $phone = $_POST['phone'];
    $phone_alt = $_POST['phone_alt'];
    $date = $_POST['date'];
    $notes = $_POST['notes'];
 
$sql_serial = "SELECT MAX(serial_no) AS max_serial FROM injection_appointment WHERE date = '$date'";
$result_serial = mysqli_query($con, $sql_serial);
$row_serial = mysqli_fetch_assoc($result_serial);
$next_serial = $row_serial['max_serial'] ? $row_serial['max_serial'] + 1 : 1;

    $insert_query = "INSERT INTO injection_appointment
    (patient_id, eye, injection_type, phone, phone_alt, date, notes, serial_no) 
    VALUES ('$patient_id', '$eye', '$injection_type', '$phone', '$phone_alt', '$date', '$notes', '$next_serial')";

    $insert_phone = "UPDATE add_patient SET phone_no = '$phone', phone_no_alt = '$phone_alt' WHERE id = '$patient_id'";
    mysqli_query($con, $insert_phone);

    


    if (mysqli_query($con, $insert_query)) {
        echo "<script>alert('تم حجز موعد الحقن بنجاح.');</script>";
    } else {
        echo "خطأ: " . mysqli_error($con);
    }
    echo "<script>window.location.href = 'patient-file.php?id=$patient_id';</script>";

}
?>