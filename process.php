<?php
// Prevent any HTML output before JSON
ob_start();

require 'db.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Helper to send error response
function sendError($message) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

// Redirect PHP errors to our JSON handler if possible
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    sendError("PHP Error [$errno]: $errstr in $errfile on line $errline");
});

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file']['tmp_name'];
        
        if (!file_exists($file)) {
            sendError('File temporary was not created. Check upload_max_filesize.');
        }

        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        // Use false for 3rd parameter ($formatData) to get RAW unrounded values (e.g. keeps .99)
        $rows = $worksheet->toArray(null, true, false, false);
        
        if (count($rows) <= 1) {
            sendError('File Excel kosong atau hanya berisi header.');
        }

        // Remove header row
        $header = array_shift($rows);
        
        $importedCount = 0;
        $members = [];
        
        foreach ($rows as $row) {
            // Mapping: NO, KODE, NIK, NAMA, BAGIAN, DEPARTEMEN, API_QRCODE
            if (count($row) < 4 || (empty($row[2]) && empty($row[3]))) continue; 

            $no_urut = $row[0] ?? '';
            $kode = $row[1] ?? '';
            $nik = $row[2] ?? '';
            $nama = $row[3] ?? '';
            $bagian = $row[4] ?? '';
            $departemen = $row[5] ?? '';
            $api_qrcode = $row[6] ?? '';

            $stmt = $conn->prepare("INSERT INTO members (no_urut, kode, nik, nama, bagian, departemen, api_qrcode) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                sendError("Database prepare error: " . $conn->error);
            }
            
            $stmt->bind_param("sssssss", $no_urut, $kode, $nik, $nama, $bagian, $departemen, $api_qrcode);
            
            if ($stmt->execute()) {
                $id = $stmt->insert_id;
                $members[] = [
                    'id' => $id,
                    'nama' => $nama,
                    'nik' => $nik,
                    'bagian' => $bagian
                ];
                $importedCount++;
            }
        }

        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'count' => $importedCount,
            'members' => $members
        ]);

    } else {
        sendError('No file uploaded or invalid request method.');
    }
} catch (Exception $e) {
    sendError('Exception: ' . $e->getMessage());
} catch (Error $e) {
    sendError('Fatal Error: ' . $e->getMessage());
}
