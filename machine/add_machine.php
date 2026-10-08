<?php
require_once("../includes/auth.php");
requireAdmin();
header("Location: list_machine.php?focus=add#addMachineSection");
exit();
?>
