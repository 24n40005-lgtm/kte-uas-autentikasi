<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'projectkte');


define('GOOGLE_CLIENT_ID', '229297870241-49buel50od2dasi7bnehsnv6bbj2alc2.apps.googleusercontent.com');


define('FONNTE_TOKEN', 'A4cVhDjvGcH7Xj7Vqggp');


define('ENCRYPTION_KEY', 'kte_secret_encryption_key_32_chars_long!!');

function encrypt_email($email) {
    $ciphering = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;
    $encryption_iv = substr(hash('sha256', ENCRYPTION_KEY), 0, $iv_length);
    $encryption_key = hash('sha256', ENCRYPTION_KEY);
    
    return openssl_encrypt($email, $ciphering, $encryption_key, $options, $encryption_iv);
}

function decrypt_email($encrypted_email) {
    $ciphering = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($ciphering);
    $options = 0;
    $decryption_iv = substr(hash('sha256', ENCRYPTION_KEY), 0, $iv_length);
    $decryption_key = hash('sha256', ENCRYPTION_KEY);
    
    return openssl_decrypt($encrypted_email, $ciphering, $decryption_key, $options, $decryption_iv);
}

function hash_sha512($email) {
    return hash('sha512', strtolower(trim($email)));
}
