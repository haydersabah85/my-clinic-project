<?php
include 'config.php';

$patient_id = intval($_GET['id']);
$patient_query = "SELECT full_name FROM add_patient WHERE id = $patient_id";
$patient_result = mysqli_query($con, $patient_query);
$patient_name = '';
if ($patient_result && mysqli_num_rows($patient_result) > 0) {
    $patient_row = mysqli_fetch_assoc($patient_result);
    $patient_name = $patient_row['full_name'];
} else {
    echo "المريض غير موجود.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<style>

   body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f5f7fa;
    color: #333;
}

h2 {
    color: #2c3e50;
    text-align: center;
    font-size: 28px;
    margin-bottom: 25px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
}

form {
    max-width: 550px;
    margin: 0 auto;
    background: #ffffff;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

form:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #34495e;
}

input[type="text"], input[type="file"], select {
    width: 95%;
    padding: 10px 12px;
    margin-bottom: 20px;
    border: 1px solid #bdc3c7;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="text"]:focus, input[type="file"]:focus, select:focus {
    border-color: #3498db;
    box-shadow: 0 0 6px rgba(52,152,219,0.3);
    outline: none;
}

#upload_button {
    background-color: #3498db;
    color: white;
    padding: 12px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

#upload_button:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

#image_preview {
    display: block;
    margin: 15px auto;
    max-width: 100%;
    max-height: 250px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

#image_preview:hover {
    transform: scale(1.03);
}


</style>

<body>
    <h2>رفع صورة للمريض</h2>
<form action="add-image2.php?id=<?php echo urlencode($patient_id); ?>" method="post" enctype="multipart/form-data">
    <label>اسم المريض:</label>
    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" >
    <input type="text" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>" readonly>
   
    <br><br>

    <label>اختر الصورة:</label>
    <input type="file" name="retina_image" accept="image/*" required>
    <img id="image_preview" src="#" alt="معاينة الصورة" style="display:none;">
    <br><br>

    <label>ملاحظات:</label>
    <input type="text" name="notes">
    <br><br>

    <button type="submit" id="upload_button">رفع الصورة</button>


</form>
<script>
document.getElementById('retina_image').addEventListener('change', function(event){
    const preview = document.getElementById('image_preview');
    const file = event.target.files[0];
    
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }
});
</script>



</body>
</html>