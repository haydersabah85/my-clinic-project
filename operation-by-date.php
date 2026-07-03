<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

$dates = [];
$dateQuery = $con->query("
    SELECT date, SUM(total) AS total
    FROM (
        SELECT date, COUNT(*) total FROM surgery_appointment WHERE date IS NOT NULL AND date <> '0000-00-00' AND status = 'pending' GROUP BY date
        UNION ALL
        SELECT date, COUNT(*) total FROM laser_appointment WHERE date IS NOT NULL AND date <> '0000-00-00' AND status = 'pending' GROUP BY date
        UNION ALL
        SELECT date, COUNT(*) total FROM injection_appointment WHERE date IS NOT NULL AND date <> '0000-00-00' AND status = 'pending' GROUP BY date
    ) all_dates
    GROUP BY date
    ORDER BY date DESC
");

while ($dateQuery && $row = $dateQuery->fetch_assoc()) {
    $dates[] = $row;
}

$selectedDate = $_GET['date'] ?? ($dates[0]['date'] ?? date('Y-m-d'));

$summaryStmt = $con->prepare("
    SELECT
        (SELECT COUNT(*) FROM surgery_appointment WHERE date = ? AND status = 'pending') surgery_count,
        (SELECT COUNT(*) FROM laser_appointment WHERE date = ? AND status = 'pending') laser_count,
        (SELECT COUNT(*) FROM injection_appointment WHERE date = ? AND status = 'pending') injection_count,
        (
            (SELECT COUNT(*) FROM surgery_appointment WHERE date = ? AND status = 'pending' AND attendance_status = 1) +
            (SELECT COUNT(*) FROM laser_appointment WHERE date = ? AND status = 'pending' AND attendance_status = 1) +
            (SELECT COUNT(*) FROM injection_appointment WHERE date = ? AND status = 'pending' AND attendance_status = 1)
        ) confirmed_count
");
$summaryStmt->bind_param("ssssss", $selectedDate, $selectedDate, $selectedDate, $selectedDate, $selectedDate, $selectedDate);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc() ?: [
    'surgery_count' => 0,
    'laser_count' => 0,
    'injection_count' => 0,
    'confirmed_count' => 0,
];

$totalCount = (int)$summary['surgery_count'] + (int)$summary['laser_count'] + (int)$summary['injection_count'];
$pendingCount = max(0, $totalCount - (int)$summary['confirmed_count']);
?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Board</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
</head>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap');

    :root {
        --bg: #f4f7fb;
        --panel: #ffffff;
        --panel-soft: #f8fafc;
        --text: #172033;
        --muted: #64748b;
        --border: #dbe7ef;
        --teal: #0f766e;
        --blue: #2563eb;
        --amber: #d97706;
        --red: #dc2626;
        --green: #047857;
        --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 24px;
        font-family: 'Cairo', Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    .page {
        max-width: 1800px;
        margin: 0 auto;
    }

    .board-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
    }

    .board-title h1 {
        margin: 0;
        font-size: 30px;
        font-weight: 900;
    }

    .board-title p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 14px;
        font-weight: 700;
    }

    .top-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .nav-btn,
    .tool-btn {
        min-height: 42px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--panel);
        color: var(--text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 900;
        cursor: pointer;
        font-family: inherit;
    }

    .tool-btn.primary {
        background: var(--teal);
        border-color: var(--teal);
        color: #ffffff;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(130px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .summary-card {
        border: 1px solid var(--border);
        border-radius: 16px;
        background: var(--panel);
        padding: 14px 16px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }

    .summary-card span {
        color: var(--muted);
        font-size: 12px;
        font-weight: 900;
    }

    .summary-card strong {
        display: block;
        margin-top: 4px;
        font-size: 28px;
        line-height: 1;
        font-weight: 900;
    }

    .control-panel {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: var(--panel);
        padding: 14px;
        box-shadow: var(--shadow);
        margin-bottom: 18px;
    }

    .date-strip {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 10px;
        scrollbar-width: thin;
    }

    .date-btn {
        min-width: 150px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--panel-soft);
        color: var(--text);
        cursor: pointer;
        padding: 10px 12px;
        text-align: right;
        font-family: inherit;
        transition: all 0.2s ease;
    }

    .date-btn span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 800;
    }

    .date-btn strong {
        display: block;
        margin-top: 2px;
        font-size: 15px;
        font-weight: 900;
    }

    .date-btn.active,
    .date-btn:hover {
        background: var(--teal);
        border-color: var(--teal);
        color: #ffffff;
    }

    .date-btn.active span,
    .date-btn:hover span {
        color: rgba(255, 255, 255, 0.78);
    }

    .tools-row {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) auto auto;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
    }

    .status-filters {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--panel-soft);
    }

    .status-filter {
        min-height: 34px;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: var(--muted);
        cursor: pointer;
        padding: 6px 10px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .status-filter.active {
        background: var(--panel);
        color: var(--teal);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .search-box {
        min-height: 44px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--panel-soft);
        color: var(--text);
        padding: 10px 14px;
        font-family: inherit;
        font-size: 14px;
        outline: none;
    }

    .search-box:focus {
        background: #ffffff;
        border-color: var(--teal);
        box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
    }

    #operations_result {
        min-height: 300px;
    }

    .loading-state {
        border: 1px dashed var(--border);
        border-radius: 18px;
        background: var(--panel);
        padding: 36px;
        text-align: center;
        color: var(--muted);
        font-weight: 900;
    }

    body[data-theme="dark"] {
        --bg: #0f1412;
        --panel: #17211d;
        --panel-soft: #111a17;
        --text: #edf4ef;
        --muted: #a8b8af;
        --border: rgba(167, 190, 177, 0.2);
        --teal: #5fd1b7;
        --blue: #79a8ff;
        --shadow: 0 18px 45px rgba(0, 0, 0, 0.38);
    }

    body[data-theme="dark"] .summary-card,
    body[data-theme="dark"] .control-panel,
    body[data-theme="dark"] .day-head,
    body[data-theme="dark"] .op-column {
        background: rgba(23, 33, 29, 0.94) !important;
        border-color: rgba(167, 190, 177, 0.2) !important;
        box-shadow: var(--shadow);
    }

    body[data-theme="dark"] .summary-card {
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.28);
    }

    body[data-theme="dark"] .date-btn,
    body[data-theme="dark"] .search-box,
    body[data-theme="dark"] .status-filters,
    body[data-theme="dark"] .op-meta div,
    body[data-theme="dark"] .op-type-head {
        background: rgba(17, 26, 23, 0.96) !important;
        border-color: rgba(167, 190, 177, 0.18) !important;
        color: var(--text);
    }

    body[data-theme="dark"] .date-btn.active,
    body[data-theme="dark"] .date-btn:hover,
    body[data-theme="dark"] .status-filter.active {
        background: rgba(95, 209, 183, 0.16) !important;
        border-color: rgba(95, 209, 183, 0.44) !important;
        color: #dffbf4;
        box-shadow: 0 10px 24px rgba(12, 68, 57, 0.28);
    }

    body[data-theme="dark"] .status-filter {
        color: var(--muted);
    }

    body[data-theme="dark"] .op-card,
    body[data-theme="dark"] .empty-column,
    body[data-theme="dark"] .loading-state {
        background: rgba(10, 17, 15, 0.76) !important;
        border-color: rgba(167, 190, 177, 0.17) !important;
    }

    body[data-theme="dark"] .serial,
    body[data-theme="dark"] .op-type-head span {
        background: rgba(121, 168, 255, 0.16);
        color: #b9d0ff;
    }

    body[data-theme="dark"] .status.confirmed {
        background: rgba(52, 211, 153, 0.16);
        color: #8df0c9;
    }

    body[data-theme="dark"] .status.waiting {
        background: rgba(251, 191, 36, 0.16);
        color: #f8d984;
    }

    body[data-theme="dark"] .eye-od {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
    }

    body[data-theme="dark"] .eye-os {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
    }

    body[data-theme="dark"] .eye-ou {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: #ffffff;
    }

    @media print {

        .top-actions,
        .control-panel,
        .nav-btn,
        .tool-btn,
        .op-actions {
            display: none !important;
        }

        body {
            background: #ffffff;
            padding: 0;
        }
    }

    @media (max-width: 900px) {
        body {
            padding: 14px;
        }

        .board-header,
        .tools-row {
            grid-template-columns: 1fr;
            flex-direction: column;
            align-items: stretch;
        }

        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<body>
    <div class="page">
        <header class="board-header">
            <div class="board-title">
                <h1>Daily Operations Board</h1>
                <p>Manage surgery, laser, and injection appointments from one screen.</p>
            </div>
            <nav class="top-actions">
                <a class="nav-btn" href="dashboard.php">Dashboard</a>
                <a class="nav-btn" href="confirmed-list.php">Confirmed list</a>
                <a class="nav-btn" href="treatment-types.php">Treatment types</a>
                <a class="nav-btn" href="main.php">إجراء طارئ مباشر (بدون موعد)</a>
                <button class="tool-btn" type="button" onclick="window.print()">Print</button>
            </nav>
        </header>

        <section class="summary-grid" id="summaryGrid">
            <div class="summary-card"><span>Selected date</span><strong id="selectedDateLabel"><?= h($selectedDate) ?></strong></div>
            <div class="summary-card"><span>Surgeries</span><strong id="surgeryCount"><?= (int)$summary['surgery_count'] ?></strong></div>
            <div class="summary-card"><span>Laser</span><strong id="laserCount"><?= (int)$summary['laser_count'] ?></strong></div>
            <div class="summary-card"><span>Injections</span><strong id="injectionCount"><?= (int)$summary['injection_count'] ?></strong></div>
            <div class="summary-card"><span>Pending confirm</span><strong id="pendingCount"><?= $pendingCount ?></strong></div>
        </section>

        <section class="control-panel">
            <div class="date-strip" aria-label="Appointment dates">
                <?php if (empty($dates)): ?>
                    <button class="date-btn active" type="button" data-date="<?= h($selectedDate) ?>">
                        <span>No pending dates</span>
                        <strong><?= h($selectedDate) ?></strong>
                    </button>
                <?php else: ?>
                    <?php foreach ($dates as $dateRow): ?>
                        <button class="date-btn <?= $dateRow['date'] === $selectedDate ? 'active' : '' ?>" type="button" data-date="<?= h($dateRow['date']) ?>">
                            <span><?= (int)$dateRow['total'] ?> appointments</span>
                            <strong><?= h($dateRow['date']) ?></strong>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tools-row">
                <input class="search-box" id="daySearch" type="search" placeholder="Search name, phone, serial, eye, or procedure">
                <div class="status-filters" aria-label="Attendance filters">
                    <button class="status-filter active" type="button" data-status="all">All</button>
                    <button class="status-filter" type="button" data-status="waiting">Need confirm</button>
                    <button class="status-filter" type="button" data-status="confirmed">Confirmed</button>
                </div>
                <button class="tool-btn primary" type="button" id="reloadBtn">Reload selected day</button>
            </div>
        </section>

        <main id="operations_result">
            <div class="loading-state">Loading selected day...</div>
        </main>
    </div>

    <script>
        let selectedDate = <?= json_encode($selectedDate) ?>;
        let selectedStatus = 'all';

        function updateUrl(date) {
            const url = new URL(window.location.href);
            url.searchParams.set('date', date);
            history.replaceState(null, '', url.toString());
        }

        function setActiveDate(date) {
            document.querySelectorAll('.date-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.date === date);
            });
            document.getElementById('selectedDateLabel').textContent = date;
        }

        function applySearch() {
            const query = document.getElementById('daySearch').value.trim().toLowerCase();
            document.querySelectorAll('.op-card').forEach(card => {
                const matchesSearch = card.dataset.search.includes(query);
                const matchesStatus = selectedStatus === 'all' || card.dataset.status === selectedStatus;
                card.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        function applySummary(data) {
            document.getElementById('surgeryCount').textContent = data.surgery || 0;
            document.getElementById('laserCount').textContent = data.laser || 0;
            document.getElementById('injectionCount').textContent = data.injection || 0;
            document.getElementById('pendingCount').textContent = data.pending || 0;
        }

        function loadOperations(date) {
            selectedDate = date;
            setActiveDate(date);
            updateUrl(date);

            const result = document.getElementById('operations_result');
            result.innerHTML = '<div class="loading-state">Loading selected day...</div>';

            fetch('load_operations.php?date=' + encodeURIComponent(date), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    result.innerHTML = html;
                    const payload = document.getElementById('operationSummaryPayload');
                    if (payload) {
                        applySummary(JSON.parse(payload.textContent));
                    }
                    applySearch();
                })
                .catch(() => {
                    result.innerHTML = '<div class="loading-state">Could not load operations for this date.</div>';
                });
        }

        document.querySelectorAll('.date-btn').forEach(btn => {
            btn.addEventListener('click', () => loadOperations(btn.dataset.date));
        });

        document.getElementById('reloadBtn').addEventListener('click', () => loadOperations(selectedDate));
        document.getElementById('daySearch').addEventListener('input', applySearch);
        document.querySelectorAll('.status-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedStatus = btn.dataset.status;
                document.querySelectorAll('.status-filter').forEach(filter => filter.classList.remove('active'));
                btn.classList.add('active');
                applySearch();
            });
        });
        window.addEventListener('DOMContentLoaded', () => loadOperations(selectedDate));
    </script>
</body>

</html>