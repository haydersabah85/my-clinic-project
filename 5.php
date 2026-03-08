<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient File</title>


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 20px;
            background-color: #f9f9f9;
        }

        header {

            text-align: center;
            margin-bottom: 10px;
        }

        .patient_info {
       background-color: #fff;
            padding: 15px;
            width: 68%;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;


        }


        a {
            background-color: #0ab370ff;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;

        }

        a:hover {
            background-color: #1a5642ff;
            transform: scale(1.05);
            transition: 0.3s;
        }


   

        .nav {
            margin: 20px 0;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 15px;
            


        }
        .nav a {
            background-color: #a011adff;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;

        }

        .nav a:hover {
            background-color: #7a0d78ff;
            transform: scale(1.05);
            transition: 0.3s;

        }

        #edit_va,
        #edit_surgery,
        #edit_laser,
        #edit_injection {
            background-color: #0ab370ff;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
            display: inline-block;

        }

        #edit_va:hover,
        #edit_surgery:hover,
        #edit_laser:hover,
        #edit_injection:hover {
            background-color: #1a5642ff;
            transform: scale(1.05);
            transition: 0.3s;

        }

        .previous_data {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;

            overflow-y: auto;
        }

        .patient_info h4 {
            margin-bottom: 15px;
        }

        .patient_info p {
            margin: 10px 0;
            font-size: 16px;
        }

        .patient_info span:first-child {
            font-weight: bold;
            margin-right: 10px;
        }

        button {
            background-color: #0ab370ff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 600;


        }

        button:hover {
            background-color: #1a5642ff;

        }

        table {
            border: 1px solid #ddd;
            width: 100%;
            height: auto;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #ece3e1e0;
            border-radius: 8px;
            overflow: hidden;

        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            overflow-y: auto;
          text-overflow: ellipsis;
          max-width: 200px;
          

        }

        th {
            background-color: #1976d2;
            color: white;
            font-weight: bold;
            font-family: 'Cairo', sans-serif;
            text-align: center;
          direction: rtl;
            font-size: 14px;
            padding: 8px;


        }

        .links {
            margin: 20px;
            text-align: center;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .links a {
            background-color: #e85a27ff;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;

        }

        .links a:hover {
            background-color: #bb4c18ff;
            transform: scale(1.05);

        }

        #notes {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 18px;
            resize: vertical;
            text-align: left;
            font-family: 'Cairo', sans-serif;
            margin-left: auto;
            margin-right: auto;
            direction: ltr;
        }

        #add_visit {
            margin-top: 10px;
            font-size: 16px;

        }

        .previous_data table tbody tr:hover {
            background-color: #d1e7fd;
        }

        .previous_visits {
            max-height: 300px;
            width: 58%;
            overflow-y: auto;

        }

        .previous_va {
            max-height: 300px;
            width: 40%;
            overflow-y: auto;

        }

        .previous_surgeries,
        .previous_lasers,
        .previous_injections {
            max-height: 300px;
            width: 49%;
            overflow-y: auto;

        }

        .visit-note {
            position: relative;
        
            cursor: pointer;
            direction: ltr;
            white-space: pre-wrap;

        }
        
        .visit-note::after {
            content: attr(data-note);
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            
            font-size: 16px;
            background-color: #333;
            color: #fff;
            padding: 10px;
            border-radius: 6px;
            display: none;
            width: 300px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            white-space: pre-wrap;
            text-align: left;
            direction: ltr;

        }
        .visit-note:hover::after {
            display: block;
        }
      
    </style>
</head>
<header>
    <h1> عيادة الدكتور حيدر صباح الربيعي</h1>
</header>


<body>


    <div class="patient_info"></span>

        <p><span>ID:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['id']); ?></span>
        </p>

        <p><span>الاسم:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['full_name']); ?></span>
        </p>

        <p><span>العمر:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['age']); ?></span>
        </p>

        <p><span>رقم الموبايل:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($row['phone_no']); ?></span>
        </p>
        <a href="edit-patient.php?id_edit=<?php echo $row['id']; ?>">تعديل البيانات</a>
    </div>

    <div class="nav">
        
        <a href="visits.php">زيارات اليوم</a>
        <a href="main.php">الصفحة الرئيسية</a>
    </div>


    <div class="previous_data">
        <div class="previous_visits">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>

                        <th>Edit</th>
                        <th>Previous Visits Notes</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])); {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM patient_visits WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($visit_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td><button class='btn btn-success edit-btn' data-note='" . htmlspecialchars($visit_row['notes'], ENT_QUOTES, 'UTF-8') . "' data-id='" . $visit_row['id'] . "'>تعديل</button></td>";

                            echo "<td class=\"visit-note\" data-note='" . htmlspecialchars($visit_row['notes'], ENT_QUOTES, 'UTF-8') . "'>" . nl2br(htmlspecialchars($visit_row['notes'])) . " 
                                </td>";
                            echo "<td>" . htmlspecialchars($visit_row['date']) . "</td>";

                            echo "</tr>";
                        }
                    }


                    ?>

                </tbody>
            </table>
        </div>

        <div class="previous_va">
            <table>
                <thead>
                    <tr>
                        <th>Edit</th>
                        <th>BCVA(OS)</th>
                        <th>BCVA(OD)</th>
                        <th>VA(OS)</th>
                        <th>VA(OD)</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM va WHERE patient_id = $id ORDER BY exam_date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($va_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td><a id='edit_va' href='edit-va.php?id_edit=" . $va_row['va_id'] . "' >تعديل</a></td>";
                            echo "<td>" . htmlspecialchars($va_row['bcva_os']) . "</td>";
                            echo "<td>" . htmlspecialchars($va_row['bcva_od']) . "</td>";
                            echo "<td>" . htmlspecialchars($va_row['va_os']) . "</td>";
                            echo "<td>" . htmlspecialchars($va_row['va_od']) . "</td>";
                            echo "<td>" . htmlspecialchars($va_row['exam_date']) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>

                </tbody>
            </table>
        </div>

        <div class="previous_surgeries">

            <table>
                <thead>
                    <tr>
                        <th>Edit</th>
                        <th>Notes</th>
                        <th>IOL</th>
                        <th>Type of Surgery</th>
                        <th>Eye</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM surgery
                        WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($surgery_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td><a id='edit_surgery' href='edit-surgery.php?id_edit=" . $surgery_row['id'] . "' >تعديل</a></td>";
                            echo "<td>" . htmlspecialchars($surgery_row['notes']) . "</td>";
                            echo "<td>" . htmlspecialchars($surgery_row['iol_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($surgery_row['surgery_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($surgery_row['eye']) . "</td>";
                            echo "<td>" . htmlspecialchars($surgery_row['date']) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>

                </tbody>
            </table>
        </div>

        <div class="previous_lasers">
            <table>
                <thead>
                    <tr>
                        <th>Edit</th>
                        <th>Notes</th>
                        <th>Laser</th>
                        <th>Eye</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM laser WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($laser_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td><a id='edit_laser' href='edit-laser.php?id_edit=" . $laser_row['id'] . "' >تعديل</a></td>";
                            echo "<td>" . htmlspecialchars($laser_row['notes']) . "</td>";
                            echo "<td>" . htmlspecialchars($laser_row['laser_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($laser_row['eye']) . "</td>";
                            echo "<td>" . htmlspecialchars($laser_row['date']) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>

                </tbody>
            </table>
        </div>

        <div class="previous_injections">
            <table>
                <thead>
                    <tr>
                        <th>Edit</th>
                        <th>Notes</th>
                        <th>Injection</th>
                        <th>Eye</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "
                        SELECT * FROM injection WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($injection_row = mysqli_fetch_assoc($result)) {

                            echo "<tr>";
                            echo "<td><a id='edit_injection' href='edit-injection.php?id_edit=" . $injection_row['id'] . "' >تعديل</a></td>";
                            echo "<td>" . htmlspecialchars($injection_row['notes']) . "</td>";
                            echo "<td>" . htmlspecialchars($injection_row['injection_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($injection_row['eye']) . "</td>";
                            echo "<td>" . htmlspecialchars($injection_row['date']) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>

                </tbody>
            </table>
        </div>

        <div class="patient_visits">

            <form action="patient-visits.php?id=<?php echo $row['id'] ?>" method="POST">
                <input type="hidden" id="id" name="id">
                <textarea spellcheck="false" id="notes" name="notes" rows="4" cols="43" placeholder="اكتب ملاحظات الزيارة هنا..."></textarea>

                <button type="submit" id="add_visit" name="add_visit">إضافة زيارة</button>
            </form>


        </div>
    </div>

    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const notes = this.dataset.note;
            const visitId = this.dataset.id;
          
            document.getElementById('notes').value = notes;
            document.getElementById('id').value = visitId;


            document.getElementById('add_visit').innerText = 'تحديث الزيارة';

        });
    });
    </script>

<script>
window.onload = function () {
    const notes = document.getElementById('notes');
    const visitId = document.getElementById('id');

    if (notes) notes.value = '';
    if (visitId) visitId.value = '';
    
    const btn = document.getElementById('add_visit');
    if (btn) btn.innerText = 'إضافة زيارة';
};

</script>


</body>
<div class="links">
    <a href="surgery-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد عملية</a>
    <a href="laser-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد ليزر</a>
    <a href="injection-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد حقن</a>
    <a href="add-va.php?id=<?php echo htmlspecialchars($row['id']); ?>">اضافة فحص النظر</a>
   
</div>

</html>