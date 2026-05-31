<?php
include 'config.php';
include_once 'clinic_helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    die('Invalid prescription id.');
}

$stmt = $con->prepare("
    SELECT p.*, pa.full_name AS patient_name, pa.age AS age
    FROM prescriptions p
    JOIN add_patient pa ON p.patient_id = pa.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    http_response_code(404);
    die('Prescription not found.');
}

$itemsStmt = $con->prepare("
    SELECT pi.*
    FROM prescription_items pi
    JOIN medicines m ON pi.medicine_id = m.id
    WHERE pi.prescription_id = ?
");
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();

$medicine_names = [];
$q = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines");
while ($m = mysqli_fetch_assoc($q)) {
    $medicine_names[$m['id']] = $m['medicine_name'] . "  " . $m['medicine_form'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>Prescription A5</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: A5 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 0;
            background: #f3f6fb;
            color: #172033;
        }

        .page {
            width: 148mm;
            height: 210mm;
            position: relative;
            padding: 15mm 12mm;
            overflow: hidden;
            margin: 0 auto;
            background: #fff;
        }

        .top-curve,
        .bottom-curve {
            position: absolute;
            left: 0;
            width: 100%;
            height: 30mm;
            background: linear-gradient(to left, #1565c0, #1e88e5);
        }

        .top-curve {
            top: 0;
            clip-path: ellipse(90% 100% at 50% 0%);
        }

        .bottom-curve {
            bottom: 0;
            clip-path: ellipse(90% 100% at 50% 100%);
        }

        .header {
            position: relative;
            text-align: center;
            margin-top: 5mm;
        }

        .header h1 {
            color: #d32f2f;
            margin: 5px 0;
            font-size: 22px;
        }

        .header h3 {
            margin: 2px 0;
            font-size: 14px;
        }

        .patient-info {
            margin-top: 10mm;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
        }

        .rx-box {
            margin-top: 8mm;
            border: 2px solid #64b5f6;
            border-radius: 8px;
            padding: 9mm;
            height: 70mm;
            position: relative;
            direction: ltr;
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 58%;
        }

        .rx-symbol {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .diagnosis {
            color: #263238;
            font-weight: 700;
            text-align: center;
        }

        .medicine {
            margin-bottom: 6px;
            font-size: 17px;
            color: #143dc6;
            page-break-inside: avoid;
        }

        .footer {
            position: absolute;
            bottom: 15mm;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 12px;
        }

        .phone {
            position: absolute;
            top: -5mm;
            left: 50%;
            transform: translateX(-50%);
            color: #d32f2f;
            font-weight: bold;
            white-space: nowrap;
        }

        .print-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 8px 15px;
            background: #1565c0;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            z-index: 2;
        }

        @media screen {
            body {
                padding: 14px;
            }

            .page {
                box-shadow: 0 16px 50px rgba(15, 23, 42, 0.18);
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .page {
                margin: 0;
                box-shadow: none;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-btn">طباعة</button>

    <div class="page">
        <div class="top-curve"></div>
        <div class="bottom-curve"></div>

        <div class="header">
            <div>الدكتور</div>
            <h1>حيدر صباح الربيعي</h1>
            <h3>اختصاص طب وجراحة العيون</h3>
            <h3>تخصص دقيق في جراحة الشبكية والسائل الزجاجي</h3>
        </div>

        <div class="patient-info">
            <div>الاسم: <?php echo h($p['patient_name']); ?></div>
            <div>العمر: <?php echo h($p['age']); ?></div>
            <div>التاريخ: <?php echo date('Y-m-d'); ?></div>
        </div>

        <div class="rx-box">
            <img src="assets/logo.png" class="watermark" alt="">

            <div class="rx-symbol">Rx:</div>
            <div class="diagnosis"><?php echo h($p['diagnosis']); ?></div>
            <hr style="border: 1px dashed #504d4d; margin: 10px 0;">

            <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                <div class="medicine">
                    <strong><?php echo h($medicine_names[$row['medicine_id']] ?? ''); ?></strong>
                    <?php echo h($row['dose']); ?> -
                    <?php echo h($row['frequency']); ?> -
                    <?php echo h($row['duration']); ?> -
                    <?php echo h($row['instructions']); ?>
                    <?php if ($row['eye'] === 'right') echo " - العين اليمنى"; ?>
                    <?php if ($row['eye'] === 'left') echo " - العين اليسرى"; ?>
                    <?php if ($row['eye'] === 'both') echo " - العينين"; ?>
                </div>
            <?php } ?>
        </div>

        <div class="footer">
            <div class="phone">الحجز مسبق على الرقم: 07737423289</div>
            بغداد - الاعلام - شارع سوق اميمة - مجمع اميمة الطبي - مقابل جامع الحبيب المصطفى
        </div>
    </div>
</body>

</html>
