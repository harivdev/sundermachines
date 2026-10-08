<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

$error = '';
$supplierId = 0;
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = mysqli_real_escape_string($conn, trim($_POST['name']      ?? ''));
    $phoneNo1   = mysqli_real_escape_string($conn, trim($_POST['phoneNo1'] ?? ''));
    $whatsAppNo = mysqli_real_escape_string($conn, trim($_POST['whatsAppNo'] ?? ''));
    $emailId    = mysqli_real_escape_string($conn, trim($_POST['emailId']   ?? ''));
    $line1      = mysqli_real_escape_string($conn, trim($_POST['line1']     ?? ''));
    $line2      = mysqli_real_escape_string($conn, trim($_POST['line2']     ?? ''));
    $city       = mysqli_real_escape_string($conn, trim($_POST['city']      ?? ''));
    $zipCode    = mysqli_real_escape_string($conn, trim($_POST['zipCode']   ?? ''));
    $active     = 1;

    if (!$name || !$phoneNo1 || !$line1 || !$city || !$zipCode) {
        $error = 'Please fill all required fields.';
    } else {
        $now       = date('Y-m-d H:i:s');
        $createdBy = $_SESSION['username'] ?? 'System Admin';

        $addrSQL = "INSERT INTO address (line1, line2, city, zipCode, createdOn, createdBy, modifiedOn, modifiedBy)
                    VALUES ('$line1', '$line2', '$city', '$zipCode', '$now', '$createdBy', '$now', '$createdBy')";

        if (mysqli_query($conn, $addrSQL)) {
            $addressId = mysqli_insert_id($conn);

            $suppSQL = "INSERT INTO supplier (active, emailId, name, phoneNo1, phoneNo2, whatsAppNo, address, createdOn, createdBy, modifiedOn, modifiedBy)
                        VALUES ($active, '$emailId', '$name', '$phoneNo1', '', '$whatsAppNo', $addressId, '$now', '$createdBy', '$now', '$createdBy')";

            if (mysqli_query($conn, $suppSQL)) {
                $supplierId = mysqli_insert_id($conn);
                $data = [
                    'id'      => $supplierId,
                    'name'    => htmlspecialchars(trim($_POST['name'])),
                    'phone'   => htmlspecialchars(trim($_POST['phoneNo1'])),
                    'email'   => htmlspecialchars(trim($_POST['emailId']   ?? '')),
                    'line1'   => htmlspecialchars(trim($_POST['line1'])),
                    'line2'   => htmlspecialchars(trim($_POST['line2']     ?? '')),
                    'city'    => htmlspecialchars(trim($_POST['city'])),
                    'zipCode' => htmlspecialchars(trim($_POST['zipCode'])),
                ];
            } else {
                $error = 'Error saving supplier: ' . mysqli_error($conn);
            }
        } else {
            $error = 'Error saving address: ' . mysqli_error($conn);
        }
    }
}
?><!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<script>
<?php if ($error): ?>
  // Notify parent of error
  if (window.parent && window.parent.onSupplierSaveError) {
    window.parent.onSupplierSaveError(<?= json_encode($error) ?>);
  }
<?php else: ?>
  // Notify parent of success with new supplier data
  if (window.parent && window.parent.onSupplierSaved) {
    window.parent.onSupplierSaved(<?= json_encode($data) ?>);
  }
<?php endif; ?>
</script>
</body>
</html>
