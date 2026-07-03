<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak. Anda harus login sebagai Admin terlebih dahulu.'
    ]);
    exit;
}

$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();

            foreach ($users as &$user) {
                $user['email'] = decrypt_email($user['email_display']);
                unset($user['email_display']);
            }

            echo json_encode([
                'status' => 'success',
                'data' => $users
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'));
            
            $email = strtolower(trim($input->email ?? ''));
            $name = trim($input->name ?? '');
            $phone = trim($input->phone ?? '');

            if (empty($email) || empty($name) || empty($phone)) {
                throw new Exception("Seluruh field (Email, Nama, WhatsApp) harus diisi.");
            }

            // Validasi format email sederhana
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            // Validasi format WhatsApp sederhana (harus angka, awali dengan 62 atau 0)
            if (!preg_match('/^[0-9]+$/', $phone)) {
                throw new Exception("Nomor WhatsApp hanya boleh berisi angka.");
            }

            // Normalisasi nomor telepon ke format internasional (misal 08123 -> 628123)
            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            }

            $emailHash = hash_sha512($email);
            $emailDisplay = encrypt_email($email);

            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email_hash = ?");
            $stmtCheck->execute([$emailHash]);
            if ($stmtCheck->fetch()) {
                throw new Exception("User dengan email tersebut sudah terdaftar.");
            }

            $stmtInsert = $db->prepare("INSERT INTO users (email_hash, email_display, name, phone) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$emailHash, $emailDisplay, $name, $phone]);

            echo json_encode([
                'status' => 'success',
                'message' => "User '$name' berhasil ditambahkan!"
            ]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'));
            
            $id = intval($input->id ?? 0);
            $email = strtolower(trim($input->email ?? ''));
            $name = trim($input->name ?? '');
            $phone = trim($input->phone ?? '');

            if ($id <= 0 || empty($email) || empty($name) || empty($phone)) {
                throw new Exception("ID, Email, Nama, dan WhatsApp harus diisi.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid.");
            }

            if (!preg_match('/^[0-9]+$/', $phone)) {
                throw new Exception("Nomor WhatsApp hanya boleh berisi angka.");
            }

            if (substr($phone, 0, 1) === '0') {
                $phone = '62' . substr($phone, 1);
            }

            $emailHash = hash_sha512($email);
            $emailDisplay = encrypt_email($email);

            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email_hash = ? AND id != ?");
            $stmtCheck->execute([$emailHash, $id]);
            if ($stmtCheck->fetch()) {
                throw new Exception("Email tersebut sudah digunakan oleh user lain.");
            }

            $stmtUpdate = $db->prepare("UPDATE users SET email_hash = ?, email_display = ?, name = ?, phone = ? WHERE id = ?");
            $stmtUpdate->execute([$emailHash, $emailDisplay, $name, $phone, $id]);

            echo json_encode([
                'status' => 'success',
                'message' => "Data user '$name' berhasil diperbarui!"
            ]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'));
            $id = intval($input->id ?? ($_GET['id'] ?? 0));

            if ($id <= 0) {
                throw new Exception("ID user tidak valid.");
            }

            $stmtGetName = $db->prepare("SELECT name FROM users WHERE id = ?");
            $stmtGetName->execute([$id]);
            $userRecord = $stmtGetName->fetch();

            if (!$userRecord) {
                throw new Exception("User tidak ditemukan.");
            }

            $name = $userRecord['name'];

            $stmtDelete = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmtDelete->execute([$id]);

            echo json_encode([
                'status' => 'success',
                'message' => "User '$name' berhasil dihapus!"
            ]);
            break;

        default:
            http_response_code(405);
            throw new Exception("Metode HTTP tidak didukung.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
