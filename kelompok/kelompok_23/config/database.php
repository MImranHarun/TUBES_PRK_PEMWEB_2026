<?php

$host = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "febmart";

$conn = new mysqli($host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");