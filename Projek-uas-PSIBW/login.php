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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #0f172a; 
            --accent-blue: #2563eb; 
            --light-blue: #f0f5ff;
            --text-dark: #1e293b;
        }

        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); 
            min-height: 100vh; 
            font-family: 'Inter', sans-serif; 
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }


        .login-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 960px;
            width: 100%;
            display: flex;
            min-height: 580px;
        }

  
        .login-sidebar {
            background: linear-gradient(135deg, rgba(187, 209, 255, 0.9) 0%, rgba(29, 78, 216, 0.95) 100%), 
                        url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ0e8rOOwb6o7gadw3B5pVOibQ0l3yjScJsbA&s') center/cover;
            padding: 45px;
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            position: relative;
        }

   
        .login-form-area {
            padding: 50px;
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .brand-title {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-dark);
        }

        /* Desain Input Modern */
        .input-group-text {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #64748b;
            border-radius: 12px 0 0 12px;
            padding-left: 15px;
        }

        .form-control { 
            border-radius: 0 12px 12px 0; 
            padding: 14px 16px;
            border-color: #cbd5e1;
            color: var(--text-dark);
            font-size: 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: var(--accent-blue);
        }


        .btn-admin { 
            background: var(--accent-blue); 
            border: none; 
            border-radius: 12px; 
            padding: 14px; 
            font-weight: 600; 
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-admin:hover { 
            background: #1d4ed8; 
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            color: white;
        }

        .alert {
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Responsif untuk layar HP */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 450px; }
            .login-sidebar { display: none; }
            .login-form-area { width: 100%; padding: 35px 25px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-sidebar">
        <div>
            <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-semibold mb-3">
                <i class="fa-solid fa-graduation-cap me-1"></i> Kelompok Empat
            </span>
            <h2 class="fw-bold lh-base text-white mt-2">Sistem Informasi Akademik <br>Universitas Riau</h2>
        </div>
        <div>
            <p class="mb-0 opacity-75 small">
                <i class="fa-solid fa-shield-halved me-1"></i> Copyright &copy; 2026 SIAKAD UNRI
            </p>
        </div>
    </div>

    <div class="login-form-area">
        <div class="mb-4">
            <h3 class="brand-title mb-1">Log In</h3>
            <p class="text-secondary small">Masuk untuk mengelola sistem akademik Anda.</p>
        </div>

        <form id="loginForm" autocomplete="off">
    <div class="mb-3">
        <label class="form-label fw-medium small text-secondary">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
            <input type="text" id="username" class="form-control" placeholder="Masukkan username Anda" required autofocus autocomplete="off">
        </div>
    </div>
    
    <div class="mb-4">
        <label class="form-label fw-medium small text-secondary">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
        </div>
    </div>
    
    <button type="submit" id="btnSubmit" class="btn btn-admin w-100">
        Masuk Sistem <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
    </button>
</form>

        <div id="message" class="mt-3"></div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const msg = document.getElementById('message');

    msg.innerHTML = '';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Autentikasi Sistem...';

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

        const rawText = await response.text();
        console.log("Raw Response:", rawText);

        const result = JSON.parse(rawText);

        if (result.status === 'success') {
            msg.innerHTML = `<div class="alert alert-success border-0 text-center py-2"><i class="fa-solid fa-circle-check me-1"></i> Login Berhasil! Mengalihkan...</div>`;
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
        msg.innerHTML = `<div class="alert alert-danger border-0 text-center py-2 small"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${error.message}</div>`;
        btn.disabled = false;
        btn.innerHTML = 'Masuk Sistem <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>';
    }
});
</script>

</body>
</html>