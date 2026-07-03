<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

try {
    $username = trim($_GET['username'] ?? '');

    if (empty($username)) {
        throw new Exception("Username admin tidak boleh kosong.");
    }

    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT raw_id_base64url FROM admin_credentials WHERE username = ?");
    $stmt->execute([$username]);
    $rawId = $stmt->fetchColumn();

    if (!$rawId) {
        throw new Exception("Username admin '$username' tidak ditemukan atau belum mendaftarkan biometrik.");
    }

    echo json_encode([
        'status' => 'success',
        'raw_id' => $rawId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
