<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id_edit'])) {
  $va_id = $_GET['id_edit'];

  $select_va = "SELECT * FROM va WHERE va_id=$va_id";
  $va_result = mysqli_query($con, $select_va);
  $va_row = mysqli_fetch_assoc($va_result);
  $patient_id = $va_row['patient_id'];
  $select_patient = "SELECT * FROM add_patient WHERE id=$patient_id";
  $patient_result = mysqli_query($con, $select_patient);
  $row = mysqli_fetch_assoc($patient_result);


  

}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>تعديل فحص النظر</title>
</head>


<style>
 
/* ================== Global ================== */
* {
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #e8f6fb, #f4fbff);
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    margin: 0;
    color: #2c3e50;
}

/* ================== Title ================== */
h1 {
    color: #0d9b57;
    margin: 25px 0 15px;
    text-align: center;
    font-size: 30px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.15);
    animation: fadeDown 0.8s ease;
}

/* ================== Patient Info ================== */
.patient-info {
    background: #ffffff;
    margin: 20px auto;
    padding: 15px 20px;
    border-radius: 16px;
    max-width: 80%;
    font-size: 17px;
    display: flex;
    justify-content: space-between;
    gap: 15px;
    direction: rtl;
    box-shadow: 0 8px 18px rgba(0,0,0,0.15);
    animation: fadeUp 0.9s ease;
}

.patient-info p {
    margin: 5px 0;
    font-weight: 600;
}

/* ================== VA Card ================== */
.add-va {
    background: linear-gradient(135deg, #ffffff, #f2fbf7);
    margin: 25px auto;
    padding: 18px 20px 60px;
    border-radius: 20px;
    max-width: 80%;
    font-size: 18px;
    box-shadow: 0 12px 26px rgba(0,0,0,0.18);
    animation: fadeUp 1s ease;
}

/* ================== OU Layout ================== */
.ou {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 15px;
    flex-wrap: wrap;
}

/* ================== OD / OS Cards ================== */
.od,
.os {
    width: 48%;
    padding: 15px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f9fffd, #ecf8f3);
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
}

.od:hover,
.os:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.2);
}

/* ================== Section Titles ================== */
h3 {
    background: linear-gradient(135deg, #0d9b57, #1ab97a);
    color: white;
    padding: 10px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 12px;
    font-size: 22px;
}

/* أيقونات OD / OS */
.od h3::before,
.os h3::before {
    content: "👁️ ";
}

/* ================== Inputs ================== */
.od-info,
.os-info {
    margin: 12px 0;
    padding: 5px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.od-info label,
.os-info label {
    font-size: 17px;
    font-weight: 600;
}

/* أيقونة للقياس */
.od-info label::before,
.os-info label::before {
    content: "📏 ";
}

.od-info input,
.os-info input {
    width: 200px;
    text-align: center;
    font-size: 17px;
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 6px;
    transition: all 0.25s ease;
}

/* Focus effect */
.od-info input:focus,
.os-info input:focus {
    outline: none;
    border-color: #0d9b57;
    box-shadow: 0 0 0 3px rgba(13,155,87,0.25);
    background: #f9fffc;
}

/* ================== Update Button ================== */
#update_va {
    background: linear-gradient(135deg, #0d9b57, #1ab97a);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 20px;
    font-size: 16px;
    cursor: pointer;
    margin: 40px auto 0;
    display: block;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 6px 14px rgba(0,0,0,0.35);
}

#update_va::before {
    content: "💾 ";
}

#update_va:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.4);
}

/* ================== Animations ================== */
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ================== Responsive ================== */
@media (max-width: 768px) {

    .patient-info,
    .add-va {
        max-width: 92%;
    }

    .od,
    .os {
        width: 100%;
    }

    .od-info input,
    .os-info input {
        width: 100px;
    }

    h1 {
        font-size: 24px;
    }
}


</style>

<body>
  <h1>اضافة فحص النظر</h1>

  <div class="patient-info">
    <p><span>الرقم التسلسلي:</span>
      <span><?php echo htmlspecialchars($row['id']); ?></span>
    </p>

    <p><span>الاسم:</span>
      <span><?php echo htmlspecialchars($row['full_name']); ?></span>
    </p>

    <p><span>العمر:</span>
      <span><?php echo htmlspecialchars($row['age']); ?></span>
    </p>

  </div>
  <form class="add-va" action="edit-va2.php?id_update=<?php echo htmlspecialchars($va_row['va_id']); ?>" method="post" >
    
    <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($row['id']); ?>">
    <div class="ou">
      <div class="od">
        <h3>OD</h3>
        <div class="od-info">
          <label for="va_od">UCVA:</label>
          <input type="text" id="va_od" name="va_od" value="<?php echo htmlspecialchars($va_row['va_od']); ?>" />
        </div>

        <div class="od-info">
          <label for="bcva_od">BCVA:</label>
          <input type="text" id="bcva_od" name="bcva_od" value="<?php echo htmlspecialchars($va_row['bcva_od']); ?>" />
        </div>

        <div class="od-info">
          <label for="old_glasses_od">Old Glasses:</label>
          <input type="text" id="old_glasses_od" name="old_glasses_od" value="<?php echo htmlspecialchars($va_row['old_glasses_od']); ?>" />
        </div>

        <div class="od-info">
          <label for="ref_od">New Refraction:</label>
          <input type="text" id="ref_od" name="ref_od" value="<?php echo htmlspecialchars($va_row['ref_od']); ?>" />
        </div>
      </div>

      <div class="os">
        <h3>OS</h3>
        <div class="os-info">
          <label for="va_os">UCVA:</label>
          <input type="text" id="va_os" name="va_os" value="<?php echo htmlspecialchars($va_row['va_os']); ?>" />
        </div>

        <div class="os-info">
          <label for="bcva_os">BCVA:</label>
          <input type="text" id="bcva_os" name="bcva_os" value="<?php echo htmlspecialchars($va_row['bcva_os']); ?>" />
        </div>

        <div class="os-info">
          <label for="old_glasses_os">Old Glasses:</label>
          <input type="text" id="old_glasses_os" name="old_glasses_os" value="<?php echo htmlspecialchars($va_row['old_glasses_os']); ?>" />
        </div>

        <div class="os-info">
          <label for="ref_os">New Refraction:</label>
          <input type="text" id="ref_os" name="ref_os" value="<?php echo htmlspecialchars($va_row['ref_os']); ?>" />
        </div>
      </div>
    </div>
   
    <button type="submit" name="update_va" id="update_va">تعديل</button>
  </form>
</body>

</html>