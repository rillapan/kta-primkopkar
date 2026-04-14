CREATE DATABASE IF NOT EXISTS generate_kta;
USE generate_kta;

CREATE TABLE IF NOT EXISTS members (
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
);
