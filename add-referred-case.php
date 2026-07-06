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

$today = date('Y-m-d');
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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إضافة حالة محولة</title>
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
        .notice {
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

        .btn.alt {
            background: var(--blue);
            border-color: var(--blue);
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
        }

        .notice.success {
            border-right: 5px solid #16a34a;
        }

        .notice.error {
            border-right: 5px solid var(--red);
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
                <span>مسار منفصل</span>
                <h1>إضافة حالة عملية محولة</h1>
            </div>
            <div class="actions">
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
                <a class="btn alt" href="referred-cases.php">قائمة الحالات المحولة</a>
                <button class="btn" type="button" id="themeToggle" aria-label="تبديل الوضع">◐</button>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= e($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="form-panel">
            <form action="add-referred-case2.php" method="post">
                <?= clinic_csrf_input() ?>

                <h2 class="section-title">بيانات المريض</h2>
                <div class="grid">
                    <div class="field">
                        <label for="patient_full_name">اسم المريض</label>
                        <input type="text" id="patient_full_name" name="patient_full_name" required>
                    </div>
                    <div class="field">
                        <label for="patient_age">العمر</label>
                        <input type="text" id="patient_age" name="patient_age" placeholder="مثال: 62">
                    </div>
                    <div class="field">
                        <label for="patient_phone">الهاتف</label>
                        <input type="text" id="patient_phone" name="patient_phone" dir="ltr" placeholder="07xxxxxxxxx">
                    </div>
                    <div class="field">
                        <label for="patient_city">المدينة</label>
                        <input type="text" id="patient_city" name="patient_city">
                    </div>
                </div>

                <h2 class="section-title">بيانات التحويل</h2>
                <div class="grid">
                    <div class="field">
                        <label for="referring_doctor_name">الطبيب المحول</label>
                        <input type="text" id="referring_doctor_name" name="referring_doctor_name" required>
                    </div>
                    <div class="field">
                        <label for="referring_doctor_clinic">العيادة / الجهة</label>
                        <input type="text" id="referring_doctor_clinic" name="referring_doctor_clinic">
                    </div>
                    <div class="field">
                        <label for="referring_doctor_phone">هاتف الطبيب</label>
                        <input type="text" id="referring_doctor_phone" name="referring_doctor_phone" dir="ltr">
                    </div>
                    <div class="field">
                        <label for="referral_date">تاريخ التحويل</label>
                        <input type="date" id="referral_date" name="referral_date" value="<?= e($today) ?>">
                    </div>
                </div>

                <h2 class="section-title">بيانات العملية والمتابعة</h2>
                <div class="grid">
                    <div class="field">
                        <label for="surgery_date">تاريخ العملية</label>
                        <input type="date" id="surgery_date" name="surgery_date" value="<?= e($today) ?>" required>
                    </div>
                    <div class="field">
                        <label for="surgery_type">نوع العملية</label>
                        <select id="surgery_type" name="surgery_type" required>
                            <option value="">اختر نوع العملية</option>
                            <?php foreach ($surgeryTypes as $type): ?>
                                <option value="<?= e($type) ?>"><?= e($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="eye">العين</label>
                        <select id="eye" name="eye">
                            <option value="">غير محدد</option>
                            <option value="OD">OD</option>
                            <option value="OS">OS</option>
                            <option value="OU">OU</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="surgeon_name">اسم الجرّاح</label>
                        <input type="text" id="surgeon_name" name="surgeon_name" value="<?= e($_SESSION['name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="anesthesia_type">نوع التخدير</label>
                        <input type="text" id="anesthesia_type" name="anesthesia_type">
                    </div>
                    <div class="field">
                        <label for="followup_destination">المتابعة بعد العملية</label>
                        <select id="followup_destination" name="followup_destination">
                            <option value="clinic">يُتابع في المركز</option>
                            <option value="referrer">يرجع للطبيب المُحيل</option>
                            <option value="unknown">غير محدد</option>
                        </select>
                    </div>
                    <div class="field wide">
                        <label for="materials_used">المواد المستخدمة</label>
                        <textarea id="materials_used" name="materials_used" placeholder="عدسة، غازات، أدوات خاصة..."></textarea>
                    </div>
                    <div class="field wide">
                        <label for="operation_notes">ملاحظات العملية</label>
                        <textarea id="operation_notes" name="operation_notes"></textarea>
                    </div>
                    <div class="field wide">
                        <label for="postop_instructions">تعليمات ما بعد العملية</label>
                        <textarea id="postop_instructions" name="postop_instructions"></textarea>
                    </div>
                    <div class="field wide">
                        <label for="followup_plan">خطة المتابعة</label>
                        <textarea id="followup_plan" name="followup_plan"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="primary" type="submit" name="save_referred_case">حفظ الحالة المحولة</button>
                    <a class="btn" href="referred-cases.php">العودة للقائمة</a>
                </div>
            </form>
        </section>
    </main>
</body>

</html>