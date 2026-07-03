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
    $stmt = $db->prepare("SELECT id FROM admin_credentials WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception("Username admin '$username' sudah terdaftar.");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Username tersedia.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
