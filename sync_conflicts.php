<?php
include 'config.php';
include 'auth.php';
include 'admin-only.php';
include_once 'clinic_helpers.php';

if (!$IS_LOCAL) {
    http_response_code(403);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>غير متاح على النسخة السحابية</h3>";
    echo "<p>إدارة التعارضات متاحة من السيرفر المحلي فقط.</p>";
    echo "<a href='settings.php'>العودة إلى الإعدادات</a>";
    echo "</body></html>";
    exit;
}

clinic_ensure_sync_conflicts($con);

ob_start();
include 'online-config.php';
ob_end_clean();

if (!isset($online) || !($online instanceof mysqli)) {
    die('Online connection is not available.');
}

$tablePkMap = [
    'add_patient' => 'id',
    'patient_visits' => 'id',
    'surgery_appointment' => 'id',
    'laser_appointment' => 'id',
    'injection_appointment' => 'id',
    'surgery' => 'id',
    'laser' => 'id',
    'injection' => 'id',
    'medicines' => 'id',
    'prescriptions' => 'id',
    'prescription_items' => 'id',
    'va' => 'va_id',
    'followups' => 'id',
    'visits' => 'visit_id',
];

function conflicts_table_exists(mysqli $db, string $table): bool
{
    $tableEscaped = mysqli_real_escape_string($db, $table);
    $result = mysqli_query($db, "SHOW TABLES LIKE '$tableEscaped'");
    return $result && mysqli_num_rows($result) > 0;
}

function conflicts_table_columns(mysqli $db, string $table): array
{
    $columns = [];
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table`");
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $columns[] = $row['Field'];
    }
    return $columns;
}

function conflicts_common_columns(mysqli $localDb, mysqli $onlineDb, string $table): array
{
    $localColumns = conflicts_table_columns($localDb, $table);
    $onlineColumns = conflicts_table_columns($onlineDb, $table);

    if (empty($localColumns) || empty($onlineColumns)) {
        return [];
    }

    $common = array_values(array_intersect($localColumns, $onlineColumns));
    return array_values(array_filter($common, static function ($col) {
        return $col !== 'sync_status';
    }));
}

function conflicts_bind_types(int $count): string
{
    return $count > 0 ? str_repeat('s', $count) : '';
}

function conflicts_fetch_row(mysqli $db, string $table, string $pk, string $recordKey): ?array
{
    if (!conflicts_table_exists($db, $table)) {
        return null;
    }

    $sql = "SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $recordKey);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return $row ?: null;
}

function conflicts_upsert_row(mysqli $targetDb, string $table, string $pk, array $columns, array $row): bool
{
    if (empty($columns) || !isset($row[$pk])) {
        return false;
    }

    $insertColumnsSql = implode(', ', array_map(static function ($col) {
        return "`$col`";
    }, $columns));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $updateColumns = array_values(array_filter($columns, static function ($col) use ($pk) {
        return $col !== $pk;
    }));

    $updateSql = implode(', ', array_map(static function ($col) {
        return "`$col` = VALUES(`$col`)";
    }, $updateColumns));

    if ($updateSql === '') {
        return false;
    }

    $sql = "INSERT INTO `$table` ($insertColumnsSql) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
    $stmt = mysqli_prepare($targetDb, $sql);
    if (!$stmt) {
        return false;
    }

    $values = [];
    foreach ($columns as $col) {
        $values[] = isset($row[$col]) ? (string) $row[$col] : null;
    }

    $types = conflicts_bind_types(count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    return (bool) mysqli_stmt_execute($stmt);
}

function conflicts_mark_resolved(mysqli $localDb, int $conflictId, string $status, string $note): bool
{
    $user = clinic_current_user();
    $stmt = mysqli_prepare(
        $localDb,
        "UPDATE sync_conflicts SET resolution_status = ?, note = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ?"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'sssi', $status, $note, $user, $conflictId);
    return (bool) mysqli_stmt_execute($stmt);
}

$message = '';
$messageType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $conflictId = (int) ($_POST['conflict_id'] ?? 0);

    if ($conflictId <= 0) {
        $message = 'معرف التعارض غير صالح.';
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($con, "SELECT * FROM sync_conflicts WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $conflictId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $conflict = $result ? mysqli_fetch_assoc($result) : null;

        if (!$conflict) {
            $message = 'التعارض غير موجود.';
            $messageType = 'error';
        } elseif (($conflict['resolution_status'] ?? '') !== 'open') {
            $message = 'تم التعامل مع هذا التعارض مسبقا.';
            $messageType = 'warn';
        } else {
            $table = $conflict['table_name'] ?? '';
            $recordKey = (string) ($conflict['record_key'] ?? '');
            $pk = $tablePkMap[$table] ?? '';

            if ($pk === '' || !conflicts_table_exists($con, $table) || !conflicts_table_exists($online, $table)) {
                $message = 'تعذر التحقق من الجدول المرتبط بهذا التعارض.';
                $messageType = 'error';
            } else {
                $commonColumns = conflicts_common_columns($con, $online, $table);

                if (!in_array($pk, $commonColumns, true)) {
                    $message = 'تعذر تطبيق الحل بسبب غياب المفتاح الأساسي في الأعمدة المشتركة.';
                    $messageType = 'error';
                } else {
                    if ($action === 'keep_local') {
                        $localRow = conflicts_fetch_row($con, $table, $pk, $recordKey);
                        if (!$localRow) {
                            $message = 'لم يتم العثور على السجل المحلي.';
                            $messageType = 'error';
                        } else {
                            $ok = conflicts_upsert_row($online, $table, $pk, $commonColumns, $localRow);
                            if ($ok) {
                                if (clinic_column_exists($con, $table, 'sync_status')) {
                                    $escapedKey = mysqli_real_escape_string($con, $recordKey);
                                    mysqli_query($con, "UPDATE `$table` SET sync_status = 1 WHERE `$pk` = '$escapedKey'");
                                }
                                conflicts_mark_resolved($con, $conflictId, 'resolved_keep_local', 'تم اعتماد المحلي وإرساله إلى السحابة.');
                                clinic_audit($con, 'resolve_conflict_keep_local', 'sync_conflicts', $conflictId, null, [
                                    'table' => $table,
                                    'record_key' => $recordKey,
                                ]);
                                $message = 'تم اعتماد نسخة المحلي بنجاح.';
                            } else {
                                $message = 'فشل اعتماد المحلي، تحقق من اتصال السحابة.';
                                $messageType = 'error';
                            }
                        }
                    } elseif ($action === 'keep_online') {
                        $onlineRow = conflicts_fetch_row($online, $table, $pk, $recordKey);
                        if (!$onlineRow) {
                            $message = 'لم يتم العثور على السجل في السحابة.';
                            $messageType = 'error';
                        } else {
                            $ok = conflicts_upsert_row($con, $table, $pk, $commonColumns, $onlineRow);
                            if ($ok) {
                                if (clinic_column_exists($con, $table, 'sync_status')) {
                                    $escapedKey = mysqli_real_escape_string($con, $recordKey);
                                    mysqli_query($con, "UPDATE `$table` SET sync_status = 1 WHERE `$pk` = '$escapedKey'");
                                }
                                conflicts_mark_resolved($con, $conflictId, 'resolved_keep_online', 'تم اعتماد السحابة وتحديث المحلي.');
                                clinic_audit($con, 'resolve_conflict_keep_online', 'sync_conflicts', $conflictId, null, [
                                    'table' => $table,
                                    'record_key' => $recordKey,
                                ]);
                                $message = 'تم اعتماد نسخة السحابة بنجاح.';
                            } else {
                                $message = 'فشل اعتماد السحابة على المحلي.';
                                $messageType = 'error';
                            }
                        }
                    } elseif ($action === 'resolve_manual') {
                        $manualNote = trim((string) ($_POST['manual_note'] ?? ''));
                        if ($manualNote === '') {
                            $manualNote = 'تم إغلاق التعارض يدويًا بدون تغيير البيانات.';
                        }

                        if (conflicts_mark_resolved($con, $conflictId, 'resolved_manual', $manualNote)) {
                            clinic_audit($con, 'resolve_conflict_manual', 'sync_conflicts', $conflictId, null, [
                                'table' => $table,
                                'record_key' => $recordKey,
                                'note' => $manualNote,
                            ]);
                            $message = 'تم إغلاق التعارض يدويًا.';
                        } else {
                            $message = 'فشل تحديث حالة التعارض.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'إجراء غير معروف.';
                        $messageType = 'error';
                    }
                }
            }
        }
    }
}

$statusFilter = $_GET['status'] ?? 'open';
$allowedFilters = ['open', 'resolved', 'all'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = 'open';
}

$whereSql = '';
if ($statusFilter === 'open') {
    $whereSql = "WHERE resolution_status = 'open'";
} elseif ($statusFilter === 'resolved') {
    $whereSql = "WHERE resolution_status <> 'open'";
}

$conflicts = mysqli_query(
    $con,
    "SELECT * FROM sync_conflicts $whereSql ORDER BY created_at DESC, id DESC LIMIT 250"
);

$openCountRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status = 'open'"));
$resolvedCountRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status <> 'open'"));
$openCount = (int) ($openCountRow['total'] ?? 0);
$resolvedCount = (int) ($resolvedCountRow['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة تعارضات المزامنة</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f1f5f9;
            font-family: Tahoma, Arial, sans-serif;
            color: #0f172a;
        }

        .page {
            max-width: 1250px;
            margin: auto;
            background: #fff;
            border: 1px solid #dbe6f2;
            border-radius: 16px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .head {
            padding: 16px 18px;
            border-bottom: 1px solid #e7eef7;
            background: linear-gradient(90deg, #f8fbff, #eef6ff);
        }

        h1 {
            margin: 0 0 8px;
            color: #1e3a8a;
        }

        .stats {
            color: #334155;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px 18px;
            border-bottom: 1px solid #edf2f7;
        }

        .pill {
            text-decoration: none;
            color: #0f172a;
            background: #e2e8f0;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 700;
        }

        .pill.active {
            background: #1d4ed8;
            color: #fff;
        }

        .msg {
            margin: 12px 18px 0;
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 700;
        }

        .msg.ok {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .msg.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .msg.warn {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-top: 1px solid #edf2f7;
            padding: 10px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #1e3a8a;
        }

        .status-open {
            color: #b45309;
            font-weight: 700;
        }

        .status-resolved {
            color: #166534;
            font-weight: 700;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 7px 10px;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-local {
            background: #b45309;
            color: #fff;
        }

        .btn-online {
            background: #0f766e;
            color: #fff;
        }

        .btn-manual {
            background: #475569;
            color: #fff;
        }

        details {
            margin-top: 8px;
        }

        summary {
            cursor: pointer;
            color: #1e40af;
            font-weight: 700;
        }

        pre {
            margin: 6px 0 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            max-height: 180px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 12px;
        }

        .back {
            display: inline-block;
            margin: 16px 18px 18px;
            text-decoration: none;
            background: #1d4ed8;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .manual-note {
            width: 100%;
            margin-top: 8px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            min-height: 60px;
            padding: 6px;
            font-family: inherit;
        }
    </style>
<script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <main class="page">
        <section class="head">
            <h1>إدارة تعارضات المزامنة</h1>
            <div class="stats">المفتوح: <?php echo $openCount; ?> | المحلول: <?php echo $resolvedCount; ?></div>
        </section>

        <section class="actions">
            <a class="pill <?php echo $statusFilter === 'open' ? 'active' : ''; ?>" href="sync_conflicts.php?status=open">مفتوحة</a>
            <a class="pill <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>" href="sync_conflicts.php?status=resolved">محلولة</a>
            <a class="pill <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="sync_conflicts.php?status=all">الكل</a>
        </section>

        <?php if ($message !== ''): ?>
            <div class="msg <?php echo h($messageType); ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الجدول / المفتاح</th>
                    <th>الاتجاه</th>
                    <th>التوقيت</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $conflicts ? mysqli_fetch_assoc($conflicts) : null): ?>
                    <?php
                    $isOpen = ($row['resolution_status'] ?? '') === 'open';
                    $statusClass = $isOpen ? 'status-open' : 'status-resolved';
                    $localSnapshot = json_decode((string) ($row['local_snapshot'] ?? ''), true);
                    $onlineSnapshot = json_decode((string) ($row['online_snapshot'] ?? ''), true);
                    ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td>
                            <strong><?php echo h($row['table_name']); ?></strong><br>
                            Key: <?php echo h($row['record_key']); ?>
                        </td>
                        <td><?php echo h($row['direction']); ?></td>
                        <td>
                            محلي: <?php echo h($row['local_updated_at']); ?><br>
                            سحابة: <?php echo h($row['online_updated_at']); ?><br>
                            إنشاء: <?php echo h($row['created_at']); ?>
                            <?php if (!empty($row['resolved_at'])): ?>
                                <br>حُل: <?php echo h($row['resolved_at']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?php echo $statusClass; ?>"><?php echo h($row['resolution_status']); ?></span>
                            <?php if (!empty($row['resolved_by'])): ?>
                                <br>بواسطة: <?php echo h($row['resolved_by']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isOpen): ?>
                                <div class="btn-row">
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="conflict_id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="action" value="keep_local">
                                        <button class="btn-local" type="submit" onclick="return confirm('اعتماد المحلي ورفعه إلى السحابة؟')">اعتماد المحلي</button>
                                    </form>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="conflict_id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="action" value="keep_online">
                                        <button class="btn-online" type="submit" onclick="return confirm('اعتماد السحابة وتحديث المحلي؟')">اعتماد السحابة</button>
                                    </form>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="conflict_id" value="<?php echo (int) $row['id']; ?>">
                                    <input type="hidden" name="action" value="resolve_manual">
                                    <textarea class="manual-note" name="manual_note" placeholder="ملاحظة إدارية (اختياري)"></textarea>
                                    <button class="btn-manual" type="submit" onclick="return confirm('إغلاق التعارض يدويًا بدون تغيير البيانات؟')">إغلاق يدوي</button>
                                </form>
                            <?php else: ?>
                                <small>لا توجد إجراءات</small>
                            <?php endif; ?>

                            <details>
                                <summary>عرض التفاصيل</summary>
                                <div><strong>ملاحظة:</strong> <?php echo h($row['note']); ?></div>
                                <div><strong>Local Snapshot:</strong></div>
                                <pre><?php echo h(json_encode($localSnapshot ?: $row['local_snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                <div><strong>Online Snapshot:</strong></div>
                                <pre><?php echo h(json_encode($onlineSnapshot ?: $row['online_snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                            </details>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a class="back" href="settings.php">العودة إلى الإعدادات</a>
    </main>
</body>

</html>
