<?php
require_once("config/db.php");
$res = mysqli_query($conn, "DESCRIBE stock");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
