<?php
include 'config.php';

include 'auth.php';
if (!isset($_GET['id'])) {
    die("No ID provided");
}

$id = intval($_GET['id']);

$stmt = $con->prepare("
    SELECT 
        injection_appointment.id AS id,
        injection_appointment.injection_type,
        injection_appointment.eye,
        injection_appointment.phone,
        injection_appointment.phone_alt,
        injection_appointment.date,
        injection_appointment.notes,
        injection_appointment.patient_id,
        add_patient.full_name
    FROM injection_appointment
    JOIN add_patient ON injection_appointment.patient_id = add_patient.id
    WHERE injection_appointment.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("No record found");
}

$row_injection = $result->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل موعد الحقن</title>
</head>


<style>

/* ================= THEME VARIABLES ================= */
:root {
  --bg: #eef2ff;
  --card: #ffffff;
  --text: #0f172a;
  --title: #020617;
  --border: #c7d2fe;

  --primary: #4338ca;     /* Indigo قوي */
  --primary-hover: #3730a3;

  --success: #16a34a;
  --success-hover: #15803d;

  --danger: #dc2626;
  --danger-hover: #b91c1c;

  --radius: 16px;
  --shadow: 0 20px 45px rgba(0,0,0,0.15);
}

/* ================= DARK MODE ================= */
body.dark {
  --bg: #020617;
  --card: #0b1220;
  --text: #e5e7eb;
  --title: #f8fafc;
  --border: #1e293b;
}

/* ================= GLOBAL ================= */
body {
  font-family: "Segoe UI", Arial, sans-serif;
  direction: rtl;
  text-align: right;
  margin: 20px;
  background: linear-gradient(135deg, var(--primary), var(--bg) 40%);
  color: var(--text);
  transition: background 0.4s, color 0.4s;
}

/* ================= TITLES ================= */
h1, h2 {
  text-align: center;
  font-weight: 900;
  color: var(--title);
  margin-bottom: 25px;
  letter-spacing: 0.5px;
}

h1::after,
h2::after {
  content: "";
  display: block;
  width: 80px;
  height: 4px;
  margin: 12px auto 0;
  background: linear-gradient(90deg, var(--success), var(--primary));
  border-radius: 10px;
}

/* ================= FORM CARD ================= */
form {
  max-width: 520px;
  margin: auto;
  background: linear-gradient(145deg, var(--card), #f1f5ff);
  padding: 35px;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  animation: slideUp 0.7s ease both;
}

body.dark form {
  background: linear-gradient(145deg, var(--card), #020617);
}

/* ================= LABELS ================= */
label {
  display: block;
  margin-bottom: 6px;
  font-weight: 800;
  color: var(--title);
}

/* ================= INPUTS ================= */
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
  background: rgba(255,255,255,0.7);
  color: var(--text);
  font-size: 15px;
  transition: all 0.35s ease;
}

body.dark input,
body.dark select,
body.dark textarea {
  background: rgba(2,6,23,0.6);
}

/* Focus Effect */
input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(67,56,202,0.35);
  transform: scale(1.02);
}

/* ================= SUBMIT BUTTON ================= */
input[type="submit"] {
  background: linear-gradient(135deg, var(--success), #22c55e);
  color: #fff;
  padding: 14px 28px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  font-weight: 900;
  font-size: 16px;
  letter-spacing: 0.5px;
  box-shadow: 0 15px 30px rgba(22,163,74,0.45);
  transition: all 0.35s ease;
}

input[type="submit"]:hover {
  background: linear-gradient(135deg, var(--success-hover), var(--success));
  transform: translateY(-4px) scale(1.05);
}

/* ================= EXIT BUTTON ================= */
#exit {
  background: linear-gradient(135deg, var(--danger), #ef4444);
  color: #fff;
  padding: 14px 26px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  margin-left: 10px;
  width: 90px;
  position: relative;
  right: 53%;
  font-weight: 900;
  letter-spacing: 0.5px;
  box-shadow: 0 15px 30px rgba(220,38,38,0.45);
  transition: all 0.35s ease;
}

#exit:hover {
  background: linear-gradient(135deg, var(--danger-hover), var(--danger));
  transform: translateY(-4px) scale(1.05);
}

/* ================= ANIMATION ================= */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(35px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}


</style>
<body>

    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>تعديل موعد الحقن</h2>
    <form action="edit-injection-appointment2.php?id_update=<?php echo $row_injection['id']; ?>" method="POST">
        
    <label for="name">الاسم الكامل:</label><br>
        <input type="hidden" name="id" value="<?php echo $row_injection['id'] ?>">
        <input type="hidden" name="patient_id" value="<?php echo $row_injection['patient_id'] ?>">
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row_injection['full_name']); ?>"><br><br>

        <label for="injection_type">نوع الحقن:</label><br>
        <select id="injection_type" name="injection_type" required>
            <option value="">اختر نوع الحقن</option>

        <?php
        $injection_types = ["Avastin", "Eylea 2mg", "Vabysmo", "Eylea 8mg", "Triamcinolone", "Lucentis"];
        foreach ($injection_types as $type) {
            $selected = ($row_injection['injection_type'] == $type) ? "selected" : "";
            echo "<option value='$type' $selected>$type</option>";
        }
        ?>


        </select><br><br>



        <label for="eye">العين:</label><br>
        <select id="eye" name="eye" required>
            <option value="">اختر العين</option>
            <?php
        $eyes = ["OD", "OS", "OU"];
        foreach ($eyes as $eye) {
            $selected = ($row_injection['eye'] == $eye) ? "selected" : "";
            echo "<option value='$eye' $selected>$eye</option>";
        }
        ?>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($row_injection['phone']); ?>" pattern="[0-9]+" placeholder="07xxxxxxxxx" required>
        
        <input type="text" id="phone_alt" name="phone_alt" value="<?php echo htmlspecialchars($row_injection['phone_alt']) ?>" pattern="[0-9]+" placeholder="رقم هاتف بديل">
        
        <br><br>

        <label for="date">موعد الحقن:</label><br>
        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($row_injection['date']) ?>" required><br><br>



        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"><?php echo htmlspecialchars($row_injection['notes']) ?></textarea><br><br>

        <input type="submit" name="edit_injection_appointment" value="تعديل الموعد">
        <button type="button" id="exit" onclick="window.location.href='operation-by-date.php';">خروج</button>
    </form>
</body>

</html>