
<?php

include 'config.php';
include 'auth.php';

$patient_id = intval($_GET['id']);



$query = "SELECT *
          FROM patient_images 
          WHERE patient_id = $patient_id 
          ORDER BY date_added DESC";

$result = mysqli_query($con, $query);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف المريض</title>
</head>

<style>

.gallery-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 15px;
}

.gallery-title {
    text-align: center;
    font-size: 28px;
    margin-bottom: 25px;
    color: #2c3e50;
}

.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 20px;
}

.image-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    transition: 0.3s;
}

.image-card:hover {
    transform: translateY(-5px);
}

.image-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    cursor: pointer;
}

.image-info {
    padding: 10px;
    text-align: center;
    font-size: 13px;
    color: #555;
}

/* ===== نافذة منبثقة ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    inset: 0;
    background: rgba(0,0,0,0.85);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    max-width: 90%;
    max-height: 90%;
    padding: 15px;
    text-align: center;
}

.modal-content img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 10px;
}

.modal-actions {
    margin-top: 10px;
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.modal-actions a,
.modal-actions button {
    padding: 8px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.download-btn {
    background: #3498db;
    color: #fff;
}

.delete-btn {
    background: #e74c3c;
    color: #fff;
}

.close-btn {
    background: #7f8c8d;
    color: #fff;
}
</style>

<body>

<div class="gallery-container">
    <div class="gallery-title">صور الشبكية</div>

    <div class="image-gallery">
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="image-card">
                <img src="<?php echo $row['image_path']; ?>"
                        alt="Patient Image"
                        onclick="openModal('<?php echo $row['image_path']; ?>',
                         <?php echo intval($row['id']); ?>, <?php echo $patient_id; ?>)">

                <div class="image-info">
                    <?php echo htmlspecialchars($row['notes']); ?><br>
                    <?php echo date('Y-m-d', strtotime($row['date_added'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="modal" id="imageModal">
    <div class="modal-content">
        <img id="modalImage">
        <div class="modal-actions">
            <button onclick="rotateImage()">تدوير</button>
            <a id="downloadLink" class="download-btn" download>تحميل</a>
            <button class="delete-btn" onclick="deleteImage()">حذف</button>
            <button class="close-btn" onclick="closeModal()">إغلاق</button>
        </div>
    </div>
</div>

<script>
let currentImageId = null;
let currentRotation = 0;
let currentMagnification = 1; // الماوس

function openModal(src, imageId) {
    document.getElementById('modalImage').src = src;
    document.getElementById('downloadLink').href = src;
    currentImageId = imageId;
    currentRotation = 0;
    
    document.getElementById('modalImage').style.transform = 'rotate(0deg) scale(1)';
    document.getElementById('imageModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}

function deleteImage() {
    if(confirm('هل أنت متأكد من حذف الصورة؟')) {
        window.location.href = 'delete-image.php?id=' + currentImageId;
    }
}
function rotateImage() {
    currentRotation = (currentRotation + 90) % 360;
    document.getElementById('modalImage').style.transform = 'rotate(' + currentRotation + 'deg) scale(' + currentMagnification + ')';
}
</script>

</body>
</html>