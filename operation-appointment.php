
<?php
include 'auth.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>


<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    h2 {
        text-align: center;
        color: #333;
    }

    a {
        display: inline-block;
        margin-bottom: 20px;
        text-decoration: none;
        color: #4CAF50;
        font-weight: bold;
    font-size: 18px;
    justify-content: center;
    align-items: center;
    display: flex;

    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    #operation_table, #laser_table, #injection_table {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        
    }   

    th,
    td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f2f2f2;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    th {
        background-color: #4CAF50;
        color: white;
    }

    tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #ddd;
    }

    .surgery,
    .laser,
    .injection {
        margin-bottom: 40px;
        overflow-y: auto;
        max-height: 400px;
        width: 100%;
        border-radius: 5px;

    }
</style>

<body>
    <div class="surgery">
        <h2>جدول مواعيد العمليات</h2>

        <a href="main.php">الصفحة الرئيسية</a>
        <table border="1" cellpadding="10" cellspacing="0" id="operation_table">
            <thead>
                <tr>
                    <th> # </th>
                    <th>الاسم</th>
                    <th>العين</th>
                    <th>نوع العملية</th>
                    <th>الملاحظات</th>
                    <th>رقم الهاتف 1</th>
                    <th>رقم الهاتف 2</th>
                    <th>التاريخ</th>
                    

                </tr>
            </thead>


            <tbody>

                <?php
                include 'config.php';


                $sql = "SELECT
surgery_appointment.serial_no,
add_patient.id,
add_patient.full_name,
surgery_appointment.eye,
surgery_appointment.surgery_type,
surgery_appointment.notes,
surgery_appointment.phone,
surgery_appointment.phone_alt,
surgery_appointment.date
FROM surgery_appointment
JOIN add_patient ON surgery_appointment.patient_id = add_patient.id
ORDER BY surgery_appointment.serial_no ASC, date DESC";

                $result = mysqli_query($con, $sql);
                while ($row = mysqli_fetch_assoc($result)) {

                ?>
                    <tr>
                        <td><?php echo $row['serial_no']; ?></td>
                        <td><?php echo $row['full_name']; ?></td>
                        <td><?php echo $row['eye']; ?></td>
                        <td><?php echo $row['surgery_type']; ?></td>
                        <td><?php echo $row['notes']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td><?php echo $row['phone_alt']; ?></td>
                        <td><?php echo $row['date']; ?></td>

                    </tr>
                <?php
                }
                ?>



            </tbody>

        </table>
    </div>

    <br><br>

    <div class="laser">
        <h2>جدول مواعيد الليزر</h2>

        <table border="1" cellpadding="10" cellspacing="0" id="laser_table">
            <thead>
                <tr
                    style="
            background: #4caf50;
            color: white;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); 
            ">
                    <th> # </th>
                    <th>الاسم</th>
                    <th>نوع الليزر</th>
                    <th>العين</th>
                    <th>الملاحظات</th>
                    <th>رقم الهاتف 1</th>
                    <th>رقم الهاتف 2</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_laser = "SELECT
        laser_appointment.serial_no,
        add_patient.id,
        add_patient.full_name,
        laser_appointment.eye,
        laser_appointment.laser_type,
        laser_appointment.notes,
        laser_appointment.phone,
        laser_appointment.phone_alt,
        laser_appointment.date
        FROM laser_appointment
        JOIN add_patient ON laser_appointment.patient_id = add_patient.id
        ORDER BY laser_appointment.serial_no ASC, date DESC";

                $result_laser = mysqli_query($con, $sql_laser);
                while ($row_laser = mysqli_fetch_assoc($result_laser)) {

                ?>
                    <tr>
                        <td><?php echo $row_laser['serial_no']; ?></td>
                        <td><?php echo $row_laser['full_name']; ?></td>
                        <td><?php echo $row_laser['eye']; ?></td>
                        <td><?php echo $row_laser['laser_type']; ?></td>
                        <td><?php echo $row_laser['notes']; ?></td>
                        <td><?php echo $row_laser['phone']; ?></td>
                        <td><?php echo $row_laser['phone_alt']; ?></td>
                        <td><?php echo $row_laser['date']; ?></td>

                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>

    <br><br>

    <div class="injection">

        <h2>جدول مواعيد الحقن</h2>
        <table border="1" cellpadding="10" cellspacing="0" id="injection_table">
            <thead>
                <tr
                    style="
            background: #4caf50;
            color: white;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2); 
            ">
                    <th> # </th>
                    <th>الاسم</th>
                    <th>نوع الحقن</th>
                    <th>العين</th>
                    <th>الملاحظات</th>
                    <th>رقم الهاتف 1</th>
                    <th>رقم الهاتف 2</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_injection = "SELECT
        injection_appointment.serial_no,
        add_patient.id,
        add_patient.full_name,
        injection_appointment.eye,
        injection_appointment.injection_type,
        injection_appointment.notes,
        injection_appointment.phone,
        injection_appointment.phone_alt,
        injection_appointment.date
        FROM injection_appointment
        JOIN add_patient ON injection_appointment.patient_id = add_patient.id
        ORDER BY injection_appointment.serial_no ASC, date DESC";

                $result_injection = mysqli_query($con, $sql_injection);
                while ($row_injection = mysqli_fetch_assoc($result_injection)) {

                ?>
                    <tr>
                        <td><?php echo $row_injection['serial_no']; ?></td>
                        <td><?php echo $row_injection['full_name']; ?></td>
                        <td><?php echo $row_injection['eye']; ?></td>
                        <td><?php echo $row_injection['injection_type']; ?></td>
                        <td><?php echo $row_injection['notes']; ?></td>
                        <td><?php echo $row_injection['phone']; ?></td>
                        <td><?php echo $row_injection['phone_alt']; ?></td>
                        <td><?php echo $row_injection['date']; ?></td>

                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>