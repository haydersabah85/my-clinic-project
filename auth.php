<?php
session_start();

include_once 'config.php';
include_once 'clinic_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: log-in.php");
    exit;
}

$currentScript = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$requiredPermissions = clinic_required_permissions_for_script($currentScript);
if (!empty($requiredPermissions)) {
    clinic_require_permissions($requiredPermissions);
}

if (isset($con) && isset($IS_LOCAL)) {
    clinic_enforce_runtime_write_policy($con, (bool) $IS_LOCAL);
    clinic_ensure_surgery_iol_power_column($con);
    clinic_auto_pull_tick($con, (bool) $IS_LOCAL);
}

if (!defined('CLINIC_SECRETARY_NOTIFIER_ATTACHED')) {
    define('CLINIC_SECRETARY_NOTIFIER_ATTACHED', true);

    $currentRole = strtolower((string) ($_SESSION['role'] ?? ''));
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $scriptName = strtolower((string) $currentScript);

    if ($currentRole === 'secretary' && $currentUserId > 0 && $scriptName !== 'staff-messages-poll.php') {
        $notifierSnippet = '<script>(function(){if(window.__clinicSecretaryNotifierStarted){return;}window.__clinicSecretaryNotifierStarted=true;var pollUrl="staff-messages-poll.php";var inboxUrl="staff-messages.php";var userId=' . $currentUserId . ';var storageKey="clinic_last_notified_msg_id_"+userId;var lastNotifiedId=parseInt(localStorage.getItem(storageKey)||"0",10)||0;var toastHostId="clinic-secretary-toast-host";function ensureHost(){var host=document.getElementById(toastHostId);if(host){return host;}host=document.createElement("div");host.id=toastHostId;host.style.position="fixed";host.style.left="18px";host.style.top="18px";host.style.zIndex="999999";host.style.display="grid";host.style.gap="8px";host.style.maxWidth="360px";document.body.appendChild(host);return host;}function showToast(text){if(!document.body){return;}var host=ensureHost();var item=document.createElement("div");item.style.background="#0f172a";item.style.color="#fff";item.style.border="1px solid rgba(148,163,184,.45)";item.style.borderRadius="10px";item.style.boxShadow="0 12px 28px rgba(15,23,42,.25)";item.style.padding="10px 12px";item.style.fontFamily="Cairo,Segoe UI,Tahoma,Arial,sans-serif";item.style.fontSize="13px";item.style.cursor="pointer";item.textContent=text;item.title="فتح صندوق الرسائل";item.onclick=function(){window.location.href=inboxUrl;};host.appendChild(item);setTimeout(function(){item.style.opacity="0";item.style.transform="translateY(-6px)";item.style.transition="all .25s ease";},4200);setTimeout(function(){if(item.parentNode){item.parentNode.removeChild(item);}},4500);}function playBeep(){try{var Ctx=window.AudioContext||window.webkitAudioContext;if(!Ctx){return;}var ctx=new Ctx();var osc=ctx.createOscillator();var gain=ctx.createGain();osc.type="sine";osc.frequency.setValueAtTime(880,ctx.currentTime);gain.gain.setValueAtTime(0.0001,ctx.currentTime);gain.gain.exponentialRampToValueAtTime(0.18,ctx.currentTime+0.02);gain.gain.exponentialRampToValueAtTime(0.0001,ctx.currentTime+0.22);osc.connect(gain);gain.connect(ctx.destination);osc.start();osc.stop(ctx.currentTime+0.24);}catch(err){}}function showNativeNotification(title,body){if(!("Notification" in window)){return;}if(Notification.permission==="granted"){try{new Notification(title,{body:body});}catch(err){}}}async function pollMessages(){try{var resp=await fetch(pollUrl+"?t="+Date.now(),{credentials:"same-origin",cache:"no-store"});if(!resp.ok){return;}var data=await resp.json();if(!data||!data.success||!data.latest){return;}var latestId=parseInt(data.latest.id||0,10)||0;if(latestId<=0||latestId<=lastNotifiedId){return;}lastNotifiedId=latestId;localStorage.setItem(storageKey,String(lastNotifiedId));var sender=(data.latest.sender_name||"المستخدم").trim();var msg=(data.latest.message_text||"").replace(/\s+/g," ").trim();var preview=msg.length>120?msg.slice(0,120)+"...":msg;var toastText="رسالة جديدة من "+sender+(preview?": "+preview:"");showToast(toastText);playBeep();showNativeNotification("رسالة داخلية جديدة",toastText);}catch(err){}}if("Notification" in window&&Notification.permission==="default"){setTimeout(function(){Notification.requestPermission().catch(function(){});},1500);}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",function(){pollMessages();});}else{pollMessages();}setInterval(pollMessages,20000);window.addEventListener("focus",pollMessages);document.addEventListener("visibilitychange",function(){if(!document.hidden){pollMessages();}});})();</script>';

        ob_start(static function ($buffer) use ($notifierSnippet) {
            if ($buffer === '' || stripos($buffer, '<html') === false || stripos($buffer, '<body') === false) {
                return $buffer;
            }

            if (strpos($buffer, '__clinicSecretaryNotifierStarted') !== false) {
                return $buffer;
            }

            $result = preg_replace('/<\/body>/i', $notifierSnippet . '</body>', $buffer, 1);
            return is_string($result) ? $result : $buffer;
        });
    }
}
