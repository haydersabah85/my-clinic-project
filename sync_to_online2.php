<?php
include 'config.php';
include 'auth.php';
$requiredPermissions = ['sync'];
include 'admin-only.php';

if (!$IS_LOCAL) {
    http_response_code(403);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>غير متاح على النسخة السحابية</h3>";
    echo "<p>تم إيقاف هذا المسار القديم. استخدم المزامنة الآمنة.</p>";
    echo "<a href='settings.php'>العودة إلى الإعدادات</a>";
    echo "</body></html>";
    exit;
}

header('Location: sync_to_online_safe.php?legacy=2', true, 302);
exit;
