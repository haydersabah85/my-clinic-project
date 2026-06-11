<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);

$today = $_GET['date'] ?? date('Y-m-d');

function fetch_all_assoc(mysqli_result|false $result): array
{
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$todaySafe = mysqli_real_escape_string($con, $today);
$active = clinic_active_patient_where($con, 'p');

$visits = fetch_all_assoc(mysqli_query($con, "
    SELECT v.*, p.full_name, p.phone_no, p.is_critical
    FROM visits v
    JOIN add_patient p ON p.id = v.patient_id
    WHERE v.visit_date = '$todaySafe' AND $active
    ORDER BY v.is_done ASC, v.daily_serial ASC, v.visit_id ASC
"));

$followups = fetch_all_assoc(mysqli_query($con, "
    SELECT f.*, p.full_name, p.phone_no, p.is_critical
    FROM followups f
    JOIN add_patient p ON p.id = f.patient_id
    WHERE f.followup_date = '$todaySafe' AND f.status = 'pending' AND $active
    ORDER BY f.id ASC
"));

$expected = fetch_all_assoc(mysqli_query($con, "
    SELECT e.*, p.full_name, p.phone_no, p.is_critical
    FROM expected_appointments e
    JOIN add_patient p ON p.id = e.patient_id
    WHERE e.expected_date = '$todaySafe' AND e.status = 'expected' AND $active
    ORDER BY e.id ASC
"));

$procedures = fetch_all_assoc(mysqli_query($con, "
    SELECT s.*, p.full_name, p.phone_no, p.is_critical
    FROM surgery_appointment s
    JOIN add_patient p ON p.id = s.patient_id
    WHERE s.date = '$todaySafe' AND s.status = 'pending' AND $active
    ORDER BY s.id ASC
"));

$nextPatientAlert = null;
$nextPatientRaw = clinic_get_app_setting($con, 'doctor_next_patient_alert', '');
if ($nextPatientRaw) {
    $decoded = json_decode($nextPatientRaw, true);
    if (is_array($decoded) && !empty($decoded['patient_id']) && !empty($decoded['full_name'])) {
        $nextPatientAlert = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة عمل اليوم</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 22px;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        .page {
            max-width: 1220px;
            margin: auto;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0;
            color: #1d4ed8;
        }

        form {
            display: flex;
            gap: 8px;
        }

        input,
        button,
        .btn {
            border: 1px solid #d9e2ec;
            border-radius: 10px;
            padding: 10px 12px;
            font-family: inherit;
            font-weight: 700;
        }

        button,
        .btn {
            background: #1d4ed8;
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .card h2 {
            margin: 0;
            padding: 14px 16px;
            background: #eef6ff;
            color: #1d4ed8;
            font-size: 20px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            padding: 12px 16px;
            border-top: 1px solid #eef2f7;
            align-items: center;
        }

        .name {
            font-weight: 900;
            color: #0f172a;
        }

        .meta {
            color: #64748b;
            font-size: 13px;
            margin-top: 3px;
        }

        .critical {
            color: #dc2626;
            font-weight: 900;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .actions a {
            color: #fff;
            background: #0f766e;
            text-decoration: none;
            padding: 7px 9px;
            border-radius: 9px;
            font-weight: 800;
        }

        .actions .notify-btn {
            background: #b45309;
        }

        .next-alert {
            margin-bottom: 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            color: #7c2d12;
            font-weight: 700;
        }

        .next-alert a {
            text-decoration: none;
            background: #92400e;
            color: #fff;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 13px;
        }

        .empty {
            padding: 18px;
            color: #64748b;
            text-align: center;
        }

        @media (max-width: 820px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <div class="top">
            <div>
                <h1>قائمة عمل اليوم</h1>
                <div class="meta"><?= h($today) ?></div>
            </div>
            <form method="get">
                <input type="date" name="date" value="<?= h($today) ?>">
                <button type="submit">عرض</button>
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
            </form>
        </div>

        <?php if ($nextPatientAlert): ?>
            <section class="next-alert">
                <div>
                    المريض القادم المحدد حاليا: <strong><?= h($nextPatientAlert['full_name']) ?></strong>
                    | القسم: <?= h($nextPatientAlert['queue'] ?? 'العيادة') ?>
                    | وقت الإرسال: <?= h($nextPatientAlert['notified_at'] ?? '-') ?>
                </div>
                <a href="notify-next-patient.php?action=clear&back=work-queue.php?date=<?= urlencode($today) ?>">مسح التنبيه</a>
            </section>
        <?php endif; ?>

        <?php
        $sections = [
            'زيارات اليوم' => [$visits, fn($r) => 'الرقم: ' . ($r['daily_serial'] ?? '-') . ' | الحالة: ' . (!empty($r['is_done']) ? 'منجز' : 'بانتظار')],
            'مراجعات اليوم' => [$followups, fn($r) => $r['followup_reason'] ?? ''],
            'المواعيد المتوقعة' => [$expected, fn($r) => $r['note'] ?? ($r['notes'] ?? '')],
            'عمليات اليوم' => [$procedures, fn($r) => trim(($r['surgery_type'] ?? '') . ' ' . ($r['eye'] ?? ''))],
        ];
        ?>

        <div class="grid">
            <?php foreach ($sections as $title => [$rows, $metaFn]): ?>
                <section class="card">
                    <h2><?= h($title) ?> (<?= count($rows) ?>)</h2>
                    <?php if (!$rows): ?>
                        <div class="empty">لا توجد عناصر</div>
                    <?php endif; ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="row">
                            <div>
                                <div class="name">
                                    <?= h($row['full_name']) ?>
                                    <?php if (!empty($row['is_critical'])): ?><span class="critical"> - حالة مهمة</span><?php endif; ?>
                                </div>
                                <div class="meta"><?= h($row['phone_no']) ?> | <?= h($metaFn($row)) ?></div>
                            </div>
                            <div class="actions">
                                <a href="patient-data.php?id=<?= (int) $row['patient_id'] ?>">فتح</a>
                                <a href="patient_timeline.php?id=<?= (int) $row['patient_id'] ?>">السجل</a>
                                <a href="next-visit-appointment.php?id=<?= (int) $row['patient_id'] ?>">مراجعة</a>
                                <a class="notify-btn" href="notify-next-patient.php?action=set&patient_id=<?= (int) $row['patient_id'] ?>&queue=<?= urlencode($title) ?>&meta=<?= urlencode((string) $metaFn($row)) ?>&back=<?= urlencode('work-queue.php?date=' . $today) ?>">تنبيه الطبيب</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>