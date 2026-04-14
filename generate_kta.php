<?php
require 'db.php';
require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Common\EccLevel;

// Prevent any prior output from corrupting the image
if (ob_get_length()) ob_clean();

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    die("ID missing");
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    header('HTTP/1.1 404 Not Found');
    die("Member not found");
}

// Load background
$bg_path = 'bgkta.png';
if (!file_exists($bg_path)) {
    $bg = imagecreatetruecolor(1011, 638);
    $bg_color = imagecolorallocate($bg, 255, 255, 255);
    imagefill($bg, 0, 0, $bg_color);
} else {
    $bg = imagecreatefrompng($bg_path);
}

imagealphablending($bg, true);
imagesavealpha($bg, true);

$width = imagesx($bg);
$height = imagesy($bg);

// Fetch QR Code data strictly from NIK to bypass #name formula errors
$qr_data_val = trim($member['nik']);

// ==========================================
// PENGATURAN LAYOUT & KOORDINAT BARU
// ==========================================
$qr_y = 500;            // Beri jarak aman dari Kop (menurunkan posisi QR)
$target_qr_size = 400;  // Diperbesar sedikit agar proporsional dengan gambar
$name_y = 1000;          // Posisi nama diturunkan ke area bawah yang kosong
$font_size = 64;        // Ukuran 64 lebih aman untuk nama panjang seperti di contoh
// ==========================================

// Fetch and draw local FAST QR Code
if (!empty($qr_data_val)) {
    $options = new QROptions([
        'version'         => 5,
        'outputInterface' => QRGdImagePNG::class,
        'eccLevel'        => EccLevel::L,
        'scale'           => 8,
        'outputBase64'    => false,
    ]);
    
    $qr = new QRCode($options);
    $qr_data = $qr->render($qr_data_val);
    
    if ($qr_data) {
        $qr_img = @imagecreatefromstring($qr_data);
        if ($qr_img) {
            $qr_w = imagesx($qr_img);
            $qr_h = imagesy($qr_img);
            
            // Positioning the QR Code strictly in the CENTER X
            $qr_x = ($width - $target_qr_size) / 2;
            
            imagecopyresampled($bg, $qr_img, $qr_x, $qr_y, 0, 0, $target_qr_size, $target_qr_size, $qr_w, $qr_h);
            imagedestroy($qr_img);
        }
    }
}

// Overlay Name & NIK
$font_path = 'C:\Windows\Fonts\impact.ttf'; 
if (!file_exists($font_path)) $font_path = 'C:\Windows\Fonts\arialbd.ttf';
if (!file_exists($font_path)) $font_path = 'C:\Windows\Fonts\arial.ttf';

$name = strtoupper($member['nama']);
$nik = "NIK: " . $member['nik'];

$black = imagecolorallocate($bg, 0, 0, 0);
$dark_blue = imagecolorallocate($bg, 17, 34, 85); // Matching the background text color!

if (file_exists($font_path)) {
    // Menghitung posisi X agar teks berada persis di tengah
    $bbox = imagettfbbox($font_size, 0, $font_path, $name);
    // Menggunakan abs() agar perhitungan lebih akurat di beberapa versi GD
    $text_width = abs($bbox[2] - $bbox[0]); 
    $name_x = ($width - $text_width) / 2; 
    
    // Draw Name in Dark Blue
    imagettftext($bg, $font_size, 0, $name_x, $name_y, $dark_blue, $font_path, $name);
    
    // NIK (Medium size, diletakkan persis di bawah Nama jika diperlukan)
    // Jika di gambar contoh tidak ada NIK, Anda bisa menghapus / komen 4 baris di bawah ini
    $font_size_nik = 20;
    $bbox_nik = imagettfbbox($font_size_nik, 0, $font_path, $nik);
    $nik_w = abs($bbox_nik[2] - $bbox_nik[0]);
    imagettftext($bg, $font_size_nik, 0, ($width - $nik_w) / 2, $name_y + 40, $dark_blue, $font_path, $nik);
    
} else {
    // Fallback jika font tidak ditemukan
    $name_x = ($width - (strlen($name) * 9)) / 2;
    imagestring($bg, 5, $name_x, $name_y, $name, $black);
    imagestring($bg, 4, ($width - (strlen($nik) * 7)) / 2, $name_y + 30, $nik, $black);
}

// Clean all buffers before output
while (ob_get_level()) {
    ob_end_clean();
}

// Final output
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
imagepng($bg, null, 0); // 0 = no compression for faster/safer output
imagedestroy($bg);
exit;