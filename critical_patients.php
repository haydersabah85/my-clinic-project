<?php
include "config.php";

/* ===== SQL: GET MANUALLY MARKED CRITICAL PATIENTS ===== */
$sql = "
SELECT
    p.id AS patient_id,
    p.full_name AS patient_name,
    p.is_critical
FROM add_patient p
WHERE p.is_critical = 1
ORDER BY p.full_name ASC
";

$result = $con->query($sql);
$count = 0;

if ($result) {
    $count = $result->num_rows;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>🚨 المرضى الحرِجون</title>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
    <style>
        body {
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #fef2f2 0%, #f8fafc 100%);
            color: #111827;
            padding: 24px;
        }

        .page {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #dc2626, #7f1d1d);
            color: white;
            padding: 22px 24px;
            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(127, 29, 29, 0.18);
            margin-bottom: 18px;
        }

        .header h2 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .header p {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .search-box {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 260px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-muted {
            background: #f3f4f6;
            color: #374151;
        }

        .stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
            min-width: 140px;
        }

        .stat-card strong {
            display: block;
            font-size: 18px;
        }

        .card {
            background: white;
            padding: 16px 18px;
            border-radius: 14px;
            margin-bottom: 12px;
            border-right: 6px solid #dc2626;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .name {
            font-size: 17px;
            font-weight: 800;
            color: #111827;
        }

        .meta {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .action-btn.open {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .action-btn.remove {
            background: #fef2f2;
            color: #b91c1c;
        }

        .empty-state {
            background: white;
            border-radius: 14px;
            padding: 24px;
            text-align: center;
            color: #6b7280;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <h2>🚨 المرضى الحرِجون</h2>
            <p>هذه القائمة تشمل فقط المرضى الذين تم تعليمهم يدويًا كمريض حرج.</p>
        </div>

        <div class="toolbar">
            <form class="search-box" id="searchForm" method="get" action="critical_patients.php">
                <input type="text" id="searchInput" name="search" placeholder="ابحث باسم المريض...">
                <button class="btn btn-primary" type="submit">بحث</button>
                <button class="btn btn-muted clear-search" type="button">عرض الكل</button>
            </form>
            <a class="btn btn-muted" href="dashboard.php">العودة للوحة التحكم</a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <strong id="patientsCount"><?= (int) $count ?></strong>
                <span>إجمالي المرضى الحرجين</span>
            </div>
        </div>

        <?php if ($result && $count > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card patient-card" data-name="<?= htmlspecialchars($row['patient_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <div class="name">
                            <?= htmlspecialchars($row['patient_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="meta">
                            🔴 مُعلّم يدويًا كمريض حرج<br>
                            📌 يظهر في هذه القائمة لأنّه تم تعليمه يدويًا كمريض حرج.
                        </div>
                    </div>
                    <div class="actions">
                        <a class="action-btn open" href="patient-file.php?id=<?= (int) $row['patient_id'] ?>">فتح الملف</a>
                        <a class="action-btn remove" href="mark_critical.php?id=<?= (int) $row['patient_id'] ?>&from=critical_list" onclick="return confirm('هل تريد إلغاء التعليم كمريض حرج؟');">إلغاء الحرج</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" id="emptyState">لا يوجد مرضى حرجون تم تعليمهم يدويًا في الوقت الحالي.</div>
        <?php endif; ?>
    </div>

    <script>
        (() => {
            const form = document.getElementById('searchForm');
            const input = document.getElementById('searchInput');
            const clearBtn = document.querySelector('.clear-search');
            const cards = Array.from(document.querySelectorAll('.patient-card'));
            const countEl = document.getElementById('patientsCount');
            const emptyState = document.getElementById('emptyState');

            function applyFilter() {
                const query = (input?.value || '').trim().toLowerCase();
                let visibleCount = 0;

                cards.forEach(card => {
                    const name = (card.dataset.name || '').toLowerCase();
                    const matches = !query || name.includes(query);
                    card.style.display = matches ? '' : 'none';
                    if (matches) visibleCount++;
                });

                if (countEl) {
                    countEl.textContent = visibleCount;
                }

                if (emptyState) {
                    emptyState.style.display = visibleCount > 0 ? 'none' : 'block';
                }
            }

            if (form && input) {
                form.addEventListener('submit', event => {
                    event.preventDefault();
                    applyFilter();
                    window.history.replaceState({}, '', window.location.pathname);
                });

                input.addEventListener('input', applyFilter);
            }

            if (clearBtn && input) {
                clearBtn.addEventListener('click', () => {
                    input.value = '';
                    applyFilter();
                    window.history.replaceState({}, '', window.location.pathname);
                });
            }

            applyFilter();
        })();
    </script>
</body>

</html>