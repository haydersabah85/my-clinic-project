<?php
include 'config.php';
include 'auth.php';

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
} else {
    die("خطأ: لم يتم تحديد المريض");
}

$patient = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM add_patient WHERE id = '$patient_id'"));
if (!$patient) {
    die("خطأ: المريض غير موجود");
}


$previous_medicines = mysqli_query($con, "
SELECT 
    p.id as prescription_id,
    p.prescription_date,
    p.diagnosis,
    m.medicine_name,
    m.medicine_form,
    pi.dose,
    pi.frequency,
    pi.duration,
    pi.eye
FROM prescriptions p
JOIN prescription_items pi ON p.id = pi.prescription_id
JOIN medicines m ON pi.medicine_id = m.id
WHERE p.patient_id = $patient_id 
ORDER BY p.prescription_date DESC
LIMIT 20
");



?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>وصفة طبية</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/theme.js" defer></script>
</head>

    <style>
        [data-theme="dark"] {
            background: #121212;
            color: #e0e0e0;
        }
        .previous-table {
            margin-top: 25px;
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .previous-table th {
            background: #343a40;
            color: #fff;
            padding: 8px;
        }

        .previous-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }

        .previous-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .previous-table tr:hover {
            background: #e9ecef;
        }

        .prescription-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ddd;
        }

        .medicine-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .medicine-row select,
        .medicine-row input {
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .add-btn {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }
        .add-btn:hover {
            background: #218838;
        }

        .remove-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 8px;
            border-radius: 6px;
            cursor: pointer;
        }
         
           
            center {
            margin: 10px 0;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            gap: 30px;

            }

        a {
            display: inline-block;
            margin-bottom: 10px;
            color: #007bff;
            text-decoration: none;
        }

      
    </style>


<body>

    <div class="prescription-box">

        <h2>إنشاء وصفة طبية</h2>
        
        <center>
            <a href="main.php">العودة إلى الصفحة الرئيسية</a>
            <a href="common-medicines.php">الأدوية الأكثر استعمالًا</a>
        </center>

        <form method="POST" action="save_prescription.php">

            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">


            <label>التشخيص:</label>
            <textarea name="diagnosis" rows="2" style="width:100%;"></textarea>

            <hr>

            <h3>الأدوية</h3>

            <div id="medicines-container">

                <div class="medicine-row">

                    <select name="medicine_id[]">
                        <option value="">اختر دواء</option>
                        <?php
                        $q = mysqli_query($con, "SELECT * FROM medicines");
                        while ($m = mysqli_fetch_assoc($q)) {
                            echo "<option value='{$m['id']}'>{$m['medicine_name']}  {$m['medicine_form']}</option>";
                        }
                        ?>
                    </select>

                    <input type="text" name="frequency[]" placeholder="عدد المرات">
                    <input type="text" name="dose[]" placeholder="الجرعة">
                    <input type="text" name="duration[]" placeholder="المدة">
                    <select name="eye[]">
                        <option value="">العين</option>
                        <option value="right">العين اليمنى</option>
                        <option value="left">العين اليسرى</option>
                        <option value="both">العينين</option>
                    </select>
                    <input type="text" name="instructions[]" placeholder="ملاحظات">

                    <button type="button" class="remove-btn" onclick="removeRow(this)">✖</button>

                </div>

            </div>

            <button type="button" class="add-btn" onclick="addMedicine()">+ إضافة دواء</button>

            <hr>

            <button type="submit" class="add-btn">💾 عرض الوصفة</button>
            

        </form>
    </div>

    <hr>

    <h3>📂 الأدوية الموصوفة سابقاً</h3>

    <table class="previous-table">
        <tr>
            <th>Date</th>
            <th>Medicine</th>
            <th>Frequency</th>
            <th>Dose</th>
            <th>Eye</th>
            <th>Duration</th>
            <th>Diagnosis</th>
            <th>View</th>
        </tr>

        <?php
      
        if (mysqli_num_rows($previous_medicines) > 0) {
            while ($row = mysqli_fetch_assoc($previous_medicines)) {

       
        ?>
                <tr>
                    <td><?php echo $row['prescription_date'] ?? '-'; ?></td>
                    <td><?php echo $row['medicine_name'] . '  ' . $row['medicine_form']; ?></td>
                    <td><?php echo $row['frequency']; ?></td>
                    <td><?php echo $row['dose']; ?></td>
                    <td><?php echo $row['eye']; ?></td>
                    <td><?php echo $row['duration']; ?></td>
                    <td><?php echo $row['diagnosis']; ?></td>
                    <td>
                        <a href="view_prescription.php?id=<?php echo $row['prescription_id']; ?>">
                            <button style="background:#007bff;color:#fff;padding:6px 10px;border:none;border-radius:6px;">
                                عرض الوصفة
                            </button>
                        </a>
                <?php
            }
        } else {
            echo "<tr><td colspan='8'>لا توجد وصفات سابقة</td></tr>";
        }
                ?>
    </table>

    <script>
        function addMedicine() {
            let container = document.getElementById("medicines-container");
            let firstRow = container.querySelector(".medicine-row");
            let newRow = firstRow.cloneNode(true);

            newRow.querySelectorAll("input").forEach(input => input.value = "");
            newRow.querySelectorAll("select").forEach(select => select.value = "");

            container.appendChild(newRow);
        }

        function removeRow(btn) {
            let rows = document.querySelectorAll(".medicine-row");
            if (rows.length > 1) {
                btn.parentElement.remove();
            }
        }
    </script>

</body>

</html>