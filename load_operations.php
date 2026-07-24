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
    $readinessSelect = $kind === 'surgery' ? 'a.readiness_json' : 'NULL AS readiness_json';

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
            $readinessSelect,
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
    echo "<div class='op-column-head'><div><span>" . count($rows) . " حالة</span><h2>" . h($title) . "</h2></div></div>";

    if (empty($rows)) {
        echo "<p class='empty-column'>لا توجد مواعيد معلقة لهذا الإجراء في التاريخ المحدد.</p>";
        echo "</section>";
        return;
    }

    $groups = [];
    foreach ($rows as $row) {
        $groupName = trim((string)($row['operation_type'] ?? ''));
        if ($groupName === '') {
            $groupName = 'غير محدد';
        }
        $groups[$groupName][] = $row;
    }
    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

    echo "<div class='op-type-groups'>";
    foreach ($groups as $groupName => $groupRows) {
        usort($groupRows, static function (array $a, array $b): int {
            $aSerial = isset($a['serial_no']) ? (int)$a['serial_no'] : 0;
            $bSerial = isset($b['serial_no']) ? (int)$b['serial_no'] : 0;
            if ($aSerial === $bSerial) {
                return ((int)$a['id']) <=> ((int)$b['id']);
            }
            return $aSerial <=> $bSerial;
        });

        echo "<section class='op-type-group'>";
        echo "<div class='op-type-head'><strong>" . h($groupName) . "</strong><span>" . count($groupRows) . "</span></div>";
        echo "<div class='op-list'>";

        $counter = 1;
        foreach ($groupRows as $row) {
            $eye = strtoupper(trim((string)$row['eye']));
            $eyeClass = '';
            if ($eye === 'OD') $eyeClass = 'eye-od';
            elseif ($eye === 'OS') $eyeClass = 'eye-os';
            elseif ($eye === 'OU') $eyeClass = 'eye-ou';

            $confirmed = (int)$row['attendance_status'] === 1;
            $readiness = json_decode((string)($row['readiness_json'] ?? ''), true);
            $readiness = is_array($readiness) ? $readiness : [];
            $readyCount = count(array_filter($readiness));
            $readyTotal = 8;
            $readinessClass = $readyCount === $readyTotal ? 'confirmed' : 'waiting';
            $readinessText = $readyCount === $readyTotal
                ? 'الجاهزية مكتملة'
                : "الجاهزية $readyCount/$readyTotal";
            $statusClass = $confirmed ? 'confirmed' : 'waiting';
            $statusText = $confirmed ? 'مؤكد' : 'بحاجة إلى تأكيد';
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

            echo "<article class='op-card op-card-" . h($statusClass) . "' data-status='" . h($statusClass) . "' data-search='" . h($searchText) . "'>";
            if ($confirmed) {
                echo "<div class='confirmed-checkmark' aria-hidden='true'><span>📞</span><span>✓</span></div>";
            }
            echo "<div class='op-card-top'>";
            echo "<span class='serial'>#" . h((string)$counter) . "</span>";
            echo "<span class='status $statusClass'>" . h($statusText) . "</span>";
            if ($kind === 'surgery') {
                echo "<span class='status $readinessClass'>" . h($readinessText) . "</span>";
            }
            echo "</div>";
            echo "<h3>" . h($row['full_name']) . "</h3>";
            echo "<div class='op-type'>" . h($row['operation_type'] ?: '-') . "</div>";
            echo "<div class='op-meta'>";
            echo "<div><span>العين</span><strong><span class='eye-badge $eyeClass'>" . h($eye ?: '-') . "</span></strong></div>";
            echo "<div><span>الهاتف</span><strong>" . h($row['phone'] ?: '-') . "</strong></div>";
            echo "<div><span>الهاتف البديل</span><strong>" . h($row['phone_alt'] ?: '-') . "</strong></div>";
            echo "</div>";
            echo "<p class='op-note'>" . nl2br(h($row['notes'] ?: 'لا توجد ملاحظات.')) . "</p>";
            echo "<div class='op-actions'>";
            if (!$confirmed) {
                echo "<a class='action confirm' href='confirm-attendance.php?id=" . h($row['id']) . "&date=" . h($date) . "'>تأكيد</a>";
            }
            echo "<a class='action done' href='" . h($row['_decision'])
                . "?id=" . h($row['patient_id'])
                . "&appointment_id=" . h($row['id'])
                . "&appointment_date=" . h($date)
                . "'>إضافة النتيجة</a>";
            echo "<a class='action edit' href='" . h($row['_edit']) . "?id=" . h($row['id']) . "'>تعديل</a>";
            echo "<a class='action delete' onclick=\"return confirm('هل تريد حذف هذا الموعد؟')\" href='" . h($row['_delete']) . "?id=" . h($row['id']) . "'>حذف</a>";
            echo "</div>";
            echo "</article>";
            $counter++;
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
        padding: 12px 14px;
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 16px;
        background: linear-gradient(135deg, #ffffff, #f8fbff);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
    }

    .op-column-head span {
        color: var(--muted, #64748b);
        font-size: 12px;
        font-weight: 900;
    }

    .op-column-head h2 {
        margin: 2px 0 0;
        color: var(--text, #172033);
        font-size: 21px;
        font-weight: 900;
        letter-spacing: 0.2px;
    }

    .op-column.surgery .op-column-head {
        border-color: rgba(37, 99, 235, 0.24);
        background: linear-gradient(135deg, #dbeafe, #eff6ff 58%, #ffffff);
    }

    .op-column.surgery .op-column-head h2 {
        color: #1d4ed8;
    }

    .op-column.surgery .op-column-head span {
        color: #1e40af;
    }

    .op-column.laser .op-column-head {
        border-color: rgba(245, 158, 11, 0.28);
        background: linear-gradient(135deg, #fef3c7, #fff7ed 58%, #ffffff);
    }

    .op-column.laser .op-column-head h2 {
        color: #b45309;
    }

    .op-column.laser .op-column-head span {
        color: #92400e;
    }

    .op-column.injection .op-column-head {
        border-color: rgba(5, 150, 105, 0.24);
        background: linear-gradient(135deg, #d1fae5, #ecfdf5 58%, #ffffff);
    }

    .op-column.injection .op-column-head h2 {
        color: #047857;
    }

    .op-column.injection .op-column-head span {
        color: #065f46;
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
        border: 1px solid rgba(37, 99, 235, 0.18);
        border-radius: 16px;
        background: linear-gradient(135deg, #eff6ff, #ffffff 58%, #ecfeff);
        padding: 12px 14px;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }

    .op-type-head strong {
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: 0.2px;
        overflow-wrap: anywhere;
    }

    .op-type-head span {
        min-width: 34px;
        min-height: 34px;
        border-radius: 999px;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
        flex: 0 0 auto;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
    }

    .op-card {
        position: relative;
        border: 1px solid var(--border, #dbe7ef);
        border-radius: 16px;
        background: var(--panel-soft, #f8fafc);
        padding: 13px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .op-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
    }

    .op-card-confirmed {
        border: 2px solid #22c55e;
        background: linear-gradient(180deg, #f0fdf4, #ecfdf5 50%, #f8fafc 100%);
        box-shadow: 0 16px 32px rgba(34, 197, 94, 0.15);
    }

    .op-card-confirmed::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 6px;
        border-radius: 16px 0 0 16px;
        background: linear-gradient(180deg, #16a34a, #22c55e);
    }

    .confirmed-checkmark {
        position: absolute;
        top: 12px;
        left: 16px;
        min-width: 64px;
        height: 38px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 0 12px;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: #ffffff;
        font-size: 20px;
        font-weight: 900;
        box-shadow: 0 12px 24px rgba(34, 197, 94, 0.28);
        z-index: 2;
    }

    .op-card-waiting {
        border-color: #fed7aa;
        background: linear-gradient(180deg, #fffaf2, #fff7ed);
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
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
    }

    .eye-os {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
    }

    .eye-ou {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: #ffffff;
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

    body[data-theme="dark"] .op-column.surgery .op-column-head {
        background: linear-gradient(135deg, rgba(30, 64, 175, 0.38), rgba(15, 23, 42, 0.96) 60%, rgba(14, 165, 233, 0.18));
        border-color: rgba(96, 165, 250, 0.42);
    }

    body[data-theme="dark"] .op-column.laser .op-column-head {
        background: linear-gradient(135deg, rgba(180, 83, 9, 0.38), rgba(15, 23, 42, 0.96) 60%, rgba(251, 191, 36, 0.16));
        border-color: rgba(251, 191, 36, 0.38);
    }

    body[data-theme="dark"] .op-column.injection .op-column-head {
        background: linear-gradient(135deg, rgba(4, 120, 87, 0.42), rgba(15, 23, 42, 0.96) 60%, rgba(52, 211, 153, 0.16));
        border-color: rgba(52, 211, 153, 0.38);
    }

    body[data-theme="dark"] .op-column-head h2 {
        color: #f8fafc;
    }

    body[data-theme="dark"] .op-column.surgery .op-column-head span {
        color: #bfdbfe;
    }

    body[data-theme="dark"] .op-column.laser .op-column-head span {
        color: #fde68a;
    }

    body[data-theme="dark"] .op-column.injection .op-column-head span {
        color: #a7f3d0;
    }

    body[data-theme="dark"] .op-type-head {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.96) 55%, rgba(8, 47, 73, 0.9));
        border-color: rgba(96, 165, 250, 0.35);
        box-shadow: 0 14px 28px rgba(2, 6, 23, 0.28);
    }

    body[data-theme="dark"] .op-card,
    body[data-theme="dark"] .empty-column {
        background: rgba(2, 6, 23, 0.24);
        border-color: rgba(148, 163, 184, 0.18);
    }

    body[data-theme="dark"] .op-card h3,
    body[data-theme="dark"] .op-note,
    body[data-theme="dark"] .op-meta strong,
    body[data-theme="dark"] .op-type-head strong,
    body[data-theme="dark"] .day-head h2 {
        color: #f8fafc;
    }

    body[data-theme="dark"] .op-meta span,
    body[data-theme="dark"] .day-head span,
    body[data-theme="dark"] .op-column-head span,
    body[data-theme="dark"] .op-type-head span {
        color: #cbd5e1;
    }

    body[data-theme="dark"] .op-type-head strong {
        color: #f8fafc;
    }

    body[data-theme="dark"] .op-type-head span {
        background: linear-gradient(135deg, #38bdf8, #2563eb);
        color: #eff6ff;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.34);
    }

    body[data-theme="dark"] .op-type {
        color: #7dd3fc;
    }

    body[data-theme="dark"] .serial {
        background: rgba(59, 130, 246, 0.28);
        color: #dbeafe;
        border: 1px solid rgba(147, 197, 253, 0.28);
    }

    body[data-theme="dark"] .status.confirmed {
        background: rgba(34, 197, 94, 0.26);
        color: #dcfce7;
        border: 1px solid rgba(74, 222, 128, 0.32);
    }

    body[data-theme="dark"] .status.waiting {
        background: rgba(245, 158, 11, 0.24);
        color: #fef3c7;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }

    body[data-theme="dark"] .op-card-confirmed {
        background: linear-gradient(180deg, rgba(21, 128, 61, 0.34), rgba(6, 95, 70, 0.5) 52%, rgba(15, 23, 42, 0.92));
        border-color: rgba(74, 222, 128, 0.95);
        box-shadow: 0 20px 36px rgba(34, 197, 94, 0.26);
    }

    body[data-theme="dark"] .op-card-confirmed::before {
        background: linear-gradient(180deg, #4ade80, #22c55e);
        box-shadow: 0 0 18px rgba(74, 222, 128, 0.45);
    }

    body[data-theme="dark"] .confirmed-checkmark {
        background: linear-gradient(135deg, #22c55e, #86efac);
        color: #052e16;
        box-shadow: 0 14px 26px rgba(34, 197, 94, 0.34);
    }

    body[data-theme="dark"] .op-card-waiting {
        background: linear-gradient(180deg, rgba(146, 64, 14, 0.3), rgba(51, 65, 85, 0.58));
        border-color: rgba(251, 191, 36, 0.45);
    }

    body[data-theme="dark"] .op-card-confirmed .op-meta div {
        background: rgba(4, 47, 46, 0.58);
        border-color: rgba(110, 231, 183, 0.22);
    }

    body[data-theme="dark"] .op-card-waiting .op-meta div {
        background: rgba(51, 65, 85, 0.5);
        border-color: rgba(251, 191, 36, 0.14);
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

<script type="application/json" id="operationSummaryPayload">
    <?= json_encode($summary) ?>
</script>

<section class="operations-day">
    <div class="day-head">
        <div>
            <span>Selected operation date</span>
            <h2><?= h($date) ?></h2>
        </div>
        <span><?= $totalCount ?> موعد معلق، <?= $confirmedCount ?> مؤكد</span>
    </div>

    <div class="columns">
        <?php render_operation_column('الحقن', 'injection', $injectionRows, $date); ?>
        <?php render_operation_column('الليزر', 'laser', $laserRows, $date); ?>
        <?php render_operation_column('العمليات', 'surgery', $surgeryRows, $date); ?>
    </div>
</section>