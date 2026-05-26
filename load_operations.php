<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

$date = $_GET['date'] ?? date('Y-m-d');

function fetch_operation_rows(mysqli $con, string $date, string $kind): array
{
    $config = [
        'surgery' => [
            'table' => 'surgery_appointment',
            'type_column' => 'surgery_type',
            'edit' => 'edit-surgery-appointment.php',
            'decision' => 'process_decision_surgery.php',
            'delete' => 'delete-surgery-appointment.php',
        ],
        'laser' => [
            'table' => 'laser_appointment',
            'type_column' => 'laser_type',
            'edit' => 'edit-laser-appointment.php',
            'decision' => 'process_decision_laser.php',
            'delete' => 'delete-laser-appointment.php',
        ],
        'injection' => [
            'table' => 'injection_appointment',
            'type_column' => 'injection_type',
            'edit' => 'edit-injection-appointment.php',
            'decision' => 'process_decision_injection.php',
            'delete' => 'delete-injection-appointment.php',
        ],
    ];

    $meta = $config[$kind];
    $table = $meta['table'];
    $typeColumn = $meta['type_column'];

    $stmt = $con->prepare("
        SELECT
            a.id,
            a.patient_id,
            a.serial_no,
            p.full_name,
            a.eye,
            a.$typeColumn AS operation_type,
            a.notes,
            a.phone,
            a.phone_alt,
            a.attendance_status,
            a.date
        FROM $table a
        JOIN add_patient p ON a.patient_id = p.id
        WHERE a.date = ? AND a.status = 'pending'
        ORDER BY a.serial_no ASC, a.id ASC
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();

    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['_kind'] = $kind;
        $row['_edit'] = $meta['edit'];
        $row['_decision'] = $meta['decision'];
        $row['_delete'] = $meta['delete'];
        $rows[] = $row;
    }

    return $rows;
}

function render_operation_column(string $title, string $kind, array $rows, string $date): void
{
    echo "<section class='op-column $kind'>";
    echo "<div class='op-column-head'><div><span>" . count($rows) . " cases</span><h2>" . h($title) . "</h2></div></div>";

    if (empty($rows)) {
        echo "<p class='empty-column'>No pending " . h(strtolower($title)) . " appointments for this date.</p>";
        echo "</section>";
        return;
    }

    $groups = [];
    foreach ($rows as $row) {
        $groupName = trim((string)($row['operation_type'] ?? ''));
        if ($groupName === '') {
            $groupName = 'Unspecified';
        }
        $groups[$groupName][] = $row;
    }
    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

    echo "<div class='op-type-groups'>";
    foreach ($groups as $groupName => $groupRows) {
        echo "<section class='op-type-group'>";
        echo "<div class='op-type-head'><strong>" . h($groupName) . "</strong><span>" . count($groupRows) . "</span></div>";
        echo "<div class='op-list'>";

    foreach ($groupRows as $row) {
        $eye = strtoupper(trim((string)$row['eye']));
        $eyeClass = '';
        if ($eye === 'OD') $eyeClass = 'eye-od';
        elseif ($eye === 'OS') $eyeClass = 'eye-os';
        elseif ($eye === 'OU') $eyeClass = 'eye-ou';

        $confirmed = (int)$row['attendance_status'] === 1;
        $statusClass = $confirmed ? 'confirmed' : 'waiting';
        $statusText = $confirmed ? 'Confirmed' : 'Needs confirmation';
        $searchText = strtolower(implode(' ', [
            $row['serial_no'],
            $row['full_name'],
            $row['eye'],
            $row['operation_type'],
            $row['notes'],
            $row['phone'],
            $row['phone_alt'],
            $kind,
        ]));

        echo "<article class='op-card' data-status='" . h($statusClass) . "' data-search='" . h($searchText) . "'>";
        echo "<div class='op-card-top'>";
        echo "<span class='serial'>#" . h($row['serial_no'] ?: '-') . "</span>";
        echo "<span class='status $statusClass'>" . h($statusText) . "</span>";
        echo "</div>";
        echo "<h3>" . h($row['full_name']) . "</h3>";
        echo "<div class='op-type'>" . h($row['operation_type'] ?: '-') . "</div>";
        echo "<div class='op-meta'>";
        echo "<div><span>Eye</span><strong><span class='eye-badge $eyeClass'>" . h($eye ?: '-') . "</span></strong></div>";
        echo "<div><span>Phone</span><strong>" . h($row['phone'] ?: '-') . "</strong></div>";
        echo "<div><span>Alt phone</span><strong>" . h($row['phone_alt'] ?: '-') . "</strong></div>";
        echo "</div>";
        echo "<p class='op-note'>" . nl2br(h($row['notes'] ?: 'No notes.')) . "</p>";
        echo "<div class='op-actions'>";
        if (!$confirmed) {
            echo "<a class='action confirm' href='confirm-attendance.php?id=" . h($row['id']) . "&date=" . h($date) . "'>Confirm</a>";
        }
        echo "<a class='action done' href='" . h($row['_decision'])
            . "?id=" . h($row['patient_id'])
            . "&appointment_id=" . h($row['id'])
            . "&appointment_date=" . h($date)
            . "'>Add result</a>";
        echo "<a class='action edit' href='" . h($row['_edit']) . "?id=" . h($row['id']) . "'>Edit</a>";
        echo "<a class='action delete' onclick=\"return confirm('Delete this appointment?')\" href='" . h($row['_delete']) . "?id=" . h($row['id']) . "'>Delete</a>";
        echo "</div>";
        echo "</article>";
    }
        echo "</div>";
        echo "</section>";
    }
    echo "</div>";
    echo "</section>";
}

$surgeryRows = fetch_operation_rows($con, $date, 'surgery');
$laserRows = fetch_operation_rows($con, $date, 'laser');
$injectionRows = fetch_operation_rows($con, $date, 'injection');

$confirmedCount = 0;
foreach (array_merge($surgeryRows, $laserRows, $injectionRows) as $row) {
    if ((int)$row['attendance_status'] === 1) {
        $confirmedCount++;
    }
}

$totalCount = count($surgeryRows) + count($laserRows) + count($injectionRows);
$summary = [
    'surgery' => count($surgeryRows),
    'laser' => count($laserRows),
    'injection' => count($injectionRows),
    'confirmed' => $confirmedCount,
    'pending' => max(0, $totalCount - $confirmedCount),
];
?>

<style>
    .operations-day {
        display: grid;
        gap: 14px;
    }

    .day-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 18px;
        background: var(--panel, #ffffff);
        padding: 16px 18px;
        box-shadow: var(--shadow, 0 18px 45px rgba(15, 23, 42, 0.08));
    }

    .day-head h2 {
        margin: 0;
        color: var(--text, #172033);
        font-size: 22px;
        font-weight: 900;
    }

    .day-head span {
        color: var(--muted, #64748b);
        font-size: 13px;
        font-weight: 800;
    }

    .columns {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .op-column {
        min-width: 0;
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 18px;
        background: var(--panel, #ffffff);
        padding: 14px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }

    .op-column-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border, #dbe7ef);
    }

    .op-column-head span {
        color: var(--muted, #64748b);
        font-size: 12px;
        font-weight: 900;
    }

    .op-column-head h2 {
        margin: 2px 0 0;
        color: var(--text, #172033);
        font-size: 18px;
        font-weight: 900;
    }

    .op-list {
        display: grid;
        gap: 12px;
    }

    .op-type-groups {
        display: grid;
        gap: 14px;
    }

    .op-type-group {
        display: grid;
        gap: 10px;
    }

    .op-type-head {
        position: sticky;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 14px;
        background: var(--panel, #ffffff);
        padding: 9px 11px;
    }

    .op-type-head strong {
        color: var(--text, #172033);
        font-size: 14px;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .op-type-head span {
        min-width: 28px;
        min-height: 28px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 900;
        flex: 0 0 auto;
    }

    .op-card {
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 16px;
        background: var(--panel-soft, #f8fafc);
        padding: 13px;
    }

    .op-card-top,
    .op-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .serial,
    .status,
    .eye-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 12px;
        font-weight: 900;
    }

    .serial {
        background: #e0f2fe;
        color: #075985;
    }

    .status.confirmed {
        background: #dcfce7;
        color: #047857;
    }

    .status.waiting {
        background: #fff7ed;
        color: #c2410c;
    }

    .op-card h3 {
        margin: 10px 0 4px;
        color: var(--text, #172033);
        font-size: 17px;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .op-type {
        color: var(--blue, #2563eb);
        font-size: 13px;
        font-weight: 900;
        margin-bottom: 10px;
        overflow-wrap: anywhere;
    }

    .op-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .op-meta div {
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 12px;
        background: var(--panel, #ffffff);
        padding: 8px;
        min-width: 0;
    }

    .op-meta span {
        display: block;
        color: var(--muted, #64748b);
        font-size: 11px;
        font-weight: 900;
    }

    .op-meta strong {
        display: block;
        color: var(--text, #172033);
        font-size: 13px;
        font-weight: 900;
        margin-top: 2px;
        overflow-wrap: anywhere;
    }

    .eye-badge {
        min-width: 42px;
        padding: 4px 8px;
    }

    .eye-od {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .eye-os {
        background: #ccfbf1;
        color: #0f766e;
    }

    .eye-ou {
        background: #fef3c7;
        color: #92400e;
    }

    .op-note {
        margin: 10px 0;
        color: var(--text, #172033);
        font-size: 13px;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .action {
        flex: 1 1 auto;
        min-height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 9px;
        text-decoration: none;
        color: #ffffff;
        font-size: 12px;
        font-weight: 900;
    }

    .action.confirm {
        background: #047857;
    }

    .action.done {
        background: #2563eb;
    }

    .action.edit {
        background: #64748b;
    }

    .action.delete {
        background: #dc2626;
    }

    .empty-column {
        margin: 0;
        border: 1px dashed var(--border, #dbe7ef);
        border-radius: 14px;
        background: var(--panel-soft, #f8fafc);
        color: var(--muted, #64748b);
        padding: 18px;
        text-align: center;
        font-weight: 800;
    }

    body[data-theme="dark"] .op-column,
    body[data-theme="dark"] .day-head,
    body[data-theme="dark"] .op-meta div,
    body[data-theme="dark"] .op-type-head {
        background: rgba(15, 27, 42, 0.96);
        border-color: rgba(148, 163, 184, 0.18);
    }

    body[data-theme="dark"] .op-card,
    body[data-theme="dark"] .empty-column {
        background: rgba(2, 6, 23, 0.24);
        border-color: rgba(148, 163, 184, 0.18);
    }

    @media (max-width: 1250px) {
        .columns {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .day-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .op-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<script type="application/json" id="operationSummaryPayload"><?= json_encode($summary) ?></script>

<section class="operations-day">
    <div class="day-head">
        <div>
            <span>Selected operation date</span>
            <h2><?= h($date) ?></h2>
        </div>
        <span><?= $totalCount ?> pending appointments, <?= $confirmedCount ?> confirmed</span>
    </div>

    <div class="columns">
        <?php render_operation_column('Surgery', 'surgery', $surgeryRows, $date); ?>
        <?php render_operation_column('Laser', 'laser', $laserRows, $date); ?>
        <?php render_operation_column('Injection', 'injection', $injectionRows, $date); ?>
    </div>
</section>
