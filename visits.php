<?php
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>زيارات اليوم 🏥</title>

    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            margin: 0;
            background: #eef4f8;
            color: #333;
            transition: background 0.3s, color 0.3s;
            direction: rtl;
        }

        h1 {
            font-size: 34px;
            font-weight: bold;
            margin: 30px 0 20px;
            color: #7a1f1f;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 15px;
        }

        /* ===== Back Button ===== */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
            background: #4caf50;
            color: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
        }

        .back-link:hover {
            background: #43a047;
            transform: translateY(-2px);
        }

        /* ===== Table ===== */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            overflow: hidden;
            overflow-y: auto;
            max-height: 700px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        a {
            color: #2d89b5;
            text-decoration: none;

        }

        thead {
            background: linear-gradient(135deg, #3fa7d6, #2d89b5);
            color: #fff;
        }

        th {
            padding: 14px 10px;
            font-size: 17px;
            font-weight: bold;
            text-align: center;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
            font-size: 15px;
        }

        tbody tr {
            transition: 0.25s;
        }

        tbody tr:hover {
            background: #a7aeaeff;
            transform: scale(1.01);
        }

        /* ===== Visit Badges ===== */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            display: inline-block;
            min-width: 110px;
        }

        .first {
            background: #6fbf73;
        }

        .repeat {
            background: #3fa7d6;
        }

        .free {
            background: #b3396d;
        }

        /* ===== Action Button ===== */
        .enter-btn {
            background: #ffb703;
            color: #333;
            padding: 6px 14px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-block;
        }

        .enter-btn:hover {
            background: #ffa200;
            transform: translateY(-2px);
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            h1 {
                font-size: 26px;
            }

            th,
            td {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <h1>زيارات اليوم</h1>

    <div class="container">

        <a href="dashboard.php" class="back-link">⬅ الصفحة الرئيسية</a>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>التسلسل</th>
                        <th>اسم المريض</th>
                        <th>العمر</th>
                        <th>التاريخ</th>
                        <th>نوع الزيارة</th>
                        <th>الدخول</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    include 'config.php';

                    $today = date("Y-m-d");

                    $sql = "
                        SELECT
                        visits.daily_serial,
                        visits.visit_type,
                        visits.visit_date,
                        add_patient.id,
                        add_patient.full_name,
                        add_patient.age
                        FROM visits
                        JOIN add_patient ON visits.patient_id = add_patient.id
                        WHERE visits.visit_date = '$today'
                        ORDER BY visits.daily_serial ASC
                    ";

                    $result_select = mysqli_query($con, $sql);

                    while ($row = mysqli_fetch_assoc($result_select)) {

                        // تحويل نوع الزيارة إلى عربي + كلاس
                        switch ($row['visit_type']) {
                            case 'first':
                                $visit_text = 'زيارة أول مرة';
                                $visit_class = 'first';
                                break;
                            case 'repeat':
                                $visit_text = 'زيارة متكررة';
                                $visit_class = 'repeat';
                                break;
                            case 'free':
                                $visit_text = 'زيارة مراجعة';
                                $visit_class = 'free';
                                break;
                            default:
                                $visit_text = 'غير معروف';
                                $visit_class = '';
                        }
                    ?>

                        <tr>
                            <td><?= $row['daily_serial']; ?></td>
                            <td><a href="patient-file.php?id=<?= $row['id']; ?>">
                                    <?= htmlspecialchars($row['full_name']); ?></a></td>

                            
                            <td><?= $row['age']; ?></td>
                            <td><?= $row['visit_date']; ?></td>
                            <td>
                                <span class="badge <?= $visit_class; ?>">
                                    <?= $visit_text; ?>
                                </span>
                            </td>
                            <td>
                                <a class="enter-btn" href="patient-file.php?id=<?= $row['id']; ?>">
                                    دخول
                                </a>
                            </td>
                        </tr>

                    <?php } ?>

                </tbody>
            </table>
        </div>

    </div>

</body>

</html>