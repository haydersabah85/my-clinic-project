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

$types = clinic_get_surgery_types($con);
if (!$types) {
    $types = ['Phaco', 'Vitrectomy', 'Phaco and Vitrectomy', 'SOR', 'Phaco and SOR', 'Squint', 'ECCE', 'ICCE', 'EUA', 'Probing', 'SMILE', 'PRK', 'AC Washout', 'Secondary IOL', 'Anterior Vitrectomy'];
}

clinic_render_appointment_page([
    'patient' => $patient,
    'title' => 'حجز موعد عملية',
    'kind' => 'قسم العمليات',
    'action' => 'surgery-appointment2.php',
    'type_field' => 'surgery_type',
    'type_label' => 'نوع العملية',
    'types' => $types,
    'date_label' => 'موعد العملية',
    'submit_name' => 'submit_surgery',
    'accent' => '#2563eb',
    'accent_soft' => 'rgba(37,99,235,.12)',
    'flash' => clinic_take_flash(),
    'readiness' => [
        'patient_verified' => 'تأكيد هوية المريض',
        'eye_verified' => 'تأكيد العين',
        'procedure_verified' => 'تأكيد نوع العملية',
        'consent_ready' => 'الموافقة الجراحية جاهزة',
        'iol_ready' => 'العدسة / IOL محددة عند الحاجة',
        'allergy_checked' => 'مراجعة الحساسية والأدوية',
        'investigations_ready' => 'الفحوصات المطلوبة جاهزة',
        'payment_reviewed' => 'مراجعة الدفع / الحالة الإدارية',
    ],
]);
