<?php
include 'config.php';
include 'auth.php';

$row = null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة وصفة طبية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f8fc, #e9f0f9);
            color: #1f2d3d;
        }

        .container {
            max-width: 980px;
            margin: 30px auto;
            padding: 0 16px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 20px;
            color: #124a8a;
        }

        .panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 20px;
        }

        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .info-item {
            background: #f7fbff;
            border: 1px solid #d8e7f8;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 15px;
        }

        .info-item span:first-child {
            color: #4c6076;
            font-weight: 600;
        }

        .info-item span:last-child {
            color: #8a1d7b;
            font-weight: 700;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-weight: 600;
            color: #33485f;
        }

        .field input,
        .field select,
        .field textarea {
            border: 1px solid #c9d8ea;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 15px;
            font-family: 'Cairo', sans-serif;
            transition: 0.2s ease;
            background: #fff;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #2c7bd9;
            box-shadow: 0 0 0 3px rgba(44, 123, 217, 0.15);
        }

        .field textarea {
            resize: vertical;
            min-height: 90px;
        }

        .medications {
            display: grid;
            gap: 12px;
            margin-top: 10px;
        }

        .med-row {
            border: 1px solid #d8e7f8;
            border-radius: 12px;
            padding: 12px;
            background: #fbfdff;
        }

        .med-row h4 {
            margin: 0 0 10px 0;
            color: #1364b2;
            font-size: 16px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 18px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #18a56f, #0f8a5b);
            color: #fff;
        }

        .btn-secondary {
            background: #e6edf7;
            color: #2a4665;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .empty-message {
            text-align: center;
            color: #b42318;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="page-title">نموذج إضافة وصفة طبية</h1>

        <?php if (!$row) { ?>
            <div class="panel empty-message">لم يتم العثور على بيانات المريض.</div>
        <?php } else { ?>
            <div class="panel patient-info">
                <div class="info-item"><span>رقم المريض:</span> <span><?php echo htmlspecialchars($row['id']); ?></span></div>
                <div class="info-item"><span>الاسم:</span> <span><?php echo htmlspecialchars($row['full_name']); ?></span></div>
                <div class="info-item"><span>العمر:</span> <span><?php echo htmlspecialchars($row['age']); ?></span></div>
                <div class="info-item"><span>رقم الهاتف:</span> <span><?php echo htmlspecialchars($row['phone_no']); ?></span></div>
            </div>

            <form class="panel" method="POST" action="">
                <div class="form-grid">
                    <div class="field">
                        <label for="diagnosis">التشخيص</label>
                        <input type="text" id="diagnosis" name="diagnosis" placeholder="مثال: التهاب ملتحمة" required>
                    </div>
                    <div class="field">
                        <label for="visit_date">تاريخ الوصفة</label>
                        <input type="date" id="visit_date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="medications">
                    <div class="med-row">
                        <h4>الدواء 1</h4>
                        <div class="form-grid">
                            <div class="field">
                                <label for="med_name_1">اسم الدواء</label>
                                <input type="text" id="med_name_1" name="med_name_1" placeholder="اسم الدواء">
                            </div>
                            <div class="field">
                                <label for="med_dose_1">الجرعة</label>
                                <input type="text" id="med_dose_1" name="med_dose_1" placeholder="مثال: نقطة كل 8 ساعات">
                            </div>
                            <div class="field">
                                <label for="med_duration_1">المدة</label>
                                <input type="text" id="med_duration_1" name="med_duration_1" placeholder="مثال: 7 أيام">
                            </div>
                        </div>
                    </div>

                    <div class="med-row">
                        <h4>الدواء 2</h4>
                        <div class="form-grid">
                            <div class="field">
                                <label for="med_name_2">اسم الدواء</label>
                                <input type="text" id="med_name_2" name="med_name_2" placeholder="اسم الدواء">
                            </div>
                            <div class="field">
                                <label for="med_dose_2">الجرعة</label>
                                <input type="text" id="med_dose_2" name="med_dose_2" placeholder="مثال: حبة مرتين يوميًا">
                            </div>
                            <div class="field">
                                <label for="med_duration_2">المدة</label>
                                <input type="text" id="med_duration_2" name="med_duration_2" placeholder="مثال: 10 أيام">
                            </div>
                        </div>
                    </div>

                    <div class="med-row">
                        <h4>ملاحظات إضافية</h4>
                        <div class="field">
                            <label for="notes">تعليمات للمريض</label>
                            <textarea id="notes" name="notes" placeholder="تعليمات الاستخدام، مواعيد المراجعة، تنبيهات..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">حفظ الوصفة</button>
                    <a class="btn btn-secondary" href="patient-file.php?id=<?php echo (int) $row['id']; ?>">الرجوع لملف المريض</a>
                </div>
            </form>
        <?php } ?>
    </div>
</body>

</html>
