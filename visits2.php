<?php 
include 'config.php';

include 'auth.php';

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    $today = date('Y-m-d');
    $type = $_GET['visit_type'];
   $sql_serial = "SELECT MAX(daily_serial) AS max_serial FROM visits WHERE visit_date = '$today'";
   $result_serial = mysqli_query($con,$sql_serial);
   $row_serial = mysqli_fetch_assoc($result_serial);

   if ($row_serial['max_serial']) {
    $daily_serial = $row_serial['max_serial'] + 1;
   } else {
    $daily_serial = 1;
   }
   
    $insert_query = "INSERT into visits (patient_id, visit_date, visit_type, daily_serial) 
    VALUES (?, ?, ?, ?)";
    
    $stmt = $con->prepare($insert_query);
    $stmt->bind_param("issi", $patient_id, $today, $type, $daily_serial);

    try {
        $stmt->execute();
        echo
        "<script>alert('تم اضافة الزيارة بنجاح.');</script>";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    $stmt->close();
} else {
    echo "Error: patient_id not provided.";
}
mysqli_close($con);

header("Location: visits.php?id=$patient_id");
exit();

?>
