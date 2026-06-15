<?php

include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$patients = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'done' => 0,
    'discharged' => 0,
    'without_phone' => 0,
];

$q = mysqli_query($con, "
SELECT add_patient.*,
surgery_appointment.status
FROM add_patient
LEFT JOIN surgery_appointment
ON surgery_appointment.id=(
  SELECT id FROM surgery_appointment
  WHERE patient_id=add_patient.id
  ORDER BY id DESC LIMIT 1
)
WHERE " . clinic_active_patient_where($con, 'add_patient') . "
ORDER BY add_patient.id ASC
");

while ($row = mysqli_fetch_assoc($q)) {
    $status = (string)($row['status'] ?? '');
    $patients[] = $row;
    $stats['total']++;

    if (isset($stats[$status])) {
        $stats[$status]++;
    }

    if (trim((string)($row['phone_no'] ?? '')) === '') {
        $stats['without_phone']++;
    }
}

function patient_status_label(?string $status): string
{
    if ($status === 'done') return 'منجز';
    if ($status === 'discharged') return 'مغادر';
    if ($status === 'pending') return 'قيد الانتظار';
    return 'بدون عملية';
}

function patient_status_class(?string $status): string
{
    if ($status === 'done') return 'status-done';
    if ($status === 'discharged') return 'status-discharged';
    if ($status === 'pending') return 'status-pending';
    return 'status-none';
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>بيانات المرضى</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/dark-mode.css">
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
        --teal: #0f766e;
        --amber: #d97706;
        --red: #dc2626;
        --green: #047857;
        --slate: #334155;
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

    a {
        color: inherit;
    }

    .app-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 250px minmax(0, 1fr);
    }

    .app-shell.sidebar-collapsed {
        grid-template-columns: 0 minmax(0, 1fr);
    }

    .sidebar {
        background: var(--panel);
        border-left: 1px solid var(--border);
        padding: 18px;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        transition: width .2s ease, padding .2s ease, transform .2s ease;
    }

    .sidebar.hidden {
        width: 0;
        padding: 0;
        overflow: hidden;
        border-left: 0;
    }

    .brand {
        padding: 8px 4px 18px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 18px;
    }

    .brand strong {
        display: block;
        font-size: 18px;
        font-weight: 900;
        color: var(--text);
    }

    .brand span {
        display: block;
        margin-top: 5px;
        color: var(--muted);
        font-size: 12px;
        font-weight: 700;
    }

    .menu-group {
        margin-bottom: 18px;
    }

    .menu-group span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 8px;
    }

    .menu-group a {
        display: flex;
        align-items: center;
        min-height: 38px;
        padding: 8px 10px;
        margin-bottom: 5px;
        border-radius: 9px;
        text-decoration: none;
        color: var(--text);
        font-size: 13px;
        font-weight: 800;
        transition: background .18s ease, color .18s ease;
    }

    .menu-group a:hover,
    .menu-group a.active {
        background: rgba(37, 99, 235, .1);
        color: var(--primary);
    }

    .menu-group a.danger:hover {
        background: rgba(220, 38, 38, .1);
        color: var(--red);
    }

    .main-area {
        min-width: 0;
        padding: 20px;
    }

    .topbar,
    .hero,
    .toolbar,
    .table-panel,
    .stat-card {
        background: var(--panel);
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
    }

    .topbar {
        min-height: 62px;
        border-radius: var(--radius);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }

    .topbar-actions,
    .quick-actions,
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        align-items: center;
    }

    .user-pill {
        color: var(--muted);
        font-size: 13px;
        font-weight: 800;
    }

    .icon-btn,
    .action-btn,
    .filter-chip {
        min-height: 38px;
        border: 1px solid var(--border);
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 8px 11px;
        background: var(--panel-soft);
        color: var(--text);
        font: inherit;
        font-size: 13px;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
    }

    .icon-btn {
        width: 40px;
        padding: 0;
        font-size: 16px;
    }

    .action-btn.primary,
    .filter-chip.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #ffffff;
    }

    .action-btn.success {
        background: var(--teal);
        border-color: var(--teal);
        color: #ffffff;
    }

    .action-btn.danger {
        background: var(--red);
        border-color: var(--red);
        color: #ffffff;
    }

    .hero {
        border-radius: var(--radius);
        padding: 18px;
        margin-bottom: 16px;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: minmax(260px, 1.4fr) minmax(280px, 2fr);
        gap: 16px;
        align-items: end;
    }

    .title-block span,
    .stat-card span,
    .table-head span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
    }

    .title-block h1 {
        margin: 5px 0 7px;
        font-size: 30px;
        line-height: 1.25;
        font-weight: 900;
        color: var(--text);
    }

    .title-block p {
        margin: 0;
        color: var(--muted);
        line-height: 1.7;
        font-size: 14px;
        font-weight: 700;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(110px, 1fr));
        gap: 10px;
    }

    .stat-card {
        border-radius: 10px;
        padding: 12px;
        box-shadow: none;
    }

    .stat-card strong {
        display: block;
        margin-top: 5px;
        font-size: 26px;
        line-height: 1;
        font-weight: 900;
        color: var(--text);
    }

    .toolbar {
        border-radius: var(--radius);
        padding: 14px;
        margin-bottom: 16px;
    }

    .search-wrap {
        position: relative;
        flex: 1 1 360px;
    }

    .search-wrap input {
        width: 100%;
        min-height: 44px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel-soft);
        color: var(--text);
        padding: 8px 14px 8px 42px;
        font: inherit;
        font-size: 15px;
        font-weight: 700;
        outline: none;
    }

    .search-wrap input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }

    .clear-search {
        position: absolute;
        left: 5px;
        top: 5px;
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--muted);
        cursor: pointer;
        font-size: 18px;
    }

    .filter-row {
        margin-top: 12px;
    }

    .table-panel {
        border-radius: var(--radius);
        overflow: hidden;
    }

    .table-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
    }

    .table-head h2 {
        margin: 3px 0 0;
        font-size: 20px;
        color: var(--text);
    }

    .result-count {
        color: var(--muted);
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .table-scroll {
        max-height: calc(100vh - 330px);
        overflow: auto;
    }

    table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--border);
        text-align: right;
        vertical-align: middle;
        font-size: 13px;
    }

    th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--panel-soft);
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
    }

    tbody tr {
        background: var(--panel);
    }

    tbody tr:nth-child(even) {
        background: var(--panel-soft);
    }

    tbody tr:hover {
        background: rgba(37, 99, 235, .08);
    }

    .id-cell {
        color: var(--muted);
        font-weight: 900;
        width: 70px;
    }

    .patient-link {
        color: var(--primary);
        font-size: 14px;
        font-weight: 900;
        text-decoration: none;
    }

    .patient-link:hover {
        text-decoration: underline;
    }

    .muted-cell {
        color: var(--muted);
        max-width: 250px;
        line-height: 1.55;
    }

    .phone-cell {
        direction: ltr;
        text-align: right;
        font-weight: 800;
    }

    .status-pill {
        min-width: 92px;
        display: inline-flex;
        justify-content: center;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .status-done {
        background: rgba(4, 120, 87, .12);
        color: var(--green);
    }

    .status-discharged {
        background: rgba(220, 38, 38, .12);
        color: var(--red);
    }

    .status-pending {
        background: rgba(217, 119, 6, .14);
        color: var(--amber);
    }

    .status-none {
        background: rgba(100, 116, 139, .14);
        color: var(--slate);
    }

    body.dark .status-none,
    body[data-theme="dark"] .status-none {
        color: #cbd5e1;
    }

    body[data-theme="dark"] .action-btn.primary,
    body.dark .action-btn.primary,
    body[data-theme="dark"] .filter-chip.active,
    body.dark .filter-chip.active {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: #ffffff !important;
    }

    body[data-theme="dark"] .action-btn.success,
    body.dark .action-btn.success {
        background: var(--teal) !important;
        border-color: var(--teal) !important;
        color: #ffffff !important;
    }

    body[data-theme="dark"] .action-btn.danger,
    body.dark .action-btn.danger {
        background: var(--red) !important;
        border-color: var(--red) !important;
        color: #ffffff !important;
    }

    .row-actions {
        display: flex;
        gap: 7px;
        justify-content: flex-start;
    }

    .empty-state {
        display: none;
        padding: 36px 18px;
        text-align: center;
        color: var(--muted);
        font-weight: 900;
        border-top: 1px solid var(--border);
    }

    .empty-state.is-visible {
        display: block;
    }

    .notice {
        margin: 0 0 14px;
        padding: 11px 13px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-weight: 800;
    }

    .notice.success {
        color: #166534;
        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    .notice.error {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
    }

    @media (max-width: 1100px) {
        .app-shell {
            grid-template-columns: 1fr;
        }

        .sidebar {
            position: fixed;
            z-index: 20;
            inset: 0 auto 0 0;
            width: min(280px, 88vw);
            height: 100vh;
            transform: translateX(0);
        }

        .sidebar.hidden {
            width: min(280px, 88vw);
            padding: 18px;
            border-left: 1px solid var(--border);
            transform: translateX(-100%);
        }

        .hero-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .main-area {
            padding: 12px;
        }

        .topbar,
        .table-head {
            align-items: stretch;
            flex-direction: column;
        }

        .title-block h1 {
            font-size: 24px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filter-chip,
        .action-btn {
            flex: 1 1 auto;
        }

        .table-scroll {
            max-height: none;
        }
    }
</style>

<?php $flash = clinic_take_flash(); ?>
<body>
    <div class="app-shell sidebar-collapsed" id="appShell">
        <aside class="sidebar hidden" id="sidebar">
            <div class="brand">
                <strong>عيادة الدكتور حيدر</strong>
                <span>نظام إدارة المرضى والمواعيد</span>
            </div>

            <div class="menu-group">
                <span>الرئيسية</span>
                <a href="dashboard.php">لوحة التحكم</a>
            </div>

            <div class="menu-group">
                <span>المرضى</span>
                <a href="add-patient.php">إضافة مريض</a>
                <a class="active" href="main.php">بيانات المرضى</a>
                <a href="archived-patients.php">أرشيف المرضى</a>
                <a href="data-quality.php">جودة البيانات</a>
                <a href="followups.php">المتابعة</a>
            </div>

            <div class="menu-group">
                <span>المواعيد</span>
                <a href="work-queue.php">قائمة عمل اليوم</a>
                <a href="visits.php">زيارات اليوم</a>
                <a href="followup-appointment.php">إعطاء موعد مراجعة</a>
                <a href="expected_appointments.php">المواعيد المتوقعة</a>
            </div>

            <div class="menu-group">
                <span>العمليات</span>
                <a href="operation-by-date.php">مواعيد العمليات</a>
                <a href="confirmed-list.php">قوائم العمليات</a>
                <a href="import_surgery_excel.php">استيراد العمليات</a>
            </div>

            <div class="menu-group">
                <span>النظام</span>
                <a href="reports.php">التقارير</a>
                <a href="common-medicines.php">الأدوية الأكثر استعمالا</a>
                <a href="settings.php">الإعدادات</a>
                <a href="logout.php" class="danger">تسجيل الخروج</a>
            </div>
        </aside>

        <main class="main-area">
            <?php if ($flash): ?>
                <div class="notice <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>">
                    <?= h($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <header class="topbar">
                <div class="topbar-actions">
                    <button class="icon-btn" type="button" onclick="toggleSidebar()" id="sidebarToggle" aria-label="إظهار القائمة">☰</button>
                    <button class="icon-btn" type="button" id="themeToggle" aria-label="تبديل الوضع">◐</button>
                </div>
                <div class="user-pill">مرحبا <?= h($_SESSION['name'] ?? 'المستخدم') ?></div>
            </header>

            <section class="hero">
                <div class="hero-grid">
                    <div class="title-block">
                        <span>سجل المرضى</span>
                        <h1>بيانات المرضى النشطين</h1>
                        <p>بحث سريع، تصفية حسب حالة آخر عملية، وروابط مباشرة لفتح ملف المريض أو جدول المتابعة.</p>
                    </div>

                    <div class="stats-grid" aria-label="ملخص المرضى">
                        <div class="stat-card"><span>إجمالي المرضى</span><strong><?= (int)$stats['total'] ?></strong></div>
                        <div class="stat-card"><span>قيد الانتظار</span><strong><?= (int)$stats['pending'] ?></strong></div>
                        <div class="stat-card"><span>منجز</span><strong><?= (int)$stats['done'] ?></strong></div>
                        <div class="stat-card"><span>مغادر</span><strong><?= (int)$stats['discharged'] ?></strong></div>
                        <div class="stat-card"><span>بدون هاتف</span><strong><?= (int)$stats['without_phone'] ?></strong></div>
                    </div>
                </div>
            </section>

            <section class="toolbar" aria-label="أدوات المرضى">
                <div class="quick-actions">
                    <a class="action-btn primary" href="add-patient.php">إضافة مريض</a>
                    <a class="action-btn success" href="visits.php">زيارات اليوم</a>
                    <a class="action-btn" href="archived-patients.php">الأرشيف</a>
                </div>

                <div class="filter-row">
                    <div class="search-wrap">
                        <input type="text" id="searchInput" placeholder="ابحث بالاسم، الهاتف، العنوان، الملاحظات..." autocomplete="off">
                        <button class="clear-search" type="button" onclick="clearSearch()" aria-label="مسح البحث">×</button>
                    </div>
                    <button class="filter-chip active" type="button" data-status="all">الكل</button>
                    <button class="filter-chip" type="button" data-status="pending">قيد الانتظار</button>
                    <button class="filter-chip" type="button" data-status="done">منجز</button>
                    <button class="filter-chip" type="button" data-status="discharged">مغادر</button>
                    <button class="filter-chip" type="button" data-status="none">بدون عملية</button>
                </div>
            </section>

            <section class="table-panel">
                <div class="table-head">
                    <div>
                        <span>قائمة المرضى</span>
                        <h2>المرضى حسب رقم التسجيل</h2>
                    </div>
                    <div class="result-count" id="resultCount"><?= (int)$stats['total'] ?> مريض ظاهر</div>
                </div>

                <div class="table-scroll">
                    <table id="patientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم المريض</th>
                                <th>الحالة</th>
                                <th>الملاحظات</th>
                                <th>العمر</th>
                                <th>الجنس</th>
                                <th>الهاتف</th>
                                <th>العنوان</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <?php
                                $status = trim((string)($patient['status'] ?? ''));
                                $statusKey = $status === '' ? 'none' : $status;
                                $statusClass = patient_status_class($status);
                                $statusLabel = patient_status_label($status);
                                $patientId = (int)$patient['id'];
                                ?>
                                <tr data-status="<?= h($statusKey) ?>">
                                    <td class="id-cell"><?= $patientId ?></td>
                                    <td>
                                        <a class="patient-link" href="patient_timeline.php?id=<?= $patientId ?>">
                                            <?= h($patient['full_name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="status-pill <?= h($statusClass) ?>"><?= h($statusLabel) ?></span></td>
                                    <td class="muted-cell"><?= h($patient['notes'] ?: '-') ?></td>
                                    <td><?= h($patient['age'] ?: '-') ?></td>
                                    <td><?= h($patient['gender'] ?: '-') ?></td>
                                    <td class="phone-cell"><?= h($patient['phone_no'] ?: '-') ?></td>
                                    <td class="muted-cell"><?= h($patient['address'] ?: '-') ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="action-btn primary" href="patient-data.php?id_open=<?= $patientId ?>">فتح</a>
                                            <button class="action-btn danger" type="button" onclick="confirmDelete(<?= $patientId ?>)">حذف</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="empty-state" id="emptyState">لا توجد نتائج مطابقة للبحث أو التصفية الحالية.</div>
            </section>
        </main>
    </div>

    <script>
        const searchInput = document.getElementById("searchInput");
        const rows = Array.from(document.querySelectorAll("#patientsTable tbody tr"));
        const resultCount = document.getElementById("resultCount");
        const emptyState = document.getElementById("emptyState");
        const filterButtons = Array.from(document.querySelectorAll(".filter-chip"));
        let activeStatus = "all";

        function normalizeText(value) {
            return value.toLowerCase().trim();
        }

        function applyFilters() {
            const term = normalizeText(searchInput.value);
            let visible = 0;

            rows.forEach(row => {
                const matchesStatus = activeStatus === "all" || row.dataset.status === activeStatus;
                const matchesSearch = !term || normalizeText(row.textContent).includes(term);
                const shouldShow = matchesStatus && matchesSearch;
                row.style.display = shouldShow ? "" : "none";
                if (shouldShow) visible++;
            });

            resultCount.textContent = visible + " مريض ظاهر";
            emptyState.classList.toggle("is-visible", visible === 0);
        }

        function clearSearch() {
            searchInput.value = "";
            searchInput.focus();
            applyFilters();
        }

        function confirmDelete(id) {
            if (confirm("هل تريد نقل هذا المريض إلى الأرشيف؟")) {
                const form = document.createElement("form");
                form.method = "post";
                form.action = "delete-patient.php";

                const idInput = document.createElement("input");
                idInput.type = "hidden";
                idInput.name = "id_delete";
                idInput.value = String(id);

                const csrfInput = document.createElement("input");
                csrfInput.type = "hidden";
                csrfInput.name = "csrf_token";
                csrfInput.value = <?= json_encode(clinic_csrf_token()) ?>;

                form.append(idInput, csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleSidebar() {
            const shell = document.getElementById("appShell");
            const sidebar = document.getElementById("sidebar");
            const button = document.getElementById("sidebarToggle");
            sidebar.classList.toggle("hidden");
            shell.classList.toggle("sidebar-collapsed", sidebar.classList.contains("hidden"));
            button.setAttribute("aria-label", sidebar.classList.contains("hidden") ? "إظهار القائمة" : "إخفاء القائمة");
        }

        filterButtons.forEach(button => {
            button.addEventListener("click", () => {
                activeStatus = button.dataset.status;
                filterButtons.forEach(item => item.classList.toggle("active", item === button));
                applyFilters();
            });
        });

        searchInput.addEventListener("input", applyFilters);
    </script>
</body>

</html>
