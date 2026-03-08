
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8" />
    <title>عيادة الدكتور حيدر صباح الربيعي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>

  <style>
    
   h2 {
      text-align: center;
      font-family: Arial, Helvetica, sans-serif;
      color: darkslategrey;
      text-shadow: 1px 2px 2px #aaa;
      font-size: 26px;
      margin-top: 20px;
    }
    .patient_info {
      background: #f0f8ff;
      border: 2px solid #000000;
      border-radius: 10px;
      padding: 10px;
      margin: 20px auto;
      max-width: 75%;
      font-size: 20px;
      font-family: Arial, Helvetica, sans-serif;
      display: flex;
      flex-direction: row;
      gap: 10px;
      justify-content: space-around;
      direction: rtl;
    }
    .patient_info p {
      margin: 5px 15px;
    }
    .patient_info span {
      font-weight: bold;
      margin-left: 5px;
    }

     .patient_info a {
            background-color: #0ab370ff;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;

        }

        .patient_info a:hover {
            background-color: #1a5642ff;
            transform: scale(1.05);
            transition: 0.3s;

        }


    h3 { 
      text-align: center;
      font-family: Arial, Helvetica, sans-serif;
      color: rgb(201, 33, 56);
      text-shadow: 1px 2px 2px #aaa;
      font-size: 22px;
      margin-top: 30px;
      text-decoration: underline;   
    }
    .surgery {
      background: darkslategrey;
    }
    .injection {
      background: teal;
    }
    .laser {
      background: slateblue;
    }
    .surgery,
    .injection,
    .laser {
      
     
      justify-content: space-around;
      position: relative;
      display: flex;
      flex-direction: column;
      max-width: 900px;
      height: 300px;
      margin: 30px auto;
      border-radius: 10px;
      padding: 6px;
      color: navajowhite;
      font-size: 18px;
      font-family: Arial, Helvetica, sans-serif;
      box-shadow: 2px 4px 4px #000000;
      padding: 15px;

    }
    .surgical_info,
    .injection_info,
    .laser_info {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 20px;
      margin-bottom: 10px;

    }
    .surgical_info label,
    .injection_info label,
    .laser_info label {
      margin-right: 10px;
      margin-left: 10px;
    }

    select {
      border-radius: 5px;
      border: none;
      font-size: 16px;
      padding: 5px;
      text-align: center;
    }

    button {
      position: relative;
      background: #27b331;
      border: none;
      color: white;
      width: 80px;
      height: 30px;
      border-radius: 5px;
      font-size: 16px;
      margin: 0 10px;
      cursor: pointer;
      margin-left: auto;
    }
    button:hover {
      background: #1e8c27;
    }
    textarea {
      border-radius: 5px;
      border: none;
      font-size: 16px;
      padding: 5px;
      text-align: left;
      margin: 2px 50px 10px 70px;
    }

    #surgery_date,
    #injection_date,
    #laser_date {
      border-radius: 5px;
      border: none;
      font-size: 16px;
      padding: 5px;
      text-align: center;
      
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
  <h3>اضافة معلومات العملية</h3>

  <body>
    <form class="surgery" action="add-surgery.php" method="POST">
      <div class="surgical_info">
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

        <label for="surgery_type">Type Of Surgery:</label>
        <select name="surgery_type" id="surgery_type" required>
          <ul>
            <option value="">اختر نوع العملية</option>
            <option value="Phaco">Phaco</option>
            <option value="Vitrectomy">Vitrectomy</option>
            <option value="Phaco and Vitrectomy">Phaco and Vitrectomy</option>
            <option value="SOR">SOR</option>
            <option value="Phaco and SOR">Phaco and SOR</option>
            <option value="Squint">Squint</option>
            <option value="EUA">EUA</option>
            <option value="Probing">Probing</option>
            <option value="SMILE">SMILE</option>
            <option value="PRK">PRK</option>
            <option value="Secondary IOL">Secondary IOL</option>
            <option value="Pterygium with Graft">Pterygium with Graft</option>
            <option value="Pterygium">Pterygium</option>
          </ul>
        </select>

        <label for="iol_type">IOL Type:</label>
        <select name="iol_type" id="iol_type">
          <ul>
            <option value="">اختر نوع العدسة</option>
            <option value="Sensar">Sensar</option>
            <option value="Eyhance">Eyhance</option>
            <option value="Alcon">Alcon</option>
            <option value="Clareon">Clareon</option>
            <option value="Synergy">Synergy</option>
            <option value="Rayner Monofocal">Rayner Monofocal</option>
            <option value="Rayner Trifocal">Rayner Trifocal</option>
            <option value="Eleon">Eleon</option>
          </ul>
        </select>

        <input type="date" required name="surgery_date" id="surgery_date" />
      </div>
      <label for="notes" id="notes">Notes</label>

      <textarea
        name="notes"
        id="notes"
        cols="50"
        rows="5"
        placeholder="Extra Notes.."
      ></textarea>

      <button id="surgery_btn" name="surgery_btn" type="submit">Save</button>
    </form>

    <br>

      <h3>اضافة معلومات الحقن</h3>
    
    
    <form class="injection" action="add-injection.php" method="POST">
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
            <option value="Ozurdix">Ozurdix</option>
          </ul>
        </select>

        <input type="date" required name="injection_date" id="injection_date" />
      </div>
      <label for="notes">Notes</label>
      <textarea
        name="notes"
        id="notes"
        cols="50"
        rows="5"
        placeholder="Extra Notes.."
      ></textarea>

      <button id="injection_btn" name="injection_btn" type="submit">Save</button>
    </form>
    <br />
    <h3>اضافة معلومات الليزر</h3>
    <form class="laser" action="add-laser.php" method="POST">
      <div class="laser_info">
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

        <label for="laser_type">Type Of Laser:</label>
        <select name="laser_type" id="laser_type" required>
          <ul>
            <option value="">اختر نوع الليزر</option>
            <option value="PRP">PRP</option>
            <option value="Retinopexy">Retinopexy</option>
            <option value="Yag">Yag</option>
            <option value="Focal Laser">Focal Laser</option>
            <option value="PI">PI</option>
          </ul>
        </select>

        <input type="date" required name="laser_date" id="laser_date" />
      </div>
      <label for="notes">Notes</label>
      <textarea
        name="notes"
        id="notes"
        cols="50"
        rows="5"
        placeholder="Extra Notes.."
      ></textarea>
      <button id="laser_btn" name="laser_btn" type="submit">Save</button>
    </form>
  </body>
</html>
