<?php
include "config_backup.php";

$today = date("Y-m-d");
$flag_file = $BACKUP_PATH . "/backup_done_$today.txt";

if (!file_exists($flag_file)) {

    $date = date("Y-m-d_H-i-s");
    $backup_file = $BACKUP_PATH . "/auto_backup_$date.sql";

    $command = "\"$MYSQLDUMP_PATH\" --user=$DB_USER --password=$DB_PASS --host=$DB_HOST $DB_NAME > \"$backup_file\"";

    system($command, $result);

    if ($result === 0) {
        file_put_contents($flag_file, "done");
    }
}
?>
