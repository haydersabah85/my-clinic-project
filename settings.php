<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';
$requiredPermissions = ['settings'];
include 'admin-only.php';

clinic_ensure_infrastructure($con);
clinic_ensure_runtime_controls($con);
clinic_ensure_sync_conflicts($con);
clinic_ensure_column($con, 'users', 'permissions_json', 'LONGTEXT NULL');

$modeMessage = '';
$autoSyncMessage = '';
$accountManageMessage = '';
$canManageWriteLock = clinic_can_manage_online_write_lock($con);
$canManageUsers = clinic_user_has_permission(['users']);
$isAdminRole = strtolower((string) ($_SESSION['role'] ?? '')) === 'admin';
$canManageAccounts = $isAdminRole;
$staffMessage = '';
$activeSection = trim((string) ($_POST['active_section'] ?? ($_GET['section'] ?? 'account-center')));

$staffPermissionOptions = [
    'patients' => 'إدارة المرضى',
    'appointments' => 'إدارة المواعيد',
    'prescriptions' => 'الوصفات الطبية',
    'reports' => 'التقارير والإحصاءات',
    'settings' => 'الإعدادات العامة',
    'backup' => 'النسخ الاحتياطي والاستعادة',
    'sync' => 'المزامنة السحابية',
    'users' => 'إدارة الحسابات',
];

if (isset($_POST['save_runtime_mode'])) {
    if (!$canManageWriteLock) {
        $modeMessage = "<p style='color:red'>❌ هذا الخيار متاح فقط لحساب المدير الرئيسي</p>";
    } else {
        $writeEnabled = isset($_POST['online_write_enabled']) ? '0' : '1';
        if (clinic_set_app_setting($con, 'online_write_lock', $writeEnabled)) {
            $modeMessage = "<p style='color:green'>✔ تم تحديث وضع الكتابة بنجاح</p>";
        } else {
            $modeMessage = "<p style='color:red'>❌ فشل تحديث وضع الكتابة</p>";
        }
    }
}

if (isset($_POST['save_auto_sync'])) {
    $enabled = isset($_POST['auto_pull_enabled']) ? '1' : '0';
    $interval = (int) ($_POST['auto_pull_interval_minutes'] ?? 10);
    if (!in_array($interval, [5, 10], true)) {
        $interval = 10;
    }

    $okEnabled = clinic_set_app_setting($con, 'auto_pull_enabled', $enabled);
    $okInterval = clinic_set_app_setting($con, 'auto_pull_interval_minutes', (string) $interval);

    if ($okEnabled && $okInterval) {
        $autoSyncMessage = "<p style='color:green'>✔ تم حفظ إعدادات المزامنة التلقائية</p>";
    } else {
        $autoSyncMessage = "<p style='color:red'>❌ فشل حفظ إعدادات المزامنة التلقائية</p>";
    }
}

if (isset($_POST['register_staff'])) {
    if (!$canManageAccounts) {
        $staffMessage = "<p style='color:red'>❌ إدارة الحسابات متاحة فقط من خلال حساب المدير</p>";
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $rawPassword = (string) ($_POST['pass'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $permissions = clinic_normalize_permissions($_POST['permissions'] ?? []);

        if ($fullName === '' || $username === '' || $rawPassword === '' || $role === '') {
            $staffMessage = "<p style='color:red'>❌ يرجى تعبئة جميع الحقول المطلوبة</p>";
        } else {
            $checkStmt = $con->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            if (!$checkStmt) {
                $staffMessage = "<p style='color:red'>❌ تعذر التحقق من اسم المستخدم</p>";
            } else {
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $staffMessage = "<p style='color:red'>❌ اسم المستخدم موجود مسبقاً</p>";
                } else {
                    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
                    $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $stmt = $con->prepare("INSERT INTO users (full_name, username, pass, role, permissions_json) VALUES (?,?,?,?,?)");
                    if ($stmt) {
                        $stmt->bind_param("sssss", $fullName, $username, $hashedPassword, $role, $permissionsJson);

                        if ($stmt->execute()) {
                            $staffMessage = "<p style='color:green'>✔ تم إنشاء حساب الموظف بنجاح</p>";
                        } else {
                            $staffMessage = (mysqli_errno($con) === 1062)
                                ? "<p style='color:red'>❌ اسم المستخدم موجود مسبقاً</p>"
                                : "<p style='color:red'>❌ تعذر حفظ الحساب، حاول مرة أخرى</p>";
                        }
                    } else {
                        $staffMessage = "<p style='color:red'>❌ تعذر تجهيز الاستعلام</p>";
                    }
                }
            }
        }
    }
}

if (isset($_POST['save_user_account'])) {
    if (!$canManageAccounts) {
        $accountManageMessage = "<p style='color:red'>❌ إدارة الحسابات متاحة فقط من خلال حساب المدير</p>";
    } else {
        $userId = (int) ($_POST['edit_user_id'] ?? 0);
        $fullName = trim((string) ($_POST['edit_full_name'] ?? ''));
        $username = trim((string) ($_POST['edit_username'] ?? ''));
        $newPassword = (string) ($_POST['edit_pass'] ?? '');
        $permissions = clinic_normalize_permissions($_POST['edit_permissions'] ?? []);
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($userId <= 0 || $fullName === '' || $username === '') {
            $accountManageMessage = "<p style='color:red'>❌ يرجى تعبئة الاسم واسم المستخدم بشكل صحيح</p>";
        } else {
            $checkStmt = $con->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
            if (!$checkStmt) {
                $accountManageMessage = "<p style='color:red'>❌ تعذر التحقق من اسم المستخدم</p>";
            } else {
                $checkStmt->bind_param("si", $username, $userId);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $accountManageMessage = "<p style='color:red'>❌ اسم المستخدم مستخدم مسبقا</p>";
                } else {
                    if ($newPassword !== '') {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $con->prepare("UPDATE users SET full_name = ?, username = ?, pass = ?, permissions_json = ? WHERE id = ? LIMIT 1");
                        if ($stmt) {
                            $stmt->bind_param("ssssi", $fullName, $username, $hashedPassword, $permissionsJson, $userId);
                        }
                    } else {
                        $stmt = $con->prepare("UPDATE users SET full_name = ?, username = ?, permissions_json = ? WHERE id = ? LIMIT 1");
                        if ($stmt) {
                            $stmt->bind_param("sssi", $fullName, $username, $permissionsJson, $userId);
                        }
                    }

                    if (!isset($stmt) || !$stmt) {
                        $accountManageMessage = "<p style='color:red'>❌ تعذر تجهيز تحديث الحساب</p>";
                    } else {
                        if ($stmt->execute()) {
                            $accountManageMessage = "<p style='color:green'>✔ تم تحديث بيانات الحساب بنجاح</p>";
                        } else {
                            $accountManageMessage = (mysqli_errno($con) === 1062)
                                ? "<p style='color:red'>❌ اسم المستخدم مستخدم مسبقا</p>"
                                : "<p style='color:red'>❌ فشل تحديث بيانات الحساب</p>";
                        }
                    }
                }
            }
        }
    }
}

$isOnlineWriteLocked = clinic_is_online_write_locked($con, (bool) $IS_LOCAL);

$autoPullEnabled = clinic_auto_pull_is_enabled($con);
$autoPullInterval = clinic_auto_pull_interval_minutes($con);
$autoPullLastSuccess = clinic_get_app_setting($con, 'auto_pull_last_success_at', 'لم يتم التنفيذ بعد');
$autoPullLastAttempt = clinic_get_app_setting($con, 'auto_pull_last_attempt_at', 'لم يتم التنفيذ بعد');
$autoPullLastStatus = clinic_get_app_setting($con, 'auto_pull_last_status', 'unknown');
$autoPullLastSummary = clinic_get_app_setting($con, 'auto_pull_last_summary', '-');

$openConflicts = 0;
if ($IS_LOCAL) {
    $openConflictsRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status = 'open'"));
    $openConflicts = (int) ($openConflictsRow['total'] ?? 0);
}

$availableSections = ['account-center', 'quick-actions', 'staff-registration', 'runtime-center', 'auto-sync-center', 'emergency-guide'];
if (!in_array($activeSection, $availableSections, true)) {
    $activeSection = 'account-center';
}

$usersList = [];
$usersResult = mysqli_query($con, "SELECT id, full_name, username, role, created_at, permissions_json FROM users ORDER BY id ASC");
while ($usersResult && ($row = mysqli_fetch_assoc($usersResult))) {
    $decodedPermissions = json_decode((string) ($row['permissions_json'] ?? '[]'), true);
    $row['permissions'] = clinic_normalize_permissions(is_array($decodedPermissions) ? $decodedPermissions : []);
    $usersList[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
</head>

<script src="assets/theme.js" defer></script>

<style>
    :root {
        --bg: #f3f6fb;
        --card: #ffffff;
        --border: #d9e3ef;
        --text: #0f172a;
        --muted: #475569;
        --primary: #1d4ed8;
        --success: #0f766e;
        --warning: #b45309;
        --danger: #b91c1c;
    }

    body[data-theme="dark"] {
        --bg: #0b1220;
        --card: #111b2f;
        --border: #23314b;
        --text: #e2e8f0;
        --muted: #94a3b8;
        --primary: #60a5fa;
    }

    body {
        font-family: Tahoma, Arial, sans-serif;
        margin: 0;
        background: var(--bg);
        color: var(--text);
    }

    .page {
        max-width: 1080px;
        margin: 24px auto;
        padding: 0 14px 24px;
    }

    .header {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .header h1 {
        margin: 0 0 6px;
        color: var(--primary);
        font-size: 24px;
    }

    .header p {
        margin: 0;
        color: var(--muted);
    }

    .section-nav {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 10px;
        margin-bottom: 14px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .section-nav a {
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: var(--text);
        font-weight: 700;
        background: #f8fbff;
    }

    .section-nav a:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .section-nav a.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .notice {
        margin: 0 0 14px;
        padding: 10px 12px;
        border-radius: 10px;
        font-weight: 700;
    }

    .notice.error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    .notice.success {
        background: #ecfdf3;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .actions-card,
    .staff-card,
    .runtime-panel,
    .emergency-guide {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 16px;
        margin-bottom: 14px;
    }

    .settings-section {
        display: none;
    }

    .settings-section.active {
        display: grid;
    }

    .actions-card.settings-section.active,
    .runtime-panel.settings-section.active,
    .emergency-guide.settings-section.active {
        display: block;
    }

    .section-title {
        margin: 0 0 10px;
        color: var(--text);
        font-size: 19px;
    }

    .section-subtitle {
        margin: 0 0 10px;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .actions-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .action-group {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px;
        background: #fafcff;
    }

    .action-group h4 {
        margin: 0 0 6px;
        font-size: 16px;
        color: var(--text);
    }

    .risk-group {
        border-color: #fecaca;
        background: #fff6f6;
    }

    .risk-group h4 {
        color: #991b1b;
    }

    .risk-note {
        margin: 0 0 10px;
        color: #9a3412;
        font-weight: 700;
        font-size: 13px;
        line-height: 1.7;
    }

    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
    }

    .staff-card {
        display: grid;
        gap: 14px;
    }

    .staff-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .staff-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .staff-field.full {
        grid-column: 1 / -1;
    }

    .staff-field label,
    .staff-permissions>label {
        font-weight: 700;
        color: var(--text);
    }

    .staff-field input,
    .staff-field select {
        min-height: 44px;
        border-radius: 10px;
        border: 1px solid var(--border);
        padding: 10px 12px;
        background: #fff;
        color: var(--text);
        font-family: inherit;
    }

    .staff-permissions {
        display: grid;
        gap: 10px;
    }

    .staff-permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .staff-permission-item {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 10px 12px;
        background: #fafcff;
    }

    .staff-permission-item label {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        line-height: 1.6;
        cursor: pointer;
        font-weight: 700;
    }

    .staff-permission-item input {
        margin-top: 4px;
        accent-color: var(--primary);
    }

    .staff-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .accounts-list {
        display: grid;
        gap: 10px;
    }

    .account-item {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 12px;
        background: #fafcff;
    }

    .account-item form {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
        align-items: end;
    }

    .account-item .meta {
        margin-bottom: 8px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
    }

    .account-item input {
        min-height: 40px;
        border-radius: 8px;
        border: 1px solid var(--border);
        padding: 8px 10px;
    }

    .account-permissions {
        grid-column: 1 / -1;
        display: grid;
        gap: 8px;
    }

    .account-permissions-title {
        font-weight: 700;
        color: var(--text);
    }

    .account-permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
    }

    .input-with-toggle {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-with-toggle input {
        width: 100%;
        padding-inline-end: 70px;
    }

    .pass-toggle {
        position: absolute;
        inset-inline-end: 6px;
        border: 1px solid var(--border);
        border-radius: 7px;
        background: #f8fbff;
        color: var(--text);
        min-height: 30px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .username-hint {
        margin-top: 4px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    .username-hint.error {
        color: var(--danger);
    }

    .btn-account-save {
        background: #0f766e;
    }

    .btn-account-save:hover {
        background: #0b5f59;
    }

    .btn-staff {
        background: #0f766e;
    }

    .btn-staff:hover {
        background: #0b5f59;
    }

    .btn-link,
    .btn-submit {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        min-height: 42px;
        border-radius: 10px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        padding: 10px 12px;
    }

    .btn-home {
        background: #ea580c;
    }

    .btn-home:hover {
        background: #c2410c;
    }

    .btn-restore {
        background: #2563eb;
    }

    .btn-restore:hover {
        background: #1d4ed8;
    }

    .btn-backup {
        background: #7e22ce;
    }

    .btn-backup:hover {
        background: #6b21a8;
    }

    .btn-pull {
        background: var(--success);
    }

    .btn-pull:hover {
        background: #0d5f58;
    }

    .btn-push {
        background: var(--warning);
    }

    .btn-push:hover {
        background: #92400e;
    }

    .btn-conflicts {
        background: #475569;
    }

    .btn-conflicts:hover {
        background: #334155;
    }

    .btn-manual {
        background: #16a34a;
    }

    .btn-manual:hover {
        background: #15803d;
    }

    .runtime-status {
        margin: 8px 0 12px;
        font-weight: 700;
    }

    .runtime-note {
        color: var(--muted);
        line-height: 1.8;
        margin-bottom: 10px;
    }

    .runtime-form {
        display: block;
    }

    .runtime-form label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-weight: 700;
    }

    .runtime-form button {
        margin-top: 0;
    }

    .emergency-guide h3 {
        margin: 0 0 10px;
        color: var(--primary);
    }

    .emergency-guide h4 {
        margin: 12px 0 6px;
        color: var(--text);
    }

    .emergency-guide ol {
        margin: 0;
        padding-inline-start: 20px;
        color: var(--text);
    }

    .conflict-badge,
    .ok-badge {
        display: inline-block;
        margin-top: 10px;
        border-radius: 999px;
        padding: 5px 12px;
        font-weight: 700;
    }

    .conflict-badge {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }

    .ok-badge {
        background: #ecfdf3;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .emergency-guide a {
        color: var(--primary);
        font-weight: 700;
    }

    .cloud-note {
        margin: 0;
        padding: 10px 12px;
        border-radius: 10px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #9f1239;
        font-weight: 700;
        line-height: 1.8;
    }

    @media (min-width: 880px) {
        .actions-layout {
            grid-template-columns: 1fr 1fr;
        }

        .staff-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 680px) {
        .staff-grid {
            grid-template-columns: 1fr;
        }

        .account-item form {
            grid-template-columns: 1fr;
        }

        .staff-actions .btn-link,
        .staff-actions .btn-submit {
            width: 100%;
        }
    }
</style>


<body>
    <main class="page">
        <section class="header">
            <h1>الإعدادات</h1>
            <p>إدارة النسخ الاحتياطي، المزامنة، وإجراءات الطوارئ من مكان واحد.</p>
        </section>

        <nav class="section-nav">
            <a href="#account-center" data-section-link="account-center">إدارة الحسابات</a>
            <a href="#quick-actions" data-section-link="quick-actions">الإجراءات السريعة</a>
            <a href="#staff-registration" data-section-link="staff-registration">إضافة موظف</a>
            <a href="#runtime-center" data-section-link="runtime-center">وضع التشغيل</a>
            <a href="#auto-sync-center" data-section-link="auto-sync-center">المزامنة التلقائية</a>
            <a href="#emergency-guide" data-section-link="emergency-guide">دليل الطوارئ</a>
        </nav>

        <?php if (!empty($modeMessage)): ?>
            <div class="notice <?php echo (strpos($modeMessage, '❌') !== false) ? 'error' : 'success'; ?>">
                <?php echo strip_tags($modeMessage); ?>
            </div>
        <?php endif; ?>

        <section class="staff-card settings-section" id="account-center" data-section-panel="account-center">
            <h3 class="section-title">إدارة الحسابات</h3>
            <p class="section-subtitle">تعديل بيانات المدير والموظفين من مكان واحد. هذه الإدارة متاحة فقط عند تسجيل الدخول بحساب المدير.</p>

            <?php if (!$canManageAccounts): ?>
                <p class="cloud-note">إدارة الحسابات (تعديل الاسم واسم المستخدم وكلمة المرور) متاحة فقط لحساب المدير.</p>
            <?php endif; ?>

            <?php if (!empty($accountManageMessage)): ?>
                <div class="notice <?php echo (strpos($accountManageMessage, '❌') !== false) ? 'error' : 'success'; ?>">
                    <?php echo strip_tags($accountManageMessage); ?>
                </div>
            <?php endif; ?>

            <div class="accounts-list">
                <?php foreach ($usersList as $u): ?>
                    <article class="account-item">
                        <div class="meta">
                            الحساب #<?php echo (int) $u['id']; ?>
                            | الدور: <?php echo htmlspecialchars((string) $u['role'], ENT_QUOTES, 'UTF-8'); ?>
                            | أنشئ بتاريخ: <?php echo htmlspecialchars((string) ($u['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <form method="post" class="manage-account-form" data-user-id="<?php echo (int) $u['id']; ?>">
                            <input type="hidden" name="active_section" value="account-center">
                            <input type="hidden" name="edit_user_id" value="<?php echo (int) $u['id']; ?>">
                            <input type="text" name="edit_full_name" value="<?php echo htmlspecialchars((string) $u['full_name'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="الاسم الكامل" required>
                            <div>
                                <input class="js-username-field" type="text" name="edit_username" value="<?php echo htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="اسم المستخدم" required>
                                <div class="username-hint">اسم المستخدم متاح</div>
                            </div>
                            <div class="input-with-toggle">
                                <input id="edit_pass_<?php echo (int) $u['id']; ?>" type="password" name="edit_pass" placeholder="كلمة مرور جديدة (اختياري)">
                                <button class="pass-toggle" type="button" data-toggle-pass data-target="edit_pass_<?php echo (int) $u['id']; ?>">إظهار</button>
                            </div>
                            <div class="account-permissions">
                                <div class="account-permissions-title">صلاحيات هذا الحساب</div>
                                <div class="account-permissions-grid">
                                    <?php foreach ($staffPermissionOptions as $key => $label): ?>
                                        <div class="staff-permission-item">
                                            <label>
                                                <input type="checkbox" name="edit_permissions[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($key, $u['permissions'] ?? [], true) ? 'checked' : ''; ?>>
                                                <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button class="btn-submit btn-account-save" type="submit" name="save_user_account" <?php echo $canManageAccounts ? '' : 'disabled'; ?>>حفظ التعديل</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="actions-card settings-section" id="quick-actions" data-section-panel="quick-actions">
            <h3 class="section-title">الإجراءات السريعة</h3>
            <p class="section-subtitle">تم تقسيم الإجراءات إلى مجموعتين: إجراءات تشغيل اعتيادية، وإجراءات حساسة تتطلب انتباها أعلى.</p>

            <div class="actions-layout">
                <section class="action-group">
                    <h4>إجراءات اعتيادية</h4>
                    <div class="actions-grid">
                        <a class="btn-link btn-home" href="dashboard.php">الصفحة الرئيسية</a>
                        <?php if ($IS_LOCAL): ?>
                            <a class="btn-link btn-conflicts" href="sync_conflicts.php">Manage Conflicts</a>
                            <a class="btn-link btn-pull" href="sync_from_online.php" onclick="return confirm('سيتم سحب أحدث بيانات السحابة إلى المحلي. المتابعة؟')">Sync From Online</a>
                            <a class="btn-link btn-pull" href="sync_from_online.php?full=1" onclick="return confirm('سيتم تنفيذ سحب كامل من السحابة (Full Pull) وقد يستغرق وقتا اطول. المتابعة؟')">Sync From Online (Full)</a>

                            <form method="post" style="margin:0;">
                                <button class="btn-submit btn-manual" name="manual_backup" onclick="return confirm('هل تريد إنشاء نسخة احتياطية الآن؟')">Backup Local Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="action-group risk-group">
                    <h4>إجراءات حساسة</h4>
                    <p class="risk-note">تنفذ هذه الإجراءات فقط عند الحاجة ويفضل بعد مراجعة دليل الطوارئ أدناه.</p>
                    <div class="actions-grid">
                        <a class="btn-link btn-restore" href="restore.php">Restore</a>

                        <?php if ($IS_LOCAL): ?>
                            <a class="btn-link btn-push" href="sync_to_online_safe.php" onclick="return confirm('سيتم رفع المحلي إلى السحابة بطريقة آمنة مع كشف التعارضات. المتابعة؟')">Safe Sync To Online</a>
                            <a class="btn-link btn-backup" href="backup_and_upload.php">Backup Online</a>
                        <?php else: ?>
                            <p class="cloud-note" style="grid-column: 1 / -1;">
                                على النسخة السحابية: تم تعطيل أزرار النسخ المحلي والرفع إلى السحابة.
                                هذه العملية يجب أن تتم من سيرفر العيادة المحلي فقط.
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </section>

        <section class="staff-card settings-section" id="staff-registration" data-section-panel="staff-registration">
            <h3 class="section-title">إضافة موظف جديد</h3>
            <p class="section-subtitle">إنشاء حساب موظف مباشر من داخل الإعدادات مع تحديد الصلاحيات الخاصة به.</p>

            <?php if (!$canManageAccounts): ?>
                <p class="cloud-note">إضافة الموظفين متاحة فقط عند تسجيل الدخول بحساب المدير.</p>
            <?php endif; ?>

            <form method="post" class="staff-grid">
                <input type="hidden" name="active_section" value="staff-registration">
                <div class="staff-field full">
                    <label for="staff_full_name">الاسم الكامل</label>
                    <input id="staff_full_name" type="text" name="full_name" placeholder="مثال: سارة أحمد" required>
                </div>

                <div class="staff-field">
                    <label for="staff_username">اسم المستخدم</label>
                    <input id="staff_username" class="js-username-field" type="text" name="username" placeholder="مثال: sara.a" required>
                    <div class="username-hint">اسم المستخدم متاح</div>
                </div>

                <div class="staff-field">
                    <label for="staff_pass">كلمة المرور</label>
                    <div class="input-with-toggle">
                        <input id="staff_pass" type="password" name="pass" placeholder="كلمة المرور" required>
                        <button class="pass-toggle" type="button" data-toggle-pass data-target="staff_pass">إظهار</button>
                    </div>
                </div>

                <div class="staff-field full">
                    <label for="staff_role">الدور</label>
                    <select id="staff_role" name="role" required>
                        <option value="">اختر الدور</option>
                        <option value="admin">أدمن</option>
                        <option value="doctor">طبيب</option>
                        <option value="secretary">استقبال / سكرتارية</option>
                        <option value="nurse">تمريض</option>
                        <option value="accountant">محاسبة</option>
                    </select>
                </div>

                <div class="staff-permissions full">
                    <label>الصلاحيات</label>
                    <div class="staff-permissions-grid">
                        <?php foreach ($staffPermissionOptions as $key => $label): ?>
                            <div class="staff-permission-item">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="staff-actions full">
                    <button class="btn-submit btn-staff" type="submit" name="register_staff" <?php echo $canManageAccounts ? '' : 'disabled'; ?>>حفظ الموظف</button>
                    <a class="btn-link btn-conflicts" href="registration.php">فتح صفحة التسجيل الكاملة</a>
                </div>
            </form>

            <?php if (!empty($staffMessage)): ?>
                <div class="notice <?php echo (strpos($staffMessage, '❌') !== false) ? 'error' : 'success'; ?>">
                    <?php echo strip_tags($staffMessage); ?>
                </div>
            <?php endif; ?>
        </section>

        <?php
        if ($IS_LOCAL) {
            include "config_backup.php";
        }

        if ($IS_LOCAL && isset($_POST['manual_backup'])) {

            $date = date("Y-m-d_H-i-s");
            $file = $BACKUP_PATH . "/manual_backup_$date.sql";

            $command = "\"$MYSQLDUMP_PATH\" --user=$DB_USER --password=$DB_PASS --host=$DB_HOST $DB_NAME > \"$file\"";

            system($command, $result);

            if ($result === 0) {
                echo "<p style='color:green'>✔ تم إنشاء النسخة الاحتياطية بنجاح</p>";
            } else {
                echo "<p style='color:red'>❌ فشل إنشاء النسخة الاحتياطية</p>";
            }
        }
        ?>

        <section class="runtime-panel settings-section" dir="rtl" id="runtime-center" data-section-panel="runtime-center">
            <h3>وضع التشغيل أثناء الطوارئ</h3>
            <div class="runtime-status">
                حالة النسخة السحابية الآن:
                <?php if ($isOnlineWriteLocked): ?>
                    <span style="color:#b30000">قراءة فقط (الكتابة مقفلة)</span>
                <?php else: ?>
                    <span style="color:#0a7a20">قراءة وكتابة مفعلة</span>
                <?php endif; ?>
            </div>
            <div class="runtime-note">
                عند انقطاع الإنترنت في العيادة: اقفل الكتابة على النسخة السحابية لتجنب تضارب البيانات، واعمل محليا.
                بعد رجوع الإنترنت ورفع البيانات: أعد تفعيل الكتابة السحابية.
            </div>
            <?php if ($canManageWriteLock): ?>
                <form class="runtime-form" method="post">
                    <input type="hidden" name="active_section" value="runtime-center">
                    <label>
                        <input type="checkbox" name="online_write_enabled" value="1" <?php echo $isOnlineWriteLocked ? '' : 'checked'; ?>>
                        السماح بالكتابة على النسخة السحابية
                    </label>
                    <button type="submit" name="save_runtime_mode" onclick="return confirm('تأكيد تحديث وضع الكتابة السحابية؟')">حفظ وضع التشغيل</button>
                </form>
            <?php else: ?>
                <p style="color:#b30000;font-weight:700;margin:6px 0 0;">هذا التحكم محصور بحساب المدير الرئيسي فقط.</p>
            <?php endif; ?>
        </section>

        <?php if ($IS_LOCAL): ?>
            <section class="runtime-panel settings-section" dir="rtl" id="auto-sync-center" data-section-panel="auto-sync-center">
                <h3>المزامنة التلقائية من السحابة إلى المحلي</h3>
                <div class="runtime-note">
                    عند تفعيل هذا الخيار سيقوم النظام بمحاولة سحب أحدث البيانات من السحابة تلقائيا كل 5 أو 10 دقائق أثناء استخدام النظام محليا.
                </div>

                <?php if (!empty($autoSyncMessage)): ?>
                    <div class="notice <?php echo (strpos($autoSyncMessage, '❌') !== false) ? 'error' : 'success'; ?>">
                        <?php echo strip_tags($autoSyncMessage); ?>
                    </div>
                <?php endif; ?>

                <form class="runtime-form" method="post">
                    <input type="hidden" name="active_section" value="auto-sync-center">
                    <label>
                        <input type="checkbox" name="auto_pull_enabled" value="1" <?php echo $autoPullEnabled ? 'checked' : ''; ?>>
                        تفعيل المزامنة التلقائية
                    </label>

                    <label for="auto_pull_interval_minutes" style="display:block;margin:10px 0 6px;">الفاصل الزمني</label>
                    <select id="auto_pull_interval_minutes" name="auto_pull_interval_minutes" style="min-height:40px;border-radius:8px;border:1px solid var(--border);padding:6px 10px;">
                        <option value="5" <?php echo $autoPullInterval === 5 ? 'selected' : ''; ?>>كل 5 دقائق</option>
                        <option value="10" <?php echo $autoPullInterval === 10 ? 'selected' : ''; ?>>كل 10 دقائق</option>
                    </select>

                    <div class="runtime-note" style="margin-top:10px;">
                        آخر محاولة: <?php echo htmlspecialchars((string) $autoPullLastAttempt, ENT_QUOTES, 'UTF-8'); ?><br>
                        آخر نجاح: <?php echo htmlspecialchars((string) $autoPullLastSuccess, ENT_QUOTES, 'UTF-8'); ?><br>
                        الحالة الأخيرة: <?php echo htmlspecialchars((string) $autoPullLastStatus, ENT_QUOTES, 'UTF-8'); ?><br>
                        الملخص: <?php echo htmlspecialchars((string) $autoPullLastSummary, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <button type="submit" name="save_auto_sync" onclick="return confirm('تأكيد حفظ إعدادات المزامنة التلقائية؟')">حفظ إعدادات المزامنة التلقائية</button>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($IS_LOCAL): ?>
            <section class="emergency-guide settings-section" dir="rtl" id="emergency-guide" data-section-panel="emergency-guide">
                <h3>دليل الطوارئ للمزامنة (إجراء معتمد)</h3>

                <h4>أولا: عند حدوث الطارئ وانقطاع الاتصال</h4>
                <ol>
                    <li>اقفل الكتابة على النسخة السحابية من خيار وضع التشغيل أعلاه.</li>
                    <li>استمر بالعمل على النسخة المحلية فقط.</li>
                    <li>لا تستخدم رفع قاعدة كاملة أثناء الطارئ إلا للنسخ الاحتياطي فقط.</li>
                </ol>

                <h4>ثانيا: عند رجوع الاتصال</h4>
                <ol>
                    <li>شغل Sync From Online لسحب أحدث ما هو موجود على السحابة إلى المحلي.</li>
                    <li>شغل Safe Sync To Online لرفع بيانات المحلي بطريقة تمنع الكتابة فوق الأحدث.</li>
                    <li>ادخل إلى Manage Conflicts لحل أي تعارضات مفتوحة.</li>
                </ol>

                <h4>ثالثا: قبل العودة للوضع الطبيعي</h4>
                <ol>
                    <li>تأكد أن عدد التعارضات المفتوحة يساوي صفر.</li>
                    <li>بعدها فقط أعد تفعيل الكتابة على النسخة السحابية.</li>
                </ol>

                <h4>رابعا: صور الشبكية (uploads) عند العمل المحلي في الطوارئ</h4>
                <ol>
                    <li>ارفع الصور محليا بشكل طبيعي ولا توقف العمل بسبب الإنترنت.</li>
                    <li>بعد رجوع الاتصال وتشغيل Safe Sync To Online: انقل ملفات الصور نفسها من مجلد uploads المحلي إلى uploads في السيرفر السحابي بنفس المسار والاسم.</li>
                    <li>لا تعتمد على Git لنقل الصور لأن uploads مستثنى من الرفع.</li>
                    <li>إذا تم نقل قاعدة البيانات بدون نقل الصورة: سيظهر سجل الصورة لكن لن تفتح.</li>
                    <li>إذا تم نقل الصورة بدون سجل قاعدة البيانات: لن تظهر الصورة داخل ملف المريض.</li>
                </ol>

                <div class="cloud-note" style="margin-top:10px;">
                    فحص نهائي سريع: افتح المريض على النسخة السحابية، تأكد أن الصورة الجديدة تظهر بعد تحديث الصفحة، ثم احذف أي صورة اختبارية غير مطلوبة.
                </div>

                <?php if ($openConflicts > 0): ?>
                    <div class="conflict-badge">يوجد <?php echo $openConflicts; ?> تعارض مفتوح - يلزم الحل من <a href="sync_conflicts.php">Manage Conflicts</a></div>
                <?php else: ?>
                    <div class="ok-badge">لا توجد تعارضات مفتوحة حاليا.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        (function() {
            const initialSection = <?php echo json_encode($activeSection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const users = <?php echo json_encode(array_map(static function ($u) {
                                return [
                                    'id' => (int) ($u['id'] ?? 0),
                                    'username' => (string) ($u['username'] ?? ''),
                                ];
                            }, $usersList), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

            const sectionLinks = document.querySelectorAll('[data-section-link]');
            const sectionPanels = document.querySelectorAll('[data-section-panel]');

            function activateSection(sectionId) {
                let found = false;

                sectionPanels.forEach((panel) => {
                    const isActive = panel.getAttribute('data-section-panel') === sectionId;
                    panel.classList.toggle('active', isActive);
                    if (isActive) {
                        found = true;
                    }
                });

                sectionLinks.forEach((link) => {
                    const isActive = link.getAttribute('data-section-link') === sectionId;
                    link.classList.toggle('active', isActive);
                });

                return found;
            }

            sectionLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const sectionId = link.getAttribute('data-section-link') || '';
                    if (!activateSection(sectionId)) {
                        return;
                    }
                    window.location.hash = sectionId;
                });
            });

            const requestedSection = (window.location.hash || '').replace('#', '') || initialSection;
            if (!activateSection(requestedSection)) {
                activateSection('account-center');
            }

            const usernameIndex = new Map();
            users.forEach((u) => {
                const key = (u.username || '').trim().toLowerCase();
                if (key !== '') {
                    usernameIndex.set(key, Number(u.id || 0));
                }
            });

            function setHint(field, message, isError) {
                const wrapper = field.closest('div');
                if (!wrapper) {
                    return;
                }
                const hint = wrapper.querySelector('.username-hint');
                if (!hint) {
                    return;
                }
                hint.textContent = message;
                hint.classList.toggle('error', !!isError);
            }

            function validateUsernameField(field) {
                const form = field.closest('form');
                const currentId = Number(form?.dataset.userId || 0);
                const value = (field.value || '').trim().toLowerCase();

                if (value === '') {
                    field.setCustomValidity('يرجى إدخال اسم المستخدم');
                    setHint(field, 'يرجى إدخال اسم المستخدم', true);
                    return false;
                }

                const ownerId = Number(usernameIndex.get(value) || 0);
                const isDuplicate = ownerId > 0 && ownerId !== currentId;

                if (isDuplicate) {
                    field.setCustomValidity('اسم المستخدم مستخدم مسبقا');
                    setHint(field, 'اسم المستخدم مستخدم مسبقا', true);
                    return false;
                }

                field.setCustomValidity('');
                setHint(field, 'اسم المستخدم متاح', false);
                return true;
            }

            document.querySelectorAll('.js-username-field').forEach((field) => {
                field.addEventListener('input', () => {
                    validateUsernameField(field);
                });

                field.addEventListener('blur', () => {
                    validateUsernameField(field);
                });

                validateUsernameField(field);
            });

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const fields = form.querySelectorAll('.js-username-field');
                    let ok = true;
                    fields.forEach((field) => {
                        if (!validateUsernameField(field)) {
                            ok = false;
                        }
                    });

                    if (!ok) {
                        event.preventDefault();
                    }
                });
            });

            document.querySelectorAll('[data-toggle-pass]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-target');
                    const input = targetId ? document.getElementById(targetId) : null;
                    if (!input) {
                        return;
                    }

                    const nextType = input.type === 'password' ? 'text' : 'password';
                    input.type = nextType;
                    btn.textContent = nextType === 'password' ? 'إظهار' : 'إخفاء';
                });
            });
        })();
    </script>
</body>

</html>