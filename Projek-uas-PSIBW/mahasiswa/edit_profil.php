<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

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

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $email = $_POST['email'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];

    $foto = $mahasiswa['foto'];

    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){

        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);

        $namaFile = time() . "_" . uniqid() . "." . $ext;

        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            "../uploads/foto_mhs/" . $namaFile
        );

        $foto = $namaFile;
    }

    $update = $db->prepare("
        UPDATE mhs
        SET
            email = ?,
            no_hp = ?,
            alamat = ?,
            foto = ?
        WHERE id_mhs = ?
    ");

    $update->bind_param(
        "ssssi",
        $email,
        $no_hp,
        $alamat,
        $foto,
        $id_mhs
    );

    if($update->execute()){

        $success = "Profil berhasil diperbarui";

        $stmt->execute();
        $mahasiswa = $stmt->get_result()->fetch_assoc();

    }else{

        $error = "Gagal memperbarui profil";

    }

}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Edit Profil</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:20px;
}

.preview{
    width:150px;
    height:150px;
    object-fit:cover;
    border-radius:50%;
    border:4px solid #0d6efd;
}

</style>

</head>

<body>

<div class="container py-5">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Edit Profil Mahasiswa</h3>
        </div>

        <div class="card-body">

            <?php if($success): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="text-center mb-4">

                    <?php if(!empty($mahasiswa['foto'])): ?>

                        <img
                        src="../uploads/foto_mhs/<?= $mahasiswa['foto']; ?>"
                        class="preview">

                    <?php else: ?>

                        <img
                        src="https://ui-avatars.com/api/?name=<?= urlencode($mahasiswa['nama']); ?>&size=200"
                        class="preview">

                    <?php endif; ?>

                </div>

                <div class="mb-3">
                    <label>NIM</label>
                    <input
                    type="text"
                    class="form-control"
                    value="<?= $mahasiswa['nim']; ?>"
                    readonly>
                </div>

                <div class="mb-3">
                    <label>Nama</label>
                    <input
                    type="text"
                    class="form-control"
                    value="<?= $mahasiswa['nama']; ?>"
                    readonly>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?= $mahasiswa['email']; ?>">
                </div>

                <div class="mb-3">
                    <label>No HP</label>
                    <input
                    type="text"
                    name="no_hp"
                    class="form-control"
                    value="<?= $mahasiswa['no_hp']; ?>">
                </div>

                <div class="mb-3">
                    <label>Alamat</label>
                    <textarea
                    name="alamat"
                    class="form-control"
                    rows="4"><?= $mahasiswa['alamat']; ?></textarea>
                </div>

                <div class="mb-3">
                    <label>Ganti Foto</label>
                    <input
                    type="file"
                    name="foto"
                    class="form-control">
                </div>

                <button
                type="submit"
                class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a
                href="profil_mhs.php"
                class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        </div>

    </div>

</div>

</body>
</html>