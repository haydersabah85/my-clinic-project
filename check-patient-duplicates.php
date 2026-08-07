<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

header('Content-Type: application/json; charset=utf-8');

function clinic_normalize_name_for_match($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace(['ـ', '-', '_', ',', ';', ':', '/', '\\', '(', ')', '[', ']', '{', '}', '"', "'"], ' ', $value);
    $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    return trim($value);
}

function clinic_name_tokens($value)
{
    $normalized = clinic_normalize_name_for_match($value);
    if ($normalized === '') {
        return [];
    }

    return array_values(array_filter(preg_split('/\s+/u', $normalized), static function ($token) {
        return $token !== '';
    }));
}

function clinic_name_similarity_score($inputName, $candidateName)
{
    $inputTokens = clinic_name_tokens($inputName);
    $candidateTokens = clinic_name_tokens($candidateName);

    if ($inputTokens === [] || $candidateTokens === []) {
        return 0;
    }

    $normalizedInput = implode(' ', $inputTokens);
    $normalizedCandidate = implode(' ', $candidateTokens);

    if ($normalizedInput === $normalizedCandidate) {
        return 100;
    }

    $commonTokens = count(array_intersect($inputTokens, $candidateTokens));
    if ($commonTokens >= 2) {
        return 90;
    }

    $shorter = count($inputTokens) <= count($candidateTokens) ? $inputTokens : $candidateTokens;
    $longer = count($inputTokens) > count($candidateTokens) ? $inputTokens : $candidateTokens;

    $prefixMatch = 0;
    foreach ($shorter as $index => $token) {
        if (($longer[$index] ?? null) === $token) {
            $prefixMatch++;
        } else {
            break;
        }
    }

    if ($prefixMatch >= 2) {
        return 80;
    }

    return 0;
}

$name = trim($_GET['name'] ?? '');
$phone = preg_replace('/\D+/', '', $_GET['phone'] ?? '');
$age = trim($_GET['age'] ?? '');

if ($name === '' && $phone === '') {
    echo json_encode(['matches' => []]);
    exit;
}

$activeWhere = clinic_active_patient_where($con, 'add_patient');
$sql = "
    SELECT id, full_name, age, phone_no
    FROM add_patient
    WHERE $activeWhere
    ORDER BY id DESC
    LIMIT 200
";

$result = mysqli_query($con, $sql);
$matches = [];

while ($row = mysqli_fetch_assoc($result)) {
    $candidateName = (string) ($row['full_name'] ?? '');
    $candidatePhone = preg_replace('/\D+/', '', (string) ($row['phone_no'] ?? ''));
    $candidateAge = trim((string) ($row['age'] ?? ''));

    $phoneMatch = $phone !== '' && $candidatePhone !== '' && $candidatePhone === $phone;
    $nameMatch = false;

    if ($name !== '') {
        $similarity = clinic_name_similarity_score($name, $candidateName);
        $nameMatch = $similarity >= 80;
    }

    $ageMatch = $age === '' || $candidateAge === '' || $age === $candidateAge;

    if ($phoneMatch || ($nameMatch && $ageMatch)) {
        $matches[] = [
            'id' => (int) $row['id'],
            'full_name' => $row['full_name'],
            'age' => $row['age'],
            'phone_no' => $row['phone_no'],
        ];

        if (count($matches) >= 5) {
            break;
        }
    }
}

echo json_encode(['matches' => $matches], JSON_UNESCAPED_UNICODE);
