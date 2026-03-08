<?php
include 'config.php';
include 'auth.php';
if (isset($_GET['id_medicine'])) {
    $id_medicine = $_GET['id_medicine'];
    $select = "SELECT * FROM medicines WHERE id = '$id_medicine'";
    $result = mysqli_query($con, $select);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
    } else {
        echo "Medicine not found.";
        exit;
    }
} else {
    echo "No medicine ID provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medicine</title>
</head>

<style>

.card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
    width: 400px;
    margin: auto;
}
.field {
    margin-bottom: 15px;
}
.field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.field input, .field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.btn:hover {
    background-color: #0056b3;
}


</style>


<body>
    <div class="card">
                <h2>تعديل دواء</h2>

                <form id="medicineForm" action="edit-medicine2.php?id_medicine=<?php echo $row['id']; ?>" method="post">
                    <div class="field">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <label for="medicine_name">Medicine Name</label>
                        <input type="text" name="medicine_name" id="medicine_name" required placeholder="مثال: Tobradex" value="<?php echo $row['medicine_name']; ?>">
                    </div>

                    <div class="field">
                        <label for="medicine_form">Drug Form</label>
                        <input type="text" name="medicine_form" id="medicine_form" placeholder="example: eye drops, ointment, tablet" value="<?php echo $row['medicine_form']; ?>">
                    </div>

                    <div class="field">
                        <label for="strength">Strength</label>
                        <input type="text" name="strength" id="strength" placeholder="example: 0.3% / 500mg" value="<?php echo $row['strength']; ?>">
                    </div>

                    <div class="field">
                        <label for="category"> Drug Category </label>
                        <select name="category" id="category" placeholder=" Drug Category ">
                            <option value=""> Choose Category </option>
                            antibiotic  and steroid
                            <option value="antibiotic">ِAntibiotic</option>
                            <option value="antibiotic and steroid">Antibiotic and Steroid</option>
                            <option value="nsaid">NSAID</option>
                            <option value="steroids">Steroids</option>
                            <option value="anti-Glaucoma">Anti-Glaucoma</option>
                            <option value="artificial-tears">Artificial Tears</option>
                            <option value="antiviral">Antiviral</option>
                            <option value="antiallergic">Antiallergic</option>
                            <option value="pain-killers">Pain Killers</option>
                            <option value="others">Others</option>
                        </select>
                    </div>

                    <button class="btn btn-primary" id="edit_medicine" name="edit_medicine" type="submit">Edit Medicine</button>
                </form>

               
            </div>
    
</body>
</html>

