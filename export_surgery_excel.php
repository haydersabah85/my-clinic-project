<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

include "config.php";

$date = isset($_GET['date']) ? $_GET['date'] : '';

$sql = "
SELECT 
surgery_appointment.patient_id, 
add_patient.full_name AS patient_name, 
surgery_appointment.eye,
surgery_appointment.notes,
surgery_appointment.date,
surgery_appointment.surgery_type
FROM surgery_appointment 
JOIN add_patient ON surgery_appointment.patient_id = add_patient.id
WHERE surgery_appointment.date='$date'";

$result = mysqli_query($con,$sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1','patient_id');
$sheet->setCellValue('B1','patient_name');
$sheet->setCellValue('C1','eye');
$sheet->setCellValue('D1','surgery_type');
$sheet->setCellValue('E1','IOL');
$sheet->setCellValue('F1','notes');
$sheet->setCellValue('G1','date');

$row = 2;

while($data=mysqli_fetch_assoc($result)){

$sheet->setCellValue('A'.$row,$data['patient_id']);
$sheet->setCellValue('B'.$row,$data['patient_name']);
$sheet->setCellValue('C'.$row,$data['eye']);
$sheet->setCellValue('D'.$row,$data['surgery_type']);
$sheet->setCellValue('E'.$row,'');
$sheet->setCellValue('F'.$row,$data['notes']);
$sheet->setCellValue('G'.$row,$data['date']);


$row++;

}

# قائمة اختيار العين

for($i=2;$i<=100;$i++){

$validation = $sheet->getCell('C'.$i)->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setAllowBlank(true);
$validation->setShowDropDown(true);
$validation->setFormula1('"OD,OS,OU"');

}

# قائمة نوع العملية

for($i=2;$i<=100;$i++){

$validation = $sheet->getCell('D'.$i)->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);


$validation->setFormula1('"Phaco,Vitrectomy,Phaco and Vitrectomy,SOR,Phaco and SOR,Squint,Chalazion,EUA,Probing,SMILE,PRK,Secondary IOL,Pterygium with Graft,Pterygium,IOL Exchange"');

}

# قائمة العدسات

for($i=2;$i<=100;$i++){

$validation = $sheet->getCell('E'.$i)->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);


$validation->setFormula1('"Sensar,Eyhance,Alcon,Clareon,Synergy,Rayner Monofocal,Rayner Trifocal,Eleon,Artisan"');

}

$writer = new Xlsx($spreadsheet);

$file="surgery_list.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$file.'"');

$writer->save("php://output");

exit;

?>