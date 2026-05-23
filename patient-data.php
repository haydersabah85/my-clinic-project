<?php
include "config.php";
include "auth.php";
include_once "clinic_helpers.php";

clinic_ensure_infrastructure($con);

$id = (int) ($_GET['id'] ?? $_GET['id_open'] ?? 0);
if ($id <= 0) {
    die("لم يتم تحديد المريض");
}

$stmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ? AND " . clinic_active_patient_where($con, 'add_patient'));
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    die("المريض غير موجود أو مؤرشف");
}

$patientId = (int) $row['id'];
$patientName = $row['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات <?= h($patientName) ?></title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #64748b;
            --border: #e2e8f0;
            --primary: #1d4ed8;
            --teal: #0f766e;
            --green: #15803d;
            --violet: #7c3aed;
            --amber: #b45309;
            --danger: #dc2626;
            --shadow: 0 14px 34px rgba(15, 23, 42, .09);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 22px;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(29, 78, 216, .08), transparent 32%),
                radial-gradient(circle at top left, rgba(15, 118, 110, .07), transparent 28%),
                var(--bg);
            color: var(--text);
        }

        body[data-theme="dark"] {
            --bg: #07111d;
            --card: #0f1b2a;
            --text: #e6edf5;
            --muted: #9fb0c2;
            --border: rgba(148, 163, 184, .18);
            --primary: #60a5fa;
            --teal: #2dd4bf;
            --green: #34d399;
            --violet: #a78bfa;
            --amber: #fbbf24;
            --danger: #fb7185;
            --shadow: 0 18px 42px rgba(0, 0, 0, .28);
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .hero {
            min-height: 158px;
            padding: 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1d4ed8, #0f766e);
            color: #fff;
            box-shadow: 0 16px 38px rgba(29, 78, 216, .18);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
        }

        .hero h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.25;
        }

        .hero p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, .86);
            font-weight: 700;
        }

        .hero-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .hero-actions a,
        .visit-actions a,
        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px 13px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 900;
            border: 1px solid transparent;
        }

        .hero-actions a {
            color: #fff;
            background: rgba(255, 255, 255, .16);
            border-color: rgba(255, 255, 255, .2);
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
            gap: 16px;
            margin-top: 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 18px;
        }

        .card-title {
            margin: 0 0 14px;
            color: var(--primary);
            font-size: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .info-item {
            min-height: 72px;
            padding: 12px;
            border-radius: 12px;
            background: rgba(148, 163, 184, .08);
            border: 1px solid var(--border);
        }

        .info-item span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .info-item strong {
            color: var(--text);
            font-size: 16px;
            word-break: break-word;
        }

        .info-item.wide {
            grid-column: 1 / -1;
        }

        .visit-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .visit-actions a {
            color: #fff;
            min-height: 54px;
        }

        .visit-first {
            background: linear-gradient(135deg, var(--green), #22c55e);
        }

        .visit-repeat {
            background: linear-gradient(135deg, var(--primary), #0ea5e9);
        }

        .visit-free {
            background: linear-gradient(135deg, var(--violet), #9333ea);
        }

        .action-section {
            margin-top: 16px;
        }

        .action-section:first-child {
            margin-top: 0;
        }

        .action-section h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: var(--muted);
        }

        .action-list {
            display: grid;
            gap: 9px;
        }

        .action-link {
            justify-content: space-between;
            color: var(--text);
            background: rgba(148, 163, 184, .08);
            border-color: var(--border);
        }

        .action-link:hover,
        .hero-actions a:hover,
        .visit-actions a:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        .action-link.primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), #0ea5e9);
            border-color: transparent;
        }

        .action-link.teal {
            color: #fff;
            background: linear-gradient(135deg, var(--teal), #14b8a6);
            border-color: transparent;
        }

        .action-link.amber {
            color: #fff;
            background: linear-gradient(135deg, var(--amber), #f59e0b);
            border-color: transparent;
        }

        .note-box {
            margin-top: 16px;
            color: var(--muted);
            line-height: 1.8;
        }

        @media (max-width: 900px) {
            .hero,
            .content-grid {
                grid-template-columns: 1fr;
            }

            .hero-actions {
                justify-content: stretch;
            }

            .hero-actions a {
                flex: 1 1 auto;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 14px;
            }

            .hero {
                padding: 18px;
            }

            .hero h1 {
                font-size: 24px;
            }

            .info-grid,
            .visit-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="hero">
            <div>
                <h1><?= h($patientName) ?></h1>
                <p>ملف المريض رقم <?= $patientId ?> | <?= h($row['phone_no'] ?? '') ?></p>
            </div>
            <div class="hero-actions">
                <a href="dashboard.php">لوحة التحكم</a>
                <a href="main.php">كل المرضى</a>
                <a href="edit-patient.php?id_edit=<?= $patientId ?>">تعديل البيانات</a>
            </div>
        </section>

        <section class="card action-section">
            <h2 class="card-title">إضافة زيارة جديدة</h2>
            <div class="visit-actions">
                <a class="visit-first" href="visits2.php?patient_id=<?= $patientId ?>&visit_type=first">زيارة أول مرة</a>
                <a class="visit-repeat" href="visits2.php?patient_id=<?= $patientId ?>&visit_type=repeat">زيارة متكررة</a>
                <a class="visit-free" href="visits2.php?patient_id=<?= $patientId ?>&visit_type=free">زيارة مراجعة</a>
            </div>
        </section>

        <div class="content-grid">
            <section class="card">
                <h2 class="card-title">بيانات المريض</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span>الرقم التسلسلي</span>
                        <strong><?= $patientId ?></strong>
                    </div>
                    <div class="info-item">
                        <span>العمر</span>
                        <strong><?= h($row['age'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الجنس</span>
                        <strong><?= h($row['gender'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الموبايل</span>
                        <strong><?= h($row['phone_no'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الموبايل البديل</span>
                        <strong><?= h($row['phone_no_alt'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>العنوان</span>
                        <strong><?= h($row['address'] ?? '') ?></strong>
                    </div>
                    <div class="info-item wide">
                        <span>الملاحظات</span>
                        <strong><?= h($row['notes'] ?? '') ?></strong>
                    </div>
                </div>
            </section>

            <aside class="card">
                <section class="action-section">
                    <h3>الملف والمتابعة</h3>
                    <div class="action-list">
                        <a class="action-link primary" href="patient-file.php?id=<?= $patientId ?>">الملف الكامل <span>فتح</span></a>
                        <a class="action-link" href="patient_timeline.php?id=<?= $patientId ?>">التسلسل الزمني <span>عرض</span></a>
                        <a class="action-link teal" href="followup-appointment.php?id=<?= $patientId ?>">موعد مراجعة <span>إضافة</span></a>
                    </div>
                </section>

                <section class="action-section">
                    <h3>الإجراءات الطبية</h3>
                    <div class="action-list">
                        <a class="action-link" href="add-va.php?id=<?= $patientId ?>">إضافة فحص النظر <span>VA</span></a>
                        <a class="action-link amber" href="add-surgery.php?id=<?= $patientId ?>">إضافة عملية <span>جديد</span></a>
                    </div>
                </section>

                <section class="action-section">
                    <h3>الصور</h3>
                    <div class="action-list">
                        <a class="action-link" href="add-image.php?id=<?= $patientId ?>">إضافة صور <span>رفع</span></a>
                        <a class="action-link" href="image-comparison.php?id=<?= $patientId ?>">مقارنة الصور <span>عرض</span></a>
                    </div>
                </section>
            </aside>
        </div>
    </main>
</body>

</html>
