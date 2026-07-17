<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);

$today = trim((string) ($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $today) || DateTimeImmutable::createFromFormat('!Y-m-d', $today)?->format('Y-m-d') !== $today) {
    $today = date('Y-m-d');
}

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

$active = clinic_active_patient_where($con, 'p');

function queue_rows_for_date(mysqli $con, string $sql, string $date): array
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $rows = fetch_all_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $rows;
}

$visits = queue_rows_for_date($con, "
    SELECT v.*, p.full_name, p.phone_no, p.is_critical
    FROM visits v
    JOIN add_patient p ON p.id = v.patient_id
    WHERE v.visit_date = ? AND $active
    ORDER BY v.is_done ASC, v.daily_serial ASC, v.visit_id ASC
", $today);

$followups = queue_rows_for_date($con, "
    SELECT f.*, p.full_name, p.phone_no, p.is_critical
    FROM followups f
    JOIN add_patient p ON p.id = f.patient_id
    WHERE f.followup_date = ? AND f.status = 'pending' AND $active
    ORDER BY f.id ASC
", $today);

$expected = queue_rows_for_date($con, "
    SELECT e.*, p.full_name, p.phone_no, p.is_critical
    FROM expected_appointments e
    JOIN add_patient p ON p.id = e.patient_id
    WHERE e.expected_date = ? AND e.status = 'expected' AND $active
    ORDER BY e.id ASC
", $today);

$surgeries = queue_rows_for_date($con, "
    SELECT s.*, p.full_name, p.phone_no, p.is_critical
    FROM surgery_appointment s
    JOIN add_patient p ON p.id = s.patient_id
    WHERE s.date = ? AND s.status = 'pending' AND $active
    ORDER BY COALESCE(s.attendance_status, 0) DESC, s.id ASC
", $today);

$lasers = queue_rows_for_date($con, "
    SELECT a.*, p.full_name, p.phone_no, p.is_critical
    FROM laser_appointment a
    JOIN add_patient p ON p.id = a.patient_id
    WHERE a.date = ? AND a.status = 'pending' AND $active
    ORDER BY COALESCE(a.attendance_status, 0) DESC, a.id ASC
", $today);

$injections = queue_rows_for_date($con, "
    SELECT a.*, p.full_name, p.phone_no, p.is_critical
    FROM injection_appointment a
    JOIN add_patient p ON p.id = a.patient_id
    WHERE a.date = ? AND a.status = 'pending' AND $active
    ORDER BY COALESCE(a.attendance_status, 0) DESC, a.id ASC
", $today);

$completedVisits = count(array_filter($visits, static fn(array $row): bool => !empty($row['is_done'])));
$pendingVisits = count($visits) - $completedVisits;
$procedureTotal = count($surgeries) + count($lasers) + count($injections);
$queueTotal = count($visits) + count($followups) + count($expected) + $procedureTotal;
$flash = clinic_take_flash();

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

        .summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .metric {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .metric strong {
            display: block;
            color: #1d4ed8;
            font-size: 27px;
            line-height: 1.1;
        }

        .metric span {
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
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

        .actions form,
        .next-alert form {
            display: inline-flex;
            margin: 0;
        }

        .actions a {
            color: #fff;
            background: #0f766e;
            text-decoration: none;
            padding: 7px 9px;
            border-radius: 9px;
            font-weight: 800;
        }

        .actions button,
        .next-alert button {
            border: 0;
            font: inherit;
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

        .flash {
            margin-bottom: 12px;
            padding: 11px 14px;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            background: #ecfdf5;
            color: #065f46;
            font-weight: 800;
        }

        .next-alert a,
        .next-alert button {
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
            .summary,
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

        <section class="summary" aria-label="ملخص قائمة العمل">
            <div class="metric"><strong><?= $queueTotal ?></strong><span>إجمالي عناصر اليوم</span></div>
            <div class="metric"><strong><?= $pendingVisits ?></strong><span>زيارات بانتظار الإنجاز</span></div>
            <div class="metric"><strong><?= $completedVisits ?></strong><span>زيارات منجزة</span></div>
            <div class="metric"><strong><?= $procedureTotal ?></strong><span>عمليات وليزر وحقن</span></div>
        </section>

        <?php if ($flash): ?>
            <div class="flash" role="status"><?= h($flash['message'] ?? '') ?></div>
        <?php endif; ?>

        <?php if ($nextPatientAlert): ?>
            <section class="next-alert">
                <div>
                    المريض القادم المحدد حاليا: <strong><?= h($nextPatientAlert['full_name']) ?></strong>
                    | القسم: <?= h($nextPatientAlert['queue'] ?? 'العيادة') ?>
                    | وقت الإرسال: <?= h($nextPatientAlert['notified_at'] ?? '-') ?>
                </div>
                <form method="post" action="notify-next-patient.php">
                    <?= clinic_csrf_input() ?>
                    <input type="hidden" name="action" value="clear">
                    <input type="hidden" name="back" value="work-queue.php?date=<?= h($today) ?>">
                    <button type="submit">مسح التنبيه</button>
                </form>
            </section>
        <?php endif; ?>

        <?php
        $sections = [
            'زيارات اليوم' => [$visits, fn($r) => 'الرقم: ' . ($r['daily_serial'] ?? '-') . ' | الحالة: ' . (!empty($r['is_done']) ? 'منجز' : 'بانتظار')],
            'مراجعات اليوم' => [$followups, fn($r) => $r['followup_reason'] ?? ''],
            'المواعيد المتوقعة' => [$expected, fn($r) => $r['note'] ?? ($r['notes'] ?? '')],
            'عمليات اليوم' => [$surgeries, fn($r) => trim(($r['surgery_type'] ?? '') . ' ' . ($r['eye'] ?? ''))],
            'ليزر اليوم' => [$lasers, fn($r) => trim(($r['laser_type'] ?? '') . ' ' . ($r['eye'] ?? ''))],
            'حقن اليوم' => [$injections, fn($r) => trim(($r['injection_type'] ?? '') . ' ' . ($r['eye'] ?? ''))],
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
                                <form method="post" action="notify-next-patient.php">
                                    <?= clinic_csrf_input() ?>
                                    <input type="hidden" name="action" value="set">
                                    <input type="hidden" name="patient_id" value="<?= (int) $row['patient_id'] ?>">
                                    <input type="hidden" name="queue" value="<?= h($title) ?>">
                                    <input type="hidden" name="meta" value="<?= h((string) $metaFn($row)) ?>">
                                    <input type="hidden" name="back" value="work-queue.php?date=<?= h($today) ?>">
                                    <button class="notify-btn" type="submit">تنبيه الطبيب</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </main>
</body>

</html>
