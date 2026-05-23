<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$logs = mysqli_query($con, "SELECT * FROM audit_log ORDER BY created_at DESC, id DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل العمليات</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        body { margin: 0; padding: 22px; font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; background: #f4f7fb; color: #172033; }
        .page { max-width: 1180px; margin: auto; background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); overflow: auto; }
        h1 { margin: 0; padding: 18px; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 10px; border-top: 1px solid #eef2f7; text-align: right; vertical-align: top; }
        th { background: #eef6ff; color: #1d4ed8; }
        small { color: #64748b; }
    </style>
</head>
<body>
<main class="page">
    <h1>سجل العمليات</h1>
    <table>
        <thead><tr><th>الوقت</th><th>المستخدم</th><th>العملية</th><th>الجدول</th><th>السجل</th><th>تفاصيل</th></tr></thead>
        <tbody>
            <?php while ($row = $logs ? mysqli_fetch_assoc($logs) : null): ?>
                <tr>
                    <td><?= h($row['created_at']) ?></td>
                    <td><?= h($row['user_name']) ?></td>
                    <td><?= h($row['action']) ?></td>
                    <td><?= h($row['table_name']) ?></td>
                    <td><?= h($row['record_id']) ?></td>
                    <td><small><?= h($row['new_value'] ?: $row['old_value']) ?></small></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
