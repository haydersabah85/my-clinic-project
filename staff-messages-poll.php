<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

header('Content-Type: application/json; charset=UTF-8');

clinic_ensure_infrastructure($con);

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole = strtolower((string) ($_SESSION['role'] ?? ''));

if ($currentUserId <= 0 || $currentRole !== 'secretary') {
    echo json_encode([
        'success' => false,
        'message' => 'not_allowed',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$countStmt = mysqli_prepare($con, "SELECT COUNT(*) AS total FROM staff_messages WHERE recipient_user_id = ? AND is_read = 0");
$unreadCount = 0;
if ($countStmt) {
    mysqli_stmt_bind_param($countStmt, 'i', $currentUserId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $unreadCount = (int) (($countResult ? mysqli_fetch_assoc($countResult)['total'] : 0) ?? 0);
    mysqli_stmt_close($countStmt);
}

$latest = null;
$latestStmt = mysqli_prepare($con, "
    SELECT
        sm.id,
        sm.message_text,
        sm.created_at,
        sender.full_name AS sender_name,
        sender.username AS sender_username
    FROM staff_messages sm
    INNER JOIN users sender ON sender.id = sm.sender_user_id
    WHERE sm.recipient_user_id = ? AND sm.is_read = 0
    ORDER BY sm.created_at DESC, sm.id DESC
    LIMIT 1
");
if ($latestStmt) {
    mysqli_stmt_bind_param($latestStmt, 'i', $currentUserId);
    mysqli_stmt_execute($latestStmt);
    $latestResult = mysqli_stmt_get_result($latestStmt);
    $row = $latestResult ? mysqli_fetch_assoc($latestResult) : null;
    if ($row) {
        $latest = [
            'id' => (int) $row['id'],
            'message_text' => (string) ($row['message_text'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'sender_name' => (string) ($row['sender_name'] ?: $row['sender_username']),
        ];
    }
    mysqli_stmt_close($latestStmt);
}

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'latest' => $latest,
], JSON_UNESCAPED_UNICODE);
