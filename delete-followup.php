<?php

include 'config.php';
include 'auth.php';

if(isset($_GET['id']) && isset($_GET['patient_id'])){
    $id = $_GET['id'];
    $patient_id = $_GET['patient_id'];

    $delete_query = mysqli_query($con, "DELETE FROM followups WHERE id='$id' AND patient_id='$patient_id'");

    if($delete_query){
        header("Location: followups.php");
        exit();
    } else {
        echo "حدث خطأ أثناء حذف المراجعة.";
    }
} else {
    echo "معرف المراجعة غير صالح.";
}
?>