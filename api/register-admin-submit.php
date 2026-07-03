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
    $credential = $data['credential'] ?? null;

    if (empty($username) || !$credential) {
        throw new Exception("Data pendaftaran tidak lengkap.");
    }

    $rawId = $credential['rawId'];     
    $credentialId = $credential['id']; 
    $db = get_db_connection();
    
    $stmtCheck = $db->prepare("SELECT id FROM admin_credentials WHERE username = ?");
    $stmtCheck->execute([$username]);
    if ($stmtCheck->fetch()) {
        throw new Exception("Admin '$username' sudah terdaftar.");
    }

    $stmt = $db->prepare("INSERT INTO admin_credentials (username, raw_id_base64url, public_key) VALUES (?, ?, ?)");
    $stmt->execute([
        $username,
        $rawId,
        $credentialId
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => "Registrasi biometrik admin '$username' berhasil! Silakan login."
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
