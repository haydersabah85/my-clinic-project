<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';
include_once 'clinic-appointment-page.php';

clinic_ensure_infrastructure($con);
$patientId = (int) ($_GET['id'] ?? 0);
$patient = clinic_load_appointment_patient($con, $patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}

$types = clinic_get_laser_types($con);
if (!$types) {
    $types = ['PRP', 'Retinopexy', 'Focal Laser', 'YAG', 'PI'];
}

clinic_render_appointment_page([
    'patient' => $patient,
    'title' => 'حجز موعد ليزر',
    'kind' => 'قسم الليزر',
    'action' => 'laser-appointment2.php',
    'type_field' => 'laser_type',
    'type_label' => 'نوع الليزر',
    'types' => $types,
    'date_label' => 'موعد جلسة الليزر',
    'submit_name' => 'submit_laser',
    'accent' => '#0891b2',
    'accent_soft' => 'rgba(8,145,178,.12)',
    'flash' => clinic_take_flash(),
]);
