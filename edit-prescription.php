<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$frequency_options = clinic_prescription_frequency_options();
$duration_options = clinic_prescription_duration_options();

$prescription_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($prescription_id <= 0) {
    die("خطأ: لم يتم تحديد الوصفة");
}

$prescription_stmt = mysqli_prepare($con, "
    SELECT p.*, pa.full_name AS patient_name, pa.age, pa.phone_no
    FROM prescriptions p
    JOIN add_patient pa ON p.patient_id = pa.id
    WHERE p.id = ?
");
mysqli_stmt_bind_param($prescription_stmt, "i", $prescription_id);
mysqli_stmt_execute($prescription_stmt);
$prescription = mysqli_fetch_assoc(mysqli_stmt_get_result($prescription_stmt));

if (!$prescription) {
    die("خطأ: الوصفة غير موجودة");
}

$linked_followup = [
    'followup_date' => $prescription['next_followup_date'] ?? '',
    'followup_reason' => $prescription['next_followup_reason'] ?? '',
    'note' => $prescription['next_followup_note'] ?? '',
    'followup_type' => 'review',
];

if (!empty($prescription['followup_id'])) {
    $followup_stmt = mysqli_prepare($con, "
        SELECT followup_date, followup_reason, note, followup_type
        FROM followups
        WHERE id = ? AND patient_id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($followup_stmt, "ii", $prescription['followup_id'], $prescription['patient_id']);
    mysqli_stmt_execute($followup_stmt);
    $followup_row = mysqli_fetch_assoc(mysqli_stmt_get_result($followup_stmt));
    if ($followup_row) {
        $linked_followup = $followup_row;
    }
}

$medicines = [];
$medicine_result = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines ORDER BY medicine_name ASC");
while ($medicine = mysqli_fetch_assoc($medicine_result)) {
    $medicines[] = $medicine;
}

$templates = [];
$template_result = mysqli_query($con, "
    SELECT id, template_name, diagnosis, payload_json, followup_after_days, followup_reason, followup_note
    FROM treatment_templates
    ORDER BY template_name ASC
");
while ($template = mysqli_fetch_assoc($template_result)) {
    $payload = json_decode((string) ($template['payload_json'] ?? ''), true);
    $templates[] = [
        'id' => (int) $template['id'],
        'name' => $template['template_name'],
        'diagnosis' => $template['diagnosis'] ?? '',
        'items' => is_array($payload['items'] ?? null) ? array_values($payload['items']) : [],
        'followup_after_days' => $payload['followup_after_days'] ?? $template['followup_after_days'],
        'followup_reason' => $payload['followup_reason'] ?? ($template['followup_reason'] ?? ''),
        'followup_note' => $payload['followup_note'] ?? ($template['followup_note'] ?? ''),
    ];
}

$items = [];
$items_stmt = mysqli_prepare($con, "
    SELECT id, medicine_id, dose, frequency, duration, eye, instructions
    FROM prescription_items
    WHERE prescription_id = ?
    ORDER BY id ASC
");
mysqli_stmt_bind_param($items_stmt, "i", $prescription_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

if (empty($items)) {
    $items[] = [
        'medicine_id' => '',
        'frequency' => '',
        'dose' => '',
        'duration' => '',
        'eye' => '',
        'instructions' => '',
    ];
}

function medicineOptions(array $medicines, $selected_id = ''): string
{
    $html = '<option value="">اختر دواء</option>';
    foreach ($medicines as $medicine) {
        $id = (string) $medicine['id'];
        $selected = ((string) $selected_id === $id) ? ' selected' : '';
        $name = htmlspecialchars($medicine['medicine_name'] . ' ' . $medicine['medicine_form'], ENT_QUOTES, 'UTF-8');
        $html .= "<option value=\"{$id}\"{$selected}>{$name}</option>";
    }
    return $html;
}

function selected($value, $current): string
{
    return ((string) $value === (string) $current) ? ' selected' : '';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الوصفة</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 34%),
                #f3f7fb;
            color: #1f2937;
        }

        .page {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
            padding: 20px 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
            color: #fff;
            box-shadow: 0 14px 32px rgba(37, 99, 235, 0.2);
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
        }

        .header p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.86);
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        a,
        button {
            font-family: inherit;
        }

        .link-btn,
        .save-btn,
        .add-btn,
        .remove-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 0;
            border-radius: 12px;
            padding: 10px 14px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .link-btn {
            background: rgba(255, 255, 255, 0.16);
        }

        .link-btn:hover,
        .save-btn:hover,
        .add-btn:hover,
        .remove-btn:hover {
            transform: translateY(-2px);
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5edf5;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .info-box {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5edf5;
        }

        .info-box span {
            display: block;
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
        }

        .info-box strong {
            display: block;
            margin-top: 4px;
            color: #1d4ed8;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 800;
        }

        textarea,
        select,
        input {
            width: 100%;
            border: 1px solid #d7e0ea;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 15px;
            background: #fff;
            color: #1f2937;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        textarea {
            font-size: 20px;
        }

        textarea:focus,
        select:focus,
        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .section-title {
            margin: 22px 0 12px;
            color: #1d4ed8;
            font-size: 20px;
        }

        .medicine-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) repeat(4, minmax(120px, 1fr)) minmax(140px, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e5edf5;
            border-radius: 14px;
            background: #f8fafc;
        }

        .add-btn {
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            margin-top: 6px;
        }

        .save-btn {
            width: 100%;
            margin-top: 18px;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            font-size: 17px;
        }

        .followup-card {
            margin-top: 18px;
            padding: 16px;
            border: 1px solid #d9e5f2;
            border-radius: 16px;
            background: #f8fafc;
        }

        .followup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .followup-hint {
            margin: 0 0 12px;
            color: #64748b;
            font-weight: 700;
        }

        .template-box {
            margin-bottom: 18px;
            padding: 16px;
            border: 1px dashed #bfd1ea;
            border-radius: 16px;
            background: #f8fbff;
        }

        .template-actions {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) auto;
            gap: 10px;
            align-items: end;
        }

        .template-help {
            margin: 10px 0 0;
            color: #64748b;
            font-weight: 700;
        }

        .remove-btn {
            width: 42px;
            height: 42px;
            padding: 0;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        body[data-theme="dark"] .header {
            background: linear-gradient(135deg, #0f2d5c, #155e9f, #0f766e);
            border: 1px solid rgba(147, 197, 253, 0.2);
        }

        body[data-theme="dark"] .info-box,
        body[data-theme="dark"] .medicine-row {
            background: rgba(15, 23, 42, 0.72) !important;
            border-color: rgba(147, 197, 253, 0.14) !important;
        }

        body[data-theme="dark"] .info-box strong {
            color: #93c5fd;
        }

        @media (max-width: 900px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .medicine-row {
                grid-template-columns: 1fr;
            }

            .remove-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div>
                <h1>تعديل الوصفة الطبية</h1>
                <p><?= htmlspecialchars($prescription['patient_name'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="header-actions">
                <a class="link-btn" href="view_prescription.php?id=<?= $prescription_id ?>">عرض الوصفة</a>
                <a class="link-btn" href="patient-file.php?id=<?= (int) $prescription['patient_id'] ?>">ملف المريض</a>
            </div>
        </div>

        <form class="card" method="POST" action="update-prescription.php">
            <input type="hidden" name="prescription_id" value="<?= $prescription_id ?>">
            <input type="hidden" name="patient_id" value="<?= (int) $prescription['patient_id'] ?>">

            <div class="patient-grid">
                <div class="info-box">
                    <span>اسم المريض</span>
                    <strong><?= htmlspecialchars($prescription['patient_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="info-box">
                    <span>العمر</span>
                    <strong><?= htmlspecialchars($prescription['age'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="info-box">
                    <span>رقم الهاتف</span>
                    <strong><?= htmlspecialchars($prescription['phone_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="info-box">
                    <span>تاريخ الوصفة</span>
                    <strong><?= htmlspecialchars($prescription['prescription_date'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <div class="template-box">
                <label for="template_id">قالب علاج جاهز</label>
                <div class="template-actions">
                    <select id="template_id">
                        <option value="">اختر قالبًا محفوظًا</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?= (int) $template['id'] ?>"><?= htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="add-btn" onclick="applySelectedTemplate()">تطبيق القالب</button>
                </div>
                <p class="template-help">عند تطبيق القالب سيتم استبدال الأدوية الحالية بمحتوى القالب، مع تعبئة التشخيص وموعد المراجعة الافتراضي.</p>
            </div>

            <label for="diagnosis">التشخيص</label>
            <textarea id="diagnosis" name="diagnosis" rows="3"><?= htmlspecialchars($prescription['diagnosis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <div class="followup-card">
                <h2 class="section-title" style="margin-top:0;">موعد المتابعة</h2>
                <p class="followup-hint">اختر نوع الموعد ثم أدخل التاريخ والسبب؛ سيتم حفظه في القائمة المناسبة.</p>
                <div class="followup-grid">
                    <div>
                        <label for="followup_type">نوع الموعد</label>
                        <select id="followup_type" name="followup_type">
                            <option value="review" <?= selected('review', $linked_followup['followup_type'] ?? 'review') ?>>موعد المراجعة القادمة</option>
                            <option value="next_visit" <?= selected('next_visit', $linked_followup['followup_type'] ?? 'review') ?>>موعد الفحص القادم</option>
                        </select>
                    </div>
                    <div>
                        <label for="followup_date">تاريخ الموعد</label>
                        <input type="date" id="followup_date" name="followup_date" value="<?= htmlspecialchars($linked_followup['followup_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div>
                        <label for="followup_reason">سبب الموعد</label>
                        <input type="text" id="followup_reason" name="followup_reason" placeholder="مثال: تقييم الاستجابة للعلاج" value="<?= htmlspecialchars($linked_followup['followup_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label for="followup_note">ملاحظات للموعد</label>
                        <textarea id="followup_note" name="followup_note" rows="2" placeholder="فحوص أو تعليمات للموعد القادم"><?= htmlspecialchars($linked_followup['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <datalist id="frequency-options">
                <?php foreach ($frequency_options as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <datalist id="duration-options">
                <?php foreach ($duration_options as $option): ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <h2 class="section-title">الأدوية</h2>
            <div id="medicines-container">
                <?php foreach ($items as $item): ?>
                    <div class="medicine-row">
                        <select name="medicine_id[]" required>
                            <?= medicineOptions($medicines, $item['medicine_id']) ?>
                        </select>
                        <input type="text" name="frequency[]" list="frequency-options" placeholder="عدد مرات الاستعمال" value="<?= htmlspecialchars($item['frequency'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="dose[]" placeholder="الجرعة" value="<?= htmlspecialchars($item['dose'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="duration[]" list="duration-options" placeholder="مدة العلاج" value="<?= htmlspecialchars($item['duration'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <select name="eye[]">
                            <option value="">العين</option>
                            <option value="right" <?= selected('right', $item['eye'] ?? '') ?>>العين اليمنى</option>
                            <option value="left" <?= selected('left', $item['eye'] ?? '') ?>>العين اليسرى</option>
                            <option value="both" <?= selected('both', $item['eye'] ?? '') ?>>العينين</option>
                        </select>
                        <input type="text" name="instructions[]" placeholder="ملاحظات" value="<?= htmlspecialchars($item['instructions'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="add-btn" onclick="addMedicine()">+ إضافة دواء</button>
            <button type="submit" class="save-btn">💾 حفظ التعديلات</button>
        </form>
    </div>

    <template id="medicine-row-template">
        <div class="medicine-row">
            <select name="medicine_id[]" required>
                <?= medicineOptions($medicines) ?>
            </select>
            <input type="text" name="frequency[]" list="frequency-options" placeholder="عدد مرات الاستعمال">
            <input type="text" name="dose[]" placeholder="الجرعة">
            <input type="text" name="duration[]" list="duration-options" placeholder="مدة العلاج">
            <select name="eye[]">
                <option value="">العين</option>
                <option value="right">العين اليمنى</option>
                <option value="left">العين اليسرى</option>
                <option value="both">العينين</option>
            </select>
            <input type="text" name="instructions[]" placeholder="ملاحظات">
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
        </div>
    </template>

    <script>
        const treatmentTemplates = <?php echo json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        function buildMedicineRow(item = {}) {
            const template = document.getElementById("medicine-row-template");
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.medicine-row');
            row.querySelector('[name="medicine_id[]"]').value = item.medicine_id || '';
            row.querySelector('[name="frequency[]"]').value = item.frequency || '';
            row.querySelector('[name="dose[]"]').value = item.dose || '';
            row.querySelector('[name="duration[]"]').value = item.duration || '';
            row.querySelector('[name="eye[]"]').value = item.eye || '';
            row.querySelector('[name="instructions[]"]').value = item.instructions || '';
            return fragment;
        }

        function addMedicine() {
            const container = document.getElementById("medicines-container");
            container.appendChild(buildMedicineRow());
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll(".medicine-row");
            if (rows.length > 1) {
                btn.closest(".medicine-row").remove();
            }
        }

        function applySelectedTemplate() {
            const selector = document.getElementById('template_id');
            const template = treatmentTemplates.find(item => String(item.id) === selector.value);
            if (!template) {
                return;
            }

            document.getElementById('diagnosis').value = template.diagnosis || '';
            document.getElementById('followup_reason').value = template.followup_reason || '';
            document.getElementById('followup_note').value = template.followup_note || '';

            if (template.followup_after_days !== null && template.followup_after_days !== '' && !Number.isNaN(Number(template.followup_after_days))) {
                const nextDate = new Date();
                nextDate.setDate(nextDate.getDate() + Number(template.followup_after_days));
                document.getElementById('followup_date').value = nextDate.toISOString().split('T')[0];
            }

            const container = document.getElementById('medicines-container');
            container.innerHTML = '';
            if (Array.isArray(template.items) && template.items.length > 0) {
                template.items.forEach(item => container.appendChild(buildMedicineRow(item)));
            } else {
                addMedicine();
            }
        }
    </script>
</body>

</html>