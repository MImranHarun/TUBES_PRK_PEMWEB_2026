<?php
// Daftar tabel/kolom tetap; nama tabel tidak diambil langsung dari masukan pengguna.
return [
    'kelas' => ['table' => 'KELAS', 'pk' => 'id_kelas',
        'fields' => ['nama_kelas' => 50, 'tingkat' => 20, 'id_wali_kelas' => 0]],
    'siswa' => ['table' => 'SISWA', 'pk' => 'id_siswa',
        'fields' => ['nisn' => 50, 'nama_siswa' => 100, 'jenis_kelamin' => 20, 'id_kelas' => 0]],
    'mapel' => ['table' => 'MATA_PELAJARAN', 'pk' => 'id_mapel',
        'fields' => ['kode_mapel' => 20, 'nama_mapel' => 100]],
    'periode' => ['table' => 'PERIODE_PENILAIAN', 'pk' => 'id_periode',
        'fields' => ['tahun_ajaran' => 20, 'semester' => 20, 'status_aktif' => -1]],
    'penugasan' => ['table' => 'PENUGASAN_MENGAJAR', 'pk' => 'id_penugasan',
        'fields' => ['id_guru' => 0, 'id_kelas' => 0, 'id_mapel' => 0, 'id_periode' => 0]]
];
