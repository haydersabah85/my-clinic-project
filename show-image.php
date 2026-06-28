<?php

include 'config.php';
include 'auth.php';

$patient_id = intval($_GET['id']);



$query = "SELECT *
          FROM patient_images 
          WHERE patient_id = $patient_id 
          ORDER BY date_added DESC";

$result = mysqli_query($con, $query);
$images = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
}
?>



<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معرض صور المريض</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
</head>

<style>
    :root {
        --bg: #f4f7fb;
        --bg-soft: #e9eef7;
        --surface: #ffffff;
        --surface-2: #f7f9fc;
        --text: #13253b;
        --muted: #5f7288;
        --line: #d9e2ec;
        --accent: #0ea5a0;
        --accent-2: #0369a1;
        --danger: #dc2626;
        --shadow: 0 14px 35px rgba(19, 37, 59, 0.12);
    }

    @media (prefers-color-scheme: dark) {
        body:not([data-theme="light"]) {
            --bg: #0b1320;
            --bg-soft: #101b2c;
            --surface: #111b2e;
            --surface-2: #0f1829;
            --text: #e8f1ff;
            --muted: #9eb0c9;
            --line: #20324b;
            --accent: #22d3cc;
            --accent-2: #38bdf8;
            --danger: #f87171;
            --shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
        }
    }

    body[data-theme="dark"] {
        --bg: #0b1320;
        --bg-soft: #101b2c;
        --surface: #111b2e;
        --surface-2: #0f1829;
        --text: #e8f1ff;
        --muted: #9eb0c9;
        --line: #20324b;
        --accent: #22d3cc;
        --accent-2: #38bdf8;
        --danger: #f87171;
        --shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: "Tajawal", "Segoe UI", sans-serif;
        background:
            radial-gradient(circle at 85% -10%, rgba(14, 165, 160, 0.2), transparent 45%),
            radial-gradient(circle at 10% 110%, rgba(3, 105, 161, 0.18), transparent 35%),
            var(--bg);
        color: var(--text);
        min-height: 100vh;
    }

    .gallery-container {
        max-width: 1320px;
        margin: 24px auto 36px;
        padding: 0 18px;
    }

    .gallery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .gallery-title {
        margin: 0;
        font-size: clamp(24px, 3.3vw, 34px);
        font-weight: 700;
        letter-spacing: 0.2px;
    }

    .gallery-subtitle {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 14px;
    }

    .counter-badge {
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: var(--surface);
        box-shadow: var(--shadow);
        color: var(--muted);
        font-size: 13px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid var(--line);
        text-decoration: none;
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        color: #fff;
        font-size: 13px;
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .back-link:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    .image-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }

    .image-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .image-card:hover {
        transform: translateY(-4px);
        border-color: color-mix(in srgb, var(--accent) 35%, var(--line));
    }

    .image-trigger {
        border: 0;
        background: none;
        width: 100%;
        padding: 0;
        cursor: zoom-in;
        display: block;
    }

    .image-card img {
        width: 100%;
        height: 190px;
        object-fit: cover;
        display: block;
    }

    .image-info {
        padding: 10px 12px 14px;
    }

    .image-note {
        margin: 0 0 6px;
        color: var(--text);
        font-size: 14px;
        font-weight: 500;
        line-height: 1.5;
        min-height: 2.8em;
    }

    .image-date {
        margin: 0;
        color: var(--muted);
        font-size: 12px;
    }

    .empty-state {
        border: 1px dashed var(--line);
        border-radius: 16px;
        background: var(--surface-2);
        text-align: center;
        color: var(--muted);
        padding: 32px 16px;
        font-size: 15px;
    }

    .modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1200;
        background: rgba(4, 10, 20, 0.88);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        padding: 14px;
    }

    .modal.is-open {
        display: flex;
    }

    .modal-shell {
        width: min(1280px, 100%);
        max-height: 94vh;
        display: grid;
        grid-template-rows: auto 1fr auto;
        gap: 12px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 20px;
        box-shadow: var(--shadow);
        padding: 12px;
    }

    .modal-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .modal-meta {
        color: var(--muted);
        font-size: 13px;
    }

    .modal-viewer {
        position: relative;
        background: var(--surface-2);
        border: 1px solid var(--line);
        border-radius: 14px;
        overflow: hidden;
        min-height: 320px;
        max-height: 68vh;
        touch-action: none;
    }

    .modal-stage {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .modal-stage img {
        max-width: 100%;
        max-height: 100%;
        user-select: none;
        -webkit-user-drag: none;
        transform-origin: center center;
        transition: transform 0.08s linear;
        cursor: grab;
    }

    .modal-stage img.dragging {
        cursor: grabbing;
    }

    .nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 1px solid var(--line);
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        font-size: 19px;
        cursor: pointer;
        display: grid;
        place-items: center;
    }

    .nav-btn:hover {
        background: rgba(14, 165, 160, 0.85);
    }

    .nav-prev {
        right: 10px;
    }

    .nav-next {
        left: 10px;
    }

    .modal-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .modal-actions button,
    .modal-actions a {
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--text);
        border-radius: 10px;
        padding: 8px 12px;
        cursor: pointer;
        text-decoration: none;
        font-size: 13px;
        font-family: inherit;
    }

    .modal-actions button:hover,
    .modal-actions a:hover {
        border-color: color-mix(in srgb, var(--accent) 45%, var(--line));
    }

    .primary-btn {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        border: none !important;
        color: #fff !important;
    }

    .danger-btn {
        background: color-mix(in srgb, var(--danger) 18%, var(--surface));
        border-color: color-mix(in srgb, var(--danger) 45%, var(--line)) !important;
        color: var(--danger) !important;
    }

    .hint {
        color: var(--muted);
        font-size: 12px;
    }

    @media (max-width: 700px) {
        .gallery-container {
            margin-top: 16px;
        }

        .modal-shell {
            padding: 10px;
            border-radius: 14px;
        }

        .modal-viewer {
            min-height: 250px;
            max-height: 56vh;
        }

        .image-card img {
            height: 165px;
        }

        .nav-btn {
            width: 36px;
            height: 36px;
        }
    }
</style>

<body>

    <div class="gallery-container">
        <div class="gallery-header">
            <div>
                <h1 class="gallery-title">معرض صور المريض</h1>
                <p class="gallery-subtitle">انقر على أي صورة لعرضها بالتفصيل والتحكم الكامل بها.</p>
            </div>
            <div class="header-actions">
                <a class="back-link" href="patient-file.php?id=<?= (int) $patient_id ?>">الرجوع إلى ملف المريض</a>
                <div class="counter-badge">عدد الصور: <?= count($images) ?></div>
            </div>
        </div>

        <?php if (empty($images)): ?>
            <div class="empty-state">لا توجد صور محفوظة لهذا المريض حالياً.</div>
        <?php else: ?>
            <div class="image-gallery" id="imageGallery">
                <?php foreach ($images as $index => $row): ?>
                    <?php
                    $note = trim((string) ($row['notes'] ?? ''));
                    $note = $note !== '' ? $note : 'بدون ملاحظات';
                    $dateText = '';
                    if (!empty($row['date_added'])) {
                        $time = strtotime((string) $row['date_added']);
                        $dateText = $time ? date('Y-m-d H:i', $time) : (string) $row['date_added'];
                    }
                    ?>
                    <article class="image-card">
                        <button
                            type="button"
                            class="image-trigger"
                            data-index="<?= (int) $index ?>"
                            data-image-id="<?= (int) $row['id'] ?>"
                            data-src="<?= h($row['image_path']) ?>"
                            data-note="<?= h($note) ?>"
                            data-date="<?= h($dateText) ?>"
                            aria-label="فتح الصورة">
                            <img src="<?= h($row['image_path']) ?>" alt="صورة المريض <?= (int) $index + 1 ?>">
                        </button>
                        <div class="image-info">
                            <p class="image-note"><?= h($note) ?></p>
                            <p class="image-date"><?= h($dateText) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal" id="imageModal" aria-hidden="true">
        <div class="modal-shell" role="dialog" aria-modal="true" aria-label="عارض الصور">
            <div class="modal-top">
                <div class="modal-meta" id="modalMeta">-</div>
                <div class="hint">الاختصارات: Esc إغلاق | ← → للتنقل | عجلة الماوس للتكبير/التصغير</div>
            </div>

            <div class="modal-viewer" id="viewerWrap">
                <button type="button" class="nav-btn nav-prev" id="prevBtn" aria-label="السابق">&#10095;</button>
                <button type="button" class="nav-btn nav-next" id="nextBtn" aria-label="التالي">&#10094;</button>
                <div class="modal-stage" id="viewerStage">
                    <img id="modalImage" alt="عرض الصورة">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" id="zoomInBtn">تكبير +</button>
                <button type="button" id="zoomOutBtn">تصغير -</button>
                <button type="button" id="resetBtn">إعادة ضبط</button>
                <button type="button" id="rotateBtn">تدوير 90&deg;</button>
                <button type="button" id="fullscreenBtn">ملء الشاشة</button>
                <a id="downloadLink" class="primary-btn" download>تحميل</a>
                <button type="button" class="danger-btn" id="deleteBtn">حذف</button>
                <button type="button" id="closeBtn">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
        const triggers = Array.from(document.querySelectorAll('.image-trigger'));
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalMeta = document.getElementById('modalMeta');
        const downloadLink = document.getElementById('downloadLink');
        const viewerWrap = document.getElementById('viewerWrap');
        const viewerStage = document.getElementById('viewerStage');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const resetBtn = document.getElementById('resetBtn');
        const rotateBtn = document.getElementById('rotateBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const closeBtn = document.getElementById('closeBtn');

        const images = triggers.map((btn) => ({
            id: parseInt(btn.dataset.imageId || '0', 10),
            src: btn.dataset.src || '',
            note: btn.dataset.note || '',
            date: btn.dataset.date || ''
        }));

        let activeIndex = -1;
        let currentImageId = null;
        let scale = 1;
        let rotation = 0;
        let translateX = 0;
        let translateY = 0;
        let dragStartX = 0;
        let dragStartY = 0;
        let pointerDragging = false;

        function applyTransform() {
            modalImage.style.transform = `translate(${translateX}px, ${translateY}px) rotate(${rotation}deg) scale(${scale})`;
        }

        function resetTransform() {
            scale = 1;
            rotation = 0;
            translateX = 0;
            translateY = 0;
            applyTransform();
        }

        function setImage(index) {
            if (!images.length) return;
            activeIndex = (index + images.length) % images.length;
            const item = images[activeIndex];
            modalImage.src = item.src;
            modalImage.alt = item.note || `صورة رقم ${activeIndex + 1}`;
            modalMeta.textContent = `${item.date || 'بدون تاريخ'} | ${item.note || 'بدون ملاحظات'} (${activeIndex + 1}/${images.length})`;
            downloadLink.href = item.src;
            currentImageId = item.id;
            resetTransform();
        }

        function openModal(index) {
            setImage(index);
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function updateZoom(delta) {
            scale = Math.min(5, Math.max(0.3, scale + delta));
            applyTransform();
        }

        function rotateImage() {
            rotation = (rotation + 90) % 360;
            applyTransform();
        }

        function deleteImage() {
            if (!currentImageId) return;
            if (confirm('هل أنت متأكد من حذف الصورة؟')) {
                window.location.href = 'delete-image.php?id=' + encodeURIComponent(currentImageId);
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                modal.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        }

        triggers.forEach((btn) => {
            btn.addEventListener('click', () => {
                const index = parseInt(btn.dataset.index || '0', 10);
                openModal(index);
            });
        });

        nextBtn?.addEventListener('click', () => setImage(activeIndex + 1));
        prevBtn?.addEventListener('click', () => setImage(activeIndex - 1));

        zoomInBtn?.addEventListener('click', () => updateZoom(0.2));
        zoomOutBtn?.addEventListener('click', () => updateZoom(-0.2));
        resetBtn?.addEventListener('click', resetTransform);
        rotateBtn?.addEventListener('click', rotateImage);
        fullscreenBtn?.addEventListener('click', toggleFullscreen);
        deleteBtn?.addEventListener('click', deleteImage);
        closeBtn?.addEventListener('click', closeModal);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal();
        });

        document.addEventListener('keydown', (event) => {
            if (!modal.classList.contains('is-open')) return;
            if (event.key === 'Escape') closeModal();
            if (event.key === 'ArrowRight') setImage(activeIndex - 1);
            if (event.key === 'ArrowLeft') setImage(activeIndex + 1);
            if (event.key === '+') updateZoom(0.1);
            if (event.key === '-') updateZoom(-0.1);
        });

        viewerStage?.addEventListener('wheel', (event) => {
            if (!modal.classList.contains('is-open')) return;
            event.preventDefault();
            updateZoom(event.deltaY < 0 ? 0.1 : -0.1);
        }, {
            passive: false
        });

        modalImage.addEventListener('mousedown', (event) => {
            if (!modal.classList.contains('is-open')) return;
            pointerDragging = true;
            dragStartX = event.clientX - translateX;
            dragStartY = event.clientY - translateY;
            modalImage.classList.add('dragging');
        });

        window.addEventListener('mousemove', (event) => {
            if (!pointerDragging) return;
            translateX = event.clientX - dragStartX;
            translateY = event.clientY - dragStartY;
            applyTransform();
        });

        window.addEventListener('mouseup', () => {
            pointerDragging = false;
            modalImage.classList.remove('dragging');
        });

        let touchStartDistance = null;

        function getTouchDistance(touches) {
            const [a, b] = touches;
            return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
        }

        viewerWrap?.addEventListener('touchstart', (event) => {
            if (event.touches.length === 2) {
                touchStartDistance = getTouchDistance(event.touches);
            }
        }, {
            passive: true
        });

        viewerWrap?.addEventListener('touchmove', (event) => {
            if (event.touches.length === 2 && touchStartDistance) {
                const currentDistance = getTouchDistance(event.touches);
                const delta = (currentDistance - touchStartDistance) / 220;
                scale = Math.min(5, Math.max(0.3, scale + delta));
                touchStartDistance = currentDistance;
                applyTransform();
            }
        }, {
            passive: true
        });

        viewerWrap?.addEventListener('touchend', () => {
            touchStartDistance = null;
        });
    </script>

</body>

</html>