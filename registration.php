<?php
session_start();
include "config.php";
include_once "clinic_helpers.php";

if (isset($_SESSION['user_id'])) {
    header("Location: settings.php#staff-registration");
    exit;
}

echo "<html lang='ar' dir='rtl'><meta charset='utf-8'><body style='font-family:Tahoma,Arial,sans-serif;padding:24px'>";
echo "<h3>تسجيل الموظفين أصبح داخل صفحة الإعدادات</h3>";
echo "<p>سجّل الدخول أولًا بحساب يملك صلاحية إدارة الحسابات ثم افتح صفحة الإعدادات لإنشاء الموظف الجديد.</p>";
echo "<a href='log-in.php'>العودة لتسجيل الدخول</a>";
echo "</body></html>";
exit;

clinic_ensure_infrastructure($con);
clinic_ensure_column($con, 'users', 'permissions_json', 'LONGTEXT NULL');

$canManageUsers = isset($_SESSION['user_id']) && clinic_user_has_permission(['users']);

if (isset($_POST['register'])) {
    if (!$canManageUsers) {
        $msg = "يلزم تسجيل الدخول بحساب إداري لإنشاء موظفين";
    } else {
        $name = $_POST['full_name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $permissions = clinic_normalize_permissions($_POST['permissions'] ?? []);
        $permissionsJson = json_encode($permissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $con->prepare("INSERT INTO users (full_name, username, pass, role, permissions_json) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $username, $password, $role, $permissionsJson);

        if ($stmt->execute()) {
            $msg = "تم إنشاء حساب الموظف بنجاح";
        } else {
            $msg = ($con->errno === 1062) ? "اسم المستخدم موجود مسبقاً" : "تعذر حفظ الحساب، حاول مرة أخرى";
        }
    }
}

$permissionOptions = [
    'patients' => 'إدارة المرضى',
    'appointments' => 'إدارة المواعيد',
    'prescriptions' => 'الوصفات الطبية',
    'reports' => 'التقارير والإحصاءات',
    'settings' => 'الإعدادات العامة',
    'backup' => 'النسخ الاحتياطي والاستعادة',
    'sync' => 'المزامنة السحابية',
    'users' => 'إدارة الحسابات',
];

$existingUsers = [];
$usersResult = mysqli_query($con, "SELECT id, username FROM users");
while ($usersResult && ($u = mysqli_fetch_assoc($usersResult))) {
    $existingUsers[] = [
        'id' => (int) ($u['id'] ?? 0),
        'username' => (string) ($u['username'] ?? ''),
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إنشاء حساب موظف | عيادة الدكتور حيدر صباح الربيعي</title>
    <link rel="icon" type="image/svg+xml" href="assets/branding/favicon.svg">

    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif
        }

        :root {
            --bg: radial-gradient(circle at top right, rgba(13, 110, 253, .16), transparent 28%),
                radial-gradient(circle at bottom left, rgba(25, 135, 84, .14), transparent 26%),
                linear-gradient(145deg, #eef4fb 0%, #f8fbfe 55%, #eef6f3 100%);
            --card: #ffffff;
            --text: #122033;
            --muted: #607089;
            --border: #dbe5f0;
            --accent: #0d6efd;
            --accent-2: #198754;
        }

        body.dark {
            --bg: radial-gradient(circle at top right, rgba(13, 110, 253, .14), transparent 28%),
                radial-gradient(circle at bottom left, rgba(25, 135, 84, .12), transparent 26%),
                linear-gradient(145deg, #08111d 0%, #111b28 55%, #142635 100%);
            --card: #141c29;
            --text: #f4f7fb;
            --muted: #96a7bf;
            --border: #2a384b;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            padding: 28px 16px;
        }

        .layout {
            width: min(1160px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(320px, .9fr) minmax(320px, 1.1fr);
            gap: 24px;
            align-items: start;
        }

        .hero,
        .card {
            background: var(--card);
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 28px;
            box-shadow: 0 22px 70px rgba(0, 0, 0, .12);
        }

        .hero {
            padding: 32px;
            position: sticky;
            top: 24px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(13, 110, 253, .08);
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
        }

        .hero h1 {
            margin: 18px 0 12px;
            font-size: 38px;
            line-height: 1.15;
        }

        .hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.9;
        }

        .summary-grid {
            display: grid;
            gap: 12px;
            margin-top: 24px;
        }

        .summary-item {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(13, 110, 253, .06);
        }

        .summary-item strong {
            display: block;
            margin-bottom: 4px;
        }

        .card {
            padding: 30px;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .card .hint {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field,
        .full {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .full {
            grid-column: 1 / -1
        }

        label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            font-size: 14px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, .12);
        }

        .input-with-toggle {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-toggle input {
            padding-inline-end: 70px;
        }

        .pass-toggle {
            position: absolute;
            inset-inline-end: 8px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: rgba(13, 110, 253, .08);
            color: var(--text);
            min-height: 32px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .field-note {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #2b6cb0;
        }

        .field-note.error {
            color: #b91c1c;
        }

        .permissions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .perm {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px 14px 12px;
            background: rgba(13, 110, 253, .03);
        }

        .perm label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            color: var(--text);
            line-height: 1.7;
        }

        .perm input {
            width: 18px;
            height: 18px;
            margin-top: 3px;
            accent-color: var(--accent);
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 4px;
        }

        button,
        .secondary-link {
            border: none;
            border-radius: 14px;
            padding: 13px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        button {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #fff;
            flex: 1;
        }

        .secondary-link {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .message {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(25, 135, 84, .08);
            color: var(--text);
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .hero {
                position: static;
            }
        }

        @media (max-width: 680px) {
            body {
                padding: 16px 10px
            }

            .card,
            .hero {
                padding: 22px 18px;
                border-radius: 22px;
            }

            .grid,
            .permissions {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            button,
            .secondary-link {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/dark-mode.css">
</head>

<body>

    <div class="layout">
        <section class="hero">
            <div class="eyebrow">إنشاء حسابات الموظفين</div>
            <h1>نقطة تحكم واحدة لإضافة المستخدمين وتحديد صلاحياتهم</h1>
            <p>
                أنشئ حسابات للموظفين من هنا، واختر الدور المناسب ثم فعّل الصلاحيات التي يحتاجها كل شخص
                حسب مسؤولياته داخل العيادة.
            </p>

            <div class="summary-grid">
                <div class="summary-item"><strong>أدوار جاهزة</strong><span>أدمن، طبيب، استقبال، تمريض، محاسبة.</span></div>
                <div class="summary-item"><strong>صلاحيات مرنة</strong><span>تحكم منفصل في المرضى والمواعيد والتقارير والحسابات.</span></div>
                <div class="summary-item"><strong>حفظ تلقائي</strong><span>تخزين الصلاحيات في قاعدة البيانات مع ترحيل العمود عند الحاجة.</span></div>
            </div>

            <div class="actions" style="margin-top:24px;">
                <a class="secondary-link" href="log-in.php">العودة لتسجيل الدخول</a>
            </div>
        </section>

        <form method="post" class="card">
            <h2>إنشاء حساب موظف جديد</h2>
            <p class="hint">
                <?php if ($canManageUsers): ?>
                    املأ البيانات الأساسية ثم اختر الصلاحيات المسموح بها لهذا الحساب.
                <?php else: ?>
                    هذه الصفحة مفتوحة من زر البداية، لكن إنشاء الحساب يتطلب دخول حساب إداري.
                <?php endif; ?>
            </p>

            <?php if (!$canManageUsers): ?>
                <div class="message">سجّل الدخول أولاً بحساب مدير أو حساب لديه صلاحية إدارة الحسابات.</div>
            <?php endif; ?>

            <div class="grid">
                <div class="field full">
                    <label for="full_name">الاسم الكامل</label>
                    <input id="full_name" type="text" name="full_name" placeholder="مثال: سارة أحمد" required>
                </div>

                <div class="field">
                    <label for="username">اسم المستخدم</label>
                    <input id="username" class="js-username-field" type="text" name="username" placeholder="مثال: sara.a" required>
                    <div class="field-note" id="username_note">اسم المستخدم متاح</div>
                </div>

                <div class="field">
                    <label for="pass">كلمة المرور</label>
                    <div class="input-with-toggle">
                        <input id="pass" type="password" name="pass" placeholder="••••••••" required>
                        <button class="pass-toggle" type="button" data-toggle-pass data-target="pass">إظهار</button>
                    </div>
                </div>

                <div class="field full">
                    <label for="role">الدور</label>
                    <select id="role" name="role" required>
                        <option value="">اختر الدور</option>
                        <option value="admin">أدمن</option>
                        <option value="doctor">طبيب</option>
                        <option value="secretary">استقبال / سكرتارية</option>
                        <option value="nurse">تمريض</option>
                        <option value="accountant">محاسبة</option>
                    </select>
                </div>

                <div class="full">
                    <label>الصلاحيات</label>
                    <div class="permissions">
                        <?php foreach ($permissionOptions as $key => $label): ?>
                            <div class="perm">
                                <label>
                                    <input type="checkbox" name="permissions[]" value="<?= h($key) ?>">
                                    <span><?= h($label) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="full actions">
                    <button name="register" <?= $canManageUsers ? '' : 'disabled' ?>>حفظ الحساب</button>
                    <a class="secondary-link" href="log-in.php">إلغاء</a>
                </div>
            </div>

            <div class="message"><?= h($msg ?? '') ?></div>
        </form>
    </div>

    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
        }

        (function() {
            const users = <?php echo json_encode($existingUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const usernameIndex = new Set();
            users.forEach((u) => {
                const key = (u.username || '').trim().toLowerCase();
                if (key !== '') {
                    usernameIndex.add(key);
                }
            });

            const usernameField = document.getElementById('username');
            const note = document.getElementById('username_note');

            function validateUsername() {
                if (!usernameField || !note) {
                    return true;
                }

                const value = (usernameField.value || '').trim().toLowerCase();
                if (value === '') {
                    usernameField.setCustomValidity('يرجى إدخال اسم المستخدم');
                    note.textContent = 'يرجى إدخال اسم المستخدم';
                    note.classList.add('error');
                    return false;
                }

                if (usernameIndex.has(value)) {
                    usernameField.setCustomValidity('اسم المستخدم مستخدم مسبقا');
                    note.textContent = 'اسم المستخدم مستخدم مسبقا';
                    note.classList.add('error');
                    return false;
                }

                usernameField.setCustomValidity('');
                note.textContent = 'اسم المستخدم متاح';
                note.classList.remove('error');
                return true;
            }

            if (usernameField) {
                usernameField.addEventListener('input', validateUsername);
                usernameField.addEventListener('blur', validateUsername);
                validateUsername();
            }

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!validateUsername()) {
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