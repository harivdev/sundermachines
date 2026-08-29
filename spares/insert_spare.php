<?php
require_once("../config/db.php");
require_once("../includes/auth.php");
requireAdmin();

// ================= INPUT =================
$spareName = mysqli_real_escape_string($conn, $_POST['spareName']);
$partNo = mysqli_real_escape_string($conn, $_POST['partNo']);
$rack = mysqli_real_escape_string($conn, $_POST['rackNumber']);
$active = isset($_POST['active']) ? 1 : 0;

// ================= IMAGE =================
$imageName = NULL;
$uploadDir = "../uploads/spares/";

// create folder if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ================= FILE UPLOAD =================
if (!empty($_FILES['image']['name'])) {

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    // allowed types
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        die("Invalid file type");
    }

    // unique name
    $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;

    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
}

// ================= CAMERA IMAGE =================
elseif (!empty($_POST['camera_image'])) {
    $data = $_POST['camera_image'];
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);
    $data = str_replace(' ', '+', $data);
    $imageData = base64_decode($data);
    if ($imageData !== false) {
        $imageName = "cam_" . time() . "_" . rand(1000, 9999) . ".png";
        file_put_contents($uploadDir . $imageName, $imageData);
    }
}

// ================= INSERT =================
$query = "INSERT INTO spares(
    spareName, partNo, rackNumber, active, picture
) VALUES (
    '$spareName','$partNo','$rack',b'$active','$imageName'
)";

if (mysqli_query($conn, $query)) {
    echo "<script>
        alert('Spare Added Successfully');
        window.location='add_spare.php';
    </script>";
} else {
    echo mysqli_error($conn);
}
?>