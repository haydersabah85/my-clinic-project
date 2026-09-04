<?php

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function clinic_normalize_digits(string $value): string
{
    return strtr($value, [
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
    ]);
}

function clinic_sanitize_phone(?string $value): string
{
    $normalized = clinic_normalize_digits((string) $value);
    return preg_replace('/\D+/', '', $normalized) ?? '';
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

    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS staff_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_user_id INT NOT NULL,
            recipient_user_id INT NOT NULL,
            message_text TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME NULL,
            INDEX idx_staff_messages_recipient (recipient_user_id, is_read, created_at),
            INDEX idx_staff_messages_sender (sender_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS referred_surgery_cases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_uuid CHAR(32) NOT NULL,
            patient_full_name VARCHAR(180) NOT NULL,
            patient_age VARCHAR(20) NULL,
            patient_phone VARCHAR(40) NULL,
            patient_city VARCHAR(120) NULL,
            referring_doctor_name VARCHAR(180) NOT NULL,
            referring_doctor_clinic VARCHAR(180) NULL,
            referring_doctor_phone VARCHAR(40) NULL,
            referral_date DATE NULL,
            surgery_date DATE NOT NULL,
            surgery_type VARCHAR(180) NOT NULL,
            eye VARCHAR(10) NULL,
            surgeon_name VARCHAR(180) NULL,
            anesthesia_type VARCHAR(120) NULL,
            materials_used TEXT NULL,
            operation_notes TEXT NULL,
            postop_instructions TEXT NULL,
            followup_plan TEXT NULL,
            followup_destination ENUM('clinic','referrer','unknown') NOT NULL DEFAULT 'unknown',
            created_by VARCHAR(120) NULL,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_referred_case_uuid (case_uuid),
            INDEX idx_referred_surgery_date (surgery_date),
            INDEX idx_referred_referral_date (referral_date),
            INDEX idx_referred_doctor (referring_doctor_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'add_patient', 'is_deleted', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'add_patient', 'deleted_at', 'DATETIME NULL');
    clinic_ensure_column($con, 'add_patient', 'deleted_by', 'VARCHAR(120) NULL');
    if (clinic_table_exists($con, 'surgery_appointment')) {
        clinic_ensure_column($con, 'surgery_appointment', 'readiness_json', 'TEXT NULL');
    }

    clinic_ensure_index($con, 'add_patient', 'idx_add_patient_deleted', '`is_deleted`');
    clinic_ensure_index($con, 'add_patient', 'idx_add_patient_phone', '`phone_no`');
    clinic_ensure_index($con, 'visits', 'idx_visits_patient_date', '`patient_id`, `visit_date`');
    clinic_ensure_index($con, 'followups', 'idx_followups_status_date', '`status`, `followup_date`');
    clinic_ensure_column($con, 'treatment_templates', 'payload_json', 'LONGTEXT NULL');
    clinic_ensure_column($con, 'treatment_templates', 'followup_after_days', 'INT NULL');
    clinic_ensure_column($con, 'treatment_templates', 'followup_reason', 'VARCHAR(255) NULL');
    clinic_ensure_column($con, 'treatment_templates', 'followup_note', 'TEXT NULL');

    clinic_ensure_column($con, 'prescriptions', 'followup_id', 'INT NULL');
    clinic_ensure_column($con, 'prescriptions', 'next_followup_date', 'DATE NULL');
    clinic_ensure_column($con, 'prescriptions', 'next_followup_reason', 'VARCHAR(255) NULL');
    clinic_ensure_column($con, 'prescriptions', 'next_followup_note', 'TEXT NULL');
    clinic_ensure_followup_type_support($con);
    clinic_ensure_column($con, 'followups', 'source_type', 'VARCHAR(50) NULL');
    clinic_ensure_column($con, 'followups', 'source_id', 'INT NULL');
    clinic_ensure_index($con, 'followups', 'idx_followups_source', '`source_type`, `source_id`');

    clinic_ensure_surgery_iol_power_column($con);
    clinic_ensure_treatment_type_tables($con);
    clinic_ensure_appointment_tables($con);
    clinic_ensure_exam_requests_table($con);
}

function clinic_ensure_exam_requests_table(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS exam_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            patient_name VARCHAR(180) NOT NULL,
            request_type VARCHAR(180) NOT NULL,
            eye VARCHAR(10) NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            requested_for_date DATE NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            result_notes TEXT NULL,
            requested_by_user_id INT NOT NULL,
            requested_by_name VARCHAR(120) NOT NULL,
            handled_by_user_id INT NULL,
            handled_by_name VARCHAR(120) NULL,
            handled_at DATETIME NULL,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_exam_requests_patient (patient_id),
            INDEX idx_exam_requests_status (status),
            INDEX idx_exam_requests_created_at (created_at),
            INDEX idx_exam_requests_requested_for_date (requested_for_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'exam_requests', 'patient_id', 'INT NOT NULL');
    clinic_ensure_column($con, 'exam_requests', 'patient_name', 'VARCHAR(180) NOT NULL DEFAULT ""');
    clinic_ensure_column($con, 'exam_requests', 'request_type', 'VARCHAR(180) NOT NULL DEFAULT ""');
    clinic_ensure_column($con, 'exam_requests', 'eye', 'VARCHAR(10) NULL');
    clinic_ensure_column($con, 'exam_requests', 'priority', 'VARCHAR(20) NOT NULL DEFAULT "normal"');
    clinic_ensure_column($con, 'exam_requests', 'requested_for_date', 'DATE NULL');
    clinic_ensure_column($con, 'exam_requests', 'notes', 'TEXT NULL');
    clinic_ensure_column($con, 'exam_requests', 'status', 'VARCHAR(20) NOT NULL DEFAULT "pending"');
    clinic_ensure_column($con, 'exam_requests', 'result_notes', 'TEXT NULL');
    clinic_ensure_column($con, 'exam_requests', 'requested_by_user_id', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'exam_requests', 'requested_by_name', 'VARCHAR(120) NOT NULL DEFAULT ""');
    clinic_ensure_column($con, 'exam_requests', 'handled_by_user_id', 'INT NULL');
    clinic_ensure_column($con, 'exam_requests', 'handled_by_name', 'VARCHAR(120) NULL');
    clinic_ensure_column($con, 'exam_requests', 'handled_at', 'DATETIME NULL');
    clinic_ensure_column($con, 'exam_requests', 'sync_status', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'exam_requests', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    clinic_ensure_column($con, 'exam_requests', 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

    clinic_ensure_index($con, 'exam_requests', 'idx_exam_requests_patient', '`patient_id`');
    clinic_ensure_index($con, 'exam_requests', 'idx_exam_requests_status', '`status`');
    clinic_ensure_index($con, 'exam_requests', 'idx_exam_requests_created_at', '`created_at`');
    clinic_ensure_index($con, 'exam_requests', 'idx_exam_requests_requested_for_date', '`requested_for_date`');
}

function clinic_ensure_appointment_table(mysqli $con, string $table, string $typeColumn, bool $withReadiness = false): void
{
    if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $typeColumn)) {
        return;
    }

    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `$table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            eye VARCHAR(10) NOT NULL,
            `$typeColumn` VARCHAR(180) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            phone_alt VARCHAR(40) NULL,
            date DATE NOT NULL,
            notes TEXT NULL,
            serial_no INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attendance_status TINYINT(1) NOT NULL DEFAULT 0,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_{$table}_patient (patient_id),
            INDEX idx_{$table}_date (date),
            INDEX idx_{$table}_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, $table, 'patient_id', 'INT NOT NULL');
    clinic_ensure_column($con, $table, 'eye', 'VARCHAR(10) NOT NULL DEFAULT "OD"');
    clinic_ensure_column($con, $table, $typeColumn, 'VARCHAR(180) NOT NULL DEFAULT ""');
    clinic_ensure_column($con, $table, 'phone', 'VARCHAR(40) NOT NULL DEFAULT ""');
    clinic_ensure_column($con, $table, 'phone_alt', 'VARCHAR(40) NULL');
    clinic_ensure_column($con, $table, 'date', 'DATE NOT NULL');
    clinic_ensure_column($con, $table, 'notes', 'TEXT NULL');
    clinic_ensure_column($con, $table, 'serial_no', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, $table, 'status', 'VARCHAR(20) NOT NULL DEFAULT "pending"');
    clinic_ensure_column($con, $table, 'attendance_status', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, $table, 'sync_status', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, $table, 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    clinic_ensure_column($con, $table, 'updated_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

    clinic_ensure_index($con, $table, "idx_{$table}_patient", '`patient_id`');
    clinic_ensure_index($con, $table, "idx_{$table}_date", '`date`');
    clinic_ensure_index($con, $table, "idx_{$table}_status", '`status`');

    if ($withReadiness) {
        clinic_ensure_column($con, $table, 'readiness_json', 'TEXT NULL');
    }
}

function clinic_ensure_appointment_tables(mysqli $con): void
{
    clinic_ensure_appointment_table($con, 'surgery_appointment', 'surgery_type', true);
    clinic_ensure_appointment_table($con, 'laser_appointment', 'laser_type');
    clinic_ensure_appointment_table($con, 'injection_appointment', 'injection_type');
}

function clinic_ensure_surgery_iol_power_column(mysqli $con): void
{
    if (!clinic_table_exists($con, 'surgery')) {
        return;
    }

    clinic_ensure_column($con, 'surgery', 'iol_power', 'DECIMAL(4,1) NULL');
}

function clinic_ensure_type_table(mysqli $con, string $table): void
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return;
    }

    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS `$table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_name VARCHAR(180) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 100,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_type_name (type_name),
            INDEX idx_active_sort (is_active, sort_order, type_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function clinic_seed_type_table(mysqli $con, string $table, array $defaults): void
{
    if (!preg_match('/^[a-z_]+$/', $table) || empty($defaults)) {
        return;
    }

    $stmt = mysqli_prepare($con, "INSERT IGNORE INTO `$table` (type_name, sort_order, sync_status) VALUES (?, ?, 0)");
    if (!$stmt) {
        return;
    }

    $order = 10;
    foreach ($defaults as $name) {
        $typeName = trim((string) $name);
        if ($typeName === '') {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'si', $typeName, $order);
        mysqli_stmt_execute($stmt);
        $order += 10;
    }

    mysqli_stmt_close($stmt);
}

function clinic_seed_types_from_source(mysqli $con, string $sourceTable, string $sourceColumn, string $targetTable): void
{
    if (!preg_match('/^[a-z_]+$/', $sourceTable) || !preg_match('/^[a-z_]+$/', $targetTable) || !preg_match('/^[a-z_]+$/', $sourceColumn)) {
        return;
    }

    if (!clinic_table_exists($con, $sourceTable) || !clinic_column_exists($con, $sourceTable, $sourceColumn)) {
        return;
    }

    $result = mysqli_query($con, "SELECT DISTINCT `$sourceColumn` AS v FROM `$sourceTable` WHERE `$sourceColumn` IS NOT NULL AND TRIM(`$sourceColumn`) <> ''");
    if (!$result) {
        return;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $value = trim((string) ($row['v'] ?? ''));
        if ($value === '') {
            continue;
        }
        $stmt = mysqli_prepare($con, "INSERT IGNORE INTO `$targetTable` (type_name, sync_status) VALUES (?, 0)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $value);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

function clinic_ensure_treatment_type_tables(mysqli $con): void
{
    clinic_ensure_type_table($con, 'surgery_types');
    clinic_ensure_type_table($con, 'laser_types');
    clinic_ensure_type_table($con, 'injection_types');
    clinic_ensure_type_table($con, 'iol_types');

    clinic_seed_type_table($con, 'surgery_types', [
        'Phaco',
        'Vitrectomy',
        'Phaco and Vitrectomy',
        'SOR',
        'Phaco and SOR',
        'Squint',
        'ECCE',
        'ICCE',
        'Chalazion',
        'EUA',
        'Probing',
        'SMILE',
        'PRK',
        'AC Washout',
        'Secondary IOL',
        'IOL Exchange',
        'Pterygium with Graft',
        'Pterygium',
        'Anterior Vitrectomy'
    ]);
    clinic_seed_type_table($con, 'laser_types', ['PRP', 'Retinopexy', 'Focal Laser', 'YAG', 'PI']);
    clinic_seed_type_table($con, 'injection_types', ['Avastin', 'Eylea 2mg', 'Vabysmo', 'Eylea 8mg', 'Triamcinolone', 'Lucentis', 'Ozurdix']);
    clinic_seed_type_table($con, 'iol_types', ['Sensar', 'Eyhance', 'Alcon', 'Clareon', 'Synergy', 'Rayner Monofocal', 'Rayner Trifocal', 'Eleon', 'Artisan']);

    clinic_seed_types_from_source($con, 'surgery', 'surgery_type', 'surgery_types');
    clinic_seed_types_from_source($con, 'surgery_appointment', 'surgery_type', 'surgery_types');

    clinic_seed_types_from_source($con, 'laser', 'laser_type', 'laser_types');
    clinic_seed_types_from_source($con, 'laser_appointment', 'laser_type', 'laser_types');

    clinic_seed_types_from_source($con, 'injection', 'injection_type', 'injection_types');
    clinic_seed_types_from_source($con, 'injection_appointment', 'injection_type', 'injection_types');

    clinic_seed_types_from_source($con, 'surgery', 'iol_type', 'iol_types');
}

function clinic_fetch_type_names(mysqli $con, string $table, bool $includeInactive = false): array
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return [];
    }

    clinic_ensure_treatment_type_tables($con);

    $where = $includeInactive ? '' : 'WHERE is_active = 1';
    $result = mysqli_query($con, "SELECT type_name FROM `$table` $where ORDER BY sort_order ASC, type_name ASC");
    if (!$result) {
        return [];
    }

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $value = trim((string) ($row['type_name'] ?? ''));
        if ($value !== '') {
            $items[] = $value;
        }
    }

    return $items;
}

function clinic_get_surgery_types(mysqli $con, bool $includeInactive = false): array
{
    return clinic_fetch_type_names($con, 'surgery_types', $includeInactive);
}

function clinic_get_laser_types(mysqli $con, bool $includeInactive = false): array
{
    return clinic_fetch_type_names($con, 'laser_types', $includeInactive);
}

function clinic_get_injection_types(mysqli $con, bool $includeInactive = false): array
{
    return clinic_fetch_type_names($con, 'injection_types', $includeInactive);
}

function clinic_get_iol_types(mysqli $con, bool $includeInactive = false): array
{
    return clinic_fetch_type_names($con, 'iol_types', $includeInactive);
}

function clinic_format_iol_power($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    if (!is_numeric($value)) {
        return '-';
    }

    $power = (float) $value;
    $formatted = number_format($power, 1, '.', '');
    if ($power > 0) {
        $formatted = '+' . $formatted;
    }

    return $formatted . ' D';
}

function clinic_ensure_daily_revenue(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS daily_revenue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            revenue_date DATE NOT NULL,
            visit_first_count INT NOT NULL DEFAULT 0,
            visit_repeat_count INT NOT NULL DEFAULT 0,
            paid_visits_count INT NOT NULL DEFAULT 0,
            visit_first_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            visit_repeat_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            visit_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            retina_count INT NOT NULL DEFAULT 0,
            retina_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            laser_count INT NOT NULL DEFAULT 0,
            laser_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            procedures_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            other_income DECIMAL(12,2) NOT NULL DEFAULT 0,
            service_staff_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_by VARCHAR(120) NULL,
            updated_by VARCHAR(120) NULL,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_daily_revenue_date (revenue_date),
            INDEX idx_daily_revenue_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'daily_revenue', 'visit_first_count', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'visit_repeat_count', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'paid_visits_count', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'visit_first_price', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'visit_repeat_price', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'retina_count', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'laser_count', 'INT NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'daily_revenue', 'procedures_income', 'DECIMAL(12,2) NOT NULL DEFAULT 0');
}

function clinic_ensure_procedure_types(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS procedure_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_name VARCHAR(180) NOT NULL,
            category ENUM('retina','laser','other') NOT NULL DEFAULT 'other',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_procedure_type_name (type_name),
            INDEX idx_procedure_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    mysqli_query($con, "INSERT IGNORE INTO procedure_types (type_name, category, is_active, sync_status) VALUES ('فحص الشبكية', 'retina', 1, 0)");
    mysqli_query($con, "INSERT IGNORE INTO procedure_types (type_name, category, is_active, sync_status) VALUES ('ليزر الشبكية', 'laser', 1, 0)");
}

function clinic_ensure_procedure_entries(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS procedure_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            procedure_date DATE NOT NULL,
            patient_id INT NULL,
            patient_name VARCHAR(220) NOT NULL,
            procedure_type_id INT NOT NULL,
            procedure_type_name VARCHAR(180) NOT NULL,
            category ENUM('retina','laser','other') NOT NULL DEFAULT 'other',
            qty INT NOT NULL DEFAULT 1,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            entered_by VARCHAR(120) NULL,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_procedure_date (procedure_date),
            INDEX idx_procedure_patient (patient_id),
            INDEX idx_procedure_category (category),
            INDEX idx_procedure_type (procedure_type_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'procedure_entries', 'patient_id', 'INT NULL');
    clinic_ensure_index($con, 'procedure_entries', 'idx_procedure_patient', '`patient_id`');
}

function clinic_ensure_runtime_controls(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function clinic_ensure_followup_type_support(mysqli $con): void
{
    if (!clinic_table_exists($con, 'followups')) {
        return;
    }

    $res = mysqli_query($con, "SHOW COLUMNS FROM followups LIKE 'followup_type'");
    if (!$res) {
        return;
    }

    $column = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    if ($column) {
        return;
    }

    mysqli_query($con, "
        ALTER TABLE followups
        ADD COLUMN followup_type ENUM('review','next_visit') NOT NULL DEFAULT 'review' AFTER followup_reason
    ");
}

function clinic_ensure_visit_type_support(mysqli $con): void
{
    if (!clinic_table_exists($con, 'visits')) {
        return;
    }

    $res = mysqli_query($con, "SHOW COLUMNS FROM visits LIKE 'visit_type'");
    if (!$res) {
        return;
    }

    $column = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    if (!$column) {
        return;
    }

    $type = strtolower((string) ($column['Type'] ?? ''));
    if (strpos($type, 'enum(') === false) {
        return;
    }

    if (strpos($type, "'charity'") !== false) {
        return;
    }

    $isNotNull = strtoupper((string) ($column['Null'] ?? 'YES')) === 'NO';
    $defaultRaw = $column['Default'] ?? null;
    $allowedDefaults = ['first', 'repeat', 'free', 'charity'];
    $defaultValue = in_array((string) $defaultRaw, $allowedDefaults, true) ? (string) $defaultRaw : 'repeat';

    $nullSql = $isNotNull ? 'NOT NULL' : 'NULL';
    $defaultSql = " DEFAULT '" . mysqli_real_escape_string($con, $defaultValue) . "'";

    mysqli_query($con, "
        ALTER TABLE visits
        MODIFY COLUMN visit_type ENUM('first','repeat','free','charity') $nullSql$defaultSql
    ");
}

function clinic_ensure_sync_conflicts(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS sync_conflicts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(120) NOT NULL,
            record_key VARCHAR(120) NOT NULL,
            direction VARCHAR(40) NOT NULL,
            resolution_status VARCHAR(30) NOT NULL DEFAULT 'open',
            local_updated_at DATETIME NULL,
            online_updated_at DATETIME NULL,
            local_snapshot LONGTEXT NULL,
            online_snapshot LONGTEXT NULL,
            note TEXT NULL,
            resolved_by VARCHAR(120) NULL,
            resolved_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sync_conflicts_status (resolution_status),
            INDEX idx_sync_conflicts_table_key (table_name, record_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    clinic_ensure_column($con, 'sync_conflicts', 'resolved_by', 'VARCHAR(120) NULL');
    clinic_ensure_column($con, 'sync_conflicts', 'resolved_at', 'DATETIME NULL');
}

function clinic_log_sync_conflict(
    mysqli $con,
    string $table,
    string $recordKey,
    string $direction,
    ?string $localUpdatedAt,
    ?string $onlineUpdatedAt,
    $localSnapshot,
    $onlineSnapshot,
    ?string $note = null
): void {
    clinic_ensure_sync_conflicts($con);

    $localJson = is_string($localSnapshot) || $localSnapshot === null
        ? $localSnapshot
        : json_encode($localSnapshot, JSON_UNESCAPED_UNICODE);
    $onlineJson = is_string($onlineSnapshot) || $onlineSnapshot === null
        ? $onlineSnapshot
        : json_encode($onlineSnapshot, JSON_UNESCAPED_UNICODE);

    $stmt = mysqli_prepare($con, "
        INSERT INTO sync_conflicts
        (table_name, record_key, direction, local_updated_at, online_updated_at, local_snapshot, online_snapshot, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssssss',
        $table,
        $recordKey,
        $direction,
        $localUpdatedAt,
        $onlineUpdatedAt,
        $localJson,
        $onlineJson,
        $note
    );

    @mysqli_stmt_execute($stmt);
}

function clinic_get_app_setting(mysqli $con, string $key, ?string $default = null): ?string
{
    clinic_ensure_runtime_controls($con);

    $stmt = mysqli_prepare($con, "SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) {
        return $default;
    }

    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    return $row['setting_value'] ?? $default;
}

function clinic_set_app_setting(mysqli $con, string $key, string $value): bool
{
    clinic_ensure_runtime_controls($con);

    $stmt = mysqli_prepare($con, "
        INSERT INTO app_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
    ");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $key, $value);
    return (bool) mysqli_stmt_execute($stmt);
}

function clinic_ensure_deleted_records(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS deleted_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(80) NOT NULL,
            record_id INT NOT NULL,
            deleted_by VARCHAR(120) NULL,
            deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_dr_table_record (table_name, record_id),
            INDEX idx_dr_deleted_at (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function clinic_log_deleted_record(mysqli $con, string $table, int $recordId): void
{
    clinic_ensure_deleted_records($con);

    $user = clinic_current_user();
    $stmt = mysqli_prepare($con, "
        INSERT IGNORE INTO deleted_records (table_name, record_id, deleted_by, deleted_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'sis', $table, $recordId, $user);
    mysqli_stmt_execute($stmt);
}

function clinic_apply_online_deletions(mysqli $localDb, mysqli $onlineDb): int
{
    clinic_ensure_deleted_records($localDb);

    if (!clinic_table_exists($onlineDb, 'deleted_records')) {
        return 0;
    }

    $allowedTables = [
        'patient_visits'        => 'id',
        'visits'                => 'visit_id',
        'patient_images'        => 'id',
        'followups'             => 'id',
        'surgery'               => 'id',
        'surgery_appointment'   => 'id',
        'laser_appointment'     => 'id',
        'injection_appointment' => 'id',
        'medicines'             => 'id',
        'va'                    => 'va_id',
    ];

    $lastChecked = clinic_get_app_setting($localDb, 'deleted_records_last_checked_at', '1970-01-01 00:00:00');
    if (!$lastChecked) {
        $lastChecked = '1970-01-01 00:00:00';
    }

    $stmt = mysqli_prepare($onlineDb, "
        SELECT id, table_name, record_id, deleted_at
        FROM deleted_records
        WHERE deleted_at > ?
        ORDER BY deleted_at ASC, id ASC
        LIMIT 2000
    ");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $lastChecked);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        return 0;
    }

    $applied = 0;
    $maxDeletedAt = $lastChecked;

    while ($row = mysqli_fetch_assoc($result)) {
        $tableName = (string) ($row['table_name'] ?? '');
        $recordId  = (int)    ($row['record_id']  ?? 0);
        $deletedAt = (string) ($row['deleted_at'] ?? '');

        if ($deletedAt > $maxDeletedAt) {
            $maxDeletedAt = $deletedAt;
        }

        if (!isset($allowedTables[$tableName]) || $recordId <= 0) {
            continue;
        }

        if (!clinic_table_exists($localDb, $tableName)) {
            continue;
        }

        $pkCol = $allowedTables[$tableName];
        mysqli_query($localDb, "DELETE FROM `$tableName` WHERE `$pkCol` = $recordId");
        $applied++;
    }

    if ($maxDeletedAt > $lastChecked) {
        clinic_set_app_setting($localDb, 'deleted_records_last_checked_at', $maxDeletedAt);
    }

    return $applied;
}

function clinic_auto_pull_interval_minutes(mysqli $con): int
{
    $value = (int) clinic_get_app_setting($con, 'auto_pull_interval_minutes', '10');
    $allowed = [5, 10];

    return in_array($value, $allowed, true) ? $value : 10;
}

function clinic_auto_pull_is_enabled(mysqli $con): bool
{
    return clinic_get_app_setting($con, 'auto_pull_enabled', '1') === '1';
}

function clinic_auto_pull_tick(mysqli $con, bool $isLocal): void
{
    if (!$isLocal) {
        return;
    }

    clinic_ensure_runtime_controls($con);

    if (!clinic_auto_pull_is_enabled($con)) {
        return;
    }

    $intervalMinutes = clinic_auto_pull_interval_minutes($con);
    $intervalSeconds = $intervalMinutes * 60;
    $now = time();

    $lastSuccess = clinic_get_app_setting($con, 'auto_pull_last_success_at', '');
    $lastTs = $lastSuccess ? strtotime($lastSuccess) : false;
    if ($lastTs !== false && ($now - $lastTs) < $intervalSeconds) {
        return;
    }

    $lockUntilRaw = clinic_get_app_setting($con, 'auto_pull_lock_until_ts', '0');
    $lockUntil = (int) $lockUntilRaw;
    if ($lockUntil > $now) {
        return;
    }

    clinic_set_app_setting($con, 'auto_pull_lock_until_ts', (string) ($now + 240));
    clinic_set_app_setting($con, 'auto_pull_last_attempt_at', date('Y-m-d H:i:s', $now));

    try {
        try {
            include_once __DIR__ . '/sync_from_online_worker.php';
            $result = clinic_sync_pull_from_online_worker($con, false, 5000);
        } catch (Throwable $e) {
            $result = [
                'ok' => false,
                'error' => 'Auto pull exception: ' . $e->getMessage(),
                'total_pulled' => 0,
                'total_applied' => 0,
            ];
        }

        $totalPulled = (int) ($result['total_pulled'] ?? 0);
        $totalApplied = (int) ($result['total_applied'] ?? 0);

        if (!empty($result['ok'])) {
            clinic_set_app_setting($con, 'auto_pull_last_success_at', date('Y-m-d H:i:s'));
            clinic_set_app_setting($con, 'auto_pull_last_status', 'ok');
            clinic_set_app_setting($con, 'auto_pull_last_summary', "pulled={$totalPulled},applied={$totalApplied}");

            clinic_audit(
                $con,
                'auto_sync_pull_from_online',
                'sync',
                null,
                null,
                [
                    'total_pulled' => $totalPulled,
                    'total_applied' => $totalApplied,
                ]
            );
        } else {
            $error = (string) ($result['error'] ?? 'unknown_error');
            clinic_set_app_setting($con, 'auto_pull_last_status', 'error');
            clinic_set_app_setting($con, 'auto_pull_last_summary', $error);
        }
    } finally {
        clinic_set_app_setting($con, 'auto_pull_lock_until_ts', '0');
    }
}

function clinic_is_write_endpoint(string $scriptName, string $requestMethod): bool
{
    if (strtoupper($requestMethod) !== 'GET') {
        return true;
    }

    $writeGetPrefixes = [
        'delete-',
        'mark-',
        'mark_',
        'process_decision_',
        'process_decision-',
        'discharge_',
        'discharge-',
        'save_',
        'update-',
        'import_',
    ];

    foreach ($writeGetPrefixes as $prefix) {
        if (strpos($scriptName, $prefix) === 0) {
            return true;
        }
    }

    $writeGetFiles = [
        'confirm-attendance.php',
        'cancel-attendance.php',
        'backup_and_upload.php',
        'restore.php',
        'sync_to_online1.php',
        'sync_to_online2.php',
    ];

    return in_array($scriptName, $writeGetFiles, true);
}

function clinic_is_online_write_locked(mysqli $con, bool $isLocal): bool
{
    if ($isLocal) {
        return false;
    }

    return clinic_get_app_setting($con, 'online_write_lock', '0') === '1';
}

function clinic_enforce_runtime_write_policy(mysqli $con, bool $isLocal): void
{
    if (!clinic_is_online_write_locked($con, $isLocal)) {
        return;
    }

    $scriptName = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Keep settings writable for admins so emergency lock can always be toggled off.
    if ($scriptName === 'settings.php') {
        return;
    }

    if (!clinic_is_write_endpoint($scriptName, $requestMethod)) {
        return;
    }

    http_response_code(423);
    echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
    echo "<h3>وضع الحماية مفعل</h3>";
    echo "<p>النسخة السحابية في وضع قراءة فقط مؤقتا لتجنب تضارب البيانات أثناء الطوارئ.</p>";
    echo "<p>يمكنك إعادة تفعيل الكتابة من صفحة الإعدادات بواسطة حساب المدير الرئيسي فقط.</p>";
    echo "<a href='dashboard.php'>العودة إلى الرئيسية</a>";
    echo "</body></html>";
    exit;
}

function clinic_write_lock_owner_user_id(mysqli $con): int
{
    $ownerId = (int) clinic_get_app_setting($con, 'online_write_lock_owner_user_id', '1');
    return $ownerId > 0 ? $ownerId : 1;
}

function clinic_can_manage_online_write_lock(mysqli $con): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $currentRole = $_SESSION['role'] ?? '';

    if ($currentUserId <= 0 || $currentRole !== 'admin') {
        return false;
    }

    return $currentUserId === clinic_write_lock_owner_user_id($con);
}

function clinic_normalize_permissions($permissions): array
{
    if (!is_array($permissions)) {
        return [];
    }

    $permissions = array_map(static function ($permission): string {
        return trim((string) $permission);
    }, $permissions);

    $permissions = array_filter($permissions, static function (string $permission): bool {
        return $permission !== '';
    });

    return array_values(array_unique($permissions));
}

function clinic_current_user_permissions(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    return clinic_normalize_permissions($_SESSION['permissions'] ?? []);
}

function clinic_user_has_permission($requiredPermissions): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (($_SESSION['role'] ?? '') === 'admin') {
        return true;
    }

    $requiredPermissions = clinic_normalize_permissions(is_array($requiredPermissions) ? $requiredPermissions : [$requiredPermissions]);
    if (empty($requiredPermissions)) {
        return false;
    }

    return count(array_intersect($requiredPermissions, clinic_current_user_permissions())) > 0;
}

function clinic_require_permissions($requiredPermissions, string $message = 'غير مصرح لك بالدخول'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
        header('Location: log-in.php');
        exit;
    }

    if (!clinic_user_has_permission($requiredPermissions)) {
        exit($message);
    }
}

function clinic_required_permissions_for_script(string $scriptName): array
{
    $scriptName = strtolower(trim($scriptName));

    $exactMap = [
        'daily-revenue.php' => ['admin'],
        'procedure-entries.php' => ['appointments'],
        'procedure-patient-search.php' => ['appointments'],
        'registration.php' => ['users'],
        'settings.php' => ['settings'],
        'backup_and_upload.php' => ['backup'],
        'restore.php' => ['backup'],
        'sync_conflicts.php' => ['sync'],
        'sync_from_online.php' => ['sync'],
        'sync_to_online_safe.php' => ['sync'],
        'sync_to_online2.php' => ['sync'],
        'audit-log.php' => ['reports'],
        'dashboard.php' => ['reports'],
        'dashboard-status.php' => ['reports'],
        'data-quality.php' => ['reports'],
        'confirmed-list.php' => ['reports'],
        'work-queue.php' => ['reports'],
        'operation-by-date.php' => ['reports'],
        'load_operations.php' => ['reports'],
        'main.php' => ['reports'],
        'patient-data.php' => ['patients'],
        'patient-file.php' => ['patients'],
        'patient-file2.php' => ['patients'],
        'patient-visits.php' => ['patients'],
        'patient_timeline.php' => ['patients'],
        'archived-patients.php' => ['patients'],
        'check-patient-duplicates.php' => ['patients'],
        'search-patient.php' => ['patients'],
        'update-patient.php' => ['patients'],
        'edit-patient.php' => ['patients'],
        'delete-patient.php' => ['patients'],
        'add-patient.php' => ['patients'],
        'add-patient2.php' => ['patients'],
        'retina-chart.php' => ['patients'],
        'image-comparison.php' => ['patients'],
        'show-image.php' => ['patients'],
        'save-retina-drawing.php' => ['patients'],
        'add-image.php' => ['patients'],
        'add-image2.php' => ['patients'],
        'delete-image.php' => ['patients'],
        'delete-va.php' => ['patients'],
        'common-medicines.php' => ['prescriptions'],
        'common-medicines2.php' => ['prescriptions'],
        'treatment.php' => ['prescriptions'],
        'treatment-templates.php' => ['prescriptions'],
        'edit-prescription.php' => ['prescriptions'],
        'update-prescription.php' => ['prescriptions'],
        'print_prescription.php' => ['prescriptions'],
        'print_treatment_only.php' => ['prescriptions'],
        'save_prescription.php' => ['prescriptions'],
        'edit-medicine.php' => ['prescriptions'],
        'edit-medicine2.php' => ['prescriptions'],
        'delete-medicine.php' => ['prescriptions'],
        'followup-appointment.php' => ['appointments'],
        'referred-cases.php' => ['appointments'],
        'add-referred-case.php' => ['appointments'],
        'add-referred-case2.php' => ['appointments'],
        'edit-referred-case.php' => ['appointments'],
        'edit-referred-case2.php' => ['appointments'],
        'treatment-types.php' => ['appointments'],
        'delete-followup.php' => ['appointments'],
        'save_followup.php' => ['appointments'],
        'visits.php' => ['appointments'],
        'visits2.php' => ['appointments'],
        'patient_reports.php' => ['reports'],
        'staff-messages.php' => ['appointments'],
        'staff-messages-poll.php' => ['appointments'],
        'exam-requests.php' => ['appointments'],
    ];

    if (isset($exactMap[$scriptName])) {
        return $exactMap[$scriptName];
    }

    if (preg_match('/^(add|edit|delete|discharge|process_decision|mark|confirm|cancel)[-_](surgery|laser|injection)/', $scriptName)) {
        return ['appointments'];
    }

    if (preg_match('/^(surgery|laser|injection)[-_]appointment(2)?\.php$/', $scriptName)) {
        return ['appointments'];
    }

    if (preg_match('/^(followup|followups|next-visit-appointment|expected_appointments|notify-next-patient|confirm-attendance|cancel-attendance|mark_done|mark_arrived|mark_not_arrived)\.php$/', $scriptName)) {
        return ['appointments'];
    }

    if (preg_match('/^(add|edit|delete|save|update)[-_](patient|visit|image|va)/', $scriptName)) {
        return ['patients'];
    }

    if (preg_match('/^(add|edit|delete|update|save)[-_]prescription/', $scriptName) || preg_match('/^(common-medicines|treatment|print_prescription|print_treatment_only)\.php$/', $scriptName)) {
        return ['prescriptions'];
    }

    return [];
}

function clinic_ensure_retina_drawings(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS retina_drawings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            patient_uuid VARCHAR(64) NULL,
            eye VARCHAR(8) NOT NULL DEFAULT 'OD',
            drawing_date DATE NOT NULL,
            title VARCHAR(180) NULL,
            notes TEXT NULL,
            drawing_data MEDIUMTEXT NULL,
            drawing_image MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            sync_status TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_retina_patient_date (patient_id, drawing_date),
            INDEX idx_retina_patient_eye (patient_id, eye)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function clinic_ensure_patient_images_sync_support(mysqli $con): void
{
    if (!clinic_table_exists($con, 'patient_images')) {
        return;
    }

    clinic_ensure_column($con, 'patient_images', 'sync_status', 'TINYINT(1) NOT NULL DEFAULT 0');
    clinic_ensure_column($con, 'patient_images', 'updated_at', 'DATETIME NULL');

    // Keep updated_at usable for sync ordering even on old schemas.
    if (clinic_column_exists($con, 'patient_images', 'uploaded_at')) {
        mysqli_query($con, "UPDATE `patient_images` SET `updated_at` = `uploaded_at` WHERE `updated_at` IS NULL");
    } elseif (clinic_column_exists($con, 'patient_images', 'date_added')) {
        mysqli_query($con, "UPDATE `patient_images` SET `updated_at` = `date_added` WHERE `updated_at` IS NULL");
    }
}

function clinic_remove_no_show_note(string $notes, string $treatmentLabel, string $appointmentDate): string
{
    $notes = trim($notes);
    if ($notes === '') {
        return $notes;
    }

    $label = trim($treatmentLabel);
    if ($label === '') {
        return $notes;
    }

    $escapedLabel = preg_quote($label, '/');
    $datePattern = preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate) ? preg_quote($appointmentDate, '/') : '\\d{4}-\\d{2}-\\d{2}';
    $pattern = '/^\[لم يحضر موعد ' . $escapedLabel . '(?: ' . $datePattern . ')?\]/u';

    $lines = preg_split('/\R/u', $notes) ?: [];
    $keptLines = [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        if (preg_match($pattern, $line) === 1) {
            continue;
        }

        $keptLines[] = $line;
    }

    return implode(PHP_EOL, $keptLines);
}

function clinic_current_user(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $role = strtolower((string) ($_SESSION['role'] ?? ''));
    if ($role === 'admin') {
        return 'admin';
    }

    $username = trim((string) ($_SESSION['username'] ?? $_SESSION['user'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    $fallbackName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['name'] ?? ''));
    return $fallbackName !== '' ? $fallbackName : 'system';
}

function clinic_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (empty($_SESSION['clinic_csrf_token'])) {
        $_SESSION['clinic_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['clinic_csrf_token'];
}

function clinic_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(clinic_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function clinic_verify_csrf(?string $token = null): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $expected = $_SESSION['clinic_csrf_token'] ?? '';
    $provided = $token ?? ($_POST['csrf_token'] ?? '');

    return is_string($expected) && $expected !== '' &&
        is_string($provided) && hash_equals($expected, $provided);
}

function clinic_require_csrf(): void
{
    if (clinic_verify_csrf()) {
        return;
    }

    http_response_code(403);
    exit('Invalid or expired form token. Please return to the previous page and try again.');
}

function clinic_set_flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $_SESSION['clinic_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function clinic_take_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $flash = $_SESSION['clinic_flash'] ?? null;
    unset($_SESSION['clinic_flash']);

    return is_array($flash) ? $flash : null;
}

function clinic_language(): string
{
    $language = strtolower((string) ($_COOKIE['clinic_lang'] ?? 'ar'));
    return $language === 'en' ? 'en' : 'ar';
}

function clinic_t(string $key, array $replacements = []): string
{
    static $translations = [
        'ar' => [
            'system_health' => 'حالة النسخ والمزامنة',
            'last_local_backup' => 'آخر نسخة محلية',
            'open_conflicts' => 'تعارضات مفتوحة',
            'pending_images' => 'صور بانتظار المزامنة',
            'no_backup' => 'لا توجد نسخة',
            'manage_conflicts' => 'إدارة التعارضات',
            'alert_open_sync_conflicts' => 'يوجد :count تعارض مزامنة مفتوح',
            'alert_no_recent_backup' => 'لا توجد نسخة احتياطية محلية حديثة',
            'alert_backup_age_hours' => 'آخر نسخة احتياطية قبل :hours ساعة',
            'alert_waiting_higher_than_done' => 'حالات الانتظار أعلى من المعاينات المنجزة اليوم',
            'alert_pending_images_count' => 'يوجد :count صورة بانتظار المزامنة',
            'secretary_tomorrow_alert_title' => 'تنبيه السكرتيرة: متابعات الغد',
            'secretary_tomorrow_alert_has' => 'يوجد :count متابعة ليوم الغد (:date)',
            'secretary_tomorrow_alert_none' => 'لا توجد متابعات مسجلة ليوم الغد (:date)',
            'open_tomorrow_followups' => 'فتح متابعات الغد',
            'critical_cases' => 'حالات حرجة',
            'late_operations' => 'عمليات متأخرة',
            'operations_in_5_days' => 'عمليات خلال 5 أيام',
            'open_sync_conflicts_label' => 'تعارضات مزامنة',
            'pending_sync_images_label' => 'صور تنتظر المزامنة',
            'tomorrow_followups_label' => 'متابعات الغد',
            'clinic_name' => 'عيادة الدكتور حيدر صباح الربيعي',
            'login_page_title' => 'تسجيل الدخول | عيادة الدكتور حيدر صباح الربيعي',
            'login_heading' => 'تسجيل الدخول',
            'login_subtitle' => 'ادخل بحسابك للوصول إلى لوحة التحكم وإدارة ملفات العيادة.',
            'username_label' => 'اسم المستخدم',
            'password_label' => 'كلمة المرور',
            'login_button' => 'دخول',
            'login_invalid_credentials' => 'بيانات الدخول غير صحيحة',
            'smart_clinic_system' => 'نظام العيادة الذكي',
            'staff_accounts_hero' => 'حسابات الموظفين، بصلاحيات واضحة ومظهر احترافي',
            'staff_accounts_description' => 'يمكنك إنشاء حسابات للموظفين من صفحة التسجيل، ثم تحديد دور كل مستخدم وصلاحياته العملية قبل منحه الوصول إلى أجزاء النظام المناسبة له.',
            'quick_staff_add_title' => 'إضافة موظفين بسرعة',
            'quick_staff_add_desc' => 'ربط مباشر مع صفحة التسجيل لإدارة الحسابات من مكان واحد.',
            'flexible_permissions_title' => 'صلاحيات مرنة',
            'flexible_permissions_desc' => 'تحديد ما إذا كان المستخدم يدير المرضى أو المواعيد أو التقارير أو الحسابات.',
            'elegant_mobile_ui_title' => 'واجهة أنيقة ومناسبة للموبايل',
            'elegant_mobile_ui_desc' => 'تصميم متجاوب بلمسة حديثة ووضع ليلي متناسق.',
            'open_staff_registration_page' => 'فتح صفحة إنشاء حساب موظف',
        ],
        'en' => [
            'system_health' => 'Backup and Sync Health',
            'last_local_backup' => 'Last Local Backup',
            'open_conflicts' => 'Open Conflicts',
            'pending_images' => 'Images Pending Sync',
            'no_backup' => 'No Backup Found',
            'manage_conflicts' => 'Manage Conflicts',
            'alert_open_sync_conflicts' => 'There are :count open sync conflicts',
            'alert_no_recent_backup' => 'No recent local backup was found',
            'alert_backup_age_hours' => 'Last backup was :hours hours ago',
            'alert_waiting_higher_than_done' => 'Waiting visits are higher than completed visits today',
            'alert_pending_images_count' => 'There are :count images pending sync',
            'secretary_tomorrow_alert_title' => 'Secretary Alert: Tomorrow Follow-ups',
            'secretary_tomorrow_alert_has' => 'There are :count follow-ups for tomorrow (:date)',
            'secretary_tomorrow_alert_none' => 'No follow-ups are scheduled for tomorrow (:date)',
            'open_tomorrow_followups' => 'Open Tomorrow Follow-ups',
            'critical_cases' => 'Critical Cases',
            'late_operations' => 'Late Operations',
            'operations_in_5_days' => 'Operations in 5 Days',
            'open_sync_conflicts_label' => 'Sync Conflicts',
            'pending_sync_images_label' => 'Images Pending Sync',
            'tomorrow_followups_label' => 'Tomorrow Follow-ups',
            'clinic_name' => 'Dr. Haider Sabah Al-Rubaie Clinic',
            'login_page_title' => 'Login | Dr. Haider Sabah Al-Rubaie Clinic',
            'login_heading' => 'Login',
            'login_subtitle' => 'Sign in to access the dashboard and manage clinic files.',
            'username_label' => 'Username',
            'password_label' => 'Password',
            'login_button' => 'Log In',
            'login_invalid_credentials' => 'Incorrect login details',
            'smart_clinic_system' => 'Smart Clinic System',
            'staff_accounts_hero' => 'Staff Accounts with Clear Permissions and a Professional Look',
            'staff_accounts_description' => 'You can create staff accounts from the registration page, then define each user role and operational permissions before granting access to relevant system sections.',
            'quick_staff_add_title' => 'Quick Staff Onboarding',
            'quick_staff_add_desc' => 'Direct link to the registration page to manage accounts in one place.',
            'flexible_permissions_title' => 'Flexible Permissions',
            'flexible_permissions_desc' => 'Choose whether the user manages patients, appointments, reports, or accounts.',
            'elegant_mobile_ui_title' => 'Elegant, Mobile-Friendly UI',
            'elegant_mobile_ui_desc' => 'Responsive design with a modern touch and consistent dark mode.',
            'open_staff_registration_page' => 'Open Staff Registration Page',
        ],
    ];

    $language = clinic_language();
    $text = $translations[$language][$key] ?? $translations['ar'][$key] ?? $key;
    foreach ($replacements as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
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

function clinic_prescription_frequency_options(): array
{
    return [
        '1x1',
        '1x2',
        '1x3',
        '1x4',
        'مرة باليوم',
        'مرتان باليوم',
        'ثلاثة مرات باليوم',
        'أربع مرات باليوم',
        'كل 6 ساعات',
        'كل 8 ساعات',
        'عند الحاجة',
        'قبل النوم',
    ];
}

function clinic_prescription_duration_options(): array
{
    return [
        'ثلاثة أيام',
        'خمسة أيام',
        'لمدة أسبوع',
        'عشرة أيام',
        'لمدة أسبوعين',
        'شهر',
        'ستة أسابيع',
        'ثلاثة أشهر',
        'باستمرار حتى المراجعة',
    ];
}

function clinic_get_prescription_followup(mysqli $con, array $prescription): ?array
{
    if (!empty($prescription['followup_id']) && !empty($prescription['patient_id'])) {
        $followup_stmt = mysqli_prepare($con, '
            SELECT followup_date, followup_reason, note, followup_type
            FROM followups
            WHERE id = ? AND patient_id = ?
            LIMIT 1
        ');
        $followup_id = (int) $prescription['followup_id'];
        $patient_id = (int) $prescription['patient_id'];
        mysqli_stmt_bind_param($followup_stmt, 'ii', $followup_id, $patient_id);
        mysqli_stmt_execute($followup_stmt);
        $followup = mysqli_fetch_assoc(mysqli_stmt_get_result($followup_stmt));
        if ($followup) {
            $followup['followup_type'] = (($followup['followup_type'] ?? 'review') === 'next_visit') ? 'next_visit' : 'review';
            return $followup;
        }
    }

    if (!empty($prescription['next_followup_date']) || !empty($prescription['next_followup_reason']) || !empty($prescription['next_followup_note'])) {
        return [
            'followup_date' => $prescription['next_followup_date'] ?? '',
            'followup_reason' => $prescription['next_followup_reason'] ?? '',
            'note' => $prescription['next_followup_note'] ?? '',
            'followup_type' => 'review',
        ];
    }

    return null;
}

function clinic_arabic_day_name(string $date): string
{
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return '';
    }

    $days = [
        'Sunday' => 'الأحد',
        'Monday' => 'الاثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
        'Saturday' => 'السبت',
    ];

    $english = date('l', $timestamp);
    return $days[$english] ?? $english;
}

function clinic_format_display_date(string $date): string
{
    $date = trim($date);
    if ($date === '') {
        return '';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('Y/m/d', $timestamp);
}

function clinic_followup_print_line(?array $followup): string
{
    if (!$followup || empty($followup['followup_date'])) {
        return '';
    }

    $date = (string) $followup['followup_date'];
    $displayDate = clinic_format_display_date($date);
    $day = clinic_arabic_day_name($date);
    $typeLabel = (($followup['followup_type'] ?? 'review') === 'next_visit') ? 'موعد الفحص القادم' : 'موعد المراجعة القادمة';
    $text = $typeLabel . ' يوم ' . $day . ' بتاريخ ' . $displayDate;

    if (!empty($followup['followup_reason'])) {
        $text .= ' - ' . trim((string) $followup['followup_reason']);
    }

    return $text;
}

function clinic_sync_prescription_followup(
    mysqli $con,
    int $patient_id,
    int $prescription_id,
    int $current_followup_id,
    string $followup_date,
    string $followup_reason,
    string $followup_note,
    string $followup_type,
    bool $isLocal
): int {
    $followup_date = trim($followup_date);
    $followup_reason = trim($followup_reason);
    $followup_note = trim($followup_note);
    $followup_type = in_array($followup_type, ['review', 'next_visit'], true) ? $followup_type : 'review';
    $hasFollowup = ($followup_date !== '' || $followup_reason !== '' || $followup_note !== '');

    if ($hasFollowup && ($followup_date === '' || $followup_reason === '')) {
        throw new InvalidArgumentException('يجب إدخال تاريخ المراجعة وسببها معًا');
    }

    $existing = null;
    if ($current_followup_id > 0 && clinic_table_exists($con, 'followups')) {
        $existing_stmt = mysqli_prepare($con, '
            SELECT id, status, source_type, source_id
            FROM followups
            WHERE id = ? AND patient_id = ?
            LIMIT 1
        ');
        mysqli_stmt_bind_param($existing_stmt, 'ii', $current_followup_id, $patient_id);
        mysqli_stmt_execute($existing_stmt);
        $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existing_stmt)) ?: null;
    }

    $canReuseExisting = $existing
        && (($existing['status'] ?? 'pending') === 'pending')
        && (($existing['source_type'] ?? '') === '' || ($existing['source_type'] ?? '') === 'prescription');

    if (!$hasFollowup) {
        if ($canReuseExisting) {
            $delete_stmt = mysqli_prepare($con, 'DELETE FROM followups WHERE id = ? AND patient_id = ?');
            mysqli_stmt_bind_param($delete_stmt, 'ii', $current_followup_id, $patient_id);
            mysqli_stmt_execute($delete_stmt);
        }

        return 0;
    }

    if ($canReuseExisting) {
        if ($isLocal) {
            $update_stmt = mysqli_prepare($con, '
                UPDATE followups
                SET followup_date = ?, followup_reason = ?, note = ?, followup_type = ?, source_type = "prescription", source_id = ?, updated_at = NOW(), sync_status = 0
                WHERE id = ? AND patient_id = ?
            ');
            mysqli_stmt_bind_param($update_stmt, 'ssssiii', $followup_date, $followup_reason, $followup_note, $followup_type, $prescription_id, $current_followup_id, $patient_id);
        } else {
            $update_stmt = mysqli_prepare($con, '
                UPDATE followups
                SET followup_date = ?, followup_reason = ?, note = ?, followup_type = ?, source_type = "prescription", source_id = ?, updated_at = NOW()
                WHERE id = ? AND patient_id = ?
            ');
            mysqli_stmt_bind_param($update_stmt, 'ssssiii', $followup_date, $followup_reason, $followup_note, $followup_type, $prescription_id, $current_followup_id, $patient_id);
        }
        mysqli_stmt_execute($update_stmt);
        return $current_followup_id;
    }

    if ($isLocal) {
        $insert_stmt = mysqli_prepare($con, '
            INSERT INTO followups (patient_id, followup_date, followup_reason, note, followup_type, source_type, source_id, updated_at, sync_status)
            VALUES (?, ?, ?, ?, ?, "prescription", ?, NOW(), 0)
        ');
        mysqli_stmt_bind_param($insert_stmt, 'issssi', $patient_id, $followup_date, $followup_reason, $followup_note, $followup_type, $prescription_id);
    } else {
        $insert_stmt = mysqli_prepare($con, '
            INSERT INTO followups (patient_id, followup_date, followup_reason, note, followup_type, source_type, source_id, updated_at)
            VALUES (?, ?, ?, ?, ?, "prescription", ?, NOW())
        ');
        mysqli_stmt_bind_param($insert_stmt, 'issssi', $patient_id, $followup_date, $followup_reason, $followup_note, $followup_type, $prescription_id);
    }
    mysqli_stmt_execute($insert_stmt);

    return (int) mysqli_insert_id($con);
}

function clinic_active_patient_where(mysqli $con, string $alias = 'add_patient'): string
{
    if (clinic_column_exists($con, 'add_patient', 'is_deleted')) {
        return "$alias.is_deleted = 0";
    }

    return "1=1";
}
