<?php
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مواعيد العمليات</title>
</head>


<style>

/* ===== Body & Headings ===== */
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: linear-gradient(to bottom, #f0f4f8, #e2e8f0);
}

h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 20px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

/* ===== Link Buttons ===== */
a {
    display: flex;
    padding: 8px 12px;
    margin-bottom: 20px;
    text-decoration: none;
    color: #4CAF50;
    font-weight: bold;
    font-size: 18px;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
}

a:hover {
    color: #ffffff;
    background: linear-gradient(90deg, #28a745, #1fa974);
    padding: 10px 15px;
    border-radius: 8px;
    width: 25%;
    margin: 0 auto;

}

/* ===== Date Buttons Container ===== */
.date-buttons {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

/* ===== Individual Date Buttons ===== */
.date-btn {
    padding: 10px 20px;
    margin: 5px;
    border: none;
    background: linear-gradient(90deg, #007bff, #00a1ff);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.date-btn:hover {
    background: linear-gradient(90deg, #0056b3, #0078d7);
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

/* ===== Operations Result Section ===== */
#operations_result {
    margin-top: 20px;
    padding: 20px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* ===== Responsive ===== */
@media screen and (max-width: 768px) {
    .date-buttons {
        flex-direction: column;
        align-items: center;
    }

    .date-btn {
        width: 80%;
        text-align: center;
    }
}


</style>

<body>
<h2>مواعيد العمليات</h2>

    
<a href="main.php">الصفحة الرئيسية</a>
<a href="confirmed-list.php">قائمة العمليات المؤكدة</a>

<?php
include "config.php";


$query = $con->query("
    SELECT DISTINCT date FROM (
        SELECT date FROM surgery_appointment
        UNION
        SELECT date FROM laser_appointment
        UNION
        SELECT date FROM injection_appointment
    ) AS all_dates
    WHERE date IS NOT NULL
      AND date <> '0000-00-00'
    ORDER BY date DESC
");

  
?>

<div class="date-buttons">
    <?php while($row = $query->fetch_assoc()): ?>
        <button class="date-btn" onclick="loadOperations('<?php echo $row['date']; ?>')">
            <?php echo $row['date']; ?>
        </button>
    <?php endwhile; ?>
</div>

<div id="operations_result">
    <!-- هنا ستظهر العمليات بعد اختيار التاريخ -->
</div>

<script>
function loadOperations(date) {
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "load_operations.php?date=" + date, true);
    xhr.onload = function() {
        document.getElementById("operations_result").innerHTML = this.responseText;
    };
    xhr.send();
}
</script>


</body>
</html>