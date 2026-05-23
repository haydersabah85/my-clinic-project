<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['template_name'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $medicines = trim($_POST['medicines_text'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name !== '') {
        $stmt = mysqli_prepare($con, "INSERT INTO treatment_templates (template_name, diagnosis, medicines_text, notes, updated_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $diagnosis, $medicines, $notes);
        mysqli_stmt_execute($stmt);
        clinic_audit($con, 'create', 'treatment_templates', mysqli_insert_id($con), null, $_POST);
    }

    header("Location: treatment-templates.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = mysqli_prepare($con, "DELETE FROM treatment_templates WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    clinic_audit($con, 'delete', 'treatment_templates', $id);
    header("Location: treatment-templates.php");
    exit;
}

$templates = mysqli_query($con, "SELECT * FROM treatment_templates ORDER BY template_name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قوالب العلاج</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 22px; font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; background: #f4f7fb; color: #172033; }
        .page { max-width: 1120px; margin: auto; display: grid; grid-template-columns: 360px 1fr; gap: 16px; }
        .card { background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); padding: 18px; }
        h1, h2 { margin-top: 0; color: #1d4ed8; }
        label { display: block; margin-top: 10px; font-weight: 900; color: #334155; }
        input, textarea, button { width: 100%; border: 1px solid #d9e2ec; border-radius: 10px; padding: 10px 12px; font-family: inherit; }
        textarea { min-height: 90px; resize: vertical; }
        button { margin-top: 12px; background: #1d4ed8; color: #fff; font-weight: 900; cursor: pointer; }
        .template { border-top: 1px solid #eef2f7; padding: 14px 0; }
        .template:first-of-type { border-top: 0; }
        .template h3 { margin: 0 0 6px; color: #0f172a; }
        pre { white-space: pre-wrap; background: #f8fafc; border-radius: 10px; padding: 10px; color: #334155; }
        a { color: #dc2626; font-weight: 900; text-decoration: none; }
        @media (max-width: 860px) { .page { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="page">
    <section class="card">
        <h1>قالب علاج جديد</h1>
        <form method="post">
            <label>اسم القالب</label>
            <input name="template_name" required>
            <label>التشخيص</label>
            <textarea name="diagnosis"></textarea>
            <label>الأدوية والتعليمات</label>
            <textarea name="medicines_text" placeholder="مثال: Drug name - dose - frequency - duration"></textarea>
            <label>ملاحظات</label>
            <textarea name="notes"></textarea>
            <button type="submit">حفظ القالب</button>
        </form>
    </section>
    <section class="card">
        <h2>القوالب المحفوظة</h2>
        <?php if (!$templates || mysqli_num_rows($templates) === 0): ?>
            <p>لا توجد قوالب محفوظة.</p>
        <?php endif; ?>
        <?php while ($row = $templates ? mysqli_fetch_assoc($templates) : null): ?>
            <article class="template">
                <h3><?= h($row['template_name']) ?></h3>
                <?php if (!empty($row['diagnosis'])): ?><strong>التشخيص</strong><pre><?= h($row['diagnosis']) ?></pre><?php endif; ?>
                <?php if (!empty($row['medicines_text'])): ?><strong>الأدوية</strong><pre><?= h($row['medicines_text']) ?></pre><?php endif; ?>
                <?php if (!empty($row['notes'])): ?><strong>ملاحظات</strong><pre><?= h($row['notes']) ?></pre><?php endif; ?>
                <a href="treatment-templates.php?delete=<?= (int) $row['id'] ?>" onclick="return confirm('حذف القالب؟')">حذف</a>
            </article>
        <?php endwhile; ?>
    </section>
</main>
</body>
</html>
