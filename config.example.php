<?php
// config.example.php
// File template konfigurasi untuk dideploy (Salin file ini menjadi config.php di server lokal Anda)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database MySQL/MariaDB
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projectkte');

// Konfigurasi Google SSO (Masukkan Client ID Anda di sini)
define('GOOGLE_CLIENT_ID', 'MASUKKAN_CLIENT_ID_GOOGLE_ANDA.apps.googleusercontent.com');

// Konfigurasi Fonnte WhatsApp API (Masukkan Token Fonnte Anda di sini)
define('FONNTE_TOKEN', 'MASUKKAN_TOKEN_FONNTE_ANDA');

// Kunci enkripsi untuk mengamankan data email display (AES-256-CBC)
// GANTI DENGAN KUNCI RAHASIA LAIN YANG UNIK SEPANJANG 32 KARAKTER
define('ENCRYPTION_KEY', 'kte_secret_encryption_key_32_chars_long!!');

/**
 * Fungsi enkripsi teks (untuk email display)
 */
function encrypt_email($email) {
    $ciphering = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;
    $encryption_iv = substr(hash('sha256', ENCRYPTION_KEY), 0, $iv_length);
    $encryption_key = hash('sha256', ENCRYPTION_KEY);
    
    return openssl_encrypt($email, $ciphering, $encryption_key, $options, $encryption_iv);
}

/**
 * Fungsi dekripsi teks (untuk email display)
 */
function decrypt_email($encrypted_email) {
    $ciphering = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;
    $decryption_iv = substr(hash('sha256', ENCRYPTION_KEY), 0, $iv_length);
    $decryption_key = hash('sha256', ENCRYPTION_KEY);
    
    return openssl_decrypt($encrypted_email, $ciphering, $decryption_key, $options, $decryption_iv);
}

/**
 * Fungsi untuk menghasilkan hash SHA-512 (untuk lookup pencarian email)
 */
function hash_sha512($email) {
    return hash('sha512', strtolower(trim($email)));
}
