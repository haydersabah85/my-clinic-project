<?php
include 'config.php';

include 'auth.php';
if (isset($_GET['id_edit'])) {
  $surgery_id = $_GET['id_edit'];

 $select_query ="
 SELECT 
 add_patient.id,
 add_patient.full_name,
  add_patient.age,
  surgery.id,
  add_patient.phone_no,
  surgery.eye,
  surgery.surgery_type,
  surgery.iol_type,
  surgery.notes,
  surgery.patient_id,
  surgery.date
 FROM surgery
  JOIN add_patient ON surgery.patient_id = add_patient.id
  WHERE surgery.id = '$surgery_id' ";
  $result = mysqli_query($con, $select_query); 
  $surgery_row = mysqli_fetch_assoc($result);
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث معلومات العملية</title>
</head>


<style>

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 20px;
}

.patient_info {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    direction: rtl;
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    gap: 10px;
    max-width: 75%; 
    margin: auto;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.patient_info p {
    margin: 10px 0;
}
.patient_info span {
    
    min-width: 100px;
    font-weight: bold;
}
.patient_info a {
    background-color: darkmagenta;
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 5px;
} 
.patient_info a:hover {
    background-color: #ba00ba;
    transform: scale(1.05);
    transition: background-color 0.3s, transform 0.3s;
} 
h3 {
    text-align: center;
    color: darkmagenta;
    margin-bottom: 20px;
}
.surgery {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    margin: auto;
   
} 
.surgery label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
} 
.surgery select,
.surgery input[type="date"],
.surgery textarea {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
} 
button#edit_surgery {
    background-color: darkmagenta;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
} 
button#edit_surgery:hover {
    background-color: #ba00ba;
    transform: scale(1.05);
    transition: background-color 0.3s, transform 0.3s;
} 



</style>


<body>
    
<main>
    <div class="patient_info">
 <p><span>ID:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($surgery_row['patient_id']); ?></span>
        </p>

        <p><span>الاسم:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($surgery_row['full_name']); ?></span>
        </p>

        <p><span>العمر:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($surgery_row['age']); ?></span>
        </p>

        <p><span>رقم الموبايل:</span>
            <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($surgery_row['phone_no']); ?></span>
        </p>
        <a href="edit-patient.php?id_edit=<?php echo $surgery_row['patient_id']; ?>">تعديل البيانات</a> 
  
  </div>
  </main>
  <h3>تحديث معلومات العملية</h3>

  <body>
    <form class="surgery" action="edit-surgery2.php?id_update=<?php echo $surgery_row['id']; ?>" method="POST">
      <div class="surgical_info">
        <input type="hidden" name="patient_id" value="<?php echo $surgery_row['patient_id']; ?>">
        <input type="hidden" name="id" value="<?php echo $surgery_row['id']; ?>">
        <label for="eye">Choose which eye?</label>
        <select name="eye" id="eye" required>
          <ul>
            <option value="">اختر العين</option>
           <?php
            $eyes = ['OD', 'OS', 'OU'];
            foreach ($eyes as $eye) {
                $selected = ($surgery_row['eye'] === $eye) ? 'selected' : '';
                echo "<option value=\"$eye\" $selected>$eye</option>";
            }
            ?>
          </ul>
        </select>

        <label for="surgery_type">Type Of Surgery:</label>
        <select name="surgery_type" id="surgery_type" required>
          <ul>
            <option value="">اختر نوع العملية</option>

            <?php
            $surgery_types = [ 'Phaco', 'Vitrectomy', 'Phaco and Vitrectomy', 'SOR',
             'Phaco and SOR', 'Squint', 'EUA', 'Probing', 'SMILE', 'PRK', 'Secondary IOL', 
             'Pterygium with Graft', 'Pterygium'];
            foreach ($surgery_types as $type) {
                $selected = ($surgery_row['surgery_type'] === $type) ? 'selected' : '';
                echo "<option value=\"$type\" $selected>$type</option>";
            } 
            ?>
          </ul>
        </select>

        <label for="iol_type">IOL Type:</label>
        <select name="iol_type" id="iol_type" >
          <ul>
            <option value="">اختر نوع العدسة</option>
            <?php
            $iol_types = [  'Sensar', 'Eyhance', 'Alcon', 'Clareon', 
              'Synergy', 'Rayner Monofocal', 'Rayner Trifocal', 'Eleon'];
            foreach ($iol_types as $iol) {
                $selected = ($surgery_row['iol_type'] === $iol) ? 'selected' : '';
                echo "<option value=\"$iol\" $selected>$iol</option>";
            }
            ?>
          </ul>
        </select>

        <label for="date">التأريخ</label>
        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($surgery_row['date']) ?>">
      </div>
      <label for="notes" id="notes">Notes</label>

      <textarea
        name="notes"
        id="notes"
        cols="50"
        rows="5"
        placeholder="Extra Notes.." ><?php echo htmlspecialchars($surgery_row['notes']); ?></textarea>

      <button id="edit_surgery" name="edit_surgery" type="submit">Update</button>
    </form>

</body>
</html>