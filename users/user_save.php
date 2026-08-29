<?php
// user_save.php – Create or update admin user in billing_login.user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once("../config/db.php");

function ensureUserProfileColumns($conn_login)
{
    $existing = [];
    $res = $conn_login->query("SHOW COLUMNS FROM user");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $existing[] = $row['Field'];
        }
    }

    $definitions = [
        'name' => 'VARCHAR(100) NULL',
        'phone_number' => 'VARCHAR(20) NULL',
        'phone_number_2' => 'VARCHAR(20) NULL',
        'email' => 'VARCHAR(100) NULL',
        'dob' => 'DATE NULL',
        'address' => 'TEXT NULL',
        'gender' => 'VARCHAR(50) NULL',
        'photo' => 'VARCHAR(255) NULL'
    ];

    foreach ($definitions as $column => $definition) {
        if (!in_array($column, $existing, true)) {
            $conn_login->query("ALTER TABLE user ADD COLUMN `$column` $definition");
        }
    }
}

ensureUserProfileColumns($conn_login);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($conn_login->real_escape_string($_POST['name'])) : '';
$username = isset($_POST['username']) ? trim($conn_login->real_escape_string($_POST['username'])) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$phoneNumber = isset($_POST['phone_number']) ? trim($conn_login->real_escape_string($_POST['phone_number'])) : '';
$phoneNumber2 = isset($_POST['phone_number_2']) ? trim($conn_login->real_escape_string($_POST['phone_number_2'])) : '';
$email = isset($_POST['email']) ? trim($conn_login->real_escape_string($_POST['email'])) : '';
$dob = isset($_POST['dob']) ? trim($conn_login->real_escape_string($_POST['dob'])) : '';
$address = isset($_POST['address']) ? trim($conn_login->real_escape_string($_POST['address'])) : '';
$gender = isset($_POST['gender']) ? trim($conn_login->real_escape_string($_POST['gender'])) : '';
$existingPhoto = isset($_POST['existing_photo']) ? trim($conn_login->real_escape_string($_POST['existing_photo'])) : '';
$role = isset($_POST['role']) ? strtoupper(trim($conn_login->real_escape_string($_POST['role']))) : 'ADMIN';

$uploadDir = dirname(__DIR__) . '/uploads/user_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$photoName = $existingPhoto;
if (isset($_FILES['photo']) && isset($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, or WEBP images are allowed.']);
        exit();
    }

    $photoName = 'user_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
        echo json_encode(['success' => false, 'message' => 'Could not save uploaded photo.']);
        exit();
    }
}

// Validate name and username
if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'Name is required.']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Invalid username. Use 3–30 alphanumeric characters or underscores.']);
    exit();
}

// Validate password required for new users
if ($id === 0 && $password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required for new users.']);
    exit();
}

$now = date('Y-m-d H:i:s');

if ($id > 0) {
    // UPDATE existing user
    $setParts = [
        "name = '$name'",
        "username = '$username'",
        "phone_number = '$phoneNumber'",
        "phone_number_2 = '$phoneNumber2'",
        "email = '$email'",
        "dob = '$dob'",
        "address = '$address'",
        "gender = '$gender'",
        "photo = '$photoName'",
        "role = '$role'"
    ];

    if ($password !== '') {
        $escaped_pass = $conn_login->real_escape_string($password);
        $setParts[] = "password = '$escaped_pass'";
    }

    $sql = "UPDATE user SET " . implode(', ', $setParts) . " WHERE id = $id";
    if ($conn_login->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn_login->error]);
    }
} else {
    // INSERT new user – check for duplicate username
    $check = $conn_login->query("SELECT id FROM user WHERE username = '$username'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Username '$username' already exists."]);
        exit();
    }

    $escaped_pass = $conn_login->real_escape_string($password);
    $sql = "INSERT INTO user (name, username, password, phone_number, phone_number_2, email, dob, address, gender, photo, role, createdOn)
            VALUES ('$name', '$username', '$escaped_pass', '$phoneNumber', '$phoneNumber2', '$email', '$dob', '$address', '$gender', '$photoName', '$role', '$now')";
    if ($conn_login->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $conn_login->error]);
    }
}
