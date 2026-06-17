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
    <title>Treatment Only</title>
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

        .diagnosis {
            text-align: center;
            font-size: 21px;
            font-weight: bold;
            color: #63089c;
            margin-bottom: 8mm;
        }

        .medicine {
            margin-bottom: 6mm;
            color: #f21818;
            font-weight: bold;
            page-break-inside: avoid;
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
        <div class="rx-area">
            <div class="diagnosis"><?php echo h($p['diagnosis']); ?></div>

            <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                <div class="medicine">
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