<?php
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$id = (int)$_GET['id'];

$linked_followup = null;

$p = mysqli_fetch_assoc(mysqli_query($con, "
SELECT p.*, pa.full_name as patient_name
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
        max-width: 600px;
        margin: auto;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
</style>

<body>
    <div class="container">


        <h2>الوصفة محفوظة بنجاح ✅</h2>

        <p><b>اسم المريض:</b> <?php echo $p['patient_name']; ?></p>
        <p><b>التاريخ:</b> <?php echo $p['prescription_date']; ?></p>
        <p><b>التشخيص:</b>
            <?php echo $p['diagnosis']; ?>
        </p>

        <?php if ($linked_followup) { ?>
            <?php $followup_label = (($linked_followup['followup_type'] ?? 'review') === 'next_visit') ? 'موعد الفحص القادم' : 'موعد المراجعة القادمة'; ?>
            <p><b><?= htmlspecialchars($followup_label, ENT_QUOTES, 'UTF-8'); ?>:</b>
                <?php echo htmlspecialchars($linked_followup['followup_date'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
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
    </div>
</body>

</html>