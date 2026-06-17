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
$canManageWriteLock = clinic_can_manage_online_write_lock($con);
$canManageUsers = clinic_user_has_permission(['users']);
$staffMessage = '';

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

if (isset($_POST['register_staff'])) {
    if (!$canManageUsers) {
        $staffMessage = "<p style='color:red'>❌ لا تملك صلاحية إنشاء موظفين</p>";
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $rawPassword = (string) ($_POST['pass'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $permissions = clinic_normalize_permissions($_POST['permissions'] ?? []);

        if ($fullName === '' || $username === '' || $rawPassword === '' || $role === '') {
            $staffMessage = "<p style='color:red'>❌ يرجى تعبئة جميع الحقول المطلوبة</p>";
        } else {
            $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
            $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $stmt = $con->prepare("INSERT INTO users (full_name, username, pass, role, permissions_json) VALUES (?,?,?,?,?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $fullName, $username, $hashedPassword, $role, $permissionsJson);

                if ($stmt->execute()) {
                    $staffMessage = "<p style='color:green'>✔ تم إنشاء حساب الموظف بنجاح</p>";
                } else {
                    $staffMessage = ($con->errno === 1062)
                        ? "<p style='color:red'>❌ اسم المستخدم موجود مسبقاً</p>"
                        : "<p style='color:red'>❌ تعذر حفظ الحساب، حاول مرة أخرى</p>";
                }
            } else {
                $staffMessage = "<p style='color:red'>❌ تعذر تجهيز الاستعلام</p>";
            }
        }
    }
}

$isOnlineWriteLocked = clinic_is_online_write_locked($con, (bool) $IS_LOCAL);

$openConflicts = 0;
if ($IS_LOCAL) {
    $openConflictsRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM sync_conflicts WHERE resolution_status = 'open'"));
    $openConflicts = (int) ($openConflictsRow['total'] ?? 0);
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

        <?php if (!empty($modeMessage)): ?>
            <div class="notice <?php echo (strpos($modeMessage, '❌') !== false) ? 'error' : 'success'; ?>">
                <?php echo strip_tags($modeMessage); ?>
            </div>
        <?php endif; ?>

        <section class="actions-card">
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

        <section class="staff-card" id="staff-registration">
            <h3 class="section-title">إضافة موظف جديد</h3>
            <p class="section-subtitle">إنشاء حساب موظف مباشر من داخل الإعدادات مع تحديد الصلاحيات الخاصة به.</p>

            <?php if (!$canManageUsers): ?>
                <p class="cloud-note">هذه الميزة متاحة فقط للحسابات التي تملك صلاحية إدارة الحسابات.</p>
            <?php endif; ?>

            <form method="post" class="staff-grid">
                <div class="staff-field full">
                    <label for="staff_full_name">الاسم الكامل</label>
                    <input id="staff_full_name" type="text" name="full_name" placeholder="مثال: سارة أحمد" required>
                </div>

                <div class="staff-field">
                    <label for="staff_username">اسم المستخدم</label>
                    <input id="staff_username" type="text" name="username" placeholder="مثال: sara.a" required>
                </div>

                <div class="staff-field">
                    <label for="staff_pass">كلمة المرور</label>
                    <input id="staff_pass" type="password" name="pass" placeholder="كلمة المرور" required>
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
                    <button class="btn-submit btn-staff" type="submit" name="register_staff" <?php echo $canManageUsers ? '' : 'disabled'; ?>>حفظ الموظف</button>
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

        <section class="runtime-panel" dir="rtl">
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
            <section class="emergency-guide" dir="rtl">
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
</body>

</html>