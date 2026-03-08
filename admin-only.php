<?php
if ($_SESSION['role'] != 'admin') {
    exit("غير مصرح لك بالدخول");
}
?>