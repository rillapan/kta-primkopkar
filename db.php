<?php
ob_start();
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP
$db   = 'generate_kta';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create table if not exists
$table_sql = "CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_urut VARCHAR(50),
    kode VARCHAR(50),
    nik VARCHAR(50),
    nama VARCHAR(255),
    bagian VARCHAR(255),
    departemen VARCHAR(255),
    api_qrcode VARCHAR(255),
    qr_code_path TEXT,
    kta_path TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_sql);
