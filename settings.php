<?php 
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
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
        background-color: rgba(0, 123, 255, 1)  ;
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

</style>


<body>
    <h1>Settings Page</h1>
    <p>Here you can configure your application settings.</p>


<div>
<a id="main" href="main.php">الصفحة الرئيسية</a>
<a id="restore" href="restore.php">Restore</a>
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

    
</div>
</body>
</html>