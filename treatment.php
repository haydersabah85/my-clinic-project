<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$frequency_options = clinic_prescription_frequency_options();
$duration_options = clinic_prescription_duration_options();

function treatmentMedicineOptions(array $medicines, $selectedId = ''): string
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

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
} else {
    die("خطأ: لم يتم تحديد المريض");
}

$patient = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM add_patient WHERE id = '$patient_id'"));
if (!$patient) {
    die("خطأ: المريض غير موجود");
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


$previous_medicines = mysqli_query($con, "
SELECT 
    p.id as prescription_id,
    p.prescription_date,
    p.diagnosis,
    m.medicine_name,
    m.medicine_form,
    pi.dose,
    pi.frequency,
    pi.duration,
    pi.eye
FROM prescriptions p
JOIN prescription_items pi ON p.id = pi.prescription_id
JOIN medicines m ON pi.medicine_id = m.id
WHERE p.patient_id = $patient_id 
ORDER BY p.prescription_date DESC
LIMIT 20
");



?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>وصفة طبية</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/theme.js" defer></script>
    <link rel="stylesheet" href="assets/dark-mode.css">
</head>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 24px;
        font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
        direction: rtl;
        background:
            repeating-linear-gradient(45deg, rgba(39, 58, 115, 0.04) 0, rgba(39, 58, 115, 0.04) 8px, transparent 8px, transparent 18px),
            radial-gradient(circle at top right, rgba(218, 124, 70, 0.14), transparent 30%),
            radial-gradient(circle at top left, rgba(52, 88, 165, 0.14), transparent 26%),
            linear-gradient(180deg, #f6f0e6, #efe5d7);
        color: #25304a;
    }

    .page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .prescription-box,
    .previous-box {
        background: rgba(255, 250, 241, 0.96);
        border: 2px solid #d7c5aa;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 10px 10px 0 rgba(37, 48, 74, 0.12);

    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        margin-bottom: 22px;
        padding: 22px 24px;
        border-radius: 16px;
        border: 2px solid #24345e;
        background: linear-gradient(120deg, #273a73, #3357a4 55%, #da7c46);
        color: #ffffff;
        box-shadow: 10px 10px 0 rgba(37, 48, 74, 0.2);
    }

    .page-header h2 {
        margin: 0;
        font-size: 30px;
    }

    .page-header p {
        margin: 6px 0 0;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.86);
    }

    .top-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 10px;
    }

    a,
    button,
    input,
    select,
    textarea {
        font-family: inherit;
    }

    a {
        text-decoration: none;
    }

    .top-links a,
    .table-btn,
    .add-btn,
    .save-btn,
    .remove-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 2px solid rgba(255, 255, 255, 0.28);
        border-radius: 10px;
        padding: 10px 14px;
        color: #ffffff;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .add-btn {
        margin-left: 3px;
    }

    .top-links a {
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
    }

    .top-links a:hover,
    .table-btn:hover,
    .add-btn:hover,
    .save-btn:hover,
    .remove-btn:hover {
        transform: translateY(-2px);
    }

    label {
        display: block;
        margin: 8px;
        color: #31405f;
        font-weight: 800;
    }

    textarea,
    select,
    input {
        width: 100%;
        border: 2px solid #d6c6ad;
        margin: 6px 0 12px;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 15px;
        background: #fffdf9;
        color: #25304a;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    textarea:focus,
    select:focus,
    input:focus {
        border-color: #2d7f7a;
        box-shadow: 0 0 0 4px rgba(45, 127, 122, 0.16);
    }

    hr {
        border: 0;
        border-top: 1px dashed #c8b59a;
        margin: 22px 0;
    }

    .section-title {
        margin: 5px 14px;
        color: #273a73;
        font-size: 21px;
    }

    .followup-box {
        margin-top: 10px;
        padding: 16px;
        border: 2px solid #dbcab0;
        border-radius: 14px;
        background: #fffaf2;
        box-shadow: 6px 6px 0 rgba(37, 48, 74, 0.08);
    }

    .followup-note {
        margin: 4px 14px 14px;
        color: #5a6478;
        font-weight: 700;
        line-height: 1.7;
    }

    .followup-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(180px, 1fr));
        gap: 12px;
        align-items: start;
    }

    .template-box {
        margin-bottom: 16px;
        padding: 16px;
        border: 2px dashed #cfba98;
        border-radius: 14px;
        background: rgba(255, 247, 235, 0.9);
    }

    .template-actions {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 10px;
        align-items: end;
    }

    .template-help {
        margin: 8px 0 0;
        color: #5a6478;
        font-weight: 700;
    }

    #medicines-container {
        counter-reset: medRow;
    }

    .medicine-row {
        counter-increment: medRow;
        position: relative;
        display: grid;
        grid-template-columns: minmax(180px, 1.4fr) repeat(4, minmax(115px, 1fr)) minmax(140px, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 16px 12px 12px;
        margin-bottom: 10px;
        border: 2px solid #dbcab0;
        border-radius: 14px;
        background: #fffaf2;
        box-shadow: 6px 6px 0 rgba(37, 48, 74, 0.08);
    }

    .medicine-row::before {
        content: "دواء " counter(medRow);
        position: absolute;
        top: -12px;
        left: 12px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        color: #ffffff;
        background: linear-gradient(120deg, #2d7f7a, #3fa39d);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .add-btn {
        background: linear-gradient(120deg, #355ea6, #4b7bd0);
        box-shadow: 8px 8px 0 rgba(37, 48, 74, 0.14);
    }

    .save-btn {
        width: 100%;
        margin-top: 4px;
        padding: 14px;
        background: linear-gradient(120deg, #2f8d6b, #47af85);
        font-size: 17px;
        box-shadow: 8px 8px 0 rgba(37, 48, 74, 0.14);
    }

    .remove-btn {
        width: 42px;
        height: 42px;
        padding: 0;
        background: linear-gradient(120deg, #c24f4c, #da6a67);
        box-shadow: 6px 6px 0 rgba(37, 48, 74, 0.12);
    }

    .previous-box {
        margin-top: 24px;
        overflow: auto;
        direction: ltr;
    }

    .previous-box h3 {
        margin: 0 0 14px;
        color: #273a73;
    }

    .previous-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        font-size: 14px;
        direction: ltr;
    }

    .previous-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(120deg, #273a73, #3458a5);
        color: #fff;
        padding: 12px 10px;
        text-align: center;
        border-bottom: 2px solid #da7c46;
    }

    .previous-table td {
        border-bottom: 1px solid #d9ccb8;
        padding: 10px 8px;
        text-align: center;
    }

    .previous-table tr:nth-child(even) {
        background: #f8f2e9;
    }

    .previous-table tr:hover {
        background: #f0e4d1;
    }

    .table-btn {
        background: linear-gradient(120deg, #355ea6, #4b7bd0);
        padding: 7px 12px;
        font-size: 13px;
        border-color: rgba(255, 255, 255, 0.24);
        box-shadow: 6px 6px 0 rgba(37, 48, 74, 0.14);
    }

    .table-btn:hover {
        transform: translateY(-2px);
    }

    .prescription-box form {
        direction: ltr;
    }

    body[data-theme="dark"] .page-header {
        background: linear-gradient(120deg, #1d2b57, #3153a2 52%, #c96d3f);
        border-color: rgba(122, 155, 230, 0.3);
    }

    body[data-theme="dark"] .prescription-box,
    body[data-theme="dark"] .previous-box {
        background: rgba(23, 35, 59, 0.92);
        border-color: rgba(117, 145, 214, 0.32);
        box-shadow: 10px 10px 0 rgba(0, 0, 0, 0.28);
    }

    body[data-theme="dark"] .medicine-row {
        background: rgba(28, 42, 70, 0.92) !important;
        border-color: rgba(122, 155, 230, 0.24) !important;
        box-shadow: 6px 6px 0 rgba(0, 0, 0, 0.24);
    }

    body[data-theme="dark"] .followup-box {
        background: rgba(28, 42, 70, 0.92);
        border-color: rgba(122, 155, 230, 0.24);
        box-shadow: 6px 6px 0 rgba(0, 0, 0, 0.24);
    }

    body[data-theme="dark"] .template-box {
        background: rgba(28, 42, 70, 0.92);
        border-color: rgba(122, 155, 230, 0.24);
    }

    body[data-theme="dark"] .followup-note {
        color: #c7d5f2;
    }

    body[data-theme="dark"] .medicine-row::before {
        background: linear-gradient(120deg, #477ecf, #55a7d0);
    }

    body[data-theme="dark"] label {
        color: #c7d5f2;
    }

    body[data-theme="dark"] hr {
        border-top-color: rgba(164, 178, 210, 0.24);
    }

    body[data-theme="dark"] textarea,
    body[data-theme="dark"] select,
    body[data-theme="dark"] input {
        border-color: rgba(117, 145, 214, 0.28);
        background: rgba(20, 30, 52, 0.96);
        color: #e5edff;
    }

    body[data-theme="dark"] .section-title,
    body[data-theme="dark"] .previous-box h3 {
        color: #c7d5f2;
    }

    body[data-theme="dark"] .previous-table td {
        border-bottom-color: rgba(117, 145, 214, 0.22);
    }

    body[data-theme="dark"] .previous-table tr:nth-child(even) {
        background: rgba(35, 48, 76, 0.68);
    }

    body[data-theme="dark"] .previous-table tr:hover {
        background: rgba(88, 118, 182, 0.24);
    }

    @media (max-width: 900px) {
        body {
            padding: 12px;
        }

        .page-header {
            flex-direction: column;
            align-items: stretch;
        }

        .top-links {
            justify-content: stretch;
        }

        .top-links a {
            flex: 1;
        }

        .followup-grid {
            grid-template-columns: 1fr;
        }

        .medicine-row {
            grid-template-columns: 1fr;
        }

        .medicine-row::before {
            right: 8px;
        }

        .remove-btn {
            width: 100%;
        }
    }
</style>


<body>

    <div class="page">

        <div class="page-header">
            <div>
                <h2>إنشاء وصفة طبية</h2>
                <p><?php echo htmlspecialchars($patient['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="top-links">
                <a href="patient-file.php?id=<?php echo (int) $patient_id; ?>">ملف المريض</a>
                <a href="dashboard.php">الصفحة الرئيسية</a>
                <a href="common-medicines.php">الأدوية الأكثر استعمالًا</a>
            </div>
        </div>

        <div class="prescription-box">

            <form method="POST" action="save_prescription.php">

                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">

                <div class="template-box">
                    <label for="template_id">قالب علاج جاهز</label>
                    <div class="template-actions">
                        <select id="template_id">
                            <option value="">اختر قالبًا محفوظًا</option>
                            <?php foreach ($templates as $template): ?>
                                <option value="<?php echo (int) $template['id']; ?>"><?php echo h($template['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="add-btn" onclick="applySelectedTemplate()">تطبيق القالب</button>
                    </div>
                    <p class="template-help">سيتم تعبئة التشخيص والأدوية وموعد المراجعة الافتراضي من القالب، ويمكنك تعديلها قبل الحفظ.</p>
                </div>


                <label>التشخيص</label>
                <textarea id="diagnosis" name="diagnosis" rows="3" placeholder="اكتب التشخيص هنا..."></textarea>

                <div class="followup-box">
                    <h3 class="section-title">موعد المتابعة</h3>
                    <p class="followup-note">اختر نوع الموعد المناسب ثم أدخل التاريخ والسبب. سيتم حفظه في القائمة المناسبة سواء كانت مراجعة مجانية أو زيارة قادمة.</p>
                    <div class="followup-grid">
                        <div>
                            <label for="followup_type">نوع الموعد</label>
                            <select id="followup_type" name="followup_type">
                                <option value="review">موعد المراجعة القادمة</option>
                                <option value="next_visit">موعد الفحص القادم</option>
                            </select>
                        </div>
                        <div>
                            <label for="followup_date">تاريخ الموعد</label>
                            <input type="date" id="followup_date" name="followup_date" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div>
                            <label for="followup_reason">سبب الموعد</label>
                            <input type="text" id="followup_reason" name="followup_reason" placeholder="مثال: قياس الضغط أو تقييم التحسن">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label for="followup_note">ملاحظات للموعد</label>
                            <textarea id="followup_note" name="followup_note" rows="2" placeholder="تعليمات أو فحوص مطلوبة في الموعد القادم"></textarea>
                        </div>
                    </div>
                </div>

                <datalist id="frequency-options">
                    <?php foreach ($frequency_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <datalist id="duration-options">
                    <?php foreach ($duration_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <hr>

                <h3 class="section-title">الأدوية</h3>

                <div id="medicines-container">
                </div>

                <button type="button" class="add-btn" onclick="addMedicine()">+ إضافة دواء</button>

                <hr>

                <button type="submit" class="save-btn">💾 عرض الوصفة</button>


            </form>
        </div>

        <div class="previous-box">
            <h3>📂 الأدوية الموصوفة سابقاً</h3>

            <table class="previous-table">
                <tr>
                    <th>Date</th>
                    <th>Medicine</th>
                    <th>Frequency</th>
                    <th>Dose</th>
                    <th>Eye</th>
                    <th>Duration</th>
                    <th>Diagnosis</th>
                    <th>View</th>
                </tr>

                <?php

                if (mysqli_num_rows($previous_medicines) > 0) {
                    while ($row = mysqli_fetch_assoc($previous_medicines)) {


                ?>
                        <tr>
                            <td><?php echo $row['prescription_date'] ?? '-'; ?></td>
                            <td><?php echo $row['medicine_name'] . '  ' . $row['medicine_form']; ?></td>
                            <td><?php echo $row['frequency']; ?></td>
                            <td><?php echo $row['dose']; ?></td>
                            <td><?php echo $row['eye']; ?></td>
                            <td><?php echo $row['duration']; ?></td>
                            <td><?php echo $row['diagnosis']; ?></td>
                            <td>
                                <a class="table-btn" href="view_prescription.php?id=<?php echo $row['prescription_id']; ?>">عرض الوصفة</a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='8'>لا توجد وصفات سابقة</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

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
            let rows = document.querySelectorAll(".medicine-row");
            if (rows.length > 1) {
                btn.parentElement.remove();
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

        addMedicine();
    </script>

    <template id="medicine-row-template">
        <div class="medicine-row">
            <select name="medicine_id[]">
                <?= treatmentMedicineOptions($medicines) ?>
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
            <button type="button" class="remove-btn" onclick="removeRow(this)">✖</button>
        </div>
    </template>

</body>

</html>