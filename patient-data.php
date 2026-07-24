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
$flash = clinic_take_flash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات <?= h($patientName) ?> | عيادة الدكتور حيدر صباح الربيعي</title>
    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
    <link rel="stylesheet" href="assets/branding/branding.css">
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            background: linear-gradient(120deg, #b0602c, #d5823c);
        }

        .visit-charity {
            background: linear-gradient(120deg, #6d28d9, #8b5cf6);
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

        .action-link.laser {
            color: #fff;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            border-color: transparent;
        }

        .action-link.injection {
            color: #fff;
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
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

        .app-sidebar-toggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1305;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 44px;
            border: 1px solid rgba(255, 255, 255, .26);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 800;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--teal));
            box-shadow: 0 12px 24px rgba(15, 23, 42, .24);
            backdrop-filter: blur(8px);
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, right .24s ease;
        }

        .app-sidebar-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(15, 23, 42, .3);
        }

        .app-sidebar-toggle:focus-visible {
            outline: 2px solid rgba(59, 130, 246, .6);
            outline-offset: 2px;
        }

        body.sidebar-open .app-sidebar-toggle {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-6px);
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 286px;
            max-width: 88vw;
            height: 100vh;
            background: var(--card);
            border-left: 1px solid var(--border);
            box-shadow: -18px 0 40px rgba(15, 23, 42, .22);
            padding: 20px 15px;
            overflow-y: auto;
            transform: translateX(102%);
            transition: transform .24s ease;
            z-index: 1250;
        }

        .app-sidebar.is-open {
            transform: translateX(0);
        }

        .app-sidebar h3 {
            margin: 0 0 16px;
            color: var(--primary);
            font-size: 21px;
        }

        .app-sidebar .menu-group {
            margin-bottom: 12px;
            border: 1px solid var(--border);
            border-radius: 11px;
            overflow: hidden;
            background: rgba(37, 99, 235, .04);
        }

        .app-sidebar .menu-title,
        .app-sidebar .menu-group summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin: 0;
            padding: 10px 12px;
            color: var(--text);
            font-size: 13px;
            font-weight: 900;
            background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(15, 118, 110, .08));
            border: 0;
            list-style: none;
            cursor: pointer;
        }

        .app-sidebar .menu-group summary::-webkit-details-marker {
            display: none;
        }

        .app-sidebar .menu-group summary::after {
            content: "▸";
            color: var(--muted);
            transition: transform .2s ease;
        }

        .app-sidebar .menu-group[open] summary::after {
            transform: rotate(90deg);
        }

        .app-sidebar .menu-links {
            padding: 8px;
        }

        .app-sidebar .menu-group a {
            display: block;
            margin-bottom: 6px;
            padding: 9px 11px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text);
            border: 1px solid transparent;
            background: rgba(148, 163, 184, .09);
            font-weight: 800;
            transition: border-color .2s ease, background-color .2s ease, transform .2s ease;
        }

        .app-sidebar .menu-group a:hover {
            border-color: rgba(37, 99, 235, .35);
            background: rgba(37, 99, 235, .11);
            transform: translateX(-2px);
        }

        .app-sidebar .menu-group a.is-current {
            border-color: rgba(37, 99, 235, .55);
            background: linear-gradient(135deg, rgba(37, 99, 235, .2), rgba(15, 118, 110, .16));
            color: #1e3a8a;
            font-weight: 900;
            box-shadow: 0 8px 16px rgba(37, 99, 235, .18);
        }

        .app-sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .36);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
            z-index: 1200;
        }

        .app-sidebar-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        body[data-theme="dark"] .app-sidebar {
            box-shadow: -18px 0 40px rgba(0, 0, 0, .45);
        }

        body[data-theme="dark"] .app-sidebar .menu-group a {
            background: rgba(15, 27, 42, .92);
        }

        body[data-theme="dark"] .app-sidebar .menu-group a:hover {
            border-color: rgba(96, 165, 250, .38);
            background: rgba(30, 58, 95, .58);
        }

        body[data-theme="dark"] .app-sidebar .menu-group a.is-current {
            color: #dbeafe;
            border-color: rgba(96, 165, 250, .62);
            background: linear-gradient(135deg, rgba(30, 64, 175, .46), rgba(13, 148, 136, .34));
            box-shadow: 0 8px 16px rgba(15, 23, 42, .42);
        }

        @media (max-width: 640px) {
            .app-sidebar-toggle {
                top: 10px;
                right: 10px;
                min-height: 40px;
                padding: 8px 12px;
                font-size: 13px;
            }

            body.sidebar-open .app-sidebar-toggle {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }
        }
    </style>
</head>

<body>
    <button type="button" class="app-sidebar-toggle" id="appSidebarToggle" aria-controls="appSidebar" aria-expanded="false">☰ القائمة</button>

    <aside class="app-sidebar" id="appSidebar" aria-label="القائمة الجانبية">
        <div class="brand-with-logo" style="margin-bottom:10px;">
            <img src="assets/branding/logo-mark.svg" alt="شعار العيادة">
            <div class="brand-text">
                <span class="brand-title">عيادة الدكتور حيدر صباح الربيعي</span>
                <span class="brand-subtitle">ملف المريض والتنقل السريع</span>
            </div>
        </div>
        <div class="menu-group">
            <div class="menu-title">📊 الرئيسية</div>
            <div class="menu-links">
                <a href="dashboard.php">📊 لوحة التحكم</a>
            </div>
        </div>
        <details class="menu-group" data-menu-key="patients" open>
            <summary>👤 المرضى</summary>
            <div class="menu-links">
                <a href="add-patient.php">➕ إضافة مريض</a>
                <a href="main.php">👥 كل المرضى</a>
                <a href="patient-file.php?id=<?= $patientId ?>">📁 ملف المريض</a>
            </div>
        </details>
        <details class="menu-group" data-menu-key="patient-actions" open>
            <summary>🧾 المريض الحالي</summary>
            <div class="menu-links">
                <a href="patient-data.php?id_open=<?= $patientId ?>">📌 بيانات المريض</a>
                <a href="patient-file.php?id=<?= $patientId ?>">📁 ملف المريض</a>
                <a href="patient_timeline.php?id=<?= $patientId ?>">🕘 التسلسل الطبي</a>
                <a href="patient_reports.php?id=<?= $patientId ?>">📄 التقارير الطبية</a>
                <a href="treatment.php?patient_id=<?= $patientId ?>">💊 وصفة العلاج</a>
                <a href="add-va.php?id=<?= $patientId ?>">👁️ إضافة فحص النظر</a>
                <a href="add-image.php?id=<?= $patientId ?>">🖼️ إضافة صور</a>
                <a href="image-comparison.php?id=<?= $patientId ?>">🧪 مقارنة الصور</a>
            </div>
        </details>
        <details class="menu-group" data-menu-key="appointments">
            <summary>📅 المواعيد</summary>
            <div class="menu-links">
                <a href="visits.php">زيارات اليوم</a>
                <a href="followup-appointment.php?id=<?= $patientId ?>">موعد مراجعة</a>
                <a href="surgery-appointment.php?id=<?= $patientId ?>">موعد عملية</a>
                <a href="laser-appointment.php?id=<?= $patientId ?>">موعد ليزر</a>
                <a href="injection-appointment.php?id=<?= $patientId ?>">موعد حقن</a>
                <a href="operation-by-date.php">مواعيد العمليات</a>
            </div>
        </details>
        <details class="menu-group" data-menu-key="system">
            <summary>⚙️ النظام</summary>
            <div class="menu-links">
                <a href="reports.php">التقارير</a>
                <a href="settings.php">الإعدادات</a>
                <a href="logout.php">تسجيل الخروج</a>
            </div>
        </details>
    </aside>
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop"></div>

    <main class="page">
        <?php if ($flash): ?>
            <div class="card" style="margin-bottom:14px;border-color:<?= ($flash['type'] ?? '') === 'success' ? '#86efac' : '#fca5a5' ?>">
                <?= h($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>
        <section class="hero">
            <div>
                <h1 class="clinic-user-content" data-no-translate><?= h($patientName) ?></h1>
                <p>ملف المريض رقم <?= $patientId ?> | <span class="clinic-user-content" data-no-translate><?= h($row['phone_no'] ?? '') ?></span></p>
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
                <a class="visit-charity" href="visits2.php?patient_id=<?= $patientId ?>&visit_type=charity">زيارة مجانية</a>
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
                        <strong class="clinic-user-content" data-no-translate><?= h($row['age'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الجنس</span>
                        <strong class="clinic-user-content" data-no-translate><?= h($row['gender'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الموبايل</span>
                        <strong class="clinic-user-content" data-no-translate><?= h($row['phone_no'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>الموبايل البديل</span>
                        <strong class="clinic-user-content" data-no-translate><?= h($row['phone_no_alt'] ?? '') ?></strong>
                    </div>
                    <div class="info-item">
                        <span>العنوان</span>
                        <strong class="clinic-user-content" data-no-translate><?= h($row['address'] ?? '') ?></strong>
                    </div>
                    <div class="info-item wide">
                        <span>الملاحظات</span>
                        <strong class="clinic-user-content" data-no-translate><?= h($row['notes'] ?? '') ?></strong>
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
                        <a class="action-link laser" href="add-laser.php?id=<?= $patientId ?>">ليزر مباشر <span>بدون موعد</span></a>
                        <a class="action-link injection" href="add-injection.php?id=<?= $patientId ?>">حقن مباشر <span>بدون موعد</span></a>
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

    <script>
        (function() {
            const sidebar = document.getElementById('appSidebar');
            const toggle = document.getElementById('appSidebarToggle');
            const backdrop = document.getElementById('appSidebarBackdrop');
            if (!sidebar || !toggle || !backdrop) return;

            const groups = Array.from(document.querySelectorAll('#appSidebar details.menu-group[data-menu-key]'));

            function markCurrentSidebarLink() {
                const currentPath = window.location.pathname.split('/').pop().toLowerCase();
                const links = Array.from(document.querySelectorAll('#appSidebar a[href]'));

                links.forEach(link => {
                    const href = (link.getAttribute('href') || '').split('?')[0].toLowerCase();
                    if (!href) return;
                    if (href === currentPath) {
                        link.classList.add('is-current');
                    }
                });
            }

            function setupSidebarAccordion() {
                if (!groups.length) return;

                const storageKey = 'patient_data_sidebar_open_group';
                const saved = localStorage.getItem(storageKey);
                const defaultGroup = groups.find(group => group.hasAttribute('open'));

                groups.forEach(group => {
                    group.open = false;
                });

                const initialGroup = groups.find(group => group.dataset.menuKey === saved) || defaultGroup || groups[0];
                if (initialGroup) initialGroup.open = true;

                groups.forEach(group => {
                    group.addEventListener('toggle', () => {
                        if (!group.open) return;
                        groups.forEach(other => {
                            if (other !== group) other.open = false;
                        });
                        if (group.dataset.menuKey) {
                            localStorage.setItem(storageKey, group.dataset.menuKey);
                        }
                    });
                });
            }

            function setSidebar(open, saveState) {
                sidebar.classList.toggle('is-open', open);
                backdrop.classList.toggle('is-open', open);
                document.body.classList.toggle('sidebar-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', 'فتح القائمة');
                toggle.textContent = '☰ القائمة';
                if (saveState) {
                    localStorage.setItem('clinicSidebarState', open ? 'show' : 'hidden');
                }
            }

            const saved = localStorage.getItem('clinicSidebarState');
            setSidebar(saved === 'show', false);

            toggle.addEventListener('click', function() {
                setSidebar(!sidebar.classList.contains('is-open'), true);
            });

            backdrop.addEventListener('click', function() {
                setSidebar(false, true);
            });

            setupSidebarAccordion();
            markCurrentSidebarLink();
        })();
    </script>
</body>

</html>