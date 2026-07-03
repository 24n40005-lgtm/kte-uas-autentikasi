<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak valid.");
    }

    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON);

    $inputOtp = trim($data->otp ?? '');

    if (empty($inputOtp)) {
        throw new Exception("Kode OTP tidak boleh kosong.");
    }

    if (!isset($_SESSION['user_otp']) || !isset($_SESSION['user_otp_expiry'])) {
        throw new Exception("Sesi OTP tidak ditemukan atau belum di-generate.");
    }

    if (time() > $_SESSION['user_otp_expiry']) {
        unset($_SESSION['user_otp']);
        unset($_SESSION['user_otp_expiry']);
        throw new Exception("Kode OTP telah kadaluwarsa (berlaku 5 menit). Silakan minta kode OTP baru.");
    }

    if ($inputOtp !== $_SESSION['user_otp']) {
        throw new Exception("Kode OTP yang Anda masukkan salah.");
    }

    $emailHash = $_SESSION['user_otp_email_hash'];
    $emailDisplay = $_SESSION['user_otp_email_display'];
    $name = $_SESSION['user_otp_name'];
    $phone = $_SESSION['user_otp_phone'];

    unset($_SESSION['user_otp']);
    unset($_SESSION['user_otp_expiry']);
    unset($_SESSION['user_otp_email_hash']);
    unset($_SESSION['user_otp_email_display']);
    unset($_SESSION['user_otp_name']);
    unset($_SESSION['user_otp_phone']);

    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_email_hash'] = $emailHash;
    $_SESSION['user_email_display'] = $emailDisplay;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_phone'] = $phone;

    echo json_encode([
        'status' => 'success',
        'message' => "Login berhasil! Selamat datang $name.",
        'redirect' => 'dashboard_user'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
