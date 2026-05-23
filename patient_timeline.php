<?php
include "config.php";
include "auth.php";
include_once "clinic_helpers.php";

clinic_ensure_infrastructure($con);

$patient_id = (int) ($_GET['id'] ?? 0);
if ($patient_id <= 0) {
    die("لم يتم تحديد المريض");
}

$patient_stmt = mysqli_prepare($con, "SELECT id, full_name, phone_no, age, notes FROM add_patient WHERE id = ? AND " . clinic_active_patient_where($con, 'add_patient'));
mysqli_stmt_bind_param($patient_stmt, "i", $patient_id);
mysqli_stmt_execute($patient_stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($patient_stmt));

if (!$patient) {
    die("المريض غير موجود أو مؤرشف");
}

$events = [];

function add_patient_events(mysqli $con, array &$events, int $patient_id, string $table, string $dateColumn, string $title, string $class, array $detailColumns = [], ?string $url = null): void
{
    if (!clinic_table_exists($con, $table) || !clinic_column_exists($con, $table, $dateColumn) || !clinic_column_exists($con, $table, 'patient_id')) {
        return;
    }

    $idColumn = null;
    foreach (['id', $table . '_id', 'visit_id'] as $candidate) {
        if (clinic_column_exists($con, $table, $candidate)) {
            $idColumn = $candidate;
            break;
        }
    }

    $select = ["`$dateColumn` AS event_date"];
    if ($idColumn !== null) {
        $select[] = "`$idColumn` AS event_id";
    }

    foreach ($detailColumns as $column) {
        if (clinic_column_exists($con, $table, $column)) {
            $select[] = "`$column`";
        }
    }

    $sql = "SELECT " . implode(", ", $select) . " FROM `$table` WHERE patient_id = ? ORDER BY `$dateColumn` DESC LIMIT 80";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $parts = [];
        foreach ($detailColumns as $column) {
            if (isset($row[$column]) && trim((string) $row[$column]) !== '') {
                $parts[] = $row[$column];
            }
        }

        $events[] = [
            'date' => $row['event_date'],
            'title' => $title,
            'details' => implode(" | ", $parts),
            'class' => $class,
            'url' => ($url && isset($row['event_id'])) ? $url . (int) $row['event_id'] : null,
        ];
    }
}

add_patient_events($con, $events, $patient_id, 'visits', 'visit_date', 'زيارة', 'visit', ['visit_type', 'notes']);
add_patient_events($con, $events, $patient_id, 'prescriptions', 'prescription_date', 'وصفة طبية', 'prescription', ['diagnosis'], 'view_prescription.php?id=');
add_patient_events($con, $events, $patient_id, 'surgery_appointment', 'date', 'موعد عملية', 'appointment', ['surgery_type', 'eye', 'status']);
add_patient_events($con, $events, $patient_id, 'surgery', 'date', 'عملية', 'surgery', ['surgery_type', 'eye', 'notes']);
add_patient_events($con, $events, $patient_id, 'laser', 'date', 'ليزر', 'laser', ['laser_type', 'eye', 'notes']);
add_patient_events($con, $events, $patient_id, 'injection', 'date', 'حقن', 'injection', ['injection_type', 'eye', 'notes']);
add_patient_events($con, $events, $patient_id, 'followups', 'followup_date', 'مراجعة', 'followup', ['followup_reason', 'note', 'status']);
if (clinic_table_exists($con, 'patient_images')) {
    $imageDateColumn = clinic_column_exists($con, 'patient_images', 'uploaded_at') ? 'uploaded_at' : null;
    if ($imageDateColumn === null && clinic_column_exists($con, 'patient_images', 'created_at')) {
        $imageDateColumn = 'created_at';
    }
    if ($imageDateColumn === null && clinic_column_exists($con, 'patient_images', 'id')) {
        $imageDateColumn = 'id';
    }
    if ($imageDateColumn !== null) {
        add_patient_events($con, $events, $patient_id, 'patient_images', $imageDateColumn, 'صورة طبية', 'image', ['notes', 'image_path']);
    }
}

usort($events, function ($a, $b) {
    return strtotime((string) $b['date']) <=> strtotime((string) $a['date']);
});
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسلسل الزمني - <?= h($patient['full_name']) ?></title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }
        .page { max-width: 1040px; margin: 0 auto; }
        .hero, .event {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
        }
        .hero {
            padding: 22px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        h1 { margin: 0; color: #1d4ed8; font-size: 26px; }
        .meta { color: #64748b; font-weight: 700; margin-top: 6px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .actions a, .event a {
            text-decoration: none;
            background: #1d4ed8;
            color: #fff;
            padding: 9px 12px;
            border-radius: 10px;
            font-weight: 800;
        }
        .event {
            padding: 16px 18px;
            margin-bottom: 12px;
            border-right: 6px solid #64748b;
        }
        .visit { border-right-color: #16a34a; }
        .prescription { border-right-color: #7c3aed; }
        .appointment { border-right-color: #0284c7; }
        .surgery { border-right-color: #dc2626; }
        .laser { border-right-color: #0891b2; }
        .injection { border-right-color: #f59e0b; }
        .followup { border-right-color: #0f766e; }
        .image { border-right-color: #475569; }
        .event-title { font-weight: 900; color: #0f172a; }
        .event-date { color: #64748b; font-size: 13px; margin: 4px 0 8px; }
        .empty {
            background: #fff;
            border-radius: 16px;
            padding: 26px;
            text-align: center;
            color: #64748b;
        }
        @media (max-width: 640px) {
            body { padding: 14px; }
            .hero { align-items: stretch; }
            .actions a { flex: 1 1 auto; text-align: center; }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="hero">
            <div>
                <h1><?= h($patient['full_name']) ?></h1>
                <div class="meta">
                    العمر: <?= h($patient['age']) ?> | الهاتف: <?= h($patient['phone_no']) ?>
                </div>
                <?php if (!empty($patient['notes'])): ?>
                    <div class="meta"><?= h($patient['notes']) ?></div>
                <?php endif; ?>
            </div>
            <div class="actions">
                <a href="patient-data.php?id=<?= $patient_id ?>">بيانات المريض</a>
                <a href="patient-file.php?id=<?= $patient_id ?>">الملف الكامل</a>
                <a href="image-comparison.php?id=<?= $patient_id ?>">مقارنة الصور</a>
            </div>
        </section>

        <?php if (!$events): ?>
            <div class="empty">لا توجد أحداث مسجلة لهذا المريض حتى الآن.</div>
        <?php endif; ?>

        <?php foreach ($events as $event): ?>
            <article class="event <?= h($event['class']) ?>">
                <div class="event-title"><?= h($event['title']) ?></div>
                <div class="event-date"><?= h($event['date']) ?></div>
                <div><?= nl2br(h($event['details'])) ?></div>
                <?php if ($event['url']): ?>
                    <p><a href="<?= h($event['url']) ?>">فتح التفاصيل</a></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </main>
</body>

</html>
