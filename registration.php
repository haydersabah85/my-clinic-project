
<?php
include "config.php";

if(isset($_POST['register'])){
    $name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $con->prepare("INSERT INTO users (full_name, username, pass, role) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $username, $password, $role);

    if($stmt->execute()){
        $msg = "تم إنشاء المستخدم بنجاح";
    } else {
        $msg = "اسم المستخدم موجود مسبقاً";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة مستخدم</title>

<link rel="stylesheet" href="assets/theme.css">
<script src="assets/theme.js" defer></script>

<style>
body{font-family:Cairo;background:#f4f6f9}
form{width:350px;margin:50px auto;background:#fff;padding:20px;border-radius:12px}
input,select,button{width:100%;padding:10px;margin-top:10px}
button{background:#0d6efd;color:#fff;border:none}
</style>
</head>
<body>

<form method="post">
<h3>إضافة مستخدم جديد</h3>
<input type="text" name="full_name" placeholder="الاسم الكامل" required>
<input type="text" name="username" placeholder="اسم المستخدم" required>
<input type="password" name="pass" placeholder="كلمة المرور" required>

<select name="role" required>
  <option value="">اختر الصلاحية</option>
  <option value="admin">أدمن</option>
  <option value="secretary">سكرتيرة</option>
</select>

<button name="register">حفظ</button>
<p><?= $msg ?? '' ?></p>
</form>

</body>
</html>
