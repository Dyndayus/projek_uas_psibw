<?php
session_start();

require_once '../config/db.php';

// Cek login mahasiswa
if (
    !isset($_SESSION['id_user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'mahasiswa'
) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

if (!$db) {
    die("Koneksi database gagal");
}

$id_mhs = $_SESSION['id_ref'];

// Ambil data mahasiswa
$stmt = $db->prepare("SELECT * FROM mhs WHERE id_mhs = ?");
$stmt->bind_param("i", $id_mhs);
$stmt->execute();
$mahasiswa = $stmt->get_result()->fetch_assoc();

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan");
}

// Total mata kuliah
$totalMK = $db->query("SELECT COUNT(*) AS total FROM kuliah")
    ->fetch_assoc()['total'];

// Total nilai mahasiswa
$stmtNilai = $db->prepare("
    SELECT COUNT(*) AS total
    FROM nilai
    WHERE id_mhs = ?
");
$stmtNilai->bind_param("i", $id_mhs);
$stmtNilai->execute();
$totalNilai = $stmtNilai->get_result()->fetch_assoc()['total'];

// Rata-rata nilai
$stmtAvg = $db->prepare("
    SELECT AVG(nilai_angka) AS rata
    FROM nilai
    WHERE id_mhs = ?
");
$stmtAvg->bind_param("i", $id_mhs);
$stmtAvg->execute();
$rataNilai = $stmtAvg->get_result()->fetch_assoc()['rata'];

// Riwayat nilai
$stmtRiwayat = $db->prepare("
    SELECT
        k.nama_mk,
        n.nilai_angka,
        n.nilai_huruf,
        n.tahun_ajaran
    FROM nilai n
    JOIN kuliah k ON n.id_kuliah = k.id_kuliah
    WHERE n.id_mhs = ?
    ORDER BY n.id_nilai DESC
");

$stmtRiwayat->bind_param("i", $id_mhs);
$stmtRiwayat->execute();
$riwayat = $stmtRiwayat->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f0f3ff;
            ;
            display: flex;
        }

        .sidebar {
            width: 300px;
            background: #081120;
            color: white;
            min-height: 100vh;
        }

        .logo-section {
            text-align: center;
            padding: 40px 20px;
        }

        .logo-section img {
            width: 80px;
            margin-bottom: 15px;
        }

        .logo-section h2 {
            color: white;
            font-size: 28px;
        }

        .logo-section p {
            color: #4ea3ff;
            margin-top: 5px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin: 5px 0;
        }

        .menu a {
            display: block;
            padding: 15px 25px;
            color: #cbd5e1;
            text-decoration: none;
        }

        .menu a.logout {
            color: #ef4444;
        }

        .menu a.logout:hover {
            color: #dc2626;
        }

        .menu a:hover {
            background: #0f2747;
            color: white;
        }

        .content {
            flex: 1;
            padding: 35px;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 34px;
            color: #1e293b;
        }

        .header p {
            color: #64748b;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            padding: 25px;
            border-radius: 18px;
            color: white;
        }

        .blue,
        .green,
        .orange {
            background: linear-gradient(to bottom, #93c5fd, #3b82f6);
            color: #111827;
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card h1 {
            font-size: 38px;
        }

        .table-box {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .05);
        }

        .table-box h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(to bottom, #93c5fd, #3b82f6);
            color: #111827;
            padding: 14px;
            text-align: left;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        .badge {
            background: #16a34a;
            color: white;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        @media(max-width:768px) {

            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                min-height: auto;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar_mhs.php'; ?>
    <div class="topbar">

        <div class="brand">

            <img src="../assets/img/logo-unri.png" alt="Logo">

            <div>
                <h4>SIAKAD</h4>
                <small>Universitas Riau</small>
            </div>

        </div>

    </div>

    <div class="content">

        <div class="header">
            <h1>Dashboard Mahasiswa</h1>
            <p>
                Selamat datang kembali,
                <?= htmlspecialchars($mahasiswa['nama']); ?> 👋
            </p>
        </div>

        <div class="cards">

            <div class="card blue">
                <h3>Total Mata Kuliah</h3>
                <h1><?= $totalMK; ?></h1>
            </div>

            <div class="card green">
                <h3>Total Nilai</h3>
                <h1><?= $totalNilai; ?></h1>
            </div>

            <div class="card orange">
                <h3>Rata-rata Nilai</h3>
                <h1><?= number_format($rataNilai ?? 0, 2); ?></h1>
            </div>

        </div>

        <div class="table-box">

            <h2>Riwayat Nilai</h2>

            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Mata Kuliah</th>
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                        <th>Tahun Ajaran</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($riwayat->num_rows > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($row = $riwayat->fetch_assoc()): ?>

                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_mk']); ?></td>
                                <td><?= $row['nilai_angka']; ?></td>
                                <td>
                                    <span class="badge">
                                        <?= htmlspecialchars($row['nilai_huruf'] ?? 'Nilai belum diinput oleh Dosen'); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['tahun_ajaran']); ?></td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">Belum ada data nilai</td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</body>

</html>