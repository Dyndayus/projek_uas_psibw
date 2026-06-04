<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

$query = "
SELECT
    k.kode_mk,
    k.nama_mk,
    k.sks,
    k.semester,
    d.nama AS nama_dosen
FROM kuliah k
LEFT JOIN dosen d
ON k.id_dosen = d.id_dosen
ORDER BY k.semester ASC
";

$result = $db->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mata Kuliah</title>

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
    background:#2563eb;
    color:white;
    padding:6px 12px;
    border-radius:10px;
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
        <h1>📚 Mata Kuliah</h1>
        <p>Daftar mata kuliah yang tersedia</p>
    </div>

    <div class="table-box">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode MK</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Semester</th>
                    <th>Dosen Pengampu</th>
                </tr>
            </thead>

            <tbody>

            <?php if($result->num_rows > 0): ?>

                <?php $no = 1; ?>

                <?php while($row = $result->fetch_assoc()): ?>

                <tr>

                    <td><?= $no++; ?></td>

                    <td>
                        <span class="badge">
                            <?= $row['kode_mk']; ?>
                        </span>
                    </td>

                    <td><?= $row['nama_mk']; ?></td>

                    <td><?= $row['sks']; ?></td>

                    <td><?= $row['semester']; ?></td>

                    <td><?= $row['nama_dosen']; ?></td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        Tidak ada data mata kuliah
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>