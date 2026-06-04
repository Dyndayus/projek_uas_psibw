<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';

$db = getDB();

$id_mhs = $_SESSION['id_ref'];

$stmt = $db->prepare("
    SELECT *
    FROM mhs
    WHERE id_mhs = ?
");

$stmt->bind_param("i", $id_mhs);
$stmt->execute();

$mahasiswa = $stmt->get_result()->fetch_assoc();

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Profil Mahasiswa</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body{
    background:#f4f6f9;
}

.profile-card{
    border:none;
    border-radius:20px;
    overflow:hidden;
}

.profile-header{
    background:linear-gradient(135deg,#0d6efd,#4e73df);
    color:white;
    padding:20px;
}

.profile-img{
    width:180px;
    height:180px;
    border-radius:50%;
    object-fit:cover;
    border:5px solid white;
    box-shadow:0 5px 15px rgba(0,0,0,.2);
}

.info-table td,
.info-table th{
    padding:15px;
    vertical-align:middle;
}

.stat-card{
    border:none;
    border-radius:15px;
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
}

</style>
</head>

<body>

<div class="container py-5">

    <div class="card shadow profile-card">

        <div class="profile-header d-flex justify-content-between align-items-center">

            <h3 class="mb-0">
                <i class="bi bi-person-badge-fill"></i>
                Profil Mahasiswa
            </h3>


        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 text-center">

                    <?php if(!empty($mahasiswa['foto'])): ?>

                        <img
                        src="../uploads/foto_mhs/<?php echo $mahasiswa['foto']; ?>"
                        class="profile-img">

                    <?php else: ?>

                        <img
                        src="https://ui-avatars.com/api/?name=<?php echo urlencode($mahasiswa['nama']); ?>&size=200"
                        class="profile-img">

                    <?php endif; ?>

                    <h3 class="mt-4">
                        <?= $mahasiswa['nama']; ?>
                    </h3>

                    <p class="text-muted mb-1">
                        <?= $mahasiswa['nim']; ?>
                    </p>

                    <span class="badge bg-success px-3 py-2">
                        <?= ucfirst($mahasiswa['status']); ?>
                    </span>

                </div>

                <div class="col-md-8">

                    <table class="table info-table">
                        <div class="row mt-4">

                <div class="col-md-6 mb-2">
                    <a href="edit_profil.php"
                    class="btn btn-primary w-100">
                        <i class="bi bi-pencil-square"></i>
                        Edit Profil
                    </a>
                </div>

                <div class="col-md-6 mb-2">
                    <a href="ganti_password.php"
                    class="btn btn-warning w-100">
                        <i class="bi bi-shield-lock-fill"></i>
                        Ganti Password
                    </a>
                </div>

    </div>

                        <tr>
                            <th width="220">
                                <i class="bi bi-envelope-fill"></i>
                                Email
                            </th>
                            <td><?= $mahasiswa['email']; ?></td>
                        </tr>

                        <tr>
                            <th>
                                <i class="bi bi-telephone-fill"></i>
                                No HP
                            </th>
                            <td><?= $mahasiswa['no_hp']; ?></td>
                        </tr>

                        <tr>
                            <th>
                                <i class="bi bi-mortarboard-fill"></i>
                                Program Studi
                            </th>
                            <td><?= $mahasiswa['program_studi']; ?></td>
                        </tr>

                        <tr>
                            <th>
                                <i class="bi bi-book-fill"></i>
                                Semester
                            </th>
                            <td><?= $mahasiswa['semester']; ?></td>
                        </tr>

                        <tr>
                            <th>
                                <i class="bi bi-geo-alt-fill"></i>
                                Alamat
                            </th>
                            <td><?= $mahasiswa['alamat']; ?></td>
                        </tr>

                        <tr>
                            <th>
                                <i class="bi bi-calendar-event-fill"></i>
                                Tanggal Lahir
                            </th>
                            <td><?= $mahasiswa['tgl_lahir']; ?></td>
                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <div class="row mt-4">

        <div class="col-md-4 mb-3">

            <div class="card shadow stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-book-half fs-1 text-primary"></i>
                    <h5 class="mt-2">Semester</h5>
                    <h2><?= $mahasiswa['semester']; ?></h2>
                </div>
            </div>

        </div>

        <div class="col-md-4 mb-3">

            <div class="card shadow stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-patch-check-fill fs-1 text-success"></i>
                    <h5 class="mt-2">Status</h5>
                    <h2><?= ucfirst($mahasiswa['status']); ?></h2>
                </div>
            </div>

        </div>

        <div class="col-md-4 mb-3">

            <div class="card shadow stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-building fs-1 text-warning"></i>
                    <h5 class="mt-2">Program Studi</h5>
                    <p class="fw-bold">
                        <?= $mahasiswa['program_studi']; ?>
                    </p>
                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>