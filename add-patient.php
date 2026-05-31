<?php
include "config.php";
include "auth.php";
include_once "clinic_helpers.php";
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <title>إضافة مريض</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/dark-mode.css">
  <script src="assets/theme.js" defer></script>
  <style>
    :root {
      --page-bg: #f5f7fb;
      --surface: #ffffff;
      --surface-soft: #f8fafc;
      --surface-strong: #eef6f6;
      --text: #172033;
      --muted: #65758b;
      --border: #d9e4ec;
      --primary: #0f766e;
      --primary-hover: #115e59;
      --blue: #2563eb;
      --blue-hover: #1d4ed8;
      --radius: 8px;
      --shadow: 0 20px 55px rgba(15, 23, 42, 0.09);
      --focus: 0 0 0 4px rgba(15, 118, 110, 0.16);
    }

    body[data-theme="dark"],
    body.dark {
      --page-bg: #08131f;
      --surface: #111d2b;
      --surface-soft: #0c1624;
      --surface-strong: #10292b;
      --text: #e8eef6;
      --muted: #a9b9ca;
      --border: rgba(148, 163, 184, 0.24);
      --shadow: 0 22px 55px rgba(0, 0, 0, 0.35);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
      background:
        linear-gradient(180deg, rgba(15, 118, 110, 0.10) 0, rgba(37, 99, 235, 0.04) 260px, transparent 520px),
        var(--page-bg);
      color: var(--text);
    }

    .page {
      width: min(1080px, calc(100% - 36px));
      margin: 0 auto;
      padding: 28px 0 52px;
    }

    .page-header {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      align-items: end;
      gap: 18px;
      margin-bottom: 18px;
      padding: 8px 2px 0;
    }

    .page-title {
      margin: 0;
      font-size: 30px;
      line-height: 1.25;
      font-weight: 800;
    }

    .page-subtitle {
      max-width: 680px;
      margin: 8px 0 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.9;
      font-weight: 700;
    }

    .header-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 9px 16px;
      border-radius: var(--radius);
      border: 1px solid transparent;
      text-decoration: none;
      font: inherit;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
      white-space: nowrap;
    }

    .btn:hover {
      transform: translateY(-1px);
    }

    .btn:focus-visible {
      outline: none;
      box-shadow: var(--focus);
    }

    .btn-primary {
      background: var(--blue);
      color: #fff;
      box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
    }

    .btn-primary:hover {
      background: var(--blue-hover);
    }

    .btn-muted {
      background: rgba(255, 255, 255, 0.72);
      color: var(--text);
      border-color: var(--border);
    }

    body[data-theme="dark"] .btn-muted,
    body.dark .btn-muted {
      background: rgba(17, 29, 43, 0.72);
    }

    .form-card {
      position: relative;
      overflow: hidden;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 26px;
    }

    .form-card::before {
      content: "";
      position: absolute;
      inset: 0 0 auto;
      height: 5px;
      background: linear-gradient(90deg, var(--primary), var(--blue));
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 16px;
    }

    .field {
      grid-column: span 3;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .field-wide {
      grid-column: 1 / -1;
    }

    label {
      color: var(--muted);
      font-size: 13px;
      line-height: 1.4;
      font-weight: 800;
    }

    input,
    select,
    textarea {
      width: 100%;
      min-height: 46px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      background: var(--surface-soft);
      color: var(--text);
      padding: 11px 13px;
      font: inherit;
      font-size: 15px;
      line-height: 1.5;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    textarea {
      min-height: 120px;
      resize: vertical;
    }

    input::placeholder,
    textarea::placeholder {
      color: #94a3b8;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: var(--focus);
      background: var(--surface);
    }

    .form-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 22px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .submit-btn {
      background: var(--primary);
      color: #fff;
      min-width: 190px;
      box-shadow: 0 10px 24px rgba(15, 118, 110, 0.22);
    }

    .submit-btn:hover {
      background: var(--primary-hover);
    }

    @media (max-width: 780px) {
      .page {
        width: min(100% - 20px, 1080px);
        padding-top: 16px;
      }

      .page-header {
        grid-template-columns: 1fr;
        align-items: stretch;
      }

      .page-title {
        font-size: 24px;
      }

      .header-actions,
      .form-footer {
        flex-direction: column;
      }

      .btn,
      .header-actions {
        width: 100%;
      }

      .form-card {
        padding: 20px;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      .field {
        grid-column: 1 / -1;
      }
    }
  </style>
</head>

<body>
  <main class="page">
    <header class="page-header">
      <div>
        <h1 class="page-title">إضافة مريض جديد</h1>
        <p class="page-subtitle">سجل البيانات الأساسية للمريض قبل فتح الملف أو إضافة الزيارات.</p>
      </div>
      <nav class="header-actions" aria-label="روابط سريعة">
        <a class="btn btn-muted" href="dashboard.php">لوحة التحكم</a>
        <a class="btn btn-muted" href="main.php">كل المرضى</a>
        <a class="btn btn-primary" href="visits.php">زيارات اليوم</a>
      </nav>
    </header>

    <form class="form-card" action="add-patient2.php" method="post" autocomplete="off">
      <div class="form-grid">
        <div class="field field-wide">
          <label for="full_name">الاسم الرباعي</label>
          <input type="text" id="full_name" name="full_name" required autofocus>
        </div>

        <div class="field">
          <label for="age">العمر</label>
          <input type="text" id="age" name="age" required>
        </div>

        <div class="field">
          <label for="date_of_birth">تاريخ الميلاد</label>
          <input type="date" id="date_of_birth" name="date_of_birth">
        </div>

        <div class="field">
          <label for="gender">الجنس</label>
          <select id="gender" name="gender">
            <option value="ذكر">ذكر</option>
            <option value="أنثى">أنثى</option>
          </select>
        </div>

        <div class="field">
          <label for="phone_no">الموبايل</label>
          <input type="text" id="phone_no" name="phone_no" pattern="[0-9]+" placeholder="07xxxxxxxxx">
        </div>

        <div class="field">
          <label for="phone_no_alt">موبايل بديل</label>
          <input type="text" id="phone_no_alt" name="phone_no_alt" pattern="[0-9]*">
        </div>

        <div class="field">
          <label for="address">العنوان</label>
          <input type="text" id="address" name="address">
        </div>

        <div class="field field-wide">
          <label for="notes">الملاحظات</label>
          <textarea id="notes" name="notes"></textarea>
        </div>
      </div>

      <div class="form-footer">
        <a class="btn btn-muted" href="main.php">إلغاء</a>
        <button class="btn submit-btn" type="submit" name="submit">إضافة المريض</button>
      </div>
    </form>
  </main>
</body>

</html>
