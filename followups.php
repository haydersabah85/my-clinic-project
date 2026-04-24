<?php
include 'config.php';
include 'auth.php';

$today = date('Y-m-d');
$next_two_weeks = date('Y-m-d', strtotime('+14 days'));

$query = mysqli_query($con, "
SELECT f.*, p.full_name, p.phone_no AS phone
FROM followups f
JOIN add_patient p ON f.patient_id = p.id
WHERE f.followup_date BETWEEN '$today' AND '$next_two_weeks'
AND f.status = 'pending'
ORDER BY f.followup_date ASC
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مراجعات الأسبوع</title>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .day-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
        }

        .day-title {
            font-size: 18px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .patient-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            margin: 6px 0;
        }

        .today {
            background: #ffe5e5 !important;
        }

        .tomorrow {
            background: #fff3cd !important;
        }

        button {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.85;
        }
    </style>
</head>

<body>

    <h2>📅 مراجعات الأسبوعين القادمين</h2>

    <?php
    $current_date = '';

    if (mysqli_num_rows($query) > 0) {

        while ($row = mysqli_fetch_assoc($query)) {

            if ($current_date != $row['followup_date']) {

                if ($current_date != '') {
                    echo "</div>";
                }

                $current_date = $row['followup_date'];

                echo "<div class='day-card'>";
                echo "<div class='day-title'>📌 " . date('l d-m-Y', strtotime($current_date)) . "</div>";
            }

            $class = '';
            if ($row['followup_date'] == $today) {
                $class = 'today';
            } elseif ($row['followup_date'] == date('Y-m-d', strtotime('+1 day'))) {
                $class = 'tomorrow';
            }
    ?>

            <div class="patient-row <?php echo $class; ?>">
                <div>
                    <strong><?php echo $row['full_name']; ?></strong><br>
                    📞 <?php echo $row['phone']; ?><br>
                    📝 <?php echo $row['note']; ?><br>
                    🔹 السبب: <?php echo $row['followup_reason']; ?>
                </div>
                <div>
                    <a href="mark_done.php?id=<?php echo $row['id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">
                        <button>✔ تم</button>
                    </a>
                    <a href="delete-followup.php?id=<?php echo $row['id']; ?>&patient_id=<?php echo $row['patient_id']; ?>
                    " onclick="return confirm('هل أنت متأكد من حذف هذه المراجعة؟');">
                        <button>❌ حذف</button>
                    </a>

                </div>
            </div>

    <?php
    
        }

        echo "</div>";
    } else {
        echo "<p>لا توجد مراجعات خلال الأسبوعين القادمين</p>";
    }
    ?>

</body>

</html>