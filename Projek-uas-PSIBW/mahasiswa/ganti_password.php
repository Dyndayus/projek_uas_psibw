<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);
    $konfirmasi    = trim($_POST['konfirmasi']);

    $id_user = $_SESSION['id_user'];

    $stmt = $db->prepare("
        SELECT password
        FROM user
        WHERE id_user = ?
    ");

    $stmt->bind_param("i", $id_user);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    // Jika password disimpan plaintext
    if ($password_lama != $user['password']) {

        $pesan = "<div class='error'>Password lama salah!</div>";

    } elseif ($password_baru != $konfirmasi) {

        $pesan = "<div class='error'>Konfirmasi password tidak cocok!</div>";

    } else {

        $stmt = $db->prepare("
            UPDATE user
            SET password = ?
            WHERE id_user = ?
        ");

        $stmt->bind_param(
            "si",
            $password_baru,
            $id_user
        );

        $stmt->execute();

        $pesan = "<div class='success'>Password berhasil diubah!</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Ganti Password</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    background:#f1f5f9;
}

.container{
    width:500px;
    margin:50px auto;
}

.card{
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

h1{
    margin-bottom:20px;
    color:#1e293b;
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:bold;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:10px;
}

button{
    width:100%;
    background: #ffad0a;
    color:black;
    border:none;
    padding:14px;
    border-radius:10px;
    cursor:pointer;
    font-size:16px;
}

button:hover{
    background: #cd8800;
}

.success{
    background: #dcfce7;
    color: #166534;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

.error{
    background: #fee2e2;
    color: #991b1b;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

.back{
    display:inline-block;
    margin-bottom:20px;
    text-decoration:none;
    color: #eb2525;
}

</style>

</head>
<body>

<div class="container">

    <a href="dashboard_mahasiswa.php" class="back">
        ← Kembali ke Dashboard
    </a>

    <div class="card">

        <h1>🔒 Ganti Password</h1>

        <?= $pesan ?>

        <form method="POST">

            <div class="form-group">
                <label>Password Lama</label>
                <input
                    type="password"
                    name="password_lama"
                    required>
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <input
                    type="password"
                    name="password_baru"
                    required>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input
                    type="password"
                    name="konfirmasi"
                    required>
            </div>

            <button type="submit">
                Simpan Password Baru
            </button>

        </form>

    </div>

</div>

</body>
</html>