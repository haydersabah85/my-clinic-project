<?php
include 'config.php';
include 'auth.php';
include 'admin-only.php';
include_once 'clinic_helpers.php';

if (!$IS_LOCAL) {
    http_response_code(403);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>غير متاح على النسخة السحابية</h3>";
    echo "<p>المزامنة الآمنة إلى السحابة تعمل من السيرفر المحلي في العيادة فقط.</p>";
    echo "<a href='settings.php'>العودة إلى الإعدادات</a>";
    echo "</body></html>";
    exit;
}

set_time_limit(0);
clinic_ensure_runtime_controls($con);
clinic_ensure_sync_conflicts($con);
clinic_ensure_patient_images_sync_support($con);

ob_start();
include 'online-config.php';
ob_end_clean();

if (!isset($online) || !($online instanceof mysqli)) {
    die('Online connection is not available.');
}

clinic_ensure_patient_images_sync_support($online);

function safe_sync_table_exists(mysqli $db, string $table): bool
{
    $tableEscaped = mysqli_real_escape_string($db, $table);
    $result = mysqli_query($db, "SHOW TABLES LIKE '$tableEscaped'");
    return $result && mysqli_num_rows($result) > 0;
}

function safe_sync_table_columns(mysqli $db, string $table): array
{
    $columns = [];
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function safe_sync_common_columns(mysqli $localDb, mysqli $onlineDb, string $table): array
{
    $localColumns = safe_sync_table_columns($localDb, $table);
    $onlineColumns = safe_sync_table_columns($onlineDb, $table);

    if (empty($localColumns) || empty($onlineColumns)) {
        return [];
    }

    $common = array_values(array_intersect($localColumns, $onlineColumns));
    $ignore = ['sync_status'];

    return array_values(array_filter($common, static function ($col) use ($ignore) {
        return !in_array($col, $ignore, true);
    }));
}

function safe_sync_bind_types(int $count): string
{
    if ($count <= 0) {
        return '';
    }

    return str_repeat('s', $count);
}

function safe_sync_push_table(mysqli $localDb, mysqli $onlineDb, string $table, string $primaryKey, int $batchSize = 300): array
{
    $status = [
        'table' => $table,
        'scanned' => 0,
        'pushed' => 0,
        'conflicts' => 0,
        'skipped' => false,
        'message' => '',
    ];

    if (!safe_sync_table_exists($localDb, $table) || !safe_sync_table_exists($onlineDb, $table)) {
        $status['skipped'] = true;
        $status['message'] = 'Table not found in both databases';
        return $status;
    }

    if (!clinic_column_exists($localDb, $table, 'sync_status') || !clinic_column_exists($localDb, $table, 'updated_at')) {
        $status['skipped'] = true;
        $status['message'] = 'Missing local sync_status or updated_at';
        return $status;
    }

    $commonColumns = safe_sync_common_columns($localDb, $onlineDb, $table);

    if (empty($commonColumns)) {
        $status['skipped'] = true;
        $status['message'] = 'No common columns';
        return $status;
    }

    if (!in_array($primaryKey, $commonColumns, true) || !in_array('updated_at', $commonColumns, true)) {
        $status['skipped'] = true;
        $status['message'] = 'Missing primary key or updated_at in common columns';
        return $status;
    }

    $localSelect = mysqli_query(
        $localDb,
        "SELECT * FROM `$table` WHERE `sync_status` = 0 ORDER BY `updated_at` ASC LIMIT $batchSize"
    );

    if (!$localSelect) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to select local pending rows';
        return $status;
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($localSelect)) {
        $rows[] = $row;
    }
    $status['scanned'] = count($rows);

    if (empty($rows)) {
        $status['message'] = 'No pending rows';
        return $status;
    }

    $insertColumnsSql = implode(', ', array_map(static function ($col) {
        return "`$col`";
    }, $commonColumns));
    $placeholders = implode(', ', array_fill(0, count($commonColumns), '?'));

    $updateColumns = array_values(array_filter($commonColumns, static function ($col) use ($primaryKey) {
        return $col !== $primaryKey;
    }));
    $updateSql = implode(', ', array_map(static function ($col) {
        return "`$col` = VALUES(`$col`)";
    }, $updateColumns));

    $upsertSql = "INSERT INTO `$table` ($insertColumnsSql) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
    $upsertStmt = mysqli_prepare($onlineDb, $upsertSql);

    if (!$upsertStmt) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to prepare online upsert';
        return $status;
    }

    $onlineCheckSql = "SELECT * FROM `$table` WHERE `$primaryKey` = ? LIMIT 1";
    $onlineCheckStmt = mysqli_prepare($onlineDb, $onlineCheckSql);

    if (!$onlineCheckStmt) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to prepare online version check';
        return $status;
    }

    mysqli_begin_transaction($localDb);

    try {
        foreach ($rows as $row) {
            $recordKey = (string) ($row[$primaryKey] ?? '');
            if ($recordKey === '') {
                continue;
            }

            $localUpdatedAt = (string) ($row['updated_at'] ?? '1970-01-01 00:00:00');

            mysqli_stmt_bind_param($onlineCheckStmt, 's', $recordKey);
            mysqli_stmt_execute($onlineCheckStmt);
            $onlineResult = mysqli_stmt_get_result($onlineCheckStmt);
            $onlineRow = $onlineResult ? mysqli_fetch_assoc($onlineResult) : null;
            $onlineUpdatedAt = $onlineRow['updated_at'] ?? null;

            if ($onlineUpdatedAt !== null && $onlineUpdatedAt > $localUpdatedAt) {
                $status['conflicts']++;
                clinic_log_sync_conflict(
                    $localDb,
                    $table,
                    $recordKey,
                    'local_to_online',
                    $localUpdatedAt,
                    $onlineUpdatedAt,
                    $row,
                    $onlineRow,
                    'Online row is newer than local pending change'
                );
                continue;
            }

            $values = [];
            foreach ($commonColumns as $col) {
                $values[] = isset($row[$col]) ? (string) $row[$col] : null;
            }

            $types = safe_sync_bind_types(count($values));
            mysqli_stmt_bind_param($upsertStmt, $types, ...$values);
            $ok = mysqli_stmt_execute($upsertStmt);

            if (!$ok) {
                throw new RuntimeException(mysqli_error($onlineDb));
            }

            $status['pushed']++;

            $escapedKey = mysqli_real_escape_string($localDb, $recordKey);
            mysqli_query($localDb, "UPDATE `$table` SET `sync_status` = 1 WHERE `$primaryKey` = '$escapedKey'");
        }

        mysqli_commit($localDb);
        $status['message'] = 'Safe push completed';
    } catch (Throwable $e) {
        mysqli_rollback($localDb);
        $status['message'] = 'Failed: ' . $e->getMessage();
    }

    return $status;
}

$tables = [
    ['name' => 'add_patient', 'pk' => 'id'],
    ['name' => 'patient_visits', 'pk' => 'id'],
    ['name' => 'patient_images', 'pk' => 'id'],
    ['name' => 'surgery_appointment', 'pk' => 'id'],
    ['name' => 'laser_appointment', 'pk' => 'id'],
    ['name' => 'injection_appointment', 'pk' => 'id'],
    ['name' => 'surgery', 'pk' => 'id'],
    ['name' => 'laser', 'pk' => 'id'],
    ['name' => 'injection', 'pk' => 'id'],
    ['name' => 'medicines', 'pk' => 'id'],
    ['name' => 'prescriptions', 'pk' => 'id'],
    ['name' => 'prescription_items', 'pk' => 'id'],
    ['name' => 'va', 'pk' => 'va_id'],
    ['name' => 'followups', 'pk' => 'id'],
    ['name' => 'visits', 'pk' => 'visit_id'],
];

$results = [];
$totalScanned = 0;
$totalPushed = 0;
$totalConflicts = 0;

foreach ($tables as $entry) {
    $result = safe_sync_push_table($con, $online, $entry['name'], $entry['pk']);
    $results[] = $result;
    $totalScanned += $result['scanned'];
    $totalPushed += $result['pushed'];
    $totalConflicts += $result['conflicts'];
}

clinic_audit(
    $con,
    'sync_push_to_online_safe',
    'sync',
    null,
    null,
    [
        'total_scanned' => $totalScanned,
        'total_pushed' => $totalPushed,
        'total_conflicts' => $totalConflicts,
        'tables' => $results,
    ]
);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مزامنة آمنة إلى السحابة</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 22px;
        }

        .wrap {
            max-width: 980px;
            margin: auto;
            background: #fff;
            border-radius: 14px;
            border: 1px solid #dbe6f1;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            padding: 18px;
        }

        h1 {
            margin-top: 0;
            color: #0f4c81;
        }

        .totals {
            background: #eef6ff;
            border: 1px solid #cfe3ff;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-top: 1px solid #edf1f6;
            padding: 9px;
            text-align: right;
        }

        th {
            background: #f8fbff;
            color: #1e40af;
        }

        .ok {
            color: #0a7a20;
            font-weight: 700;
        }

        .warn {
            color: #a15c00;
            font-weight: 700;
        }

        .skip {
            color: #64748b;
        }

        a.btn {
            display: inline-block;
            margin-top: 14px;
            text-decoration: none;
            background: #1d4ed8;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
        }
    </style>
<script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <main class="wrap">
        <h1>نتيجة المزامنة الآمنة (من المحلي إلى السحابة)</h1>
        <div class="totals">
            تم فحص <strong><?php echo (int) $totalScanned; ?></strong> سجل محلي غير متزامن،
            ودفع <strong><?php echo (int) $totalPushed; ?></strong> سجل إلى السحابة،
            وتسجيل <strong><?php echo (int) $totalConflicts; ?></strong> تعارض للمراجعة.
        </div>

        <table>
            <thead>
                <tr>
                    <th>الجدول</th>
                    <th>مفحوص</th>
                    <th>مرفوع</th>
                    <th>تعارضات</th>
                    <th>الحالة</th>
                    <th>تفاصيل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo h($row['table']); ?></td>
                        <td><?php echo (int) $row['scanned']; ?></td>
                        <td><?php echo (int) $row['pushed']; ?></td>
                        <td><?php echo (int) $row['conflicts']; ?></td>
                        <td>
                            <?php if ($row['skipped']): ?>
                                <span class="skip">تخطي</span>
                            <?php elseif ($row['conflicts'] > 0): ?>
                                <span class="warn">تعارضات</span>
                            <?php else: ?>
                                <span class="ok">آمن</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo h($row['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a class="btn" href="settings.php">العودة إلى الإعدادات</a>
    </main>
</body>

</html>
