<?php
include 'config.php';
include 'auth.php';

$today = date('Y-m-d');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$conditions = ["f.status = 'pending'"];
$params = [];
$types = "";

if ($date_from !== '') {
    $conditions[] = "f.followup_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to !== '') {
    $conditions[] = "f.followup_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where = implode(" AND ", $conditions);

$stmt = mysqli_prepare($con, "
    SELECT f.*, p.full_name, p.phone_no AS phone
    FROM followups f
    JOIN add_patient p ON f.patient_id = p.id
    WHERE $where
    ORDER BY f.followup_date ASC
");

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>مراجعات الأسبوع</title>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (localStorage.getItem("theme") === "dark") {
                document.body.setAttribute("data-theme", "dark");
            }
        });
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background:
                radial-gradient(circle at top right, rgba(45, 137, 181, 0.08), transparent 32%),
                #f4f6f9;
            color: #263238;
            margin: 0;
            padding: 20px;
        }

        h2 {
            max-width: 1100px;
            margin: 0 auto 22px;
            padding: 20px 24px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2d89b5, #3fa7d6);
            color: #ffffff;
            text-align: center;
            box-shadow: 0 12px 28px rgba(45, 137, 181, 0.18);
        }

        .day-card {
            background: #ffffff;
            border: 1px solid rgba(45, 137, 181, 0.08);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }

        .day-title {
            font-size: 18px;
            font-weight: 800;
            color: #2d89b5;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5edf2;
        }

        .patient-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            background: #f8f9fa;
            border: 1px solid #edf2f7;
            padding: 12px 14px;
            border-radius: 12px;
            margin: 8px 0;
            line-height: 1.8;
        }

        .patient-row strong {
            color: #1f5169;
        }

        .today {
            background: #ffe5e5 !important;
            border-color: #ffc9c9;
        }

        .tomorrow {
            background: #fff3cd !important;
            border-color: #ffe08a;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .actions a {
            text-decoration: none;
        }

        button {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 9px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            transition: transform 0.2s ease, opacity 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 8px 18px rgba(40, 167, 69, 0.18);
        }

        button:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: #e74c3c;
            box-shadow: 0 8px 18px rgba(231, 76, 60, 0.18);
        }

        .empty-message {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            background: #ffffff;
            border-radius: 16px;
            color: #607d8b;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        body[data-theme="dark"] {
            background:
                radial-gradient(circle at top right, rgba(63, 167, 214, 0.16), transparent 34%),
                radial-gradient(circle at top left, rgba(45, 212, 191, 0.1), transparent 28%),
                linear-gradient(180deg, #07111d, #0b1220 58%, #08111d);
            color: #e6edf5;
        }

        body[data-theme="dark"] h2 {
            background: linear-gradient(135deg, #0f2d5c, #155e9f, #0f766e);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.34);
            border: 1px solid rgba(147, 197, 253, 0.2);
        }

        body[data-theme="dark"] .day-card,
        body[data-theme="dark"] .empty-message {
            background: linear-gradient(145deg, rgba(15, 27, 42, 0.96), rgba(11, 18, 32, 0.96));
            border-color: rgba(148, 163, 184, 0.18);
            box-shadow: 0 22px 55px rgba(0, 0, 0, 0.34);
        }

        body[data-theme="dark"] .day-title {
            color: #93c5fd;
            border-bottom-color: rgba(148, 163, 184, 0.16);
        }

        body[data-theme="dark"] .patient-row {
            background: rgba(15, 23, 42, 0.72);
            border-color: rgba(147, 197, 253, 0.14);
            color: #dce7f3;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        body[data-theme="dark"] .patient-row strong {
            color: #bfdbfe;
        }

        body[data-theme="dark"] .today {
            background: rgba(127, 29, 29, 0.42) !important;
            border-color: rgba(248, 113, 113, 0.34);
        }

        body[data-theme="dark"] .tomorrow {
            background: rgba(113, 63, 18, 0.42) !important;
            border-color: rgba(251, 191, 36, 0.34);
        }

        body[data-theme="dark"] button {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.18);
        }

        body[data-theme="dark"] .delete-btn {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            box-shadow: 0 12px 24px rgba(239, 68, 68, 0.18);
        }

        body[data-theme="dark"] .empty-message {
            color: #a8bdd1;
        }

        @media (max-width: 700px) {
            body {
                padding: 12px;
            }

            h2 {
                font-size: 20px;
                padding: 16px;
            }

            .patient-row {
                align-items: stretch;
                flex-direction: column;
            }

            .actions {
                justify-content: stretch;
            }

            .actions a,
            .actions button {
                width: 100%;
            }
        }
    </style>
<script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <h2>📅 مواعيد المراجعة</h2>

    <form method="GET" class="day-card" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
        <div style="flex:1; min-width:180px;">
            <label for="date_from">من تاريخ</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" style="width:100%; padding:10px; border:1px solid #d6e2ea; border-radius:9px;">
        </div>
        <div style="flex:1; min-width:180px;">
            <label for="date_to">إلى تاريخ</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" style="width:100%; padding:10px; border:1px solid #d6e2ea; border-radius:9px;">
        </div>
        <button type="submit">عرض</button>
        <a href="followups.php"><button type="button" class="delete-btn">كل المواعيد</button></a>
        <a href="followup-appointment.php"><button type="button">إعطاء موعد</button></a>
    </form><?php
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
                <div class="actions">
                    <a href="mark_done.php?id=<?php echo $row['id']; ?>&patient_id=<?php echo $row['patient_id']; ?>">
                        <button>✔ تم</button>
                    </a>
                    <a href="delete-followup.php?id=<?php echo $row['id']; ?>&patient_id=<?php echo $row['patient_id']; ?>
                    " onclick="return confirm('هل أنت متأكد من حذف هذه المراجعة؟');">
                        <button class="delete-btn">❌ حذف</button>
                    </a>

                </div>
            </div>

    <?php
    
        }

        echo "</div>";
    } else {
        echo "<p class='empty-message'>لا توجد مراجعات حسب التصفية الحالية</p>";
    }
    ?>

</body>

</html>
