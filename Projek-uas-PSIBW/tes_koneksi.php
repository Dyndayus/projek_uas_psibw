<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "sistem_akademik"
);

if($conn->connect_error){
    die("Gagal");
}

echo "Koneksi berhasil";

?>