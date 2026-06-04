<!-- SIDEBAR -->

<style>

.sidebar{
    width:260px;
    height:100vh;
    background:#07122b;
    position:fixed;
    left:0;
    top:0;
    color:white;
    display:flex;
    flex-direction:column;
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

.menu{
    padding:20px 0;
    flex:1;
}

.menu a{
    display:flex;
    align-items:center;
    gap:15px;
    padding:18px 30px;
    color:#9ca3af;
    text-decoration:none;
    font-size:17px;
    transition:0.3s;
}

.menu a:hover{
    background:#0f1e45;
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
}

</style>

<div class="sidebar">

    <div class="logo-section">

        <img src="../assets/logo_unri.png" alt="Logo">

        <h3>SIAKAD</h3>

        <p>Universitas Riau</p>

    </div>

    <div class="menu">

        <a href="dashboard_mahasiswa.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='dashboard_mahasiswa.php' ? 'active' : '' ?>">
            🏠
            Dashboard
        </a>

        <a href="profil_mhs.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='profil_mhs.php' ? 'active' : '' ?>">
            👤
            Profil
        </a>

        <a href="matkul.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='matkul.php' ? 'active' : '' ?>">
            📚
            Mata Kuliah
        </a>

        <a href="nilai.php"
           class="<?= basename($_SERVER['PHP_SELF'])=='nilai.php' ? 'active' : '' ?>">
            📊
            Nilai
        </a>

    </div>

    <div class="menu">

        <a href="../logout.php" class="logout">
            🚪
            Logout
        </a>

    </div>

</div>