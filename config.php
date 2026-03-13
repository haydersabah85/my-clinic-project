<?php

//$con = mysqli_connect("192.168.0.116", "haider", "1985", "clinic");
//$con = mysqli_connect("192.168.66.106", "haider", "1985", "clinic");


$con = mysqli_connect("localhost", "root", "", "clinic");

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
?>