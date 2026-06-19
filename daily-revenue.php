<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';
$requiredPermissions = ['admin'];
include 'admin-only.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);
clinic_ensure_daily_revenue($con);
clinic_ensure_procedure_types($con);
clinic_ensure_procedure_entries($con);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

function revenue_visit_counts(mysqli $con, string $date): array
{
    $counts = ['first' => 0, 'repeat' => 0];
    $stmt = mysqli_prepare($con, "
        SELECT
            SUM(CASE WHEN visit_type = 'first' THEN 1 ELSE 0 END) AS first_count,
            SUM(CASE WHEN visit_type = 'repeat' THEN 1 ELSE 0 END) AS repeat_count
        FROM visits
        WHERE visit_date = ?
    ");

    if (!$stmt) {
        return $counts;
    }

    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    if ($row) {
        $counts['first'] = (int) ($row['first_count'] ?? 0);
        $counts['repeat'] = (int) ($row['repeat_count'] ?? 0);
    }

    return $counts;
}

function revenue_procedure_aggregates(mysqli $con, string $date): array
{
    $data = [
        'retina_count' => 0,
        'retina_income' => 0.0,
        'laser_count' => 0,
        'laser_income' => 0.0,
        'other_count' => 0,
        'other_income' => 0.0,
        'all_count' => 0,
        'all_income' => 0.0,
    ];

    $stmt = mysqli_prepare($con, "
        SELECT
            category,
            SUM(qty) AS qty_total,
            SUM(total_cost) AS amount_total
        FROM procedure_entries
        WHERE procedure_date = ?
        GROUP BY category
    ");

    if (!$stmt) {
        return $data;
    }

    mysqli_stmt_bind_param($stmt, 's', $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $category = (string) ($row['category'] ?? 'other');
        $qty = (int) ($row['qty_total'] ?? 0);
        $amount = (float) ($row['amount_total'] ?? 0);

        if (!in_array($category, ['retina', 'laser', 'other'], true)) {
            $category = 'other';
        }

        if ($category === 'retina') {
            $data['retina_count'] = $qty;
            $data['retina_income'] = $amount;
        } elseif ($category === 'laser') {
            $data['laser_count'] = $qty;
            $data['laser_income'] = $amount;
        } else {
            $data['other_count'] = $qty;
            $data['other_income'] = $amount;
        }

        $data['all_count'] += $qty;
        $data['all_income'] += $amount;
    }

    return $data;
}

$existing = [
    'visit_first_price' => (float) clinic_get_app_setting($con, 'revenue_default_first_price', '25000'),
    'visit_repeat_price' => (float) clinic_get_app_setting($con, 'revenue_default_repeat_price', '20000'),
    'service_staff_due' => 0.0,
    'other_income' => 0.0,
    'notes' => '',
    'updated_at' => '',
    'updated_by' => '',
];

$existingStmt = mysqli_prepare($con, "
    SELECT visit_first_price, visit_repeat_price, service_staff_due, other_income, notes, updated_at, updated_by
    FROM daily_revenue
    WHERE revenue_date = ?
    LIMIT 1
");

if ($existingStmt) {
    mysqli_stmt_bind_param($existingStmt, 's', $selectedDate);
    mysqli_stmt_execute($existingStmt);
    $existingResult = mysqli_stmt_get_result($existingStmt);
    $existingRow = $existingResult ? mysqli_fetch_assoc($existingResult) : null;
    if ($existingRow) {
        $existing = array_merge($existing, $existingRow);
    }
}

$flashMessage = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinic_require_csrf();

    $postedDate = $_POST['revenue_date'] ?? '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $postedDate)) {
        $selectedDate = $postedDate;
    }

    $firstPrice = max(0, (float) ($_POST['visit_first_price'] ?? 0));
    $repeatPrice = max(0, (float) ($_POST['visit_repeat_price'] ?? 0));
    $serviceStaffDue = max(0, (float) ($_POST['service_staff_due'] ?? 0));
    $otherIncome = max(0, (float) ($_POST['other_income'] ?? 0));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    clinic_set_app_setting($con, 'revenue_default_first_price', (string) $firstPrice);
    clinic_set_app_setting($con, 'revenue_default_repeat_price', (string) $repeatPrice);

    $visitCounts = revenue_visit_counts($con, $selectedDate);
    $procedureAgg = revenue_procedure_aggregates($con, $selectedDate);

    $firstCount = (int) $visitCounts['first'];
    $repeatCount = (int) $visitCounts['repeat'];
    $paidVisitsCount = $firstCount + $repeatCount;

    $visitIncome = ($firstCount * $firstPrice) + ($repeatCount * $repeatPrice);
    $retinaCount = (int) $procedureAgg['retina_count'];
    $retinaIncome = (float) $procedureAgg['retina_income'];
    $laserCount = (int) $procedureAgg['laser_count'];
    $laserIncome = (float) $procedureAgg['laser_income'];
    $proceduresIncome = (float) $procedureAgg['all_income'];

    $user = clinic_current_user();
    $syncStatus = $IS_LOCAL ? 0 : 1;

    $sql = "
        INSERT INTO daily_revenue
        (revenue_date, visit_first_count, visit_repeat_count, paid_visits_count, visit_first_price, visit_repeat_price, visit_income,
         retina_count, retina_income, laser_count, laser_income, procedures_income, other_income, service_staff_due,
         notes, created_by, updated_by, sync_status, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            visit_first_count = VALUES(visit_first_count),
            visit_repeat_count = VALUES(visit_repeat_count),
            paid_visits_count = VALUES(paid_visits_count),
            visit_first_price = VALUES(visit_first_price),
            visit_repeat_price = VALUES(visit_repeat_price),
            visit_income = VALUES(visit_income),
            retina_count = VALUES(retina_count),
            retina_income = VALUES(retina_income),
            laser_count = VALUES(laser_count),
            laser_income = VALUES(laser_income),
            procedures_income = VALUES(procedures_income),
            other_income = VALUES(other_income),
            service_staff_due = VALUES(service_staff_due),
            notes = VALUES(notes),
            updated_by = VALUES(updated_by),
            updated_at = NOW()";

    if ($IS_LOCAL) {
        $sql .= ", sync_status = 0";
    }

    $saveStmt = mysqli_prepare($con, $sql);

    if ($saveStmt) {
        mysqli_stmt_bind_param(
            $saveStmt,
            'siiidddididdddsssi',
            $selectedDate,
            $firstCount,
            $repeatCount,
            $paidVisitsCount,
            $firstPrice,
            $repeatPrice,
            $visitIncome,
            $retinaCount,
            $retinaIncome,
            $laserCount,
            $laserIncome,
            $proceduresIncome,
            $otherIncome,
            $serviceStaffDue,
            $notes,
            $user,
            $user,
            $syncStatus
        );

        if (mysqli_stmt_execute($saveStmt)) {
            clinic_audit(
                $con,
                'upsert',
                'daily_revenue',
                null,
                null,
                [
                    'revenue_date' => $selectedDate,
                    'visit_first_count' => $firstCount,
                    'visit_repeat_count' => $repeatCount,
                    'visit_income' => $visitIncome,
                    'retina_count' => $retinaCount,
                    'retina_income' => $retinaIncome,
                    'laser_count' => $laserCount,
                    'laser_income' => $laserIncome,
                    'procedures_income' => $proceduresIncome,
                    'other_income' => $otherIncome,
                    'service_staff_due' => $serviceStaffDue,
                ]
            );
            $flashMessage = 'تم احتساب وحفظ الإيراد تلقائيا بنجاح.';
            $flashType = 'success';
        } else {
            $flashMessage = 'فشل حفظ الإيراد.';
            $flashType = 'error';
        }
    } else {
        $flashMessage = 'تعذر تجهيز عملية الحفظ.';
        $flashType = 'error';
    }
}

$current = [
    'visit_first_count' => 0,
    'visit_repeat_count' => 0,
    'paid_visits_count' => 0,
    'visit_first_price' => (float) clinic_get_app_setting($con, 'revenue_default_first_price', '25000'),
    'visit_repeat_price' => (float) clinic_get_app_setting($con, 'revenue_default_repeat_price', '20000'),
    'visit_income' => 0.0,
    'retina_count' => 0,
    'retina_income' => 0.0,
    'laser_count' => 0,
    'laser_income' => 0.0,
    'procedures_income' => 0.0,
    'other_income' => 0.0,
    'service_staff_due' => 0.0,
    'notes' => '',
    'updated_at' => '',
    'updated_by' => '',
];

$loadStmt = mysqli_prepare($con, "
    SELECT visit_first_count, visit_repeat_count, paid_visits_count, visit_first_price, visit_repeat_price, visit_income,
           retina_count, retina_income, laser_count, laser_income, procedures_income, other_income, service_staff_due,
           notes, updated_at, updated_by
    FROM daily_revenue
    WHERE revenue_date = ?
    LIMIT 1
");

if ($loadStmt) {
    mysqli_stmt_bind_param($loadStmt, 's', $selectedDate);
    mysqli_stmt_execute($loadStmt);
    $loadResult = mysqli_stmt_get_result($loadStmt);
    $loadRow = $loadResult ? mysqli_fetch_assoc($loadResult) : null;
    if ($loadRow) {
        $current = array_merge($current, $loadRow);
    }
}

$previewVisitCounts = revenue_visit_counts($con, $selectedDate);
$previewProcAgg = revenue_procedure_aggregates($con, $selectedDate);

$previewVisitIncome = ((int) $previewVisitCounts['first'] * (float) $current['visit_first_price']) + ((int) $previewVisitCounts['repeat'] * (float) $current['visit_repeat_price']);
$previewTotalIncome = $previewVisitIncome + (float) $previewProcAgg['all_income'] + (float) $current['other_income'];
$previewNet = $previewTotalIncome - (float) $current['service_staff_due'];

$recentRows = [];
$recentQuery = mysqli_query($con, "
    SELECT
        dr.revenue_date,
        dr.visit_income,
        COALESCE(proc.procedures_income_live, 0) AS procedures_income,
        dr.other_income,
        dr.service_staff_due
    FROM daily_revenue dr
    LEFT JOIN (
        SELECT procedure_date, SUM(total_cost) AS procedures_income_live
        FROM procedure_entries
        GROUP BY procedure_date
    ) proc ON proc.procedure_date = dr.revenue_date
    ORDER BY dr.revenue_date DESC
    LIMIT 7
");
while ($recentQuery && ($row = mysqli_fetch_assoc($recentQuery))) {
    $recentRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإيراد اليومي</title>
    <style>
        :root {
            --bg: #f6f8fc;
            --card: #fff;
            --ink: #0f172a;
            --muted: #475569;
            --border: #dbe3ef;
            --accent: #0f766e;
            --danger: #b91c1c;
            --shadow: 0 10px 24px rgba(2, 6, 23, .08);
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: linear-gradient(180deg, #edf3ff 0%, var(--bg) 100%);
            color: var(--ink);
        }

        .wrap {
            max-width: 1120px;
            margin: 20px auto;
            padding: 0 12px 20px;
        }

        .head {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .head h1 {
            margin: 0;
            font-size: 24px;
            color: #0b4d46;
        }

        .head .actions a {
            text-decoration: none;
            color: #fff;
            background: #2563eb;
            border-radius: 8px;
            padding: 9px 12px;
            font-weight: 700;
            margin-inline-start: 6px;
            display: inline-block;
        }

        .notice {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .notice.success {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .notice.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 12px;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 14px;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 700;
        }

        input,
        textarea {
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            font-family: inherit;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        .hint {
            color: var(--muted);
            font-size: 13px;
        }

        .btn {
            border: none;
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            min-height: 42px;
            padding: 9px 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .summary {
            display: grid;
            gap: 8px;
        }

        .summary-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
        }

        .summary-item.net {
            background: #f0fdf4;
            color: #14532d;
            border-color: #86efac;
        }

        .summary-item.net.negative {
            background: #fef2f2;
            color: var(--danger);
            border-color: #fca5a5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        th,
        td {
            border-top: 1px solid #ebf0f7;
            padding: 8px;
            text-align: right;
        }

        th {
            color: #1e3a8a;
            background: #f8fbff;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="wrap">
        <section class="head">
            <h1>حساب الإيراد اليومي (تلقائي)</h1>
            <div class="actions">
                <a href="procedure-entries.php?date=<?php echo urlencode($selectedDate); ?>">إدخال الإجراءات</a>
                <a href="visits.php">زيارات اليوم</a>
            </div>
        </section>

        <?php if ($flashMessage !== ''): ?>
            <div class="notice <?php echo $flashType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section class="grid">
            <article class="panel">
                <h2>إعدادات اليوم</h2>
                <form method="post" class="form-grid">
                    <?php echo clinic_csrf_input(); ?>
                    <div class="field full">
                        <label for="revenue_date">التاريخ</label>
                        <input id="revenue_date" type="date" name="revenue_date" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <span class="hint">تغيير التاريخ يحدث العد تلقائيا حسب زيارات وإجراءات ذلك اليوم.</span>
                    </div>

                    <div class="field">
                        <label>عدد الزيارات أول مرة (تلقائي)</label>
                        <input type="text" value="<?php echo (int) $previewVisitCounts['first']; ?>" readonly>
                    </div>
                    <div class="field">
                        <label for="visit_first_price">سعر الزيارة أول مرة</label>
                        <input id="visit_first_price" type="number" min="0" step="0.01" name="visit_first_price" value="<?php echo (float) $current['visit_first_price']; ?>" required>
                    </div>

                    <div class="field">
                        <label>عدد الزيارات المتكررة (تلقائي)</label>
                        <input type="text" value="<?php echo (int) $previewVisitCounts['repeat']; ?>" readonly>
                    </div>
                    <div class="field">
                        <label for="visit_repeat_price">سعر الزيارة المتكررة</label>
                        <input id="visit_repeat_price" type="number" min="0" step="0.01" name="visit_repeat_price" value="<?php echo (float) $current['visit_repeat_price']; ?>" required>
                    </div>

                    <div class="field">
                        <label>عدد فحص الشبكية (تلقائي)</label>
                        <input type="text" value="<?php echo (int) $previewProcAgg['retina_count']; ?>" readonly>
                    </div>
                    <div class="field">
                        <label>إيراد الشبكية (تلقائي من صفحة الإجراءات)</label>
                        <input type="text" value="<?php echo number_format((float) $previewProcAgg['retina_income'], 0); ?>" readonly>
                    </div>

                    <div class="field">
                        <label>عدد الليزر (تلقائي)</label>
                        <input type="text" value="<?php echo (int) $previewProcAgg['laser_count']; ?>" readonly>
                    </div>
                    <div class="field">
                        <label>إيراد الليزر (تلقائي من صفحة الإجراءات)</label>
                        <input type="text" value="<?php echo number_format((float) $previewProcAgg['laser_income'], 0); ?>" readonly>
                    </div>

                    <div class="field">
                        <label for="other_income">إيرادات أخرى (يدوي)</label>
                        <input id="other_income" type="number" min="0" step="0.01" name="other_income" value="<?php echo (float) $current['other_income']; ?>" required>
                    </div>
                    <div class="field">
                        <label for="service_staff_due">مستحقات موظف الخدمة (يدوي)</label>
                        <input id="service_staff_due" type="number" min="0" step="0.01" name="service_staff_due" value="<?php echo (float) $current['service_staff_due']; ?>" required>
                    </div>

                    <div class="field full">
                        <label for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" placeholder="اختياري"><?php echo htmlspecialchars((string) $current['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="field full">
                        <button type="submit" class="btn">احتساب وحفظ الإيراد</button>
                    </div>
                </form>
            </article>

            <article class="panel">
                <h2>ملخص اليوم</h2>
                <div class="summary">
                    <div class="summary-item"><span>إيراد الزيارات (تلقائي)</span><span><?php echo number_format($previewVisitIncome, 0); ?></span></div>
                    <div class="summary-item"><span>إجمالي إيراد الإجراءات (تلقائي)</span><span><?php echo number_format((float) $previewProcAgg['all_income'], 0); ?></span></div>
                    <div class="summary-item"><span>إيرادات أخرى</span><span><?php echo number_format((float) $current['other_income'], 0); ?></span></div>
                    <div class="summary-item"><span>إجمالي الإيراد</span><span><?php echo number_format($previewTotalIncome, 0); ?></span></div>
                    <div class="summary-item"><span>مستحقات موظف الخدمة</span><span><?php echo number_format((float) $current['service_staff_due'], 0); ?></span></div>
                    <div class="summary-item net <?php echo $previewNet < 0 ? 'negative' : ''; ?>"><span>صافي اليوم</span><span><?php echo number_format($previewNet, 0); ?></span></div>
                </div>
                <p class="hint" style="margin-top:10px;">آخر تحديث: <?php echo htmlspecialchars((string) ($current['updated_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> <?php if (!empty($current['updated_by'])): ?>بواسطة <?php echo htmlspecialchars((string) $current['updated_by'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></p>
            </article>
        </section>

        <section class="panel" style="margin-top:12px;">
            <h2>آخر 7 أيام</h2>
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>إيراد الزيارات</th>
                        <th>إيراد الإجراءات</th>
                        <th>إيرادات أخرى</th>
                        <th>المستحقات</th>
                        <th>الصافي</th>
                        <th>فتح</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRows)): ?>
                        <tr>
                            <td colspan="7">لا توجد بيانات بعد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentRows as $row): ?>
                            <?php
                            $rowIncome = (float) $row['visit_income'] + (float) $row['procedures_income'] + (float) $row['other_income'];
                            $rowNet = $rowIncome - (float) $row['service_staff_due'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) $row['revenue_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format((float) $row['visit_income'], 0); ?></td>
                                <td><?php echo number_format((float) $row['procedures_income'], 0); ?></td>
                                <td><?php echo number_format((float) $row['other_income'], 0); ?></td>
                                <td><?php echo number_format((float) $row['service_staff_due'], 0); ?></td>
                                <td><?php echo number_format($rowNet, 0); ?></td>
                                <td><a href="daily-revenue.php?date=<?php echo urlencode((string) $row['revenue_date']); ?>">فتح</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>

</html>