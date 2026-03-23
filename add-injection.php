<?php
include 'config.php';

include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $select_query = "SELECT * FROM add_patient WHERE id = $id";


    $result = mysqli_query($con, $select_query);
    $row = mysqli_fetch_assoc($result);
}

?>


<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8" />
    <title>عيادة الدكتور حيدر صباح الربيعي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="assets/style.css" />
    <script src="assets/theme.js" defer></script>
</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

    body {
        font-family: 'Cairo', sans-serif;
        background: #f4f6f8;
        margin: 20px;
        padding: 0;
        direction: rtl;
    }

    /* ====== HEADERS ====== */
    h2 {
        text-align: center;
        color: #1976d2;
        margin: 20px 0;
    }

    h3 {
        text-align: center;
        color: #37474f;
        margin: 25px 0 10px;
        font-size: 20px;
        border-bottom: 2px solid #1976d2;
        display: inline-block;
        padding-bottom: 5px;
    }

    /* ====== PATIENT INFO ====== */
    .patient_info {
        background: #ffffff;
        border-radius: 12px;
        padding: 15px;
        margin: 20px auto;
        max-width: 85%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
    }

    .patient_info p {
        margin: 0 10px;
        font-size: 16px;
    }

    .patient_info span {
        font-weight: bold;
    }

    .patient_info a {
        background: #1976d2;
        color: #fff;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        transition: .3s;
    }

    .patient_info a:hover {
        background: #0d47a1;
    }

    /* ====== FORMS GENERAL ====== */
    form {
        max-width: 900px;
        margin: 20px auto;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, .15);
        direction: ltr;
        background: #f1f8e9;
    }

    .injection_info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
        align-items: center;
        padding: 10px 0;

    }

    /* ====== LABELS ====== */
    label {
        font-size: 16px;
        color: #37474f;
        font-weight: 600;
    }

    /* ====== INPUTS ====== */
    select {
        background: whitesmoke;
        text-align: center;
    }

    select,
    input[type="date"],
    textarea {
        width: 100%;
        border-radius: 8px;
        border: 1px solid #ccc;
        padding: 8px;
        font-size: 14px;
        font-family: 'Cairo', sans-serif;
    }

    textarea {
        resize: vertical;
        min-height: 80px;
        direction: ltr;
        width: 90%;
    }

    /* ====== BUTTON ====== */
    button {
        background: #2e7d32;
        color: #fff;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        font-size: 15px;
        cursor: pointer;
        transition: .3s;
        margin-top: 10px;
        float: left;
    }

    button:hover {
        background: #1b5e20;
    }

    /* ====== RESPONSIVE ====== */
    @media(max-width:1024px) {

        .surgical_info,
        .injection_info,
        .laser_info {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }

    @media(max-width:768px) {
        .patient_info {
            flex-direction: column;
            gap: 10px;
        }
    }

    @media(max-width:480px) {
        form {
            padding: 15px;

        }
    }

    /* ================= DARK THEME ================= */
    body[data-theme="dark"] {
        background: #121212;
        color: #e0e0e0;
    }

    /* HEADERS */
    body[data-theme="dark"] h2 {
        color: #90caf9;
    }

    body[data-theme="dark"] h3 {
        color: #e0e0e0;
        border-bottom-color: #90caf9;
    }

    /* PATIENT INFO */
    body[data-theme="dark"] .patient_info {
        background: #1e1e1e;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .6);
    }

    body[data-theme="dark"] .patient_info p {
        color: #e0e0e0;
    }

    body[data-theme="dark"] .patient_info span {
        color: #90caf9;
    }

    body[data-theme="dark"] .patient_info a {
        background: #1565c0;
    }

    body[data-theme="dark"] .patient_info a:hover {
        background: #0d47a1;
    }

    /* FORMS */
    body[data-theme="dark"] form {
        background: #1b1b1b;
        box-shadow: 0 6px 15px rgba(0, 0, 0, .7);
    }

    /* LABELS */
    body[data-theme="dark"] label {
        color: #cfd8dc;
    }

    /* INPUTS */
    body[data-theme="dark"] select,
    body[data-theme="dark"] input[type="date"],
    body[data-theme="dark"] textarea {
        background: #2a2a2a;
        border: 1px solid #444;
        color: #e0e0e0;
    }

    body[data-theme="dark"] select option {
        background: #2a2a2a;
        color: #e0e0e0;
    }

    /* BUTTON */
    body[data-theme="dark"] button {
        background: #388e3c;
    }

    body[data-theme="dark"] button:hover {
        background: #2e7d32;
    }

    /* SHADOW FIX */
    body[data-theme="dark"] * {
        box-shadow: 0 0 10px rgba(0, 0, 0, .8);
    }
</style>




<header>
    <h2>اضافة المعلومات الجراحية</h2>
</header>

<main>
    <div class="patient_info">
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
</main>

<h3>اضافة معلومات الحقن</h3>


<form class="injection" action="add-injection2.php" method="POST">
    <div class="injection_info">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <label for="eye">Choose which eye?</label>
        <select name="eye" id="eye" required>
            <ul>
                <option value="">اختر العين</option>
                <option value="OD">OD</option>
                <option value="OS">OS</option>
                <option value="OU">OU</option>
            </ul>
        </select>

        <label for="injection_type">Type Of Injection:</label>
        <select name="injection_type" id="injection_type" required>
            <ul>
                <option value="">اختر نوع الحقن</option>
                <option value="Avastin">Avastin</option>
                <option value="Triamcinolone">Triamcinolone</option>
                <option value="Eylea 2mg">Eylea 2mg</option>
                <option value="Vabysmo">Vabysmo</option>
                <option value="Eylea 8mg">Eylea 8mg</option>
                <option value="Lucentis">Lucentis</option>
                <option value="Ozurdix">Ozurdix</option>
            </ul>
        </select>

        <input type="date" required name="date" id="date" />
    </div>
    <label for="notes">Notes</label>
    <textarea
        name="notes"
        id="notes"
        cols="50"
        rows="5"
        placeholder="Extra Notes.."></textarea>

    <button id="injection_btn" name="injection_btn" type="submit">💉 Save</button>
</form>
</body>

</html>