<?php

function clinic_load_appointment_patient(mysqli $con, int $patientId): ?array
{
    if ($patientId <= 0) {
        return null;
    }
    $stmt = mysqli_prepare($con, 'SELECT id, full_name, phone_no, phone_no_alt FROM add_patient WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $patientId);
    mysqli_stmt_execute($stmt);
    $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $patient;
}

function clinic_render_appointment_page(array $config): void
{
    $patient = $config['patient'];
    $patientId = (int) $patient['id'];
    $accent = (string) ($config['accent'] ?? '#2563eb');
    $typeField = (string) $config['type_field'];
    $typeLabel = (string) $config['type_label'];
    $types = (array) $config['types'];
    $readiness = (array) ($config['readiness'] ?? []);
    $flash = $config['flash'] ?? null;
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($config['title']) ?> - <?= h($patient['full_name']) ?></title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <link rel="stylesheet" href="assets/clinic-ui.css">
    <link rel="stylesheet" href="assets/appointment-form.css">
    <script src="assets/theme.js" defer></script>
    <script src="assets/clinic-actions.js" defer></script>
</head>
<body class="clinic-polished" style="--appointment-accent:<?= h($accent) ?>;--appointment-accent-soft:<?= h($config['accent_soft'] ?? 'rgba(37,99,235,.12)') ?>">
<main class="appointment-page">
    <div class="appointment-topbar">
        <a class="appointment-back" href="patient-file.php?id=<?= $patientId ?>">→ العودة إلى ملف المريض</a>
        <span class="appointment-kind"><?= h($config['kind']) ?></span>
    </div>
    <?php if ($flash): ?><div class="appointment-flash" role="status"><?= h($flash['message'] ?? '') ?></div><?php endif; ?>
    <section class="appointment-card">
        <header class="appointment-header">
            <div><h1 class="appointment-title"><?= h($config['title']) ?></h1><p class="appointment-subtitle">راجع بيانات المريض وحدد تفاصيل الموعد قبل الحفظ.</p></div>
            <div class="patient-chip"><strong><?= h($patient['full_name']) ?></strong><span>رقم الملف: <?= $patientId ?></span></div>
        </header>
        <form class="appointment-form" action="<?= h($config['action']) ?>?id=<?= $patientId ?>" method="post" data-prevent-double-submit>
            <?= clinic_csrf_input() ?>
            <div class="appointment-grid">
                <div class="appointment-field full"><label for="name">اسم المريض</label><input id="name" name="name" value="<?= h($patient['full_name']) ?>" readonly></div>
                <div class="appointment-field"><label for="<?= h($typeField) ?>"><?= h($typeLabel) ?> <span class="required-mark">*</span></label><select id="<?= h($typeField) ?>" name="<?= h($typeField) ?>" required><option value="">اختر</option><?php foreach ($types as $type): ?><option value="<?= h($type) ?>"><?= h($type) ?></option><?php endforeach; ?></select></div>
                <div class="appointment-field"><label for="eye">العين <span class="required-mark">*</span></label><select id="eye" name="eye" required><option value="">اختر العين</option><option value="OD">OD - اليمنى</option><option value="OS">OS - اليسرى</option><option value="OU">OU - كلتا العينين</option></select></div>
                <div class="appointment-field"><label for="phone">رقم الهاتف <span class="required-mark">*</span></label><input type="tel" id="phone" name="phone" value="<?= h($patient['phone_no']) ?>" inputmode="numeric" pattern="[0-9]+" placeholder="07xxxxxxxxx" required></div>
                <div class="appointment-field"><label for="phone_alt">رقم هاتف بديل</label><input type="tel" id="phone_alt" name="phone_alt" value="<?= h($patient['phone_no_alt']) ?>" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="appointment-field full"><label for="date"><?= h($config['date_label']) ?> <span class="required-mark">*</span></label><input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required></div>
                <div class="appointment-field full"><label for="notes">ملاحظات وتعليمات</label><textarea id="notes" name="notes" placeholder="أي تحضير، فحوصات أو ملاحظات مهمة قبل الإجراء"></textarea></div>
                <?php if ($readiness): ?><fieldset class="readiness"><legend>قائمة الجاهزية</legend><div class="readiness-grid"><?php foreach ($readiness as $key => $label): ?><label><input type="checkbox" name="readiness[<?= h($key) ?>]" value="1"> <?= h($label) ?></label><?php endforeach; ?></div></fieldset><?php endif; ?>
            </div>
            <div class="appointment-actions"><a class="appointment-btn secondary" href="patient-file.php?id=<?= $patientId ?>">إلغاء</a><button class="appointment-btn primary" type="submit" name="<?= h($config['submit_name']) ?>" value="1" data-loading-text="جاري حفظ الموعد...">حفظ الموعد</button></div>
        </form>
    </section>
</main>
</body>
</html>
<?php
}
