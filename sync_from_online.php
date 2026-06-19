<?php
include 'config.php';
include 'auth.php';
$requiredPermissions = ['sync'];
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
clinic_ensure_patient_images_sync_support($con);
clinic_ensure_deleted_records($con);
clinic_ensure_daily_revenue($con);
clinic_ensure_procedure_types($con);
clinic_ensure_procedure_entries($con);
set_time_limit(0);
$forceFullPull = isset($_GET['full']) && $_GET['full'] === '1';

ob_start();
include 'online-config.php';
ob_end_clean();

if (!isset($online) || !($online instanceof mysqli)) {
    die('Online connection is not available.');
}

clinic_ensure_patient_images_sync_support($online);
clinic_ensure_deleted_records($online);
clinic_ensure_daily_revenue($online);
clinic_ensure_procedure_types($online);
clinic_ensure_procedure_entries($online);

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
    int $batchSize = 5000,
    bool $forceFullPull = false
): array {
    $status = [
        'table' => $table,
        'pulled' => 0,
        'applied' => 0,
        'skipped_fk' => 0,  // ✅ عد السجلات المتخطاة بسبب FK
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
    $cursorKey = 'sync_pull_last_pk_' . $table;
    $lastSync = clinic_get_app_setting($localDb, $settingKey, '1970-01-01 00:00:00');
    if (!$lastSync) {
        $lastSync = '1970-01-01 00:00:00';
    }
    $lastPk = (int) clinic_get_app_setting($localDb, $cursorKey, '0');

    if ($forceFullPull) {
        $lastSync = '1970-01-01 00:00:00';
        $lastPk = 0;
    }

    $selectedColumnsSql = implode(', ', array_map(static function ($col) {
        return "`$col`";
    }, $commonColumns));

    $selectSql = "SELECT $selectedColumnsSql
        FROM `$table`
        WHERE (`updated_at` > ?) OR (`updated_at` = ? AND `$primaryKey` > ?)
        ORDER BY `updated_at` ASC, `$primaryKey` ASC
        LIMIT $batchSize";
    $selectStmt = mysqli_prepare($onlineDb, $selectSql);

    if (!$selectStmt) {
        $status['skipped'] = true;
        $status['message'] = 'Failed to prepare online select';
        return $status;
    }

    mysqli_stmt_bind_param($selectStmt, 'ssi', $lastSync, $lastSync, $lastPk);
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
    $maxPkAtMaxUpdatedAt = $lastPk;
    $syncedIds = [];
    $skippedFkCount = 0;  // ✅ عداد السجلات المتخطاة

    try {
        foreach ($rows as $row) {
            if (isset($row['updated_at'], $row[$primaryKey])) {
                $rowUpdatedAt = (string) $row['updated_at'];
                $rowPk = (int) $row[$primaryKey];

                if ($rowUpdatedAt > $maxUpdatedAt) {
                    $maxUpdatedAt = $rowUpdatedAt;
                    $maxPkAtMaxUpdatedAt = $rowPk;
                } elseif ($rowUpdatedAt === $maxUpdatedAt && $rowPk > $maxPkAtMaxUpdatedAt) {
                    $maxPkAtMaxUpdatedAt = $rowPk;
                }
            }

            // ✅ التحقق من Foreign Keys قبل الإدراج
            // للجداول التي تحتوي على patient_id
            if (in_array($table, ['patient_visits', 'prescriptions', 'surgery_appointment', 'laser_appointment', 'injection_appointment', 'surgery', 'laser', 'injection', 'va', 'followups', 'visits'], true)) {
                if (isset($row['patient_id'])) {
                    $patientId = (int)$row['patient_id'];
                    $checkPatient = mysqli_query($localDb, "SELECT 1 FROM `add_patient` WHERE `id` = $patientId LIMIT 1");
                    if (!$checkPatient || mysqli_num_rows($checkPatient) === 0) {
                        // تخطي السجل - المريض غير موجود في المحلي
                        $skippedFkCount++;
                        error_log("⚠️ [{$table}] تخطي سجل - patient_id: $patientId غير موجود في المحلي");
                        continue;
                    }
                }
            }

            // ✅ التحقق من prescription_id إذا كان موجوداً
            if ($table === 'prescription_items' && isset($row['prescription_id'])) {
                $prescriptionId = (int)$row['prescription_id'];
                $checkPrescription = mysqli_query($localDb, "SELECT 1 FROM `prescriptions` WHERE `id` = $prescriptionId LIMIT 1");
                if (!$checkPrescription || mysqli_num_rows($checkPrescription) === 0) {
                    $skippedFkCount++;
                    error_log("⚠️ [{$table}] تخطي سجل - prescription_id: $prescriptionId غير موجود في المحلي");
                    continue;
                }
            }

            // ✅ التحقق من visit_id إذا كان موجوداً
            if ($table === 'prescriptions' && isset($row['visit_id']) && (int)$row['visit_id'] > 0) {
                $visitId = (int)$row['visit_id'];
                $checkVisit = mysqli_query($localDb, "SELECT 1 FROM `visits` WHERE `visit_id` = $visitId LIMIT 1");
                if (!$checkVisit || mysqli_num_rows($checkVisit) === 0) {
                    $skippedFkCount++;
                    error_log("⚠️ [{$table}] تخطي سجل - visit_id: $visitId غير موجود في المحلي");
                    continue;
                }
            }

            $values = [];
            foreach ($commonColumns as $col) {
                $values[] = isset($row[$col]) ? (string) $row[$col] : null;
            }

            $types = sync_make_bind_types(count($values));
            mysqli_stmt_bind_param($upsertStmt, $types, ...$values);
            $ok = mysqli_stmt_execute($upsertStmt);

            if (!$ok) {
                $error = mysqli_error($localDb);

                // ✅ التحقق من أخطاء Foreign Key والتعامل معها (آخر حد للفشل)
                if (strpos($error, 'FOREIGN KEY') !== false || strpos($error, 'foreign key') !== false) {
                    // تخطي السجل بدلاً من إيقاف العملية
                    $skippedFkCount++;
                    error_log("⚠️ [{$table}] تخطي سجل بسبب Foreign Key: " . $error);
                    continue;
                }

                throw new RuntimeException($error);
            }

            $status['applied']++;

            if (isset($row[$primaryKey]) && $row[$primaryKey] !== '') {
                $syncedIds[] = (int) $row[$primaryKey];
            }
        }

        if (clinic_column_exists($localDb, $table, 'sync_status') && !empty($syncedIds)) {
            $idsSql = implode(',', array_map('intval', $syncedIds));
            mysqli_query($localDb, "UPDATE `$table` SET `sync_status` = 1 WHERE `$primaryKey` IN ($idsSql)");
        }

        clinic_set_app_setting($localDb, $settingKey, $maxUpdatedAt);
        clinic_set_app_setting($localDb, $cursorKey, (string) $maxPkAtMaxUpdatedAt);
        mysqli_commit($localDb);

        // ✅ رسالة محسّنة تتضمن عدد السجلات المتخطاة
        if ($skippedFkCount > 0) {
            $pct = round(($skippedFkCount / $status['pulled']) * 100);
            $status['message'] = "نجح جزئياً: {$status['applied']} مطبق، {$skippedFkCount} متخطي ({$pct}%) - أب غير موجود";
            $status['skipped_fk'] = $skippedFkCount;
        } else {
            $status['message'] = 'نجح تماماً ✓';
        }
    } catch (Throwable $e) {
        mysqli_rollback($localDb);
        $status['message'] = 'فشل: ' . $e->getMessage();
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
    ['name' => 'daily_revenue', 'pk' => 'id'],
    ['name' => 'procedure_types', 'pk' => 'id'],
    ['name' => 'procedure_entries', 'pk' => 'id'],
];

$results = [];
$totalPulled = 0;
$totalApplied = 0;

foreach ($tables as $entry) {
    $result = sync_pull_table($con, $online, $entry['name'], $entry['pk'], 5000, $forceFullPull);
    $results[] = $result;
    $totalPulled += $result['pulled'];
    $totalApplied += $result['applied'];
}

$deletionsApplied = clinic_apply_online_deletions($con, $online);

clinic_audit(
    $con,
    'sync_pull_from_online',
    'sync',
    null,
    null,
    [
        'total_pulled' => $totalPulled,
        'total_applied' => $totalApplied,
        'deletions_applied' => $deletionsApplied,
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
    <script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <main class="wrap">
        <h1>نتيجة المزامنة العكسية (من السحابة إلى المحلي)</h1>
        <div class="totals">
            تم سحب <strong><?php echo (int) $totalPulled; ?></strong> سجل، وتطبيق <strong><?php echo (int) $totalApplied; ?></strong> سجل على النسخة المحلية.
            <?php if ($deletionsApplied > 0): ?>
                — حُذف <strong><?php echo (int) $deletionsApplied; ?></strong> سجل محلياً بناءً على حذوفات السحابة.
            <?php else: ?>
                — لا توجد حذوفات جديدة من السحابة.
            <?php endif; ?>
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
                        <td>
                            <?php
                            $applied = (int) $row['applied'];
                            $skippedFk = isset($row['skipped_fk']) ? (int) $row['skipped_fk'] : 0;
                            $total = $applied + $skippedFk;
                            echo "$applied";
                            if ($skippedFk > 0) {
                                echo " <span style='color:#a15c00;'>(⚠️ $skippedFk متخطي)</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($row['skipped']): ?>
                                <span class="skip">تخطي</span>
                            <?php elseif ($row['pulled'] > 0 && $applied === $row['pulled']): ?>
                                <span class="ok">✓ نجاح</span>
                            <?php elseif ($applied > 0): ?>
                                <span class="warn">⚠️ جزئي</span>
                            <?php else: ?>
                                <span class="skip">-</span>
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