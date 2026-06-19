<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

$requiredPermissions = ['reports'];
include 'admin-only.php';

clinic_ensure_infrastructure($con);

mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS medical_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        report_date DATE NOT NULL,
        report_title VARCHAR(190) NOT NULL,
        diagnosis TEXT NULL,
        findings TEXT NULL,
        recommendations TEXT NULL,
        report_body LONGTEXT NULL,
        created_by VARCHAR(120) NULL,
        updated_by VARCHAR(120) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        INDEX idx_medical_reports_patient_date (patient_id, report_date),
        INDEX idx_medical_reports_date (report_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

clinic_ensure_column($con, 'medical_reports', 'medicines', 'TEXT NULL');

$patientId = (int) ($_GET['id'] ?? 0);
if ($patientId <= 0) {
    die('لم يتم تحديد المريض');
}

$patientStmt = mysqli_prepare($con, "
    SELECT id, full_name, age, phone_no
    FROM add_patient
    WHERE id = ? AND " . clinic_active_patient_where($con, 'add_patient') . "
    LIMIT 1
");
mysqli_stmt_bind_param($patientStmt, 'i', $patientId);
mysqli_stmt_execute($patientStmt);
$patientResult = mysqli_stmt_get_result($patientStmt);
$patient = $patientResult ? mysqli_fetch_assoc($patientResult) : null;
if (!$patient) {
    die('المريض غير موجود أو مؤرشف');
}

$flash = '';
$flashType = 'success';
$editingId = (int) ($_GET['report_id'] ?? 0);

$form = [
    'id' => 0,
    'report_date' => date('Y-m-d'),
    'report_title' => 'تقرير طبي',
    'diagnosis' => '',
    'medicines' => '',
    'findings' => '',
    'recommendations' => '',
    'report_body' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinic_require_csrf();

    $editingId = (int) ($_POST['report_id'] ?? 0);
    $reportDate = (string) ($_POST['report_date'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
        $reportDate = date('Y-m-d');
    }

    $reportTitle = trim((string) ($_POST['report_title'] ?? ''));
    $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));
    $medicines = trim((string) ($_POST['medicines'] ?? ''));
    $findings = trim((string) ($_POST['findings'] ?? ''));
    $recommendations = trim((string) ($_POST['recommendations'] ?? ''));
    $reportBody = trim((string) ($_POST['report_body'] ?? ''));

    if ($reportTitle === '') {
        $reportTitle = 'تقرير طبي';
    }

    if ($reportBody === '') {
        $reportBody = implode("\n\n", array_values(array_filter([
            $diagnosis,
            $medicines,
            $recommendations,
            $findings,
        ], static fn($v) => trim((string) $v) !== '')));
    }

    $actor = clinic_current_user();

    if ($editingId > 0) {
        $oldStmt = mysqli_prepare($con, "SELECT * FROM medical_reports WHERE id = ? AND patient_id = ? LIMIT 1");
        $oldData = null;
        if ($oldStmt) {
            mysqli_stmt_bind_param($oldStmt, 'ii', $editingId, $patientId);
            mysqli_stmt_execute($oldStmt);
            $oldRes = mysqli_stmt_get_result($oldStmt);
            $oldData = $oldRes ? mysqli_fetch_assoc($oldRes) : null;
        }

        $updateStmt = mysqli_prepare($con, "
            UPDATE medical_reports
            SET report_date = ?,
                report_title = ?,
                diagnosis = ?,
                medicines = ?,
                findings = ?,
                recommendations = ?,
                report_body = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND patient_id = ?
            LIMIT 1
        ");

        if ($updateStmt) {
            mysqli_stmt_bind_param(
                $updateStmt,
                'ssssssssii',
                $reportDate,
                $reportTitle,
                $diagnosis,
                $medicines,
                $findings,
                $recommendations,
                $reportBody,
                $actor,
                $editingId,
                $patientId
            );

            if (mysqli_stmt_execute($updateStmt)) {
                clinic_audit($con, 'update', 'medical_reports', $editingId, $oldData, [
                    'report_date' => $reportDate,
                    'report_title' => $reportTitle,
                    'diagnosis' => $diagnosis,
                    'medicines' => $medicines,
                ]);
                header('Location: patient_reports.php?id=' . $patientId . '&report_id=' . $editingId . '&saved=1');
                exit;
            }

            $flash = 'تعذر تحديث التقرير.';
            $flashType = 'error';
        } else {
            $flash = 'تعذر تجهيز تحديث التقرير.';
            $flashType = 'error';
        }
    } else {
        $insertStmt = mysqli_prepare($con, "
            INSERT INTO medical_reports
            (patient_id, report_date, report_title, diagnosis, medicines, findings, recommendations, report_body, created_by, updated_by, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if ($insertStmt) {
            mysqli_stmt_bind_param(
                $insertStmt,
                'isssssssss',
                $patientId,
                $reportDate,
                $reportTitle,
                $diagnosis,
                $medicines,
                $findings,
                $recommendations,
                $reportBody,
                $actor,
                $actor
            );

            if (mysqli_stmt_execute($insertStmt)) {
                $newId = (int) mysqli_insert_id($con);
                clinic_audit($con, 'insert', 'medical_reports', $newId, null, [
                    'patient_id' => $patientId,
                    'report_date' => $reportDate,
                    'report_title' => $reportTitle,
                    'medicines' => $medicines,
                ]);
                header('Location: patient_reports.php?id=' . $patientId . '&report_id=' . $newId . '&saved=1');
                exit;
            }

            $flash = 'تعذر حفظ التقرير.';
            $flashType = 'error';
        } else {
            $flash = 'تعذر تجهيز حفظ التقرير.';
            $flashType = 'error';
        }
    }

    $form = [
        'id' => $editingId,
        'report_date' => $reportDate,
        'report_title' => $reportTitle,
        'diagnosis' => $diagnosis,
        'medicines' => $medicines,
        'findings' => $findings,
        'recommendations' => $recommendations,
        'report_body' => $reportBody,
    ];
}

if (isset($_GET['saved'])) {
    $flash = 'تم حفظ التقرير الطبي بنجاح.';
    $flashType = 'success';
}

if ($editingId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editStmt = mysqli_prepare($con, "
        SELECT id, report_date, report_title, diagnosis, medicines, findings, recommendations, report_body
        FROM medical_reports
        WHERE id = ? AND patient_id = ?
        LIMIT 1
    ");
    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'ii', $editingId, $patientId);
        mysqli_stmt_execute($editStmt);
        $editRes = mysqli_stmt_get_result($editStmt);
        $editRow = $editRes ? mysqli_fetch_assoc($editRes) : null;
        if ($editRow) {
            $form = [
                'id' => (int) ($editRow['id'] ?? 0),
                'report_date' => (string) ($editRow['report_date'] ?? date('Y-m-d')),
                'report_title' => (string) ($editRow['report_title'] ?? 'تقرير طبي'),
                'diagnosis' => (string) ($editRow['diagnosis'] ?? ''),
                'medicines' => (string) ($editRow['medicines'] ?? ''),
                'findings' => (string) ($editRow['findings'] ?? ''),
                'recommendations' => (string) ($editRow['recommendations'] ?? ''),
                'report_body' => (string) ($editRow['report_body'] ?? ''),
            ];
        }
    }
}

$reports = [];
$listStmt = mysqli_prepare($con, "
    SELECT id, report_date, report_title, created_by, updated_at
    FROM medical_reports
    WHERE patient_id = ?
    ORDER BY report_date DESC, id DESC
    LIMIT 200
");
if ($listStmt) {
    mysqli_stmt_bind_param($listStmt, 'i', $patientId);
    mysqli_stmt_execute($listStmt);
    $listRes = mysqli_stmt_get_result($listStmt);
    while ($listRes && ($r = mysqli_fetch_assoc($listRes))) {
        $reports[] = $r;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير الطبي - <?php echo h($patient['full_name']); ?></title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        @page {
            size: A5 portrait;
            margin: 0;
        }

        :root {
            --bg: #eef3fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #d9e3ef;
            --primary: #1d4ed8;
            --ok: #166534;
            --err: #b91c1c;
        }

        body[data-theme="dark"],
        body.dark {
            --bg: #0b1220;
            --card: #111b2f;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #23314b;
            --primary: #60a5fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            max-width: 1180px;
            margin: 16px auto;
            padding: 0 12px 20px;
        }

        .head,
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(2, 6, 23, .08);
        }

        .head {
            padding: 14px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .head h1 {
            margin: 0;
            font-size: 22px;
            color: var(--primary);
        }

        .head .meta {
            color: var(--muted);
            margin-top: 4px;
            font-weight: 700;
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid var(--border);
            background: #f8fbff;
            color: var(--text);
            border-radius: 9px;
            min-height: 38px;
            padding: 8px 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .notice {
            margin-bottom: 10px;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
        }

        .notice.success {
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            color: var(--ok);
        }

        .notice.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--err);
        }

        .layout {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 12px;
        }

        .card {
            padding: 14px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 17px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }

        input,
        textarea {
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            font-family: inherit;
            background: transparent;
            color: var(--text);
        }

        textarea {
            min-height: 85px;
            resize: vertical;
            text-align: start;
        }

        .report-editor {
            min-height: 170px;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border-top: 1px solid #e8eef7;
            padding: 8px;
            text-align: right;
        }

        th {
            background: #f8fbff;
            color: #1e3a8a;
        }

        .print-sheet {
            display: none;
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {

            .head,
            .layout,
            .notice {
                display: none !important;
            }

            .print-sheet {
                display: block;
                position: absolute;
                top: 90mm;
                right: 20mm;
                left: 20mm;
                font-size: 18px;
                line-height: 2;
                white-space: pre-wrap;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="head">
            <div>
                <h1>التقارير الطبية</h1>
                <div class="meta">
                    المريض: <?php echo h($patient['full_name']); ?>
                    | العمر: <?php echo h($patient['age']); ?>
                    | الهاتف: <?php echo h($patient['phone_no']); ?>
                </div>
            </div>
            <div class="actions">
                <a class="btn" href="patient-file.php?id=<?php echo (int) $patientId; ?>">العودة لملف المريض</a>
                <button class="btn primary" type="button" onclick="preparePrint()">طباعة التقرير</button>
            </div>
        </section>

        <?php if ($flash !== ''): ?>
            <div class="notice <?php echo $flashType === 'error' ? 'error' : 'success'; ?>">
                <?php echo h($flash); ?>
            </div>
        <?php endif; ?>

        <section class="layout">
            <article class="card">
                <h2><?php echo ((int) ($form['id'] ?? 0) > 0) ? 'تعديل تقرير طبي' : 'إضافة تقرير طبي جديد'; ?></h2>
                <form method="post" class="grid" id="medicalReportForm">
                    <?php echo clinic_csrf_input(); ?>
                    <input type="hidden" name="report_id" value="<?php echo (int) ($form['id'] ?? 0); ?>">

                    <div class="field">
                        <label for="report_date">تاريخ التقرير</label>
                        <input id="report_date" type="date" name="report_date" value="<?php echo h((string) ($form['report_date'] ?? date('Y-m-d'))); ?>" required>
                    </div>

                    <div class="field">
                        <label for="report_title">عنوان التقرير</label>
                        <input id="report_title" type="text" name="report_title" value="<?php echo h((string) ($form['report_title'] ?? 'تقرير طبي')); ?>" required>
                    </div>

                    <div class="field full">
                        <label for="diagnosis">التشخيص</label>
                        <textarea id="diagnosis" name="diagnosis" class="bidi-input" dir="auto" placeholder="مثال: اعتلال شبكي سكري... "><?php echo h((string) ($form['diagnosis'] ?? '')); ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="findings">ملخص الحالة / الفحص</label>
                        <textarea id="findings" name="findings" class="bidi-input" dir="auto" placeholder="تفاصيل الفحص السريري أو نتائج الإجراء... "><?php echo h((string) ($form['findings'] ?? '')); ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="medicines">الأدوية</label>
                        <textarea id="medicines" name="medicines" class="bidi-input" dir="auto" placeholder="الأدوية المقترحة أو العلاج الدوائي... "><?php echo h((string) ($form['medicines'] ?? '')); ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="recommendations">التوصيات الطبية</label>
                        <textarea id="recommendations" name="recommendations" class="bidi-input" dir="auto" placeholder="الأدوية أو الخطة أو المتابعة... "><?php echo h((string) ($form['recommendations'] ?? '')); ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="report_body">نص التقرير للطباعة (يمكنك التعديل الحر)</label>
                        <textarea id="report_body" class="report-editor bidi-input" dir="auto" name="report_body" placeholder="النص النهائي الذي سيطبع على الراجيتة..."><?php echo h((string) ($form['report_body'] ?? '')); ?></textarea>
                    </div>

                    <div class="field full" style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn primary" type="submit">حفظ التقرير</button>
                        <button class="btn" type="button" id="buildFromSections">توليد النص من الحقول</button>
                        <a class="btn" href="patient_reports.php?id=<?php echo (int) $patientId; ?>">تقرير جديد فارغ</a>
                    </div>
                </form>
            </article>

            <article class="card">
                <h2>التقارير السابقة</h2>
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>العنوان</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="3">لا توجد تقارير محفوظة لهذا المريض.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><?php echo h((string) ($r['report_date'] ?? '')); ?></td>
                                    <td><?php echo h((string) ($r['report_title'] ?? 'تقرير طبي')); ?></td>
                                    <td>
                                        <a href="patient_reports.php?id=<?php echo (int) $patientId; ?>&report_id=<?php echo (int) $r['id']; ?>">فتح</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </article>
        </section>

        <div class="print-sheet" id="printSheet"></div>
    </main>

    <script>
        (function() {
            const diagnosis = document.getElementById('diagnosis');
            const findings = document.getElementById('findings');
            const reportBody = document.getElementById('report_body');
            const medicines = document.getElementById('medicines');
            const recommendations = document.getElementById('recommendations');
            
            const buildBtn = document.getElementById('buildFromSections');

            function detectDirection(text) {
                const value = (text || '').trim();
                if (value === '') {
                    return 'rtl';
                }

                const firstArabic = value.search(/[\u0600-\u06FF]/);
                const firstLatin = value.search(/[A-Za-z]/);

                if (firstArabic === -1 && firstLatin === -1) {
                    return 'rtl';
                }
                if (firstArabic === -1) {
                    return 'ltr';
                }
                if (firstLatin === -1) {
                    return 'rtl';
                }

                return firstLatin < firstArabic ? 'ltr' : 'rtl';
            }

            function applyDirection(element) {
                if (!element) {
                    return;
                }

                const dir = detectDirection(element.value || '');
                element.dir = dir;
                element.style.textAlign = dir === 'ltr' ? 'left' : 'right';
            }

            function buildText() {
                reportBody.value = [
                    (diagnosis.value || '').trim(),
                    (findings.value || '').trim(),
                    (medicines.value || '').trim(),
                    (recommendations.value || '').trim(),
                    
                ].filter(Boolean).join('\n\n');

                applyDirection(reportBody);
            }

            buildBtn.addEventListener('click', buildText);

            [diagnosis, medicines, findings, recommendations, reportBody].forEach((element) => {
                if (!element) {
                    return;
                }
                applyDirection(element);
                element.addEventListener('input', () => applyDirection(element));
            });
        })();

        function preparePrint() {
            const reportInput = document.getElementById('report_body');
            const reportText = reportInput.value || '';
            const printSheet = document.getElementById('printSheet');
            printSheet.textContent = reportText;
            const isLatin = /[A-Za-z]/.test(reportText);
            const isArabic = /[\u0600-\u06FF]/.test(reportText);
            const printDir = isLatin && (!isArabic || reportText.search(/[A-Za-z]/) < reportText.search(/[\u0600-\u06FF]/)) ? 'ltr' : 'rtl';
            printSheet.dir = printDir;
            printSheet.style.textAlign = printDir === 'ltr' ? 'left' : 'right';
            window.print();
        }
    </script>
</body>

</html>