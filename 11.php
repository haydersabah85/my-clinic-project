body {
        font-family: Arial, sans-serif;
        direction: rtl;
        text-align: right;
        margin: 20px;
        background-color: #f0f0f0;
    }

    h1, h2 {
        color: #333;
        text-align: center;

    }

    form {
        max-width: 500px;
        margin: auto;
        direction: rtl;
        text-align: right;
        background: #f9f9f9;
        padding: 30px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);

    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;

    }

    input[type="text"],
    input[type="tel"],
    input[type="date"],
    select,
    textarea {
        width: 90%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;

    }

    input[type="submit"] {
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    
    }

    input[type="submit"]:hover {
        background-color: #218838;
    }

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز موعد عملية</title>

   

</head>

<style>


</style>


<body>
    
    <h1>عيادة الدكتور حيدر صباح الربيعي</h1>
    <h2>حجز موعد عملية</h2>
    <form action="surgery-appointment2.php?id=<?php echo $id; ?>" method="POST">
        
        <label for="name">الاسم الكامل:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($row['full_name']); ?>"
  
        ><br><br>

        <label for="surgery_type">نوع العملية:</label><br>
        <select id="surgery_type" name="surgery_type" required>
            <option value="">اختر نوع العملية</option>
            <option value="phaco">Phaco</option>
            <option value="vitrectomy">Vitrectomy</option>
            <option value="Phaco and Vitrectomy">Phaco and Vitrectomy</option>
            <option value="SOR">SOR</option>
            <option value="Phaco and SOR">Phaco and SOR</option>
            <option value="Squint">Squint</option>
            <option value="EUA">EUA</option>
            <option value="Probing">Probing</option>
            <option value="SMILE">SMILE</option>
            <option value="PRK">PRK</option>
            <option value="Secondary IOL">Secondary IOL</option>
            <option value="Anterior Vitrectomy">Anterior Vitrectomy</option>
            
        </select><br><br>

        <label for="eye">العين:</label><br>
        <select id="eye" name="eye" required>
            <option value="">اختر العين</option>
            <option value="OD">OD</option>
            <option value="OS">OS</option>
            <option value="OU">OU</option>
        </select><br><br>

        <label for="phone">رقم الهاتف:</label><br>
        <input type="tel" id="phone" name="phone" required
        placeholder="رقم الهاتف"
        ><br><br>
        <input type="tel" id="phone_alt" name="phone_alt" placeholder="رقم هاتف ثاني(اختياري)">
        <br><br>

        <label for="date">موعد العملية:</label><br>
        <input type="date" id="date" name="date" ><br><br>

        

        <label for="notes">ملاحظات إضافية:</label><br>
        <textarea id="notes" name="notes"></textarea><br><br>

        <input type="submit" name="submit_surgery" value="حجز الموعد">

    </form>
</body>
</html>


<style>

    ::after, ::before {
        box-sizing: border-box;

    }
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 20px;
        direction: rtl;
    }
    h1, h2 {
        text-align: center;
    }
    form {
        background-color: #fff;
        padding: 25px 30px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    input[type="text"],
    input[type="tel"],  
    input[type="date"],
    select,
    textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        direction: rtl;
    }
    textarea {
        height: 100px;
        resize: vertical;
        direction: ltr;
    }
    input[type="submit"] {
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    input[type="submit"]:hover {
        background-color: #218838;
    }


    @media screen and (max-width: 768px) {
        form {
            padding: 25px 20px;
        }

        input[type="submit"] {
            width: 100%;
        }
    }
</style>