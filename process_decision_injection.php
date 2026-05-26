<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
    $appointment_date = $_GET['appointment_date'] ?? '';
    

} else {
    // Redirect or handle the error if 'id' is not set
    header("Location: error_page.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>قرار العملية</title>
<link rel="stylesheet" href="assets/theme.css">
<script src="assets/theme.js" defer></script>


    <link rel="stylesheet" href="assets/dark-mode.css">
</head>
<style>
.box{
    width:400px;
    margin:100px auto;
    padding:20px;
    border-radius:10px;
    background:#f9f9f9;
    text-align:center;
}
button{
    width:100%;
    padding:10px;
    margin-top:10px;
    font-size:16px;
    cursor:pointer;
}
.done{ background:#4CAF50; color:white; }
.dis{ background:#e74c3c; color:white; }
</style>


<body>


<div class="box">
    <h3>هل حضر المريض؟</h3>

     <!-- إبر -->
    <form action="add-injection.php" method="get">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
        <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date, ENT_QUOTES, 'UTF-8'); ?>">
        <button class="done">💉 تم إعطاء الإبرة</button>
    </form>


    
    <!-- لم يحضر -->
    <form action="discharge_injection.php" method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
        <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date, ENT_QUOTES, 'UTF-8'); ?>">
        <button class="dis" name="dis_btn">✖ لم يحضر المريض</button>
    </form>
</div>


</body>
</html>

