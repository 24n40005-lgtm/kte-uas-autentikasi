<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak valid.");
    }

    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON);

    $credential = $data->credential ?? '';
    if (empty($credential)) {
        throw new Exception("Kredensial Google SSO tidak ditemukan.");
    }

    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($credential);
    
    $responseJSON = @file_get_contents($verifyUrl);
    if ($responseJSON === false) {
        throw new Exception("Gagal memverifikasi token Google SSO ke Google API.");
    }

    $tokenInfo = json_decode($responseJSON);
    if (!isset($tokenInfo->email)) {
        throw new Exception("Token Google SSO tidak valid atau tidak berisi email.");
    }

    if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID)) {
        if ($tokenInfo->aud !== GOOGLE_CLIENT_ID) {
            throw new Exception("Client ID tidak cocok (Mismatch).");
        }
    }

    $email = strtolower(trim($tokenInfo->email));

    if (empty($email)) {
        throw new Exception("Email tidak teridentifikasi.");
    }

    $emailHash = hash_sha512($email);

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email_hash = ?");
    $stmt->execute([$emailHash]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Email '$email' belum terdaftar di sistem. Silakan hubungi Admin untuk mendaftarkan akun Anda.");
    }

    $_SESSION['pending_user'] = [
        'email' => decrypt_email($user['email_display']),
        'email_hash' => $emailHash,
        'name' => $user['name'],
        'phone' => trim($user['phone'])
    ];

    $phone = trim($user['phone']);
    $maskedPhone = substr($phone, 0, 4) . 'xxxx' . substr($phone, -3);

    echo json_encode([
        'status' => 'success',
        'message' => 'Silakan verifikasi OTP untuk melanjutkan.',
        'phone' => $maskedPhone
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
