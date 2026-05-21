<?php
require_once '../config/db.php';
requireRole(['dosen']);

$conn = getDB();

// 1. Ambil data semua mahasiswa untuk pilihan dropdown
$mhs_query = "SELECT id_mhs, nim, nama FROM mhs ORDER BY nim ASC";
$mhs_result = $conn->query($mhs_query);

// 2. REVISI SINKRON: Menggunakan nama_mk dan kode_mk sesuai dengan struktur database kelompokmu
$kuliah_query = "SELECT id_kuliah, nama_mk, kode_mk FROM kuliah ORDER BY nama_mk ASC";
$kuliah_result = $conn->query($kuliah_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Mahasiswa</title>
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
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-4">
            <h4 class="fw-bold text-primary mb-4">Input Nilai Akademik</h4>
            
            <form id="formInputNilai">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Mata Kuliah</label>
                    <select name="id_kuliah" class="form-select" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php while($row = $kuliah_result->fetch_assoc()): ?>
                            <option value="<?= $row['id_kuliah'] ?>"><?= htmlspecialchars($row['kode_mk'] . ' - ' . $row['nama_mk']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Mahasiswa</label>
                    <select name="id_mhs" class="form-select" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php while($row = $mhs_result->fetch_assoc()): ?>
                            <option value="<?= $row['id_mhs'] ?>"><?= htmlspecialchars($row['nim'] . ' - ' . $row['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Nilai Angka (0 - 100)</label>
                        <input type="number" name="nilai_angka" class="form-control" min="0" max="100" placeholder="Contoh: 85.5" step="0.01" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026" required>
                    </div>
                </div>

                <button type="submit" id="btnSimpan" class="btn btn-primary w-100 fw-bold py-2 mt-3">Simpan Nilai Mahasiswa</button>
            </form>
            
            <div id="alertMessage" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('formInputNilai').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSimpan');
    const alertMsg = document.getElementById('alertMessage');
    
    btn.disabled = true;
    btn.innerHTML = 'Memproses...';
    alertMsg.innerHTML = '';

    const formData = new FormData(e.target);
    const dataObj = Object.fromEntries(formData.entries());

    // Hitung Nilai Huruf Otomatis Sebelum Dikirim ke API kelompok
    const angka = parseFloat(dataObj.nilai_angka);
    let huruf = 'E';
    
    if (angka >= 85) {
        huruf = 'A';
    } else if (angka >= 75) {
        huruf = 'B+';
    } else if (angka >= 65) {
        huruf = 'B';
    } else if (angka >= 55) {
        huruf = 'C+';
    } else if (angka >= 45) {
        huruf = 'C';
    }

    dataObj.nilai_huruf = huruf;

    try {
        const response = await fetch('../api/nilai/store.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataObj)
        });

        const rawText = await response.text();
        const jsonStart = rawText.indexOf('{');
        if (jsonStart === -1) throw new Error("Respon server kotor");
        
        const result = JSON.parse(rawText.substring(jsonStart));

        if (result.status === 'success') {
            alertMsg.innerHTML = `<div class="alert alert-success border-0 text-center">${result.message}</div>`;
            document.getElementById('formInputNilai').reset(); 
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alertMsg.innerHTML = `<div class="alert alert-danger border-0 text-center small">${error.message}</div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Simpan Nilai Mahasiswa';
    }
});
</script>
</body>
</html>