
<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();
$session_email = $_SESSION['username']; // Mengambil email dari session login

// Ambil data dosen berdasarkan email untuk mengisi form awal
$query = "SELECT * FROM dosen WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_email);
$stmt->execute();
$result = $stmt->get_result();
$dosen = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard_dosen.php">SIAKAD DOSEN</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link text-white" href="dashboard_dosen.php">← Kembali ke Dashboard</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 700px;">
        <div class="card-body p-4">
            <h4 class="fw-bold text-success mb-4">Edit Profil Saya</h4>
            
            <form id="formEditProfil" enctype="multipart/form-data">
                <input type="hidden" name="id_dosen" value="<?= $dosen['id_dosen'] ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($dosen['status']) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($dosen['email']) ?>">

                <div class="text-center mb-4">
                    <?php 
                    $foto_path = !empty($dosen['foto']) ? '../uploads/foto_dosen/' . $dosen['foto'] : 'https://via.placeholder.com/150';
                    ?>
                    <img src="<?= $foto_path ?>" class="rounded-circle img-thumbnail shadow-sm mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    <div class="small text-muted">Email: <?= htmlspecialchars($dosen['email']) ?></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">NIDN</label>
                        <input type="text" name="nidn" class="form-control" value="<?= htmlspecialchars($dosen['nidn']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($dosen['nama']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" class="form-control" value="<?= $dosen['tgl_lahir'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-control">
                            <option value="L" <?= $dosen['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $dosen['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No. HP / WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($dosen['no_hp'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pendidikan Terakhir</label>
                        <input type="text" name="pendidikan_terakhir" class="form-control" value="<?= htmlspecialchars($dosen['pendidikan_terakhir'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($dosen['jabatan'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($dosen['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ganti Foto Profil (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>

                <button type="submit" id="btnSimpan" class="btn btn-success w-100 fw-bold py-2 mt-4">Simpan Perubahan Profil</button>
            </form>
            
            <div id="alertMessage" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('formEditProfil').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSimpan');
    const alertMsg = document.getElementById('alertMessage');
    
    btn.disabled = true;
    btn.innerHTML = 'Menyimpan...';
    alertMsg.innerHTML = '';

    const formData = new FormData(e.target);

    try {
        // Tembak langsung ke file API dosen yang sudah dibuat temanmu
        const response = await fetch('../api/dosen/update.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center">${result.message}</div>`;
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center">${error.message}</div>`;
        btn.disabled = false;
        btn.innerHTML = 'Simpan Perubahan Profil';
    }
});
</script>
</body>
</html>