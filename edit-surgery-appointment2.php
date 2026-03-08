
<?php
include "config.php";

include 'auth.php';

if (isset($_POST['edit_surgery_appointment'])) {

    $id         = intval($_POST['id']);
    $patient_id = intval($_POST['patient_id']);
    $surgery_type = $_POST['surgery_type'];
    $eye        = $_POST['eye'];
    $phone      = $_POST['phone'];
    $phone_alt  = $_POST['phone_alt'];
    $date       = $_POST['date'];
    $notes      = $_POST['notes'];

    $stmt = $con->prepare("
        UPDATE surgery_appointment SET
            surgery_type = ?,
            eye = ?,
            phone = ?,
            phone_alt = ?,
            date = ?,
            notes = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssi",
        $surgery_type,
        $eye,
        $phone,
        $phone_alt,
        $date,
        $notes,
        $id
    );

    $insert_phone = "UPDATE add_patient SET phone_no = '$phone', phone_no_alt = '$phone_alt' WHERE id = '$patient_id'";
    mysqli_query($con, $insert_phone);
    

    if ($stmt->execute()) {
        echo "<script>
            alert('تم تحديث موعد العملية بنجاح');
            window.location.href='operation-by-date.php';
        </script>";
    } else {
        echo "خطأ: " . $stmt->error;
    }
    
    $stmt->close();
}
?>
