<?php
include 'config.php';
include 'auth.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأدوية الأكثر استعمالًا</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="assets/theme.js" defer></script>
</head>

<style>
    :root {
        --bg-1: #f4f7fb;
        --bg-2: #e8f0fb;
        --card: #ffffff;
        --primary: #0b6ab8;
        --primary-2: #084f8a;
        --accent: #0ca678;
        --danger: #d62839;
        --text: #1f2d3d;
        --muted: #5f6c7b;
        --border: #d6e2f0;
    }

/* ===== Dark Mode ===== */

    [data-theme="dark"] {
        --bg-1: #1f2d3d;
        --bg-2: #1a2230;
        --card: #28323e;
        --primary: #0b6ab8;
        --primary-2: #084f8a;
        --accent: #0ca678;
        --danger: #d62839;
        --text: #f4f7fb;
        --muted: #5f6c7b;
        --border: #3a4756;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: 'Cairo', sans-serif;
        background: linear-gradient(130deg, var(--bg-1), var(--bg-2));
        color: var(--text);
    }

    .container {
        max-width: 1080px;
        margin: 28px auto;
        padding: 0 16px 24px;
        max-height: 95vh;
        overflow-y: auto;
    }

    .header {
        background: linear-gradient(135deg, var(--primary), var(--primary-2));
        color: #fff;
        border-radius: 16px;
        padding: 16px 20px;
        margin-bottom: 16px;
        box-shadow: 0 10px 25px rgba(8, 79, 138, 0.25);
    }

    .header h1 {
        margin: 0;
        font-size: 28px;
    }

    .header p {
        margin: 6px 0 0;
        opacity: 0.92;
        font-size: 15px;
    }

    .grid {
        display: grid;
        grid-template-columns: 1fr 1.3fr;
        gap: 16px;

    }

    .card {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 6px 18px rgba(26, 50, 77, 0.08);
        padding: 16px;

    }

    h2 {
        margin: 0 0 12px;
        font-size: 20px;
        color: #0e3d6f;
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 10px;

    }

    .field label {
        font-size: 14px;
        color: var(--muted);
        font-weight: 700;
    }

    .field input,
    .field textarea {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 14px;
        font-family: 'Cairo', sans-serif;
        transition: all 0.2s ease;
        width: 60%;
    }

    .field input:focus,
    .field textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 106, 184, 0.14);
    }

    select {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 14px;
        font-family: 'Cairo', sans-serif;
        transition: all 0.2s ease;
        background: #fff;
        width: 60%;
    }

    .btn {
        border: none;
        border-radius: 10px;
        padding: 10px 15px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-family: 'Cairo', sans-serif;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--accent), #088f67);
        color: #fff;
    }

    .btn-secondary {
        background: #e9f2ff;
        color: #164978;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        direction: ltr;

    }

    th,
    td {
        border-bottom: 1px solid #e6edf7;
        padding: 9px 8px;
        text-align: center;
        vertical-align: top;
        overflow-y: hidden;
    }

    th {
        color: #244d78;
        background: #f5f9ff;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .table-wrap {
        max-height: 350px;
        overflow: auto;
        border: 1px solid #e6edf7;
        border-radius: 10px;
    }

    .btn-danger {
        background: #ffe3e7;
        color: var(--danger);
        border: 1px solid #ffc4cc;
        padding: 6px 10px;
    }

    .empty {
        text-align: center;
        color: #6a7685;
        padding: 16px 8px;
    }

    .top-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        gap: 8px;
        flex-wrap: wrap;
    }

    .hint {
        margin-top: 10px;
        color: #5c6e82;
        font-size: 13px;
    }

    a {
        color: var(--primary);
        text-decoration: none;
    }

    input {
        padding: 6px;
        border: 1px solid #ccc;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 6px;
        width: 200px;
        direction: ltr;
    }

    @media (max-width: 900px) {
        .grid {
            grid-template-columns: 1fr;
        }

        .header h1 {
            font-size: 23px;
        }
    }
</style>


<body>
    <div class="container">
        <div class="header">
            <h1>إدارة الأدوية الأكثر استعمالًا</h1>

        </div>

        <div class="grid">
            <div class="card">
                <h2>إضافة دواء جديد</h2>

                <form id="medicineForm" action="common-medicines2.php" method="post">
                    <div class="field">
                        <label for="medicine_name">Medicine Name</label>
                        <input type="text" name="medicine_name" id="medicine_name" required placeholder="e.g., Tobradex">
                    </div>

                    <div class="field">
                        <label for="medicine_form">Drug Form</label>
                        <input type="text" name="medicine_form" id="medicine_form" placeholder="example: eye drops, ointment, tablet">
                    </div>

                    <div class="field">
                        <label for="strength">Strength</label>
                        <input type="text" name="strength" id="strength" placeholder="example: 0.3% / 500mg">
                    </div>

                    <div class="field">
                        <label for="category"> Drug Category </label>
                        <select name="category" id="category" placeholder=" Drug Category ">
                            <option value=""> Choose Category </option>
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

                    <button class="btn btn-primary" id="add_medicine" name="add_medicine" type="submit">Add to List</button>
                </form>


            </div>

            <div class="card">
                <div class="top-actions">
                    <input type="text" id="searchInput" placeholder="Search for a Drug ..." onkeyup="filterTable()">
                    <a class="btn btn-secondary" href="dashboard.php">العودة إلى الصفحة الرئيسية</a>
                </div>

                <!-- #region -->

                <h2>قائمة الأدوية</h2>

                <div class="table-wrap">
                    <table id="drug_table">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Drug Form</th>
                                <th>Strength</th>
                                <th>Category</th>
                                <th>Edit</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody id="medicationsBody">

                            <?php
                            $select = "SELECT * FROM medicines";
                            $result = mysqli_query($con, $select);
                            while ($row = mysqli_fetch_assoc($result)) {

                            ?>
                                <tr id="emptyRow">

                                    <td> <?php echo $row['medicine_name']; ?></td>
                                    <td><?php echo $row['medicine_form']; ?></td>
                                    <td><?php echo $row['strength']; ?></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td> <a class="btn" href="edit-medicine.php?id_medicine=<?php echo $row['id']; ?>">Edit</a> </td>
                                    <td><button class="btn btn-danger" onclick="confirmDelete(<?= $row['id'] ?>)">Delete</button></td>

                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this medicine?")) {
                window.location.href = "delete-medicine.php?id_medicine=" + id;
            }
        }

        function filterTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("drug_table");
            tr = table.getElementsByTagName("tr");

            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>

</body>

</html>