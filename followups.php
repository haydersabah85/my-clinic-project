<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_followup_type_support($con);

$today = date('Y-m-d');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$search = trim($_GET['search'] ?? '');
$type_filter = trim($_GET['type_filter'] ?? 'all');
$conditions = ["f.status = 'pending'"];
$params = [];
$types = "";

if ($date_from !== '') {
    $conditions[] = "f.followup_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to !== '') {
    $conditions[] = "f.followup_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($search !== '') {
    $conditions[] = "(p.full_name LIKE ? OR p.phone_no LIKE ? OR f.followup_reason LIKE ? OR f.note LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($type_filter !== 'all') {
    $conditions[] = "f.followup_type = ?";
    $params[] = $type_filter === 'next_visit' ? 'next_visit' : 'review';
    $types .= "s";
}

$where = implode(" AND ", $conditions);

$stmt = mysqli_prepare($con, "
    SELECT f.*, p.full_name, p.phone_no AS phone
    FROM followups f
    JOIN add_patient p ON f.patient_id = p.id
    WHERE $where
    ORDER BY f.followup_date ASC, f.id ASC
");

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
$rows = [];
while ($row = mysqli_fetch_assoc($query)) {
    $rows[] = $row;
}

$stats = [
    'total' => count($rows),
    'today' => 0,
    'tomorrow' => 0,
    'overdue' => 0,
];

foreach ($rows as $row) {
    if ($row['followup_date'] == $today) {
        $stats['today']++;
    }
    if ($row['followup_date'] == date('Y-m-d', strtotime('+1 day'))) {
        $stats['tomorrow']++;
    }
    if ($row['followup_date'] < $today) {
        $stats['overdue']++;
    }
}

$grouped = [];
foreach ($rows as $row) {
    $grouped[$row['followup_date']][] = $row;
}

function clinic_ar_day_name(string $date): string
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

function clinic_followup_type_label(array $row): string
{
    return (($row['followup_type'] ?? 'review') === 'next_visit') ? 'زيارة قادمة' : 'مراجعة مجانية';
}

function clinic_followup_type_class(array $row): string
{
    return (($row['followup_type'] ?? 'review') === 'next_visit') ? 'paid' : 'free';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مراجعات الأسبوع</title>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem("theme") === "dark") {
                document.body.setAttribute("data-theme", "dark");
            }
        });
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(45, 137, 181, 0.08), transparent 32%),
                #f4f6f9;
            color: #263238;
            margin: 0;
            padding: 20px;
        }

        .page-shell {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            margin: 0 0 18px;
            padding: 20px 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2d89b5, #3fa7d6);
            color: #ffffff;
            text-align: center;
            box-shadow: 0 12px 28px rgba(45, 137, 181, 0.18);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            padding: 14px 16px;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid #e0e9ef;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        .stat-card strong {
            display: block;
            font-size: 22px;
            color: #125d8a;
        }

        .stat-card span {
            color: #64748b;
            font-weight: 700;
        }

        .filters-card,
        .day-card,
        .empty-message {
            background: #ffffff;
            border: 1px solid #e3ebf2;
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .filters-card {
            padding: 16px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }

        .filters-card .field {
            flex: 1;
            min-width: 160px;
        }

        .filters-card label {
            display: block;
            margin-bottom: 6px;
            font-weight: 800;
            color: #456173;
        }

        .filters-card input,
        .filters-card select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d6e2ea;
            border-radius: 9px;
            font-family: inherit;
        }

        .filters-card .btn,
        .action-link {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-family: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .filters-card .btn {
            background: #2563eb;
            color: #fff;
        }

        .filters-card .btn.secondary {
            background: #0f766e;
        }

        .filters-card .btn.danger {
            background: #e74c3c;
        }

        .day-card {
            padding: 16px;
            margin-bottom: 18px;
        }

        .day-title {
            font-size: 18px;
            font-weight: 800;
            color: #2d89b5;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5edf2;
        }

        .patient-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #edf2f7;
            padding: 12px 14px;
            border-radius: 12px;
            margin: 8px 0;
            line-height: 1.8;
        }

        .patient-row strong {
            color: #1f5169;
        }

        .row-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }

        .entry-type,
        .type-badge,
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }

        .entry-type.free,
        .type-badge.free {
            background: #e0f2fe;
            color: #0369a1;
        }

        .entry-type.paid,
        .type-badge.paid {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pill.overdue {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-pill.today {
            background: #dcfce7;
            color: #166534;
        }

        .today {
            background: #ffe5e5 !important;
            border-color: #ffc9c9;
        }

        .tomorrow {
            background: #fff3cd !important;
            border-color: #ffe08a;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .action-link {
            background: #28a745;
            color: #fff;
            box-shadow: 0 8px 18px rgba(40, 167, 69, 0.18);
        }

        .action-link.delete {
            background: #e74c3c;
            box-shadow: 0 8px 18px rgba(231, 76, 60, 0.18);
        }

        .empty-message {
            padding: 20px;
            text-align: center;
            color: #607d8b;
        }

        body[data-theme="dark"] {
            background:
                radial-gradient(circle at top right, rgba(63, 167, 214, 0.16), transparent 34%),
                radial-gradient(circle at top left, rgba(45, 212, 191, 0.1), transparent 28%),
                linear-gradient(180deg, #07111d, #0b1220 58%, #08111d);
            color: #e6edf5;
        }

        body[data-theme="dark"] h2 {
            background: linear-gradient(135deg, #0f2d5c, #155e9f, #0f766e);
            border: 1px solid rgba(147, 197, 253, 0.2);
        }

        body[data-theme="dark"] .stat-card,
        body[data-theme="dark"] .filters-card,
        body[data-theme="dark"] .day-card,
        body[data-theme="dark"] .empty-message {
            background: linear-gradient(145deg, rgba(15, 27, 42, 0.96), rgba(11, 18, 32, 0.96));
            border-color: rgba(148, 163, 184, 0.18);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.34);
        }

        body[data-theme="dark"] .day-title {
            color: #93c5fd;
            border-bottom-color: rgba(148, 163, 184, 0.16);
        }

        body[data-theme="dark"] .patient-row {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.14);
            color: #dce7f3;
        }

        body[data-theme="dark"] .patient-row strong {
            color: #bfdbfe;
        }

        body[data-theme="dark"] .entry-type.free,
        body[data-theme="dark"] .type-badge.free {
            background: rgba(2, 132, 199, 0.18);
            color: #bae6fd;
        }

        body[data-theme="dark"] .entry-type.paid,
        body[data-theme="dark"] .type-badge.paid {
            background: rgba(217, 119, 6, 0.2);
            color: #fde68a;
        }

        body[data-theme="dark"] .today {
            background: rgba(127, 29, 29, 0.42) !important;
            border-color: rgba(248, 113, 113, 0.34);
        }

        body[data-theme="dark"] .tomorrow {
            background: rgba(113, 63, 18, 0.42) !important;
            border-color: rgba(251, 191, 36, 0.34);
        }

        @media (max-width: 700px) {
            body {
                padding: 12px;
            }

            .filters-card {
                align-items: stretch;
            }

            .patient-row {
                flex-direction: column;
                align-items: stretch;
            }

            .actions {
                justify-content: stretch;
            }

            .actions .action-link {
                width: 100%;
                text-align: center;
            }
        }
    </style>
    <script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <div class="page-shell">
        <h2>📅 مواعيد المتابعة</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <strong><?= (int) $stats['total'] ?></strong>
                <span>إجمالي المواعيد المعلقة</span>
            </div>
            <div class="stat-card">
                <strong><?= (int) $stats['today'] ?></strong>
                <span>لليوم</span>
            </div>
            <div class="stat-card">
                <strong><?= (int) $stats['tomorrow'] ?></strong>
                <span>للغد</span>
            </div>
            <div class="stat-card">
                <strong><?= (int) $stats['overdue'] ?></strong>
                <span>متأخر</span>
            </div>
        </div>

        <form method="GET" class="filters-card">
            <div class="field">
                <label for="search">بحث</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="اسم المريض أو الهاتف أو السبب">
            </div>
            <div class="field">
                <label for="date_from">من تاريخ</label>
                <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="date_to">إلى تاريخ</label>
                <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="type_filter">النوع</label>
                <select id="type_filter" name="type_filter">
                    <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>الكل</option>
                    <option value="review" <?= $type_filter === 'review' ? 'selected' : '' ?>>مراجعة مجانية</option>
                    <option value="next_visit" <?= $type_filter === 'next_visit' ? 'selected' : '' ?>>زيارة قادمة</option>
                </select>
            </div>
            <button type="submit" class="btn">عرض</button>
            <a href="followups.php" class="btn secondary">إعادة تعيين</a>
            <a href="followup-appointment.php" class="btn">إعطاء موعد</a>
        </form>

        <?php if (!empty($rows)) : ?>
            <?php foreach ($grouped as $date => $items) : ?>
                <?php
                $day_class = '';
                if ($date == $today) {
                    $day_class = 'today';
                } elseif ($date == date('Y-m-d', strtotime('+1 day'))) {
                    $day_class = 'tomorrow';
                }
                ?>
                <div class="day-card <?= htmlspecialchars($day_class, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="day-title">📌 <?= htmlspecialchars(clinic_ar_day_name($date) . ' ' . date('d-m-Y', strtotime($date)), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php foreach ($items as $row) : ?>
                        <?php $row_class = $row['followup_date'] < $today ? 'overdue' : ($row['followup_date'] == $today ? 'today' : ''); ?>
                        <div class="patient-row">
                            <div>
                                <div class="row-meta">
                                    <span class="entry-type <?= htmlspecialchars(clinic_followup_type_class($row), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(clinic_followup_type_label($row), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="type-badge <?= htmlspecialchars(clinic_followup_type_class($row), ENT_QUOTES, 'UTF-8') ?>">
                                        النوع: <?= htmlspecialchars(clinic_followup_type_label($row), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($row_class !== '') : ?>
                                        <span class="status-pill <?= htmlspecialchars($row_class, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= $row_class === 'overdue' ? 'متأخر' : 'اليوم' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <strong><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                📞 <?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?><br>
                                📝 <?= htmlspecialchars($row['note'] ?: '—', ENT_QUOTES, 'UTF-8') ?><br>
                                🔹 السبب: <?= htmlspecialchars($row['followup_reason'] ?: '—', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="actions">
                                <a class="action-link" href="mark_done.php?id=<?= (int) $row['id'] ?>&patient_id=<?= (int) $row['patient_id'] ?>">✔ تم</a>
                                <a class="action-link delete" href="delete-followup.php?id=<?= (int) $row['id'] ?>&patient_id=<?= (int) $row['patient_id'] ?>" onclick="return confirm('هل أنت متأكد من حذف هذه المراجعة؟');">❌ حذف</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="empty-message">لا توجد مراجعات حسب التصفية الحالية</div>
        <?php endif; ?>
    </div>
</body>

</html>