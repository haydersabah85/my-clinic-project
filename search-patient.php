<?php
include 'config.php';
include 'auth.php';
include_once 'clinic_helpers.php';

if (!isset($_GET['q'])) {
    exit;
}

clinic_ensure_infrastructure($con);

$q = trim($_GET['q']);
$like = '%' . $q . '%';
$activePatientWhere = clinic_active_patient_where($con, 'add_patient');

$query = "
    SELECT
        add_patient.id,
        add_patient.full_name,
        add_patient.notes,
        add_patient.phone_no,
        add_patient.age,
        add_patient.is_critical,
        MAX(visits.visit_date) AS last_visit_date,
        latest_surgery.status,
        next_followup.next_followup_date
    FROM add_patient
    LEFT JOIN surgery_appointment latest_surgery ON latest_surgery.id = (
        SELECT id FROM surgery_appointment
        WHERE patient_id = add_patient.id
        ORDER BY id DESC
        LIMIT 1
    )
    LEFT JOIN visits ON add_patient.id = visits.patient_id
    LEFT JOIN (
        SELECT patient_id, MIN(followup_date) AS next_followup_date
        FROM followups
        WHERE status = 'pending' AND followup_date >= CURDATE()
        GROUP BY patient_id
    ) next_followup ON next_followup.patient_id = add_patient.id
    WHERE $activePatientWhere
    AND (
        CAST(add_patient.id AS CHAR) LIKE ?
        OR add_patient.full_name LIKE ?
        OR add_patient.phone_no LIKE ?
        OR add_patient.notes LIKE ?
    )
    GROUP BY add_patient.id
    ORDER BY last_visit_date DESC, add_patient.id DESC
    LIMIT 8
";

$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<div class='result-item'><span>لا توجد نتائج مطابقة</span></div>";
    exit;
}

while ($row = mysqli_fetch_assoc($result)) {
    $color = "";
    if ($row['status'] == "done") {
        $color = "green";
    } elseif ($row['status'] == "discharged") {
        $color = "red";
    } elseif ($row['status'] == "pending") {
        $color = "orange";
    }

    $lastVisitText = !empty($row['last_visit_date'])
        ? "آخر زيارة: " . h($row['last_visit_date'])
        : "لا توجد زيارة سابقة";

    $nextFollowupText = !empty($row['next_followup_date'])
        ? "المراجعة القادمة: " . h($row['next_followup_date'])
        : "";

    $critical = !empty($row['is_critical']) ? " - حالة مهمة" : "";
    $patientId = (int) $row['id'];
    $patientUrl = "patient-data.php?id=" . $patientId;

    echo "
        <div class='result-item'>
            <span class='clinic-user-content' data-no-translate
                  onclick=\"window.location.href='" . $patientUrl . "'\"
                  style='color: $color; cursor:pointer; font-weight: bold;'>
                " . h($row['full_name']) . "
            </span>

            <span class='clinic-user-content' data-no-translate
                  onclick=\"window.location.href='" . $patientUrl . "'\"
                  style='color: $color; cursor:pointer; font-size: 14px;'>
                 " . h($row['notes']) . "
            </span>

            <span onclick=\"window.location.href='" . $patientUrl . "'\">
                 <small style='color: #888; font-size: 12px;'>" . $lastVisitText . "</small>
                 <small style='color: #0f766e; font-size: 12px; display:block;'>" . $nextFollowupText . "</small>
                 <small style='color: #64748b; font-size: 12px; display:block;'>
                    العمر: <span class='clinic-user-content' data-no-translate>" . h($row['age']) . "</span>
                    | الهاتف: <span class='clinic-user-content' data-no-translate>" . h($row['phone_no']) . "</span>
                 </small>
            </span>

            <span class='delete-btn' onclick='deletePatient(" . $patientId . ")'>
            
            🗑️
            </span>
        </div>";
}
