<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['pending_user'])) {
        throw new Exception("Sesi login tidak valid. Silakan masuk menggunakan Google SSO kembali.");
    }

    if (!defined('FONNTE_TOKEN') || empty(FONNTE_TOKEN)) {
        throw new Exception("Token Fonnte belum dikonfigurasi di config.php. Silakan hubungi Administrator.");
    }

    $pending = $_SESSION['pending_user'];
    $phone = trim($pending['phone']);
    $name = $pending['name'];
    $emailHash = $pending['email_hash'];
    $email = $pending['email'];

    $otp = strval(rand(100000, 999999));
    $otpExpiry = time() + (5 * 60); // Berlaku 5 menit

    $_SESSION['user_otp'] = $otp;
    $_SESSION['user_otp_expiry'] = $otpExpiry;
    $_SESSION['user_otp_email_hash'] = $emailHash;
    $_SESSION['user_otp_email_display'] = $email;
    $_SESSION['user_otp_name'] = $name;
    $_SESSION['user_otp_phone'] = $phone;

    $message = "Kode OTP Keamanan Transaksi Anda adalah: *{$otp}*.\n\nKode ini berlaku selama 5 menit. Tolong jangan bagikan kode ini kepada siapa pun.";
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $phone,
            'message' => $message,
            'countryCode' => '62',
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . FONNTE_TOKEN
        ),
    ));
    
    $fonnteResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        throw new Exception("Gagal menghubungi server Fonnte: " . $curlError);
    }

    $resData = json_decode($fonnteResponse, true);
    if (!isset($resData['status']) || $resData['status'] != true) {
        $detailError = $resData['reason'] ?? 'Kesalahan tidak diketahui dari Fonnte.';
        throw new Exception("Fonnte gagal mengirimkan OTP: " . $detailError);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Kode OTP berhasil dikirim ke WhatsApp Anda.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
