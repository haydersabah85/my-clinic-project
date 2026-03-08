<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id_edit'])) {
    $laser_id = $_GET['id_edit'];

    $select_query = "
    SELECT
        add_patient.id,
        add_patient.full_name,
        add_patient.age,
        laser.id,
        add_patient.phone_no,
        laser.eye,
        laser.laser_type,
        laser.notes,
        laser.date,
        laser.patient_id
        FROM laser
        JOIN add_patient ON laser.patient_id = add_patient.id
        WHERE laser.id = '$laser_id' ";
    $result = mysqli_query($con, $select_query);
    $laser_row = mysqli_fetch_assoc($result);
}


?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث معلومات الليزر</title>
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
    }

    .patient_info a {
        display: inline-block;
        margin-top: 10px;
        text-decoration: none;
        color: #fff;
        background-color: darkmagenta;
        padding: 8px 12px;
        border-radius: 4px;
    }

    .patient_info a:hover {
        background-color: #ba00ba;
    }

    h3 {
        text-align: center;
        color: darkmagenta;
    }

    form.laser {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 0 auto;

    }

    form.laser label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }

    form.laser select,
    form.laser input[type="date"],
    form.laser textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    form.laser button {
        background-color: darkmagenta;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    form.laser button:hover {
        background-color: #ba00ba;
    }
</style>


<body>

    <main>
        <div class="patient_info">
            <p><span>ID:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($laser_row['patient_id']); ?></span>
            </p>

            <p><span>الاسم:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($laser_row['full_name']); ?></span>
            </p>

            <p><span>العمر:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($laser_row['age']); ?></span>
            </p>

            <p><span>رقم الموبايل:</span>
                <span style="color:darkmagenta; font-weight: bold; "><?php echo htmlspecialchars($laser_row['phone_no']); ?></span>
            </p>
            <a href="edit-patient.php?id_edit=<?php echo $laser_row['patient_id']; ?>">تعديل البيانات</a>

        </div>
    </main>

    <h3>تحديث معلومات الليزر</h3>
    <form class="laser" action="edit-laser2.php?id_update=<?php echo $laser_row['id'] ?>" method="POST">
        <div class="laser_info">
            <input type="hidden" name="id" value="<?php echo $laser_row['id']; ?>">
            <input type="hidden" name="patient_id" value="<?php echo $laser_row['patient_id']; ?>">
            <label for="eye">Choose which eye?</label>
            <select name="eye" id="eye" required>
                <ul>
                    <option value="">اختر العين</option>
                    <?php
                    $eyes = ['OD', 'OS', 'OU'];
                    foreach ($eyes as $eye_option) {
                        $selected = ($laser_row['eye'] === $eye_option) ? 'selected' : '';
                        echo "<option value='$eye_option' $selected>$eye_option</option>";
                    }
                    ?>
                </ul>
            </select>

            <label for="laser_type">Type Of Laser:</label>
            <select name="laser_type" id="laser_type" required>
                <ul>
                    <option value="">اختر نوع الليزر</option>
                    <?php
                    $laser_types = ['PRP', 'Retinopexy', 'Yag', 'Focal Laser', 'PI'];
                    foreach ($laser_types as $type) {
                        $selected = ($laser_row['laser_type'] === $type) ? 'selected' : '';
                        echo "<option value='$type' $selected>$type</option>";
                    }   
                    ?>
                  
                </ul>
            </select>

            <label for="date">التأريخ</label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($laser_row['date']) ?>">
        </div>
        <label for="notes">Notes</label>
        <textarea
            name="notes"
            id="notes"
            cols="50"
            rows="5"
            placeholder="Extra Notes.."><?php echo htmlspecialchars($laser_row['notes']); ?></textarea>
        <button id="edit_laser" name="edit_laser" type="submit">Update</button>

</body>

</html>