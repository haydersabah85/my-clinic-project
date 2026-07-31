<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_followup_type_support($con);

$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = trim($_GET['q'] ?? '');
$patient = null;
$patients = null;
$history = null;
$today = date('Y-m-d');

if ($patient_id > 0) {
    $stmt = mysqli_prepare($con, "SELECT id, full_name, age, phone_no, notes FROM add_patient WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($patient) {
        $history_stmt = mysqli_prepare($con, "
            SELECT followup_date, followup_reason, note, status, followup_type
            FROM followups
            WHERE patient_id = ?
            ORDER BY followup_date DESC
            LIMIT 8
        ");
        mysqli_stmt_bind_param($history_stmt, "i", $patient_id);
        mysqli_stmt_execute($history_stmt);
        $history = mysqli_stmt_get_result($history_stmt);
    }
} elseif ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($con, "
        SELECT id, full_name, age, phone_no, notes
        FROM add_patient
        WHERE full_name LIKE ? OR phone_no LIKE ? OR id LIKE ?
        ORDER BY full_name ASC
        LIMIT 30
    ");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $patients = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعطاء موعد مراجعة</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <link rel="stylesheet" href="assets/clinic-ui.css">
    <script src="assets/theme.js" defer></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 22px;
            font-family: "Cairo", Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .topbar,
        .panel,
        .patient-row {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 18px;
            margin-bottom: 16px;
        }

        h1 {
            margin: 0;
            font-size: 26px;
            color: #0f5790;
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        a,
        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-family: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .link {
            background: #e8f2ff;
            color: #0f5790;
        }

        .primary {
            background: #0f766e;
            color: #fff;
        }

        .secondary {
            background: #2563eb;
            color: #fff;
        }

        .danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .panel {
            padding: 18px;
        }

        .panel h2 {
            margin: 0 0 14px;
            font-size: 20px;
            color: #174760;
        }

        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 800;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #cfdceb;
            border-radius: 9px;
            padding: 11px 12px;
            font-family: inherit;
            font-size: 15px;
            background: #fff;
        }

        textarea {
            min-height: 105px;
            resize: vertical;
        }

        .patient-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .muted {
            color: #64748b;
            font-size: 14px;
        }

        .selected {
            display: grid;
            gap: 8px;
            padding: 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .history-item {
            border-bottom: 1px solid #e5edf5;
            padding: 10px 0;
            line-height: 1.8;
        }

        .empty {
            padding: 14px;
            color: #64748b;
            background: #f8fafc;
            border-radius: 10px;
        }

        body[data-theme="dark"] {
            background: #07111d;
            color: #e6edf5;
        }

        body[data-theme="dark"] .topbar,
        body[data-theme="dark"] .panel,
        body[data-theme="dark"] .patient-row {
            background: #0f1b2a;
            border-color: rgba(148, 163, 184, .18);
        }

        body[data-theme="dark"] input,
        body[data-theme="dark"] textarea,
        body[data-theme="dark"] .selected,
        body[data-theme="dark"] .empty {
            background: #0b1220;
            color: #e6edf5;
            border-color: rgba(148, 163, 184, .25);
        }

        @media (max-width: 850px) {
            body {
                padding: 12px;
            }

            .topbar,
            .patient-row {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .links a,
            .patient-row a,
            button {
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>

<body class="clinic-polished">
    <div class="page">
        <div class="panel" style="margin-bottom:16px; border:1px solid #bfdbfe; background:linear-gradient(135deg, #f0f9ff, #eff6ff);">
            <h2 style="margin:0 0 8px; color:#1d4ed8;">إرشادات سريعة</h2>
            <div style="display:grid; gap:6px; color:#334155; font-weight:700;">
                <div>• المراجعة المجانية: مناسبة لزيارة سريعة أو متابعة قصيرة.</div>
                <div>• إذا كان الموعد يحتاج إلى متابعة مدفوعة أو تكرار بعد فترة أطول، استخدم صفحة الزيارة القادمة.</div>
                <div>• هذه الأنواع ستظهر بوضوح في صفحة المتابعة لتسهيل العمل اليومي.</div>
            </div>
        </div>

        <div class="topbar">
            <div>
                <h1>إعطاء موعد مراجعة مجانية</h1>
                <div class="muted">هذا النوع مخصص لمراجعة قصيرة مجانية ومتابعة قريبة للحالة، بدون رسوم.</div>
            </div>
            <div class="links">
                <a class="link" href="dashboard.php">لوحة التحكم</a>
                <a class="link" href="followups.php">قائمة المراجعات</a>
                <a class="link" href="main.php">بيانات المرضى</a>
            </div>
        </div>

        <div class="grid">
            <section class="panel">
                <h2>اختيار المريض</h2>
                <form method="GET" action="followup-appointment.php">
                    <label for="q">بحث بالاسم أو الهاتف أو الرقم</label>
                    <input id="q" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="اكتب اسم المريض أو رقم الهاتف">
                    <button class="secondary" type="submit">بحث</button>
                </form>

                <?php if ($patient): ?>
                    <div class="selected">
                        <strong><?= htmlspecialchars($patient['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="muted">العمر: <?= htmlspecialchars($patient['age'] ?? '-', ENT_QUOTES, 'UTF-8') ?> | الهاتف: <?= htmlspecialchars($patient['phone_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="links">
                            <a class="primary" href="patient-data.php?id=<?= $patient['id'] ?>">بيانات المريض</a>
                            <a class="link" href="patient-file.php?id=<?= $patient['id'] ?>">ملف المريض</a>
                        </div>
                    </div>
                <?php elseif ($patients && mysqli_num_rows($patients) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($patients)): ?>
                        <div class="patient-row">
                            <div>
                                <strong><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <div class="muted">#<?= $row['id'] ?> | <?= htmlspecialchars($row['phone_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($row['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="links">
                                <a class="primary" href="followup-appointment.php?id=<?= $row['id'] ?>">اختيار</a>
                                <a class="link" href="patient-data.php?id=<?= $row['id'] ?>">بيانات المريض</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php elseif ($search !== ''): ?>
                    <div class="empty">لا توجد نتائج مطابقة.</div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>بيانات الموعد</h2>
                <?php if ($patient): ?>
                    <form method="POST" action="save_followup.php">
                        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                        <input type="hidden" name="return_to" value="appointment-page">

                        <label for="followup_date">تاريخ المراجعة</label>
                        <input type="date" id="followup_date" name="followup_date" min="<?= $today ?>" required>

                        <div class="empty" style="margin-bottom:12px; border:1px solid #dbeafe; background:#eff6ff; color:#1d4ed8;">نوع الموعد: مراجعة مجانية</div>
                        <input type="hidden" name="followup_type" value="review">

                        <label for="followup_reason">سبب المراجعة</label>
                        <input type="text" id="followup_reason" name="followup_reason" placeholder="مثال: مراجعة ضغط العين" required>

                        <label for="note">ملاحظات إضافية</label>
                        <textarea id="note" name="note" placeholder="أي تفاصيل مهمة للزيارة القادمة"></textarea>

                        <button class="primary" type="submit">حفظ الموعد</button>
                    </form>
                <?php else: ?>
                    <div class="empty">ابحث عن المريض واختره أولا حتى يظهر نموذج الموعد.</div>
                <?php endif; ?>
            </section>
        </div>

        <?php if ($patient): ?>
            <section class="panel" style="margin-top:16px;">
                <h2>مواعيد المراجعة السابقة</h2>
                <?php if ($history && mysqli_num_rows($history) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($history)): ?>
                        <div class="history-item">
                            <strong><?= htmlspecialchars($row['followup_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <div><?= htmlspecialchars($row['followup_reason'] ?: 'بدون سبب مسجل', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="muted">
                                <?= htmlspecialchars($row['note'] ?: '', ENT_QUOTES, 'UTF-8') ?>
                                | النوع: <?= htmlspecialchars(($row['followup_type'] ?? 'review') === 'next_visit' ? 'زيارة قادمة' : 'مراجعة مجانية', ENT_QUOTES, 'UTF-8') ?>
                                | الحالة: <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty">لا توجد مواعيد مراجعة سابقة لهذا المريض.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</body>

</html>