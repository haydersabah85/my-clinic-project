<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$active = clinic_active_patient_where($con, 'p');

function dq_rows(mysqli $con, string $sql): array
{
    $rows = [];
    try {
        $result = mysqli_query($con, $sql);
    } catch (mysqli_sql_exception $e) {
        return [];
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$missingPhone = dq_rows($con, "SELECT p.id, p.full_name, p.phone_no, p.notes FROM add_patient p WHERE $active AND (p.phone_no IS NULL OR TRIM(p.phone_no) = '') ORDER BY p.id DESC LIMIT 100");
$noVisits = dq_rows($con, "SELECT p.id, p.full_name, p.phone_no, p.notes FROM add_patient p LEFT JOIN visits v ON v.patient_id = p.id WHERE $active GROUP BY p.id HAVING COUNT(v.patient_id) = 0 ORDER BY p.id DESC LIMIT 100");
$duplicates = dq_rows($con, "SELECT phone_no, COUNT(*) total, GROUP_CONCAT(full_name SEPARATOR ' | ') names, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') ids FROM add_patient WHERE phone_no IS NOT NULL AND TRIM(phone_no) <> '' AND is_deleted = 0 GROUP BY phone_no HAVING COUNT(*) > 1 ORDER BY total DESC LIMIT 100");
$overdueFollowups = dq_rows($con, "SELECT f.id, f.patient_id AS id, p.full_name, p.phone_no, f.followup_date AS notes FROM followups f JOIN add_patient p ON p.id = f.patient_id WHERE f.status = 'pending' AND f.followup_date < CURDATE() AND $active ORDER BY f.followup_date ASC LIMIT 100");
$orphanProcedures = dq_rows($con, "SELECT s.id, s.patient_id, s.date, s.surgery_type FROM surgery_appointment s LEFT JOIN add_patient p ON p.id = s.patient_id WHERE p.id IS NULL ORDER BY s.id DESC LIMIT 100");

$totalIssues = count($missingPhone) + count($noVisits) + count($duplicates) + count($overdueFollowups) + count($orphanProcedures);

$cards = [
    [
        'title' => 'مرضى بدون رقم هاتف',
        'rows' => $missingPhone,
        'type' => 'patient',
        'hint' => 'يفضل إكمال الرقم لتسهيل الاتصال والمراجعات.',
    ],
    [
        'title' => 'مرضى بدون زيارات',
        'rows' => $noVisits,
        'type' => 'patient',
        'hint' => 'مرضى مسجلون لكن لا توجد زيارة مرتبطة بهم.',
    ],
    [
        'title' => 'مراجعات متأخرة',
        'rows' => $overdueFollowups,
        'type' => 'patient',
        'hint' => 'مراجعات بقيت pending بعد تاريخها.',
    ],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جودة البيانات</title>
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
            --primary: #2563eb;
            --danger: #dc2626;
            --success: #047857;
            --warning: #d97706;
            --radius: 8px;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
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
            padding: 22px;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .page {
            max-width: 1240px;
            margin: auto;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
            padding: 18px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        h1 {
            margin: 0;
            color: var(--primary);
            font-size: clamp(24px, 3vw, 32px);
        }

        .subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 13px;
            border-radius: var(--radius);
            background: var(--panel-soft);
            color: var(--text);
            border: 1px solid var(--border);
            font-weight: 800;
            text-decoration: none;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .metric {
            padding: 14px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .metric strong {
            display: block;
            font-size: 28px;
            color: var(--primary);
        }

        .metric span {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 16px;
            background: var(--panel-soft);
            border-bottom: 1px solid var(--border);
        }

        h2 {
            margin: 0;
            color: var(--text);
            font-size: 18px;
        }

        .count {
            min-width: 34px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
            color: var(--primary);
            font-weight: 900;
        }

        .hint {
            padding: 10px 16px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 520px;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 10px;
            border-top: 1px solid var(--border);
            text-align: right;
            vertical-align: top;
        }

        a {
            color: var(--primary);
            font-weight: 900;
            text-decoration: none;
        }

        .empty {
            padding: 22px;
            color: var(--success);
            text-align: center;
            font-weight: 900;
        }

        .danger-text {
            color: var(--danger);
        }

        @media (max-width: 900px) {
            body {
                padding: 12px;
            }

            .page-header {
                align-items: stretch;
                flex-direction: column;
            }

            .actions,
            .btn {
                width: 100%;
            }

            .summary,
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="page-header">
            <div>
                <h1>جودة البيانات</h1>
                <p class="subtitle">قائمة سريعة بالمشاكل التي تؤثر على التواصل، المتابعة، ودقة السجلات.</p>
            </div>
            <nav class="actions" aria-label="روابط سريعة">
                <a class="btn" href="dashboard.php">لوحة التحكم</a>
                <a class="btn" href="main.php">كل المرضى</a>
                <a class="btn" href="followups.php">المتابعات</a>
            </nav>
        </header>

        <section class="summary" aria-label="ملخص جودة البيانات">
            <div class="metric"><strong><?= $totalIssues ?></strong><span>إجمالي الملاحظات</span></div>
            <div class="metric"><strong><?= count($missingPhone) ?></strong><span>بدون هاتف</span></div>
            <div class="metric"><strong><?= count($noVisits) ?></strong><span>بدون زيارات</span></div>
            <div class="metric"><strong><?= count($overdueFollowups) ?></strong><span>مراجعات متأخرة</span></div>
            <div class="metric"><strong><?= count($duplicates) ?></strong><span>أرقام مكررة</span></div>
        </section>

        <div class="grid">
            <?php foreach ($cards as $card): ?>
                <section class="card">
                    <div class="card-head">
                        <h2><?= h($card['title']) ?></h2>
                        <span class="count"><?= count($card['rows']) ?></span>
                    </div>
                    <div class="hint"><?= h($card['hint']) ?></div>
                    <?php if (!$card['rows']): ?>
                        <div class="empty">لا توجد مشاكل</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <?php foreach ($card['rows'] as $row): ?>
                                    <tr>
                                        <td><a href="patient-data.php?id=<?= (int) $row['id'] ?>"><?= h($row['full_name']) ?></a></td>
                                        <td><?= h($row['phone_no'] ?? '') ?></td>
                                        <td><?= h($row['notes'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <section class="card">
                <div class="card-head">
                    <h2>أرقام هاتف مكررة</h2>
                    <span class="count"><?= count($duplicates) ?></span>
                </div>
                <div class="hint">قد تشير إلى ملفين لنفس المريض أو رقم عائلة مستخدم لأكثر من شخص.</div>
                <?php if (!$duplicates): ?>
                    <div class="empty">لا توجد مشاكل</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <?php foreach ($duplicates as $row): ?>
                                <tr>
                                    <td><?= h($row['phone_no']) ?></td>
                                    <td><?= h($row['names']) ?></td>
                                    <td><?= h($row['ids']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2>مواعيد عمليات بدون مريض</h2>
                    <span class="count"><?= count($orphanProcedures) ?></span>
                </div>
                <div class="hint">هذه سجلات عمليات مرتبطة بمريض غير موجود، وتحتاج مراجعة قاعدة البيانات.</div>
                <?php if (!$orphanProcedures): ?>
                    <div class="empty">لا توجد مشاكل</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <?php foreach ($orphanProcedures as $row): ?>
                                <tr>
                                    <td>#<?= (int) $row['id'] ?></td>
                                    <td class="danger-text">Patient <?= (int) $row['patient_id'] ?></td>
                                    <td><?= h($row['date']) ?></td>
                                    <td><?= h($row['surgery_type']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>

</html>
