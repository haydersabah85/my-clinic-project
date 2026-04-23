

<?php

$serverName = $_SERVER['SERVER_NAME'];

if ($serverName == 'localhost') {

    // 💻 اللابتوب أو العيادة
    $con = mysqli_connect("localhost", "haider", "1985", "clinic");

    $IS_LOCAL = true;

} else {

    // 🌐 الأونلاين
    $con = mysqli_connect("localhost", "user", "pass", "clinic_online");

    $IS_LOCAL = false; 
}





//$con = mysqli_connect("192.168.0.116", "haider", "1985", "clinic");
//$con = mysqli_connect("192.168.66.106", "haider", "1985", "clinic");

?>
