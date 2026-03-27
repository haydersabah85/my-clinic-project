<?php
include 'config.php';

// إحصائيات اليوم
$today = date('Y-m-d');

$surgery = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM surgery_appointment WHERE date='$today'"))['total'];
$laser = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM laser_appointment WHERE date='$today'"))['total'];
$injection = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM injection_appointment WHERE date='$today'"))['total'];
$followups = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as total FROM followups WHERE followup_date='$today'"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>

    <style>
        body {
            margin: 0;
            font-family: Arial;
            display: flex;
            background: #f5f6fa;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #1e272e;
            color: white;
            height: 100vh;
            padding: 20px;
        }

        .sidebar h2 {
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 10px 0;
        }

        .sidebar a:hover {
            color: #00cec9;
        }

        /* Main */
        .main {
            flex: 1;
            padding: 20px;
        }

        /* Search */
        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        /* Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .card {
            padding: 20px;
            border-radius: 12px;
            color: white;
            font-size: 18px;
        }

        .surgery { background: #6c5ce7; }
        .laser { background: #00b894; }
        .injection { background: #0984e3; }
        .followup { background: #d63031; }

        /* Results */
        .results {
            background: white;
            margin-top: 20px;
            border-radius: 10px;
            padding: 10px;
        }

        .patient {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .patient:hover {
            background: #f1f2f6;
        }

        /* Buttons */
        .actions {
            margin-top: 20px;
        }

        .actions a {
            padding: 10px 15px;
            margin-right: 10px;
            background: #0984e3;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Clinic</h2>
    <a href="#">🏠 Dashboard</a>
    <a href="patients.php">👤 المرضى</a>
    <a href="#">📅 المواعيد</a>
    <a href="#">📊 التقارير</a>
</div>

<!-- Main -->
<div class="main">

    <!-- Search -->
    <div class="search-box">
        <input type="text" id="search" placeholder="🔍 ابحث عن مريض بالاسم..." onkeyup="searchPatient()">
    </div>

    <!-- Results -->
    <div class="results" id="results"></div>

    <!-- Cards -->
    <div class="cards">
        <div class="card surgery">عمليات اليوم<br><strong><?php echo $surgery; ?></strong></div>
        <div class="card laser">ليزر<br><strong><?php echo $laser; ?></strong></div>
        <div class="card injection">إبر<br><strong><?php echo $injection; ?></strong></div>
        <div class="card followup">متابعات<br><strong><?php echo $followups; ?></strong></div>
    </div>

    <!-- Quick Actions -->
    <div class="actions">
        <a href="add-patient.php">+ إضافة مريض</a>
        <a href="#">+ إضافة متابعة</a>
    </div>

</div>

<script>
function searchPatient() {
    let query = document.getElementById("search").value;

    if (query.length < 2) {
        document.getElementById("results").innerHTML = "";
        return;
    }

    fetch("3.php?q=" + query)
        .then(res => res.text())
        .then(data => {
            document.getElementById("results").innerHTML = data;
        });
}
</script>

</body>
</html>