<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php'; 

$adminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$userLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$adminUsername = $_SESSION['admin_username'] ?? '';
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email_display'] ?? ''; 
$userPhone = $_SESSION['user_phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keamanan Transaksi Elektronik - UAS</title>
  
  <meta name="description" content="Tugas Akhir UAS Keamanan Transaksi Elektronik - PWA, Google SSO, Fonnte OTP, WebAuthn.">
  <meta name="theme-color" content="#6366f1">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="icons/icon-192.jpg">
  
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

  <header>
    <div class="logo-container">
      <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <div class="logo-text">Malcolm Website</div>
    </div>
    <div class="nav-links">
      <button id="header-logout-btn" class="nav-btn hidden" onclick="handleLogout()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</button>
    </div>
  </header>

  <main>
    
    <div id="login-view" class="view-container">
      <div class="glass-card">
        <h2 class="card-title">Selamat Datang</h2>
        <p class="card-subtitle">Sistem Autentikasi Transaksi Elektronik</p>
        
        <div class="auth-tabs">
          <button id="tab-user" class="tab-btn active" onclick="switchLoginTab('user')"><i class="fa-solid fa-user"></i> User Login</button>
          <button id="tab-admin" class="tab-btn" onclick="switchLoginTab('admin')"><i class="fa-solid fa-user-shield"></i> Admin Login</button>
        </div>

        <div id="panel-user">
          <div class="info-box">
            <span class="info-box-icon"><i class="fa-solid fa-circle-info"></i></span>
            <p>Login menggunakan Akun Google Anda. Jika terdaftar, sistem akan mengirimkan OTP ke WhatsApp Anda.</p>
          </div>
          
          <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
            <div id="g_id_onload"
                 data-client_id="<?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?>"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="onGoogleSignIn"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="pill"
                 data-theme="filled_blue"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left">
            </div>
          </div>


        </div>

        <div id="panel-admin" class="hidden">
          <div class="info-box">
            <span class="info-box-icon"><i class="fa-solid fa-fingerprint"></i></span>
            <p>Autentikasi biometrik menggunakan Windows Hello, Sidik Jari, atau PIN yang sudah terdaftar.</p>
          </div>
          <form id="admin-login-form" onsubmit="handleAdminLogin(event)">
            <div class="form-group">
              <label for="admin-login-username">Username Admin</label>
              <div class="input-wrapper">
                <span class="input-icon"><i class="fa-solid fa-user-lock"></i></span>
                <input type="text" id="admin-login-username" placeholder="Masukkan username admin" required>
              </div>
            </div>
            <button type="submit" class="btn-primary"><i class="fa-solid fa-key"></i> Masuk dengan Biometrik</button>
            <div style="margin-top: 1.5rem; text-align: center;">
              <a href="#" onclick="switchView('admin-register-view')" style="color: var(--accent-cyan); font-size: 0.9rem; text-decoration: none; font-weight: 500;"><i class="fa-solid fa-plus-circle"></i> Daftar Biometrik Admin Baru</a>
            </div>
          </form>
        </div>

      </div>
    </div>

    <div id="admin-register-view" class="view-container hidden">
      <div class="glass-card">
        <h2 class="card-title">Registrasi Admin</h2>
        <p class="card-subtitle">Pendaftaran Kunci Keamanan Biometrik</p>
        
        <div class="info-box">
          <span class="info-box-icon"><i class="fa-solid fa-shield-heart"></i></span>
          <p>Daftarkan perangkat keras sidik jari/Windows Hello Anda pada sistem untuk masuk ke panel pengelolaan user.</p>
        </div>

        <form id="admin-register-form" onsubmit="handleAdminRegister(event)">
          <div class="form-group">
            <label for="admin-reg-username">Username Admin Baru</label>
            <div class="input-wrapper">
              <span class="input-icon"><i class="fa-solid fa-user-plus"></i></span>
              <input type="text" id="admin-reg-username" placeholder="Masukkan username" required>
            </div>
          </div>
          <button type="submit" class="btn-primary"><i class="fa-solid fa-fingerprint"></i> Daftarkan Kunci Biometrik</button>
          
          <div style="margin-top: 1.5rem; text-align: center;">
            <a href="#" onclick="switchView('login-view')" style="color: var(--text-secondary); font-size: 0.9rem; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Login</a>
          </div>
        </form>
      </div>
    </div>

    <div id="otp-view" class="view-container hidden">
      <div class="glass-card">
        <h2 class="card-title">Verifikasi 2-Faktor</h2>
        <p class="card-subtitle">Verifikasi ganda menggunakan kode OTP WhatsApp</p>
        
        <div class="info-box">
          <span class="info-box-icon"><i class="fa-brands fa-whatsapp"></i></span>
          <p>Kirim kode OTP ke nomor WhatsApp berakhiran <strong id="otp-phone-display">xxxx</strong>.</p>
        </div>

        <button type="button" id="request-otp-btn" class="btn-primary" onclick="handleRequestOtp(event)" style="margin-bottom: 1.5rem;">
          <i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP ke WhatsApp
        </button>

        <div id="otp-input-container" class="hidden">


          <form id="otp-form" onsubmit="handleOtpVerify(event)">
            <div class="otp-wrapper">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 1)" onkeydown="backspaceOtpFocus(this, event)">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 2)" onkeydown="backspaceOtpFocus(this, event)">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 3)" onkeydown="backspaceOtpFocus(this, event)">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 4)" onkeydown="backspaceOtpFocus(this, event)">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 5)" onkeydown="backspaceOtpFocus(this, event)">
              <input type="text" class="otp-input" maxlength="1" required oninput="moveOtpFocus(this, 6)" onkeydown="backspaceOtpFocus(this, event)">
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 1rem;"><i class="fa-solid fa-shield-check"></i> Verifikasi OTP</button>
          </form>
        </div>

        <div style="margin-top: 1.5rem; text-align: center;">
          <a href="#" onclick="switchView('login-view')" style="color: var(--text-secondary); font-size: 0.9rem; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
      </div>
    </div>

    <div id="user-view" class="view-container hidden">
      <div class="glass-card" style="text-align: center;">
        <div class="avatar" style="margin: 0 auto 1.5rem; width: 72px; height: 72px; font-size: 2rem;">
          <i class="fa-solid fa-circle-user"></i>
        </div>
        <h2 class="card-title">Halo, <span id="user-name-display">User</span>!</h2>
        <p class="card-subtitle">Anda berhasil masuk ke portal aman</p>

        <div class="info-box" style="background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.2); color: #34d399; text-align: left;">
          <span class="info-box-icon"><i class="fa-solid fa-circle-check"></i></span>
          <p>Login Anda telah diverifikasi ganda via <strong>Google SSO</strong> dan <strong>MFA WhatsApp OTP</strong> (Fonnte API).</p>
        </div>

        <div style="text-align: left; margin: 1.5rem 0; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid var(--border-color);">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-secondary);">Email Akun:</span>
            <strong id="user-email-display">user@example.com</strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-secondary);">No. WhatsApp:</span>
            <strong id="user-phone-display">628xxx</strong>
          </div>
        </div>

        <button class="btn-secondary" onclick="handleLogout()"><i class="fa-solid fa-right-from-bracket"></i> Keluar</button>
      </div>
    </div>

    <div id="admin-view" class="view-container wide hidden">
      <div class="glass-card">
        
        <div class="dashboard-header">
          <div class="dashboard-user-info">
            <div class="avatar"><i class="fa-solid fa-user-shield"></i></div>
            <div class="user-meta">
              <h3>Admin <span id="admin-name-display">Root</span></h3>
              <p>Pengelolaan Akses Pengguna (CRUD)</p>
            </div>
          </div>
          <button class="btn-primary" style="width: auto;" onclick="openCrudModal('add')"><i class="fa-solid fa-plus"></i> Tambah User Baru</button>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Hash Email (SHA-512)</th>
                <th>Email Display</th>
                <th>WhatsApp</th>
                <th>Dibuat</th>
                <th>Diperbarui</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="user-table-body">
              <tr>
                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat data user...</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </main>

  <div id="crud-modal" class="modal-overlay hidden">
    <div class="modal-content glass-card">
      <h2 id="modal-title" class="card-title">Tambah User</h2>
      <p id="modal-subtitle" class="card-subtitle">Input data pengguna baru</p>
      
      <form id="crud-form" onsubmit="handleCrudSubmit(event)">
        <input type="hidden" id="user-id-field">
        
        <div class="form-group">
          <label for="user-name-field">Nama Lengkap</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fa-solid fa-signature"></i></span>
            <input type="text" id="user-name-field" placeholder="Nama Lengkap" required>
          </div>
        </div>

        <div class="form-group">
          <label for="user-email-field">Alamat Email</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="user-email-field" placeholder="email@contoh.com" required>
          </div>
        </div>

        <div class="form-group">
          <label for="user-phone-field">No. WhatsApp</label>
          <div class="input-wrapper">
            <span class="input-icon"><i class="fa-solid fa-phone"></i></span>
            <input type="tel" id="user-phone-field" placeholder="Format: 628123456789 atau 08123456789" required>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
          <button type="button" class="btn-secondary" onclick="closeCrudModal()"><i class="fa-solid fa-xmark"></i> Batal</button>
          <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Data</button>
        </div>
      </form>
    </div>
  </div>

  <div class="toast-container" id="toast-container"></div>

  <footer>
    <p>&copy; 2026 Keamanan Transaksi Elektronik.</p>
  </footer>

  <script>
    const INITIAL_STATE = {
      adminLoggedIn: <?php echo $adminLoggedIn ? 'true' : 'false'; ?>,
      userLoggedIn: <?php echo $userLoggedIn ? 'true' : 'false'; ?>,
      adminUsername: <?php echo json_encode($adminUsername); ?>,
      userName: <?php echo json_encode($userName); ?>,
      userEmail: <?php echo json_encode($userEmail); ?>,
      userPhone: <?php echo json_encode($userPhone); ?>
    };
  </script>
  <script src="app.js?v=3"></script>
</body>
</html>
