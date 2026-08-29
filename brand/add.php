<?php
header("Location: list.php");
require_once("../includes/auth.php");
requireAdmin();
exit();
?>