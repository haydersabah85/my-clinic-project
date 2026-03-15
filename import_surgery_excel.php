<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<style>
.body {
    font-family: Tahoma, Arial;
    background: #f4f6f9;
    margin: 20px;

}

h2 {
    text-align: center;
    color: #2c3e50;
}
h4 {
    text-align: center;
    color: #2c3e50;
}

.btn {
    display: block;
    margin: 20px auto;
    padding: 10px 20px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn:hover {
    background-color: #2980b9;
}
.form {
    text-align: center;
    margin-top: 50px;
}
input[type="file"] {
    display: block;
    margin: 20px auto;
    padding: 10px;
    border: 2px solid #3498db;
    border-radius: 5px;
    cursor: pointer;
}
input[type="file"]:hover {
    border-color: #2980b9;
}

</style>

<body>
    
<form action="import_surgery_excel2.php" method="post" enctype="multipart/form-data">

<input type="file" name="excel_file">

<button type="submit" class="btn btn-primary">
Import Surgeries
</button>

</form>

</body>
</html>