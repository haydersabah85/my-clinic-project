<?php
session_start();
include "config.php";
include_once "clinic_helpers.php";

clinic_ensure_infrastructure($con);
clinic_ensure_column($con, 'users', 'permissions_json', 'LONGTEXT NULL');

$currentLanguage = clinic_language();
$isEnglish = $currentLanguage === 'en';

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['pass'];

  $stmt = $con->prepare("SELECT * FROM users WHERE username=?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['pass'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['full_name'] = $user['full_name'];
      $_SESSION['name'] = $user['full_name'];
      $_SESSION['permissions'] = json_decode($user['permissions_json'] ?? '[]', true) ?: [];

      header("Location: dashboard.php");
      exit;
    }
  }
  $error = clinic_t('login_invalid_credentials');
}
?>

<!DOCTYPE html>
<html lang="<?= $isEnglish ? 'en' : 'ar' ?>" dir="<?= $isEnglish ? 'ltr' : 'rtl' ?>">

<head>
  <meta charset="UTF-8">
  <title><?= h(clinic_t('login_page_title')) ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
  <link rel="stylesheet" href="assets/branding/branding.css">
  <link rel="stylesheet" href="assets/dark-mode.css">
  <script src="assets/theme.js" defer></script>
</head>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

  * {
    box-sizing: border-box;
    font-family: 'Cairo', sans-serif;
  }

  :root {
    --bg: radial-gradient(circle at top right, rgba(25, 135, 84, .35), transparent 34%),
      radial-gradient(circle at left center, rgba(13, 110, 253, .28), transparent 28%),
      linear-gradient(135deg, #06121f 0%, #0f2239 55%, #14384d 100%);
    --card: #fefefe;
    --card-soft: #f7fbff;
    --text: #122033;
    --muted: #5c6b82;
    --input: #fff;
    --border: #d9e3ef;
    --accent: #0d6efd;
    --accent-2: #198754;
  }

  body.dark {
    --bg: radial-gradient(circle at top right, rgba(25, 135, 84, .14), transparent 34%),
      radial-gradient(circle at left center, rgba(13, 110, 253, .15), transparent 28%),
      linear-gradient(135deg, #08111d 0%, #101a28 55%, #132538 100%);
    --card: #131b27;
    --card-soft: #182234;
    --text: #f4f7fb;
    --muted: #9eb0c6;
    --input: #1c2635;
    --border: #2a3850;
  }

  body {
    margin: 0;
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
    transition: .3s;
    padding: 32px 20px;
  }

  .page-shell {
    min-height: calc(100vh - 64px);
    display: grid;
    grid-template-columns: minmax(320px, 1fr) minmax(280px, .95fr);
    gap: 24px;
    align-items: center;
    width: min(1140px, 100%);
    margin: 0 auto;
  }

  .login-box {
    background: var(--card);
    width: 100%;
    padding: 36px;
    border-radius: 28px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
    animation: fadeIn .6s ease;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, .08);
    position: relative;
    overflow: hidden;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px)
    }

    to {
      opacity: 1;
      transform: translateY(0)
    }
  }

  .login-box::before {
    content: '';
    position: absolute;
    inset: -60px auto auto -60px;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(13, 110, 253, .14), transparent 68%);
  }

  .login-box h3 {
    margin: 0 0 8px;
    font-size: 32px;
    line-height: 1.25;
    color: var(--text);
  }

  .login-logo {
    margin: 0 0 14px;
    display: flex;
    justify-content: center;
  }

  .login-logo .brand-with-logo {
    max-width: 100%;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: var(--card-soft);
    box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
  }

  .login-logo .brand-text {
    min-width: 0;
  }

  .login-logo .brand-title {
    color: var(--text);
    font-size: 16px;
    white-space: normal;
  }

  .login-logo .brand-subtitle {
    color: var(--muted);
    font-size: 11px;
    white-space: normal;
  }

  .login-box .subtitle {
    margin: 0 0 24px;
    color: var(--muted);
    font-size: 14px;
  }

  .form-grid {
    display: grid;
    gap: 14px;
  }

  .input-group {
    position: relative;
  }

  .input-group input {
    width: 100%;
    padding: 14px 44px 14px 14px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: var(--input);
    color: var(--text);
    font-size: 14px;
  }

  .input-group input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
  }

  .toggle-pass {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 16px;
    opacity: .7;
  }

  button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: .3s;
  }

  button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, .25);
  }

  .error {
    margin-top: 10px;
    text-align: center;
    color: #dc3545;
    font-size: 14px;
    min-height: 20px;
  }

  /* زر الدارك مود */
  .theme-toggle {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(255, 255, 255, .12);
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 14px;
    backdrop-filter: blur(12px);
  }

  .register-panel {
    color: #fff;
    padding: 24px;
  }

  .brand-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(12px);
    font-size: 13px;
    margin-bottom: 18px;
  }

  .register-panel h1 {
    margin: 0 0 14px;
    font-size: 48px;
    line-height: 1.1;
  }

  .register-panel p {
    margin: 0 0 18px;
    color: rgba(255, 255, 255, .84);
    max-width: 460px;
    line-height: 1.9;
  }

  .feature-list {
    display: grid;
    gap: 12px;
    margin: 24px 0 28px;
  }

  .feature-item {
    padding: 14px 16px;
    border-radius: 18px;
    background: rgba(255, 255, 255, .1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, .12);
  }

  .feature-item strong {
    display: block;
    margin-bottom: 4px;
  }

  .register-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 13px 18px;
    border-radius: 14px;
    text-decoration: none;
    color: #fff;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .18);
    transition: .25s ease;
  }

  .register-link:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, .22);
  }

  @media (max-width: 920px) {
    .page-shell {
      grid-template-columns: 1fr;
    }

    .register-panel h1 {
      font-size: 38px;
    }
  }

  @media (max-width: 540px) {
    body {
      padding: 18px 12px;
    }

    .login-box {
      padding: 24px 18px;
      border-radius: 22px;
    }

    .register-panel {
      padding: 10px 6px 0;
    }

    .register-panel h1 {
      font-size: 30px;
    }
  }
</style>


<body>

  <button class="theme-toggle" onclick="themeToggle()">🌙</button>

  <div class="page-shell">
    <form method="post" class="login-box">
      <div class="login-logo">
        <div class="brand-with-logo">
          <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
          <div class="brand-text">
            <span class="brand-title"><?= h(clinic_t('clinic_name')) ?></span>

          </div>
        </div>
      </div>
      <h3><?= h(clinic_t('login_heading')) ?></h3>
      <p class="subtitle"><?= h(clinic_t('login_subtitle')) ?></p>

      <div class="form-grid">
        <div class="input-group">
          <input type="text" name="username" placeholder="<?= h(clinic_t('username_label')) ?>" required>
        </div>

        <div class="input-group">
          <input type="password" name="pass" id="password" placeholder="<?= h(clinic_t('password_label')) ?>" required>
          <span class="toggle-pass" onclick="togglePassword()">👁️</span>
        </div>

        <button name="login"><?= h(clinic_t('login_button')) ?></button>

        <div class="error">
          <?= h($error ?? '') ?>
        </div>
      </div>
    </form>

    <section class="register-panel">
      <div class="brand-pill">
        <span class="page-brand-inline">
          <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
          <span><?= h(clinic_t('smart_clinic_system')) ?></span>
        </span>
      </div>
      <h1><?= h(clinic_t('staff_accounts_hero')) ?></h1>
      <p>
        <?= h(clinic_t('staff_accounts_description')) ?>
      </p>

      <div class="feature-list">
        <div class="feature-item">
          <strong><?= h(clinic_t('quick_staff_add_title')) ?></strong>
          <span><?= h(clinic_t('quick_staff_add_desc')) ?></span>
        </div>
        <div class="feature-item">
          <strong><?= h(clinic_t('flexible_permissions_title')) ?></strong>
          <span><?= h(clinic_t('flexible_permissions_desc')) ?></span>
        </div>
        <div class="feature-item">
          <strong><?= h(clinic_t('elegant_mobile_ui_title')) ?></strong>
          <span><?= h(clinic_t('elegant_mobile_ui_desc')) ?></span>
        </div>
      </div>

      <a class="register-link" href="registration.php"><?= h(clinic_t('open_staff_registration_page')) ?></a>
    </section>
  </div>

  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      pass.type = pass.type === "password" ? "text" : "password";
    }

    /* Dark Mode */
    const themeToggleBtn = document.querySelector('.theme-toggle');

    function themeToggle() {
      document.body.classList.toggle('dark');
      if (document.body.classList.contains('dark')) {
        themeToggleBtn.textContent = '☀️';
      } else {
        themeToggleBtn.textContent = '🌙';
      }
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark');
      themeToggleBtn.textContent = '☀️';
    }
  </script>

</body>

</html>