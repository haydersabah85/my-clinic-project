<?php
include 'config.php';

include 'auth.php';

$row = [];

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

if (isset($_GET['id_open'])) {
    $id_open = $_GET['id_open'];
    //جلب بيانات المريض والزيارات السابقة وفحص النظر//
    $select_query = "SELECT * FROM add_patient WHERE id = $id_open";

    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}


// جلب البيانات عند الضغط على critical patients
if (isset($_GET['patient_id'])) {
    $id_edit = $_GET['patient_id'];

    $select_query = "SELECT * FROM add_patient WHERE id = $id_edit";

    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📁 <?php echo $row['full_name']; ?></title>
</head>

<script src="assets/theme.js" defer></script>



<style>
    /* ====== الخط والخلفية العامة ====== */
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');


    /* ==================================================
   MODERN PATIENT DASHBOARD - PHASE 1
   ================================================== */

    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap');

    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #0ea5e9;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --purple: #8b5cf6;

        --bg: #f8fafc;
        --card: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --border: #e2e8f0;

        --shadow-sm: 0 4px 12px rgba(15, 23, 42, 0.06);
        --shadow-md: 0 10px 30px rgba(15, 23, 42, 0.08);
        --shadow-lg: 0 20px 50px rgba(15, 23, 42, 0.12);

        --radius: 20px;
        --radius-sm: 12px;
    }

    /* ==================================================
   GENERAL
================================================== */
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        margin: 0;
        padding: 24px;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.06), transparent 35%),
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.05), transparent 30%),
            linear-gradient(180deg, #f8fafc, #eef4fb);
        color: var(--text);
    }

    /* Dark Mode */
    body[data-theme="dark"] {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #f8fafc;
    }

    /* ==================================================
   PAGE CONTAINER
================================================== */
    .page-container {
        max-width: 1800px;
        margin: 0 auto;
    }

    /* ==================================================
   HERO HEADER
================================================== */
    header h1 {
        margin: 0 0 24px;
        padding: 24px 32px;
        border-radius: 28px;
        background: linear-gradient(135deg, #1d4ed8, #2563eb, #0ea5e9);
        color: #ffffff;
        text-align: center;
        font-size: 34px;
        font-weight: 800;
        letter-spacing: -0.5px;
        box-shadow: 0 20px 45px rgba(37, 99, 235, 0.22);
    }

    /* ==================================================
   PATIENT HERO CARD
================================================== */
    .patient_info {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 28px;
        padding: 28px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-md);

        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        align-items: stretch;
        justify-content: space-between;

    }

    .patient_info p {
        margin: 0;
        padding: 14px 18px;
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 16px;
        font-size: 15px;
        line-height: 1.9;
        white-space: nowrap;
        flex: 1 1 200px;

    }

    .patient_info span:first-child {
        font-weight: 700;
        color: var(--muted);
        margin-left: 6px;
    }

    .patient_info span:last-child {
        color: var(--purple);
        font-weight: 800;
    }

    @media (max-width: 768px) {
        .patient_info {
            flex-direction: column;
            align-items: stretch;
        }

        .patient_info p {
            width: 100%;
        }

    }

    /* ==================================================
   GLOBAL BUTTONS
================================================== */
    a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;

        padding: 10px 16px;
        border-radius: 14px;
        border: none;

        background: linear-gradient(135deg, var(--success), #059669);
        color: #ffffff;
        text-decoration: none;

        font-size: 14px;
        font-weight: 700;
        font-family: 'Cairo', sans-serif;

        box-shadow: var(--shadow-sm);
        transition: all 0.25s ease;
    }

    a:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }

    /* أزرار بطاقة المريض */
    .patient_info>a {
        min-height: 54px;
    }



    /* ==================================================
   QUICK ACTION TOOLBAR
================================================== */


    .nav {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 24px;
        padding: 18px 24px;
        margin-bottom: 28px;
        box-shadow: var(--shadow-md);

        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 18px;
    }

    /* ==================================================
   ICON BUTTONS
================================================== */
    .icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 56px;
        height: 56px;
        border-radius: 18px;

        font-size: 24px;
        padding: 0;
        position: relative;
        cursor: pointer;

        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
        transition: all 0.25s ease;
    }

    .icon-btn:hover {
        transform: translateY(-3px) scale(1.05);
    }

    .edit-icon {
        background: linear-gradient(135deg, #3b82f6, #1e40af);
        color: #fff;
    }

    .delete-icon {
        background: linear-gradient(135deg, #ef4444, #991b1b);
        color: #fff;
    }

    .warning-icon {
        background: linear-gradient(135deg, #facc15, #eab308);
        color: #111827;
    }

    .visits-icon {
        background: linear-gradient(135deg, #a855f7, #7e22ce);
        color: #fff;
    }

    .home-icon {
        background: linear-gradient(135deg, #6b7280, #374151);
        color: #fff;
    }

    .followup-btn {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        color: #fff;
    }

    .recipe-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
    }

    /* Tooltips */
    .icon-btn::after {
        content: attr(data-title);
        position: absolute;
        bottom: 120%;
        right: 50%;
        transform: translateX(50%);
        background: #0f172a;
        color: #ffffff;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s ease;
    }

    .icon-btn:hover::after {
        opacity: 1;
    }

    /* Critical Blink */
    .critical-blink {
        animation: blink 1s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            box-shadow: 0 0 0 rgba(239, 68, 68, 0);
        }

        50% {
            box-shadow: 0 0 18px rgba(239, 68, 68, 0.8);
        }
    }

    /* ==================================================
   RESPONSIVE
================================================== */
    @media (max-width: 768px) {
        body {
            padding: 12px;
        }

        header h1 {
            font-size: 24px;
            padding: 18px;
            border-radius: 20px;
        }

        .patient_info {
            padding: 18px;
            border-radius: 20px;
        }

        .nav {
            padding: 14px;
            gap: 12px;
            border-radius: 20px;
        }

        .icon-btn {
            width: 48px;
            height: 48px;
            font-size: 20px;
            border-radius: 14px;
        }

        .patient_info>a {
            width: 100%;
        }
    }

    /* ==================================================
   MEDICAL DASHBOARD GRID
================================================== */
    .previous_data {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 24px;
        align-items: start;
    }

    /* بطاقات الأقسام */
    .previous_visits,
    .previous_va,
    .previous_surgeries,
    .previous_lasers,
    .previous_injections,
    .previous_medicines,
    .patient_visits {
        width: 100%;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 24px;
        padding: 22px;
        box-shadow: var(--shadow-md);
        overflow: auto;
    }

    /* ارتفاعات مناسبة */
    .previous_visits,
    .previous_va {
        max-height: 420px;
    }

    .previous_surgeries,
    .previous_lasers,
    .previous_injections {
        max-height: 380px;

    }

    .patient_visits {
        min-height: 300px;
    }

    /* ==================================================
   PREVIOUS MEDICINES
================================================== */
    .previous_medicines {
        width: 100%;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 24px;
        padding: 22px;
        box-shadow: var(--shadow-md);
        overflow: auto;
        max-height: 320px;
        direction: ltr;
    }

    /* ==================================================
   SECTION TITLES
================================================== */
    .section-title {
        margin: 0 0 16px;
        font-size: 20px;
        font-weight: 800;
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ==================================================
   TABLES
================================================== */
    table {
        width: 100%;
        border-collapse: collapse;
        background: transparent;
        box-shadow: none;
    }

    thead {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    th {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        padding: 12px 10px;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
    }

    th:first-child {
        border-top-right-radius: 12px;
    }

    th:last-child {
        border-top-left-radius: 12px;
    }

    td {
        padding: 10px 8px;
        font-size: 13px;
        text-align: center;
        border-bottom: 1px solid #edf2f7;
        white-space: nowrap;
        max-width: 240px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    tbody tr {
        transition: background-color 0.2s ease;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    /* ==================================================
   NOTE TOOLTIP
================================================== */
    .visit-note,
    .surgery-notes {
        position: relative;
        cursor: pointer;
        direction: ltr;
    }

    .visit-note::after,
    .surgery-notes::after {
        content: attr(data-note);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        width: 260px;
        background: #0f172a;
        color: #ffffff;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.7;
        white-space: normal;
        box-shadow: var(--shadow-md);
        display: none;
        z-index: 20;
    }

    .visit-note:hover::after,
    .surgery-notes:hover::after {
        display: block;
    }

    /* ==================================================
   SMALL ICONS INSIDE TABLES
================================================== */
    .previous_data .icon-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        font-size: 15px;
        box-shadow: 0 4px 8px rgba(15, 23, 42, 0.12);
    }

    /* ==================================================
   VISIT FORM
================================================== */
    .patient_visits form {
        display: flex;
        flex-direction: column;
        gap: 16px;
        height: 100%;
    }

    #notes {
        width: 100%;
        min-height: 120px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #f8fafc;
        font-size: 15px;
        line-height: 1.8;
        direction: ltr;
        resize: vertical;
        font-family: 'Cairo', sans-serif;
        transition: all 0.2s ease;
    }

    #notes:focus {
        outline: none;
        border-color: var(--primary);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    }

    #add_visit {
        width: 100%;
        height: 54px;
        border: none;
        border-radius: 16px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        font-size: 16px;
        font-weight: 800;
        font-family: 'Cairo', sans-serif;
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.22);
        transition: all 0.2s ease;
    }

    #add_visit:hover {
        transform: translateY(-2px);
    }

    /* ==================================================
   QUICK LINKS
================================================== */

    /* ==================================================
   QUICK LINKS - SINGLE ROW
================================================== */
    .links {
        margin-top: 28px;
        display: flex;
        flex-wrap: nowrap;
        /* صف واحد */
        justify-content: center;
        gap: 14px;
        overflow-x: auto;
        /* يسمح بالتمرير الأفقي إذا لم تكفِ المساحة */
        padding-bottom: 6px;
        scrollbar-width: thin;
    }

    /* تحسين شريط التمرير في المتصفحات المعتمدة على WebKit */
    .links::-webkit-scrollbar {
        height: 6px;
    }

    .links::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.5);
        border-radius: 999px;
    }

    .links a {
        flex: 0 0 auto;
        /* يمنع تمدد الأزرار */
        min-width: 170px;
        min-height: 58px;
        padding: 14px 18px;
        border-radius: 16px;
        background: linear-gradient(135deg, #ea580c, #c2410c);
        color: #ffffff;
        font-size: 15px;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: 0 10px 20px rgba(234, 88, 12, 0.18);
        transition: all 0.2s ease;
    }

    .links a:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(234, 88, 12, 0.24);
    }

    /* على الشاشات الصغيرة نسمح بالالتفاف لتبقى القراءة مريحة */
    @media (max-width: 768px) {
        .links {
            flex-wrap: wrap;
            overflow-x: visible;
            justify-content: stretch;
        }

        .links a {
            flex: 1 1 calc(50% - 14px);
            min-width: 0;
        }
    }

    /* ==========================================
   MODAL BACKDROP
========================================== */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1000;

        /* توسيط المحتوى */
        display: none;
        align-items: center;
        justify-content: center;

        /* خلفية داكنة خلف النافذة */
        background: rgba(15, 23, 42, 0.55);

        padding: 20px;
    }

    /* عند فتح النافذة عبر JavaScript */
    .modal.show {
        display: flex;
    }

    /* ==========================================
   MODAL WINDOW
========================================== */
    .modal-content {
        background: #ffffff;
        /* خلفية بيضاء كاملة */
        opacity: 1;
        /* غير شفافة */
        width: min(420px, 100%);
        padding: 28px;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        position: relative;
        animation: modalPop 0.25s ease;
    }

    /* حركة الظهور */
    @keyframes modalPop {
        from {
            transform: scale(0.95);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* ==================================================
   RESPONSIVE
================================================== */
    @media (max-width: 1200px) {
        .previous_data {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {

        .previous_visits,
        .previous_va,
        .previous_surgeries,
        .previous_lasers,
        .previous_injections,
        .patient_visits {
            padding: 16px;
            border-radius: 18px;
        }

        .section-title {
            font-size: 18px;
        }

        td,
        th {
            font-size: 12px;
        }

        .links {
            grid-template-columns: 1fr;
        }
    }

    /* ==================================================
   STATISTICS CARDS
================================================== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 22px;
        padding: 20px 16px;
        text-align: center;
        box-shadow: var(--shadow-md);
        transition: all 0.25s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-icon {
        display: block;
        font-size: 28px;
        margin-bottom: 8px;
    }

    .stat-value {
        display: block;
        font-size: 30px;
        font-weight: 800;
        color: var(--primary-dark);
        line-height: 1.2;
    }

    .stat-label {
        display: block;
        margin-top: 4px;
        font-size: 14px;
        font-weight: 700;
        color: var(--muted);
    }

    /* تثبيت نموذج الزيارة أثناء التمرير */
    @media (min-width: 1201px) {
        .patient_visits {
            position: sticky;
            top: 20px;
        }
    }

    /* ==================================================
   EYE BADGES
================================================== */
    .eye-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        color: #ffffff;
    }

    .eye-od {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .eye-os {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .eye-ou {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }


    /* ==================================================
   PRESCRIPTION CARDS
================================================== */
    .prescription-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px;
        margin-bottom: 16px;
        width: 100%;
    }

    .prescription-header {
        margin-bottom: 10px;
    }

    .prescription-date {
        font-weight: 800;
        color: var(--primary-dark);
        font-size: 15px;
    }

    .prescription-diagnosis {
        margin-bottom: 12px;
        color: #475569;
        font-weight: 600;
        line-height: 1.8;
    }

    .prescription-list {
        margin: 0;
        padding-right: 20px;
        line-height: 2;
    }

    .prescription-list li {
        margin-bottom: 6px;
    }

    .prescription-footer {
        margin-top: 14px;
        text-align: left;
    }

    .view-prescription-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }
</style>




<header>
    <h1> ملف المريض: <?php echo htmlspecialchars($row['full_name']); ?></h1>
</header>


<body>

    <div class="page-container">

        <div class="patient_info"></span>

            <p><span>ID:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['id']); ?></span>
            </p>

            <p><span>الاسم:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['full_name']); ?></span>
            </p>

            <p><span>العمر:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['age']); ?></span>
            </p>

            <p><span>رقم الموبايل:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['phone_no']); ?></span>
            </p>
            <a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a>
            <a href="patient-data.php?id_open=<?php echo $row['id']; ?>"
                style="background: linear-gradient(135deg, #e85a27, #bb4c18); 
        color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none;">📁 بيانات المريض</a>
        </div>

        <div class="nav">
            <?php

            $critical_class = ($row['is_critical'] == 1) ? 'critical-blink' : '';
            ?>

            <a href="marked_as_done.php?id=<?= $id ?>"
                class="icon-btn done-icon"
                data-title="تمت الزيارة">✅</a>

            <a href="mark_critical.php?id=<?= $id ?>"
                class="icon-btn warning-icon <?= $critical_class ?>"
                data-title="تعليم كمريض حرج">
                🚨
            </a>

            <a href="#" class="icon-btn followup-btn"
                data-title="إضافة متابعة"
                onclick="openFollowup(event)">
                📌
            </a>

            <a href="visits.php"
                class="icon-btn visits-icon"
                data-title="زيارات اليوم">
                🏥 </a>

            <a href="dashboard.php"
                class="icon-btn home-icon"
                data-title="الصفحة الرئيسية">
                🏠 </a>

            <a href="treatment.php?patient_id=<?php echo $row['id']; ?>"
                class="icon-btn recipe-btn"
                data-title="وصفة العلاج">
                💊
            </a>
        </div>


        <?php
        $visits_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM patient_visits WHERE patient_id = $id"));
        $va_count = mysqli_num_rows(mysqli_query($con, "SELECT va_id FROM va WHERE patient_id = $id"));
        $surgery_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM surgery WHERE patient_id = $id"));
        $laser_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM laser WHERE patient_id = $id"));
        $injection_count = mysqli_num_rows(mysqli_query($con, "SELECT id FROM injection WHERE patient_id = $id"));
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📝</span>
                <span class="stat-value"><?= $visits_count ?></span>
                <span class="stat-label">الزيارات</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">👁️</span>
                <span class="stat-value"><?= $va_count ?></span>
                <span class="stat-label">فحص النظر</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🏥</span>
                <span class="stat-value"><?= $surgery_count ?></span>
                <span class="stat-label">العمليات</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🔦</span>
                <span class="stat-value"><?= $laser_count ?></span>
                <span class="stat-label">الليزر</span>
            </div>

            <div class="stat-card">
                <span class="stat-icon">💉</span>
                <span class="stat-value"><?= $injection_count ?></span>
                <span class="stat-label">الحقن</span>
            </div>
        </div>
        <div class="previous_data">
            <div class="previous_visits">
                <h3 class="section-title">📝 الزيارات السابقة</h3>
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Delete</th>
                            <th>Edit</th>
                            <th>Previous Visits Notes</th>
                            <th>Date</th>

                        </tr>
                    </thead>
                    <tbody>
                        <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                        <?php
                        include 'config.php';
                        if (isset($_GET['id'])) {
                            $id = $_GET['id'];

                            $select_query = "SELECT * FROM patient_visits WHERE patient_id = $id ORDER BY date DESC";
                            $result = mysqli_query($con, $select_query);
                            while ($visit_row = mysqli_fetch_assoc($result)) {

                                echo "<tr>";
                                echo "<td><a class='icon-btn delete-icon' 
                                    href='delete-visit.php?id_delete=" . $visit_row['id'] . "'
                                    onclick=\"return confirm('هل أنت متأكد من حذف هذه الزيارة؟');\">
                                    🗑️
                                    </a>
                                    </td>";

                                echo "<td>
                                    <button class='icon-btn edit-icon edit-btn'                                           
                                            data-note='" . htmlspecialchars($visit_row['notes'], ENT_QUOTES, 'UTF-8') . "'
                                            data-id='" . $visit_row['id'] . "'>
                                    ✏️
                                    </button>
                                    </td>";

                                echo "<td class=\"visit-note\" data-note='" . htmlspecialchars($visit_row['notes'], ENT_QUOTES, 'UTF-8') . "'>" . nl2br(htmlspecialchars($visit_row['notes'])) . " 
                                </td>";
                                echo "<td>" . htmlspecialchars($visit_row['date']) . "</td>";

                                echo "</tr>";
                            }
                        }


                        ?>

                    </tbody>
                </table>
            </div>

            <div class="previous_va">
                <h3 class="section-title">👁️ فحص النظر السابق</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Edit</th>
                            <th>BCVA(OS)</th>
                            <th>BCVA(OD)</th>
                            <th>VA(OS)</th>
                            <th>VA(OD)</th>
                            <th>Date</th>

                        </tr>
                    </thead>
                    <tbody>
                        <!-- بيانات فحص النظر السابقة ستُضاف هنا -->

                        <?php
                        include 'config.php';
                        if (isset($_GET['id'])) {
                            $id = $_GET['id'];
                            $select_query = "SELECT * FROM va WHERE patient_id = $id ORDER BY exam_date DESC";
                            $result = mysqli_query($con, $select_query);
                            while ($va_row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>
                                    <a class='icon-btn edit-icon'                                   
                                    href='edit-va.php?id_edit=" . $va_row['va_id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
                                echo "<td>" . htmlspecialchars($va_row['bcva_os']) . "</td>";
                                echo "<td>" . htmlspecialchars($va_row['bcva_od']) . "</td>";
                                echo "<td>" . htmlspecialchars($va_row['va_os']) . "</td>";
                                echo "<td>" . htmlspecialchars($va_row['va_od']) . "</td>";
                                echo "<td>" . htmlspecialchars($va_row['exam_date']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>

                    </tbody>
                </table>
            </div>

            <div class="previous_surgeries">
                <h3 class="section-title">🏥 العمليات السابقة</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Delete</th>
                            <th>Edit</th>
                            <th>Notes</th>
                            <th>IOL</th>
                            <th>Type of Surgery</th>
                            <th>Eye</th>
                            <th>Date</th>

                        </tr>
                    </thead>
                    <tbody>
                        <!-- بيانات العمليات السابقة ستُضاف هنا -->

                        <?php
                        include 'config.php';
                        if (isset($_GET['id'])) {
                            $id = $_GET['id'];
                            $select_query = "SELECT * FROM surgery
                        WHERE patient_id = $id ORDER BY date DESC";
                            $result = mysqli_query($con, $select_query);
                            while ($surgery_row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>
                                    <a class='icon-btn delete-icon'
                                    href='delete-surgery.php?id_delete=" . $surgery_row['id'] . "'
                                    onclick=\"return confirm('هل أنت متأكد من حذف هذه العملية؟');\">
                                    🗑️
                                    </a>
                                    </td>";

                                echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-surgery.php?id_edit=" . $surgery_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";

                                echo "<td class='surgery-notes'>" . htmlspecialchars($surgery_row['notes']) . "</td>";
                                echo "<td>" . htmlspecialchars($surgery_row['iol_type']) . "</td>";
                                echo "<td>" . htmlspecialchars($surgery_row['surgery_type']) . "</td>";

                                $eye = strtoupper(trim($surgery_row['eye']));
                                $eye_class = '';

                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<td><span class='eye-badge $eye_class'>" . htmlspecialchars($eye) . "</span></td>";
                                echo "<td>" . htmlspecialchars($surgery_row['date']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>

                    </tbody>
                </table>
            </div>








            <div class="previous_lasers">
                <h3 class="section-title">🔦 جلسات الليزر</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Edit</th>
                            <th>Notes</th>
                            <th>Laser</th>
                            <th>Eye</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                        <?php
                        include 'config.php';
                        if (isset($_GET['id'])) {
                            $id = $_GET['id'];
                            $select_query = "SELECT * FROM laser WHERE patient_id = $id ORDER BY date DESC";
                            $result = mysqli_query($con, $select_query);
                            while ($laser_row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-laser.php?id_edit=" . $laser_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
                                echo "<td>" . htmlspecialchars($laser_row['notes']) . "</td>";
                                echo "<td>" . htmlspecialchars($laser_row['laser_type']) . "</td>";

                                $eye = strtoupper(trim($laser_row['eye']));
                                $eye_class = '';

                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<td><span class='eye-badge $eye_class'>" . htmlspecialchars($eye) . "</span></td>";
                                echo "<td>" . htmlspecialchars($laser_row['date']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>

                    </tbody>
                </table>
            </div>

            <div class="previous_injections">
                <h3 class="section-title">💉 الحقن السابقة</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Edit</th>
                            <th>Notes</th>
                            <th>Injection</th>
                            <th>Eye</th>
                            <th>Date</th>

                        </tr>
                    </thead>
                    <tbody>
                        <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                        <?php
                        include 'config.php';
                        if (isset($_GET['id'])) {
                            $id = $_GET['id'];
                            $select_query = "
                        SELECT * FROM injection WHERE patient_id = $id ORDER BY date DESC";
                            $result = mysqli_query($con, $select_query);
                            while ($injection_row = mysqli_fetch_assoc($result)) {

                                echo "<tr>";
                                echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-injection.php?id_edit=" . $injection_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
                                echo "<td>" . htmlspecialchars($injection_row['notes']) . "</td>";
                                echo "<td>" . htmlspecialchars($injection_row['injection_type']) . "</td>";
                                $eye = strtoupper(trim($injection_row['eye']));
                                $eye_class = '';

                                if ($eye == 'OD') $eye_class = 'eye-od';
                                elseif ($eye == 'OS') $eye_class = 'eye-os';
                                elseif ($eye == 'OU') $eye_class = 'eye-ou';

                                echo "<td><span class='eye-badge $eye_class'>" . htmlspecialchars($eye) . "</span></td>";
                                echo "<td>" . htmlspecialchars($injection_row['date']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>

                    </tbody>
                </table>
            </div>



            <div class="patient_visits">
                <h3 class="section-title">📝 إضافة أو تعديل زيارة</h3>
                <form action="patient-visits.php?id=<?php echo $id ?>" method="POST">
                    <input type="hidden" id="id" name="id">
                    <textarea spellcheck="false" id="notes" name="notes" rows="4" cols="43" placeholder="اكتب ملاحظات الزيارة هنا..."></textarea>

                    <button type="submit" id="add_visit" name="add_visit"> 📝 إضافة زيارة</button>
                </form>


            </div>


            <div class="previous_medicines">
                <h3 class="section-title">💊 الأدوية المصروفة</h3>




                <?php

                include 'config.php';
                $patient_id = $_GET['id'];
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

                if (mysqli_num_rows($previous_medicines) > 0) {

                    $current_prescription_id = null;
                    $last_prescription_id = null;

                    while ($prescription_row = mysqli_fetch_assoc($previous_medicines)) {

                        // عند بدء وصفة جديدة
                        if ($current_prescription_id != $prescription_row['prescription_id']) {

                            // إغلاق البطاقة السابقة
                            if ($current_prescription_id !== null) {
                                echo "</ul>";
                                echo "<div class='prescription-footer'>";
                                echo "<a class='view-prescription-btn' href='view_prescription.php?id={$last_prescription_id}'>📄 عرض الوصفة</a>";
                                echo "</div>";
                                echo "</div>";
                            }

                            $current_prescription_id = $prescription_row['prescription_id'];
                            $last_prescription_id = $prescription_row['prescription_id'];

                            // فتح بطاقة جديدة
                            echo "<div class='prescription-card'>";

                            echo "<div class='prescription-header'>";
                            echo "<span class='prescription-date'>📅 " . ($prescription_row['prescription_date'] ?? '-') . "</span>";
                            echo "</div>";

                            if (!empty($prescription_row['diagnosis'])) {
                                echo "<div class='prescription-diagnosis'>🩺 " . htmlspecialchars($prescription_row['diagnosis']) . "</div>";
                            }

                            echo "<ul class='prescription-list'>";
                        }

                        // تحديد لون شارة العين
                        $eye = strtoupper(trim($prescription_row['eye']));
                        $eye_class = '';

                        if ($eye == 'RIGHT') $eye_class = 'eye-od';
                        elseif ($eye == 'LEFT') $eye_class = 'eye-os';
                        elseif ($eye == 'BOTH') $eye_class = 'eye-ou';

                        // اسم الدواء
                        $medicine_name = trim($prescription_row['medicine_name'] . ' ' . $prescription_row['medicine_form']);

                        // عرض الدواء
                        echo "<li>";
                        echo "<strong>" . htmlspecialchars($medicine_name) . "</strong>";

                        if (!empty($prescription_row['frequency'])) {
                            echo " — " . htmlspecialchars($prescription_row['frequency']);
                        }

                        if (!empty($prescription_row['dose'])) {
                            echo " — " . htmlspecialchars($prescription_row['dose']);
                        }

                        if (!empty($eye)) {
                            echo " — <span class='eye-badge {$eye_class}'>" . htmlspecialchars($eye) . "</span>";
                        }

                        if (!empty($prescription_row['duration'])) {
                            echo " — " . htmlspecialchars($prescription_row['duration']);
                        }

                        echo "</li>";
                    }

                    // إغلاق آخر بطاقة
                    echo "</ul>";
                    echo "<div class='prescription-footer'>";
                    echo "<a class='view-prescription-btn' href='view_prescription.php?id={$last_prescription_id}'>📄 عرض الوصفة</a>";
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "<p style='text-align:center;color:#64748b;'>لا توجد وصفات سابقة</p>";
                }
                ?>
            </div>





        </div>

        <script>
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notes = this.dataset.note;
                    const visitId = this.dataset.id;

                    document.getElementById('notes').value = notes;
                    document.getElementById('id').value = visitId;


                    document.getElementById('add_visit').innerText = 'تحديث الزيارة';

                });
            });
        </script>

        <script>
            window.onload = function() {
                const notes = document.getElementById('notes');
                const visitId = document.getElementById('id');

                if (notes) notes.value = '';
                if (visitId) visitId.value = '';

                const btn = document.getElementById('add_visit');
                if (btn) btn.innerText = ' 📝 إضافة زيارة';
                // إخفاء نافذة المتابعة عند تحميل الصفحة
                const modal = document.getElementById('followupModal');
                if (modal) modal.style.display = 'none';
            };
        </script>

        <div id="followupModal" class="modal">

            <div class="modal-content">

                <span class="close-btn" onclick="closeFollowup()">×</span>

                <h3>📅 تحديد موعد المراجعة</h3>

                <form method="POST" action="save_followup.php?id=<?php echo $row['id']; ?>">

                    <input type="hidden" name="patient_id" value="<?php echo $row['id']; ?>">

                    <label>تاريخ المراجعة</label>
                    <input type="date" name="followup_date" required>

                    <label>سبب المراجعة</label>
                    <input type="text" name="followup_reason" placeholder="مثال: مراجعة ضغط العين">

                    <button type="submit">💾 حفظ المتابعة</button>

                </form>

            </div>

        </div>

        <script>
            function openFollowup(e) {
                e.preventDefault();
                document.getElementById("followupModal").style.display = "block";
            }

            function closeFollowup() {
                document.getElementById("followupModal").style.display = "none";
            }

            /* اغلاق عند الضغط خارج الصندوق */

            window.onclick = function(event) {

                let modal = document.getElementById("followupModal");

                if (event.target == modal) {
                    modal.style.display = "none";
                }

            }
        </script>


        <div class="links">
            <a href="surgery-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد عملية</a>
            <a href="laser-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد ليزر</a>
            <a href="injection-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد حقن</a>
            <a href="add-va.php?id=<?php echo htmlspecialchars($row['id']); ?>">اضافة فحص النظر</a>
            <a href="show-image.php?id=<?php echo htmlspecialchars($row['id']); ?>"> عرض الصور</a>
            <a href="patient_reports.php?id=<?php echo htmlspecialchars($row['id']); ?>">التقارير الطبية</a>
        </div>

    </div>
</body>

</html>