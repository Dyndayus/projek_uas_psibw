<?php
session_start();

// Hapus semua session
$_SESSION = [];

// Hancurkan session
session_destroy();

// Kembali ke login
header("Location: login.php");
exit();
?>