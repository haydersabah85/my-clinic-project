<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$flash = clinic_take_flash();
$currentRole = strtolower((string) ($_SESSION['role'] ?? ''));
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentUserName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? $_SESSION['user'] ?? clinic_current_user()));
if ($currentUserName === '') {
    $currentUserName = clinic_current_user();
}

$isAdmin = ($currentRole === 'admin');
$isSecretary = ($currentRole === 'secretary');
$canCreateRequests = $isAdmin;
$canUpdateRequests = $isSecretary || $isAdmin;

$requestTypeOptions = [
    'oct_macula' => 'OCT Macula',
    'oct_disc' => 'OCT Disc',
    'oct_rnfl' => 'OCT RNFL',
    'fundus_photo' => 'Fundus Photo',
    'ffa' => 'FFA',
    'other' => 'أخرى',
];

$priorityOptions = [
    'normal' => 'اعتيادي',
    'urgent' => 'مستعجل',
];

$statusOptions = [
    'pending' => 'قيد الانتظار',
    'in_progress' => 'قيد الإجراء',
    'done' => 'مكتمل',
    'cancelled' => 'ملغي',
];

$eyeOptions = [
    'OU' => 'OU (كلتا العينين)',
    'OD' => 'OD',
    'OS' => 'OS',
    '-' => 'غير محدد',
];

function exam_requests_redirect(array $params = []): void
{
    $query = http_build_query($params);
    header('Location: exam-requests.php' . ($query !== '' ? ('?' . $query) : ''));
    exit;
}

function exam_requests_status_class(string $status): string
{
    switch ($status) {
        case 'done':
            return 'st-done';
        case 'in_progress':
            return 'st-progress';
        case 'cancelled':
            return 'st-cancelled';
        default:
            return 'st-pending';
    }
}

$patientId = (int) ($_GET['patient_id'] ?? $_GET['id'] ?? 0);
$statusFilter = strtolower(trim((string) ($_GET['status'] ?? ($isSecretary ? 'open' : 'all'))));
$allowedFilters = ['all', 'open', 'pending', 'in_progress', 'done', 'cancelled'];
if (!in_array($statusFilter, $allowedFilters, true)) {
    $statusFilter = $isSecretary ? 'open' : 'all';
}

$search = trim((string) ($_GET['q'] ?? ''));
$activePatientWhere = clinic_active_patient_where($con, 'add_patient');
$selectedPatient = null;

if ($patientId > 0) {
    $patientStmt = mysqli_prepare($con, "SELECT id, full_name, age, phone_no FROM add_patient WHERE id = ? AND $activePatientWhere LIMIT 1");
    if ($patientStmt) {
        mysqli_stmt_bind_param($patientStmt, 'i', $patientId);
        mysqli_stmt_execute($patientStmt);
        $patientResult = mysqli_stmt_get_result($patientStmt);
        $selectedPatient = $patientResult ? mysqli_fetch_assoc($patientResult) : null;
        mysqli_stmt_close($patientStmt);
    }

    if (!$selectedPatient) {
        $patientId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clinic_require_csrf();

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'create_request') {
        if (!$canCreateRequests) {
            clinic_set_flash('error', 'هذا الحساب غير مخول بإنشاء طلبات الفحوصات.');
            exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
        }

        $formPatientId = (int) ($_POST['patient_id'] ?? 0);
        $requestTypeKey = trim((string) ($_POST['request_type'] ?? ''));
        $customRequestType = trim((string) ($_POST['custom_request_type'] ?? ''));
        $eye = strtoupper(trim((string) ($_POST['eye'] ?? 'OU')));
        $priority = trim((string) ($_POST['priority'] ?? 'normal'));
        $requestedForDate = trim((string) ($_POST['requested_for_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (!isset($requestTypeOptions[$requestTypeKey])) {
            clinic_set_flash('error', 'نوع الفحص غير صالح.');
            exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
        }

        if ($requestTypeKey === 'other') {
            if ($customRequestType === '') {
                clinic_set_flash('error', 'يرجى كتابة نوع الفحص في حقل أخرى.');
                exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
            }
            $requestType = $customRequestType;
        } else {
            $requestType = (string) $requestTypeOptions[$requestTypeKey];
        }

        if (!in_array($eye, ['OU', 'OD', 'OS', '-'], true)) {
            $eye = 'OU';
        }

        if (!isset($priorityOptions[$priority])) {
            $priority = 'normal';
        }

        if ($requestedForDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedForDate) !== 1) {
            clinic_set_flash('error', 'تاريخ الطلب غير صالح.');
            exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
        }

        $patientName = '';
        $verifyStmt = mysqli_prepare($con, "SELECT id, full_name FROM add_patient WHERE id = ? AND $activePatientWhere LIMIT 1");
        if ($verifyStmt) {
            mysqli_stmt_bind_param($verifyStmt, 'i', $formPatientId);
            mysqli_stmt_execute($verifyStmt);
            $verifyResult = mysqli_stmt_get_result($verifyStmt);
            $verifyRow = $verifyResult ? mysqli_fetch_assoc($verifyResult) : null;
            mysqli_stmt_close($verifyStmt);

            if ($verifyRow) {
                $formPatientId = (int) ($verifyRow['id'] ?? 0);
                $patientName = trim((string) ($verifyRow['full_name'] ?? ''));
            }
        }

        if ($formPatientId <= 0 || $patientName === '') {
            clinic_set_flash('error', 'يرجى اختيار مريض صالح قبل إرسال الطلب.');
            exam_requests_redirect(['status' => $statusFilter]);
        }

        $syncStatus = $IS_LOCAL ? 0 : 1;
        $insertStmt = mysqli_prepare($con, '
            INSERT INTO exam_requests
            (patient_id, patient_name, request_type, eye, priority, requested_for_date, notes, status, result_notes, requested_by_user_id, requested_by_name, sync_status, created_at, updated_at)
              VALUES (?, ?, ?, ?, ?, NULLIF(?, ""), ?, "pending", NULL, ?, ?, ?, NOW(), NOW())
        ');

        if (!$insertStmt) {
            clinic_set_flash('error', 'تعذر تجهيز حفظ طلب الفحص.');
            exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
        }

        $dateForInsert = ($requestedForDate !== '') ? $requestedForDate : '';
        mysqli_stmt_bind_param(
            $insertStmt,
            'issssssisi',
            $formPatientId,
            $patientName,
            $requestType,
            $eye,
            $priority,
            $dateForInsert,
            $notes,
            $currentUserId,
            $currentUserName,
            $syncStatus
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            clinic_set_flash('error', 'فشل حفظ طلب الفحص.');
            exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
        }

        $newRequestId = (int) mysqli_insert_id($con);

        $secretaries = [];
        $secretaryResult = mysqli_query($con, "SELECT id FROM users WHERE role = 'secretary'");
        while ($secretaryResult && ($sec = mysqli_fetch_assoc($secretaryResult))) {
            $secretaryId = (int) ($sec['id'] ?? 0);
            if ($secretaryId > 0) {
                $secretaries[] = $secretaryId;
            }
        }

        if (!empty($secretaries) && $currentUserId > 0) {
            $messageText = 'طلب فحص جديد للمريض ' . $patientName . ' | ' . $requestType . ' | رقم الطلب #' . $newRequestId;
            $msgStmt = mysqli_prepare($con, 'INSERT INTO staff_messages (sender_user_id, recipient_user_id, message_text, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
            if ($msgStmt) {
                foreach ($secretaries as $recipientId) {
                    mysqli_stmt_bind_param($msgStmt, 'iis', $currentUserId, $recipientId, $messageText);
                    mysqli_stmt_execute($msgStmt);
                }
                mysqli_stmt_close($msgStmt);
            }
        }

        clinic_set_flash('success', 'تم إرسال طلب الفحص بنجاح وظهر للسكرتيرة.');
        exam_requests_redirect(['patient_id' => $formPatientId, 'status' => $statusFilter]);
    }

    if ($action === 'update_request') {
        if (!$canUpdateRequests) {
            clinic_set_flash('error', 'هذا الحساب غير مخول بتحديث طلبات الفحص.');
            exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $newStatus = trim((string) ($_POST['new_status'] ?? 'pending'));
        $resultNotes = trim((string) ($_POST['result_notes'] ?? ''));

        if ($requestId <= 0 || !isset($statusOptions[$newStatus])) {
            clinic_set_flash('error', 'بيانات التحديث غير صالحة.');
            exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
        }

        $updateStmt = mysqli_prepare($con, '
            UPDATE exam_requests
            SET status = ?,
                result_notes = ?,
                handled_by_user_id = ?,
                handled_by_name = ?,
                handled_at = IF(? = "pending", NULL, NOW()),
                updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ');

        if (!$updateStmt) {
            clinic_set_flash('error', 'تعذر تجهيز تحديث الطلب.');
            exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
        }

        mysqli_stmt_bind_param($updateStmt, 'ssissi', $newStatus, $resultNotes, $currentUserId, $currentUserName, $newStatus, $requestId);
        if (!mysqli_stmt_execute($updateStmt)) {
            clinic_set_flash('error', 'فشل تحديث الطلب.');
            exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
        }

        clinic_set_flash('success', 'تم تحديث حالة الطلب بنجاح.');
        exam_requests_redirect(['patient_id' => $patientId, 'status' => $statusFilter]);
    }
}

$patientSearchResults = [];
if ($patientId <= 0 && $search !== '') {
    $searchLike = '%' . $search . '%';
    $searchId = (int) $search;
    $searchStmt = mysqli_prepare($con, "
        SELECT id, full_name, age, phone_no
        FROM add_patient
        WHERE $activePatientWhere
          AND (full_name LIKE ? OR phone_no LIKE ? OR id = ?)
        ORDER BY id DESC
        LIMIT 30
    ");

    if ($searchStmt) {
        mysqli_stmt_bind_param($searchStmt, 'ssi', $searchLike, $searchLike, $searchId);
        mysqli_stmt_execute($searchStmt);
        $searchResult = mysqli_stmt_get_result($searchStmt);
        while ($searchResult && ($searchRow = mysqli_fetch_assoc($searchResult))) {
            $patientSearchResults[] = $searchRow;
        }
        mysqli_stmt_close($searchStmt);
    }
}

$listWhere = ['1=1'];
if ($statusFilter === 'open') {
    $listWhere[] = "r.status IN ('pending', 'in_progress')";
} elseif ($statusFilter !== 'all') {
    $statusEsc = mysqli_real_escape_string($con, $statusFilter);
    $listWhere[] = "r.status = '$statusEsc'";
}
if ($patientId > 0) {
    $listWhere[] = 'r.patient_id = ' . $patientId;
}
if ($search !== '' && $patientId <= 0) {
    $searchEsc = mysqli_real_escape_string($con, $search);
    $listWhere[] = "(r.patient_name LIKE '%$searchEsc%' OR r.request_type LIKE '%$searchEsc%' OR CAST(r.patient_id AS CHAR) = '$searchEsc')";
}

$listSql = '
    SELECT
        r.*,
        p.full_name AS latest_patient_name,
        p.phone_no
    FROM exam_requests r
    LEFT JOIN add_patient p ON p.id = r.patient_id
    WHERE ' . implode(' AND ', $listWhere) . '
    ORDER BY FIELD(r.status, "pending", "in_progress", "done", "cancelled"), COALESCE(r.requested_for_date, DATE(r.created_at)) DESC, r.id DESC
    LIMIT 300
';

$listRows = [];
$listResult = mysqli_query($con, $listSql);
while ($listResult && ($listRow = mysqli_fetch_assoc($listResult))) {
    $listRows[] = $listRow;
}

$countWhere = ['1=1'];
if ($patientId > 0) {
    $countWhere[] = 'patient_id = ' . $patientId;
}
$countSql = 'SELECT status, COUNT(*) AS c FROM exam_requests WHERE ' . implode(' AND ', $countWhere) . ' GROUP BY status';
$countResult = mysqli_query($con, $countSql);
$statusCounts = [
    'pending' => 0,
    'in_progress' => 0,
    'done' => 0,
    'cancelled' => 0,
];
while ($countResult && ($countRow = mysqli_fetch_assoc($countResult))) {
    $st = (string) ($countRow['status'] ?? '');
    if (isset($statusCounts[$st])) {
        $statusCounts[$st] = (int) ($countRow['c'] ?? 0);
    }
}
$totalCount = array_sum($statusCounts);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات الفحوصات | عيادة الدكتور حيدر صباح الربيعي</title>
    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">
    <link rel="stylesheet" href="assets/branding/branding.css">
    <script src="assets/theme.js" defer></script>
    <style>
        :root {
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #dbe4ef;
            --primary: #0f766e;
            --primary-strong: #115e59;
            --blue: #1d4ed8;
            --blue-soft: #dbeafe;
            --teal-soft: #ccfbf1;
            --danger: #b91c1c;
            --warn: #b45309;
            --ok: #166534;
        }

        body[data-theme="dark"] {
            --bg: #091321;
            --card: #101c2d;
            --text: #e2e8f0;
            --muted: #8ea2b9;
            --line: rgba(148, 163, 184, 0.28);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(14, 165, 233, 0.14), transparent 24%),
                linear-gradient(180deg, rgba(15, 118, 110, 0.08) 0, transparent 220px),
                var(--bg);
            color: var(--text);
            padding: 18px;
        }

        .page {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            gap: 14px;
        }

        .topbar,
        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }

        .topbar {
            position: relative;
            overflow: hidden;
            padding: 18px;
            display: grid;
            gap: 12px;
            background: linear-gradient(135deg, #0f766e, #0f5ea8 56%, #0284c7);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.18);
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.16);
        }

        .topbar::before {
            content: "";
            position: absolute;
            inset: auto -8% -48% auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.10);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 999px;
            padding: 5px 10px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 11px;
            font-weight: 900;
        }

        .topbar h1 {
            margin: 0;
            font-size: clamp(26px, 3vw, 34px);
        }

        .topbar p {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 700;
            max-width: 880px;
            line-height: 1.9;
        }

        .links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .links a,
        .btn,
        button {
            border: 1px solid transparent;
            border-radius: 10px;
            min-height: 38px;
            padding: 7px 12px;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        .links a:hover,
        .btn:hover,
        button:hover {
            transform: translateY(-1px);
        }

        .links a,
        .btn-secondary {
            background: #f8fafc;
            border-color: var(--line);
            color: #1e3a8a;
        }

        .topbar .links a {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.22);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }

        .topbar .links a:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .btn-secondary:hover {
            background: #eef6ff;
            border-color: #bfd5f5;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #0ea5a3);
            color: #fff;
            box-shadow: 0 12px 24px rgba(15, 118, 110, 0.18);
        }

        .btn-primary:hover {
            box-shadow: 0 16px 30px rgba(15, 118, 110, 0.22);
        }

        .notice {
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 800;
            border: 1px solid;
        }

        .notice.success {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }

        .notice.error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .stats {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            text-align: right;
            position: relative;
            overflow: hidden;
        }

        .stat::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, var(--primary), var(--blue));
        }

        .stat strong {
            display: block;
            font-size: 24px;
            color: var(--blue);
        }

        .stat span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(340px, 0.95fr) minmax(500px, 1.5fr);
            gap: 14px;
            align-items: start;
        }

        .panel {
            padding: 16px;
        }

        h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .panel-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.8;
        }

        .muted {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .patient-card {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            background: linear-gradient(135deg, rgba(219, 234, 254, 0.86), rgba(204, 251, 241, 0.82));
            margin-bottom: 12px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .patient-card strong {
            display: block;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .search-row {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(0, 1fr) auto;
            margin-bottom: 10px;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            min-height: 40px;
            padding: 9px 11px;
            font: inherit;
            background: #fff;
            color: #0f172a;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.12);
        }

        body[data-theme="dark"] input,
        body[data-theme="dark"] select,
        body[data-theme="dark"] textarea {
            background: #0a1627;
            color: var(--text);
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .patient-list {
            display: grid;
            gap: 8px;
            max-height: 240px;
            overflow: auto;
        }

        .patient-item {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: #f8fbff;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .patient-item:hover {
            transform: translateY(-1px);
            border-color: #bfdbfe;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.08);
        }

        body[data-theme="dark"] .patient-item {
            background: #0c1728;
        }

        .filter-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .filter-row a {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 7px 12px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            color: var(--muted);
            background: #fff;
            transition: all 0.2s ease;
        }

        .filter-row a:hover {
            border-color: #99f6e4;
            background: #f0fdfa;
            color: var(--primary);
        }

        .filter-row a.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(248, 250, 252, 0.88));
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
            background: var(--card);
        }

        th,
        td {
            border-top: 1px solid var(--line);
            padding: 10px 8px;
            text-align: right;
            vertical-align: top;
            font-size: 13px;
        }

        th {
            background: #f8fafc;
            color: #0f172a;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        body[data-theme="dark"] th {
            background: #0b1628;
            color: var(--text);
        }

        tbody tr:nth-child(even) {
            background: rgba(248, 250, 252, 0.65);
        }

        tbody tr:hover {
            background: rgba(186, 230, 253, 0.24);
        }

        body[data-theme="dark"] tbody tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.36);
        }

        body[data-theme="dark"] tbody tr:hover {
            background: rgba(30, 64, 175, 0.18);
        }

        .status-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid;
        }

        .st-pending {
            color: var(--warn);
            border-color: #fdba74;
            background: #fff7ed;
        }

        .st-progress {
            color: #1d4ed8;
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .st-done {
            color: var(--ok);
            border-color: #86efac;
            background: #ecfdf5;
        }

        .st-cancelled {
            color: var(--danger);
            border-color: #fca5a5;
            background: #fef2f2;
        }

        .priority {
            font-size: 11px;
            font-weight: 900;
        }

        .priority.urgent {
            color: #b91c1c;
        }

        .row-form {
            display: grid;
            gap: 6px;
        }

        .row-form textarea {
            min-height: 66px;
        }

        .empty {
            margin: 0;
            border: 1px dashed var(--line);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            color: var(--muted);
        }

        body[data-theme="dark"] .topbar {
            box-shadow: 0 22px 50px rgba(0, 0, 0, 0.3);
        }

        body[data-theme="dark"] .patient-card,
        body[data-theme="dark"] .table-wrap,
        body[data-theme="dark"] .stat,
        body[data-theme="dark"] .panel {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
        }

        body[data-theme="dark"] .patient-card {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(12, 74, 110, 0.54));
        }

        body[data-theme="dark"] .btn-secondary,
        body[data-theme="dark"] .filter-row a,
        body[data-theme="dark"] .patient-item {
            background: #0a1627;
            color: var(--text);
        }

        @media (max-width: 1024px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="topbar">
            <div>
                <span class="eyebrow">نظام الطلبات التشخيصية</span>
                <h1>طلبات الفحوصات</h1>
                <p>إرسال طلبات OCT / OCT Disc وغيرها من الطبيب، ومتابعتها من السكرتيرة مع ارتباط مباشر بملف المريض.</p>
            </div>
            <div class="links">
                <a href="dashboard.php">لوحة التحكم</a>
                <a href="main.php">بيانات المرضى</a>
                <a href="procedure-entries.php">إدخال الإجراءات</a>
                <?php if ($selectedPatient): ?>
                    <a href="patient-file.php?id=<?= (int) $selectedPatient['id'] ?>">ملف المريض</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= (($flash['type'] ?? '') === 'success') ? 'success' : 'error' ?>">
                <?= h($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><strong><?= (int) $totalCount ?></strong><span>إجمالي الطلبات</span></div>
            <div class="stat"><strong><?= (int) $statusCounts['pending'] ?></strong><span>قيد الانتظار</span></div>
            <div class="stat"><strong><?= (int) $statusCounts['in_progress'] ?></strong><span>قيد الإجراء</span></div>
            <div class="stat"><strong><?= (int) $statusCounts['done'] ?></strong><span>مكتمل</span></div>
            <div class="stat"><strong><?= (int) $statusCounts['cancelled'] ?></strong><span>ملغي</span></div>
        </section>

        <section class="layout">
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>إنشاء طلب فحص جديد</h2>
                        <p class="panel-subtitle">اختر المريض وحدد نوع الفحص والأولوية والملاحظات قبل الإرسال إلى السكرتيرة.</p>
                    </div>
                </div>
                <?php if (!$canCreateRequests): ?>
                    <p class="empty">إنشاء الطلبات متاح للطبيب/الادمن فقط. يمكنك من هذه الصفحة متابعة الحالة وتحديثها.</p>
                <?php else: ?>
                    <?php if ($selectedPatient): ?>
                        <div class="patient-card">
                            <strong class="clinic-user-content" data-no-translate><?= h($selectedPatient['full_name'] ?? '') ?></strong>
                            <div class="muted">ID: <?= (int) $selectedPatient['id'] ?> | الهاتف: <?= h($selectedPatient['phone_no'] ?? '-') ?> | العمر: <?= h($selectedPatient['age'] ?? '-') ?></div>
                            <div class="actions" style="margin-top:8px;">
                                <a class="btn-secondary" href="patient-file.php?id=<?= (int) $selectedPatient['id'] ?>">فتح ملف المريض</a>
                                <a class="btn-secondary" href="exam-requests.php?status=<?= urlencode($statusFilter) ?>">تغيير المريض</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="get" action="exam-requests.php">
                            <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
                            <div class="search-row">
                                <input type="text" name="q" value="<?= h($search) ?>" placeholder="بحث بالمريض (اسم / هاتف / رقم ملف)">
                                <button class="btn-secondary" type="submit">بحث</button>
                            </div>
                        </form>

                        <?php if (!empty($patientSearchResults)): ?>
                            <div class="patient-list">
                                <?php foreach ($patientSearchResults as $item): ?>
                                    <div class="patient-item">
                                        <div>
                                            <strong class="clinic-user-content" data-no-translate><?= h($item['full_name'] ?? '') ?></strong>
                                            <div class="muted">ID: <?= (int) ($item['id'] ?? 0) ?> | <?= h($item['phone_no'] ?? '-') ?></div>
                                        </div>
                                        <a class="btn-secondary" href="exam-requests.php?patient_id=<?= (int) ($item['id'] ?? 0) ?>&status=<?= urlencode($statusFilter) ?>">اختيار</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($search !== ''): ?>
                            <p class="empty">لا توجد نتائج مطابقة.</p>
                        <?php else: ?>
                            <p class="empty">ابحث عن المريض أولاً ثم أنشئ الطلب.</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($selectedPatient): ?>
                        <form method="post" style="display:grid;gap:8px;">
                            <?= clinic_csrf_input() ?>
                            <input type="hidden" name="action" value="create_request">
                            <input type="hidden" name="patient_id" value="<?= (int) $selectedPatient['id'] ?>">

                            <div class="grid-2">
                                <div>
                                    <label for="request_type">نوع الفحص</label>
                                    <select id="request_type" name="request_type" required>
                                        <option value="">اختر نوع الفحص</option>
                                        <?php foreach ($requestTypeOptions as $typeKey => $typeLabel): ?>
                                            <option value="<?= h($typeKey) ?>"><?= h($typeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="custom_request_type">تفصيل نوع آخر (اختياري)</label>
                                    <input id="custom_request_type" type="text" name="custom_request_type" placeholder="مثال: OCT Angiography">
                                </div>
                            </div>

                            <div class="grid-2">
                                <div>
                                    <label for="eye">العين</label>
                                    <select id="eye" name="eye" required>
                                        <?php foreach ($eyeOptions as $eyeValue => $eyeLabel): ?>
                                            <option value="<?= h($eyeValue) ?>"><?= h($eyeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="priority">الأولوية</label>
                                    <select id="priority" name="priority" required>
                                        <?php foreach ($priorityOptions as $priorityValue => $priorityLabel): ?>
                                            <option value="<?= h($priorityValue) ?>"><?= h($priorityLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid-2">
                                <div>
                                    <label for="requested_for_date">تاريخ مطلوب للفحص (اختياري)</label>
                                    <input id="requested_for_date" type="date" name="requested_for_date" value="<?= h(date('Y-m-d')) ?>">
                                </div>
                                <div>
                                    <label>الإجراء المقترح بعد الفحص</label>
                                    <input type="text" value="يظهر للسكرتيرة مباشرة بعد الحفظ" disabled>
                                </div>
                            </div>

                            <div>
                                <label for="notes">ملاحظات للطبيب/السكرتيرة</label>
                                <textarea id="notes" name="notes" placeholder="مثال: المريض يحتاج تصوير OCT Disc قبل القرار العلاجي"></textarea>
                            </div>

                            <div class="actions">
                                <button class="btn-primary" type="submit">إرسال الطلب</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </article>

            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2>قائمة الطلبات</h2>
                        <p class="panel-subtitle">تصفية سريعة حسب الحالة مع انتقال مباشر إلى ملف المريض أو صفحة إدخال الإجراء.</p>
                    </div>
                    <div class="muted">عدد النتائج الحالية: <?= count($listRows) ?></div>
                </div>
                <div class="filter-row">
                    <?php
                    $baseFilterParams = [];
                    if ($patientId > 0) {
                        $baseFilterParams['patient_id'] = $patientId;
                    }
                    if ($search !== '' && $patientId <= 0) {
                        $baseFilterParams['q'] = $search;
                    }

                    $filterLabels = [
                        'all' => 'الكل',
                        'open' => 'المفتوحة',
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد الإجراء',
                        'done' => 'المكتملة',
                        'cancelled' => 'الملغية',
                    ];
                    foreach ($filterLabels as $filterKey => $filterLabel):
                        $params = $baseFilterParams;
                        $params['status'] = $filterKey;
                    ?>
                        <a class="<?= $statusFilter === $filterKey ? 'active' : '' ?>" href="exam-requests.php?<?= h(http_build_query($params)) ?>"><?= h($filterLabel) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($listRows)): ?>
                    <p class="empty">لا توجد طلبات مطابقة للفلتر الحالي.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>الفحص</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>الطلب</th>
                                    <th>ملاحظات</th>
                                    <th>التحديث</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listRows as $row): ?>
                                    <?php
                                    $statusValue = (string) ($row['status'] ?? 'pending');
                                    $statusLabel = $statusOptions[$statusValue] ?? $statusValue;
                                    $priorityValue = (string) ($row['priority'] ?? 'normal');
                                    $priorityLabel = $priorityOptions[$priorityValue] ?? $priorityValue;
                                    $requestDate = trim((string) ($row['requested_for_date'] ?? ''));
                                    $procedureDate = $requestDate !== '' ? $requestDate : substr((string) ($row['created_at'] ?? ''), 0, 10);
                                    ?>
                                    <tr>
                                        <td>#<?= (int) ($row['id'] ?? 0) ?></td>
                                        <td>
                                            <strong class="clinic-user-content" data-no-translate><?= h($row['patient_name'] ?? $row['latest_patient_name'] ?? '-') ?></strong>
                                            <div class="muted">ID: <?= (int) ($row['patient_id'] ?? 0) ?> | <?= h($row['phone_no'] ?? '-') ?></div>
                                            <div class="actions" style="margin-top:6px;">
                                                <a class="btn-secondary" href="patient-file.php?id=<?= (int) ($row['patient_id'] ?? 0) ?>">ملف المريض</a>
                                                <a class="btn-secondary" href="procedure-entries.php?date=<?= urlencode($procedureDate) ?>&patient_id=<?= (int) ($row['patient_id'] ?? 0) ?>">إدخال الإجراء</a>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= h($row['request_type'] ?? '-') ?></strong>
                                            <div class="muted">العين: <?= h($row['eye'] ?? '-') ?></div>
                                        </td>
                                        <td>
                                            <span class="priority <?= $priorityValue === 'urgent' ? 'urgent' : '' ?>"><?= h($priorityLabel) ?></span>
                                            <div class="muted">التاريخ المطلوب: <?= h($requestDate !== '' ? $requestDate : '-') ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= h(exam_requests_status_class($statusValue)) ?>"><?= h($statusLabel) ?></span>
                                            <div class="muted">تم الطلب بواسطة: <?= h($row['requested_by_name'] ?? '-') ?></div>
                                            <div class="muted"><?= h($row['created_at'] ?? '-') ?></div>
                                        </td>
                                        <td style="min-width:240px;">
                                            <div class="clinic-user-content" data-no-translate style="line-height:1.8;white-space:pre-wrap;"><?= h($row['notes'] ?? '-') ?></div>
                                        </td>
                                        <td style="min-width:240px;">
                                            <div class="clinic-user-content" data-no-translate style="line-height:1.8;white-space:pre-wrap;"><?= h($row['result_notes'] ?? '-') ?></div>
                                            <?php if (!empty($row['handled_by_name'])): ?>
                                                <div class="muted" style="margin-top:6px;">آخر تحديث: <?= h($row['handled_by_name']) ?><?= !empty($row['handled_at']) ? (' | ' . h($row['handled_at'])) : '' ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="min-width:260px;">
                                            <?php if ($canUpdateRequests): ?>
                                                <form method="post" class="row-form">
                                                    <?= clinic_csrf_input() ?>
                                                    <input type="hidden" name="action" value="update_request">
                                                    <input type="hidden" name="request_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                                    <select name="new_status" required>
                                                        <?php foreach ($statusOptions as $stKey => $stLabel): ?>
                                                            <option value="<?= h($stKey) ?>" <?= $statusValue === $stKey ? 'selected' : '' ?>><?= h($stLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <textarea name="result_notes" placeholder="ملاحظات التنفيذ / النتيجة"><?= h($row['result_notes'] ?? '') ?></textarea>
                                                    <button class="btn-primary" type="submit">حفظ الحالة</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">لا تملك صلاحية تعديل الحالة.</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>

    <script>
        (function() {
            const requestTypeSelect = document.getElementById('request_type');
            const customRequestTypeInput = document.getElementById('custom_request_type');
            if (!requestTypeSelect || !customRequestTypeInput) {
                return;
            }

            function syncCustomTypeState() {
                const isOther = requestTypeSelect.value === 'other';
                customRequestTypeInput.required = isOther;
                customRequestTypeInput.placeholder = isOther ? 'اكتب نوع الفحص' : 'اختياري عند اختيار "أخرى"';
                if (!isOther) {
                    customRequestTypeInput.value = '';
                }
            }

            requestTypeSelect.addEventListener('change', syncCustomTypeState);
            syncCustomTypeState();
        })();
    </script>
</body>

</html>