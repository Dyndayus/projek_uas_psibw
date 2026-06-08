<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$id_mhs = $_SESSION['id_ref'];

$query = "
SELECT
    k.id_kuliah,
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
    background: #f1f5f9;
}

.container{
    width:95%;
    margin:30px auto;
}

.header{
    margin-bottom:25px;
}

.header h1{
    color: #1e293b;
}

.header p{
    color: #64748b;
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
    background:#3b82f6;
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

.ambil-btn{
    background: #16a34a;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
}

.ambil-btn:hover{
    background:#15803d;
}
</style>
</head>

<body>

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
                    <th>Aksi</th>
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

                    <td>
    <button
        class="ambil-btn"
        onclick="ambilMK(<?= $row['id_kuliah']; ?>)">
        Ambil
    </button>
</td>

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

<script>

const idMahasiswa = <?= $id_mhs ?>;

function ambilMK(idKuliah){

    if(!confirm('Ambil mata kuliah ini?')){
        return;
    }

    fetch('../api/nilai/ambil_krs.php', {

        method:'POST',

        headers:{
            'Content-Type':'application/json'
        },

        body:JSON.stringify({
            id_kuliah:idKuliah
        })

    })

    .then(response => response.json())

    .then(data => {

        alert(data.message);

        if(data.success){
            location.reload();
        }

    })

    .catch(error => {

        console.error(error);

        alert('Terjadi kesalahan');

    });

}

</script>
</div>

</body>
</html>