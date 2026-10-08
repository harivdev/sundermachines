<?php
require_once("config/db.php");
require_once("includes/auth.php");
requireAdmin();
$res = mysqli_query($conn, "DESCRIBE stock");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

