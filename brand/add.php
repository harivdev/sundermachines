<?php
require_once("../includes/auth.php");
requireAdmin();
header("Location: list.php?focus=add");
exit();
?>