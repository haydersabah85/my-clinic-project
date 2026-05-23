<?php
include 'config.php';
include 'auth.php';

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

$medicines = [];
$medicine_result = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines ORDER BY medicine_name ASC");
while ($medicine = mysqli_fetch_assoc($medicine_result)) {
    $medicines[] = $medicine;
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

            <label for="diagnosis">التشخيص</label>
            <textarea id="diagnosis" name="diagnosis" rows="3"><?= htmlspecialchars($prescription['diagnosis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <h2 class="section-title">الأدوية</h2>
            <div id="medicines-container">
                <?php foreach ($items as $item): ?>
                    <div class="medicine-row">
                        <select name="medicine_id[]" required>
                            <?= medicineOptions($medicines, $item['medicine_id']) ?>
                        </select>
                        <input type="text" name="frequency[]" placeholder="عدد المرات" value="<?= htmlspecialchars($item['frequency'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="dose[]" placeholder="الجرعة" value="<?= htmlspecialchars($item['dose'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="duration[]" placeholder="المدة" value="<?= htmlspecialchars($item['duration'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <select name="eye[]">
                            <option value="">العين</option>
                            <option value="right"<?= selected('right', $item['eye'] ?? '') ?>>العين اليمنى</option>
                            <option value="left"<?= selected('left', $item['eye'] ?? '') ?>>العين اليسرى</option>
                            <option value="both"<?= selected('both', $item['eye'] ?? '') ?>>العينين</option>
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
            <input type="text" name="frequency[]" placeholder="عدد المرات">
            <input type="text" name="dose[]" placeholder="الجرعة">
            <input type="text" name="duration[]" placeholder="المدة">
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
        function addMedicine() {
            const container = document.getElementById("medicines-container");
            const template = document.getElementById("medicine-row-template");
            container.appendChild(template.content.cloneNode(true));
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll(".medicine-row");
            if (rows.length > 1) {
                btn.closest(".medicine-row").remove();
            }
        }
    </script>
</body>

</html>
