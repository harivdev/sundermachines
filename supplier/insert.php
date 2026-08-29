<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $line1 = mysqli_real_escape_string($conn, $_POST['line1']);
    $line2 = mysqli_real_escape_string($conn, $_POST['line2']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $zipCode = mysqli_real_escape_string($conn, $_POST['zipCode']);
    $createdOn = date('Y-m-d H:i:s');
    $createdBy = "System Admin";

    $addressQuery = "INSERT INTO address (line1, line2, city, zipCode, createdOn, createdBy, modifiedOn, modifiedBy) 
                     VALUES ('$line1', '$line2', '$city', '$zipCode', '$createdOn', '$createdBy', '$createdOn', '$createdBy')";

    if (mysqli_query($conn, $addressQuery)) {
        $addressId = mysqli_insert_id($conn);

        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $phoneNo1 = mysqli_real_escape_string($conn, $_POST['phoneNo1']);
        $whatsAppNo = mysqli_real_escape_string($conn, $_POST['whatsAppNo']);
        $emailId = mysqli_real_escape_string($conn, $_POST['emailId']);
        $active = isset($_POST['active']) ? 1 : 0;

        $supplierQuery = "INSERT INTO supplier (active, emailId, name, phoneNo1, phoneNo2, whatsAppNo, address, createdOn, createdBy) 
                          VALUES ($active, '$emailId', '$name', '$phoneNo1', '', '$whatsAppNo', $addressId, '$createdOn', '$createdBy')";

        if (mysqli_query($conn, $supplierQuery)) {
            echo "<script>alert('Supplier Added Successfully!'); window.location.href='list.php';</script>";
        } else {
            echo "Error adding supplier: " . mysqli_error($conn);
        }
    } else {
        echo "Error adding address: " . mysqli_error($conn);
    }
} else {
    header("Location: add.php");
    exit();
}
?>