<?php
session_start();

require_once '../config/db.php';

// Cek login mahasiswa
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$id_mhs = $_SESSION['id_ref'];

// Ambil data mahasiswa
$stmt = $db->prepare("SELECT * FROM mhs WHERE id_mhs = ?");
$stmt->bind_param("i", $id_mhs);
$stmt->execute();
$mahasiswa = $stmt->get_result()->fetch_assoc();

// Total mata kuliah
$totalMK = $db->query("SELECT COUNT(*) as total FROM kuliah")
              ->fetch_assoc()['total'];

// Total nilai mahasiswa
$stmtNilai = $db->prepare("SELECT COUNT(*) as total FROM nilai WHERE id_mhs = ?");
$stmtNilai->bind_param("i", $id_mhs);
$stmtNilai->execute();
$totalNilai = $stmtNilai->get_result()->fetch_assoc()['total'];

// Rata-rata nilai
$stmtAvg = $db->prepare("SELECT AVG(nilai_angka) as rata FROM nilai WHERE id_mhs = ?");
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
    ORDER BY n.created_at DESC
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

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            background:#f4f7fc;
            display:flex;
        }

        /* SIDEBAR */

        .sidebar{
            width:260px;
            height:100vh;
            background:linear-gradient(180deg,#2563eb,#1e40af);
            padding:25px;
            position:fixed;
            color:white;
        }

        .profile{
            text-align:center;
            margin-bottom:40px;
        }

        .profile img{
            width:95px;
            height:95px;
            border-radius:50%;
            object-fit:cover;
            border:4px solid white;
            margin-bottom:10px;
        }

        .profile h2{
            font-size:20px;
            margin-bottom:5px;
        }

        .profile p{
            font-size:14px;
            opacity:0.8;
        }

        .menu a{
            display:block;
            padding:14px 16px;
            color:white;
            text-decoration:none;
            margin-bottom:12px;
            border-radius:12px;
            transition:0.3s;
            font-weight:500;
        }

        .menu a:hover{
            background:rgba(255,255,255,0.2);
            transform:translateX(5px);
        }

        /* CONTENT */

        .content{
            margin-left:260px;
            width:100%;
            padding:35px;
        }

        .header{
            margin-bottom:30px;
        }

        .header h1{
            font-size:34px;
            color:#1e293b;
            margin-bottom:8px;
        }

        .header p{
            color:#64748b;
            font-size:16px;
        }

        /* CARDS */

        .cards{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:20px;
            margin-bottom:35px;
        }

        .card{
            padding:25px;
            border-radius:18px;
            color:white;
            position:relative;
            overflow:hidden;
            box-shadow:0 10px 25px rgba(0,0,0,0.08);
        }

        .card h3{
            font-size:17px;
            margin-bottom:10px;
        }

        .card h1{
            font-size:38px;
        }

        .blue{
            background:linear-gradient(135deg,#3b82f6,#1d4ed8);
        }

        .green{
            background:linear-gradient(135deg,#22c55e,#15803d);
        }

        .orange{
            background:linear-gradient(135deg,#f59e0b,#d97706);
        }

        /* TABLE */

        .table-box{
            background:white;
            border-radius:18px;
            padding:25px;
            box-shadow:0 10px 25px rgba(0,0,0,0.05);
        }

        .table-box h2{
            margin-bottom:20px;
            color:#1e293b;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        table th{
            background:#2563eb;
            color:white;
            padding:14px;
            text-align:left;
        }

        table td{
            padding:14px;
            border-bottom:1px solid #e2e8f0;
        }

        table tr:hover{
            background:#f8fafc;
        }

        .badge{
            background:#16a34a;
            color:white;
            padding:6px 12px;
            border-radius:10px;
            font-size:12px;
            font-weight:bold;
        }

        /* RESPONSIVE */

        @media(max-width:768px){

            body{
                flex-direction:column;
            }

            .sidebar{
                width:100%;
                height:auto;
                position:relative;
            }

            .content{
                margin-left:0;
                padding:20px;
            }

        }

    </style>

</head>
<body>

    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="profile">

            <?php if(!empty($mahasiswa['foto'])): ?>

                <img src="../assets/img/profile/<?= $mahasiswa['foto']; ?>">

            <?php else: ?>

                <img src="https://ui-avatars.com/api/?name=<?= urlencode($mahasiswa['nama']); ?>">

            <?php endif; ?>

            <h2><?= $mahasiswa['nama']; ?></h2>
            <p><?= $mahasiswa['nim']; ?></p>

        </div>

        <div class="menu">
            <a href="#">🏠 Dashboard</a>
            <a href="profil_mhs.php">👤 Profil</a>
            <a href="matkul.php">📚 Mata Kuliah</a>
            <a href="nilai.php">📝 Nilai</a>
            <a href="../logout.php">🚪 Logout</a>
        </div>

    </div>

    <!-- CONTENT -->

    <div class="content">

        <div class="header">
            <h1>Dashboard Mahasiswa</h1>
            <p>Selamat datang kembali, <?= $mahasiswa['nama']; ?> 👋</p>
        </div>

        <!-- CARD -->

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
                <h1><?= number_format($rataNilai ?? 0,2); ?></h1>
            </div>

        </div>

        <!-- TABLE -->

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

                    <?php if($riwayat->num_rows > 0): ?>

                        <?php $no = 1; ?>

                        <?php while($row = $riwayat->fetch_assoc()): ?>

                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $row['nama_mk']; ?></td>
                            <td><?= $row['nilai_angka']; ?></td>
                            <td>
                                <span class="badge">
                                    <?= $row['nilai_huruf']; ?>
                                </span>
                            </td>
                            <td><?= $row['tahun_ajaran']; ?></td>
                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">
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