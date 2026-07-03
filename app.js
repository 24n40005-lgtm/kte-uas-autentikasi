let currentTab = 'user';
let activeView = 'login-view';

function bufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function base64ToBuffer(base64) {
    let str = base64.replace(/-/g, '+').replace(/_/g, '/');
    while (str.length % 4) {
        str += '=';
    }
    let binaryString = window.atob(str);
    let len = binaryString.length;
    let bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

document.addEventListener('DOMContentLoaded', () => {
    if (INITIAL_STATE.adminLoggedIn) {
        showAdminDashboard(INITIAL_STATE.adminUsername);
    } else if (INITIAL_STATE.userLoggedIn) {
        showUserDashboard(INITIAL_STATE.userName, INITIAL_STATE.userEmail, INITIAL_STATE.userPhone);
    } else {
        switchView('login-view');
    }

    registerServiceWorker();
});

function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Service Worker terdaftar di scope:', reg.scope))
            .catch(err => console.error('Pendaftaran Service Worker gagal:', err));
    }
}

function switchView(viewId) {
    const views = ['login-view', 'admin-register-view', 'otp-view', 'user-view', 'admin-view'];
    views.forEach(id => {
        const viewEl = document.getElementById(id);
        if (viewEl) {
            viewEl.classList.add('hidden');
            viewEl.classList.remove('wide');
        }
    });

    const targetEl = document.getElementById(viewId);
    if (targetEl) {
        targetEl.classList.remove('hidden');
        if (viewId === 'admin-view') {
            targetEl.classList.add('wide');
        }
        activeView = viewId;
    }

    const logoutBtn = document.getElementById('header-logout-btn');
    if (viewId === 'admin-view' || viewId === 'user-view') {
        logoutBtn.classList.remove('hidden');
    } else {
        logoutBtn.classList.add('hidden');
    }
}

function switchLoginTab(tab) {
    currentTab = tab;
    const tabUser = document.getElementById('tab-user');
    const tabAdmin = document.getElementById('tab-admin');
    const panelUser = document.getElementById('panel-user');
    const panelAdmin = document.getElementById('panel-admin');

    if (tab === 'user') {
        tabUser.classList.add('active');
        tabAdmin.classList.remove('active');
        panelUser.classList.remove('hidden');
        panelAdmin.classList.add('hidden');
    } else {
        tabUser.classList.remove('active');
        tabAdmin.classList.add('active');
        panelUser.classList.add('hidden');
        panelAdmin.classList.remove('hidden');
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let iconClass = 'fa-circle-check';
    if (type === 'error') iconClass = 'fa-circle-xmark';
    if (type === 'info') iconClass = 'fa-circle-info';
    if (type === 'warning') iconClass = 'fa-circle-exclamation';

    toast.innerHTML = `
        <span class="toast-icon"><i class="fa-solid ${iconClass}"></i></span>
        <span class="toast-text">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function onGoogleSignIn(response) {
    if (!response.credential) {
        showToast("Gagal menerima kredensial Google.", "error");
        return;
    }

    showToast("Login Google SSO Berhasil! Memproses verifikasi...", "info");
    
    fetch('api/login-user-google.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            prepareOtpScreen(data);
        } else {
            showToast(data.message || "Gagal memproses login Google.", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Koneksi ke backend bermasalah.", "error");
    });
}



function prepareOtpScreen(data) {
    document.getElementById('otp-phone-display').innerText = data.phone;
    
    document.getElementById('otp-input-container').classList.add('hidden');
    
    const requestBtn = document.getElementById('request-otp-btn');
    requestBtn.classList.remove('hidden');
    requestBtn.disabled = false;
    requestBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP ke WhatsApp`;

    const inputs = document.querySelectorAll('.otp-input');
    inputs.forEach(input => input.value = '');
    
    switchView('otp-view');
}

function handleRequestOtp(event) {
    event.preventDefault();
    const requestBtn = document.getElementById('request-otp-btn');
    
    requestBtn.disabled = true;
    requestBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Mengirim OTP...`;

    showToast("Mengirimkan kode OTP...", "info");

    fetch('api/send-otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            showToast(data.message, "success");
            
            requestBtn.classList.add('hidden');
            document.getElementById('otp-input-container').classList.remove('hidden');
            
            setTimeout(() => {
                document.querySelectorAll('.otp-input')[0].focus();
            }, 100);
        } else {
            showToast(data.message || "Gagal mengirimkan OTP.", "error");
            requestBtn.disabled = false;
            requestBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP ke WhatsApp`;
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Terjadi kesalahan jaringan.", "error");
        requestBtn.disabled = false;
        requestBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Kirim Kode OTP ke WhatsApp`;
    });
}

function moveOtpFocus(input, index) {
    input.value = input.value.replace(/[^0-9]/g, '');
    if (input.value && index < 6) {
        document.querySelectorAll('.otp-input')[index].focus();
    }
}

function backspaceOtpFocus(input, event) {
    if (event.key === "Backspace" && !input.value) {
        const inputs = document.querySelectorAll('.otp-input');
        const index = Array.from(inputs).indexOf(input);
        if (index > 0) {
            inputs[index - 1].focus();
        }
    }
}

function handleOtpVerify(event) {
    event.preventDefault();
    const inputs = document.querySelectorAll('.otp-input');
    let otp = '';
    inputs.forEach(input => otp += input.value);

    if (otp.length < 6) {
        showToast("Silakan masukkan kode OTP 6-digit lengkap.", "warning");
        return;
    }

    fetch('api/login-user-otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ otp: otp })
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            showToast(data.message, "success");
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message || "Kode OTP salah.", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Gagal melakukan verifikasi OTP.", "error");
    });
}

async function handleAdminRegister(event) {
    event.preventDefault();
    const username = document.getElementById('admin-reg-username').value.trim();

    if (!username) {
        showToast("Silakan masukkan username admin.", "warning");
        return;
    }

    showToast("Menginisialisasi modul biometrik...", "info");

    try {
        const checkRes = await fetch(`api/register-admin-options.php?username=${encodeURIComponent(username)}`);
        const checkData = await checkRes.json();
        
        if (checkData.status === 'error') {
            throw new Error(checkData.message);
        }

        const challenge = new Uint8Array(32);
        window.crypto.getRandomValues(challenge);
        const userId = new Uint8Array(16);
        window.crypto.getRandomValues(userId);

        const credential = await navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: {
                    name: "Keamanan Transaksi Elektronik",
                    id: window.location.hostname
                },
                user: {
                    id: userId,
                    name: username,
                    displayName: username
                },
                pubKeyCredParams: [
                    { type: "public-key", alg: -7 },   // ES256
                    { type: "public-key", alg: -257 }  // RS256
                ],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "required",
                    residentKey: "required",
                    requireResidentKey: true
                },
                timeout: 60000,
                attestation: "none"
            }
        });

        if (!credential) {
            throw new Error("Pendaftaran biometrik dibatalkan.");
        }

        const cred = {
            id: credential.id,
            rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
            type: credential.type,
            userId: btoa(String.fromCharCode(...userId)),
            response: {
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject)))
            }
        };

        showToast("Menyimpan kunci biometrik...", "info");

        const saveRes = await fetch('api/register-admin-submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                credential: cred
            })
        });
        
        const saveResult = await saveRes.json();

        if (saveResult.status === 'success') {
            showToast(saveResult.message, "success");
            document.getElementById('admin-reg-username').value = '';
            setTimeout(() => {
                switchView('login-view');
                switchLoginTab('admin');
                document.getElementById('admin-login-username').value = username;
            }, 1500);
        } else {
            showToast(saveResult.message || "Gagal menyimpan kredensial.", "error");
        }

    } catch (err) {
        console.error(err);
        showToast(err.message || "Registrasi Biometrik Gagal.", "error");
    }
}

async function handleAdminLogin(event) {
    event.preventDefault();
    const username = document.getElementById('admin-login-username').value.trim();

    if (!username) {
        showToast("Silakan masukkan username admin.", "warning");
        return;
    }

    showToast("Mengambil data kunci biometrik...", "info");

    try {
        const optRes = await fetch(`api/login-admin-options.php?username=${encodeURIComponent(username)}`);
        const optData = await optRes.json();

        if (optData.status === 'error') {
            throw new Error(optData.message);
        }

        const savedRawIdBase64 = optData.raw_id; // raw_id base64 dari DB
        
        const savedRawIdBuffer = base64urlToUint8Array(savedRawIdBase64);

        showToast("Silakan pindai sidik jari / Windows Hello Anda...", "info");

        const options = {
            publicKey: {
                challenge: crypto.getRandomValues(new Uint8Array(32)), // challenge acak
                timeout: 60000,
                userVerification: "required",
                allowCredentials: [{
                    id: savedRawIdBuffer,
                    type: "public-key",
                    transports: ["internal"]
                }]
            }
        };

        const credential = await navigator.credentials.get(options);
        
        if (!credential) {
            throw new Error("Verifikasi dibatalkan oleh pengguna.");
        }

        const rawIdBase64 = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));

        showToast("Memverifikasi kecocokan kunci...", "info");

        const submitRes = await fetch('api/login-admin-submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                raw_id: rawIdBase64
            })
        });
        
        const submitData = await submitRes.json();

        if (submitData.status === 'success') {
            showToast(submitData.message, "success");
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(submitData.message || "Autentikasi biometrik gagal.", "error");
        }

    } catch (err) {
        console.error(err);
        showToast(err.message || "Autentikasi Biometrik Gagal.", "error");
    }
}

function base64urlToUint8Array(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/')
        + '='.repeat((4 - base64url.length % 4) % 4);
    const raw = atob(base64);
    return new Uint8Array([...raw].map(c => c.charCodeAt(0)));
}

function handleLogout() {
    fetch('api/logout.php')
    .then(res => res.json())
    .then(result => {
        showToast(result.message, "success");
        setTimeout(() => {
            location.href = 'index.php';
        }, 1000);
    })
    .catch(err => {
        console.error(err);
        showToast("Gagal melakukan logout.", "error");
    });
}

function showUserDashboard(name, email, phone) {
    document.getElementById('user-name-display').innerText = name;
    document.getElementById('user-email-display').innerText = email;
    document.getElementById('user-phone-display').innerText = phone;
    switchView('user-view');
}

function showAdminDashboard(username) {
    document.getElementById('admin-name-display').innerText = username;
    switchView('admin-view');
    loadUsersList();
}

function loadUsersList() {
    const tableBody = document.getElementById('user-table-body');
    tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">Memuat data user...</td></tr>';

    fetch('api/crud-users.php')
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            const users = data.data;
            if (users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada user terdaftar. Silakan tambah user baru.</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            users.forEach(user => {
                // Shorten SHA-512 hash to keep UI clean
                const shortHash = user.email_hash.substring(0, 15) + '...';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.id}</td>
                    <td><strong>${escapeHtml(user.name)}</strong></td>
                    <td title="${user.email_hash}" style="font-family: monospace; font-size: 0.8rem; color: var(--text-secondary);">${shortHash}</td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>+${escapeHtml(user.phone)}</td>
                    <td><small>${user.created_at}</small></td>
                    <td><small>${user.updated_at}</small></td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn-icon edit" onclick="openCrudModal('edit', ${user.id}, '${escapeJs(user.email)}', '${escapeJs(user.name)}', '${escapeJs(user.phone)}')" title="Edit User"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon delete" onclick="deleteUser(${user.id})" title="Hapus User"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        } else {
            tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--error); padding: 2rem;">${data.message || 'Gagal memuat user.'}</td></tr>`;
        }
    })
    .catch(err => {
        console.error(err);
        tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: var(--error); padding: 2rem;">Koneksi gagal saat memuat user.</td></tr>';
    });
}

function openCrudModal(mode, id = null, email = '', name = '', phone = '') {
    const modal = document.getElementById('crud-modal');
    const title = document.getElementById('modal-title');
    const subtitle = document.getElementById('modal-subtitle');
    
    document.getElementById('user-id-field').value = id || '';
    document.getElementById('user-email-field').value = email;
    document.getElementById('user-name-field').value = name;
    document.getElementById('user-phone-field').value = phone;

    if (mode === 'add') {
        title.innerText = 'Tambah User';
        subtitle.innerText = 'Input data pengguna baru ke database';
    } else {
        title.innerText = 'Edit User';
        subtitle.innerText = 'Perbarui data pengguna di database';
    }

    modal.classList.remove('hidden');
}

function closeCrudModal() {
    document.getElementById('crud-modal').classList.add('hidden');
}

function handleCrudSubmit(event) {
    event.preventDefault();
    const id = document.getElementById('user-id-field').value;
    const name = document.getElementById('user-name-field').value.trim();
    const email = document.getElementById('user-email-field').value.trim();
    const phone = document.getElementById('user-phone-field').value.trim();

    const payload = { name, email, phone };
    let method = 'POST';

    if (id) {
        payload.id = id;
        method = 'PUT';
    }

    fetch('api/crud-users.php', {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            showToast(data.message, "success");
            closeCrudModal();
            loadUsersList();
        } else {
            showToast(data.message || "Gagal menyimpan data user.", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Terjadi kesalahan jaringan saat menyimpan.", "error");
    });
}

function deleteUser(id) {
    if (!confirm("Apakah Anda yakin ingin menghapus user ini secara permanen dari database?")) {
        return;
    }

    fetch('api/crud-users.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        if (status === 200 && data.status === 'success') {
            showToast(data.message, "success");
            loadUsersList();
        } else {
            showToast(data.message || "Gagal menghapus user.", "error");
        }
    })
    .catch(err => {
        console.error(err);
        showToast("Terjadi kesalahan saat menghapus user.", "error");
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function escapeJs(text) {
    if (!text) return '';
    return text.toString()
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}
