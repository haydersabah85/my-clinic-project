<?php

include "config.php";

include 'auth.php';

if (isset($_GET['id_open'])) {
    $id = $_GET['id_open'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}
?>
<?php
include 'config.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $patient_id = $id;
    
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>بيانات <?php echo htmlspecialchars($row['full_name']); ?> 📁</title>

    <script src="assets/theme.js" defer></script>
    
</head>

    <style>
/*================= DARK THEME =================*/
body[data-theme="dark"] {
    background: linear-gradient(135deg, #1e1e1e, #262626);
    color: #e0e0e0;
}
body[data-theme="dark"] .container {
    background: linear-gradient(135deg, #2c2c2c, #333333);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
}
body[data-theme="dark"] nav {   
    background: linear-gradient(135deg, #3a3a3a, #4a4a4a);
    box-shadow: 0 6px 14px rgba(0, 0,
    0, 0.5);
}
body[data-theme="dark"] nav ul li {
    background: linear-gradient(135deg, #4a4a4a, #5a5a5a);
}
body[data-theme="dark"] nav ul li:hover {
    background: linear-gradient(135deg, #6a6a6a, #7a7a7a);
}
body[data-theme="dark"] nav ul li a {
    color: #f0f0f0;
}
body[data-theme="dark"] .info {
    background: linear-gradient(135deg, #2e2e2e, #3a3a3a);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.5);
}
body[data-theme="dark"] .info p {
    border-bottom: 1px dashed #555555;
}
body[data-theme="dark"] .visit_type a {
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.5);
}
body[data-theme="dark"] .visit_type a:hover {
    opacity: 0.9;
}
/*================= END DARK THEME =================*/



        /* ================== Global ================== */

        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f4f7fb, #eaf1f7);
            color: #2c3e50;
        }

        /* ================== Title ================== */
        h1 {
            text-align: center;
            margin: 25px 0;
            font-size: 32px;
            color: #8b2e2e;
            font-weight: 700;
        }

        /* ================== Container ================== */
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 25px;
            background: linear-gradient(135deg, #ffffff, #f8fbff);
            border-radius: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        /* ================== Navigation ================== */
        nav {
            flex: 1 1 240px;
            background: linear-gradient(135deg, #fff3e6, #ffe7cc);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        }

        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        nav ul li {
            background: linear-gradient(135deg, #ffe2c6, #ffd1a3);
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        nav ul li:hover {
            background: linear-gradient(135deg, #ffb870, #ffa24d);
            transform: translateX(-6px);
        }

        nav ul li a {
            text-decoration: none;
            color: #2b2b2b;
            font-weight: 700;
            display: block;
            text-align: center;
            font-size: 16px;
        }

        /* ================== Patient Info ================== */
        .info {
            flex: 2 1 450px;
            background: linear-gradient(135deg, #f9fcf8, #f1f6ee);
            border-radius: 16px;
            padding: 22px;
            font-size: 18px;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        }

        .info p {
            margin: 12px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #cfd8dc;
            padding-bottom: 8px;
        }

        .info span:first-child {
            font-weight: 700;
            color: #34495e;
        }

        /* ================== Visit Buttons ================== */
        .visit_type {
            width: 100%;
            display: flex;
            justify-content: center;
            gap: 18px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .visit_type a {
            padding: 12px 24px;
            border-radius: 30px;
            color: #fff;
            text-decoration: none;
            font-size: 17px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
        }

        .visit_type a:hover {
            transform: translateY(-4px) scale(1.05);
            opacity: 0.95;
        }

        /* ألوان طبية هادئة */
        #a {
            background: linear-gradient(135deg, #6fbf73, #3fa75a);
        }

        #b {
            background: linear-gradient(135deg, #3fa7d6, #2c82b7);
        }

        #c {
            background: linear-gradient(135deg, #b3396d, #8e2a55);
        }

        /* ================== Responsive ================== */
        @media (max-width: 992px) {
            h1 {
                font-size: 26px;
            }

            .info {
                font-size: 16px;
            }

            .container {
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            nav {
                flex: 1 1 100%;
            }

            .info {
                flex: 1 1 100%;
            }

            nav ul li a {
                font-size: 15px;
            }

            .visit_type a {
                font-size: 15px;
                padding: 10px 18px;
            }
        }

        /* ================== Animations ================== */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeSlideRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ================== Entry Animations ================== */
        .container {
            animation: fadeSlideUp 0.8s ease forwards;
        }

        nav {
            animation: fadeSlideRight 0.9s ease forwards;
        }

        .info {
            animation: fadeSlideUp 1s ease forwards;
        }

        .visit_type a {
            animation: fadeSlideUp 1.1s ease forwards;
        }

        /* ================== Icons for Navigation ================== */
        nav ul li a::before {
            margin-left: 8px;
            font-size: 18px;
        }

        /* ترتيب الأيقونات حسب العنصر */
        nav ul li:nth-child(1) a::before {
            content: "🏠";
        }

        nav ul li:nth-child(2) a::before {
            content: "👤";
        }

        nav ul li:nth-child(3) a::before {
            content: "📅";
        }

        nav ul li:nth-child(4) a::before {
            content: "🧾";
        }

        nav ul li:nth-child(5) a::before {
            content: "📊";
        }

        nav ul li:nth-child(6) a::before {
            content: "🚪";
        }

        /* ================== Icons for Visit Buttons ================== */
        #a::before {
            content: "➕ ";
        }

        #b::before {
            content: "📝 ";
        }

        #c::before {
            content: "📁 ";
        }

        /* ================== Hover Enhancements ================== */
        nav ul li:hover a::before {
            transform: scale(1.2);
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .visit_type a:hover::before {
            transform: rotate(-8deg) scale(1.2);
            display: inline-block;
            transition: transform 0.3s ease;
        }
    </style>


<body>

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
                <li><a href="main.php">الصفحة الرئيسية</a></li>
                <li><a href="patient-file.php?id=<?php echo $row['id']; ?>">الزيارات</a></li>
                <li><a href="add-va.php?id=<?php echo $row['id']; ?>">إضافة فحص النظر</a></li>
                <li><a href="add-surgery.php?id=<?php echo $row['id']; ?>">إضافة عملية</a></li>
                <li><a href="add-image.php?id=<?php echo $row['id']; ?>">إضافة صور</a></li>
                <li><a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a></li>
            </ul>
        </nav>

        <!-- Visit Types -->
        <div class="visit_type">
            <a id="a" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=first">زيارة أول مرة</a>
            <a id="b" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=repeat">زيارة متكررة</a>
            <a id="c" href="visits2.php?patient_id=<?php echo $row['id']; ?>&visit_type=free">زيارة مراجعة</a>
        </div>

    </div>

</body>

</html>