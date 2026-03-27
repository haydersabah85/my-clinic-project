<?php
include "config.php";

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $con->prepare("
    SELECT * FROM expected_appointments
Order BY expected_date =?
   
");

$stmt->bind_param("s", $date);
$stmt->execute();

$result = $stmt->get_result();
$appointments = [];

while($row = $result->fetch_assoc()){
    $appointments[] = $row;
}
?>


<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>مواعيد اليوم المتوقعة</title>

<style>
body{
    font-family:Cairo, Tahoma;
    background:#f1f5f9;
    padding:30px;
}

h2{
    text-align:center;
    margin-bottom:25px;
    color:#0d6efd;
}
.links{
    text-align:center;
    margin-bottom:20px;
}
.links a{
    margin:0 10px;
    text-decoration:none;
    color:#0d6efd;
    font-weight:bold;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,.1);
}

th,td{
    padding:12px;
    text-align:center;
}

th{
    background:#0d6efd;
    color:#fff;
}

.status-expected{background:#f8f9fa;}
.status-arrived{background:#d1e7dd;}
.status-not_arrived{background:#f8d7da;}

button{
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    margin:2px;
}

.arrived{background:#198754;color:#fff;}
.not_arrived{background:#dc3545;color:#fff;}
</style>

</head>
<body>

<h2>مواعيد اليوم المتوقعة</h2>
<div class="links">التاريخ: <?= htmlspecialchars($date) ?>
<a href="dashboard.php">الصفحة الرئيسية</a>
<a href="visits.php">زيارات اليوم</a>

</div>

<table>
<thead>
<tr>
    <th>#</th>
    <th>الاسم</th>
    <th>الوقت</th>
    <th>الهاتف</th>
    <th>نوع الزيارة</th>
    <th>ملاحظات</th>
    <th>الحالة</th>
    <th>إجراء</th>
</tr>
</thead>

<tbody>
<?php foreach($appointments as $i => $row): ?>
<tr class="status-<?= $row['status'] ?>">
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($row['full_name']) ?></td>
    <td><?= $row['expected_time'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= htmlspecialchars($row['visit_type']) ?></td>
    <td><?= htmlspecialchars($row['notes']) ?></td>
    <td><?= $row['status'] ?></td>
    <td>
        <?php if($row['status']=='expected'): ?>
            <a href="add-patient.php?id_admit=<?= $row['id'] ?>">
                <button class="arrived">تم الحضور</button>
            </a>
            <a href="mark_not_arrived.php?id=<?= $row['id'] ?>">
                <button class="not_arrived">لم يحضر</button>
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
