<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

include "config.php";

$file = $_FILES['excel_file']['tmp_name'];

$spreadsheet = IOFactory::load($file);

$sheet = $spreadsheet->getActiveSheet();

$data = $sheet->toArray();

foreach($data as $index => $row){

if($index==0) continue;

$patient_id = $row[0];
$patient_name = $row[1];
$eye = $row[2];
$surgery_type = $row[3];
$iol = $row[4];
$notes = $row[5];
$date = $row[6];

if($patient_id=="") continue;

$sql="INSERT INTO surgery
(patient_id,eye,surgery_type,iol_type,notes,date)
VALUES
('$patient_id','$eye','$surgery_type','$iol','$notes','$date')";

mysqli_query($con,$sql);

$update_sql = "UPDATE surgery_appointment SET status = 'done' WHERE patient_id = '$patient_id' AND DATE(date) = '$date'";
mysqli_query($con,$update_sql);


}
echo "Data Imported Successfully";
header("Location: main.php");
exit();
?>