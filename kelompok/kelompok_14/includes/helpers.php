<?php
// 1. Fungsi untuk mencegah XSS (Cross Site Scripting)
// Dipakai di semua tampilan output variabel ke HTML
function e($teks) {
    return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
}

// 2. Fungsi untuk redirect (pindah halaman) yang aman
// Hanya memperbolehkan redirect ke internal halaman (tidak boleh link luar)
function redirect($tujuan) {
    // Cegah redirect eksternal (misal: http:// atau https:// atau //)
    if (preg_match('#^(https?:)?//#i', $tujuan)) {
        $tujuan = 'index.php';
    }
    
    header("Location: " . $tujuan);
    exit;
}

// 3. Fungsi flash message untuk notifikasi singkat (sukses, galat, info)
// Menggunakan session bawaan PHP
function flash($tipe = null, $pesan = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Jika mengisi tipe dan pesan, berarti kita mau set pesan baru
    if ($tipe !== null && $pesan !== null) {
        $_SESSION['flash_pesan'] = [
            'tipe' => $tipe,   // 'sukses', 'galat', atau 'info'
            'pesan' => $pesan
        ];
        return;
    }

    // Jika dipanggil tanpa argumen, ambil pesan lalu hapus dari session
    if (isset($_SESSION['flash_pesan'])) {
        $data = $_SESSION['flash_pesan'];
        unset($_SESSION['flash_pesan']);
        return $data;
    }

    return null;
}

// 4. Fungsi format tanggal Indonesia (WIB)
// Contoh hasil: "2 Nov 2026 14:30"
function formatTanggal($waktu) {
    if (empty($waktu)) {
        return '-';
    }

    $timestamp = is_numeric($waktu) ? (int)$waktu : strtotime($waktu);
    if (!$timestamp) {
        return '-';
    }

    $namaBulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $hari = date('j', $timestamp);
    $bulan = $namaBulan[(int)date('n', $timestamp)];
    $tahun = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);

    return "$hari $bulan $tahun $jam";
}

// 5. Fungsi format durasi waktu dari detik ke teks menit/detik
// Contoh: 90 detik jadi "1 mnt 30 dtk" atau "01:30"
function formatMenitDetik($detik) {
    $menit = floor($detik / 60);
    $sisaDetik = $detik % 60;
    return sprintf('%02d:%02d', $menit, $sisaDetik);
}

// 6. Fungsi sederhana untuk cek apakah request saat ini adalah POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
