<!-- SIDEBAR -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

$db = getDB();

$id_mhs = $_SESSION['id_ref'];

$stmt = $db->prepare("SELECT * FROM mhs WHERE id_mhs = ?");
$stmt->bind_param("i", $id_mhs);
$stmt->execute();

$mahasiswa = $stmt->get_result()->fetch_assoc();
?>
<style>

.sidebar{
    width:260px;
    height:100vh;
background: linear-gradient(to right,  #3b82f6, #5ea6f7);
    position:fixed;
    left:0;
    top:0;
    color:white;
    display:flex;
    flex-direction:column;
}
.topbar{
    position:fixed;
    top:0;
    left:260px;
    right:0;
    height:70px;
    background:linear-gradient(135deg,#07122b,#1d4ed8);
    display:flex;
    align-items:center;
    padding:0 25px;
    z-index:1000;
    box-shadow:0 2px 10px rgba(0,0,0,.15);
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
}

.brand img{
    width:40px;
    height:40px;
}

.brand h4{
    color:white;
    margin:0;
    font-size:20px;
}

.brand small{
    color:#cbd5e1;
}
.logo-section{
    text-align:center;
    padding:30px 20px;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

.logo-section img{
    width:70px;
    margin-bottom:10px;
}

.logo-section h3{
    margin:0;
    font-size:32px;
    font-weight:700;
}

.logo-section p{
    margin-top:5px;
    color:#3b82f6;
    font-size:15px;
}

.profile-section{
    text-align:center;
    padding:25px 15px;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

.sidebar-photo{
    width:90px;
    height:90px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #3b82f6;
    margin-bottom:12px;
}

.profile-section h4{
    margin:0;
    color:white;
    font-size:18px;
    font-weight:600;
}

.profile-section p{
    margin-top:5px;
    color: #000000;
    font-size:13px;
}

.menu{
    padding:20px 0;
    flex:1;
}


.menu a{
    display:flex;
    align-items:center;
    gap:15px;
    padding:18px 30px;
    color: #000000;
    text-decoration:none;
    font-size:17px;
    transition:0.3s;
}

.menu a:hover{
    background: #08122c;
    color:white;
}

.menu a.active{
    background:#0d2f66;
    color:#38bdf8;
    border-left:4px solid #38bdf8;
}

.logout{
    color:#ef4444 !important;
}

.content{
    margin-left:260px;
    padding-top:90px;
}

</style>

<div class="sidebar">
    

<div class="profile-section">

    <?php if(!empty($mahasiswa['foto'])): ?>

        <img
        src="../uploads/foto_mhs/<?= $mahasiswa['foto']; ?>"
        class="sidebar-photo">

    <?php else: ?>

        <img
        src="https://ui-avatars.com/api/?name=<?= urlencode($mahasiswa['nama']); ?>&size=150"
        class="sidebar-photo">

    <?php endif; ?>

    <h4><?= $mahasiswa['nama']; ?></h4>

    <p><?= $mahasiswa['nim']; ?></p>

</div>

    <div class="menu">

        <a href="dashboard_mahasiswa.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='dashboard_mahasiswa.php' ? 'active' : '' ?>">
            🏠 Dashboard
        </a>

        <a href="profil_mhs.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='profil_mhs.php' ? 'active' : '' ?>">
            👤 Profil
        </a>

        <a href="matkul.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='matkul.php' ? 'active' : '' ?>">
            📚 Mata Kuliah
        </a>

        <a href="nilai.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='nilai.php' ? 'active' : '' ?>">
            📊 Nilai
        </a>

        <a href="../logout.php" class="logout">
            🚪 Logout
        </a>

    </div>

</div>