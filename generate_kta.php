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
$qr_y = 380;            // Beri jarak aman dari Kop
$target_qr_size = 580;  // Diperbesar sesuai proporsi reference (1748x1240)
$name_y = 1140;          // Posisi nama diletakkan di bawah (base line)
$font_size = 115;       // Ukuran font besar menyesuaikan kartu
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
    
    // Jika nama terlalu panjang melebihi lebar kartu, perkecil font size secara dinamis
    $dynamic_font_size = $font_size;
    $max_width = $width - 100; // padding 50px kiri kanan
    while ($text_width > $max_width && $dynamic_font_size > 40) {
        $dynamic_font_size -= 5;
        $bbox = imagettfbbox($dynamic_font_size, 0, $font_path, $name);
        $text_width = abs($bbox[2] - $bbox[0]);
    }
    
    $name_x = ($width - $text_width) / 2; 
    
    // Draw Name in Dark Blue
    imagettftext($bg, $dynamic_font_size, 0, $name_x, $name_y, $dark_blue, $font_path, $name);
    
    // NIK rendering dihilangkan sesuai dengan gambar referensi
    
} else {
    // Fallback jika font tidak ditemukan
    $name_x = ($width - (strlen($name) * 9)) / 2;
    imagestring($bg, 5, $name_x, $name_y, $name, $black);
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