<?php

include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

function redirect_with_alert($message, $url)
{
    $safeMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
    $safeUrl = json_encode($url, JSON_UNESCAPED_UNICODE);
    echo "<script>alert($safeMessage);window.location.href=$safeUrl;</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_alert('طلب غير صالح.', 'visits.php');
}

$patient_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($patient_id <= 0) {
    redirect_with_alert('المريض غير صالح.', 'visits.php');
}

$select_query = "SELECT full_name FROM add_patient WHERE id = $patient_id LIMIT 1";
$select_result = mysqli_query($con, $select_query);
if (!$select_result || mysqli_num_rows($select_result) === 0) {
    redirect_with_alert('المريض غير موجود.', 'visits.php');
}

$patient_row = mysqli_fetch_assoc($select_result);
$patient_name = $patient_row['full_name'];

if (!isset($_FILES['retina_image'])) {
    redirect_with_alert('يرجى اختيار صورة قبل الرفع.', 'add-image.php?id=' . $patient_id);
}

$file = $_FILES['retina_image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح في إعدادات الخادم.',
        UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح في النموذج.',
        UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط.',
        UPLOAD_ERR_NO_FILE => 'لم يتم اختيار أي ملف.',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير موجود.',
        UPLOAD_ERR_CANT_WRITE => 'تعذر حفظ الملف على الخادم.',
        UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الملف بواسطة إضافة في PHP.'
    ];
    $message = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'حدث خطأ غير معروف أثناء رفع الصورة.';
    redirect_with_alert($message, 'add-image.php?id=' . $patient_id);
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$originalName = isset($file['name']) ? $file['name'] : '';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    redirect_with_alert('صيغة الصورة غير مدعومة. المسموح: JPG, JPEG, PNG, GIF, WEBP.', 'add-image.php?id=' . $patient_id);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
if ($finfo) {
    finfo_close($finfo);
}

if (!in_array($mimeType, $allowedMimeTypes, true)) {
    redirect_with_alert('الملف المرفوع ليس صورة صالحة.', 'add-image.php?id=' . $patient_id);
}

$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    redirect_with_alert('حجم الصورة كبير جداً. الحد الأقصى 10MB.', 'add-image.php?id=' . $patient_id);
}

$safePatientName = trim((string) $patient_name);

// Keep Arabic/Unicode letters and numbers, replacing only unsafe filesystem characters.
$safePatientName = preg_replace('/[^\p{L}\p{N}_ -]+/u', '_', $safePatientName);
$safePatientName = preg_replace('/\s+/u', '_', $safePatientName);
$safePatientName = preg_replace('/_+/u', '_', $safePatientName);
$safePatientName = trim($safePatientName, '_- ');

if ($safePatientName === '' || $safePatientName === null) {
    $safePatientName = 'patient_' . $patient_id;
}

$target_dir = "uploads/image_{$safePatientName}/";
if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
    redirect_with_alert('تعذر إنشاء مجلد الصور.', 'add-image.php?id=' . $patient_id);
}

try {
    $uniqueName = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
} catch (Exception $e) {
    $uniqueName = 'img_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $extension;
}

$target_file = $target_dir . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $target_file)) {
    redirect_with_alert('عذراً، حدث خطأ أثناء حفظ الصورة على الخادم.', 'add-image.php?id=' . $patient_id);
}

$hasUpdatedAt = clinic_column_exists($con, 'patient_images', 'updated_at');
$insertSql = $hasUpdatedAt
    ? "INSERT INTO patient_images (patient_id, image_path, notes, updated_at) VALUES (?, ?, ?, NOW())"
    : "INSERT INTO patient_images (patient_id, image_path, notes) VALUES (?, ?, ?)";

$stmt = $con->prepare($insertSql);
if (!$stmt) {
    @unlink($target_file);
    redirect_with_alert('تعذر حفظ بيانات الصورة في قاعدة البيانات.', 'add-image.php?id=' . $patient_id);
}

$stmt->bind_param("iss", $patient_id, $target_file, $notes);
if (!$stmt->execute()) {
    @unlink($target_file);
    $stmt->close();
    redirect_with_alert('فشل تسجيل الصورة في قاعدة البيانات.', 'add-image.php?id=' . $patient_id);
}

$stmt->close();
redirect_with_alert('تم رفع الصورة بنجاح.', 'patient-data.php?id=' . $patient_id);
