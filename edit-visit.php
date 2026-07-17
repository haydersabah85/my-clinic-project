<?php

include "config.php";

include 'auth.php';

$id = isset($_GET['id_edit']) ? (int) $_GET['id_edit'] : 0;
$row = [
    'id' => '',
    'full_name' => '',
    'age' => '',
    'gender' => '',
    'phone_no' => '',
    'phone_no_alt' => '',
    'address' => '',
    'visit_id' => '',
    'visit_type' => '',
    'visit_date' => ''
];

if (isset($_GET['id_edit'])) {
    $select_query = "SELECT 
    add_patient.*,
    visits.visit_id,
    visits.visit_type,
    visits.visit_date
     FROM add_patient
        JOIN visits ON add_patient.id = visits.patient_id
    
     WHERE visits.visit_id = $id";
    $result = mysqli_query($con, $select_query);
    $fetched = mysqli_fetch_assoc($result);
    if ($fetched) {
        $row = $fetched;
    }
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>بيانات <?php echo htmlspecialchars($row['full_name']); ?> 📁</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="assets/theme.js" defer></script>

    <link rel="stylesheet" href="assets/dark-mode.css">
    <link rel="stylesheet" href="assets/clinic-ui.css">
</head>

<style>
    :root {
        --bg-1: #f2f4fb;
        --bg-2: #e8ecf8;
        --ink: #1d2740;
        --muted: #5b6883;
        --panel: rgba(255, 255, 255, 0.92);
        --panel-border: rgba(88, 112, 173, 0.24);
        --title-grad: linear-gradient(120deg, #273a73, #3458a5 52%, #da7c46);
        --nav-grad: linear-gradient(150deg, #f5f8ff, #edf2ff);
        --link-bg: rgba(58, 91, 165, 0.08);
        --link-hover: rgba(58, 91, 165, 0.18);
        --line: rgba(91, 104, 131, 0.28);
        --shadow-soft: 0 14px 28px rgba(25, 41, 78, 0.12);
        --shadow-strong: 0 24px 48px rgba(25, 41, 78, 0.18);
    }

    body[data-theme="dark"],
    body.dark {
        --bg-1: #151b2d;
        --bg-2: #1d2438;
        --ink: #e5edff;
        --muted: #a4b2d2;
        --panel: rgba(24, 35, 60, 0.9);
        --panel-border: rgba(117, 145, 214, 0.28);
        --title-grad: linear-gradient(120deg, #1d2b57, #3153a2 52%, #c96d3f);
        --nav-grad: linear-gradient(150deg, #202d4c, #1a2742);
        --link-bg: rgba(125, 163, 255, 0.14);
        --link-hover: rgba(125, 163, 255, 0.24);
        --line: rgba(164, 178, 210, 0.34);
        --shadow-soft: 0 16px 34px rgba(0, 0, 0, 0.34);
        --shadow-strong: 0 30px 56px rgba(0, 0, 0, 0.44);
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        min-height: 100vh;
        font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
        color: var(--ink);
        background:
            radial-gradient(circle at 88% 8%, rgba(218, 124, 70, 0.22), transparent 26%),
            radial-gradient(circle at 8% 0%, rgba(52, 88, 165, 0.2), transparent 24%),
            linear-gradient(180deg, var(--bg-1), var(--bg-2));
    }

    h1 {
        margin: 0;
        padding: 22px 14px;
        text-align: center;
        color: #ffffff;
        font-size: clamp(24px, 4vw, 35px);
        font-weight: 800;
        background: var(--title-grad);
        border-bottom-left-radius: 26px;
        border-bottom-right-radius: 26px;
        box-shadow: var(--shadow-strong);
        letter-spacing: 0.3px;
    }

    .container {
        max-width: 1180px;
        margin: 24px auto 34px;
        padding: 18px;
        border-radius: 22px;
        border: 1px solid var(--panel-border);
        background: var(--panel);
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 18px;
        box-shadow: var(--shadow-strong);
        backdrop-filter: blur(8px);
    }

    nav {
        background: var(--nav-grad);
        border: 1px solid var(--panel-border);
        border-radius: 16px;
        padding: 12px;
        box-shadow: var(--shadow-soft);
    }

    nav ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    nav ul li a {
        display: block;
        text-decoration: none;
        font-weight: 700;
        text-align: center;
        color: var(--ink);
        background: var(--link-bg);
        border: 1px solid transparent;
        border-radius: 11px;
        padding: 10px 11px;
        transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    nav ul li a:hover {
        transform: translateY(-2px);
        background: var(--link-hover);
        border-color: rgba(58, 91, 165, 0.36);
    }

    .info {
        border: 1px solid var(--panel-border);
        border-radius: 16px;
        padding: 16px;
        background: rgba(255, 255, 255, 0.52);
        box-shadow: var(--shadow-soft);
    }

    .info p {
        margin: 0;
        padding: 11px 0;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px dashed var(--line);
        font-size: 16px;
    }

    .info p:last-child {
        border-bottom: none;
    }

    .info span:first-child {
        font-weight: 700;
        color: var(--muted);
    }

    .visit_type {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(3, minmax(170px, 1fr));
        gap: 12px;
    }

    .visit_type a {
        text-decoration: none;
        color: #ffffff;
        text-align: center;
        font-size: 16px;
        font-weight: 700;
        border-radius: 12px;
        padding: 13px 10px;
        box-shadow: 0 12px 22px rgba(24, 41, 74, 0.24);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .visit_type a:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 28px rgba(24, 41, 74, 0.32);
    }

    #a {
        background: linear-gradient(120deg, #2f8d6b, #45ac84);
    }

    #b {
        background: linear-gradient(120deg, #2d5eb3, #3d7de0);
    }

    #c {
        background: linear-gradient(120deg, #b0602c, #d5823c);
    }

    @media (max-width: 900px) {
        .container {
            grid-template-columns: 1fr;
            margin: 16px 10px 30px;
            padding: 12px;
        }

        .visit_type {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        .container {
            animation: inUp 0.5s ease;
        }

        nav,
        .info {
            animation: inCard 0.6s ease;
        }
    }

    @keyframes inUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes inCard {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>


<body class="clinic-polished">

    <h1>بيانات المريض</h1>

    <div class="container">



        <!-- Patient Info -->
        <div class="info">
            <p><span>الرقم التسلسلي</span><span><?php echo htmlspecialchars($row['id']); ?></span></p>
            <p><span>الاسم</span><span><?php echo htmlspecialchars($row['full_name']); ?></span></p>
            <p><span>العمر</span><span><?php echo htmlspecialchars($row['age']); ?></span></p>
            <p><span>الجنس</span><span><?php echo htmlspecialchars($row['gender']); ?></span></p>
            <p><span>الموبايل</span><span><?php echo htmlspecialchars($row['phone_no']); ?></span></p>
            <p><span>الموبايل البديل</span><span><?php echo htmlspecialchars($row['phone_no_alt']); ?></span></p>
            <p><span>العنوان</span><span><?php echo htmlspecialchars($row['address']); ?></span></p>
        </div>

        <!-- Navigation -->
        <nav>
            <ul>
                <li><a href="dashboard.php">الصفحة الرئيسية</a></li>
                <li><a href="patient-file.php?id=<?php echo $row['id']; ?>">الزيارات</a></li>
                <li><a href="add-va.php?id=<?php echo $row['id']; ?>">إضافة فحص النظر</a></li>
                <li><a href="add-surgery.php?id=<?php echo $row['id']; ?>">إضافة عملية</a></li>
                <li><a href="add-image.php?id=<?php echo $row['id']; ?>">إضافة صور</a></li>
                <li><a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a></li>
            </ul>
        </nav>

        <!-- Visit Types -->
        <div class="visit_type">
            <a id="a" href="edit-visit2.php?visit_id=<?php echo $row['visit_id']; ?>&visit_type=first">زيارة أول مرة</a>
            <a id="b" href="edit-visit2.php?visit_id=<?php echo $row['visit_id']; ?>&visit_type=repeat">زيارة متكررة</a>
            <a id="c" href="edit-visit2.php?visit_id=<?php echo $row['visit_id']; ?>&visit_type=free">زيارة مراجعة</a>
        </div>

    </div>

</body>

</html>
