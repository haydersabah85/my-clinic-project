<?php
include 'config.php';
include 'auth.php';
include 'admin-only.php';

if (!$IS_LOCAL) {
    http_response_code(403);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>غير متاح على النسخة السحابية</h3>";
    echo "<p>زر Backup Online يجب تشغيله من السيرفر المحلي في العيادة فقط.</p>";
    echo "<a href='settings.php'>العودة إلى الإعدادات</a>";
    echo "</body></html>";
    exit;
}

echo "<h2>رفع قاعدة البيانات إلى السحابة</h2>";

// =====================================
// إعدادات
// =====================================
$secret = "MY_SECRET_KEY";

// اسم قاعدة البيانات المحلية
$dbName = "clinic";

// بيانات الاتصال المحلية (من config.php)
$dbUser = "root";
$dbPass = "";
// إذا كان config.php يستخدم $servername = localhost
$dbHost = "localhost";

// مسار mysqldump في XAMPP
$mysqldump = "C:/xampp/mysql/bin/mysqldump.exe";

// اسم الملف المؤقت
$backupFile = __DIR__ . "/clinic_backup.sql";

// رابط الرفع إلى الاستضافة
$uploadUrl = "https://hayder-sabah-clinic.com/upload_backup.php?auth=" . urlencode($secret);

// رابط الاستيراد
$importUrl = "https://hayder-sabah-clinic.com/import_backup.php?auth=" . urlencode($secret);

// =====================================
// 1. إنشاء النسخة الاحتياطية
// =====================================
echo "1. Creating backup...<br>";

$command =
    '"' . $mysqldump . '" ' .
    '--host=' . escapeshellarg($dbHost) . ' ' .
    '--user=' . escapeshellarg($dbUser) . ' ' .
    '--password=' . escapeshellarg($dbPass) . ' ' .
    '--single-transaction --routines --triggers ' .
    escapeshellarg($dbName) .
    ' > ' . escapeshellarg($backupFile);

exec($command, $output, $resultCode);

if ($resultCode !== 0 || !file_exists($backupFile) || filesize($backupFile) == 0) {
    die("❌ Failed to create backup.");
}

echo "✅ Backup created successfully.<br>";

// =====================================
// 2. رفع الملف إلى الاستضافة
// =====================================
echo "2. Uploading backup...<br>";

$fileData = file_get_contents($backupFile);

$ch = curl_init($uploadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/sql",
    "Content-Length: " . strlen($fileData)
]);

$uploadResponse = curl_exec($ch);

if (curl_errno($ch)) {
    die("❌ Upload failed: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    die("❌ Upload failed. HTTP Code: " . $httpCode);
}

echo "✅ Backup uploaded successfully.<br>";

// =====================================
// 3. تشغيل الاستيراد على الموقع
// =====================================
echo "3. Importing backup online...<br>";

$importResponse = file_get_contents($importUrl);

echo "<pre>" . htmlspecialchars($importResponse) . "</pre>";

// =====================================
// 4. حذف الملف المؤقت (اختياري)
// =====================================
if (file_exists($backupFile)) {
    unlink($backupFile);
}

echo "<br><h3>✅ Database upload completed successfully.</h3>";
