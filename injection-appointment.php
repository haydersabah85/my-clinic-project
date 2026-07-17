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

$types = clinic_get_injection_types($con);
if (!$types) {
    $types = ['Avastin', 'Eylea 2mg', 'Vabysmo', 'Eylea 8mg', 'Triamcinolone', 'Lucentis'];
}

clinic_render_appointment_page([
    'patient' => $patient,
    'title' => 'حجز موعد حقن',
    'kind' => 'قسم الحقن',
    'action' => 'injection-appointment2.php',
    'type_field' => 'injection_type',
    'type_label' => 'نوع الحقن',
    'types' => $types,
    'date_label' => 'موعد الحقن',
    'submit_name' => 'submit_injection',
    'accent' => '#7c3aed',
    'accent_soft' => 'rgba(124,58,237,.12)',
    'flash' => clinic_take_flash(),
]);
