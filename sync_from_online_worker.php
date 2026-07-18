<?php

include_once __DIR__ . '/clinic_helpers.php';

function sync_worker_table_exists(mysqli $db, string $table): bool
{
    $tableEscaped = mysqli_real_escape_string($db, $table);
    $result = mysqli_query($db, "SHOW TABLES LIKE '$tableEscaped'");
    return $result && mysqli_num_rows($result) > 0;
}

function sync_worker_table_columns(mysqli $db, string $table): array
{
    $columns = [];
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function sync_worker_common_columns(mysqli $localDb, mysqli $onlineDb, string $table): array
{
    $localColumns = sync_worker_table_columns($localDb, $table);
    $onlineColumns = sync_worker_table_columns($onlineDb, $table);

    if (empty($localColumns) || empty($onlineColumns)) {
        return [];
    }

    $common = array_values(array_intersect($localColumns, $onlineColumns));
    $ignore = ['sync_status'];

    return array_values(array_filter($common, static function ($col) use ($ignore) {
        return !in_array($col, $ignore, true);
    }));
}

function sync_worker_bind_types(int $count): string
{
    if ($count <= 0) {
        return '';
    }

    return str_repeat('s', $count);
}

function sync_worker_pull_table(
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
        'skipped_fk' => 0,
        'skipped' => false,
        'message' => '',
    ];

    if (!sync_worker_table_exists($localDb, $table) || !sync_worker_table_exists($onlineDb, $table)) {
        $status['skipped'] = true;
        $status['message'] = 'Table not found in both databases';
        return $status;
    }

    $commonColumns = sync_worker_common_columns($localDb, $onlineDb, $table);
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
    $skippedFkCount = 0;

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

            if (in_array($table, ['patient_visits', 'prescriptions', 'surgery_appointment', 'laser_appointment', 'injection_appointment', 'surgery', 'laser', 'injection', 'va', 'followups', 'visits'], true)) {
                if (isset($row['patient_id'])) {
                    $patientId = (int) $row['patient_id'];
                    $checkPatient = mysqli_query($localDb, "SELECT 1 FROM `add_patient` WHERE `id` = $patientId LIMIT 1");
                    if (!$checkPatient || mysqli_num_rows($checkPatient) === 0) {
                        $skippedFkCount++;
                        continue;
                    }
                }
            }

            if ($table === 'prescription_items' && isset($row['prescription_id'])) {
                $prescriptionId = (int) $row['prescription_id'];
                $checkPrescription = mysqli_query($localDb, "SELECT 1 FROM `prescriptions` WHERE `id` = $prescriptionId LIMIT 1");
                if (!$checkPrescription || mysqli_num_rows($checkPrescription) === 0) {
                    $skippedFkCount++;
                    continue;
                }
            }

            if ($table === 'prescriptions' && isset($row['visit_id']) && (int) $row['visit_id'] > 0) {
                $visitId = (int) $row['visit_id'];
                $checkVisit = mysqli_query($localDb, "SELECT 1 FROM `visits` WHERE `visit_id` = $visitId LIMIT 1");
                if (!$checkVisit || mysqli_num_rows($checkVisit) === 0) {
                    $skippedFkCount++;
                    continue;
                }
            }

            $values = [];
            foreach ($commonColumns as $col) {
                $values[] = isset($row[$col]) ? (string) $row[$col] : null;
            }

            $types = sync_worker_bind_types(count($values));
            mysqli_stmt_bind_param($upsertStmt, $types, ...$values);
            $ok = mysqli_stmt_execute($upsertStmt);

            if (!$ok) {
                $error = mysqli_error($localDb);
                if (strpos($error, 'FOREIGN KEY') !== false || strpos($error, 'foreign key') !== false) {
                    $skippedFkCount++;
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

        if ($skippedFkCount > 0) {
            $status['message'] = 'Completed with FK skips';
            $status['skipped_fk'] = $skippedFkCount;
        } else {
            $status['message'] = 'Completed';
        }
    } catch (Throwable $e) {
        mysqli_rollback($localDb);
        $status['message'] = 'Failed: ' . $e->getMessage();
    }

    return $status;
}

function clinic_sync_pull_from_online_worker(mysqli $localDb, bool $forceFullPull = false, int $batchSize = 5000): array
{
    clinic_ensure_runtime_controls($localDb);
    clinic_ensure_patient_images_sync_support($localDb);
    clinic_ensure_deleted_records($localDb);
    clinic_ensure_daily_revenue($localDb);
    clinic_ensure_procedure_types($localDb);
    clinic_ensure_procedure_entries($localDb);

    $onlineHost = $GLOBALS['onlineDb']['host'] ?? 'localhost';
    $onlineUser = $GLOBALS['onlineDb']['user'] ?? '';
    $onlinePass = $GLOBALS['onlineDb']['pass'] ?? '';
    $onlineName = $GLOBALS['onlineDb']['name'] ?? '';

    try {
        $online = @mysqli_connect($onlineHost, $onlineUser, $onlinePass, $onlineName);
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'error' => 'Online connection failed: ' . $e->getMessage(),
            'results' => [],
            'total_pulled' => 0,
            'total_applied' => 0,
        ];
    }
    if (!($online instanceof mysqli)) {
        return [
            'ok' => false,
            'error' => 'Online connection is not available.',
            'results' => [],
            'total_pulled' => 0,
            'total_applied' => 0,
        ];
    }

    mysqli_set_charset($online, 'utf8mb4');

    clinic_ensure_patient_images_sync_support($online);
    clinic_ensure_deleted_records($online);
    clinic_ensure_daily_revenue($online);
    clinic_ensure_procedure_types($online);
    clinic_ensure_procedure_entries($online);

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
        $result = sync_worker_pull_table($localDb, $online, $entry['name'], $entry['pk'], $batchSize, $forceFullPull);
        $results[] = $result;
        $totalPulled += (int) ($result['pulled'] ?? 0);
        $totalApplied += (int) ($result['applied'] ?? 0);
    }

    $deletionsApplied = clinic_apply_online_deletions($localDb, $online);

    if ($online instanceof mysqli) {
        mysqli_close($online);
    }

    return [
        'ok' => true,
        'error' => null,
        'results' => $results,
        'total_pulled' => $totalPulled,
        'total_applied' => $totalApplied,
        'deletions_applied' => $deletionsApplied,
    ];
}
