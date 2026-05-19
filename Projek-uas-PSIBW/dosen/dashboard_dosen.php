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
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        /* --- CARDS (BERANDA) --- */
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            flex: 1;
        }

        .card h3 {
            font-size: 2rem;
            color: var(--accent-color);
        }

        /* --- TABLES (MATAKULIAH & NILAI) --- */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--secondary-color);
            color: white;
        }

        tr:hover {
            background-color: #f1f2f6;
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
            width: 100%;
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
            <img src="uploads/foto_dosen/dosen_1778570746.png" alt="Logo Universitas" class="logo-univ">
            <span class="univ-name">SIAKAD UNIVERSITAS</span>
        </div>
        <div class="nav-right">
            <div class="profile-click" onclick="bukaKonten('profil')">
                <span class="dosen-name">Dr. Budi Santoso, M.T.</span>
                <img src="uploads/foto_dosen/dosen_1778570746.png" alt="Foto Dosen" class="foto-profil">
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
                <p>Selamat datang di Sistem Informasi Akademik khusus Dosen. Silakan gunakan menu di sebelah kiri atau klik foto profil Anda untuk mengelola data akademik.</p>
                
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>2</h3>
                        <p>Matakuliah Diampu resmi</p>
                    </div>
                    <div class="card">
                        <h3>60</h3>
                        <p>Total Mahasiswa Aktif</p>
                    </div>
                </div>
            </section>

            <section id="konten-matakuliah" class="tab-konten">
                <h2>Matakuliah Saya</h2>
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
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="konten-nilai" class="tab-konten">
                <h2>Input Nilai Mahasiswa</h2>
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
                <button class="btn-simpan" onclick="alert('Data nilai berhasil disimpan! (Simulasi)')">Simpan Nilai</button>
            </section>

            <section id="konten-profil" class="tab-konten">
                <h2>Profil Saya</h2>
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
            // 1. Sembunyikan semua section konten terlebih dahulu
            let semuaKonten = document.getElementsByClassName('tab-konten');
            for (let i = 0; i < semuaKonten.length; i++) {
                semuaKonten[i].classList.remove('active');
            }

            // 2. Matikan status 'active' pada semua menu di sidebar
            let semuaMenu = document.getElementsByClassName('menu-item');
            for (let i = 0; i < semuaMenu.length; i++) {
                semuaMenu[i].classList.remove('active');
            }

            // 3. Tampilkan section konten yang sedang dipilih
            document.getElementById('konten-' + namaKonten).classList.add('active');
            
            // 4. Hidupkan status 'active' pada menu sidebar yang bersesuaian
            let menuAktif = document.getElementById('menu-' + namaKonten);
            if(menuAktif) {
                menuAktif.classList.add('active');
            }
        }
    </script>
</body>
</html>