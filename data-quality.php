<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$active = clinic_active_patient_where($con, 'p');

function dq_rows(mysqli $con, string $sql): array
{
    $rows = [];
    try {
        $result = mysqli_query($con, $sql);
    } catch (mysqli_sql_exception $e) {
        return [];
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$missingPhone = dq_rows($con, "SELECT p.id, p.full_name, p.phone_no, p.notes FROM add_patient p WHERE $active AND (p.phone_no IS NULL OR TRIM(p.phone_no) = '') ORDER BY p.id DESC LIMIT 100");
$noVisits = dq_rows($con, "SELECT p.id, p.full_name, p.phone_no, p.notes FROM add_patient p LEFT JOIN visits v ON v.patient_id = p.id WHERE $active GROUP BY p.id HAVING COUNT(v.patient_id) = 0 ORDER BY p.id DESC LIMIT 100");
$duplicates = dq_rows($con, "SELECT phone_no, COUNT(*) total, GROUP_CONCAT(full_name SEPARATOR ' | ') names, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') ids FROM add_patient WHERE phone_no IS NOT NULL AND TRIM(phone_no) <> '' AND is_deleted = 0 GROUP BY phone_no HAVING COUNT(*) > 1 ORDER BY total DESC LIMIT 100");
$overdueFollowups = dq_rows($con, "SELECT f.id, f.patient_id AS id, p.full_name, p.phone_no, f.followup_date AS notes FROM followups f JOIN add_patient p ON p.id = f.patient_id WHERE f.status = 'pending' AND f.followup_date < CURDATE() AND $active ORDER BY f.followup_date ASC LIMIT 100");
$orphanProcedures = dq_rows($con, "SELECT s.id, s.patient_id, s.date, s.surgery_type FROM surgery_appointment s LEFT JOIN add_patient p ON p.id = s.patient_id WHERE p.id IS NULL ORDER BY s.id DESC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جودة البيانات</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 22px; font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; background: #f4f7fb; color: #172033; }
        .page { max-width: 1180px; margin: auto; }
        h1 { margin: 0 0 16px; color: #1d4ed8; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .card { background: #fff; border: 1px solid #e5edf5; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, .08); overflow: hidden; }
        h2 { margin: 0; padding: 14px 16px; background: #eef6ff; color: #1d4ed8; font-size: 19px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 10px; border-top: 1px solid #eef2f7; text-align: right; vertical-align: top; }
        a { color: #1d4ed8; font-weight: 800; text-decoration: none; }
        .empty { padding: 18px; color: #64748b; text-align: center; }
        @media (max-width: 820px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main class="page">
    <h1>جودة البيانات</h1>
    <div class="grid">
        <?php
        $cards = [
            'مرضى بدون رقم هاتف' => $missingPhone,
            'مرضى بدون زيارات' => $noVisits,
            'مراجعات متأخرة' => $overdueFollowups,
        ];
        foreach ($cards as $title => $rows):
        ?>
            <section class="card">
                <h2><?= h($title) ?> (<?= count($rows) ?>)</h2>
                <?php if (!$rows): ?><div class="empty">لا توجد مشاكل</div><?php endif; ?>
                <?php if ($rows): ?>
                    <table>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><a href="patient-data.php?id=<?= (int) $row['id'] ?>"><?= h($row['full_name']) ?></a></td>
                                <td><?= h($row['phone_no'] ?? '') ?></td>
                                <td><?= h($row['notes'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <section class="card">
            <h2>أرقام هاتف مكررة (<?= count($duplicates) ?>)</h2>
            <?php if (!$duplicates): ?><div class="empty">لا توجد مشاكل</div><?php endif; ?>
            <?php if ($duplicates): ?>
                <table>
                    <?php foreach ($duplicates as $row): ?>
                        <tr>
                            <td><?= h($row['phone_no']) ?></td>
                            <td><?= h($row['names']) ?></td>
                            <td><?= h($row['ids']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>مواعيد عمليات بدون مريض (<?= count($orphanProcedures) ?>)</h2>
            <?php if (!$orphanProcedures): ?><div class="empty">لا توجد مشاكل</div><?php endif; ?>
            <?php if ($orphanProcedures): ?>
                <table>
                    <?php foreach ($orphanProcedures as $row): ?>
                        <tr>
                            <td>#<?= (int) $row['id'] ?></td>
                            <td>Patient <?= (int) $row['patient_id'] ?></td>
                            <td><?= h($row['date']) ?></td>
                            <td><?= h($row['surgery_type']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
