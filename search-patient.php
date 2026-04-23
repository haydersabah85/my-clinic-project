<?php
include 'config.php';

if (isset($_GET['q'])) {
    $q = mysqli_real_escape_string($con, $_GET['q']);

    $query = "
    SELECT 
add_patient.id,
add_patient.full_name,
add_patient.notes,
add_patient.phone_no,
surgery_appointment.status
FROM add_patient
LEFT JOIN surgery_appointment ON add_patient.id = surgery_appointment.patient_id
WHERE add_patient.id LIKE '%$q%' OR add_patient.full_name LIKE '%$q%' OR add_patient.phone_no LIKE '%$q%' 
GROUP BY add_patient.id
LIMIT 5
";



    $result = mysqli_query($con, $query);


    while ($row = mysqli_fetch_assoc($result)) {

        $color = "";
        if ($row['status'] == "done") $color = "green";
        elseif ($row['status'] == "discharged") $color = "red";
        elseif ($row['status'] == "pending") $color = "orange";

        echo "
        <div class='result-item'>

            <span onclick=\"window.location.href='patient-data.php?id=" . $row['id'] . "'\" 
                  style='color: $color; cursor:pointer; font-weight: bold;'>
                👤 " . $row['full_name'] . "
            </span>

             <span onclick=\"window.location.href='patient-data.php?id=" . $row['id'] . "'\" 
                  style='color: $color; cursor:pointer;'>
                 " . $row['notes'] . "
            </span>

            <span class='delete-btn' 
                  onclick='deletePatient(" . $row['id'] . ")'>
                🗑️
            </span>

        </div>";
    }
}
