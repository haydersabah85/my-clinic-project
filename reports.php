<?php

include "config.php";



/* ===== FILTER DATES ===== */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

/* ===== NOT attend SURGERIES ===== */
$stmt = $con->prepare("
    SELECT COUNT(*) 
    FROM surgery_appointment 
    WHERE status = 'discharged'
    AND date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$stmt->bind_result($not_attend);
$stmt->fetch();
$stmt->close();

/* ===== GIVEN appointments ===== */
$stmt = $con->prepare("
    SELECT COUNT(*) 
    FROM surgery_appointment 
    WHERE  date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$stmt->bind_result($given_appointments);
$stmt->fetch();
$stmt->close();

/* ===== PERFORMED SURGERIES ===== */
$stmt = $con->prepare("
    SELECT COUNT(*) 
    FROM surgery
    WHERE date BETWEEN ? AND ?
    
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$stmt->bind_result($performed);
$stmt->fetch();
$stmt->close();

/* ===== TOTAL ===== */
//$total_surgeries = $confirmed + $performed + $postponed;


/* ===== VISITS COUNT ===== */
$stmt = $con->prepare("
    SELECT COUNT(*) 
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$stmt->bind_result($visits_total);
$stmt->fetch();
$stmt->close();

/* ===== DAYS COUNT ===== */
$days = (strtotime($to) - strtotime($from)) / 86400 + 1;
$avg_visits = $days > 0 ? round($visits_total / $days, 1) : 0;



?>





<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>التقارير</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/theme.css">
<script src="assets/theme.js" defer></script>

<style>
/* ====== VARIABLES ====== */
:root{
    --bg:#f4f6f8;
    --card:#ffffff;
    --text:#222;
    --muted:#6c757d;
    --primary:#0d6efd;
    --border:#e0e0e0;
}

[data-theme="dark"]{
    --bg:#0f172a;
    --card:#1e293b;
    --text:#e5e7eb;
    --muted:#94a3b8;
    --primary:#38bdf8;
    --border:#334155;
}

/* ====== BASE ====== */
body{
    margin:0;
    font-family:'Cairo',sans-serif;
    background:var(--bg);
    color:var(--text);
    direction:rtl;
}

.container{
    max-width:1200px;
    margin:auto;
    padding:25px;
}

/* ====== HEADER ====== */
.page-title{
    font-size:26px;
    font-weight:700;
    margin-bottom:5px;
}

.page-desc{
    color:var(--muted);
    margin-bottom:25px;
}

/* ====== FILTERS ====== */
.filters{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:14px;
    padding:20px;
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:end;
    margin-bottom:30px;
    direction: rtl;
}

.filters .field{
    flex:1;
    min-width:200px;
   
}

.filters label{
    font-size:13px;
    color:var(--muted);
    margin-bottom:6px;
    display:block;
}

.filters input{
    width:90%;
    padding:10px 12px;
    border-radius:10px;
    border:1px solid var(--border);
    background:transparent;
    color:var(--text);
    margin: auto 0;
}

.filters button{
    padding:11px 18px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-family:'Cairo';
    font-weight:600;
}

.btn-primary{
    background:var(--primary);
    color:#fff;
}

.btn-print{
    background:transparent;
    color:var(--primary);
    border:1px solid var(--primary);
}

/* ====== CARDS ====== */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:20px;
}

.card{
    background:var(--card);
    border-radius:16px;
    padding:22px;
    border:1px solid var(--border);
}

.card h3{
    margin:0 0 15px;
    font-size:18px;
}

.stat{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
    font-size:15px;
}

.stat span{
    color:var(--muted);
}

.big-number{
    font-size:34px;
    font-weight:700;
    color:var(--primary);
}

@media print{
    body{background:#fff}
    .filters{display:none}
    .card{page-break-inside:avoid}
}

</style>
</head>

<body>

<div class="container">

    <!-- ===== TITLE ===== -->
    <div class="page-title">📊 التقارير</div>
    <div class="page-desc">تحليل نشاط العيادة حسب الفترة الزمنية</div>

    <!-- ===== FILTERS ===== -->
    <form class="filters" method="GET">
        <div class="field">
            <label>من تاريخ</label>
            <input type="date" name="from">
        </div>

        <div class="field">
            <label>إلى تاريخ</label>
            <input type="date" name="to">
        </div>

        <button class="btn-primary">عرض التقرير</button>
        <button type="button" class="btn-print" onclick="window.print()">طباعة</button>
    </form>

    <!-- ===== CARDS ===== -->
    <div class="cards">

        <!-- عمليات -->
        <div class="card">
            <h3>🩺 تقرير العمليات</h3>

            <div class="big-number">—</div>

            <div class="stat">
                <span>المرضى الذين لديهم موعد</span>
                <strong><?= $given_appointments ?></strong>
            </div>

            <div class="stat">
                <span>المرضى الذين لم يحضروا</span>
                <strong><?= $not_attend ?></strong>
            </div>

            <div class="stat">
                <span>العمليات المنجزة</span>
                <strong><?= $performed ?></strong>
            </div>
        </div>

        <!-- زيارات -->
        <div class="card">
            <h3>👥 تقرير الزيارات</h3>

            <div class="big-number"><?= $visits_total ?></div>

            <div class="stat">
                <span>حضروا</span>
                <strong>-</strong>
            </div>

            <div class="stat">
                <span>لم يحضروا</span>
                <strong>-</strong>
            </div>

            <div class="stat">
                <span>متوسط يومي</span>
                <strong><?= $avg_visits ?></strong>
            </div>
        </div>

    </div>
</div>

</body>
</html>
