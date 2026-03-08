<?php

include 'config.php';

include 'auth.php';

$patient_id = $_POST['id'];
$notes = $_POST['notes'];
$patient_id = intval($patient_id);


$select_query = "SELECT full_name FROM add_patient WHERE id = $patient_id";
$select_result = mysqli_query($con, $select_query);
$patient_name = '';
if ($select_result && mysqli_num_rows($select_result) > 0) {
    $patient_row = mysqli_fetch_assoc($select_result);
    $patient_name = $patient_row['full_name'];
}

// إعداد مجلد الصور
$target_dir = "uploads/image_$patient_name/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// حفظ الصورة
$target_file = $target_dir . basename($_FILES["retina_image"]["name"]);
if (move_uploaded_file($_FILES["retina_image"]["tmp_name"], $target_file)) {
    // تخزين المسار في قاعدة البيانات
    $stmt = $con->prepare("INSERT INTO patient_images (patient_id, image_path, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $patient_id, $target_file, $notes);
    $stmt->execute();

    echo "script>alert('تم رفع الصورة بنجاح.');</script>";
    echo "<script>window.location.href = 'patient-data.php?id=" . $patient_id . "';</script>";
} else {
    echo "<script>alert('عذراً، حدث خطأ أثناء رفع الصورة.');</script>";
    echo "<script>window.location.href = 'add-image2.php?id=" . $patient_id . "';</script>";
}
?>
