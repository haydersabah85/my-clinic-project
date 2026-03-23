<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<style>
body {
    font-family: 'Cairo', sans-serif;
    margin: 0;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 220px;
    background: #2c3e50;
    color: white;
    height: 100vh;
    padding: 20px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    margin: 10px 0;
}

/* Main */
.main {
    flex: 1;
    padding: 30px;
}

/* Search */
.search-box {
    width: 60%;
    margin: auto;
    text-align: center;
}

.search-box input {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

/* Results */
#results {
    margin-top: 20px;
}



.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f4f4f4;
    margin-bottom: 8px;
    border-radius: 10px;
}

.delete-btn {
    cursor: pointer;
    font-size: 18px;
    transition: 0.3s;
}

.delete-btn:hover {
    transform: scale(1.3) rotate(-10deg);
}

</style>
</head>

<body>

<div class="sidebar">
    <h2>🏥 Clinic</h2>
    <a href="index.php">🏠 الرئيسية</a>
    <a href="visits.php">📅 الزيارات</a>
    <a href="main.php">👥 المرضى</a>
</div>

<div class="main">

    <h2 style="text-align:center;">🔍 البحث عن مريض</h2>

    <div class="search-box">
        <input type="text" id="search" placeholder="اكتب اسم المريض...">
        <div id="results"></div>
    </div>

</div>

<script>
document.getElementById("search").addEventListener("keyup", function(){
    let query = this.value;

    if(query.length < 2){
        document.getElementById("results").innerHTML = "";
        return;
    }

    fetch("3.php?q=" + query)
    .then(res => res.text())
    .then(data => {
        document.getElementById("results").innerHTML = data;
    });
});



function deletePatient(id){

    if(confirm("⚠️ هل أنت متأكد من حذف هذا المريض؟")){
        
        fetch("delete-patient.php?id_delete=" + id)
        .then(res => res.text())
        .then(data => {
            alert("تم الحذف بنجاح");
            
            // إعادة البحث لتحديث النتائج
            document.getElementById("search").dispatchEvent(new Event('keyup'));
        });
    }
}

</script>
</body>
</html>