<?php
include 'config.php';

if(isset($_GET['q'])){
    $q = mysqli_real_escape_string($con, $_GET['q']);

    $query = "SELECT id, full_name, notes FROM add_patient 
              WHERE id LIKE '%$q%' OR full_name LIKE '%$q%'
              LIMIT 5";

    $result = mysqli_query($con, $query);


    while($row = mysqli_fetch_assoc($result)){
        echo "
        <div class='result-item'>

            <span onclick=\"window.location.href='patient-data.php?id=".$row['id']."'\" 
                  style='cursor:pointer;'>
                👤 ".$row['full_name']."
            </span>

             <span onclick=\"window.location.href='patient-data.php?id=".$row['id']."'\" 
                  style='cursor:pointer;'>
                 ".$row['notes']."
            </span>

            <span class='delete-btn' 
                  onclick='deletePatient(".$row['id'].")'>
                🗑️
            </span>

        </div>";
    }
}
?>

