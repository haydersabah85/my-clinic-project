<?php
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$id = (int)$_GET['id'];

$linked_followup = null;

$p = mysqli_fetch_assoc(mysqli_query($con, "
SELECT p.*, pa.full_name as patient_name, pa.age as patient_age
FROM prescriptions p
JOIN add_patient pa ON p.patient_id = pa.id
WHERE p.id = $id
"));

$items = mysqli_query($con, "
SELECT pi.*, m.medicine_name, m.medicine_form
FROM prescription_items pi
JOIN medicines m ON pi.medicine_id = m.id
WHERE pi.prescription_id = $id
");

$medicine_names = [];
$q = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines");
while ($m = mysqli_fetch_assoc($q)) {
    $medicine_names[$m['id']] = $m['medicine_name'] . " " . $m['medicine_form'];
}

if (!empty($p['followup_id'])) {
    $followup_stmt = mysqli_prepare($con, "
        SELECT followup_date, followup_reason, note
        FROM followups
        WHERE id = ? AND patient_id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($followup_stmt, "ii", $p['followup_id'], $p['patient_id']);
    mysqli_stmt_execute($followup_stmt);
    $linked_followup = mysqli_fetch_assoc(mysqli_stmt_get_result($followup_stmt)) ?: null;
}

if (!$linked_followup && (!empty($p['next_followup_date']) || !empty($p['next_followup_reason']) || !empty($p['next_followup_note']))) {
    $linked_followup = [
        'followup_date' => $p['next_followup_date'] ?? '',
        'followup_reason' => $p['next_followup_reason'] ?? '',
        'note' => $p['next_followup_note'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>عرض الوصفة | عيادة الدكتور حيدر صباح الربيعي</title>
    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">

    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
</head>

<style>
    body {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        padding: 20px;
    }

    .container {
        max-width: 700px;
        margin: auto;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .patient-card {
        background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
        border: 1px solid #dfeaff;
        border-right: 5px solid #3b82f6;
        border-radius: 16px;
        padding: 14px;
        margin: 20px 0;
    }

    .patient-details {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .patient-detail {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        min-height: 44px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 12px;
        padding: 8px 10px;
    }

    .patient-detail-label {
        color: #475569;
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }

    .patient-detail-value {
        color: #0f172a;
        font-weight: 800;
        font-size: 15px;
        text-align: left;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    h2 {
        color: #343a40;
    }

    p {
        font-size: 16px;
        margin: 10px 0;
    }

    hr {
        margin: 20px 0;
    }

    @media (max-width: 600px) {
        .patient-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<body>
    <div class="container">


        <h2>الوصفة محفوظة بنجاح ✅</h2>

        <div class="patient-card">
            <div class="patient-details">
                <div class="patient-detail">
                    <span class="patient-detail-label">الاسم</span>
                    <span class="patient-detail-value"><?php echo htmlspecialchars($p['patient_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="patient-detail">
                    <span class="patient-detail-label">العمر</span>
                    <span class="patient-detail-value"><?php echo htmlspecialchars($p['patient_age'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="patient-detail">
                    <span class="patient-detail-label">التاريخ</span>
                    <span class="patient-detail-value"><?php echo htmlspecialchars($p['prescription_date'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>

        <p><b>التشخيص:</b>
            <?php echo htmlspecialchars($p['diagnosis'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <?php if ($linked_followup) { ?>
            <?php $followup_label = (($linked_followup['followup_type'] ?? 'review') === 'next_visit') ? 'موعد الفحص القادم' : 'موعد المراجعة القادمة'; ?>
            <?php $followup_display_date = !empty($linked_followup['followup_date']) ? clinic_format_display_date((string) $linked_followup['followup_date']) : '-'; ?>
            <p><b><?= htmlspecialchars($followup_label, ENT_QUOTES, 'UTF-8'); ?>:</b>
                <?php echo htmlspecialchars($followup_display_date, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($linked_followup['followup_reason'])) echo ' - ' . htmlspecialchars($linked_followup['followup_reason'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($linked_followup['note'])) echo '<br><span style="color:#475569;">' . htmlspecialchars($linked_followup['note'], ENT_QUOTES, 'UTF-8') . '</span>'; ?>
            </p>
            <p><a href="followups.php" style="color:#1565c0;font-weight:700;">عرضها ضمن قائمة المراجعات</a></p>
        <?php } ?>

        <hr>

        <h3>الأدوية:</h3>

        <?php while ($row = mysqli_fetch_assoc($items)) { ?>

            <p>

                <b><?php echo $medicine_names[$row['medicine_id']]; ?></b><br>
                <?php echo $row['dose']; ?> -
                <?php echo $row['frequency']; ?> -
                <?php echo $row['duration']; ?> -
                <?php if ($row['eye'] == 'right') echo " - العين اليمنى"; ?>
                <?php if ($row['eye'] == 'left') echo " - العين اليسرى"; ?>
                <?php if ($row['eye'] == 'both') echo " - العينين"; ?>
                <?php echo $row['instructions']; ?>
            </p>
        <?php } ?>

        <hr>

        <a href="print_prescription.php?id=<?php echo $id; ?>" target="_blank">
            <button style="background:#1565c0;color:#fff;padding:8px 12px;border:none;border-radius:6px; cursor: pointer;">
                🖨 طباعة كاملة
            </button>
        </a>

        <a href="print_treatment_only.php?id=<?php echo $id; ?>" target="_blank">
            <button style="background:#2e7d32;color:#fff;padding:8px 12px;border:none;border-radius:6px; cursor: pointer;">
                💊 طباعة العلاج فقط
            </button>
        </a>
        <a href="patient-file.php?id=<?php echo $p['patient_id']; ?>" target="_blank">
            <button style="background:#6b21a8;color:#fff;padding:8px 12px;border:none;border-radius:6px; cursor: pointer;">
                🗂 ملف المريض
            </button>
        </a>
    </div>
</body>

</html>