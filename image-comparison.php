<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$patient_id = (int) ($_GET['id'] ?? $_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    die("لم يتم تحديد المريض");
}

$patient_stmt = mysqli_prepare($con, "SELECT id, full_name FROM add_patient WHERE id = ?");
mysqli_stmt_bind_param($patient_stmt, "i", $patient_id);
mysqli_stmt_execute($patient_stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($patient_stmt));
if (!$patient) {
    die("المريض غير موجود");
}

$images = [];
if (clinic_table_exists($con, 'patient_images')) {
    $dateColumn = clinic_column_exists($con, 'patient_images', 'uploaded_at') ? 'uploaded_at' : 'id';
    $stmt = mysqli_prepare($con, "SELECT * FROM patient_images WHERE patient_id = ? ORDER BY `$dateColumn` DESC");
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
}

$leftId = (int) ($_GET['left'] ?? ($images[1]['id'] ?? ($images[0]['id'] ?? 0)));
$rightId = (int) ($_GET['right'] ?? ($images[0]['id'] ?? 0));
function find_image(array $images, int $id): ?array
{
    foreach ($images as $image) {
        if ((int) $image['id'] === $id) {
            return $image;
        }
    }
    return null;
}
$left = find_image($images, $leftId);
$right = find_image($images, $rightId);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقارنة الصور - <?= h($patient['full_name']) ?></title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 22px; font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; background: #f4f7fb; color: #172033; }
        .page { max-width: 1240px; margin: auto; }
        .top, .panel { background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); }
        .top { padding: 18px; margin-bottom: 16px; }
        h1 { margin: 0 0 12px; color: #1d4ed8; }
        form { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; }
        select, button { border: 1px solid #d9e2ec; border-radius: 10px; padding: 10px 12px; font-family: inherit; font-weight: 800; }
        button { background: #1d4ed8; color: #fff; cursor: pointer; }
        .compare { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .panel { padding: 14px; min-height: 360px; }
        .panel img { width: 100%; max-height: 72vh; object-fit: contain; background: #0f172a; border-radius: 12px; }
        .meta { color: #64748b; margin-top: 8px; }
        .empty { padding: 26px; text-align: center; color: #64748b; }
        @media (max-width: 860px) { form, .compare { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="page">
    <section class="top">
        <h1>مقارنة الصور - <?= h($patient['full_name']) ?></h1>
        <form method="get">
            <input type="hidden" name="id" value="<?= $patient_id ?>">
            <select name="left">
                <?php foreach ($images as $image): ?>
                    <option value="<?= (int) $image['id'] ?>" <?= (int) $image['id'] === $leftId ? 'selected' : '' ?>>
                        <?= h(($image['uploaded_at'] ?? $image['id']) . ' - ' . ($image['notes'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="right">
                <?php foreach ($images as $image): ?>
                    <option value="<?= (int) $image['id'] ?>" <?= (int) $image['id'] === $rightId ? 'selected' : '' ?>>
                        <?= h(($image['uploaded_at'] ?? $image['id']) . ' - ' . ($image['notes'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">مقارنة</button>
        </form>
    </section>

    <?php if (count($images) < 1): ?>
        <div class="panel empty">لا توجد صور لهذا المريض.</div>
    <?php else: ?>
        <section class="compare">
            <?php foreach ([$left, $right] as $image): ?>
                <div class="panel">
                    <?php if ($image): ?>
                        <img src="<?= h($image['image_path']) ?>" alt="">
                        <div class="meta"><?= h($image['uploaded_at'] ?? '') ?></div>
                        <div class="meta"><?= h($image['notes'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="empty">اختر صورة</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
