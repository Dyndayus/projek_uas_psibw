<?php

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        margin: 0;
        background-color: #f4f6f9;
        color: #334155;
    }


    .wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }

    .main-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        overflow-x: hidden;
    }

    
    .sidebar {
        width: 260px;
        min-width: 260px;
        background-color: #1e2640;
        color: #94a3b8;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.05);
        z-index: 1000;
    }


    .sidebar-brand {
        padding: 25px 20px 20px 20px; 
        text-align: center;
    }

    .sidebar-brand img {
        width: 95px; 
        height: auto;
        object-fit: contain;
        filter: drop-shadow(0px 4px 10px rgba(0, 0, 0, 0.25)); 
        margin-bottom: 14px;
    }

    .sidebar-brand h4 {
        color: #ffffff;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .sidebar-brand small {
        color: #0ea5e9;
        font-size: 0.9rem;
        font-weight: 500;
        display: block;
    }

    .sidebar-menu {
        list-style: none;
        padding: 10px 0;
    }

    .sidebar-menu li {
        position: relative;
        margin: 2px 0;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 16px 28px;
        color: #94a3b8;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .sidebar-menu a:hover {
        background-color: #2a3454;
        color: #f8fafc;
    }

  
    .sidebar-menu li.active a {
        background-color: #2a3454;
        color: #0ea5e9;
        font-weight: 600;
    }

    .sidebar-menu li.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: #0ea5e9;
        z-index: 5;
    }

    .sidebar-divider {
        padding: 0 28px;
        margin: 20px 0;
    }

    .sidebar-divider hr {
        border: 0;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        margin: 0;
    }

    .sidebar-menu a.logout-btn {
        color: #ef4444;
    }

    .sidebar-menu a.logout-btn:hover {
        background-color: rgba(239, 68, 68, 0.08);
        color: #f87171;
    }

    .main-content {
        padding: 40px;
        flex: 1;
    }
</style>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="https://kompaspedia.kompas.id/wp-content/uploads/2020/08/logo_Universitas-Riau-thumb.png" alt="Logo UNRI">
        <h4>SIAKAD</h4>
        <small>Universitas Riau</small>
    </div>

    <ul class="sidebar-menu">
        <li class="<?= $current_page == 'dashboard_admin.php' ? 'active' : ''; ?>">
            <a href="dashboard_admin.php">
                <i class="bi bi-grid-1x2-fill me-3 fs-5"></i> Dashboard
            </a>
        </li>
        <li class="<?= $current_page == 'data_mahasiswa.php' ? 'active' : ''; ?>">
            <a href="data_mahasiswa.php">
                <i class="bi bi-mortarboard-fill me-3 fs-5"></i> Data Mahasiswa
            </a>
        </li>
        <li class="<?= $current_page == 'data_dosen.php' ? 'active' : ''; ?>">
            <a href="data_dosen.php">
                <i class="bi bi-person-badge-fill me-3 fs-5"></i> Data Dosen
            </a>
        </li>
        <li class="<?= $current_page == 'data_kuliah.php' ? 'active' : ''; ?>">
            <a href="data_kuliah.php">
                <i class="bi bi-journal-bookmark-fill me-3 fs-5"></i> Data Matakuliah
            </a>
        </li>

        <li class="sidebar-divider">
            <hr>
        </li>

        <li>
            <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')" class="logout-btn">
                <i class="bi bi-power me-3 fs-5"></i> Logout
            </a>
        </li>
    </ul>
</div>