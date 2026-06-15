<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);
clinic_ensure_sync_conflicts($con);

header('Content-Type: application/json; charset=utf-8');

$todayVisits = (int) (mysqli_fetch_assoc(mysqli_query(
    $con,
    "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE()"
))['total'] ?? 0);
$todayPending = (int) (mysqli_fetch_assoc(mysqli_query(
    $con,
    "SELECT COUNT(*) total FROM visits WHERE visit_date = CURDATE() AND is_done = 0"
))['total'] ?? 0);
$openConflicts = (int) (mysqli_fetch_assoc(mysqli_query(
    $con,
    "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status = 'open'"
))['total'] ?? 0);

$pendingImages = 0;
if (clinic_table_exists($con, 'patient_images') && clinic_column_exists($con, 'patient_images', 'sync_status')) {
    $pendingImages = (int) (mysqli_fetch_assoc(mysqli_query(
        $con,
        "SELECT COUNT(*) total FROM patient_images WHERE sync_status = 0"
    ))['total'] ?? 0);
}

$backupDir = 'C:/clinic_backups';
$latestBackupAt = null;
if (is_dir($backupDir)) {
    $files = glob($backupDir . '/*.sql') ?: [];
    if ($files) {
        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $mtime = filemtime($files[0]);
        $latestBackupAt = $mtime ? date('Y-m-d H:i', $mtime) : null;
    }
}

echo json_encode([
    'today_visits' => $todayVisits,
    'today_pending' => $todayPending,
    'open_conflicts' => $openConflicts,
    'pending_images' => $pendingImages,
    'latest_backup_at' => $latestBackupAt,
], JSON_UNESCAPED_UNICODE);
