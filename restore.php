<?php
include "config_backup.php";

include 'auth.php';

$files = glob($BACKUP_PATH . "/*.sql");
rsort($files); // الأحدث أولاً
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore</title>
</head>

<style>

    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f4f4f4;
        text-align: center;

    }

    h1 {
        color: #333;
        margin-bottom: 20px;
    }

    p {
        font-size: 16px;
        color: #555;
    }

    form {
        display: inline-block;
        margin-top: 30px;
        padding: 20px;
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    label {
        font-size: 16px;
        color: #555;
    }

    select {
        margin-top: 10px;
        padding: 5px;
        font-size: 16px;
    }

    button {
        margin-top: 20px;
        padding: 10px 15px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button:hover {
        background-color: #218838;
    }
    a {
        text-decoration: none;
        background-color: rgba(255, 136, 0, 1);
        font-size: 18px;
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        margin-top: 20px;
        display: flex;
        font-family: Arial, sans-serif;
        justify-content: center;
        align-items: center;
        width: 150px;
        margin-left: auto;
        margin-right: auto;
        cursor: pointer;
        
    }
    a:hover {
        background-color: rgba(255, 100, 0, 1);
    }


</style>

<body>
    <h1>استعادة النسخة الاحتياطية</h1>
    <p>هنا يمكنك استعادة نسخة احتياطية من قاعدة البيانات الخاصة بك.</p>

    
<form method="post">
    <label>اختر النسخة الاحتياطية</label><br>

    <select name="backup_file" required style="width:300px">
        <option value="">-- اختر نسخة --</option>
        <?php foreach ($files as $file): ?>
            <option value="<?= basename($file) ?>">
                <?= basename($file) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <button type="submit" name="restore"
        onclick="return confirm('⚠️ سيتم حذف البيانات الحالية واستبدالها بالنسخة المختارة. هل أنت متأكد؟')">
        استعادة النسخة الاحتياطية
    </button>
</form>


<?php


if (isset($_POST['restore'])) {

    $file = basename($_POST['backup_file']);
    $full_path = $BACKUP_PATH . "/" . $file;
   
    


    if (!file_exists($full_path)) {
        die("❌ الملف غير موجود");
    }

    // 🔒 Backup تلقائي قبل الاستعادة
    $date = date("Y-m-d_H-i-s");
    $safe_backup = $BACKUP_PATH . "/before_restore_$date.sql";

    $backup_cmd = "\"$MYSQLDUMP_PATH\" --user=$DB_USER --password=$DB_PASS --host=$DB_HOST $DB_NAME > \"$safe_backup\"";
    system($backup_cmd);

    // 🔄 تنفيذ الاستعادة
    $restore_cmd = "\"$MYSQL_PATH\" --user=$DB_USER --password=$DB_PASS --host=$DB_HOST $DB_NAME < \"$full_path\"";
    system($restore_cmd, $result);

    if ($result === 0) {
        echo "<p style='color:green'>✔ تمت الاستعادة بنجاح</p>";
    } else {
        echo "<p style='color:red'>❌ فشلت عملية الاستعادة</p>";
    }
}
?>
<a href="main.php">الصفحة الرئيسية</a>

    
</body>
</html>