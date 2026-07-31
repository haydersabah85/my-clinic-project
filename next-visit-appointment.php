<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_followup_type_support($con);

$patient_id = 0;
if (isset($_GET['id'])) {
    $patient_id = (int) $_GET['id'];
} elseif (isset($_GET['patient_id'])) {
    $patient_id = (int) $_GET['patient_id'];
}

if ($patient_id <= 0) {
    die("خطأ: لم يتم تحديد المريض");
}

$patient_stmt = mysqli_prepare($con, "SELECT id, full_name, age, phone_no FROM add_patient WHERE id = ?");
mysqli_stmt_bind_param($patient_stmt, "i", $patient_id);
mysqli_stmt_execute($patient_stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($patient_stmt));

if (!$patient) {
    die("خطأ: المريض غير موجود");
}

$today = new DateTime('today');
$clinic_days = [1, 2, 3, 4, 6]; // Monday, Tuesday, Wednesday, Thursday, Saturday.
$clinic_dates = [];
$cursor = clone $today;

while (count($clinic_dates) < 30) {
    if (in_array((int) $cursor->format('N'), $clinic_days, true)) {
        $clinic_dates[] = $cursor->format('Y-m-d');
    }
    $cursor->modify('+1 day');
}

$end_date = end($clinic_dates);
$capacity_map = [];
$capacity_stmt = mysqli_prepare($con, "
    SELECT followup_date, COUNT(*) total
    FROM followups
    WHERE followup_date BETWEEN ? AND ?
    AND status = 'pending'
    GROUP BY followup_date
");
$today_sql = $today->format('Y-m-d');
mysqli_stmt_bind_param($capacity_stmt, "ss", $today_sql, $end_date);
mysqli_stmt_execute($capacity_stmt);
$capacity_result = mysqli_stmt_get_result($capacity_stmt);
while ($row = mysqli_fetch_assoc($capacity_result)) {
    $capacity_map[$row['followup_date']] = (int) $row['total'];
}

$previous_followups = mysqli_query($con, "
    SELECT followup_date, followup_reason, note, status, followup_type
    FROM followups
    WHERE patient_id = $patient_id
    ORDER BY followup_date DESC
    LIMIT 5
");

function arDayName(string $date): string
{
    $days = [
        'Saturday' => 'السبت',
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
    ];

    return $days[date('l', strtotime($date))] ?? date('l', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موعد المراجعة القادمة</title>
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
                radial-gradient(circle at top right, rgba(14, 165, 233, .1), transparent 34%),
                radial-gradient(circle at top left, rgba(16, 185, 129, .08), transparent 30%),
                #f3f7fb;
            color: #1f2937;
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.4fr .8fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .hero-main,
        .patient-card,
        .form-card,
        .side-card {
            background: rgba(255, 255, 255, .96);
            border: 1px solid #e5edf5;
            border-radius: 22px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
        }

        .hero-main {
            padding: 24px;
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
            color: #fff;
        }

        .hero-main h1 {
            margin: 0;
            font-size: 30px;
        }

        .hero-main p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, .86);
            font-weight: 700;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-actions a,
        .save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 12px;
            padding: 11px 15px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
        }

        .hero-actions a {
            background: rgba(255, 255, 255, .16);
        }

        .patient-card {
            padding: 18px;
            display: grid;
            gap: 12px;
        }

        .info-row {
            padding: 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e5edf5;
        }

        .info-row span {
            display: block;
            color: #64748b;
            font-weight: 800;
            font-size: 13px;
        }

        .info-row strong {
            display: block;
            margin-top: 4px;
            color: #1d4ed8;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            gap: 18px;
        }

        .form-card,
        .side-card {
            padding: 22px;
        }

        .section-title {
            margin: 0 0 16px;
            color: #1d4ed8;
            font-size: 21px;
        }

        label {
            display: block;
            margin: 14px 0 8px;
            color: #374151;
            font-weight: 800;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #d7e0ea;
            border-radius: 13px;
            padding: 12px 14px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            color: #1f2937;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .1);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .preset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .date-chip {
            border: 1px solid #d7e0ea;
            border-radius: 14px;
            padding: 11px;
            background: #f8fafc;
            cursor: pointer;
            text-align: center;
            font-family: inherit;
            font-weight: 800;
            color: #1f2937;
        }

        .date-chip small {
            display: block;
            margin-top: 4px;
            color: #64748b;
        }

        .date-chip.full {
            opacity: .5;
            cursor: not-allowed;
        }

        .date-chip:not(.full):hover,
        .date-chip.active {
            border-color: #2563eb;
            background: rgba(37, 99, 235, .08);
            color: #1d4ed8;
        }

        .save-btn {
            width: 100%;
            margin-top: 18px;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            font-size: 17px;
            box-shadow: 0 12px 24px rgba(16, 185, 129, .18);
        }

        .capacity-list,
        .history-list {
            display: grid;
            gap: 10px;
        }

        .capacity-item,
        .history-item {
            padding: 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e5edf5;
        }

        .capacity-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .capacity-item strong,
        .history-item strong {
            color: #1d4ed8;
        }

        .capacity-badge {
            flex: 0 0 auto;
            min-width: 58px;
            text-align: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(16, 185, 129, .12);
            color: #047857;
            font-weight: 900;
        }

        .capacity-badge.full {
            background: rgba(239, 68, 68, .12);
            color: #b91c1c;
        }

        .history-item p {
            margin: 5px 0 0;
            color: #64748b;
            line-height: 1.7;
        }

        body[data-theme="dark"] .hero-main {
            background: linear-gradient(135deg, #0f2d5c, #155e9f, #0f766e);
            border-color: rgba(147, 197, 253, .2);
        }

        body[data-theme="dark"] .info-row,
        body[data-theme="dark"] .date-chip,
        body[data-theme="dark"] .capacity-item,
        body[data-theme="dark"] .history-item {
            background: rgba(15, 23, 42, .72) !important;
            border-color: rgba(147, 197, 253, .14) !important;
        }

        body[data-theme="dark"] .info-row strong,
        body[data-theme="dark"] .capacity-item strong,
        body[data-theme="dark"] .history-item strong {
            color: #93c5fd;
        }

        body[data-theme="dark"] .date-chip {
            color: #dbeafe;
        }

        body[data-theme="dark"] label,
        body[data-theme="dark"] .history-item p,
        body[data-theme="dark"] .date-chip small {
            color: #a8bdd1;
        }

        @media (max-width: 900px) {
            body {
                padding: 12px;
            }

            .hero,
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <section class="hero">
            <div class="hero-main">
                <h1>تحديد موعد الزيارة القادمة</h1>
                <p>هذا النوع مخصص للزيارة القادمة/المتكررة التي تُحسب كموعد مدفوع بعد فترة زمنية أكبر من المراجعة المجانية.</p>
                <div class="hero-actions">
                    <a href="patient-file.php?id=<?= $patient_id ?>">ملف المريض</a>
                    <a href="followups.php">قائمة المتابعة</a>
                    <a href="dashboard.php">لوحة التحكم</a>
                </div>
            </div>

            <div class="patient-card">
                <div class="info-row">
                    <span>اسم المريض</span>
                    <strong><?= htmlspecialchars($patient['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="info-row">
                    <span>العمر</span>
                    <strong><?= htmlspecialchars($patient['age'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="info-row">
                    <span>رقم الهاتف</span>
                    <strong><?= htmlspecialchars($patient['phone_no'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        </section>

        <section class="content-grid">
            <form class="form-card" method="POST" action="save_followup.php">
                <h2 class="section-title">بيانات الموعد</h2>
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

                <label for="followup_date">تاريخ المراجعة</label>
                <input type="date" id="followup_date" name="followup_date" min="<?= $today_sql ?>" required>

                <div class="preset-grid">
                    <?php foreach (array_slice($clinic_dates, 0, 8) as $date): ?>
                        <?php
                        $booked = $capacity_map[$date] ?? 0;
                        $is_full = false;
                        ?>
                        <button
                            type="button"
                            class="date-chip<?= $is_full ? ' full' : '' ?>"
                            data-date="<?= $date ?>"
                            <?= $is_full ? 'disabled' : '' ?>>
                            <?= arDayName($date) ?> <?= date('d/m', strtotime($date)) ?>
                            <small><?= $booked ?> مواعيد</small>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="empty" style="margin-bottom:12px; border:1px solid #fde68a; background:#fffbeb; color:#92400e;">نوع الموعد: زيارة قادمة مدفوعة</div>
                <input type="hidden" name="followup_type" value="next_visit">

                <label for="followup_reason">سبب الزيارة القادمة</label>
                <input type="text" id="followup_reason" name="followup_reason" placeholder="مثال: متابعة بعد شهر" required>

                <label for="note">ملاحظات إضافية</label>
                <textarea id="note" name="note" placeholder="اكتب أي تفاصيل مهمة للزيارة القادمة..."></textarea>

                <button type="submit" class="save-btn">💾 حفظ موعد المراجعة</button>
            </form>

            <div class="side-card">
                <h2 class="section-title">سعة الأيام القادمة</h2>
                <div class="capacity-list">
                    <?php foreach ($clinic_dates as $date): ?>
                        <?php
                        $booked = $capacity_map[$date] ?? 0;
                        $is_full = false;
                        ?>
                        <div class="capacity-item">
                            <div>
                                <strong><?= arDayName($date) ?></strong><br>
                                <span><?= date('Y-m-d', strtotime($date)) ?></span>
                            </div>
                            <span class="capacity-badge<?= $is_full ? ' full' : '' ?>"><?= $booked ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="side-card">
                <h2 class="section-title">آخر مواعيد المتابعة</h2>
                <div class="history-list">
                    <?php if (mysqli_num_rows($previous_followups) > 0): ?>
                        <?php while ($followup = mysqli_fetch_assoc($previous_followups)): ?>
                            <div class="history-item">
                                <strong><?= htmlspecialchars($followup['followup_date'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <p>
                                    <?= htmlspecialchars($followup['followup_reason'] ?: 'بدون سبب مسجل', ENT_QUOTES, 'UTF-8') ?><br>
                                    النوع: <?= htmlspecialchars(($followup['followup_type'] ?? 'review') === 'next_visit' ? 'زيارة قادمة' : 'مراجعة مجانية', ENT_QUOTES, 'UTF-8') ?><br>
                                    الحالة: <?= htmlspecialchars($followup['status'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="history-item">
                            <strong>لا توجد مواعيد سابقة</strong>
                            <p>سيظهر سجل المتابعة هنا بعد إضافة أول موعد.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll(".date-chip:not(.full)").forEach(button => {
            button.addEventListener("click", () => {
                document.getElementById("followup_date").value = button.dataset.date;
                document.querySelectorAll(".date-chip").forEach(chip => chip.classList.remove("active"));
                button.classList.add("active");
            });
        });
    </script>
</body>

</html>