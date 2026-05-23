<?php

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function clinic_table_exists(mysqli $con, string $table): bool
{
    $table = mysqli_real_escape_string($con, $table);
    $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return $result && mysqli_num_rows($result) > 0;
}

function clinic_column_exists(mysqli $con, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function clinic_ensure_column(mysqli $con, string $table, string $column, string $definition): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return;
    }

    if (clinic_table_exists($con, $table) && !clinic_column_exists($con, $table, $column)) {
        mysqli_query($con, "ALTER TABLE `$table` ADD `$column` $definition");
    }
}

function clinic_index_exists(mysqli $con, string $table, string $index): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    $index = mysqli_real_escape_string($con, $index);
    $result = mysqli_query($con, "SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return $result && mysqli_num_rows($result) > 0;
}

function clinic_ensure_index(mysqli $con, string $table, string $index, string $columns): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $index)) {
        return;
    }

    if (clinic_table_exists($con, $table) && !clinic_index_exists($con, $table, $index)) {
        mysqli_query($con, "CREATE INDEX `$index` ON `$table` ($columns)");
    }
}

function clinic_ensure_infrastructure(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(120) NULL,
            action VARCHAR(80) NOT NULL,
            table_name VARCHAR(80) NOT NULL,
            record_id INT NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS treatment_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(180) NOT NULL,
            diagnosis TEXT NULL,
            medicines_text TEXT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'add_patient', 'is_deleted', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'add_patient', 'deleted_at', 'DATETIME NULL');
    clinic_ensure_column($con, 'add_patient', 'deleted_by', 'VARCHAR(120) NULL');

    clinic_ensure_index($con, 'add_patient', 'idx_add_patient_deleted', '`is_deleted`');
    clinic_ensure_index($con, 'add_patient', 'idx_add_patient_phone', '`phone_no`');
    clinic_ensure_index($con, 'visits', 'idx_visits_patient_date', '`patient_id`, `visit_date`');
    clinic_ensure_index($con, 'followups', 'idx_followups_status_date', '`status`, `followup_date`');
}

function clinic_current_user(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    return $_SESSION['username'] ?? $_SESSION['user'] ?? $_SESSION['full_name'] ?? 'system';
}

function clinic_audit(mysqli $con, string $action, string $table, ?int $record_id = null, $old_value = null, $new_value = null): void
{
    if (!clinic_table_exists($con, 'audit_log')) {
        return;
    }

    $user = clinic_current_user();
    $old = is_string($old_value) || $old_value === null ? $old_value : json_encode($old_value, JSON_UNESCAPED_UNICODE);
    $new = is_string($new_value) || $new_value === null ? $new_value : json_encode($new_value, JSON_UNESCAPED_UNICODE);

    $stmt = mysqli_prepare($con, "
        INSERT INTO audit_log (user_name, action, table_name, record_id, old_value, new_value)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "sssiss", $user, $action, $table, $record_id, $old, $new);
    @mysqli_stmt_execute($stmt);
}

function clinic_active_patient_where(mysqli $con, string $alias = 'add_patient'): string
{
    if (clinic_column_exists($con, 'add_patient', 'is_deleted')) {
        return "$alias.is_deleted = 0";
    }

    return "1=1";
}
