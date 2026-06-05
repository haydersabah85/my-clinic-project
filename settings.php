<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';
include 'admin-only.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);

$modeMessage = '';

if (isset($_POST['save_runtime_mode'])) {
    $writeEnabled = isset($_POST['online_write_enabled']) ? '0' : '1';
    if (clinic_set_app_setting($con, 'online_write_lock', $writeEnabled)) {
        $modeMessage = "<p style='color:green'>✔ تم تحديث وضع الكتابة بنجاح</p>";
    } else {
        $modeMessage = "<p style='color:red'>❌ فشل تحديث وضع الكتابة</p>";
    }
}

$isOnlineWriteLocked = clinic_is_online_write_locked($con, (bool) $IS_LOCAL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
</head>

<script src="assets/theme.js" defer></script>

<style>
    body[data-theme="dark"] {
        background-color: #121212;
        color: #e0e0e0;
    }


    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f4f4f4;
    }

    h1 {
        color: #333;
        margin-bottom: 20px;
        text-align: center;

    }

    p {
        font-size: 16px;
        color: #555;
        text-align: center;
    }

    div {
        text-align: center;
        margin-top: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;

    }


    #main {
        text-decoration: none;
        background-color: rgba(255, 136, 0, 1);
        font-size: 18px;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        margin-top: 20px;
        cursor: pointer;
        font-family: Arial, sans-serif;
    }

    #main:hover {
        background-color: rgba(255, 100, 0, 1);
    }

    #restore {

        text-decoration: none;
        background-color: rgba(0, 123, 255, 1);
        font-size: 18px;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        margin-top: 20px;
        cursor: pointer;
    }

    #restore:hover {
        background-color: rgba(0, 90, 190, 1);
    }

    #backup {
        text-decoration: none;
        background-color: rgb(161, 53, 175);
        font-size: 18px;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        margin-top: 20px;
        cursor: pointer;
    }

    #backup:hover {
        background-color: rgb(120, 40, 150);
    }

    button {
        padding: 10px 20px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 18px;
        margin-top: 20px;
    }

    button:hover {
        background-color: #218838;
    }

    .runtime-panel {
        margin-top: 28px;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 14px;
        max-width: 580px;
        margin-inline: auto;
        background: #fff;
        display: block;
        text-align: right;
    }

    .runtime-panel h3 {
        margin: 0 0 8px;
    }

    .runtime-status {
        margin: 8px 0 12px;
        font-weight: 700;
    }

    .runtime-note {
        color: #555;
        line-height: 1.7;
        margin-bottom: 10px;
    }

    .runtime-form {
        display: block;
        text-align: right;
        margin-top: 10px;
    }

    .runtime-form label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-weight: 700;
    }

    .runtime-form button {
        margin-top: 0;
    }
</style>


<body>
    <h1>Settings Page</h1>
    <p>Here you can configure your application settings.</p>


    <div>
        <a id="main" href="dashboard.php">الصفحة الرئيسية</a>
        <a id="restore" href="restore.php">Restore</a>
        <a id="backup" href="backup_and_upload.php">Backup Online</a>
        <form method="post">
            <button name="manual_backup"
                onclick="return confirm('هل تريد إنشاء نسخة احتياطية الآن؟')">
                Backup</button>
        </form>

        <?php
        include "config_backup.php";

        if (isset($_POST['manual_backup'])) {

            $date = date("Y-m-d_H-i-s");
            $file = $BACKUP_PATH . "/manual_backup_$date.sql";

            $command = "\"$MYSQLDUMP_PATH\" --user=$DB_USER --password=$DB_PASS --host=$DB_HOST $DB_NAME > \"$file\"";

            system($command, $result);

            if ($result === 0) {
                echo "<p style='color:green'>✔ تم إنشاء النسخة الاحتياطية بنجاح</p>";
            } else {
                echo "<p style='color:red'>❌ فشل إنشاء النسخة الاحتياطية</p>";
            }
        }
        ?>

        <?php echo $modeMessage; ?>

        <section class="runtime-panel" dir="rtl">
            <h3>وضع التشغيل أثناء الطوارئ</h3>
            <div class="runtime-status">
                حالة النسخة السحابية الآن:
                <?php if ($isOnlineWriteLocked): ?>
                    <span style="color:#b30000">قراءة فقط (الكتابة مقفلة)</span>
                <?php else: ?>
                    <span style="color:#0a7a20">قراءة وكتابة مفعلة</span>
                <?php endif; ?>
            </div>
            <div class="runtime-note">
                عند انقطاع الإنترنت في العيادة: اقفل الكتابة على النسخة السحابية لتجنب تضارب البيانات، واعمل محليا.
                بعد رجوع الإنترنت ورفع البيانات: أعد تفعيل الكتابة السحابية.
            </div>
            <form class="runtime-form" method="post">
                <label>
                    <input type="checkbox" name="online_write_enabled" value="1" <?php echo $isOnlineWriteLocked ? '' : 'checked'; ?>>
                    السماح بالكتابة على النسخة السحابية
                </label>
                <button type="submit" name="save_runtime_mode" onclick="return confirm('تأكيد تحديث وضع الكتابة السحابية؟')">حفظ وضع التشغيل</button>
            </form>
        </section>


    </div>
</body>

</html>