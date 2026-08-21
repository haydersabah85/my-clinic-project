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

$linked_followup = clinic_get_prescription_followup($con, $p);
$followup_print_line = clinic_followup_print_line($linked_followup);

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

function clinic_print_prescription_number($n)
{
    $map = [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
    $result = '';
    while ($n > 0) {
        foreach ($map as $value => $letter) {
            if ($n >= $value) {
                $result .= $letter;
                $n -= $value;
                break;
            }
        }
    }
    return $result;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>طباعة الوصفة | عيادة الدكتور حيدر صباح الربيعي</title>
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

        .print-logo {
            width: 34mm;
            height: auto;
            margin: 0 auto 4px;
            display: block;
        }

        .page {
            width: 148mm;
            height: 210mm;
            position: relative;
            padding: 15mm 12mm;
            overflow: hidden;
            margin: 0 auto;
            background: #fff;
            z-index: 0;
        }

        .header,
        .patient-card,
        .rx-box,
        .footer {
            position: relative;
            z-index: 2;
        }

        .top-curve,
        .bottom-curve {
            position: absolute;
            left: 0;
            width: 100%;
            height: 30mm;
            background: linear-gradient(to left, #1565c0, #1e88e5);
            z-index: 1;
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

        .patient-card {
            margin-top: 10mm;
            background: linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
            border: 1px solid #dfeaff;
            border-right: 5px solid #3b82f6;
            border-radius: 16px;
            padding: 12px 14px;
            box-shadow: 0 8px 18px rgba(59, 130, 246, 0.06);
            clear: both;
        }

        .patient-details {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            width: 100%;
        }

        .patient-detail {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 42px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 12px;
            padding: 8px 10px;
            min-width: 0;
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

        .next-followup {
            margin: 10px 0 6px;
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(21, 101, 192, 0.08);
            color: #0f3d91;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
        }

        .next-followup-note {
            margin-top: 4px;
            color: #41546f;
            font-size: 12px;
            font-weight: 600;
        }

        .medicine {
            margin-bottom: 6px;
            font-size: 17px;
            color: #143dc6;
            page-break-inside: avoid;
        }

        .medicine-number {
            display: inline-block;
            margin-left: 6px;
            min-width: 22px;
            font-weight: 700;
            color: #d32f2f;
        }

        .medicine-number::after {
            content: ".";
            margin-right: 4px;
            color: #d32f2f;
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
            <img class="print-logo" src="assets/logo.png" alt="شعار العيادة">
            <div>عيادة</div>
            <h1>الدكتور حيدر صباح الربيعي</h1>
            <h3>اختصاص طب وجراحة العيون</h3>
            <h3>تخصص دقيق في جراحة الشبكية والسائل الزجاجي</h3>
        </div>

        <?php $print_date = !empty($p['prescription_date']) ? date('Y-m-d', strtotime($p['prescription_date'])) : date('Y-m-d'); ?>
        <div class="patient-card">
            <div class="patient-details">
                <div class="patient-detail">
                    <span class="patient-detail-label">الاسم</span>
                    <span class="patient-detail-value"><?php echo h($p['patient_name']); ?></span>
                </div>
                <div class="patient-detail">
                    <span class="patient-detail-label">العمر</span>
                    <span class="patient-detail-value"><?php echo h($p['age']); ?></span>
                </div>
                <div class="patient-detail">
                    <span class="patient-detail-label">التاريخ</span>
                    <span class="patient-detail-value"><?php echo h($print_date); ?></span>
                </div>
            </div>
        </div>

        <div class="rx-box">
            <img src="assets/logo.png" class="watermark" alt="">

            <div class="rx-symbol">Rx:</div>
            <div class="diagnosis"><?php echo h($p['diagnosis']); ?></div>
            <?php if ($followup_print_line !== '') { ?>
                <div class="next-followup">
                    <?php echo h($followup_print_line); ?>
                    <?php if (!empty($linked_followup['note'])) { ?>
                        <div class="next-followup-note"><?php echo h($linked_followup['note']); ?></div>
                    <?php } ?>
                </div>
            <?php } ?>
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