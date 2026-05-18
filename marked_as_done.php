<?php

include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    

    // تحديث حالة الزيارة إلى "تمت"
    $updateQuery = "UPDATE visits SET is_done = 1 WHERE patient_id = $id";
    mysqli_query($con, $updateQuery);
}

header("Location: patient-file.php?id=$id");
exit();
