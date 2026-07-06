<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
$flash = clinic_take_flash();

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$case = null;

if ($id > 0) {
    $stmt = mysqli_prepare($con, 'SELECT * FROM referred_surgery_cases WHERE id = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $case = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
    }
}

$surgeryTypes = clinic_get_surgery_types($con);
if (empty($surgeryTypes)) {
    $surgeryTypes = [
        'Phaco',
        'Vitrectomy',
        'Phaco and Vitrectomy',
        'SOR',
        'Phaco and SOR',
        'Squint',
        'ECCE',
        'ICCE',
        'Chalazion',
        'EUA',
        'Probing',
        'SMILE',
        'PRK',
        'AC Washout',
        'Secondary IOL',
        'IOL Exchange',
        'Pterygium with Graft',
        'Pterygium',
        'Anterior Vitrectomy',
    ];
}

$currentSurgeryType = trim((string) ($case['surgery_type'] ?? ''));
$hasCurrentInList = $currentSurgeryType !== '' && in_array($currentSurgeryType, $surgeryTypes, true);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تعديل حالة محولة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --text: #172033;
            --muted: #64748b;
            --border: #dbe7ef;
            --primary: #0f766e;
            --blue: #2563eb;
            --red: #dc2626;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            --radius: 12px;
        }

        body[data-theme="dark"],
        body.dark {
            --bg: #07111d;
            --panel: #101c2d;
            --panel-soft: #0c1625;
            --text: #e6edf5;
            --muted: #a8bdd1;
            --border: rgba(148, 163, 184, 0.2);
            --shadow: 0 20px 45px rgba(0, 0, 0, 0.32);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            width: min(1100px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 42px;
        }

        .topbar,
        .form-panel,
        .notice,
        .empty {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .title h1 {
            margin: 2px 0 0;
            font-size: 28px;
        }

        .title span {
            color: var(--muted);
            font-weight: 800;
            font-size: 12px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn,
        button {
            min-height: 40px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--panel-soft);
            color: var(--text);
            padding: 8px 12px;
            font: inherit;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.primary,
        button.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .form-panel {
            padding: 18px;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: 19px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.wide {
            grid-column: 1 / -1;
        }

        label {
            color: var(--text);
            font-size: 13px;
            font-weight: 900;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--panel-soft);
            color: var(--text);
            padding: 8px 10px;
            font: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .notice {
            margin-bottom: 14px;
            padding: 10px 12px;
            font-weight: 800;
            border-right: 5px solid var(--red);
        }

        .empty {
            padding: 24px;
            text-align: center;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        @media (max-width: 920px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .btn,
            button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="topbar">
            <div class="title">
                <span>تحديث البيانات</span>
                <h1>تعديل الحالة المحولة</h1>
            </div>
            <div class="actions">
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
                <a class="btn" href="referred-cases.php">قائمة الحالات المحولة</a>
                <button class="btn" type="button" id="themeToggle" aria-label="تبديل الوضع">◐</button>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="notice"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>

        <?php if (!$case): ?>
            <section class="empty">
                لم يتم العثور على الحالة المطلوبة.
                <div class="form-actions" style="justify-content:center; margin-top:12px;">
                    <a class="btn primary" href="referred-cases.php">العودة إلى قائمة الحالات</a>
                </div>
            </section>
        <?php else: ?>
            <section class="form-panel">
                <form action="edit-referred-case2.php" method="post">
                    <?= clinic_csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int) $case['id'] ?>">

                    <h2 class="section-title">بيانات المريض</h2>
                    <div class="grid">
                        <div class="field">
                            <label for="patient_full_name">اسم المريض</label>
                            <input type="text" id="patient_full_name" name="patient_full_name" value="<?= e($case['patient_full_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="patient_age">العمر</label>
                            <input type="text" id="patient_age" name="patient_age" value="<?= e($case['patient_age'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="patient_phone">الهاتف</label>
                            <input type="text" id="patient_phone" name="patient_phone" dir="ltr" value="<?= e($case['patient_phone'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="patient_city">المدينة</label>
                            <input type="text" id="patient_city" name="patient_city" value="<?= e($case['patient_city'] ?? '') ?>">
                        </div>
                    </div>

                    <h2 class="section-title">بيانات التحويل</h2>
                    <div class="grid">
                        <div class="field">
                            <label for="referring_doctor_name">الطبيب المحول</label>
                            <input type="text" id="referring_doctor_name" name="referring_doctor_name" value="<?= e($case['referring_doctor_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="referring_doctor_clinic">العيادة / الجهة</label>
                            <input type="text" id="referring_doctor_clinic" name="referring_doctor_clinic" value="<?= e($case['referring_doctor_clinic'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="referring_doctor_phone">هاتف الطبيب</label>
                            <input type="text" id="referring_doctor_phone" name="referring_doctor_phone" dir="ltr" value="<?= e($case['referring_doctor_phone'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="referral_date">تاريخ التحويل</label>
                            <input type="date" id="referral_date" name="referral_date" value="<?= e($case['referral_date'] ?? '') ?>">
                        </div>
                    </div>

                    <h2 class="section-title">بيانات العملية والمتابعة</h2>
                    <div class="grid">
                        <div class="field">
                            <label for="surgery_date">تاريخ العملية</label>
                            <input type="date" id="surgery_date" name="surgery_date" value="<?= e($case['surgery_date'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="surgery_type">نوع العملية</label>
                            <select id="surgery_type" name="surgery_type" required>
                                <option value="">اختر نوع العملية</option>
                                <?php if ($currentSurgeryType !== '' && !$hasCurrentInList): ?>
                                    <option value="<?= e($currentSurgeryType) ?>" selected><?= e($currentSurgeryType) ?> (حالي)</option>
                                <?php endif; ?>
                                <?php foreach ($surgeryTypes as $type): ?>
                                    <option value="<?= e($type) ?>" <?= $currentSurgeryType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="eye">العين</label>
                            <select id="eye" name="eye">
                                <option value="" <?= ($case['eye'] ?? '') === '' ? 'selected' : '' ?>>غير محدد</option>
                                <option value="OD" <?= ($case['eye'] ?? '') === 'OD' ? 'selected' : '' ?>>OD</option>
                                <option value="OS" <?= ($case['eye'] ?? '') === 'OS' ? 'selected' : '' ?>>OS</option>
                                <option value="OU" <?= ($case['eye'] ?? '') === 'OU' ? 'selected' : '' ?>>OU</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="surgeon_name">اسم الجرّاح</label>
                            <input type="text" id="surgeon_name" name="surgeon_name" value="<?= e($case['surgeon_name'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="anesthesia_type">نوع التخدير</label>
                            <input type="text" id="anesthesia_type" name="anesthesia_type" value="<?= e($case['anesthesia_type'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="followup_destination">المتابعة بعد العملية</label>
                            <select id="followup_destination" name="followup_destination">
                                <option value="clinic" <?= ($case['followup_destination'] ?? '') === 'clinic' ? 'selected' : '' ?>>يُتابع في المركز</option>
                                <option value="referrer" <?= ($case['followup_destination'] ?? '') === 'referrer' ? 'selected' : '' ?>>يرجع للطبيب المُحيل</option>
                                <option value="unknown" <?= ($case['followup_destination'] ?? '') === 'unknown' ? 'selected' : '' ?>>غير محدد</option>
                            </select>
                        </div>
                        <div class="field wide">
                            <label for="materials_used">المواد المستخدمة</label>
                            <textarea id="materials_used" name="materials_used"><?= e($case['materials_used'] ?? '') ?></textarea>
                        </div>
                        <div class="field wide">
                            <label for="operation_notes">ملاحظات العملية</label>
                            <textarea id="operation_notes" name="operation_notes"><?= e($case['operation_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="field wide">
                            <label for="postop_instructions">تعليمات ما بعد العملية</label>
                            <textarea id="postop_instructions" name="postop_instructions"><?= e($case['postop_instructions'] ?? '') ?></textarea>
                        </div>
                        <div class="field wide">
                            <label for="followup_plan">خطة المتابعة</label>
                            <textarea id="followup_plan" name="followup_plan"><?= e($case['followup_plan'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="primary" type="submit" name="update_referred_case">حفظ التعديلات</button>
                        <a class="btn" href="referred-cases.php">إلغاء</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>