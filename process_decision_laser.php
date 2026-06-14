<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $appointment_id = isset($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
    $appointment_date = $_GET['appointment_date'] ?? '';
    $page_title = 'قرار الليزر';
    $confirm_label = '⚡ تم الليزر';
    $confirm_action = 'add-laser.php';
    $accent_class = 'laser';
} else {
    // Redirect or handle the error if 'id' is not set
    header("Location: error_page.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>
    <link rel="stylesheet" href="assets/dark-mode.css">
</head>
<style>
    .decision-page {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        background:
            radial-gradient(circle at top right, rgba(14, 165, 233, 0.2), transparent 28%),
            radial-gradient(circle at bottom left, rgba(245, 158, 11, 0.12), transparent 30%),
            linear-gradient(135deg, #ecfeff, #f8fafc 45%, #dbeafe);
    }

    .decision-shell {
        width: min(100%, 720px);
    }

    .decision-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.18);
    }

    .decision-card::before {
        content: "";
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 8px;
    }

    .decision-card.laser::before {
        background: linear-gradient(90deg, #0891b2, #06b6d4, #f59e0b);
    }

    .decision-header {
        text-align: center;
        margin-bottom: 26px;
    }

    .decision-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: #cffafe;
        color: #155e75;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .decision-header h1 {
        margin: 0;
        color: #0f172a;
        font-size: 32px;
        font-weight: 800;
    }

    .decision-header p {
        margin: 10px 0 0;
        color: #475569;
        font-size: 16px;
        line-height: 1.8;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .action-form,
    .action-block {
        margin: 0;
    }

    .action-btn {
        width: 100%;
        border: 0;
        border-radius: 22px;
        padding: 22px 18px;
        cursor: pointer;
        font: inherit;
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    }

    .action-btn:hover {
        transform: translateY(-3px);
        filter: brightness(1.03);
    }

    .action-btn strong {
        font-size: 22px;
        font-weight: 800;
        color: #ffffff;
    }

    .action-btn span {
        font-size: 14px;
        line-height: 1.8;
        color: rgba(255, 255, 255, 0.92);
    }

    .action-btn.done {
        background: linear-gradient(135deg, #0284c7, #06b6d4);
    }

    .action-btn.dis {
        background: linear-gradient(135deg, #dc2626, #f97316);
    }

    .decision-meta {
        margin-top: 18px;
        display: flex;
        justify-content: center;
    }

    .decision-meta span {
        padding: 8px 12px;
        border-radius: 999px;
        background: #f8fafc;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #e2e8f0;
    }

    body[data-theme="dark"] .decision-page {
        background:
            radial-gradient(circle at top right, rgba(6, 182, 212, 0.22), transparent 28%),
            radial-gradient(circle at bottom left, rgba(245, 158, 11, 0.14), transparent 30%),
            linear-gradient(160deg, #020617, #0f172a 48%, #111827);
    }

    body[data-theme="dark"] .decision-card {
        background: rgba(15, 23, 42, 0.92);
        border-color: rgba(148, 163, 184, 0.18);
        box-shadow: 0 30px 80px rgba(2, 6, 23, 0.45);
    }

    body[data-theme="dark"] .decision-header h1 {
        color: #f8fafc;
    }

    body[data-theme="dark"] .decision-header p {
        color: #cbd5e1;
    }

    body[data-theme="dark"] .decision-badge {
        background: rgba(6, 182, 212, 0.16);
        color: #a5f3fc;
    }

    body[data-theme="dark"] .decision-meta span {
        background: rgba(15, 23, 42, 0.7);
        border-color: rgba(148, 163, 184, 0.16);
        color: #cbd5e1;
    }

    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(2, 6, 23, 0.72);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-backdrop.open {
        display: flex;
    }

    .modal-card {
        width: min(100%, 480px);
        background: #ffffff;
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 24px 55px rgba(0, 0, 0, 0.25);
        text-align: right;
    }

    .modal-card h4 {
        margin: 0 0 10px;
        color: #0f172a;
        font-size: 24px;
    }

    .modal-card p {
        margin: 0 0 12px;
        color: #475569;
        line-height: 1.8;
    }

    .modal-card textarea {
        width: 100%;
        min-height: 130px;
        padding: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        resize: vertical;
        font: inherit;
        box-sizing: border-box;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 14px;
    }

    .modal-actions button {
        margin-top: 0;
        border: 0;
        border-radius: 16px;
        padding: 14px 16px;
        font: inherit;
        font-weight: 700;
    }

    .cancel-btn {
        background: #64748b;
        color: #ffffff;
    }

    body[data-theme="dark"] .modal-card {
        background: #0f172a;
    }

    body[data-theme="dark"] .modal-card h4 {
        color: #f8fafc;
    }

    body[data-theme="dark"] .modal-card p {
        color: #cbd5e1;
    }

    body[data-theme="dark"] .modal-card textarea {
        background: #020617;
        border-color: #334155;
        color: #f8fafc;
    }

    @media (max-width: 640px) {
        .decision-card {
            padding: 22px;
        }

        .decision-header h1 {
            font-size: 26px;
        }

        .action-grid {
            grid-template-columns: 1fr;
        }

        .modal-actions {
            flex-direction: column;
        }
    }
</style>


<body class="decision-page">


    <div class="decision-shell">
        <div class="decision-card <?php echo htmlspecialchars($accent_class, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="decision-header">
                <div class="decision-badge">قرار الموعد</div>
                <h1><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>اختر الإجراء المناسب لهذا الموعد. في حالة عدم حضور المريض ستظهر نافذة لإضافة السبب وحفظه ضمن ملاحظات المريض.</p>
            </div>

            <div class="action-grid">
                <form class="action-form" action="<?php echo htmlspecialchars($confirm_action, ENT_QUOTES, 'UTF-8'); ?>" method="get">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                    <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="action-btn done" type="submit">
                        <strong><?php echo htmlspecialchars($confirm_label, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>تأكيد حضور المريض والانتقال إلى صفحة الإجراء لإكمال البيانات النهائية.</span>
                    </button>
                </form>

                <div class="action-block">
                    <button class="action-btn dis" type="button" id="openNoShowModal">
                        <strong>✖ لم يحضر المريض</strong>
                        <span>فتح نافذة لإدخال سبب عدم الحضور وحفظه مباشرةً داخل ملاحظات المريض.</span>
                    </button>
                </div>
            </div>

            <div class="decision-meta">
                <span>تاريخ الموعد: <?php echo htmlspecialchars($appointment_date !== '' ? $appointment_date : 'غير محدد', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <form action="discharge_laser.php" method="post" id="noShowForm">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-backdrop" id="noShowModal" aria-hidden="true">
                    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="noShowTitle">
                        <h4 id="noShowTitle">سبب عدم حضور المريض</h4>
                        <p>أدخل الملاحظة التي تريد ظهورها لاحقًا ضمن ملاحظات المريض.</p>
                        <textarea name="no_show_reason" id="noShowReason" placeholder="مثال: لم يحضر بسبب السفر أو تعذر التواصل معه" required></textarea>
                        <div class="modal-actions">
                            <button class="action-btn dis" type="submit" name="dis_btn">
                                <strong>حفظ السبب وتأكيد عدم الحضور</strong>
                                <span>سيتم تحديث حالة الموعد وإضافة السبب إلى ملاحظات المريض.</span>
                            </button>
                            <button class="cancel-btn" type="button" id="closeNoShowModal">إلغاء</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <script>
        const openNoShowModalButton = document.getElementById('openNoShowModal');
        const closeNoShowModalButton = document.getElementById('closeNoShowModal');
        const noShowModal = document.getElementById('noShowModal');
        const noShowReasonField = document.getElementById('noShowReason');

        function toggleNoShowModal(open) {
            if (!noShowModal) return;
            noShowModal.classList.toggle('open', open);
            noShowModal.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (open && noShowReasonField) {
                noShowReasonField.focus();
            }
        }

        if (openNoShowModalButton) {
            openNoShowModalButton.addEventListener('click', function() {
                toggleNoShowModal(true);
            });
        }

        if (closeNoShowModalButton) {
            closeNoShowModalButton.addEventListener('click', function() {
                toggleNoShowModal(false);
            });
        }

        if (noShowModal) {
            noShowModal.addEventListener('click', function(event) {
                if (event.target === noShowModal) {
                    toggleNoShowModal(false);
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                toggleNoShowModal(false);
            }
        });
    </script>



</body>

</html>