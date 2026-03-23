<?php
include 'auth.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مواعيد العمليات</title>

    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme.js" defer></script>
</head>


<style>


   
/* ===== Table Container ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    background-color: #ffffff;
    font-family: 'Arial', sans-serif;
    border: none;
}

/* ===== Table Header ===== */
th {
    background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
    color: #fff;
    font-weight: 600;
    padding: 12px 15px;
    text-align: center;
    font-size: 14px;
}

/* ===== Table Cells ===== */
td {
    padding: 12px 15px;
    text-align: center;
    font-size: 14px;
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    transition: background 0.3s ease;
    border: 0.5px solid #f5f5f5;
}

/* ===== Hover Effect on Rows ===== */
tr:hover {
    background-color: #f0f7ff;
}

/* ===== Action Buttons (Edit/Add/Delete) ===== */
#edit, #add, #delete {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: bold;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    margin: auto ;
}

/* ===== Edit Button ===== */
#edit {
    color: #fff;
    background: linear-gradient(90deg, #28a745, #1fa974);
}
#edit:hover {
    background: linear-gradient(90deg, #218838, #1e8b65);
}

/* ===== Add Button ===== */
#add {
    color: #fff;
    background: linear-gradient(90deg, #007bff, #0056b3);
}
#add:hover {
    background: linear-gradient(90deg, #0056b3, #003f7f);
}

/* ===== Delete Button ===== */
#delete {
    color: #fff;
    background: linear-gradient(90deg, #dc3545, #a71d2a);
}
#delete:hover {
    background: linear-gradient(90deg, #a71d2a, #7f141b);
}

.confirm-btn {
    background: #27ae60;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.confirm-btn:hover {
    background: #219150;
}

.confirmed-label {
    color: #27ae60;
    font-weight: bold;
}

/* ===== Responsive ===== */
@media screen and (max-width: 768px) {
    th, td {
        padding: 10px 8px;
        font-size: 12px;
    }

    #edit, #add, #delete {
        padding: 5px 8px;
        font-size: 12px;
    }
}

/* ===== Optional: Smooth Button Hover Effect ===== */
#edit:hover, #add:hover, #delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

   
</style>

<body>


    <?php
    include "config.php";

    $date = $_GET['date'];

    $stmt = $con->prepare("
    SELECT 
    surgery_appointment.serial_no,
    surgery_appointment.id,
    add_patient.full_name,
    surgery_appointment.patient_id,
    surgery_appointment.eye,
    surgery_appointment.surgery_type,
    surgery_appointment.notes,
    surgery_appointment.phone,
    surgery_appointment.phone_alt,
    surgery_appointment.date,
    surgery_appointment.attendance_status
    FROM surgery_appointment
    JOIN add_patient ON surgery_appointment.patient_id = add_patient.id
    WHERE date = ? and status = 'pending' ORDER BY surgery_appointment.serial_no ASC");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();


    echo "<h3>عمليات بتاريخ: $date</h3>";

    echo "<table border='1' width='100%' cellspacing='0'>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع العملية</th>
    <th>ملاحظات</th>
    <th>رقم الهاتف 1</th>
    <th> رقم الهاتف 2</th>
    <th>تأكيد الحضور</th>
    <th>تعديل</th>
    <th>اضافة</th>
    <th> حذف</th>
    
    
    
</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
        <td>{$row['serial_no']}</td>
        <td>{$row['full_name']}</td>  
        <td>{$row['eye']}</td>  
        <td>{$row['surgery_type']}</td>
        <td>{$row['notes']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['phone_alt']}</td>
        <td>";
        
        if ((int)$row['attendance_status'] === 0) {
            echo "<a class='confirm-btn' 
                    href='confirm-attendance.php?id={$row['id']}'><span class='confirmed-label'>✔ مؤكد</span>
                    تأكيد الحضور
                  </a>";
        } else {
            echo "<span class='confirmed-label'>✔ مؤكد</span>";
        }

    echo "</td>
        <td><a id='edit' href='edit-surgery-appointment.php?id={$row['id']}'>تعديل</a></td>
        <td><a id='add' href='process_decision_surgery.php?id={$row['patient_id']}'>اضافة</a></td>
        <td><a id='delete' onclick=\"return confirm('هل أنت متأكد من حذف هذا الموعد؟')\" href='delete-surgery-appointment.php?id={$row['id']}'>حذف</a></td>
        
        
    </tr>";
    }

    echo "</table>";
    ?>

    <br><br>
    <?php
    include "config.php";

    $date = $_GET['date'];

    $stmt = $con->prepare("
    SELECT 
    laser_appointment.id,
    laser_appointment.patient_id,
    laser_appointment.serial_no,
    add_patient.full_name,
    laser_appointment.eye,
    laser_appointment.laser_type,
    laser_appointment.notes,
    laser_appointment.phone,
    laser_appointment.phone_alt,
    laser_appointment.attendance_status,
    laser_appointment.date
    FROM laser_appointment
    JOIN add_patient ON laser_appointment.patient_id = add_patient.id
    WHERE date = ? and status = 'pending' ORDER BY laser_appointment.serial_no ASC");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();


    echo "<h3>الليزر بتاريخ: $date</h3>";
    echo "<table border='1' width='100%' cellspacing='0'>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع الليزر</th>
    <th>ملاحظات</th>
    <th>رقم الهاتف 1</th>
    <th> رقم الهاتف 2</th>
    <th>تأكيد الحضور</th>
    <th>تعديل</th>
    <th>اضافة</th>
    <th>حذف</th>
    
    
    
</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
        <td>{$row['serial_no']}</td>
        <td>{$row['full_name']}</td>  
        <td>{$row['eye']}</td>  
        <td>{$row['laser_type']}</td>
        <td>{$row['notes']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['phone_alt']}</td>
        <td>";
        
        if ((int)$row['attendance_status'] === 0) {
            echo "<a class='confirm-btn' 
                    href='confirm-attendance.php?id={$row['id']}'><span class='confirmed-label'>✔ مؤكد</span>
                    تأكيد الحضور
                  </a>";
        } else {
            echo "<span class='confirmed-label'>✔ مؤكد</span>";
        }

    echo "</td>
        <td><a id='edit' href='edit-laser-appointment.php?id={$row['id']}'>تعديل</a></td>
        <td><a id='add' href='process_decision_laser.php?id={$row['patient_id']}'>اضافة</a></td>
        <td><a id='delete' onclick=\"return confirm('هل أنت متأكد من حذف هذا الموعد؟')\" href='delete-laser-appointment.php?id={$row['id']}'>حذف</a></td>

        
        
    </tr>";
    }

    echo "</table>";
    ?>

    <br><br>

    <?php
    include "config.php";

    $date = $_GET['date'];

    $stmt = $con->prepare("
    SELECT 
    injection_appointment.serial_no,
    injection_appointment.patient_id,
    injection_appointment.id,
    add_patient.full_name,
    injection_appointment.eye,
    injection_appointment.injection_type,
    injection_appointment.notes,
    injection_appointment.phone,
    injection_appointment.phone_alt,
    injection_appointment.attendance_status,
    injection_appointment.date
    FROM injection_appointment
    JOIN add_patient ON injection_appointment.patient_id = add_patient.id
    WHERE date = ? and status = 'pending' ORDER BY injection_appointment.serial_no ASC");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();


    echo "<h3>حقن الابر بتاريخ: $date</h3>";
    echo "<table border='1' width='100%' cellspacing='0'>
<tr>
    <th>#</th>
    <th>اسم المريض</th>
    <th>العين</th>
    <th>نوع الحقن</th>
    <th>ملاحظات</th>
    <th>رقم الهاتف 1</th>
    <th> رقم الهاتف 2</th>
    <th>تأكيد الحضور</th>
    <th>تعديل</th>
    <th>اضافة</th>
    <th>حذف</th>
    
    
    
</tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
        <td>{$row['serial_no']}</td>
        <td>{$row['full_name']}</td>  
        <td>{$row['eye']}</td>  
        <td>{$row['injection_type']}</td>
        <td>{$row['notes']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['phone_alt']}</td>
        <td>";
        
        if ((int)$row['attendance_status'] === 0) {
            echo "<a class='confirm-btn' 
                    href='confirm-attendance.php?id={$row['id']}'><span class='confirmed-label'>✔ مؤكد</span>
                    تأكيد الحضور
                  </a>";
        } else {
            echo "<span class='confirmed-label'>✔ مؤكد</span>";
        }

    echo "</td>
        <td><a id='edit' href='edit-injection-appointment.php?id={$row['id']}'>تعديل</a></td>
        <td><a id='add' href='process_decision_injection.php?id={$row['patient_id']}'>اضافة</a></td>
        <td><a id='delete' onclick=\"return confirm('هل أنت متأكد من حذف هذا الموعد؟')\" href='delete-injection-appointment.php?id={$row['id']}'>حذف</a></td>
        
        
    </tr>";
    }

    echo "</table>";
    ?>


</body>

</html>