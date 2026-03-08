<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    

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
    <h3>هل تمت العملية؟</h3>

    <!-- تمت العملية -->
    <form action="add-surgery.php?id=<?php echo $id; ?>" method="get">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <button class="done">نعم، تمت العملية</button>
    </form>

    <!-- لم يحضر -->
    <form action="discharge.php?id=<?php echo $id; ?>" method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <button class="dis" name="dis_btn">لا، لم يحضر المريض</button>
    </form>
</div>

</body>
</html>
