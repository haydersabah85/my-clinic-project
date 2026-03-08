<?php
include 'config.php';

$id = $_GET['id'];

$p = mysqli_fetch_assoc(mysqli_query($con, "
SELECT p.*, pa.full_name as patient_name, pa.age as age
FROM prescriptions p
JOIN add_patient pa ON p.patient_id = pa.id
WHERE p.id = $id
"));

$items = mysqli_query($con, "
SELECT pi.*
FROM prescription_items pi
JOIN medicines m ON pi.medicine_id = m.id
WHERE pi.prescription_id = $id
");

$medicine_names = [];
$q = mysqli_query($con, "SELECT id, medicine_name, medicine_form FROM medicines");
while ($m = mysqli_fetch_assoc($q)) {
    $medicine_names[$m['id']] = $m['medicine_name'] . "  " . $m['medicine_form'];
}

?>


<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>Prescription A5</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: A5 portrait;
            margin: 0;
        }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 0;
        }

        /* ===== الصفحة ===== */
        .page {
            width: 148mm;
            height: 210mm;
            position: relative;
            box-sizing: border-box;
            padding: 15mm 12mm;
            overflow: hidden;
        }

        /* ===== القوس العلوي ===== */
        .top-curve {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 30mm;
            background: linear-gradient(to left, #1565c0, #1e88e5);
            clip-path: ellipse(90% 100% at 50% 0%);
        }

        /* ===== القوس السفلي ===== */
        .bottom-curve {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 30mm;
            background: linear-gradient(to left, #1565c0, #1e88e5);
            clip-path: ellipse(90% 100% at 50% 100%);
        }

        /* ===== الهيدر ===== */
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

        /* ===== بيانات المريض ===== */
        .patient-info {
            margin-top: 10mm;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        /* ===== صندوق الوصفة ===== */
        .rx-box {
            margin-top: 8mm;
            border: 2px solid #64b5f6;
            border-radius: 18px;
            padding: 10mm;
            height: 70mm;
            position: relative;
            direction: ltr;
        }

        /* watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            width: 60%;
        }

        /* Rx */
        .rx-symbol {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* medicine */
        .medicine {
            margin-bottom: 6px;
            font-size: 18px;
            color: #108fe4fe;
        }

        /* footer */
        .footer {
            position: absolute;
            bottom: 15mm;
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
        }

        /* زر الطباعة */
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
        }

        @media print {
            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <button onclick="printPage()" class="print-btn">🖨 طباعة</button>

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
            <div>الاسم: <?php echo $p['patient_name']; ?></div>
            <div>العمر: <?php echo $p['age']; ?></div>
            <div>التاريخ: <?php echo date('Y-m-d'); ?></div>
        </div>

        <div class="rx-box">

            <img src="assets/logo.png" class="watermark">

            <div class="rx-symbol">℞:</div>
            <center><?php echo $p['diagnosis']; ?></center>
            <hr style="border: 1px dashed #504d4d; margin: 10px 0;">
            <br>
            <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                <div class="medicine">
                    <strong><?php echo $medicine_names[$row['medicine_id']]; ?></strong> 
                    <?php echo $row['dose']; ?> -
                    <?php echo $row['frequency']; ?> ---
                    <?php echo $row['duration']; ?>-
                    <?php echo $row['instructions']; ?>
                    <?php if ($row['eye'] == 'right') echo " - العين اليمنى"; ?>
                    <?php if ($row['eye'] == 'left') echo " - العين اليسرى"; ?>
                    <?php if ($row['eye'] == 'both') echo " - العينين"; ?>

                    <br>
                </div>
            <?php } ?>

        </div>

        <div class="footer">
            <div class="phone"> الحجز مسبق على الرقم: 07737423289 </div>
            بغداد – الاعلام - شارع سوق اميمة- مجمع اميمة الطبي- مقابل جامع الحبيب المصطفى
        </div>

    </div>

    <script>
        function printPage() {
            window.print();
        }
    </script>

</body>

</html>