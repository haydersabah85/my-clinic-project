<?php
include 'config.php';
include 'auth.php';

$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($patient_id <= 0) {
    echo "المريض غير موجود.";
    exit;
}

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
    <title>رفع صورة للمريض</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: linear-gradient(to bottom right, #eef2f7, #d6e0f0);
            color: #333;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            font-size: 30px;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        form {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        form:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #bdc3c7;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="file"]:focus,
        select:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
            outline: none;
        }

        #upload_button {
            background-color: #3498db;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 600;
            width: 100%;
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
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease;
        }

        #image_preview:hover {
            transform: scale(1.03);
        }

        .file-hint {
            margin-top: -10px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #5f6b7a;
        }

        .file-error {
            display: none;
            margin-top: -8px;
            margin-bottom: 12px;
            padding: 9px 11px;
            border-radius: 8px;
            background: #fdecec;
            color: #b42318;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
    <link rel="stylesheet" href="assets/dark-mode.css">
    <script src="assets/theme.js" defer></script>
</head>

<body>
    <h2>🖼️ رفع صورة للمريض</h2>
    <form id="upload_form" action="add-image2.php?id=<?php echo urlencode($patient_id); ?>" method="post" enctype="multipart/form-data">
        <label>اسم المريض:</label>
        <input type="hidden" name="id" value="<?php echo $patient_id; ?>">
        <input type="text" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>" readonly>

        <label>اختر الصورة:</label>
        <input type="file" name="retina_image" id="retina_image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" required>
        <p class="file-hint">الصيغ المسموح بها: JPG, JPEG, PNG, GIF, WEBP - الحد الأقصى: 10MB</p>
        <div id="file_error" class="file-error"></div>
        <img id="image_preview" src="#" alt="معاينة الصورة" style="display:none;">

        <label>ملاحظات:</label>
        <input type="text" name="notes">

        <button type="submit" id="upload_button">🖼️ رفع الصورة</button>
    </form>

    <script>
        const MAX_FILE_SIZE = 10 * 1024 * 1024;
        const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        const uploadForm = document.getElementById('upload_form');
        const imageInput = document.getElementById('retina_image');
        const preview = document.getElementById('image_preview');
        const errorBox = document.getElementById('file_error');

        function showFileError(message) {
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        }

        function clearFileError() {
            errorBox.textContent = '';
            errorBox.style.display = 'none';
        }

        function resetPreview() {
            preview.src = '#';
            preview.style.display = 'none';
        }

        function validateSelectedFile(file) {
            if (!file) {
                return {
                    valid: false,
                    message: 'يرجى اختيار صورة.'
                };
            }

            const parts = file.name.split('.');
            const extension = parts.length > 1 ? parts.pop().toLowerCase() : '';

            if (!ALLOWED_EXTENSIONS.includes(extension)) {
                return {
                    valid: false,
                    message: 'صيغة الصورة غير مدعومة. المسموح: JPG, JPEG, PNG, GIF, WEBP.'
                };
            }

            if (file.type && !ALLOWED_MIME_TYPES.includes(file.type)) {
                return {
                    valid: false,
                    message: 'نوع الملف غير صالح. يرجى اختيار صورة صحيحة.'
                };
            }

            if (file.size > MAX_FILE_SIZE) {
                return {
                    valid: false,
                    message: 'حجم الصورة كبير جداً. الحد الأقصى 10MB.'
                };
            }

            return {
                valid: true,
                message: ''
            };
        }

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const result = validateSelectedFile(file);

            if (!result.valid) {
                showFileError(result.message);
                imageInput.value = '';
                resetPreview();
                return;
            }

            clearFileError();
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });

        uploadForm.addEventListener('submit', function(event) {
            const file = imageInput.files[0];
            const result = validateSelectedFile(file);

            if (!result.valid) {
                event.preventDefault();
                showFileError(result.message);
                resetPreview();
            }
        });
    </script>
</body>

</html>