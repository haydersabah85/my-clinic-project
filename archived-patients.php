<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

if (isset($_GET['restore'])) {
    $id = (int) $_GET['restore'];
    $stmt = mysqli_prepare($con, "UPDATE add_patient SET is_deleted = 0, deleted_at = NULL, deleted_by = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    clinic_audit($con, 'restore', 'add_patient', $id, null, ['is_deleted' => 0]);
    header("Location: archived-patients.php");
    exit;
}

$patients = mysqli_query($con, "
    SELECT id, full_name, phone_no, notes, deleted_at, deleted_by
    FROM add_patient
    WHERE is_deleted = 1
    ORDER BY deleted_at DESC, id DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أرشيف المرضى</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        body { margin: 0; padding: 22px; font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; background: #f4f7fb; color: #172033; }
        .page { max-width: 1100px; margin: auto; background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); overflow: auto; }
        h1 { margin: 0; padding: 18px; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; min-width: 820px; }
        th, td { padding: 11px; border-top: 1px solid #eef2f7; text-align: right; }
        th { background: #eef6ff; color: #1d4ed8; }
        a { color: #fff; background: #0f766e; border-radius: 9px; padding: 7px 10px; text-decoration: none; font-weight: 900; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
    </style>
</head>
<body>
<main class="page">
    <h1>أرشيف المرضى</h1>
    <?php if (!$patients || mysqli_num_rows($patients) === 0): ?>
        <div class="empty">لا يوجد مرضى في الأرشيف.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>الاسم</th><th>الهاتف</th><th>الملاحظات</th><th>تاريخ الأرشفة</th><th>بواسطة</th><th>استرجاع</th></tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($patients)): ?>
                    <tr>
                        <td><?= h($row['full_name']) ?></td>
                        <td><?= h($row['phone_no']) ?></td>
                        <td><?= h($row['notes']) ?></td>
                        <td><?= h($row['deleted_at']) ?></td>
                        <td><?= h($row['deleted_by']) ?></td>
                        <td><a href="archived-patients.php?restore=<?= (int) $row['id'] ?>" onclick="return confirm('استرجاع هذا المريض؟')">استرجاع</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
