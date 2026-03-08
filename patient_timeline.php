<?php
include "config.php";

$patient_id = $_GET['id'] ?? 0;

/* ===== GET PATIENT NAME ===== */
$stmt = $con->prepare("SELECT full_name FROM add_patient WHERE id=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($patient_name);
$stmt->fetch();
$stmt->close();

/* ===== TIMELINE DATA ===== */
$sql = "
(
  SELECT 
    date AS event_date,
    'visit' AS type,
   notes AS details
  FROM patient_visits
  WHERE patient_id = ?
)
UNION ALL
(
  SELECT
    date AS event_date,
    'appointment' AS type,
   surgery_type AS details
  FROM surgery_appointment
  WHERE patient_id = ?
)
UNION ALL
(
  SELECT
  date AS event_date,
   'surgery' AS type,
  surgery_type AS details
  FROM surgery
  WHERE patient_id = ?
)
ORDER BY event_date DESC
";

$stmt = $con->prepare($sql);
$stmt->bind_param("iii", $patient_id, $patient_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ملف المريض</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
   

    <style>
        body {
            font-family: Cairo;
            background: var(--bg, #f4f6f8);
            margin: 0;
            padding: 25px;
            direction: rtl;
        }

        header {
            max-width: 900px;
            margin: auto;
            text-align: center;
            justify-content: center;
            flex-wrap: wrap;
            display: flex;
            gap:25px;
            
        }
        header a {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--primary, #3b82f6);
            font-weight: 600;
            font-size: 18px;
        }

        .timeline {
            max-width: 900px;
            margin: auto;
        }

        .patient-name {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .event {
            background: var(--card, #fff);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 15px;
            border-right: 6px solid;
        }

        .visit {
            border-color: #22c55e;
           
        }

        .appointment {
            border-color: #38bdf8;
        }

        .surgery {
            border-color: #ef4444;
        }

        .event:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .event-title {
            font-weight: 700;
            margin-bottom: 6px;

        }

        .event-date {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <header>
        <a href="main.php">الصفحة الرئيسية</a>
        <a href="patient-data.php?id=<?= $patient_id ?>">عودة إلى بيانات المريض</a>
    </header>

    <div class="timeline">

        <div class="patient-name">
            👤 <?= htmlspecialchars($patient_name) ?>
        </div>

        <?php while ($row = $result->fetch_assoc()):
            $type = $row['type'];
            $title = $type == 'visit' ? 'زيارة' : ($type == 'appointment' ? 'موعد عملية' : 'عملية منجزة');
        ?>
            <div class="event <?= $type ?>">
                <div class="event-title"><?= $title ?></div>
                <div class="event-date"><?= $row['event_date'] ?></div>
                <div><?= nl2br(htmlspecialchars($row['details'])) ?></div>
            </div>
        <?php endwhile; ?>

    </div>

</body>

</html>