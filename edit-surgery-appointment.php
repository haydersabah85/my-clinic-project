
<?php
include 'config.php';

include 'auth.php';

if (!isset($_GET['id'])) {
    die("No ID provided");
}

$id = intval($_GET['id']);

$stmt = $con->prepare("
    SELECT 
        surgery_appointment.id AS id,
        surgery_appointment.surgery_type,
        surgery_appointment.eye,
        surgery_appointment.phone,
        surgery_appointment.phone_alt,
        surgery_appointment.date,
        surgery_appointment.notes,
        surgery_appointment.patient_id,
        add_patient.full_name
    FROM surgery_appointment
    JOIN add_patient ON surgery_appointment.patient_id = add_patient.id
    WHERE surgery_appointment.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No record found");
}

$row_surgery = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز موعد عملية</title>
</head>


<style>

   /* ================== THEME CORE ================== */
:root {
  --bg: #eef2ff;
  --card: #ffffff;
  --text: #0f172a;
  --title: #020617;
  --border: #c7d2fe;

  --primary: #4f46e5;   /* Indigo */
  --primary-glow: rgba(79,70,229,0.35);

  --success: #22c55e;
  --danger: #ef4444;

  --radius: 18px;
  --shadow: 0 25px 50px rgba(0,0,0,0.15);
}

/* ================== DARK MODE ================== */
body.dark {
  --bg: #020617;
  --card: #0b1220;
  --text: #e5e7eb;
  --title: #f8fafc;
  --border: #1e293b;

  --primary: #6366f1;
  --primary-glow: rgba(99,102,241,0.4);
}

/* ================== GLOBAL ================== */
body {
  font-family: "Segoe UI", Arial, sans-serif;
  direction: rtl;
  text-align: right;
  margin: 20px;
  background: radial-gradient(circle at top, var(--primary-glow), var(--bg) 45%);
  color: var(--text);
  transition: background 0.4s, color 0.4s;
}

/* ================== TITLES ================== */
h1, h2 {
  text-align: center;
  font-weight: 900;
  letter-spacing: 0.5px;
  color: var(--title);
  margin-bottom: 25px;
  position: relative;
}

h1::after, h2::after {
  content: "";
  width: 70px;
  height: 4px;
  background: linear-gradient(90deg, var(--primary), #22c55e);
  display: block;
  margin: 12px auto 0;
  border-radius: 10px;
}

/* ================== FORM CARD ================== */
form {
  max-width: 520px;
  margin: auto;
  background: linear-gradient(145deg, var(--card), #f1f5ff);
  padding: 35px;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  animation: floatIn 0.7s ease both;
}

body.dark form {
  background: linear-gradient(145deg, var(--card), #020617);
}

/* ================== LABELS ================== */
label {
  display: block;
  margin-bottom: 6px;
  font-weight: 800;
  color: var(--title);
}

/* ================== INPUTS ================== */
input[type="text"],
input[type="tel"],
input[type="date"],
select,
textarea {
  width: 92%;
  padding: 14px;
  margin-bottom: 18px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(6px);
  color: var(--text);
  font-size: 15px;
  transition: all 0.35s ease;
}

body.dark input,
body.dark select,
body.dark textarea {
  background: rgba(2,6,23,0.6);
}

input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow:
    0 0 0 4px var(--primary-glow),
    inset 0 0 0 1px var(--primary);
  transform: scale(1.02);
}

/* ================== SUBMIT BUTTON ================== */
input[type="submit"] {
  background: linear-gradient(135deg, var(--primary), #22c55e);
  color: white;
  padding: 14px 26px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  font-weight: 900;
  font-size: 16px;
  letter-spacing: 0.5px;
  box-shadow: 0 15px 30px var(--primary-glow);
  transition: all 0.35s ease;
}

input[type="submit"]:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 25px 45px var(--primary-glow);
}

/* ================== EXIT BUTTON ================== */
#exit {
  background: linear-gradient(135deg, var(--danger), #b91c1c);
  color: white;
  padding: 14px 26px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  width: 95px;
  position: relative;
  right: 53%;
  font-weight: 900;
  letter-spacing: 0.5px;
  box-shadow: 0 15px 30px rgba(239,68,68,0.35);
  transition: all 0.35s ease;
}

#exit:hover {
  transform: translateY(-4px) scale(1.05);
  box-shadow: 0 25px 45px rgba(239,68,68,0.5);
}

/* ================== ANIMATION ================== */
@keyframes floatIn {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}



</style>


<body>
    
    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>حجز موعد عملية</h2>
    <form action="edit-surgery-appointment2.php?id_update=<?php echo $row_surgery['id']; ?>" method="POST">
      <input type="hidden" name="id" value="<?php echo $row_surgery['id']; ?>">
        <input type="hidden" name="patient_id" value="<?php echo $row_surgery['patient_id'] ?>">
        <label for="name">الاسم الكامل:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row_surgery['full_name']); ?>"><br><br>

        <label for="surgery_type">نوع العملية:</label><br>
        <select id="surgery_type" name="surgery_type" required>
            <option value="">اختر نوع العملية</option>
            <?php
        $types = [
            "Phaco","Vitrectomy","Phaco and Vitrectomy","SOR",
            "Phaco and SOR","Squint","EUA","Probing","SMILE",
            "PRK","Secondary IOL","IOL Exchange","Anterior Vitrectomy"
        ];
        foreach ($types as $type) {
            $selected = ($row_surgery['surgery_type'] == $type) ? "selected" : "";
            echo "<option value='$type' $selected>$type</option>";
        }
        ?>
            
        </select><br><br>

        <label for="eye">العين:</label><br>
        <select id="eye" name="eye"  required>
           <option value="">اختر العين</option>
            <?php
        $eyes = ["OD", "OS", "OU"];
        foreach ($eyes as $eye) {
            $selected = ($row_surgery['eye'] == $eye) ? "selected" : "";
            echo "<option value='$eye' $selected>$eye</option>";
        }
        ?>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($row_surgery['phone']); ?>" pattern="[0-9]+" placeholder="07xxxxxxxxx" required>
        <input type="text" id="phone_alt" name="phone_alt" value="<?php echo htmlspecialchars($row_surgery['phone_alt']); ?>" pattern="[0-9]+" placeholder="رقم هاتف بديل">
        <br><br>

        <label for="date">موعد العملية:</label><br>
        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($row_surgery['date']); ?>"><br><br>
        

        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"><?php echo htmlspecialchars($row_surgery['notes']); ?></textarea><br><br>

        <input type="submit" name="edit_surgery_appointment" id="edit_surgery_appointment" value="تعديل الموعد">
        <button type="button" id="exit" onclick="window.location.href='operation-by-date.php';">خروج</button>
    </form>
</body>
</html>