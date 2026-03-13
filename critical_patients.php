<?php
include "config.php";

/* ===== SQL: GET CRITICAL PATIENTS ===== */
$sql = "
SELECT DISTINCT
    p.id AS patient_id,
    p.full_name AS patient_name,
    p.is_critical,
    MAX(s.date) AS last_surgery_date,
    MIN(a.date) AS missed_followup

FROM add_patient p
LEFT JOIN surgery s 
    ON p.id = s.patient_id

LEFT JOIN surgery_appointment a 
    ON p.id = a.patient_id 
    AND a.status = 'pending'
    AND a.date < CURDATE()

WHERE
    p.is_critical = 1
    OR s.date >= CURDATE() - INTERVAL 7 DAY
    OR a.id IS NOT NULL

GROUP BY p.id, p.full_name, p.is_critical
ORDER BY last_surgery_date DESC, missed_followup ASC
";

$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>🚨 المرضى الحرِجون</title>

<style>
body{
    font-family:Cairo;
    background:#f8fafc;
    padding:30px;
}
.card{
    background:white;
    padding:15px 20px;
    border-radius:10px;
    margin-bottom:10px;
    border-right:6px solid red;
}
.name{
    font-size:18px;
    font-weight:bold;
}
.meta{
    font-size:13px;
    color:#6b7280;
}
a{color:#2563eb;text-decoration:none}
</style>
</head>

<body>

<h2>🚨 المرضى الحرِجون</h2>

<?php while($row = $result->fetch_assoc()): ?>
<div class="card">
    <div class="name">
        <a href="patient-file.php?patient_id=<?= $row['patient_id'] ?>">
            <?= $row['patient_name'] ?>
        </a>
    </div>

    <div class="meta">
        <?php if($row['is_critical']): ?>
            🔴 مُعلّم يدويًا كمريض حرج<br>
        <?php endif; ?>

        <?php if($row['last_surgery_date']): ?>
            🩺 آخر عملية: <?= $row['last_surgery_date'] ?><br>
        <?php endif; ?>

        <?php if($row['missed_followup']): ?>
            ⏰ متابعة فائتة منذ: <?= $row['missed_followup'] ?>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>

</body>
</html>
