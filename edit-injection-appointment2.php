<?php

include 'config.php';

include 'auth.php';

if (isset($_POST['edit_injection_appointment'])) {
      $id         = intval($_POST['id']);
        $patient_id = intval($_POST['patient_id']);
        $injection_type = $_POST['injection_type'];
        $eye        = $_POST['eye'];
        $phone      = $_POST['phone'];
        $phone_alt  = $_POST['phone_alt'];
        $date       = $_POST['date'];
        $notes      = $_POST['notes'];
        $update_query = "
            UPDATE injection_appointment SET
                    injection_type = ?,
                    eye = ?,
                    phone = ?,
                    phone_alt = ?,
                    date = ?,
                    notes = ?
                WHERE id = ?";
        $stmt = $con->prepare($update_query);
        $stmt->bind_param(
            "ssssssi",
                $injection_type,
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
            
            echo "<script>alert('تم تعديل موعد الحقن بنجاح.');</script>";
            echo "<script>window.location.href = 'operation-by-date.php';</script>";
        } else {
            echo "خطأ: " . mysqli_error($con);
        }
    }
    ?>