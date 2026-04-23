<?php
$online = mysqli_connect(
    "srv547.hstgr.io",
    "u560090848_haider",
    "Rediahhabas5891",
    "u560090848_clinic_view",
    3306
);
if (!$online) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully to the online database.";
?>