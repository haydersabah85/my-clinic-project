<?php

include 'config.php';
include 'auth.php';


if (isset($_GET['id_delete'])) {
    $id_delete = $_GET['id_delete'];

    $delete = "DELETE FROM add_patient WHERE id='$id_delete'";
    $result_delete = mysqli_query($con, $delete);
    if ($result_delete) {
        echo "<script>alert('تم حذف بيانات المريض بنجاح');</script>";
        echo "<script>window.location.href='main.php';</script>";
    } else {
        echo "<script>alert('فشل في حذف بيانات المريض');</script>";
        echo "<script>window.location.href='main.php';</script>";
    }
} else {
    echo "<script>window.location.href='main.php';</script>";
}
?>
