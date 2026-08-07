<?php
define('CLINIC_LANGUAGE_LOADER_ENABLED', false);
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
    <title>طباعة العلاج فقط | عيادة الدكتور حيدر صباح الربيعي</title>
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
            color: #172033;
            background: #f3f6fb;
        }

        .page {
            width: 148mm;
            height: 210mm;
            position: relative;
            margin: 0 auto;
            background: #fff;
            overflow: hidden;
        }

        .rx-area {
            direction: ltr;
            position: absolute;
            top: 90mm;
            right: 13mm;
            left: 17mm;
            font-size: 17px;
            line-height: 2;
        }

        .print-logo {
            position: absolute;
            top: 8mm;
            right: 10mm;
            width: 30mm;
            height: auto;
        }

        .diagnosis {
            text-align: center;
            font-size: 19px;
            font-weight: bold;
            color: #63089c;
            margin-bottom: 8mm;
        }

        .next-followup {
            margin: 0 0 6mm;
            padding: 8px 12px;
            border: 1px dashed #1565c0;
            border-radius: 10px;
            background: rgba(21, 101, 192, 0.06);
            color: #0f3d91;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
        }

        .next-followup-note {
            margin-top: 4px;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
        }

        .medicine {
            margin-bottom: 6mm;
            color: #f21818;
            font-weight: bold;
            page-break-inside: avoid;
        }

        .medicine-number {
            display: inline-block;
            margin-left: 6px;
            min-width: 22px;
            font-weight: 700;
            color: #0f3d91;
        }

        .medicine-number::after {
            content: ".";
            margin-right: 4px;
            color: #0f3d91;
        }

        .medicine strong {
            color: rgb(20, 69, 232);
            margin-right: 10px;
        }

        .medicine-part {
            display: inline-block;
            margin-right: 10px;
            font-size: 16px;
        }

        .print-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 8px 14px;
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
                display: none;
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="print-btn">طباعة</button>

    <div class="page">
        <!-- <img class="print-logo" src="assets/logo.png" alt="شعار العيادة"> -->
        <div class="rx-area">

            <?php if ($followup_print_line !== '') { ?>
                <div class="next-followup">
                    <?php echo h($followup_print_line); ?>
                    <?php if (!empty($linked_followup['note'])) { ?>
                        <div class="next-followup-note"><?php echo h($linked_followup['note']); ?></div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="diagnosis"><?php echo h($p['diagnosis']); ?></div>

            <?php $counter = 1;
            while ($row = mysqli_fetch_assoc($items)) { ?>
                <div class="medicine">
                    <span class="medicine-number"><?php echo h(clinic_print_prescription_number($counter++)); ?></span>
                    <strong><?php echo h($medicine_names[$row['medicine_id']] ?? ''); ?></strong>
                    <span class="medicine-part"><?php echo h($row['dose']); ?></span>
                    <span class="medicine-part"><?php echo h($row['frequency']); ?></span>
                    <span class="medicine-part"><?php echo h($row['duration']); ?></span>
                    <span class="medicine-part"><?php echo h($row['instructions']); ?></span>
                    <span class="medicine-part">
                        <?php if ($row['eye'] === 'right') echo " (العين اليمنى)"; ?>
                        <?php if ($row['eye'] === 'left') echo " (العين اليسرى)"; ?>
                        <?php if ($row['eye'] === 'both') echo " (العينين)"; ?>
                    </span>
                </div>
            <?php } ?>
        </div>
    </div>
</body>

</html>