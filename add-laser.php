<?php
include 'config.php';
include 'auth.php';

$patient = null;
$patientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$appointmentId = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
$appointmentDate = $_GET['appointment_date'] ?? '';
$defaultDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate) ? $appointmentDate : date('Y-m-d');

if ($patientId > 0) {
    $stmt = mysqli_prepare($con, "SELECT id, full_name, age, phone_no, address FROM add_patient WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $patientId);
    mysqli_stmt_execute($stmt);
    $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$laserTypes = clinic_get_laser_types($con);
if (empty($laserTypes)) {
    $laserTypes = [
        'PRP',
        'Retinopexy',
        'YAG',
        'Focal Laser',
        'PI',
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إضافة ليزر</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/dark-mode.css">
    <link rel="stylesheet" href="assets/clinic-ui.css">
    <script src="assets/theme.js" defer></script>
</head>

<style>
    :root {
        --bg: #f4f7fb;
        --panel: #ffffff;
        --panel-soft: #f8fafc;
        --text: #172033;
        --muted: #64748b;
        --border: #dbe7ef;
        --primary: #2563eb;
        --tone: #d97706;
        --tone-rgb: 217, 119, 6;
        --teal: #0f766e;
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
        min-height: 100vh;
        font-family: Tahoma, "Segoe UI", Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    .page {
        width: min(1120px, calc(100% - 32px));
        margin: 0 auto;
        padding: 22px 0 34px;
    }

    .topbar,
    .patient-card,
    .form-panel,
    .empty-panel {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }

    .title-block span,
    .patient-detail span,
    .section-label {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
    }

    .title-block h1 {
        margin: 3px 0 0;
        font-size: 28px;
        line-height: 1.25;
        font-weight: 900;
        color: var(--text);
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        align-items: center;
    }

    .btn,
    button {
        min-height: 40px;
        border: 1px solid var(--border);
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 8px 13px;
        background: var(--panel-soft);
        color: var(--text);
        font: inherit;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .btn.primary,
    button.primary {
        background: var(--tone);
        border-color: var(--tone);
        color: #ffffff;
    }

    .btn.success {
        background: var(--teal);
        border-color: var(--teal);
        color: #ffffff;
    }

    .theme-btn {
        width: 40px;
        padding: 0;
    }

    .patient-card {
        padding: 16px;
        margin-bottom: 16px;
        border-top: 4px solid var(--tone);
    }

    .patient-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(120px, 1fr));
        gap: 10px;
    }

    .patient-detail {
        background: var(--panel-soft);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 11px 12px;
    }

    .patient-detail strong {
        display: block;
        margin-top: 4px;
        color: var(--text);
        font-size: 15px;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .form-panel {
        padding: 18px;
    }

    .form-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        padding-bottom: 14px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border);
    }

    .form-head h2 {
        margin: 3px 0 0;
        font-size: 22px;
        color: var(--text);
    }

    form {
        margin: 0;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
    }

    .field {
        display: grid;
        gap: 7px;
    }

    .field.wide {
        grid-column: 1 / -1;
    }

    label {
        color: var(--text);
        font-size: 13px;
        font-weight: 900;
    }

    select,
    input[type="date"],
    textarea {
        width: 100%;
        min-height: 42px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel-soft);
        color: var(--text);
        padding: 8px 11px;
        font: inherit;
        font-size: 14px;
        font-weight: 700;
        outline: none;
    }

    select:focus,
    input[type="date"]:focus,
    textarea:focus {
        border-color: var(--tone);
        box-shadow: 0 0 0 4px rgba(var(--tone-rgb), .14);
    }

    textarea {
        min-height: 120px;
        resize: vertical;
        direction: ltr;
        text-align: left;
        line-height: 1.6;
    }

    .form-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 10px;
        margin-top: 16px;
    }

    .empty-panel {
        padding: 26px;
        text-align: center;
        color: var(--muted);
        font-weight: 900;
    }

    body[data-theme="dark"] .btn.primary,
    body.dark .btn.primary,
    body[data-theme="dark"] button.primary,
    body.dark button.primary {
        background: var(--tone) !important;
        border-color: var(--tone) !important;
        color: #ffffff !important;
    }

    @media (max-width: 900px) {

        .topbar,
        .form-head {
            display: grid;
        }

        .patient-grid,
        .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 620px) {
        .page {
            width: min(100% - 20px, 1120px);
            padding-top: 12px;
        }

        .patient-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }

        .title-block h1 {
            font-size: 23px;
        }

        .btn,
        button {
            width: 100%;
        }

        .theme-btn {
            width: 40px;
        }
    }
</style>

<body class="clinic-polished">
    <main class="page">
        <header class="topbar">
            <div class="title-block">
                <span>إضافة إجراء</span>
                <h1>إضافة معلومات الليزر</h1>
            </div>
            <nav class="actions" aria-label="روابط الصفحة">
                <a class="btn" href="main.php">بيانات المرضى</a>
                <a class="btn success" href="operation-by-date.php">مواعيد العمليات</a>
                <button class="btn theme-btn" type="button" id="themeToggle" aria-label="تبديل الوضع">◐</button>
            </nav>
        </header>

        <?php if (!$patient): ?>
            <section class="empty-panel">
                لم يتم العثور على المريض المطلوب.
                <div class="form-actions">
                    <a class="btn primary" href="main.php">العودة إلى بيانات المرضى</a>
                </div>
            </section>
        <?php else: ?>
            <section class="patient-card" aria-label="بيانات المريض">
                <div class="patient-grid">
                    <div class="patient-detail"><span>ID</span><strong><?= e($patient['id']) ?></strong></div>
                    <div class="patient-detail"><span>الاسم</span><strong><?= e($patient['full_name']) ?></strong></div>
                    <div class="patient-detail"><span>العمر</span><strong><?= e($patient['age'] ?: '-') ?></strong></div>
                    <div class="patient-detail"><span>الهاتف</span><strong dir="ltr"><?= e($patient['phone_no'] ?: '-') ?></strong></div>
                    <div class="patient-detail"><span>العنوان</span><strong><?= e($patient['address'] ?: '-') ?></strong></div>
                </div>
                <div class="form-actions">
                    <a class="btn" href="edit-patient.php?id_edit=<?= (int) $patient['id'] ?>">تعديل البيانات</a>
                    <a class="btn" href="patient-file.php?id=<?= (int) $patient['id'] ?>">ملف المريض</a>
                </div>
            </section>

            <section class="form-panel">
                <div class="form-head">
                    <div>
                        <span class="section-label">نموذج الليزر</span>
                        <h2>تفاصيل جلسة الليزر</h2>
                    </div>
                    <a class="btn" href="add-injection.php?id=<?= (int) $patient['id'] ?>">إضافة حقن لنفس المريض</a>
                </div>

                <form action="add-laser2.php" method="POST">
                    <input type="hidden" name="id" value="<?= (int) $patient['id'] ?>">
                    <input type="hidden" name="appointment_id" value="<?= (int) $appointmentId ?>">
                    <input type="hidden" name="appointment_date" value="<?= e($appointmentDate) ?>">

                    <div class="form-grid">
                        <div class="field">
                            <label for="eye">العين</label>
                            <select name="eye" id="eye" required>
                                <option value="">اختر العين</option>
                                <option value="OD">OD</option>
                                <option value="OS">OS</option>
                                <option value="OU">OU</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="laser_type">نوع الليزر</label>
                            <select name="laser_type" id="laser_type" required>
                                <option value="">اختر نوع الليزر</option>
                                <?php foreach ($laserTypes as $type): ?>
                                    <option value="<?= e($type) ?>"><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="date">التاريخ</label>
                            <input type="date" required name="date" id="date" value="<?= e($defaultDate) ?>">
                        </div>

                        <div class="field wide">
                            <label for="notes">الملاحظات</label>
                            <textarea name="notes" id="notes" placeholder="Extra notes..."></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="primary" id="laser_btn" name="laser_btn" type="submit">حفظ الليزر</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>
