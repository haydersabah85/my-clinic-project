<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";
    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

if (isset($_GET['id_open'])) {
    $id_open = $_GET['id_open'];
    //جلب بيانات المريض والزيارات السابقة وفحص النظر//
    $select_query = "SELECT * FROM add_patient WHERE id = $id_open";

    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}


// جلب البيانات عند الضغط على critical patients
if (isset($_GET['patient_id'])) {
    $id_edit = $_GET['patient_id'];

    $select_query = "SELECT * FROM add_patient WHERE id = $id_edit";

    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📁 <?php echo $row['full_name']; ?></title>
</head>

<script src="assets/theme.js" defer></script>



<style>
    /* ====== الخط والخلفية العامة ====== */
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');


    /* Dark Mode */
    body[data-theme="dark"] {
        background: linear-gradient(135deg, #2c3e50, #4ca1af);
        color: #2c3e50;
        font-size: 28px;
    }

    .patient_info[data-theme="dark"] {
        background: #34495e;
        color: #ecf0f1;
    }

    .patient_info[data-theme="dark"] span:first-child {
        color: #ecf0f1;
    }




    body {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        margin: 20px;
        background: linear-gradient(135deg, #f4f7fb, #eef2f6);
    }

    /* ====== الهيدر ====== */
    header h1 {
        color: #2c3e50;
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 15px;
        text-align: center;
    }

    /* ====== معلومات المريض ====== */
    .patient_info {
        background: #ffffff;
        padding: 18px 20px;
        width: fit-content;
        margin: 0 auto 20px;
        border-radius: 14px;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 35px;
    }

    .patient_info p {
        font-size: 16px;
        margin: 5px 0;
    }

    .patient_info span:first-child {
        font-weight: 600;
        color: #34495e;
    }

    /* ====== الأزرار العامة ====== */



    a {
        background: linear-gradient(135deg, #0ab370, #1a8f65);
        color: #fff;
        text-decoration: none;
        padding: 8px 14px;
        font-size: 14px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        font-family: 'Cairo', sans-serif;

    }

    a:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
    }

    /* ====== شريط التنقل ====== */
    .nav {
        margin: 25px 0;
        display: flex;
        justify-content: center;
        gap: 50px;


    }



    /* ====== الحاوية الرئيسية ====== */
    .previous_data {
        background: #fff;
        padding: 22px;
        border-radius: 16px;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 30%;
        text-decoration: none;
        font-size: 17px;
        transition: all .3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
        position: relative;
        cursor: pointer;
        padding: 20px;

    }

    /* ====== جدول الزيارات وفحص النظر جنب بعض ====== */
    .previous_visits {
        width: 58%;
        max-height: 320px;
        overflow-y: auto;
    }

    .previous_va {
        width: 40%;
        max-height: 320px;
        overflow-y: auto;
    }

    /* ====== بقية الجداول ====== */
    .previous_surgeries,
    .previous_lasers,
    .previous_injections {
        width: 49%;
        max-height: 300px;
        overflow-y: auto;
    }

    /* ====== الجداول ====== */
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fdfefe;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    th {
        background: linear-gradient(135deg, #1976d2, #125aa3);
        color: #fff;
        font-size: 14px;
        padding: 10px;
    }

    td {
        padding: 8px;
        font-size: 14px;
        text-align: center;
        white-space: nowrap;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;

    }

    /* تفاعل الصف */
    tbody tr {
        transition: background-color 0.25s ease;
    }

    tbody tr:hover {
        background-color: #e3f2fd;
    }

    /* ====== نافذة الملاحظات المنبثقة ====== */
    .visit-note,
    .surgery-notes {
        position: relative;
        cursor: pointer;
        direction: ltr;
        white-space: nowrap;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;

    }

    .visit-note::after,
    .surgery-notes::after {
        content: attr(data-note);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        white-space: normal;
        width: 200px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        display: none;
        z-index: 10;

    }

    .visit-note:hover::after,
    .surgery-notes:hover::after {
        display: block;
    }

    /* ====== فورم إضافة الزيارة ====== */
    .patient_visits {
        width: 47%;
        margin-top: 10px;
    }

    #notes {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border-radius: 10px;
        border: 1px solid #ccc;
        resize: vertical;
        direction: ltr;
    }

    #add_visit {
        margin-top: 10px;
        background: linear-gradient(135deg, #0ab370, #1a8f65);
        color: #fff;
        border: none;
        width: 150px;
        height: 40px;
        padding: 10px 10px;
        font-size: 16px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }



    /* ====== الروابط السفلية ====== */
    .links {
        margin: 25px 0;
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .links a {
        background: linear-gradient(135deg, #e85a27, #bb4c18);
    }

    /* ====== ICON BUTTONS ====== */
    .icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        text-decoration: none;
        font-size: 17px;
        transition: all .3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
        position: relative;
        cursor: pointer;
    }

    /* Hover effect */
    .icon-btn:hover {
        transform: translateY(-2px) scale(1.08);
        box-shadow: 0 6px 12px rgba(0, 0, 0, .25);
    }

    /* Colors */
    .edit-icon {
        background: linear-gradient(135deg, #3b82f6, #1e40af);
        color: #fff;
    }

    .delete-icon {
        background: linear-gradient(135deg, #ef4444, #991b1b);
        color: #fff;
    }

    .warning-icon {
        background: linear-gradient(135deg, #facc15, #eab308);
        color: #000;
    }

    .visits-icon {
        background: linear-gradient(135deg, #a011ad, #7a0d78);
        color: #fff;
    }

    .home-icon {
        background: linear-gradient(135deg, #6b7280, #374151);
        color: #fff;
    }

    .followup-btn {
        background: linear-gradient(135deg, #8bafce, #36bcf5);
        color: #000;
    }

    /* DELETE SHAKE EFFECT */
    /* ============================= */
    .delete-icon:hover {
        animation: shake .4s;
    }

    @keyframes shake {
        0% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-3px);
        }

        50% {
            transform: translateX(3px);
        }

        75% {
            transform: translateX(-3px);
        }

        100% {
            transform: translateX(0);
        }
    }

    /* ============================= */
    /* EDIT ROTATION EFFECT */
    /* ============================= */
    .edit-icon:hover {
        animation: rotateHalf .5s forwards;
    }

    @keyframes rotateHalf {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(180deg);
        }
    }

    /* ============================= */
    /* WARNING BLINK EFFECT */
    /* ============================= */
    .critical-blink {
        animation: blink 1s infinite;
    }

    @keyframes blink {
        0% {
            box-shadow: 0 0 0px rgba(255, 0, 0, 0);
        }

        50% {
            box-shadow: 0 0 15px rgba(255, 0, 0, .9);
        }

        100% {
            box-shadow: 0 0 0px rgba(255, 0, 0, 0);
        }
    }


    /* Tooltip */
    .icon-btn::after {
        content: attr(data-title);
        position: absolute;
        bottom: 120%;
        right: 50%;
        transform: translateX(50%);
        background: #111;
        color: #fff;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s;
    }

    .icon-btn:not(.edit-icon, .delete-icon):hover::after {
        opacity: 1;
    }

    /* الخلفية */
.modal{
    display:none;
    position:fixed;
    z-index:1000;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.45);
}

/* الصندوق */
.modal-content{
    background:#fff;
    width:350px;
    padding:25px;
    border-radius:12px;
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    animation:pop 0.25s ease;
}

/* حركة الظهور */
@keyframes pop{
    from{
        transform:translate(-50%,-60%) scale(0.9);
        opacity:0;
    }
    to{
        transform:translate(-50%,-50%) scale(1);
        opacity:1;
    }
}

/* زر الاغلاق */
.close-btn{
    float:left;
    font-size:22px;
    cursor:pointer;
    color:#999;
}

/* الحقول */
.modal input{
    width:100%;
    padding:8px;
    margin-top:5px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:6px;
}

/* زر الحفظ */
.modal button{
    width:100%;
    padding:10px;
    border:none;
    background:#28a745;
    color:white;
    border-radius:6px;
    font-size:15px;
    cursor:pointer;
}

.modal button:hover{
    background:#23913c;
}
</style>




<header>
    <h1> ملف المريض: <?php echo htmlspecialchars($row['full_name']); ?></h1>
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
        <a href="patient-data.php?id_open=<?php echo $row['id']; ?>"
            style="background: linear-gradient(135deg, #e85a27, #bb4c18); 
        color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none;">📁 بيانات المريض</a>
    </div>

    <div class="nav">
        <?php

        $critical_class = ($row['is_critical'] == 1) ? 'critical-blink' : '';
        ?>

        <a href="mark_critical.php?id=<?= $id ?>"
            class="icon-btn warning-icon <?= $critical_class ?>"
            data-title="تعليم كمريض حرج">
            🚨
        </a>

        <a href="#" class="icon-btn followup-btn"
            data-title="إضافة متابعة"
           onclick="openFollowup(event)">
            📌
        </a>

        <a href="visits.php"
            class="icon-btn visits-icon"
            data-title="زيارات اليوم">
            🏥 </a>

        <a href="main.php"
            class="icon-btn home-icon"
            data-title="الصفحة الرئيسية">
            🏠 </a>

        <a href="treatment.php?patient_id=<?php echo $row['id']; ?>"
            class="icon-btn recipe-btn"
            data-title="وصفة العلاج">
            💊
        </a>
    </div>


    <div class="previous_data">
        <div class="previous_visits">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Delete</th>
                        <th>Edit</th>
                        <th>Previous Visits Notes</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات الزيارات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];

                        $select_query = "SELECT * FROM patient_visits WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($visit_row = mysqli_fetch_assoc($result)) {

                            echo "<tr>";
                            echo "<td><a class='icon-btn delete-icon' 
                                    href='delete-visit.php?id_delete=" . $visit_row['id'] . "'
                                    onclick=\"return confirm('هل أنت متأكد من حذف هذه الزيارة؟');\">
                                    🗑️
                                    </a>
                                    </td>";

                            echo "<td>
                                    <button class='icon-btn edit-icon edit-btn'                                           
                                            data-note='" . htmlspecialchars($visit_row['notes'], ENT_QUOTES, 'UTF-8') . "'
                                            data-id='" . $visit_row['id'] . "'>
                                    ✏️
                                    </button>
                                    </td>";

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
                    <!-- بيانات فحص النظر السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM va WHERE patient_id = $id ORDER BY exam_date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($va_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>
                                    <a class='icon-btn edit-icon'                                   
                                    href='edit-va.php?id_edit=" . $va_row['va_id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
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
                        <th>Delete</th>
                        <th>Edit</th>
                        <th>Notes</th>
                        <th>IOL</th>
                        <th>Type of Surgery</th>
                        <th>Eye</th>
                        <th>Date</th>

                    </tr>
                </thead>
                <tbody>
                    <!-- بيانات العمليات السابقة ستُضاف هنا -->

                    <?php
                    include 'config.php';
                    if (isset($_GET['id'])) {
                        $id = $_GET['id'];
                        $select_query = "SELECT * FROM surgery
                        WHERE patient_id = $id ORDER BY date DESC";
                        $result = mysqli_query($con, $select_query);
                        while ($surgery_row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>
                                    <a class='icon-btn delete-icon'
                                    href='delete-surgery.php?id_delete=" . $surgery_row['id'] . "'
                                    onclick=\"return confirm('هل أنت متأكد من حذف هذه العملية؟');\">
                                    🗑️
                                    </a>
                                    </td>";

                            echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-surgery.php?id_edit=" . $surgery_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";

                            echo "<td class='surgery-notes'>" . htmlspecialchars($surgery_row['notes']) . "</td>";
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
                            echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-laser.php?id_edit=" . $laser_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
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
                            echo "<td>
                                    <a class='icon-btn edit-icon'
                                    href='edit-injection.php?id_edit=" . $injection_row['id'] . "'>
                                    ✏️
                                    </a>
                                    </td>";
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

                <button type="submit" id="add_visit" name="add_visit"> 📝 إضافة زيارة</button>
            </form>


        </div>
    </div>

    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const notes = this.dataset.note;
                const visitId = this.dataset.id;

                document.getElementById('notes').value = notes;
                document.getElementById('id').value = visitId;


                document.getElementById('add_visit').innerText = 'تحديث الزيارة';

            });
        });
    </script>

    <script>
        window.onload = function() {
            const notes = document.getElementById('notes');
            const visitId = document.getElementById('id');

            if (notes) notes.value = '';
            if (visitId) visitId.value = '';

            const btn = document.getElementById('add_visit');
            if (btn) btn.innerText = ' 📝 إضافة زيارة';
        };
    </script>

<div id="followupModal" class="modal">

    <div class="modal-content">

        <span class="close-btn" onclick="closeFollowup()">×</span>

        <h3>📅 تحديد موعد المراجعة</h3>

        <form method="POST" action="save_followup.php?id=<?php echo $row['id']; ?>">

            <input type="hidden" name="patient_id" value="<?php echo $row['id']; ?>">

            <label>تاريخ المراجعة</label>
            <input type="date" name="followup_date" required>

            <label>سبب المراجعة</label>
            <input type="text" name="followup_reason" placeholder="مثال: مراجعة ضغط العين">

            <button type="submit">💾 حفظ المتابعة</button>

        </form>

    </div>

</div>

<script>

function openFollowup(e){
    e.preventDefault();
    document.getElementById("followupModal").style.display = "block";
}

function closeFollowup(){
    document.getElementById("followupModal").style.display = "none";
}

/* اغلاق عند الضغط خارج الصندوق */

window.onclick = function(event){

    let modal = document.getElementById("followupModal");

    if(event.target == modal){
        modal.style.display = "none";
    }

}

</script>

</body>
<div class="links">
    <a href="surgery-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد عملية</a>
    <a href="laser-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد ليزر</a>
    <a href="injection-appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>">موعد حقن</a>
    <a href="add-va.php?id=<?php echo htmlspecialchars($row['id']); ?>">اضافة فحص النظر</a>
    <a href="show-image.php?id=<?php echo htmlspecialchars($row['id']); ?>"> عرض الصور</a>
</div>

</html>