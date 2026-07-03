<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak valid.");
    }

    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);

    $username = trim($data['username'] ?? '');
    $clientRawId = trim($data['raw_id'] ?? '');

    if (empty($username) || empty($clientRawId)) {
        throw new Exception("Kredensial login tidak lengkap.");
    }

    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT raw_id_base64url FROM admin_credentials WHERE username = ?");
    $stmt->execute([$username]);
    $dbRawId = $stmt->fetchColumn();

    if (!$dbRawId) {
        throw new Exception("Admin tidak ditemukan.");
    }

    if ($clientRawId !== $dbRawId) {
        throw new Exception("Autentikasi biometrik tidak cocok.");
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;

    echo json_encode([
        'status' => 'success',
        'message' => "Selamat datang kembali, Admin $username!",
        'redirect' => 'dashboard_admin'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
