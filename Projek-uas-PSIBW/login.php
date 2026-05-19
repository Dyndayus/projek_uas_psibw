<?php
session_start();
// Redirect otomatis jika session sudah ada
if (isset($_SESSION['role'])) { 
    $role = $_SESSION['role'];
    header("Location: $role/dashboard_$role.php"); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIAKAD UNRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); background: white; }
        .btn-primary { background: #4e73df; border: none; border-radius: 10px; padding: 12px; font-weight: 600; transition: 0.3s; }
        .btn-primary:hover { background: #2e59d9; transform: translateY(-2px); }
        .form-control { border-radius: 10px; padding: 12px; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary">SIAKAD UNRI</h3>
        <p class="text-muted small">Silakan login dengan akun Anda</p>
    </div>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" id="username" class="form-control" placeholder="Contoh: admin" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" id="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" id="btnSubmit" class="btn btn-primary w-100">Masuk Sekarang</button>
    </form>

    <div id="message" class="mt-4"></div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const msg = document.getElementById('message');

    msg.innerHTML = '';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menghubungkan...';

    try {
        const rootPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const apiUrl = `${rootPath}/api/auth/login.php`;

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: document.getElementById('username').value.trim(),
                password: document.getElementById('password').value.trim()
            })
        });

        // Tambahan debug: cek isi respon mentah
        const rawText = await response.text();
        console.log("Raw Response:", rawText);

        // Parse JSON dari respon mentah
        const result = JSON.parse(rawText);

        if (result.status === 'success') {
            msg.innerHTML = `<div class="alert alert-success border-0 text-center">Login Berhasil! Mengalihkan...</div>`;
            setTimeout(() => {
                const role = result.data.role;
                if (role === 'admin') {
                    window.location.href = 'admin/dashboard_admin.php';
                } else if (role === 'dosen') {
                    window.location.href = 'dosen/dashboard_dosen.php';
                } else if (role === 'mahasiswa') {
                    window.location.href = 'mahasiswa/dashboard_mahasiswa.php';
                } else {
                    alert("Role tidak dikenali: " + role);
                    location.reload();
                }
            }, 1200);
        } else {
            throw new Error(result.message || "Username atau Password salah.");
        }

    } catch (error) {
        console.error("Fetch Error:", error);
        msg.innerHTML = `<div class="alert alert-danger border-0 text-center small">${error.message}</div>`;
        btn.disabled = false;
        btn.innerText = 'Masuk Sekarang';
    }
});
</script>

</body>
</html>