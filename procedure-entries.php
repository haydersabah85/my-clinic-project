<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);
clinic_ensure_procedure_types($con);
clinic_ensure_procedure_entries($con);
clinic_ensure_daily_revenue($con);

$selectedDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$flashMap = [
    'entry_added' => 'تم حفظ الإجراء بنجاح.',
    'entry_updated' => 'تم تعديل الإجراء بنجاح.',
    'entry_deleted' => 'تم حذف الإجراء بنجاح.',
    'type_added' => 'تمت إضافة نوع الإجراء بنجاح.',
];
$flashKey = trim((string) ($_GET['flash'] ?? ''));
$flashType = (($_GET['flash_type'] ?? 'success') === 'error') ? 'error' : 'success';
$flashMessage = $flashMap[$flashKey] ?? '';
$editingEntry = null;

function procedure_entries_redirect(string $date, string $flashKey, string $flashType = 'success'): void
{
    header('Location: procedure-entries.php?date=' . urlencode($date) . '&flash=' . urlencode($flashKey) . '&flash_type=' . urlencode($flashType));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinic_require_csrf();

    if (isset($_POST['delete_entry'])) {
        $deleteId = (int) ($_POST['entry_id'] ?? 0);
        if ($deleteId > 0) {
            $oldStmt = mysqli_prepare($con, "SELECT * FROM procedure_entries WHERE id = ? LIMIT 1");
            $oldEntry = null;
            if ($oldStmt) {
                mysqli_stmt_bind_param($oldStmt, 'i', $deleteId);
                mysqli_stmt_execute($oldStmt);
                $oldResult = mysqli_stmt_get_result($oldStmt);
                $oldEntry = $oldResult ? mysqli_fetch_assoc($oldResult) : null;
            }

            $deleteStmt = mysqli_prepare($con, "DELETE FROM procedure_entries WHERE id = ? LIMIT 1");
            if ($deleteStmt) {
                mysqli_stmt_bind_param($deleteStmt, 'i', $deleteId);
                if (mysqli_stmt_execute($deleteStmt)) {
                    clinic_audit($con, 'delete', 'procedure_entries', $deleteId, $oldEntry, null);
                    procedure_entries_redirect($selectedDate, 'entry_deleted');
                } else {
                    $flashMessage = 'فشل حذف الإجراء.';
                    $flashType = 'error';
                }
            } else {
                $flashMessage = 'تعذر تجهيز حذف الإجراء.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'معرف الإجراء غير صالح.';
            $flashType = 'error';
        }
    }

    if (isset($_POST['add_type'])) {
        $newTypeName = trim((string) ($_POST['new_type_name'] ?? ''));
        $newTypeCategory = (string) ($_POST['new_type_category'] ?? 'other');
        if (!in_array($newTypeCategory, ['retina', 'laser', 'other'], true)) {
            $newTypeCategory = 'other';
        }

        if ($newTypeName === '') {
            $flashMessage = 'يرجى كتابة اسم الإجراء الجديد.';
            $flashType = 'error';
        } else {
            $insertType = mysqli_prepare($con, "INSERT IGNORE INTO procedure_types (type_name, category, is_active, sync_status) VALUES (?, ?, 1, ?)");
            if ($insertType) {
                $syncStatus = $IS_LOCAL ? 0 : 1;
                mysqli_stmt_bind_param($insertType, 'ssi', $newTypeName, $newTypeCategory, $syncStatus);
                if (mysqli_stmt_execute($insertType)) {
                    procedure_entries_redirect($selectedDate, 'type_added');
                } else {
                    $flashMessage = 'تعذر إضافة نوع الإجراء.';
                    $flashType = 'error';
                }
            } else {
                $flashMessage = 'تعذر تجهيز إضافة نوع الإجراء.';
                $flashType = 'error';
            }
        }
    }

    if (isset($_POST['add_entry'])) {
        $postedDate = $_POST['procedure_date'] ?? '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $postedDate)) {
            $selectedDate = $postedDate;
        }

        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $isExternalPatient = (string) ($_POST['is_external_patient'] ?? '') === '1';
        $externalPatientName = trim((string) ($_POST['external_patient_name'] ?? ''));
        $typeId = (int) ($_POST['procedure_type_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $unitCost = max(0, (float) ($_POST['unit_cost'] ?? 0));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($typeId <= 0) {
            $flashMessage = 'يرجى اختيار نوع الإجراء.';
            $flashType = 'error';
        } else {
            $typeStmt = mysqli_prepare($con, "SELECT type_name, category FROM procedure_types WHERE id = ? AND is_active = 1 LIMIT 1");
            if ($typeStmt) {
                mysqli_stmt_bind_param($typeStmt, 'i', $typeId);
                mysqli_stmt_execute($typeStmt);
                $typeResult = mysqli_stmt_get_result($typeStmt);
                $typeRow = $typeResult ? mysqli_fetch_assoc($typeResult) : null;

                if (!$typeRow) {
                    $flashMessage = 'نوع الإجراء غير متاح.';
                    $flashType = 'error';
                } else {
                    $patientName = '';
                    $patientIdForStore = 0;

                    if ($isExternalPatient) {
                        $patientName = $externalPatientName;
                        if ($patientName === '') {
                            $flashMessage = 'يرجى إدخال اسم المراجع الخارجي.';
                            $flashType = 'error';
                            goto end_add_entry;
                        }
                    } else {
                        if ($patientId <= 0) {
                            $flashMessage = 'يرجى اختيار المريض من نتائج البحث أولاً.';
                            $flashType = 'error';
                            goto end_add_entry;
                        }

                        $activePatientWhere = clinic_active_patient_where($con, 'add_patient');
                        $patientStmt = mysqli_prepare($con, "SELECT id, full_name FROM add_patient WHERE id = ? AND $activePatientWhere LIMIT 1");
                        if ($patientStmt) {
                            mysqli_stmt_bind_param($patientStmt, 'i', $patientId);
                            mysqli_stmt_execute($patientStmt);
                            $patientResult = mysqli_stmt_get_result($patientStmt);
                            $patientRow = $patientResult ? mysqli_fetch_assoc($patientResult) : null;
                            if ($patientRow) {
                                $patientName = (string) ($patientRow['full_name'] ?? '');
                                $patientIdForStore = (int) ($patientRow['id'] ?? 0);
                            }
                        }
                    }

                    if ($patientName === '') {
                        $flashMessage = 'المريض المحدد غير متاح. يرجى إعادة الاختيار من البحث.';
                        $flashType = 'error';
                        goto end_add_entry;
                    }

                    $typeName = (string) $typeRow['type_name'];
                    $category = (string) $typeRow['category'];
                    $totalCost = $qty * $unitCost;
                    $enteredBy = clinic_current_user();
                    $syncStatus = $IS_LOCAL ? 0 : 1;

                    $insertStmt = mysqli_prepare($con, "
                        INSERT INTO procedure_entries
                        (procedure_date, patient_id, patient_name, procedure_type_id, procedure_type_name, category, qty, unit_cost, total_cost, notes, entered_by, sync_status, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    if ($insertStmt) {
                        mysqli_stmt_bind_param(
                            $insertStmt,
                            'sisissiddssi',
                            $selectedDate,
                            $patientIdForStore,
                            $patientName,
                            $typeId,
                            $typeName,
                            $category,
                            $qty,
                            $unitCost,
                            $totalCost,
                            $notes,
                            $enteredBy,
                            $syncStatus
                        );

                        if (mysqli_stmt_execute($insertStmt)) {
                            clinic_audit(
                                $con,
                                'insert',
                                'procedure_entries',
                                null,
                                null,
                                [
                                    'procedure_date' => $selectedDate,
                                    'patient_id' => $patientIdForStore,
                                    'patient_name' => $patientName,
                                    'procedure_type' => $typeName,
                                    'qty' => $qty,
                                    'unit_cost' => $unitCost,
                                    'total_cost' => $totalCost,
                                ]
                            );
                            procedure_entries_redirect($selectedDate, 'entry_added');
                        } else {
                            $flashMessage = 'فشل حفظ الإجراء.';
                            $flashType = 'error';
                        }
                    } else {
                        $flashMessage = 'تعذر تجهيز حفظ الإجراء.';
                        $flashType = 'error';
                    }
                }
            }
        }

        end_add_entry:;
    }

    if (isset($_POST['save_entry'])) {
        $entryId = (int) ($_POST['entry_id'] ?? 0);
        $postedDate = $_POST['procedure_date'] ?? '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $postedDate)) {
            $selectedDate = $postedDate;
        }

        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $isExternalPatient = (string) ($_POST['is_external_patient'] ?? '') === '1';
        $externalPatientName = trim((string) ($_POST['external_patient_name'] ?? ''));
        $typeId = (int) ($_POST['procedure_type_id'] ?? 0);
        $qty = max(1, (int) ($_POST['qty'] ?? 1));
        $unitCost = max(0, (float) ($_POST['unit_cost'] ?? 0));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($entryId <= 0 || $typeId <= 0) {
            $flashMessage = 'بيانات التعديل غير مكتملة.';
            $flashType = 'error';
        } else {
            $typeStmt = mysqli_prepare($con, "SELECT type_name, category FROM procedure_types WHERE id = ? AND is_active = 1 LIMIT 1");
            $typeRow = null;
            if ($typeStmt) {
                mysqli_stmt_bind_param($typeStmt, 'i', $typeId);
                mysqli_stmt_execute($typeStmt);
                $typeResult = mysqli_stmt_get_result($typeStmt);
                $typeRow = $typeResult ? mysqli_fetch_assoc($typeResult) : null;
            }

            if (!$typeRow) {
                $flashMessage = 'نوع الإجراء غير متاح.';
                $flashType = 'error';
            } else {
                $patientName = '';
                $patientIdForStore = 0;

                if ($isExternalPatient) {
                    $patientName = $externalPatientName;
                    if ($patientName === '') {
                        $flashMessage = 'يرجى إدخال اسم المراجع الخارجي.';
                        $flashType = 'error';
                    }
                } else {
                    $activePatientWhere = clinic_active_patient_where($con, 'add_patient');
                    $patientStmt = mysqli_prepare($con, "SELECT id, full_name FROM add_patient WHERE id = ? AND $activePatientWhere LIMIT 1");
                    if ($patientStmt) {
                        mysqli_stmt_bind_param($patientStmt, 'i', $patientId);
                        mysqli_stmt_execute($patientStmt);
                        $patientResult = mysqli_stmt_get_result($patientStmt);
                        $patientRow = $patientResult ? mysqli_fetch_assoc($patientResult) : null;
                        if ($patientRow) {
                            $patientName = (string) ($patientRow['full_name'] ?? '');
                            $patientIdForStore = (int) ($patientRow['id'] ?? 0);
                        }
                    }
                }

                if ($patientName === '') {
                    $flashMessage = $isExternalPatient
                        ? 'يرجى إدخال اسم المراجع الخارجي.'
                        : 'المريض المحدد غير متاح.';
                    $flashType = 'error';
                } else {
                    $typeName = (string) $typeRow['type_name'];
                    $category = (string) $typeRow['category'];
                    $totalCost = $qty * $unitCost;
                    $syncStatus = $IS_LOCAL ? 0 : 1;

                    $oldStmt = mysqli_prepare($con, "SELECT * FROM procedure_entries WHERE id = ? LIMIT 1");
                    $oldEntry = null;
                    if ($oldStmt) {
                        mysqli_stmt_bind_param($oldStmt, 'i', $entryId);
                        mysqli_stmt_execute($oldStmt);
                        $oldResult = mysqli_stmt_get_result($oldStmt);
                        $oldEntry = $oldResult ? mysqli_fetch_assoc($oldResult) : null;
                    }

                    $updateStmt = mysqli_prepare($con, "
                        UPDATE procedure_entries
                        SET procedure_date = ?,
                            patient_id = ?,
                            patient_name = ?,
                            procedure_type_id = ?,
                            procedure_type_name = ?,
                            category = ?,
                            qty = ?,
                            unit_cost = ?,
                            total_cost = ?,
                            notes = ?,
                            sync_status = ?,
                            updated_at = NOW()
                        WHERE id = ?
                        LIMIT 1
                    ");

                    if ($updateStmt) {
                        mysqli_stmt_bind_param(
                            $updateStmt,
                            'sisissiddsii',
                            $selectedDate,
                            $patientIdForStore,
                            $patientName,
                            $typeId,
                            $typeName,
                            $category,
                            $qty,
                            $unitCost,
                            $totalCost,
                            $notes,
                            $syncStatus,
                            $entryId
                        );

                        if (mysqli_stmt_execute($updateStmt)) {
                            clinic_audit(
                                $con,
                                'update',
                                'procedure_entries',
                                $entryId,
                                $oldEntry,
                                [
                                    'procedure_date' => $selectedDate,
                                    'patient_id' => $patientIdForStore,
                                    'patient_name' => $patientName,
                                    'procedure_type' => $typeName,
                                    'qty' => $qty,
                                    'unit_cost' => $unitCost,
                                    'total_cost' => $totalCost,
                                ]
                            );
                            procedure_entries_redirect($selectedDate, 'entry_updated');
                        } else {
                            $flashMessage = 'فشل تعديل الإجراء.';
                            $flashType = 'error';
                        }
                    } else {
                        $flashMessage = 'تعذر تجهيز تعديل الإجراء.';
                        $flashType = 'error';
                    }
                }
            }
        }
    }
}

$procedureTypes = [];
$typesQuery = mysqli_query($con, "SELECT id, type_name, category FROM procedure_types WHERE is_active = 1 ORDER BY type_name ASC");
while ($typesQuery && ($row = mysqli_fetch_assoc($typesQuery))) {
    $procedureTypes[] = $row;
}

$entries = [];
$entriesStmt = mysqli_prepare($con, "
    SELECT id, patient_id, patient_name, procedure_type_name, category, qty, unit_cost, total_cost, entered_by, created_at
    FROM procedure_entries
    WHERE procedure_date = ?
    ORDER BY id DESC
    LIMIT 100
");
if ($entriesStmt) {
    mysqli_stmt_bind_param($entriesStmt, 's', $selectedDate);
    mysqli_stmt_execute($entriesStmt);
    $entriesResult = mysqli_stmt_get_result($entriesStmt);
    while ($entriesResult && ($row = mysqli_fetch_assoc($entriesResult))) {
        $entries[] = $row;
    }
}

$totals = ['retina' => 0.0, 'laser' => 0.0, 'other' => 0.0, 'all' => 0.0];
$counts = ['retina' => 0, 'laser' => 0, 'other' => 0, 'all' => 0];
foreach ($entries as $entry) {
    $cat = (string) ($entry['category'] ?? 'other');
    if (!isset($totals[$cat])) {
        $cat = 'other';
    }
    $amount = (float) ($entry['total_cost'] ?? 0);
    $qtyVal = (int) ($entry['qty'] ?? 0);
    $totals[$cat] += $amount;
    $totals['all'] += $amount;
    $counts[$cat] += $qtyVal;
    $counts['all'] += $qtyVal;
}

$canAccessRevenue = (($_SESSION['role'] ?? '') === 'admin');

$editId = (int) ($_GET['edit_id'] ?? 0);
if ($editId > 0) {
    $editStmt = mysqli_prepare($con, "
        SELECT id, procedure_date, patient_id, patient_name, procedure_type_id, qty, unit_cost, notes
        FROM procedure_entries
        WHERE id = ?
        LIMIT 1
    ");
    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        $editingEntry = $editResult ? mysqli_fetch_assoc($editResult) : null;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدخال إجراءات اليوم</title>
    <style>
        :root {
            --bg: #f5f8ff;
            --card: #fff;
            --border: #dce4f3;
            --ink: #0f172a;
            --accent: #0f766e;
            --accent2: #1d4ed8;
            --danger: #b91c1c;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: linear-gradient(180deg, #ecf3ff 0%, var(--bg) 100%);
            color: var(--ink);
        }

        .wrap {
            max-width: 1120px;
            margin: 18px auto;
            padding: 0 12px 20px;
        }

        .head,
        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(2, 6, 23, .08);
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .head h1 {
            margin: 0;
            font-size: 23px;
            color: #0b4d46;
        }

        .actions a {
            text-decoration: none;
            color: #fff;
            background: var(--accent2);
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
            grid-template-columns: 1.5fr 1fr;
            gap: 12px;
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
        select,
        textarea {
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            font-family: inherit;
        }

        textarea {
            min-height: 78px;
            resize: vertical;
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

        .summary .item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
        }

        .patient-search-wrap {
            position: relative;
        }

        .patient-search-results {
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            left: 0;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            max-height: 220px;
            overflow-y: auto;
            z-index: 30;
            box-shadow: 0 10px 18px rgba(2, 6, 23, 0.12);
            display: none;
        }

        .patient-search-item {
            padding: 8px 10px;
            border-top: 1px solid #eef2f7;
            cursor: pointer;
        }

        .patient-search-item:first-child {
            border-top: none;
        }

        .patient-search-item:hover {
            background: #f8fbff;
        }

        .patient-search-item.active {
            background: #e8f1ff;
            outline: 1px solid #93c5fd;
        }

        .patient-search-item strong {
            display: block;
            font-size: 14px;
        }

        .patient-search-item small {
            color: #64748b;
            font-size: 12px;
        }

        .patient-picked {
            margin-top: 4px;
            font-size: 12px;
            color: #0f766e;
            font-weight: 700;
        }

        .external-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #334155;
        }

        .external-toggle input {
            min-height: auto;
        }

        .external-patient-wrap {
            margin-top: 8px;
            display: none;
        }

        .patient-picked-actions {
            margin-top: 6px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .patient-picked-actions a,
        .patient-picked-actions button {
            border: 1px solid var(--border);
            border-radius: 8px;
            min-height: 34px;
            padding: 6px 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            background: #f8fbff;
            color: #1d4ed8;
        }

        .patient-picked-actions button {
            color: #334155;
        }

        .patient-picked-actions a.is-disabled {
            pointer-events: none;
            color: #94a3b8;
            background: #f8fafc;
        }

        .patient-link {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 700;
        }

        .patient-link:hover {
            text-decoration: underline;
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .icon-btn {
            border: 1px solid var(--border);
            background: #f8fbff;
            border-radius: 8px;
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
            line-height: 1;
        }

        .icon-btn.edit {
            color: #1d4ed8;
        }

        .icon-btn.delete {
            color: #b91c1c;
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

        .cat-retina {
            color: #0f766e;
            font-weight: 700;
        }

        .cat-laser {
            color: #1d4ed8;
            font-weight: 700;
        }

        .cat-other {
            color: #6b7280;
            font-weight: 700;
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
            <h1>إدخال إجراءات اليوم</h1>
            <div class="actions">
                <a href="visits.php">زيارات اليوم</a>
                <?php if ($canAccessRevenue): ?>
                    <a href="daily-revenue.php?date=<?php echo urlencode($selectedDate); ?>">صفحة الإيرادات</a>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($flashMessage !== ''): ?>
            <div class="notice <?php echo $flashType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <section class="grid">
            <article class="panel">
                <h2><?php echo $editingEntry ? 'تعديل الإجراء' : 'إضافة إجراء لمريض'; ?></h2>
                <form method="post" class="form-grid">
                    <?php echo clinic_csrf_input(); ?>
                    <input type="hidden" name="entry_id" value="<?php echo (int) ($editingEntry['id'] ?? 0); ?>">
                    <div class="field">
                        <label for="procedure_date">التاريخ</label>
                        <input id="procedure_date" type="date" name="procedure_date" value="<?php echo htmlspecialchars((string) ($editingEntry['procedure_date'] ?? $selectedDate), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="field">
                        <label for="patient_name">اسم المريض</label>
                        <?php $isEditingExternal = ((int) ($editingEntry['patient_id'] ?? 0) <= 0) && trim((string) ($editingEntry['patient_name'] ?? '')) !== ''; ?>
                        <label class="external-toggle" for="is_external_patient">
                            <input id="is_external_patient" type="checkbox" name="is_external_patient" value="1" <?php echo $isEditingExternal ? 'checked' : ''; ?>>
                            مراجع خارجي (غير مسجل) - إدخال الاسم يدويًا
                        </label>
                        <div class="patient-search-wrap">
                            <input id="patient_name" type="text" name="patient_name" placeholder="اكتب اسم المريض أو الهاتف" autocomplete="off" value="<?php echo htmlspecialchars((string) ($editingEntry['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <input id="patient_id" type="hidden" name="patient_id" value="<?php echo (int) ($editingEntry['patient_id'] ?? 0); ?>">
                            <div id="patientResults" class="patient-search-results"></div>
                        </div>
                        <div id="externalPatientWrap" class="external-patient-wrap" style="<?php echo $isEditingExternal ? 'display:block;' : ''; ?>">
                            <input id="external_patient_name" type="text" name="external_patient_name" placeholder="اسم المراجع الخارجي" value="<?php echo htmlspecialchars($isEditingExternal ? (string) ($editingEntry['patient_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div id="pickedPatient" class="patient-picked">
                            <?php if (!empty($editingEntry['patient_id'])): ?>
                                تم اختيار المريض: <?php echo htmlspecialchars((string) ($editingEntry['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (ID: <?php echo (int) ($editingEntry['patient_id'] ?? 0); ?>)
                            <?php elseif ($isEditingExternal): ?>
                                مراجع خارجي: <?php echo htmlspecialchars((string) ($editingEntry['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                لم يتم اختيار مريض بعد
                            <?php endif; ?>
                        </div>
                        <div class="patient-picked-actions">
                            <a id="openPatientFile" href="<?php echo !empty($editingEntry['patient_id']) ? 'patient-file.php?id=' . (int) $editingEntry['patient_id'] : '#'; ?>" class="<?php echo empty($editingEntry['patient_id']) ? 'is-disabled' : ''; ?>" aria-disabled="<?php echo empty($editingEntry['patient_id']) ? 'true' : 'false'; ?>">فتح ملف المريض</a>
                            <button id="clearPatientSelection" type="button">مسح الاختيار</button>
                        </div>
                    </div>
                    <div class="field">
                        <label for="procedure_type_id">نوع الإجراء</label>
                        <select id="procedure_type_id" name="procedure_type_id" required>
                            <option value="">اختر نوع الإجراء</option>
                            <?php foreach ($procedureTypes as $type): ?>
                                <option value="<?php echo (int) $type['id']; ?>" <?php echo ((int) ($editingEntry['procedure_type_id'] ?? 0) === (int) $type['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $type['type_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string) $type['category'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="qty">العدد</label>
                        <input id="qty" type="number" min="1" step="1" name="qty" value="<?php echo (int) ($editingEntry['qty'] ?? 1); ?>" required>
                    </div>
                    <div class="field">
                        <label for="unit_cost">سعر الوحدة</label>
                        <input id="unit_cost" type="number" min="0" step="0.01" name="unit_cost" value="<?php echo (float) ($editingEntry['unit_cost'] ?? 0); ?>" required>
                    </div>
                    <div class="field full">
                        <label for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" placeholder="اختياري"><?php echo htmlspecialchars((string) ($editingEntry['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="field full">
                        <?php if ($editingEntry): ?>
                            <button type="submit" class="btn" name="save_entry">حفظ التعديل</button>
                            <a class="icon-btn" style="padding:0 12px;width:auto;text-decoration:none;color:#334155;" href="procedure-entries.php?date=<?php echo urlencode($selectedDate); ?>">إلغاء التعديل</a>
                        <?php else: ?>
                            <button type="submit" class="btn" name="add_entry">حفظ الإجراء</button>
                        <?php endif; ?>
                    </div>
                </form>
            </article>

            <article class="panel">
                <h2>إضافة نوع إجراء جديد</h2>
                <form method="post" class="form-grid">
                    <?php echo clinic_csrf_input(); ?>
                    <div class="field full">
                        <label for="new_type_name">اسم الإجراء</label>
                        <input id="new_type_name" type="text" name="new_type_name" placeholder="مثال: تصوير OCT" required>
                    </div>
                    <div class="field full">
                        <label for="new_type_category">الفئة</label>
                        <select id="new_type_category" name="new_type_category" required>
                            <option value="retina">شبكية</option>
                            <option value="laser">ليزر</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="field full">
                        <button type="submit" class="btn" name="add_type">إضافة النوع</button>
                    </div>
                </form>

                <h2 style="margin-top:16px;">ملخص اليوم</h2>
                <div class="summary">
                    <div class="item"><span>عدد إجراءات الشبكية</span><span><?php echo (int) $counts['retina']; ?></span></div>
                    <div class="item"><span>إيراد الشبكية</span><span><?php echo number_format((float) $totals['retina'], 0); ?></span></div>
                    <div class="item"><span>عدد إجراءات الليزر</span><span><?php echo (int) $counts['laser']; ?></span></div>
                    <div class="item"><span>إيراد الليزر</span><span><?php echo number_format((float) $totals['laser'], 0); ?></span></div>
                    <div class="item"><span>إجراءات أخرى</span><span><?php echo (int) $counts['other']; ?></span></div>
                    <div class="item"><span>إجمالي إيراد الإجراءات</span><span><?php echo number_format((float) $totals['all'], 0); ?></span></div>
                </div>
            </article>
        </section>

        <section class="panel" style="margin-top:12px;">
            <h2>إجراءات <?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المريض</th>
                        <th>الإجراء</th>
                        <th>الفئة</th>
                        <th>العدد</th>
                        <th>سعر الوحدة</th>
                        <th>المجموع</th>
                        <th>أدخل بواسطة</th>
                        <th>تحكم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="9">لا توجد إجراءات لهذا اليوم.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php $cat = (string) ($entry['category'] ?? 'other'); ?>
                            <tr>
                                <td><?php echo (int) $entry['id']; ?></td>
                                <td>
                                    <?php if ((int) ($entry['patient_id'] ?? 0) > 0): ?>
                                        <a class="patient-link" href="patient-file.php?id=<?php echo (int) $entry['patient_id']; ?>">
                                            <?php echo htmlspecialchars((string) $entry['patient_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars((string) $entry['patient_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string) $entry['procedure_type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cat-<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $entry['qty']; ?></td>
                                <td><?php echo number_format((float) $entry['unit_cost'], 0); ?></td>
                                <td><?php echo number_format((float) $entry['total_cost'], 0); ?></td>
                                <td><?php echo htmlspecialchars((string) ($entry['entered_by'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn edit" href="procedure-entries.php?date=<?php echo urlencode($selectedDate); ?>&edit_id=<?php echo (int) $entry['id']; ?>" title="تعديل">✏️</a>
                                        <form method="post" style="margin:0;display:inline;">
                                            <?php echo clinic_csrf_input(); ?>
                                            <input type="hidden" name="entry_id" value="<?php echo (int) $entry['id']; ?>">
                                            <button type="submit" name="delete_entry" class="icon-btn delete" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الإجراء؟');">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
        (function() {
            const shouldFocusNewEntry = <?php echo json_encode(in_array($flashKey, ['entry_added', 'entry_updated'], true) && !$editingEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const nameInput = document.getElementById('patient_name');
            const idInput = document.getElementById('patient_id');
            const resultsBox = document.getElementById('patientResults');
            const picked = document.getElementById('pickedPatient');
            const openPatientFile = document.getElementById('openPatientFile');
            const clearPatientSelection = document.getElementById('clearPatientSelection');
            const externalToggle = document.getElementById('is_external_patient');
            const externalPatientWrap = document.getElementById('externalPatientWrap');
            const externalPatientName = document.getElementById('external_patient_name');
            let timer = null;
            let currentItems = [];
            let activeIndex = -1;

            if (shouldFocusNewEntry && nameInput) {
                nameInput.focus();
            }

            function clearSelection() {
                idInput.value = '';
                picked.textContent = 'لم يتم اختيار مريض بعد';
                openPatientFile.classList.add('is-disabled');
                openPatientFile.setAttribute('aria-disabled', 'true');
                openPatientFile.setAttribute('href', '#');
            }

            function updatePatientMode() {
                const isExternal = !!(externalToggle && externalToggle.checked);
                if (isExternal) {
                    idInput.value = '';
                    hideResults();
                    nameInput.value = '';
                    nameInput.readOnly = true;
                    nameInput.placeholder = 'تم تفعيل وضع المراجع الخارجي';
                    externalPatientWrap.style.display = 'block';
                    externalPatientName.required = true;
                    picked.textContent = externalPatientName.value.trim() ? ('مراجع خارجي: ' + externalPatientName.value.trim()) : 'مراجع خارجي (لم يتم إدخال الاسم بعد)';
                    openPatientFile.classList.add('is-disabled');
                    openPatientFile.setAttribute('aria-disabled', 'true');
                    openPatientFile.setAttribute('href', '#');
                } else {
                    nameInput.readOnly = false;
                    nameInput.placeholder = 'اكتب اسم المريض أو الهاتف';
                    externalPatientWrap.style.display = 'none';
                    externalPatientName.required = false;
                    externalPatientName.value = '';
                    if (idInput.value) {
                        picked.textContent = 'تم اختيار المريض: ' + (nameInput.value || '') + ' (ID: ' + idInput.value + ')';
                        openPatientFile.classList.remove('is-disabled');
                        openPatientFile.setAttribute('aria-disabled', 'false');
                        openPatientFile.setAttribute('href', 'patient-file.php?id=' + encodeURIComponent(idInput.value));
                    } else {
                        clearSelection();
                    }
                }
            }

            function hideResults() {
                resultsBox.style.display = 'none';
                resultsBox.innerHTML = '';
                currentItems = [];
                activeIndex = -1;
            }

            function setActiveItem(index) {
                const rows = resultsBox.querySelectorAll('.patient-search-item');
                rows.forEach((row, i) => {
                    row.classList.toggle('active', i === index);
                });

                activeIndex = index;
                if (index >= 0 && rows[index]) {
                    rows[index].scrollIntoView({
                        block: 'nearest'
                    });
                }
            }

            function pickItem(item) {
                nameInput.value = item.full_name || '';
                idInput.value = item.id || '';
                picked.textContent = 'تم اختيار المريض: ' + (item.full_name || '') + ' (ID: ' + (item.id || '-') + ')';
                openPatientFile.classList.remove('is-disabled');
                openPatientFile.setAttribute('aria-disabled', 'false');
                openPatientFile.setAttribute('href', 'patient-file.php?id=' + encodeURIComponent(item.id || ''));
                hideResults();
            }

            function renderResults(items) {
                if (!Array.isArray(items) || items.length === 0) {
                    hideResults();
                    return;
                }

                currentItems = items;
                activeIndex = -1;
                resultsBox.innerHTML = '';
                items.forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'patient-search-item';
                    row.innerHTML =
                        '<strong>' + (item.full_name || '') + '</strong>' +
                        '<small>ID: ' + (item.id || '-') + ' | الهاتف: ' + (item.phone_no || '-') + ' | العمر: ' + (item.age || '-') + '</small>';

                    row.addEventListener('click', () => {
                        pickItem(item);
                    });

                    row.addEventListener('mouseenter', () => {
                        const rows = Array.from(resultsBox.querySelectorAll('.patient-search-item'));
                        const idx = rows.indexOf(row);
                        setActiveItem(idx);
                    });

                    resultsBox.appendChild(row);
                });

                resultsBox.style.display = 'block';
            }

            async function searchPatients(term) {
                try {
                    const response = await fetch('procedure-patient-search.php?q=' + encodeURIComponent(term), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) {
                        hideResults();
                        return;
                    }
                    const data = await response.json();
                    renderResults(data.items || []);
                } catch (e) {
                    hideResults();
                }
            }

            nameInput.addEventListener('input', () => {
                if (externalToggle && externalToggle.checked) {
                    return;
                }
                clearSelection();
                const term = nameInput.value.trim();
                if (term.length < 2) {
                    hideResults();
                    return;
                }

                if (timer) {
                    clearTimeout(timer);
                }

                timer = setTimeout(() => {
                    searchPatients(term);
                }, 180);
            });

            nameInput.addEventListener('keydown', (event) => {
                if (externalToggle && externalToggle.checked) {
                    return;
                }
                const hasResults = resultsBox.style.display === 'block' && currentItems.length > 0;

                if (event.key === 'Escape') {
                    hideResults();
                    return;
                }

                if (!hasResults) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    const nextIndex = activeIndex < currentItems.length - 1 ? activeIndex + 1 : 0;
                    setActiveItem(nextIndex);
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    const prevIndex = activeIndex > 0 ? activeIndex - 1 : currentItems.length - 1;
                    setActiveItem(prevIndex);
                    return;
                }

                if (event.key === 'Enter' && activeIndex >= 0 && currentItems[activeIndex]) {
                    event.preventDefault();
                    pickItem(currentItems[activeIndex]);
                }
            });

            document.addEventListener('click', (event) => {
                if (!resultsBox.contains(event.target) && event.target !== nameInput) {
                    hideResults();
                }
            });

            clearPatientSelection.addEventListener('click', () => {
                if (externalToggle && externalToggle.checked) {
                    externalPatientName.value = '';
                    picked.textContent = 'مراجع خارجي (لم يتم إدخال الاسم بعد)';
                    externalPatientName.focus();
                    return;
                }

                nameInput.value = '';
                clearSelection();
                hideResults();
                nameInput.focus();
            });

            openPatientFile.addEventListener('click', (event) => {
                if (!idInput.value) {
                    event.preventDefault();
                }
            });

            externalToggle.addEventListener('change', () => {
                updatePatientMode();
            });

            externalPatientName.addEventListener('input', () => {
                if (externalToggle.checked) {
                    const val = externalPatientName.value.trim();
                    picked.textContent = val ? ('مراجع خارجي: ' + val) : 'مراجع خارجي (لم يتم إدخال الاسم بعد)';
                }
            });

            updatePatientMode();

            nameInput.form.addEventListener('submit', (event) => {
                if (externalToggle && externalToggle.checked) {
                    if (!externalPatientName.value.trim()) {
                        event.preventDefault();
                        alert('يرجى إدخال اسم المراجع الخارجي.');
                        externalPatientName.focus();
                    }
                    return;
                }

                if (!idInput.value) {
                    event.preventDefault();
                    alert('يرجى اختيار المريض من نتائج البحث أولاً.');
                    nameInput.focus();
                }
            });
        })();
    </script>
</body>

</html>