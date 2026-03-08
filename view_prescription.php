<?php
include 'config.php';

$id = (int)$_GET['id'];

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
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>عرض الوصفة</title>

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