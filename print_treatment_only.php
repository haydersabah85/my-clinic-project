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
    <title>Treatment Only</title>

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
            width: 145mm;
            height: 210mm;
            position: relative;
        }

        /* ===== منطقة الكتابة فقط ===== */
        /* عدل top حسب مكان مربع Rx في الورقة الجاهزة */
        .rx-area {
            direction: ltr;
            position: absolute;
            top: 90mm;
            /* 👈 عدل هذا الرقم لو احتجت */
            right: 20mm;
            /* مسافة من اليمين */
            left: 20mm;
            /* مسافة من اليسار */
            font-size: 18px;
            line-height: 2.2;
        }

        /* كل دواء */
        .medicine {
            margin-bottom: 6mm;
            color: black;
        }

        /* زر الطباعة */
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
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="print-btn">🖨 طباعة</button>

    <div class="page">

        <div class="rx-area">
           <center><?php echo $p['diagnosis']; ?></center> 

             
             <br>
            <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                <div class="medicine">
                    <b><?php echo $medicine_names[$row['medicine_id']]; ?></b>
                    <?php echo $row['dose']; ?> -
                    <?php echo $row['frequency']; ?> - - 
                    <?php echo $row['duration']; ?>- 
                    <?php echo $row['instructions']; ?>
                    <?php if ($row['eye'] == 'right') echo " - العين اليمنى"; ?>
                    <?php if ($row['eye'] == 'left') echo " - العين اليسرى"; ?>
                    <?php if ($row['eye'] == 'both') echo " - العينين"; ?>
                    
                    <br>
                <?php } ?>
                </div>
           

        </div>

    </div>

</body>

</html>