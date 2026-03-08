<?php
include 'config.php';

include 'auth.php';
$id = intval($_GET['id']);

$q = mysqli_query($con, "SELECT image_path FROM patient_images WHERE id = $id");
$row = mysqli_fetch_assoc($q);

if($row){
    unlink($row['image_path']); // حذف الملف
    mysqli_query($con, "DELETE FROM patient_images WHERE id = $id");
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
