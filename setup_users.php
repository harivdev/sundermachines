<?php
require_once("config/db.php");

echo "<div style='font-family: sans-serif; padding: 30px; max-width: 600px; margin: 50px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>";
echo "<h2 style='color: #1e293b; margin-top: 0;'>⚙️ User Table Setup</h2>";

// 1. Create 'user' Table (Requested Name)
$createTable = "CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone_number_2` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `createdOn` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn_login, $createTable)) {
    echo "<p style='color: #10b981;'>✅ Database table <b>'user'</b> is ready.</p>";
} else {
    echo "<p style='color: #ef4444;'>❌ Error creating table: " . mysqli_error($conn_login) . "</p>";
}

// 2. Add Default Admin User
$username = "admin";
$password = "admin1441";
$role = "ADMIN";

$check = mysqli_query($conn_login, "SELECT id FROM user WHERE username = '$username'");
if (mysqli_num_rows($check) == 0) {
    $insert = "INSERT INTO user (username, password, role) VALUES ('$username', '$password', '$role')";
    if (mysqli_query($conn_login, $insert)) {
        echo "<p style='color: #10b981;'>✅ Default admin account created in 'user' table.</p>";
    }
} else {
    echo "<p style='color: #64748b;'>ℹ️ Admin user already exists in 'user' table.</p>";
}

echo "<hr style='border: 0; border-top: 1px solid #f1f5f9; margin: 25px 0;'>";
echo "<div style='background: #f8fafc; padding: 15px; border-radius: 8px;'>";
echo "<h3 style='margin-top: 0; font-size: 16px;'>Login Credentials:</h3>";
echo "<b>Username:</b> $username<br>";
echo "<b>Password:</b> $password<br>";
echo "</div>";
echo "<br><a href='index.php' style='display: inline-block; background: #1e7e34; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Go to Login Page</a>";
echo "</div>";
?>