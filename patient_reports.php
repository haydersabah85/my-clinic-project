<?php
include 'config.php';

$id = $_GET['id'];

// جلب بيانات المريض
$query = "SELECT * FROM add_patient WHERE id = '$id'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>

<head>
    <title>استشارة طبية</title>

    <style>
   
    @page {
        size: A5 portrait;
        margin: 0;
    }

    body {
        font-family: 'Cairo', sans-serif;
        direction: ltr; /* 👈 انكليزي */
        margin: 0;
    }

    .reports {
        position: absolute;
        top: 90mm;
        right: 20mm;
        left: 20mm;
        font-size: 18px;
        line-height: 2.0;
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
    }

    textarea {
        width: 100%;
        height: 100mm;
        font-size: 18px;
        padding: 10px;
        box-sizing: border-box;
        font-family: 'Cairo', sans-serif;
        color: #043150fe;
        border: 1px dashed #999; /* يظهر فقط على الشاشة */
        resize: none;
    }

    /* النص النهائي للطباعة */
    .print-text {
        display: none;
        white-space: pre-wrap;
    }

    @media print {

        .print-btn {
            display: none;
        }

        textarea {
            display: none; /* نخفيه */
        }

        .print-text {
            display: block; /* نعرض النص */
        }

        .reports {
            border: none; /* إزالة الحدود */
        }
    }
</style>
</head>

<body>

<button class="print-btn" onclick="preparePrint()">🖨 طباعة</button>

<div class="reports">

    <textarea id="report" placeholder="اكتب الاستشارة هنا..."></textarea>

    <!-- هذا يظهر فقط أثناء الطباعة -->
    <div class="print-text" id="printText"></div>

</div>

<script>
function preparePrint() {
    // نقل النص من textarea إلى div
    let text = document.getElementById("report").value;
    document.getElementById("printText").innerText = text;

    window.print();
}
</script>

</body>
</html>    