<?php
include 'config.php';

include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
$flash = clinic_take_flash();
$row = null;
$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if (!$row) {
    http_response_code(404);
    exit('Patient not found.');
}

$surgeryTypes = clinic_get_surgery_types($con);
if (empty($surgeryTypes)) {
    $surgeryTypes = [
        'Phaco',
        'Vitrectomy',
        'Phaco and Vitrectomy',
        'SOR',
        'Phaco and SOR',
        'Squint',
        'ECCE',
        'ICCE',
        'EUA',
        'Probing',
        'SMILE',
        'PRK',
        'AC Washout',
        'Secondary IOL',
        'Anterior Vitrectomy',
    ];
}

?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موعد عملية للمريض <?= htmlspecialchars($row['full_name']) ?></title>



    <style>
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 30px 15px;
            background: linear-gradient(135deg, #1f2933, #111827);
        }

        /* العناوين */
        h1,
        h2 {
            color: #e5f0ff;
            text-align: center;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        /* الفورم */
        form {
            max-width: 540px;
            margin: auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            position: relative;
        }

        /* شريط علوي جمالي */
        form::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2563eb, #22c55e, #a855f7);
            border-radius: 16px 16px 0 0;
        }

        /* العناوين */
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
        }

        /* الحقول */
        input[type="text"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 95%;
            padding: 13px 15px;
            margin-bottom: 20px;
            margin-left: 5%;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        /* ============================= */
        /* إبراز الحقول المهمة */
        /* ============================= */

        /* أول حقل نصي (اسم المريض غالبًا) */
        input[type="text"]:first-of-type {
            border-color: #2563eb;
            background: #eef2ff;
        }

        /* حقل التاريخ */
        input[type="date"] {
            border-color: #22c55e;
            background: #ecfdf5;
        }

        /* الحقول المطلوبة */
        input:required,
        select:required,
        textarea:required {
            border-color: #a855f7;
            background: #faf5ff;
        }

        /* ============================= */
        /* تأثير التركيز */
        /* ============================= */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            background: #ffffff;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.25);
        }

        /* النصوص الطويلة */
        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* زر الحفظ */
        input[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #ffffff;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* hover */
        input[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.4);
            background: linear-gradient(135deg, #1d4ed8, #6d28d9);
        }

        /* active */
        input[type="submit"]:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* شاشات كبيرة */
        @media screen and (max-width: 1200px) {
            form {
                padding: 30px 25px;
            }

            h1,
            h2 {
                font-size: 24px;
            }
        }

        /* شاشات صغيرة */
        @media screen and (max-width: 768px) {
            form {
                padding: 25px 20px;
            }

            h1,
            h2 {
                font-size: 20px;
            }
        }
    </style>

    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
</head>

<body>
    <?php if ($flash): ?>
        <div style="max-width:760px;margin:0 auto 16px;padding:12px 16px;border-radius:12px;font-weight:700;background:<?= ($flash['type'] ?? '') === 'success' ? '#dcfce7' : '#fee2e2' ?>;color:<?= ($flash['type'] ?? '') === 'success' ? '#166534' : '#991b1b' ?>;">
            <?= h($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>حجز موعد عملية</h2>
    <form action="surgery-appointment2.php?id=<?php echo $id; ?>" method="POST">
        <?php echo clinic_csrf_input(); ?>

        <label for="name">الاسم الكامل:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row['full_name']); ?>"><br><br>

        <label for="surgery_type">نوع العملية:</label><br>
        <select id="surgery_type" name="surgery_type" required>
            <option value="">اختر نوع العملية</option>
            <?php foreach ($surgeryTypes as $type): ?>
                <option value="<?= h($type) ?>"><?= h($type) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="eye">العين:</label><br>
        <select id="eye" name="eye" required>
            <option value="">اختر العين</option>
            <option value="OD">OD</option>
            <option value="OS">OS</option>
            <option value="OU">OU</option>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="text" id="phone" name="phone" value="<?php echo $row['phone_no'] ?>" pattern="[0-9]+" placeholder="07xxxxxxxxx" required>
        <br><br>
        <input type="text" id="phone_alt" name="phone_alt" value="<?php echo $row['phone_no_alt'] ?>" pattern="[0-9]+" placeholder="رقم هاتف بديل">
        <br><br>

        <label for="date">موعد العملية:</label><br>
        <input type="date" id="date" name="date"><br><br>



        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"></textarea><br><br>

        <fieldset style="margin:0 0 20px;padding:16px;border:1px solid #cbd5e1;border-radius:12px">
            <legend style="font-weight:800">جاهزية العملية</legend>
            <?php
            $readinessItems = [
                'patient_verified' => 'تأكيد هوية المريض',
                'eye_verified' => 'تأكيد العين',
                'procedure_verified' => 'تأكيد نوع العملية',
                'consent_ready' => 'الموافقة الجراحية جاهزة',
                'iol_ready' => 'العدسة / IOL محددة عند الحاجة',
                'allergy_checked' => 'مراجعة الحساسية والأدوية',
                'investigations_ready' => 'الفحوصات المطلوبة جاهزة',
                'payment_reviewed' => 'مراجعة الدفع / الحالة الإدارية',
            ];
            foreach ($readinessItems as $key => $label) {
                echo '<label style="display:flex;gap:8px;align-items:center;margin:8px 0">';
                echo '<input type="checkbox" name="readiness[' . h($key) . ']" value="1"> ';
                echo h($label) . '</label>';
            }
            ?>
        </fieldset>

        <input type="submit" name="submit_surgery" value="حجز الموعد">

    </form>
</body>

</html>