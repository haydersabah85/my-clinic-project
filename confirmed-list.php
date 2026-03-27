<?php
include "config.php";

include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $id = 0;
}
$date = isset($_GET['date']) ? $_GET['date'] : '';

$stmt = $con->prepare("
    SELECT 
        surgery_appointment.serial_no,
        add_patient.id,
        add_patient.full_name,
        surgery_appointment.id,
        surgery_appointment.eye,
        surgery_appointment.surgery_type,
        surgery_appointment.notes,
        surgery_appointment.phone,
        surgery_appointment.phone_alt,
        surgery_appointment.date,
        surgery_appointment.attendance_status
    FROM 
        surgery_appointment
    JOIN 
        add_patient ON surgery_appointment.patient_id = add_patient.id
    WHERE 
    
        surgery_appointment.date = ? AND 
        surgery_appointment.attendance_status = 1
    ORDER BY 
        surgery_appointment.serial_no ASC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$counter = 1;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>العمليات المؤكدة</title>

<script src="assets/theme.js" defer></script>

<style>
body {
    font-family: Tahoma, Arial;
    background: #f4f6f9;
    margin: 20px;
}

h2 {
    text-align: center;
    color: #2c3e50;
}

h4 {
    color: #34495e;
    margin-top: 30px;
}
a {
    text-decoration: none;
    color: #2980b9;
   display: inline-block;
    margin-bottom: 15px;

    margin-right: 10px;
    
    font-size: 16px;
    font-weight: bold;
    padding: 8px 12px;
    border: 2px solid #2980b9;
    border-radius: 5px;
    transition: all 0.3s ease;
    cursor: pointer;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    background-color: #ffffff;


}
a:hover {
    background: #2980b9;
    color: #fff;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);

}
.filter-box {
    text-align: center;
    margin-bottom: 15px;
}

input[type="date"] {
    padding: 6px 10px;
    font-size: 14px;
}

button {
    padding: 7px 14px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    border: none;
}

.print-btn {
    background: #3498db;
    color: #fff;
}

.cancel-btn {
    background: #e74c3c;
    color: #fff;
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 4px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
    font-size: 14px;
}

th {
    background: #ecf0f1;
}

/* ===== ترويسة الطباعة ===== */
.print-header {
    display: none;
    text-align: center;
    margin-bottom: 15px;
}

.print-header h1 {
    margin: 0;
    font-size: 22px;
}

.print-header p {
    margin: 5px 0;
    font-size: 14px;
}

/* ===== تنسيق الطباعة ===== */
@media print {
    body {
        background: #fff;
        margin: 0;
    }

    .filter-box,
    .print-btn,
    .cancel-btn {
        display: none;
    }

    .print-header {
        display: block;
    }

    table {
        font-size: 13px;
        border: 1px solid #000;
    }
}
</style>
</head>

<body>

<div class="print-header">
    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <p>قائمة العمليات المؤكدة</p>
    <?php if($date): ?>
        <p>التاريخ: <?php echo $date; ?></p>
    <?php endif; ?>
</div>

<h2>العمليات المؤكدة</h2>
<a href="dashboard.php">الصفحة الرئيسية</a>
<a href="export_surgery_excel.php?date=<?php echo $date; ?>" class="btn btn-success">
Export Surgery List
</a>
<a href="operation-by-date.php?date=<?php echo $date; ?>">عرض العمليات حسب التاريخ</a>

<div class="filter-box">
    <form method="GET">
        <input type="date" name="date" value="<?php echo $date; ?>">
        <button type="submit">عرض</button>
        <button type="button" class="print-btn" onclick="window.print()">🖨️ طباعة</button>
    </form>
</div>
<h4>قائمة العمليات المؤكدة بتاريخ: <?php echo $date; ?></h4>
<table>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع العملية</th>
    <th>ملاحظات</th>
    <th>الهاتف</th>
    <th>هاتف بديل</th>
    <th>إجراء</th>
</tr>

<?php if(mysqli_num_rows($result) > 0): ?>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?php echo $counter++; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['eye']; ?></td>
        <td><?php echo $row['surgery_type']; ?></td>
        <td><?php echo $row['notes']; ?></td>
        <td><?php echo $row['phone']; ?></td>
        <td><?php echo $row['phone_alt']; ?></td>
        <td>
            <a class="cancel-btn"
               href="cancel-attendance.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('هل تريد إلغاء تأكيد هذا المريض؟');">
               إلغاء التأكيد
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="8">لا توجد عمليات مؤكدة لهذا التاريخ</td>
</tr>
<?php endif; ?>

</table>

<h4>قائمة الليزر المؤكد بتاريخ: <?php echo $date; ?></h4>
<table>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع الليزر</th>
    <th>ملاحظات</th>
    <th>الهاتف</th>
    <th>هاتف بديل</th>
    <th>إجراء</th>
</tr>


<?php
$stmt = $con->prepare("
SELECT laser_appointment.*, 
add_patient.full_name,
add_patient.id AS patient_id
FROM laser_appointment
JOIN add_patient ON laser_appointment.patient_id = add_patient.id
WHERE attendance_status = 1 AND DATE(laser_appointment.date) = ?
ORDER BY laser_appointment.serial_no ASC");
$date = isset($_GET['date']) ? $_GET['date'] : '';
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$counter = 1;
?>
<?php if(mysqli_num_rows($result) > 0): ?>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?php echo $counter++; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['eye']; ?></td>
        <td><?php echo $row['laser_type']; ?></td>
        <td><?php echo $row['notes']; ?></td>
        <td><?php echo $row['phone']; ?></td>
        <td><?php echo $row['phone_alt']; ?></td>
        <td>
            <a class="cancel-btn"
               href="cancel-attendance.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('هل تريد إلغاء تأكيد هذا المريض؟');">
               إلغاء التأكيد
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="8">لا يوجد ليزر مؤكد لهذا التاريخ</td>
</tr>
<?php endif; ?>

</table>

<h4>قائمة الابر المؤكدة بتاريخ: <?php echo $date; ?></h4>
<table>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع الحقن</th>
    <th>ملاحظات</th>
    <th>الهاتف</th>
    <th>هاتف بديل</th>
    <th>إجراء</th>
</tr>
<?php
$stmt = $con->prepare("
SELECT injection_appointment.*, 
add_patient.full_name,
add_patient.id AS patient_id
FROM injection_appointment
JOIN add_patient ON injection_appointment.patient_id = add_patient.id
    WHERE attendance_status = 1 AND DATE(injection_appointment.date) = ?
    ORDER BY injection_appointment.serial_no ASC");
$date = isset($_GET['date']) ? $_GET['date'] : '';
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$counter = 1;
?>
<?php if(mysqli_num_rows($result) > 0): ?>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?php echo $counter++; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['eye']; ?></td>
        <td><?php echo $row['injection_type']; ?></td>
        <td><?php echo $row['notes']; ?></td>
        <td><?php echo $row['phone']; ?></td>
        <td><?php echo $row['phone_alt']; ?></td>
        <td>
            <a class="cancel-btn"
               href="cancel-attendance.php?id=<?php echo $row['id']; ?>"
               onclick="return confirm('هل تريد إلغاء تأكيد هذا المريض؟');">
               إلغاء التأكيد
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="8">لا يوجد حقن مؤكد لهذا التاريخ</td>
</tr>
<?php endif; ?>

</table>


</body>
</html>
