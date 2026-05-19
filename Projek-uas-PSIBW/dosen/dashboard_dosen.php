<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SIAKAD</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-bg: #f8f9fa;
            --text-color: #333;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        /* --- NAVBAR --- */
        .navbar {
            background-color: #fff;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-univ {
            height: 45px;
        }

        .univ-name {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .nav-right .profile-click {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .nav-right .profile-click:hover {
            background-color: #f0f2f5;
        }

        .dosen-name {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .foto-profil {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-color);
        }

        /* --- CONTAINER UTAMA --- */
        .container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-color: var(--primary-color);
            color: #fff;
            padding-top: 20px;
            position: fixed;
            height: 100%;
        }

        .menu-list {
            list-style: none;
        }

        .menu-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 25px;
        }

        .menu-item:hover, .menu-item.active {
            background-color: var(--accent-color);
            padding-left: 25px;
        }

        .menu-item.logout {
            margin-top: 50px;
            background-color: #c0392b;
        }

        .menu-item.logout a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        /* --- CONTENT AREA --- */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            width: calc(100% - 260px);
        }

        .tab-konten {
            display: none; /* Sembunyikan konten secara default */
        }

        .tab-konten.active {
            display: block; /* Tampilkan konten yang aktif */
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .sub-title {
            color: #7f8c8d;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .section-header {
            margin-top: 30px;
            margin-bottom: 15px;
            color: var(--secondary-color);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }

        /* --- CARDS STATISTIK (BERANDA) --- */
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 5px solid var(--accent-color);
        }

        .card.card-matkul { border-left-color: var(--accent-color); }
        .card.card-mahasiswa { border-left-color: var(--success-color); }
        .card.card-jadwal { border-left-color: var(--warning-color); }

        .card-info h3 {
            font-size: 2rem;
            color: #2c3e50;
            line-height: 1.2;
        }

        .card-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .card-icon {
            font-size: 2.5rem;
            color: #bdc3c7;
        }

        /* --- TABLES (ALL SECTIONS) --- */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 10px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f1f2f6;
        }

        /* Badge status untuk kelas hari ini */
        .badge-status {
            background-color: #e8f8f5;
            color: #2ecc71;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* --- PROFIL SAYA --- */
        .profil-box {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            max-width: 600px;
        }

        .table-profil {
            box-shadow: none;
        }

        .table-profil td {
            border: none;
            padding: 12px 10px;
        }

        .table-profil td:first-child {
            width: 30%;
            font-weight: bold;
            color: var(--secondary-color);
        }

        /* --- INPUT NILAI --- */
        .input-nilai {
            width: 70px;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 300px;
            font-size: 0.95rem;
        }

        .btn-simpan {
            background-color: #2ecc71;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn-simpan:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="nav-left">
            <img src="https://via.placeholder.com/150x50?text=LOGO+UNIV" alt="Logo Universitas" class="logo-univ">
            <span class="univ-name">SIAKAD UNIVERSITAS</span>
        </div>
        <div class="nav-right">
            <div class="profile-click" onclick="bukaKonten('profil')">
                <span class="dosen-name">Dr. Budi Santoso, M.T.</span>
                <img src="../uploads/foto_dosen/dosen_1778570746.jpg" onerror="this.src='https://via.placeholder.com/150?text=Dosen'" alt="Foto Dosen" class="foto-profil">
            </div>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <ul class="menu-list">
                <li class="menu-item active" id="menu-beranda" onclick="bukaKonten('beranda')">
                    <i class="fa-solid fa-house"></i> <span>Beranda</span>
                </li>
                <li class="menu-item" id="menu-matakuliah" onclick="bukaKonten('matakuliah')">
                    <i class="fa-solid fa-book"></i> <span>Matakuliah Saya</span>
                </li>
                <li class="menu-item" id="menu-nilai" onclick="bukaKonten('nilai')">
                    <i class="fa-solid fa-pen-to-square"></i> <span>Input Nilai</span>
                </li>
                <li class="menu-item" id="menu-profil" onclick="bukaKonten('profil')">
                    <i class="fa-solid fa-user"></i> <span>Profil Saya</span>
                </li>
                <li class="menu-item logout">
                    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Keluar</span></a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            
            <section id="konten-beranda" class="tab-konten active">
                <h2>Selamat Datang, Dr. Budi Santoso, M.T.</h2>
                <p class="sub-title">Sistem Informasi Akademik Dosen | Universitas Terbuka</p>
                
                <div class="dashboard-cards">
                    <div class="card card-matkul">
                        <div class="card-info">
                            <h3>4</h3>
                            <p>Total Matakuliah</p>
                        </div>
                        <div class="card-icon"><i class="fa-solid fa-book-bookmark"></i></div>
                    </div>
                    <div class="card card-mahasiswa">
                        <div class="card-info">
                            <h3>145</h3>
                            <p>Jumlah Mahasiswa</p>
                        </div>
                        <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                    </div>
                    <div class="card card-jadwal">
                        <div class="card-info">
                            <h3>2</h3>
                            <p>Jadwal Hari Ini</p>
                        </div>
                        <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
                    </div>
                </div>

                <div class="section-header">
                    <i class="fa-solid fa-clock"></i> 
                    <span>Jadwal Saya Hari Ini (<?php 
                        // Array nama hari Indonesia berbasis PHP
                        $hari = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
                        echo $hari[date("w")]; 
                    ?>)</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Matkul</th>
                                <th>Nama Matkul</th>
                                <th>SKS</th>
                                <th>Jam Masuk</th>
                                <th>Ruangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>INF201</td>
                                <td><strong>Pemrograman Web</strong></td>
                                <td>3</td>
                                <td>08:00 - 10:30</td>
                                <td>Lab Komputer 3</td>
                                <td><span class="badge-status">Siap Mengajar</span></td>
                            </tr>
                            <tr>
                                <td>INF205</td>
                                <td><strong>Basis Data</strong></td>
                                <td>3</td>
                                <td>11:00 - 13:30</td>
                                <td>Ruang Teori 4.2</td>
                                <td><span class="badge-status">Siap Mengajar</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="konten-matakuliah" class="tab-konten">
                <h2>Matakuliah Saya</h2>
                <p class="sub-title">Daftar keseluruhan matakuliah yang Anda ampu pada semester ini.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Matkul</th>
                                <th>Nama Matkul</th>
                                <th>SKS</th>
                                <th>Jam Masuk</th>
                                <th>Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>INF201</td>
                                <td>Pemrograman Web</td>
                                <td>3</td>
                                <td>08:00 - 10:30</td>
                                <td>Lab Komputer 3</td>
                            </tr>
                            <tr>
                                <td>INF205</td>
                                <td>Basis Data</td>
                                <td>3</td>
                                <td>11:00 - 13:30</td>
                                <td>Ruang Teori 4.2</td>
                            </tr>
                            <tr>
                                <td>INF302</td>
                                <td>Rekayasa Perangkat Lunak</td>
                                <td>4</td>
                                <td>14:00 - 17:20</td>
                                <td>Ruang Teori 2.1</td>
                            </tr>
                            <tr>
                                <td>INF108</td>
                                <td>Logika Informatika</td>
                                <td>2</td>
                                <td>08:00 - 09:40</td>
                                <td>Ruang Teori 1.5</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="konten-nilai" class="tab-konten">
                <h2>Input Nilai Mahasiswa</h2>
                <p class="sub-title">Silakan pilih kelas dan masukkan nilai komponen mahasiswa.</p>
                <div class="form-group">
                    <label>Pilih Matakuliah:</label>
                    <select>
                        <option>INF201 - Pemrograman Web (Kelas A)</option>
                        <option>INF205 - Basis Data (Kelas B)</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Nilai Tugas</th>
                                <th>Nilai UTS</th>
                                <th>Nilai UAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2200101</td>
                                <td>Ahmad Rian</td>
                                <td><input type="number" class="input-nilai" value="85"></td>
                                <td><input type="number" class="input-nilai" value="80"></td>
                                <td><input type="number" class="input-nilai" value="88"></td>
                            </tr>
                            <tr>
                                <td>2200102</td>
                                <td>Siti Aminah</td>
                                <td><input type="number" class="input-nilai" value="90"></td>
                                <td><input type="number" class="input-nilai" value="85"></td>
                                <td><input type="number" class="input-nilai" value="92"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button class="btn-simpan" onclick="alert('Data nilai berhasil disimpan!')">Simpan Nilai</button>
            </section>

            <section id="konten-profil" class="tab-konten">
                <h2>Profil Saya</h2>
                <p class="sub-title">Informasi data diri resmi dosen pengajar.</p>
                <div class="profil-box">
                    <table class="table-profil">
                        <tr>
                            <td>NIDN</td>
                            <td>: 0412038801</td>
                        </tr>
                        <tr>
                            <td>Nama Lengkap</td>
                            <td>: Dr. Budi Santoso, M.T.</td>
                        </tr>
                        <tr>
                            <td>Nomor Telepon</td>
                            <td>: 0812-3456-7890</td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>: budi.santoso@univ.ac.id</td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>: Jl. Merdeka No. 45, Kota Pilihan</td>
                        </tr>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <script>
        function bukaKonten(namaKonten) {
            // Sembunyikan semua section konten
            let semuaKonten = document.getElementsByClassName('tab-konten');
            for (let i = 0; i < semuaKonten.length; i++) {
                semuaKonten[i].classList.remove('active');
            }

            // Nonaktifkan semua menu di sidebar
            let semuaMenu = document.getElementsByClassName('menu-item');
            for (let i = 0; i < semuaMenu.length; i++) {
                semuaMenu[i].classList.remove('active');
            }

            // Tampilkan section konten yang dipilih
            document.getElementById('konten-' + namaKonten).classList.add('active');
            
            // Set menu sidebar menjadi aktif jika tombol navigasi yang ditekan
            let menuAktif = document.getElementById('menu-' + namaKonten);
            if(menuAktif) {
                menuAktif.classList.add('active');
            }
        }
    </script>
</body>
</html>