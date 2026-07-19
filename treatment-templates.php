<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$frequency_options = clinic_prescription_frequency_options();
$duration_options = clinic_prescription_duration_options();
$medicines = [];
$medicine_result = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines ORDER BY medicine_name ASC");
while ($medicine = mysqli_fetch_assoc($medicine_result)) {
    $medicines[] = $medicine;
}

function templateMedicineOptions(array $medicines, $selectedId = ''): string
{
    $html = '<option value="">اختر دواء</option>';
    foreach ($medicines as $medicine) {
        $id = (string) $medicine['id'];
        $selected = ((string) $selectedId === $id) ? ' selected' : '';
        $label = h($medicine['medicine_name'] . ' ' . $medicine['medicine_form']);
        $html .= "<option value=\"{$id}\"{$selected}>{$label}</option>";
    }
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['template_name'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $followup_after_days_input = trim($_POST['followup_after_days'] ?? '');
    $followup_reason = trim($_POST['followup_reason'] ?? '');
    $followup_note = trim($_POST['followup_note'] ?? '');
    $medicine_ids = $_POST['medicine_id'] ?? [];
    $doses = $_POST['dose'] ?? [];
    $frequencies = $_POST['frequency'] ?? [];
    $durations = $_POST['duration'] ?? [];
    $eyes = $_POST['eye'] ?? [];
    $instructions_list = $_POST['instructions'] ?? [];

    $template_items = [];
    $template_lines = [];
    $medicine_lookup = [];
    foreach ($medicines as $medicine) {
        $medicine_lookup[(int) $medicine['id']] = $medicine['medicine_name'] . ' ' . $medicine['medicine_form'];
    }

    foreach ($medicine_ids as $index => $medicine_id) {
        $medicine_id = (int) $medicine_id;
        if ($medicine_id <= 0) {
            continue;
        }

        $item = [
            'medicine_id' => $medicine_id,
            'dose' => trim($doses[$index] ?? ''),
            'frequency' => trim($frequencies[$index] ?? ''),
            'duration' => trim($durations[$index] ?? ''),
            'eye' => trim($eyes[$index] ?? ''),
            'instructions' => trim($instructions_list[$index] ?? ''),
        ];
        $template_items[] = $item;

        $eye_label = '';
        if ($item['eye'] === 'right') {
            $eye_label = 'العين اليمنى';
        } elseif ($item['eye'] === 'left') {
            $eye_label = 'العين اليسرى';
        } elseif ($item['eye'] === 'both') {
            $eye_label = 'العينين';
        }

        $parts = array_filter([
            $medicine_lookup[$medicine_id] ?? ('دواء #' . $medicine_id),
            $item['dose'],
            $item['frequency'],
            $item['duration'],
            $eye_label,
            $item['instructions'],
        ], static fn($value) => $value !== '');
        $template_lines[] = implode(' - ', $parts);
    }

    $followup_after_days = ($followup_after_days_input !== '' && is_numeric($followup_after_days_input))
        ? max(0, (int) $followup_after_days_input)
        : null;
    $payload_json = json_encode([
        'items' => $template_items,
        'followup_after_days' => $followup_after_days,
        'followup_reason' => $followup_reason,
        'followup_note' => $followup_note,
    ], JSON_UNESCAPED_UNICODE);
    $medicines_text = implode(PHP_EOL, $template_lines);

    if ($name !== '') {
        $stmt = mysqli_prepare($con, "
            INSERT INTO treatment_templates (
                template_name, diagnosis, medicines_text, notes, payload_json, followup_after_days, followup_reason, followup_note, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        mysqli_stmt_bind_param($stmt, "sssssiss", $name, $diagnosis, $medicines_text, $notes, $payload_json, $followup_after_days, $followup_reason, $followup_note);
        mysqli_stmt_execute($stmt);
        clinic_audit($con, 'create', 'treatment_templates', mysqli_insert_id($con), null, $_POST);
    }

    header("Location: treatment-templates.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = mysqli_prepare($con, "DELETE FROM treatment_templates WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    clinic_audit($con, 'delete', 'treatment_templates', $id);
    header("Location: treatment-templates.php");
    exit;
}

$templates = mysqli_query($con, "SELECT * FROM treatment_templates ORDER BY template_name ASC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قوالب العلاج</title>
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
            max-width: 1120px;
            margin: auto;
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
            padding: 18px;
        }

        h1,
        h2 {
            margin-top: 0;
            color: #1d4ed8;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: 900;
            color: #334155;
        }

        input,
        textarea,
        button {
            width: 100%;
            border: 1px solid #d9e2ec;
            border-radius: 10px;
            padding: 10px 12px;
            font-family: inherit;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            margin-top: 12px;
            background: #1d4ed8;
            color: #fff;
            font-weight: 900;
            cursor: pointer;
        }

        .template {
            border-top: 1px solid #eef2f7;
            padding: 14px 0;
        }

        .template:first-of-type {
            border-top: 0;
        }

        .template h3 {
            margin: 0 0 6px;
            color: #0f172a;
        }

        pre {
            white-space: pre-wrap;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px;
            color: #334155;
        }

        a {
            color: #dc2626;
            font-weight: 900;
            text-decoration: none;
        }

        .medicine-row {
            display: grid;
            grid-template-columns: minmax(170px, 1.4fr) repeat(4, minmax(120px, 1fr)) minmax(130px, 1fr) auto;
            gap: 8px;
            align-items: center;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #e5edf5;
            border-radius: 12px;
            background: #f8fafc;
        }

        .subtle {
            color: #64748b;
            font-size: 14px;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .add-row-btn,
        .remove-row-btn {
            width: auto;
        }

        .add-row-btn {
            background: #0f766e;
        }

        .remove-row-btn {
            background: #dc2626;
        }

        @media (max-width: 860px) {
            .page {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {

            .medicine-row,
            .mini-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="card">
            <h1>قالب علاج جديد</h1>
            <form method="post">
                <label>اسم القالب</label>
                <input name="template_name" required>
                <label>التشخيص</label>
                <textarea name="diagnosis"></textarea>
                <label>أدوية القالب</label>
                <div class="subtle">يمكنك إدخال أكثر من دواء مع الجرعة وعدد المرات ومدة العلاج، وسيتم تطبيقها تلقائيًا عند اختيار القالب داخل صفحة الوصفة.</div>
                <datalist id="frequency-options">
                    <?php foreach ($frequency_options as $option): ?>
                        <option value="<?= h($option) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <datalist id="duration-options">
                    <?php foreach ($duration_options as $option): ?>
                        <option value="<?= h($option) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <div id="template-medicines-container">
                    <div class="medicine-row">
                        <select name="medicine_id[]">
                            <?= templateMedicineOptions($medicines) ?>
                        </select>
                        <input type="text" name="dose[]" placeholder="الجرعة">
                        <input type="text" name="frequency[]" list="frequency-options" placeholder="عدد مرات الاستعمال">
                        <input type="text" name="duration[]" list="duration-options" placeholder="مدة العلاج">
                        <select name="eye[]">
                            <option value="">العين</option>
                            <option value="right">العين اليمنى</option>
                            <option value="left">العين اليسرى</option>
                            <option value="both">العينين</option>
                        </select>
                        <input type="text" name="instructions[]" placeholder="ملاحظات">
                        <button type="button" class="remove-row-btn" onclick="removeTemplateRow(this)">حذف</button>
                    </div>
                </div>
                <button type="button" class="add-row-btn" onclick="addTemplateRow()">إضافة دواء للقالب</button>
                <div class="mini-grid">
                    <div>
                        <label>بعد كم يوم تكون المراجعة</label>
                        <input type="number" name="followup_after_days" min="0" placeholder="مثال: 7">
                    </div>
                    <div>
                        <label>سبب المراجعة الافتراضي</label>
                        <input name="followup_reason" placeholder="مثال: فحص الاستجابة للعلاج">
                    </div>
                </div>
                <label>ملاحظات المراجعة الافتراضية</label>
                <textarea name="followup_note" placeholder="مثال: قياس ضغط العين مع إعادة التقييم"></textarea>
                <label>ملاحظات</label>
                <textarea name="notes"></textarea>
                <button type="submit">حفظ القالب</button>
            </form>
        </section>
        <section class="card">
            <h2>القوالب المحفوظة</h2>
            <?php if (!$templates || mysqli_num_rows($templates) === 0): ?>
                <p>لا توجد قوالب محفوظة.</p>
            <?php endif; ?>
            <?php while ($row = $templates ? mysqli_fetch_assoc($templates) : null): ?>
                <article class="template">
                    <h3><?= h($row['template_name']) ?></h3>
                    <?php if (!empty($row['diagnosis'])): ?><strong>التشخيص</strong>
                        <pre><?= h($row['diagnosis']) ?></pre><?php endif; ?>
                    <?php if (!empty($row['medicines_text'])): ?><strong>الأدوية</strong>
                        <pre><?= h($row['medicines_text']) ?></pre><?php endif; ?>
                    <?php if (!empty($row['followup_after_days']) || !empty($row['followup_reason']) || !empty($row['followup_note'])): ?>
                        <strong>المراجعة الافتراضية</strong>
                        <pre><?php
                                $followupPreview = [];
                                if (!empty($row['followup_after_days'])) {
                                    $followupPreview[] = 'بعد ' . (int) $row['followup_after_days'] . ' يوم';
                                }
                                if (!empty($row['followup_reason'])) {
                                    $followupPreview[] = $row['followup_reason'];
                                }
                                if (!empty($row['followup_note'])) {
                                    $followupPreview[] = $row['followup_note'];
                                }
                                echo h(implode(' - ', $followupPreview));
                                ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($row['notes'])): ?><strong>ملاحظات</strong>
                        <pre><?= h($row['notes']) ?></pre><?php endif; ?>
                    <a href="treatment-templates.php?delete=<?= (int) $row['id'] ?>" onclick="return confirm('حذف القالب؟')">حذف</a>
                </article>
            <?php endwhile; ?>
        </section>
    </main>
    <template id="template-medicine-row-template">
        <div class="medicine-row">
            <select name="medicine_id[]">
                <?= templateMedicineOptions($medicines) ?>
            </select>
            <input type="text" name="dose[]" placeholder="الجرعة">
            <input type="text" name="frequency[]" list="frequency-options" placeholder="عدد مرات الاستعمال">
            <input type="text" name="duration[]" list="duration-options" placeholder="مدة العلاج">
            <select name="eye[]">
                <option value="">العين</option>
                <option value="right">العين اليمنى</option>
                <option value="left">العين اليسرى</option>
                <option value="both">العينين</option>
            </select>
            <input type="text" name="instructions[]" placeholder="ملاحظات">
            <button type="button" class="remove-row-btn" onclick="removeTemplateRow(this)">حذف</button>
        </div>
    </template>
    <script>
        function addTemplateRow() {
            const container = document.getElementById('template-medicines-container');
            const template = document.getElementById('template-medicine-row-template');
            container.appendChild(template.content.cloneNode(true));
        }

        function removeTemplateRow(button) {
            const rows = document.querySelectorAll('#template-medicines-container .medicine-row');
            if (rows.length > 1) {
                button.closest('.medicine-row').remove();
            }
        }
    </script>
</body>

</html>