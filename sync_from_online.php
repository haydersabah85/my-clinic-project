<?php
include 'config.php';
include 'auth.php';
include 'admin-only.php';
include_once 'clinic_helpers.php';

if (!$IS_LOCAL) {
    http_response_code(403);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>غير متاح على النسخة السحابية</h3>";
    echo "<p>المزامنة العكسية تعمل من السيرفر المحلي في العيادة فقط.</p>";
    echo "<a href='settings.php'>العودة إلى الإعدادات</a>";
    echo "</body></html>";
    exit;
}

clinic_ensure_runtime_controls($con);
set_time_limit(0);

ob_start();
include 'online-config.php';
ob_end_clean();

if (!isset($online) || !($online instanceof mysqli)) {
    die('Online connection is not available.');
}

function sync_table_exists(mysqli $db, string $table): bool
{
    $tableEscaped = mysqli_real_escape_string($db, $table);
    $result = mysqli_query($db, "SHOW TABLES LIKE '$tableEscaped'");
    return $result && mysqli_num_rows($result) > 0;
}

function sync_table_columns(mysqli $db, string $table): array
{
    $columns = [];
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function sync_get_common_columns(mysqli $localDb, mysqli $onlineDb, string $table): array
{
    $localColumns = sync_table_columns($localDb, $table);
    $onlineColumns = sync_table_columns($onlineDb, $table);

    if (empty($localColumns) || empty($onlineColumns)) {
        return [];
    }

    $common = array_values(array_intersect($localColumns, $onlineColumns));

    // Avoid pulling local-only operational flags from online if present.
    $ignore = ['sync_status'];
    return array_values(array_filter($common, static function ($col) use ($ignore) {
        return !in_array($col, $ignore, true);
    }));
}

function sync_make_bind_types(int $count): string
{
    if ($count <= 0) {
        return '';
    }

    return str_repeat('s', $count);
}

function sync_pull_table(
    mysqli $localDb,
    mysqli $onlineDb,
    string $table,
    string $primaryKey,
    int $batchSize = 300
): array {
    $status = [
        'table' => $table,
        'pulled' => 0,
        'applied' => 0,
        'skipped' => false,
        'message' => '',
    ];

    if (!sync_table_exists($localDb, $table) || !sync_table_exists($onlineDb, $table)) {
        $status['skipped'] = true;
        $status['message'] = 'Table not found in both databases';
        return $status;
    }

    $commonColumns = sync_get_common_columns($localDb, $onlineDb, $table);
    if (empty($commonColumns)) {
        $status['skipped'] = true;
        $status['message'] = 'No common columns';
        return $status;
    }

    if (!in_array($primaryKey, $commonColumns, true) || !in_array('updated_at', $commonColumns, true)) {
        $status['skipped'] = true;
        $status['message'] = 'Missing primary key or updated_at';
        return $status;
    }

    $settingKey = 'sync_pull_last_' . $table;
    $lastSync = clinic_get_app_setting($localDb, $settingKey, '1970-01-01 00:00:00');
    if (!$lastSync) {
        $lastSync = '1970-01-01 00:00:00';
    }

    $selectedColumnsSql = implode(', ', array_map(static function ($col) {
        return "`$col`";
    }, $commonColumns));

    $selectSql = "SELECT $selectedColumnsSql FROM `$table` WHERE `updated_at` > ? ORDER BY `updated_at` ASC, `$primaryKey` ASC LIMIT $batchSize";
    $selectStmt = mysqli_prepare($onlineDb, $selectSql);

    if (!$selectStmt) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to prepare online select';
        return $status;
    }

    mysqli_stmt_bind_param($selectStmt, 's', $lastSync);
    mysqli_stmt_execute($selectStmt);
    $result = mysqli_stmt_get_result($selectStmt);

    if (!$result) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to fetch online rows';
        return $status;
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    $status['pulled'] = count($rows);

    if (empty($rows)) {
        $status['message'] = 'No new rows';
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
    $upsertStmt = mysqli_prepare($localDb, $upsertSql);

    if (!$upsertStmt) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to prepare local upsert';
        return $status;
    }

    mysqli_begin_transaction($localDb);

    $maxUpdatedAt = $lastSync;
    $syncedIds = [];

    try {
        foreach ($rows as $row) {
            $values = [];
            foreach ($commonColumns as $col) {
                $values[] = isset($row[$col]) ? (string) $row[$col] : null;
            }

            $types = sync_make_bind_types(count($values));
            mysqli_stmt_bind_param($upsertStmt, $types, ...$values);
            $ok = mysqli_stmt_execute($upsertStmt);

            if (!$ok) {
                throw new RuntimeException(mysqli_error($localDb));
            }

            $status['applied']++;

            if (isset($row['updated_at']) && $row['updated_at'] > $maxUpdatedAt) {
                $maxUpdatedAt = $row['updated_at'];
            }

            if (isset($row[$primaryKey]) && $row[$primaryKey] !== '') {
                $syncedIds[] = (int) $row[$primaryKey];
            }
        }

        if (clinic_column_exists($localDb, $table, 'sync_status') && !empty($syncedIds)) {
            $idsSql = implode(',', array_map('intval', $syncedIds));
            mysqli_query($localDb, "UPDATE `$table` SET `sync_status` = 1 WHERE `$primaryKey` IN ($idsSql)");
        }

        clinic_set_app_setting($localDb, $settingKey, $maxUpdatedAt);
        mysqli_commit($localDb);
        $status['message'] = 'Pulled and applied successfully';
    } catch (Throwable $e) {
        mysqli_rollback($localDb);
        $status['message'] = 'Failed: ' . $e->getMessage();
    }

    return $status;
}

$tables = [
    ['name' => 'add_patient', 'pk' => 'id'],
    ['name' => 'patient_visits', 'pk' => 'id'],
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
$totalPulled = 0;
$totalApplied = 0;

foreach ($tables as $entry) {
    $result = sync_pull_table($con, $online, $entry['name'], $entry['pk']);
    $results[] = $result;
    $totalPulled += $result['pulled'];
    $totalApplied += $result['applied'];
}

clinic_audit(
    $con,
    'sync_pull_from_online',
    'sync',
    null,
    null,
    [
        'total_pulled' => $totalPulled,
        'total_applied' => $totalApplied,
        'tables' => $results,
    ]
);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المزامنة العكسية</title>
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
</head>

<body>
    <main class="wrap">
        <h1>نتيجة المزامنة العكسية (من السحابة إلى المحلي)</h1>
        <div class="totals">
            تم سحب <strong><?php echo (int) $totalPulled; ?></strong> سجل، وتطبيق <strong><?php echo (int) $totalApplied; ?></strong> سجل على النسخة المحلية.
        </div>

        <table>
            <thead>
                <tr>
                    <th>الجدول</th>
                    <th>مسحوب</th>
                    <th>مطبق</th>
                    <th>الحالة</th>
                    <th>تفاصيل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo h($row['table']); ?></td>
                        <td><?php echo (int) $row['pulled']; ?></td>
                        <td><?php echo (int) $row['applied']; ?></td>
                        <td>
                            <?php if ($row['skipped']): ?>
                                <span class="skip">تخطي</span>
                            <?php elseif ($row['pulled'] > 0 && $row['applied'] === $row['pulled']): ?>
                                <span class="ok">نجاح</span>
                            <?php else: ?>
                                <span class="warn">تحقق</span>
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