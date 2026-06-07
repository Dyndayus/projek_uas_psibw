<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

$id_mhs = $_SESSION['id_ref'];

$stmt = $db->prepare("
    SELECT
        k.kode_mk,
        k.nama_mk,
        n.nilai_angka,
        n.nilai_huruf,
        n.tahun_ajaran
    FROM nilai n
    JOIN kuliah k
        ON n.id_kuliah = k.id_kuliah
    WHERE n.id_mhs = ?
    ORDER BY n.tahun_ajaran DESC
");

$stmt->bind_param("i", $id_mhs);
$stmt->execute();

$nilai = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Nilai Mahasiswa</title>

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
    width:95%;
    margin:30px auto;
}

.header{
    margin-bottom:25px;
}

.header h1{
    color:#1e293b;
}

.header p{
    color:#64748b;
}

.table-box{
    background:white;
    border-radius:20px;
    padding:25px;
    box-shadow:0 10px 25px rgba(0,0,0,.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#2563eb;
    color:white;
    padding:15px;
    text-align:left;
}

table td{
    padding:15px;
    border-bottom:1px solid #e2e8f0;
}

table tr:hover{
    background:#f8fafc;
}

.badge{
    color:white;
    padding:6px 12px;
    border-radius:10px;
    font-weight:bold;
}

.A{
    background:#16a34a;
}

.B{
    background:#2563eb;
}

.C{
    background:#f59e0b;
}

.D{
    background:#dc2626;
}

.btn{
    display:inline-block;
    text-decoration:none;
    background:#2563eb;
    color:white;
    padding:10px 15px;
    border-radius:10px;
    margin-bottom:20px;
}

.btn:hover{
    background:#1d4ed8;
}

</style>

</head>

<body>

<div class="container">

    <a href="dashboard_mahasiswa.php" class="btn">
        ← Kembali ke Dashboard
    </a>

    <div class="header">
        <h1>📝 Nilai Mahasiswa</h1>
        <p>Riwayat nilai akademik</p>
    </div>

    <div class="table-box">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode MK</th>
                    <th>Mata Kuliah</th>
                    <th>Nilai Angka</th>
                    <th>Nilai Huruf</th>
                    <th>Tahun Ajaran</th>
                </tr>
            </thead>

            <tbody>

            <?php if($nilai->num_rows > 0): ?>

                <?php $no = 1; ?>

                <?php while($row = $nilai->fetch_assoc()): ?>

                <tr>

                    <td><?= $no++; ?></td>

                    <td><?= $row['kode_mk']; ?></td>

                    <td><?= $row['nama_mk']; ?></td>

                    <td><?= $row['nilai_angka']; ?></td>

                    <td>

                        <?php
                        $huruf = strtoupper(substr($row['nilai_huruf'],0,1));
                        ?>

                        <span class="badge <?= $huruf ?>">
                            <?= $row['nilai_huruf']; ?>
                        </span>

                    </td>

                    <td><?= $row['tahun_ajaran']; ?></td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        Belum ada data nilai
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>