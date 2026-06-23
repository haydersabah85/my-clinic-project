<?php
include 'auth.php';
include 'config.php';
include_once 'clinic_helpers.php';

clinic_ensure_infrastructure($con);

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentRole = strtolower((string) ($_SESSION['role'] ?? ''));
$isSecretary = ($currentRole === 'secretary');
$isAdmin = ($currentRole === 'admin');
$canSendMessages = true;

$flashMessage = '';
$flashType = 'ok';

$allowedRecipients = [];
if ($isSecretary) {
    $senderStmt = mysqli_prepare($con, "
        SELECT
            u.id,
            u.full_name,
            u.username,
            MAX(sm.created_at) AS last_sent_at
        FROM staff_messages sm
        INNER JOIN users u ON u.id = sm.sender_user_id
        WHERE sm.recipient_user_id = ?
        GROUP BY u.id, u.full_name, u.username
        ORDER BY last_sent_at DESC
    ");
    if ($senderStmt) {
        mysqli_stmt_bind_param($senderStmt, 'i', $currentUserId);
        mysqli_stmt_execute($senderStmt);
        $senderResult = mysqli_stmt_get_result($senderStmt);
        while ($senderResult && ($row = mysqli_fetch_assoc($senderResult))) {
            $allowedRecipients[] = $row;
        }
        mysqli_stmt_close($senderStmt);
    }
} else {
    $secResult = mysqli_query($con, "SELECT id, full_name, username FROM users WHERE role = 'secretary' ORDER BY full_name ASC");
    while ($secResult && ($row = mysqli_fetch_assoc($secResult))) {
        $allowedRecipients[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!$canSendMessages) {
        $flashMessage = 'هذا الحساب غير مخول لإرسال الرسائل.';
        $flashType = 'error';
    } else {
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $messageText = trim((string) ($_POST['message_text'] ?? ''));

        if ($recipientId <= 0 || $messageText === '') {
            $flashMessage = 'يرجى اختيار المستلم وكتابة الرسالة.';
            $flashType = 'error';
        } else {
            $isValidRecipient = false;
            foreach ($allowedRecipients as $recipient) {
                if ((int) $recipient['id'] === $recipientId) {
                    $isValidRecipient = true;
                    break;
                }
            }

            if (!$isValidRecipient) {
                $flashMessage = 'المستخدم المختار غير متاح للإرسال من هذا الحساب.';
                $flashType = 'error';
            } else {
                $insertStmt = mysqli_prepare($con, "
                    INSERT INTO staff_messages (sender_user_id, recipient_user_id, message_text, is_read, created_at)
                    VALUES (?, ?, ?, 0, NOW())
                ");

                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, 'iis', $currentUserId, $recipientId, $messageText);
                    if (mysqli_stmt_execute($insertStmt)) {
                        $flashMessage = 'تم إرسال الرسالة بنجاح.';
                        $flashType = 'ok';
                    } else {
                        $flashMessage = 'فشل إرسال الرسالة. حاول مرة أخرى.';
                        $flashType = 'error';
                    }
                    mysqli_stmt_close($insertStmt);
                } else {
                    $flashMessage = 'تعذر تجهيز إرسال الرسالة.';
                    $flashType = 'error';
                }
            }
        }
    }
}

$inbox = [];
$inboxStmt = mysqli_prepare($con, "
    SELECT
        sm.id,
        sm.message_text,
        sm.is_read,
        sm.created_at,
        sm.read_at,
        sm.sender_user_id,
        sender.full_name AS sender_name,
        sender.username AS sender_username
    FROM staff_messages sm
    INNER JOIN users sender ON sender.id = sm.sender_user_id
    WHERE sm.recipient_user_id = ?
    ORDER BY sm.created_at DESC
    LIMIT 100
");
if ($inboxStmt) {
    mysqli_stmt_bind_param($inboxStmt, 'i', $currentUserId);
    mysqli_stmt_execute($inboxStmt);
    $inboxResult = mysqli_stmt_get_result($inboxStmt);
    while ($inboxResult && ($row = mysqli_fetch_assoc($inboxResult))) {
        $inbox[] = $row;
    }
    mysqli_stmt_close($inboxStmt);
}

$sent = [];
$sentStmt = mysqli_prepare($con, "
    SELECT
        sm.id,
        sm.message_text,
        sm.is_read,
        sm.created_at,
        sm.read_at,
        recipient.full_name AS recipient_name,
        recipient.username AS recipient_username
    FROM staff_messages sm
    INNER JOIN users recipient ON recipient.id = sm.recipient_user_id
    WHERE sm.sender_user_id = ?
    ORDER BY sm.created_at DESC
    LIMIT 100
");
if ($sentStmt) {
    mysqli_stmt_bind_param($sentStmt, 'i', $currentUserId);
    mysqli_stmt_execute($sentStmt);
    $sentResult = mysqli_stmt_get_result($sentStmt);
    while ($sentResult && ($row = mysqli_fetch_assoc($sentResult))) {
        $sent[] = $row;
    }
    mysqli_stmt_close($sentStmt);
}

if (!empty($inbox)) {
    $markReadStmt = mysqli_prepare($con, "UPDATE staff_messages SET is_read = 1, read_at = NOW() WHERE recipient_user_id = ? AND is_read = 0");
    if ($markReadStmt) {
        mysqli_stmt_bind_param($markReadStmt, 'i', $currentUserId);
        mysqli_stmt_execute($markReadStmt);
        mysqli_stmt_close($markReadStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسائل داخلية</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #64748b;
            --primary: #0f766e;
            --border: #dbe4ef;
            --ok: #166534;
            --ok-bg: #ecfdf3;
            --err: #991b1b;
            --err-bg: #fef2f2;
            --shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            --unread: #ffedd5;
        }

        body {
            margin: 0;
            font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
        }

        .container {
            max-width: 1150px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
        }

        .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: var(--shadow);
        }

        .head h1 {
            margin: 0;
            font-size: 25px;
        }

        .head a {
            text-decoration: none;
            color: #fff;
            background: var(--primary);
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 700;
        }

        .notice {
            padding: 12px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-weight: 700;
        }

        .notice.ok {
            color: var(--ok);
            background: var(--ok-bg);
            border-color: #86efac;
        }

        .notice.error {
            color: var(--err);
            background: var(--err-bg);
            border-color: #fecaca;
        }

        .notify-tools {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .notify-tools button {
            border: none;
            background: linear-gradient(130deg, #0f766e, #2563eb);
            color: #fff;
            font-weight: 800;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
        }

        .notify-status {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card h2 {
            margin: 0;
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 20px;
        }

        .card-body {
            padding: 14px;
        }

        form {
            display: grid;
            gap: 10px;
        }

        select,
        textarea,
        button {
            font: inherit;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 10px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            border: none;
            background: linear-gradient(130deg, #0f766e, #2563eb);
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }

        .msg-list {
            display: grid;
            gap: 10px;
        }

        .msg-item {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            background: #fff;
        }

        .msg-item.unread {
            background: var(--unread);
            border-color: #fdba74;
        }

        .meta {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .body {
            white-space: pre-wrap;
            line-height: 1.7;
            font-size: 14px;
        }

        .empty {
            color: var(--muted);
            font-weight: 700;
            text-align: center;
            padding: 16px;
            border: 1px dashed var(--border);
            border-radius: 10px;
        }

        .reply-form {
            margin-top: 8px;
            display: grid;
            gap: 8px;
        }

        .reply-form textarea {
            min-height: 70px;
        }

        .reply-form button {
            width: fit-content;
            min-width: 120px;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="head">
            <h1>📩 رسائل داخلية</h1>
            <a href="dashboard.php">العودة للداشبورد</a>
        </div>

        <?php if ($flashMessage !== ''): ?>
            <div class="notice <?php echo h($flashType); ?>"><?php echo h($flashMessage); ?></div>
        <?php endif; ?>

        <?php if ($isSecretary): ?>
            <section class="notify-tools">
                <div>
                    <strong>تنبيهات المتصفح</strong>
                    <div class="notify-status" id="notificationStatus">جاري التحقق من حالة الإذن...</div>
                </div>
                <button type="button" id="enableNotificationsBtn">تفعيل التنبيهات</button>
            </section>
        <?php endif; ?>

        <div class="grid">
            <section class="card">
                <h2><?php echo $isSecretary ? 'إرسال رد' : 'إرسال توصية للسكرتيرة'; ?></h2>
                <div class="card-body">
                    <?php if (empty($allowedRecipients)): ?>
                        <?php if ($isSecretary): ?>
                            <div class="empty">لا يوجد مرسلون سابقون للرد عليهم حالياً.</div>
                        <?php else: ?>
                            <div class="empty">لا يوجد أي حساب سكرتيرة حالياً.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post">
                            <label for="recipient_id"><?php echo $isSecretary ? 'المُرسل (للرد)' : 'السكرتيرة'; ?></label>
                            <select name="recipient_id" id="recipient_id" required>
                                <?php foreach ($allowedRecipients as $recipient): ?>
                                    <option value="<?php echo (int) $recipient['id']; ?>">
                                        <?php echo h($recipient['full_name']); ?> (<?php echo h($recipient['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="message_text"><?php echo $isSecretary ? 'نص الرد' : 'التوصية / الأمر'; ?></label>
                            <textarea name="message_text" id="message_text" required placeholder="<?php echo $isSecretary ? 'اكتبي الرد هنا...' : 'مثال: الرجاء عدم إدخال مريض جديد قبل إنهاء قائمة اليوم...'; ?>"></textarea>

                            <button type="submit" name="send_message" value="1"><?php echo $isSecretary ? 'إرسال الرد' : 'إرسال الرسالة'; ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card">
                <h2>الوارد</h2>
                <div class="card-body">
                    <?php if (empty($inbox)): ?>
                        <div class="empty">لا توجد رسائل واردة.</div>
                    <?php else: ?>
                        <div class="msg-list">
                            <?php foreach ($inbox as $msg): ?>
                                <article class="msg-item <?php echo ((int) $msg['is_read'] === 0) ? 'unread' : ''; ?>">
                                    <div class="meta">
                                        من: <?php echo h($msg['sender_name'] ?: $msg['sender_username']); ?>
                                        | <?php echo h($msg['created_at']); ?>
                                    </div>
                                    <div class="body"><?php echo h($msg['message_text']); ?></div>
                                    <?php if ($isSecretary): ?>
                                        <form class="reply-form" method="post">
                                            <input type="hidden" name="recipient_id" value="<?php echo (int) $msg['sender_user_id']; ?>">
                                            <textarea name="message_text" required placeholder="رد سريع على هذه الرسالة..."></textarea>
                                            <button type="submit" name="send_message" value="1">رد سريع</button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="card">
            <h2>الرسائل المرسلة</h2>
            <div class="card-body">
                <?php if (empty($sent)): ?>
                    <div class="empty">لا توجد رسائل مرسلة.</div>
                <?php else: ?>
                    <div class="msg-list">
                        <?php foreach ($sent as $msg): ?>
                            <article class="msg-item">
                                <div class="meta">
                                    إلى: <?php echo h($msg['recipient_name'] ?: $msg['recipient_username']); ?>
                                    | <?php echo h($msg['created_at']); ?>
                                    | الحالة: <?php echo ((int) $msg['is_read'] === 1) ? 'تمت القراءة' : 'غير مقروءة'; ?>
                                </div>
                                <div class="body"><?php echo h($msg['message_text']); ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php if ($isSecretary): ?>
        <script>
            (function() {
                const statusEl = document.getElementById('notificationStatus');
                const enableBtn = document.getElementById('enableNotificationsBtn');

                if (!statusEl || !enableBtn) {
                    return;
                }

                function updateStatus() {
                    if (!('Notification' in window)) {
                        statusEl.textContent = 'هذا المتصفح لا يدعم إشعارات النظام.';
                        enableBtn.disabled = true;
                        enableBtn.style.opacity = '.7';
                        return;
                    }

                    if (Notification.permission === 'granted') {
                        statusEl.textContent = 'الإشعارات مفعلة.';
                        enableBtn.textContent = 'تم التفعيل';
                        enableBtn.disabled = true;
                        enableBtn.style.opacity = '.7';
                        return;
                    }

                    if (Notification.permission === 'denied') {
                        statusEl.textContent = 'الإشعارات مرفوضة. يرجى السماح بها من إعدادات المتصفح.';
                        enableBtn.textContent = 'إعادة المحاولة';
                        enableBtn.disabled = false;
                        enableBtn.style.opacity = '1';
                        return;
                    }

                    statusEl.textContent = 'الإشعارات غير مفعلة بعد. اضغطي زر التفعيل.';
                    enableBtn.textContent = 'تفعيل التنبيهات';
                    enableBtn.disabled = false;
                    enableBtn.style.opacity = '1';
                }

                enableBtn.addEventListener('click', async function() {
                    if (!('Notification' in window)) {
                        updateStatus();
                        return;
                    }

                    try {
                        const permission = await Notification.requestPermission();
                        updateStatus();

                        if (permission === 'granted') {
                            new Notification('تم تفعيل التنبيهات', {
                                body: 'ستصلك الآن إشعارات الرسائل الجديدة داخل النظام.'
                            });
                        }
                    } catch (err) {
                        statusEl.textContent = 'حدث خطأ أثناء طلب إذن الإشعارات.';
                    }
                });

                updateStatus();
            })();
        </script>
    <?php endif; ?>
</body>

</html>