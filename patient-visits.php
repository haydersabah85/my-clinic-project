<?php

include 'config.php';

include 'auth.php';

    $visit_id = $_POST['id'];
    $notes = $_POST['notes'];
    $patient_id = $_GET['id'];
    $visit_date = date('Y-m-d');  // Use current date
   



    if(!empty($visit_id)) {
        // Update existing visit
        
        $update_query = "UPDATE patient_visits SET notes='$notes'  WHERE id='$visit_id'";
       
        mysqli_query($con, $update_query);

        header("Location: patient-file.php?id=$patient_id");
        exit();
    } else {
        // Insert new visit
       
    
    $insert_query = "INSERT INTO patient_visits (patient_id, date, notes) VALUES ('$patient_id', '$visit_date', '$notes')";
    mysqli_query($con, $insert_query);

    header("Location: patient-file.php?id=$patient_id");
    exit();
}

?>