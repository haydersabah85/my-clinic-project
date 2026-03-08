
<?php
session_start();
include "config.php";

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['pass'];

    $stmt = $con->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['pass'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];

            header("Location: main.php");
            exit;
        }
    }
    $error = "بيانات الدخول غير صحيحة";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول</title>

</head>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

*{
  box-sizing:border-box;
  font-family:'Cairo',sans-serif;
}

:root{
  --bg: linear-gradient(135deg,#0d6efd,#0b5ed7,#198754);
  --card:#ffffff;
  --text:#222;
  --input:#fff;
  --border:#ddd;
}

body.dark{
  --bg: linear-gradient(135deg,#121212,#1f2933);
  --card:#1e1e1e;
  --text:#f1f1f1;
  --input:#2a2a2a;
  --border:#333;
}

body{
  margin:0;
  height:100vh;
  display:flex;
  justify-content:center;
  align-items:center;
  background:var(--bg);
  transition:.3s;
}

.login-box{
  background:var(--card);
  width:360px;
  padding:30px;
  border-radius:20px;
  box-shadow:0 20px 40px rgba(0,0,0,.25);
  animation:fadeIn .6s ease;
  color:var(--text);
}

@keyframes fadeIn{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}

.login-box h3{
  text-align:center;
  margin-bottom:20px;
  color:#0d6efd;
}

.input-group{
  position:relative;
  margin-bottom:15px;
}

.input-group input{
  width:100%;
  padding:12px 42px 12px 12px;
  border-radius:12px;
  border:1px solid var(--border);
  background:var(--input);
  color:var(--text);
  font-size:14px;
}

.input-group input:focus{
  outline:none;
  border-color:#0d6efd;
  box-shadow:0 0 0 3px rgba(13,110,253,.15);
}

.toggle-pass{
  position:absolute;
  left:12px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  font-size:16px;
  opacity:.7;
}

button{
  width:100%;
  padding:12px;
  border:none;
  border-radius:14px;
  background:linear-gradient(135deg,#0d6efd,#198754);
  color:#fff;
  font-size:15px;
  cursor:pointer;
  transition:.3s;
}

button:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 20px rgba(0,0,0,.25);
}

.error{
  margin-top:10px;
  text-align:center;
  color:#dc3545;
  font-size:14px;
}

/* زر الدارك مود */
.theme-toggle {
   position:absolute;
  top:20px;
  margin: 0 20px;
  
  background:rgba(255,255,255,.25);
  border:none;
  padding:8px 12px;
  border-radius:12px;
  cursor:pointer;
  font-size:14px;
}
</style>


<body>

<button class="theme-toggle" onclick="themeToggle()">🌙</button>

<form method="post" class="login-box">
  <h3>تسجيل الدخول</h3>

  <div class="input-group">
    <input type="text" name="username" placeholder="اسم المستخدم" required>
  </div>

  <div class="input-group">
    <input type="password" name="pass" id="password" placeholder="كلمة المرور" required>
    <span class="toggle-pass" onclick="togglePassword()">👁️</span>
  </div>

  <button name="login">دخول</button>

  <div class="error">
    <?= $error ?? '' ?>
  </div>
</form>

<script>
function togglePassword(){
  const pass = document.getElementById("password");
  pass.type = pass.type === "password" ? "text" : "password";
}

 /* Dark Mode */
const themeToggleBtn = document.querySelector('.theme-toggle');
function themeToggle() {
  document.body.classList.toggle('dark');
  if(document.body.classList.contains('dark')){
    themeToggleBtn.textContent = '☀️';
  } else {
    themeToggleBtn.textContent = '🌙';
  }
  localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}
</script>

</body>
</html>

