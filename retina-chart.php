<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

clinic_ensure_retina_drawings($con);

$patient_id = (int)($_GET['patient_id'] ?? $_GET['id'] ?? 0);
$drawing_id = (int)($_GET['drawing_id'] ?? 0);

if ($patient_id <= 0) {
    die('Invalid patient.');
}

$patientWhere = clinic_active_patient_where($con, 'add_patient');
$patientStmt = mysqli_prepare($con, "SELECT * FROM add_patient WHERE id = ? AND $patientWhere");
mysqli_stmt_bind_param($patientStmt, "i", $patient_id);
mysqli_stmt_execute($patientStmt);
$patientResult = mysqli_stmt_get_result($patientStmt);
$patient = mysqli_fetch_assoc($patientResult);

if (!$patient) {
    die('Patient not found.');
}

$drawing = [
    'id' => 0,
    'eye' => $_GET['eye'] ?? 'OD',
    'drawing_date' => $_GET['date'] ?? date('Y-m-d'),
    'title' => '',
    'notes' => '',
    'drawing_data' => '',
    'drawing_image' => '',
];

if ($drawing_id > 0) {
    $drawingStmt = mysqli_prepare($con, "SELECT * FROM retina_drawings WHERE id = ? AND patient_id = ?");
    mysqli_stmt_bind_param($drawingStmt, "ii", $drawing_id, $patient_id);
    mysqli_stmt_execute($drawingStmt);
    $drawingResult = mysqli_stmt_get_result($drawingStmt);
    if ($row = mysqli_fetch_assoc($drawingResult)) {
        $drawing = $row;
    }
}

$allowedEyes = ['OD', 'OS', 'OU'];
if (!in_array($drawing['eye'], $allowedEyes, true)) {
    $drawing['eye'] = 'OD';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$drawing['drawing_date'])) {
    $drawing['drawing_date'] = date('Y-m-d');
}

$history = [];
$historyStmt = mysqli_prepare($con, "
    SELECT id, eye, drawing_date, title, updated_at
    FROM retina_drawings
    WHERE patient_id = ?
    ORDER BY drawing_date DESC, id DESC
    LIMIT 18
");
mysqli_stmt_bind_param($historyStmt, "i", $patient_id);
mysqli_stmt_execute($historyStmt);
$historyResult = mysqli_stmt_get_result($historyStmt);
while ($historyRow = mysqli_fetch_assoc($historyResult)) {
    $history[] = $historyRow;
}

$initialDrawingData = $drawing['drawing_data'] ?: '{"strokes":[]}';
$patientName = $patient['name'] ?? $patient['full_name'] ?? $patient['patient_name'] ?? ('Patient #' . $patient_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسم الشبكية - <?= h($patientName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #eef5f3;
            --panel: #ffffff;
            --ink: #102033;
            --muted: #607087;
            --border: #d7e4df;
            --teal: #0f766e;
            --blue: #2563eb;
            --red: #dc2626;
            --yellow: #f59e0b;
            --shadow: 0 16px 45px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.78), rgba(238, 245, 243, 0.92)),
                repeating-linear-gradient(90deg, rgba(15, 118, 110, 0.05) 0 1px, transparent 1px 90px);
            color: var(--ink);
            font-family: 'Cairo', Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .page {
            width: min(1500px, calc(100% - 28px));
            margin: 18px auto 28px;
        }

        .topbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 16px;
        }

        .identity {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: var(--shadow);
        }

        .identity h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .identity p {
            margin: 5px 0 0;
            color: var(--muted);
            font-weight: 700;
        }

        .top-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn,
        .icon-btn,
        .tool-btn,
        select,
        input,
        textarea {
            font-family: inherit;
        }

        .btn,
        .icon-btn,
        .tool-btn {
            border: 0;
            cursor: pointer;
            font-weight: 900;
            text-decoration: none;
            transition: transform 0.15s ease, filter 0.15s ease, background 0.15s ease;
        }

        .btn:hover,
        .icon-btn:hover,
        .tool-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .btn {
            min-height: 42px;
            border-radius: 8px;
            padding: 9px 14px;
            color: #ffffff;
            background: var(--teal);
        }

        .btn.secondary {
            background: #334155;
        }

        .btn.blue {
            background: var(--blue);
        }

        .workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 16px;
            align-items: start;
        }

        .drawing-shell,
        .side-panel {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .toolbar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid var(--border);
            direction: ltr;
        }

        .tool-group {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
            padding: 8px;
            background: #f8fbfb;
            border: 1px solid #e1ece9;
            border-radius: 8px;
        }

        .tool-group label {
            font-size: 12px;
            font-weight: 900;
            color: #41576b;
        }

        .icon-btn,
        .tool-btn {
            min-width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e7eef5;
            color: #172033;
            padding: 0 10px;
        }

        .tool-btn.active {
            background: var(--teal);
            color: #ffffff;
        }

        .stamp-btn {
            min-width: auto;
            font-size: 12px;
        }

        input[type="color"] {
            width: 40px;
            height: 38px;
            border: 0;
            padding: 0;
            background: transparent;
        }

        input[type="range"] {
            width: 94px;
        }

        .canvas-wrap {
            padding: 14px;
            background: #f7faf8;
            direction: ltr;
        }

        .canvas-frame {
            position: relative;
            width: 100%;
            border: 1px solid #c9d8d3;
            border-radius: 8px;
            overflow: hidden;
            background: #fffdf9;
        }

        canvas {
            display: block;
            width: 100%;
            height: auto;
            touch-action: none;
            cursor: crosshair;
        }

        .side-panel {
            padding: 14px;
            position: sticky;
            top: 14px;
        }

        .form-grid {
            display: grid;
            gap: 10px;
        }

        .field {
            display: grid;
            gap: 5px;
        }

        .field label {
            font-size: 12px;
            font-weight: 900;
            color: #43566b;
        }

        select,
        input[type="date"],
        input[type="text"],
        textarea {
            width: 100%;
            border: 1px solid #d5e2de;
            border-radius: 8px;
            padding: 10px 12px;
            background: #f8fbfb;
            color: #102033;
            font-size: 14px;
            font-weight: 700;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
            line-height: 1.7;
        }

        .save-note {
            color: #0f766e;
            font-size: 13px;
            font-weight: 900;
            min-height: 20px;
        }

        .history {
            margin-top: 18px;
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        .history h2 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .history-list {
            display: grid;
            gap: 8px;
            max-height: 320px;
            overflow: auto;
        }

        .history-item {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px;
            align-items: center;
            padding: 9px;
            border: 1px solid #dce8e4;
            border-radius: 8px;
            background: #fbfdfd;
            text-decoration: none;
        }

        .eye-chip {
            display: inline-flex;
            min-width: 42px;
            height: 30px;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 900;
        }

        .history-item strong,
        .history-item span {
            display: block;
        }

        .history-item span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .workspace,
            .topbar {
                grid-template-columns: 1fr;
            }

            .toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .side-panel {
                position: static;
            }
        }

        @media (max-width: 680px) {
            .page {
                width: min(100% - 16px, 1500px);
            }

            .toolbar {
                grid-template-columns: 1fr;
            }

            .identity h1 {
                font-size: 20px;
            }
        }

        @media print {
            body {
                background: #ffffff;
            }

            .top-actions,
            .toolbar,
            .side-panel {
                display: none;
            }

            .page,
            .workspace {
                width: 100%;
                margin: 0;
                display: block;
            }

            .drawing-shell,
            .identity {
                box-shadow: none;
                border: 0;
            }
        }
    </style>
<script src="assets/lang.js" data-clinic-lang defer></script>
</head>

<body>
    <div class="page">
        <header class="topbar">
            <div class="identity">
                <h1>رسم الشبكية - <?= h($patientName) ?></h1>
                <p>رقم المريض: <?= h($patient_id) ?> | التاريخ: <?= h($drawing['drawing_date']) ?> | العين: <?= h($drawing['eye']) ?></p>
            </div>
            <div class="top-actions">
                <a class="btn secondary" href="patient-file.php?id=<?= h($patient_id) ?>">ملف المريض</a>
                <button type="button" class="btn blue" id="printBtn">طباعة</button>
                <button type="button" class="btn secondary" id="downloadBtn">تنزيل PNG</button>
            </div>
        </header>

        <?php if (isset($_GET['saved'])): ?>
            <p class="save-note">تم حفظ رسم الشبكية.</p>
        <?php endif; ?>

        <main class="workspace">
            <section class="drawing-shell">
                <div class="toolbar" aria-label="Retina drawing tools">
                    <div class="tool-group">
                        <label>الأداة</label>
                        <button type="button" class="tool-btn active" data-tool="pen" title="قلم">Pen</button>
                        <button type="button" class="tool-btn" data-tool="eraser" title="ممحاة">Erase</button>
                        <button type="button" class="tool-btn" data-tool="line" title="خط">Line</button>
                        <button type="button" class="tool-btn" data-tool="circle" title="دائرة">Circle</button>
                        <button type="button" class="tool-btn" data-tool="arrow" title="سهم">Arrow</button>
                        <button type="button" class="tool-btn" data-tool="text" title="كتابة">Text</button>
                    </div>
                    <div class="tool-group">
                        <label>اللون</label>
                        <input type="color" id="colorPicker" value="#dc2626" title="لون الرسم">
                        <label>الحجم</label>
                        <input type="range" id="sizePicker" min="2" max="36" value="5">
                    </div>
                    <div class="tool-group">
                        <label>علامات</label>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="heme">Heme</button>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="exudate">Exudate</button>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="laser">Laser</button>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="tear">Tear</button>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="rd">RD</button>
                        <button type="button" class="tool-btn stamp-btn" data-stamp="edema">Edema</button>
                    </div>
                    <div class="tool-group">
                        <label>تحكم</label>
                        <button type="button" class="icon-btn" id="undoBtn" title="تراجع">Undo</button>
                        <button type="button" class="icon-btn" id="redoBtn" title="إعادة">Redo</button>
                        <button type="button" class="icon-btn" id="clearBtn" title="مسح الرسم">Clear</button>
                    </div>
                </div>

                <div class="canvas-wrap">
                    <div class="canvas-frame">
                        <canvas id="retinaCanvas" width="1200" height="860"></canvas>
                    </div>
                </div>
            </section>

            <aside class="side-panel">
                <form class="form-grid" id="drawingForm" action="save-retina-drawing.php" method="POST">
                    <input type="hidden" name="patient_id" value="<?= h($patient_id) ?>">
                    <input type="hidden" name="drawing_id" value="<?= h($drawing['id'] ?? 0) ?>">
                    <input type="hidden" name="drawing_data" id="drawingData">
                    <input type="hidden" name="drawing_image" id="drawingImage">

                    <div class="field">
                        <label for="eyeSelect">العين</label>
                        <select id="eyeSelect" name="eye">
                            <option value="OD" <?= $drawing['eye'] === 'OD' ? 'selected' : '' ?>>OD - Right eye</option>
                            <option value="OS" <?= $drawing['eye'] === 'OS' ? 'selected' : '' ?>>OS - Left eye</option>
                            <option value="OU" <?= $drawing['eye'] === 'OU' ? 'selected' : '' ?>>OU - Both eyes</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="drawingDate">تاريخ الرسم</label>
                        <input type="date" id="drawingDate" name="drawing_date" value="<?= h($drawing['drawing_date']) ?>">
                    </div>

                    <div class="field">
                        <label for="title">العنوان</label>
                        <input type="text" id="title" name="title" value="<?= h($drawing['title'] ?? '') ?>" placeholder="مثلا: PDR with PRP marks">
                    </div>

                    <div class="field">
                        <label for="notes">ملاحظات الرسم</label>
                        <textarea id="notes" name="notes" placeholder="اكتب ملخص التغيرات المشاهدة..."><?= h($drawing['notes'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn" id="saveBtn">حفظ الرسم</button>
                </form>

                <section class="history">
                    <h2>رسومات سابقة</h2>
                    <div class="history-list">
                        <?php if (empty($history)): ?>
                            <p class="save-note">لا توجد رسومات محفوظة بعد.</p>
                        <?php endif; ?>
                        <?php foreach ($history as $item): ?>
                            <a class="history-item" href="retina-chart.php?patient_id=<?= h($patient_id) ?>&drawing_id=<?= h($item['id']) ?>">
                                <span class="eye-chip"><?= h($item['eye']) ?></span>
                                <span>
                                    <strong><?= h($item['title'] ?: 'Retina chart') ?></strong>
                                    <span><?= h($item['drawing_date']) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>
        </main>
    </div>

    <script>
        const canvas = document.getElementById('retinaCanvas');
        const ctx = canvas.getContext('2d');
        const eyeSelect = document.getElementById('eyeSelect');
        const colorPicker = document.getElementById('colorPicker');
        const sizePicker = document.getElementById('sizePicker');
        const drawingDataField = document.getElementById('drawingData');
        const drawingImageField = document.getElementById('drawingImage');
        const form = document.getElementById('drawingForm');
        const initialData = <?= json_encode($initialDrawingData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        let state = { strokes: [] };
        let redoStack = [];
        let currentTool = 'pen';
        let currentStamp = '';
        let drawing = false;
        let draft = null;
        let activeStroke = null;

        try {
            const parsed = JSON.parse(initialData || '{"strokes":[]}');
            if (parsed && Array.isArray(parsed.strokes)) state = parsed;
        } catch (error) {
            state = { strokes: [] };
        }

        function pointFromEvent(event) {
            const rect = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * (canvas.width / rect.width),
                y: (event.clientY - rect.top) * (canvas.height / rect.height)
            };
        }

        function drawTemplate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#fffdf7';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const eye = eyeSelect.value;
            if (eye === 'OU') {
                drawEyeTemplate(330, 430, 285, 'OD');
                drawEyeTemplate(870, 430, 285, 'OS');
                return;
            }
            drawEyeTemplate(600, 430, 360, eye);
        }

        function drawEyeTemplate(cx, cy, r, eye) {
            ctx.save();
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#213547';
            ctx.fillStyle = '#fff9ee';
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            const rings = [0.33, 0.58, 0.78];
            rings.forEach((ratio, index) => {
                ctx.beginPath();
                ctx.setLineDash(index === 1 ? [8, 10] : []);
                ctx.strokeStyle = index === 0 ? '#7c8da3' : '#b3c0cf';
                ctx.arc(cx, cy, r * ratio, 0, Math.PI * 2);
                ctx.stroke();
            });
            ctx.setLineDash([]);

            for (let i = 0; i < 12; i++) {
                const angle = (-90 + i * 30) * Math.PI / 180;
                const inner = r * 0.12;
                const outer = r * 0.98;
                ctx.beginPath();
                ctx.strokeStyle = i % 3 === 0 ? '#93a4b8' : '#d3dde8';
                ctx.lineWidth = i % 3 === 0 ? 1.5 : 1;
                ctx.moveTo(cx + Math.cos(angle) * inner, cy + Math.sin(angle) * inner);
                ctx.lineTo(cx + Math.cos(angle) * outer, cy + Math.sin(angle) * outer);
                ctx.stroke();

                const labelRadius = r * 1.08;
                ctx.fillStyle = '#44566c';
                ctx.font = '700 18px Cairo, Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(String(i === 0 ? 12 : i), cx + Math.cos(angle) * labelRadius, cy + Math.sin(angle) * labelRadius);
            }

            ctx.fillStyle = '#5d7086';
            ctx.font = '800 16px Cairo, Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Posterior pole', cx, cy - r * 0.35);
            ctx.fillText('Equator', cx, cy - r * 0.60);
            ctx.fillText('Ora / periphery', cx, cy - r * 0.82);

            const discX = eye === 'OD' ? cx - r * 0.34 : cx + r * 0.34;
            const maculaX = eye === 'OD' ? cx + r * 0.10 : cx - r * 0.10;
            drawDisc(discX, cy, r * 0.105);
            drawMacula(maculaX, cy + r * 0.01, r * 0.065);
            drawVessels(discX, cy, r, eye);

            ctx.fillStyle = '#102033';
            ctx.font = '900 26px Cairo, Arial';
            ctx.textAlign = 'center';
            ctx.fillText(eye, cx, cy + r + 48);
            ctx.restore();
        }

        function drawDisc(x, y, r) {
            const grad = ctx.createRadialGradient(x - r * 0.2, y - r * 0.2, r * 0.15, x, y, r);
            grad.addColorStop(0, '#fff5d6');
            grad.addColorStop(1, '#f2b66d');
            ctx.fillStyle = grad;
            ctx.strokeStyle = '#b86f28';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.ellipse(x, y, r * 0.88, r * 1.12, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
            ctx.strokeStyle = '#c58a4a';
            ctx.beginPath();
            ctx.ellipse(x, y, r * 0.42, r * 0.55, 0, 0, Math.PI * 2);
            ctx.stroke();
        }

        function drawMacula(x, y, r) {
            ctx.fillStyle = 'rgba(110, 58, 44, 0.18)';
            ctx.strokeStyle = '#7f4d3f';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.ellipse(x, y, r * 1.55, r, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();
            ctx.fillStyle = '#5f332d';
            ctx.beginPath();
            ctx.arc(x, y, r * 0.24, 0, Math.PI * 2);
            ctx.fill();
        }

        function drawVessels(discX, discY, r, eye) {
            const dir = eye === 'OD' ? 1 : -1;
            const branches = [
                [-0.18, -0.72, '#b91c1c', 5], [0.18, -0.68, '#1d4ed8', 4],
                [0.28, -0.34, '#b91c1c', 4], [0.34, 0.34, '#1d4ed8', 4],
                [-0.14, 0.72, '#b91c1c', 5], [0.12, 0.66, '#1d4ed8', 4]
            ];

            branches.forEach(([dx, dy, color, width], index) => {
                ctx.strokeStyle = color;
                ctx.lineWidth = width;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(discX, discY);
                ctx.bezierCurveTo(
                    discX + dir * r * 0.18,
                    discY + r * dy * 0.20,
                    discX + dir * r * (0.30 + Math.abs(dx)),
                    discY + r * dy * 0.55,
                    discX + dir * r * (0.52 + Math.abs(dx) * 0.35),
                    discY + r * dy
                );
                ctx.stroke();

                if (index < 4) {
                    ctx.lineWidth = Math.max(2, width - 2);
                    ctx.beginPath();
                    ctx.moveTo(discX + dir * r * 0.20, discY + r * dy * 0.25);
                    ctx.quadraticCurveTo(
                        discX + dir * r * 0.42,
                        discY + r * dy * 0.25,
                        discX + dir * r * 0.62,
                        discY + r * (dy * 0.48 + (dy > 0 ? 0.12 : -0.12))
                    );
                    ctx.stroke();
                }
            });
        }

        function drawStroke(stroke) {
            ctx.save();
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = stroke.color;
            ctx.fillStyle = stroke.color;
            ctx.lineWidth = stroke.size;

            if (stroke.tool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
            }

            if ((stroke.tool === 'pen' || stroke.tool === 'eraser') && stroke.points.length) {
                ctx.beginPath();
                ctx.moveTo(stroke.points[0].x, stroke.points[0].y);
                stroke.points.slice(1).forEach(point => ctx.lineTo(point.x, point.y));
                ctx.stroke();
            }

            if (stroke.tool === 'line' || stroke.tool === 'arrow') {
                drawLine(stroke.start, stroke.end, stroke.tool === 'arrow');
            }

            if (stroke.tool === 'circle') {
                const rx = stroke.end.x - stroke.start.x;
                const ry = stroke.end.y - stroke.start.y;
                ctx.beginPath();
                ctx.ellipse(stroke.start.x, stroke.start.y, Math.abs(rx), Math.abs(ry), 0, 0, Math.PI * 2);
                ctx.stroke();
            }

            if (stroke.tool === 'text') {
                ctx.font = `${Math.max(16, stroke.size * 4)}px Cairo, Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(stroke.text, stroke.x, stroke.y);
            }

            if (stroke.tool === 'stamp') {
                drawStamp(stroke.stamp, stroke.x, stroke.y, stroke.size);
            }

            ctx.restore();
        }

        function drawLine(start, end, arrow) {
            ctx.beginPath();
            ctx.moveTo(start.x, start.y);
            ctx.lineTo(end.x, end.y);
            ctx.stroke();
            if (!arrow) return;
            const angle = Math.atan2(end.y - start.y, end.x - start.x);
            const len = 18 + ctx.lineWidth;
            ctx.beginPath();
            ctx.moveTo(end.x, end.y);
            ctx.lineTo(end.x - len * Math.cos(angle - Math.PI / 6), end.y - len * Math.sin(angle - Math.PI / 6));
            ctx.moveTo(end.x, end.y);
            ctx.lineTo(end.x - len * Math.cos(angle + Math.PI / 6), end.y - len * Math.sin(angle + Math.PI / 6));
            ctx.stroke();
        }

        function drawStamp(stamp, x, y, size) {
            const s = Math.max(10, size * 3);
            ctx.save();
            ctx.lineWidth = Math.max(2, size / 2);
            if (stamp === 'heme') {
                ctx.fillStyle = '#b91c1c';
                ctx.beginPath();
                ctx.ellipse(x, y, s * 0.7, s * 0.45, -0.35, 0, Math.PI * 2);
                ctx.fill();
            } else if (stamp === 'exudate') {
                ctx.fillStyle = '#facc15';
                for (let i = 0; i < 8; i++) {
                    const a = i * Math.PI / 4;
                    ctx.beginPath();
                    ctx.ellipse(x + Math.cos(a) * s * 0.25, y + Math.sin(a) * s * 0.25, s * 0.20, s * 0.38, a, 0, Math.PI * 2);
                    ctx.fill();
                }
            } else if (stamp === 'laser') {
                ctx.strokeStyle = '#111827';
                ctx.fillStyle = '#6b7280';
                ctx.beginPath();
                ctx.arc(x, y, s * 0.35, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();
            } else if (stamp === 'tear') {
                ctx.strokeStyle = '#7c2d12';
                ctx.fillStyle = 'rgba(249, 115, 22, 0.24)';
                ctx.beginPath();
                ctx.moveTo(x, y - s);
                ctx.lineTo(x + s * 0.75, y + s * 0.55);
                ctx.lineTo(x - s * 0.75, y + s * 0.55);
                ctx.closePath();
                ctx.fill();
                ctx.stroke();
            } else if (stamp === 'rd') {
                ctx.strokeStyle = '#7e22ce';
                ctx.fillStyle = 'rgba(168, 85, 247, 0.18)';
                ctx.beginPath();
                ctx.moveTo(x - s, y + s * 0.3);
                ctx.bezierCurveTo(x - s * 0.45, y - s, x + s * 0.45, y + s, x + s, y - s * 0.2);
                ctx.lineTo(x + s, y + s);
                ctx.lineTo(x - s, y + s);
                ctx.closePath();
                ctx.fill();
                ctx.stroke();
            } else if (stamp === 'edema') {
                ctx.strokeStyle = '#0891b2';
                ctx.fillStyle = 'rgba(34, 211, 238, 0.16)';
                ctx.beginPath();
                ctx.arc(x, y, s * 0.9, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();
                ctx.beginPath();
                ctx.arc(x, y, s * 0.45, 0, Math.PI * 2);
                ctx.stroke();
            }
            ctx.restore();
        }

        function render() {
            drawTemplate();
            state.strokes.forEach(drawStroke);
            if (draft) drawStroke(draft);
        }

        function pushStroke(stroke) {
            state.strokes.push(stroke);
            redoStack = [];
            render();
        }

        canvas.addEventListener('pointerdown', event => {
            canvas.setPointerCapture(event.pointerId);
            const point = pointFromEvent(event);
            if (currentStamp) {
                pushStroke({ tool: 'stamp', stamp: currentStamp, x: point.x, y: point.y, size: Number(sizePicker.value) });
                return;
            }
            if (currentTool === 'text') {
                const text = prompt('اكتب النص الذي تريد وضعه على الرسم:');
                if (text) {
                    pushStroke({ tool: 'text', x: point.x, y: point.y, text, color: colorPicker.value, size: Number(sizePicker.value) });
                }
                return;
            }
            drawing = true;
            activeStroke = {
                tool: currentTool,
                color: colorPicker.value,
                size: Number(sizePicker.value),
                points: [point],
                start: point,
                end: point
            };
            draft = activeStroke;
            render();
        });

        canvas.addEventListener('pointermove', event => {
            if (!drawing || !activeStroke) return;
            const point = pointFromEvent(event);
            if (currentTool === 'pen' || currentTool === 'eraser') {
                activeStroke.points.push(point);
            } else {
                activeStroke.end = point;
            }
            draft = activeStroke;
            render();
        });

        function finishStroke() {
            if (!drawing || !activeStroke) return;
            drawing = false;
            state.strokes.push(activeStroke);
            activeStroke = null;
            draft = null;
            redoStack = [];
            render();
        }

        canvas.addEventListener('pointerup', finishStroke);
        canvas.addEventListener('pointercancel', finishStroke);

        document.querySelectorAll('[data-tool]').forEach(button => {
            button.addEventListener('click', () => {
                currentTool = button.dataset.tool;
                currentStamp = '';
                document.querySelectorAll('[data-tool], [data-stamp]').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });
        });

        document.querySelectorAll('[data-stamp]').forEach(button => {
            button.addEventListener('click', () => {
                currentStamp = button.dataset.stamp;
                document.querySelectorAll('[data-tool], [data-stamp]').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
            });
        });

        eyeSelect.addEventListener('change', render);

        document.getElementById('undoBtn').addEventListener('click', () => {
            const item = state.strokes.pop();
            if (item) redoStack.push(item);
            render();
        });

        document.getElementById('redoBtn').addEventListener('click', () => {
            const item = redoStack.pop();
            if (item) state.strokes.push(item);
            render();
        });

        document.getElementById('clearBtn').addEventListener('click', () => {
            if (!confirm('هل تريد مسح الرسم الحالي؟')) return;
            redoStack = state.strokes.slice().reverse();
            state.strokes = [];
            render();
        });

        document.getElementById('downloadBtn').addEventListener('click', () => {
            render();
            const link = document.createElement('a');
            link.download = `retina-<?= h($patient_id) ?>-${eyeSelect.value}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        });

        document.getElementById('printBtn').addEventListener('click', () => window.print());

        form.addEventListener('submit', () => {
            render();
            drawingDataField.value = JSON.stringify(state);
            drawingImageField.value = canvas.toDataURL('image/png');
        });

        render();
    </script>
</body>

</html>
