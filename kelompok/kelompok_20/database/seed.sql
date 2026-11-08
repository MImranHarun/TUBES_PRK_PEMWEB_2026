-- Active: 1776470071229@@localhost@3306@tubes_ppw
-- 1. Pengguna (1 Admin, 3 Pengguna Mahasiswa)
INSERT INTO users (id, nama, email, password, role) VALUES
(1, 'Administrator FISIP', 'admin@fisip.unila.ac.id', '$2y$12$lm79T6Wh.ZR0NBA/wHzY0OA98qCVFjw5UENs8wOraJEH8C3ArhM5y', 'admin'),
(2, 'Budi Santoso', 'budi.santoso@mhs.unila.ac.id', '$2y$12$lm79T6Wh.ZR0NBA/wHzY0OA98qCVFjw5UENs8wOraJEH8C3ArhM5y', 'user'),
(3, 'Siti Rahma', 'siti.rahma@mhs.unila.ac.id', '$2y$12$lm79T6Wh.ZR0NBA/wHzY0OA98qCVFjw5UENs8wOraJEH8C3ArhM5y', 'user'),
(4, 'Ahmad Fauzi', 'ahmad.fauzi@mhs.unila.ac.id', '$2y$12$lm79T6Wh.ZR0NBA/wHzY0OA98qCVFjw5UENs8wOraJEH8C3ArhM5y', 'user')
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

-- 2. Data Laporan 
INSERT INTO laporan (id, user_id, kategori_id, jenis, judul, isi, lokasi, foto, is_public, status) VALUES
(1, 2, 1, 'pengaduan', 'AC Ruang Kuliah D2.1 Rusak dan Meneteskan Air', 'AC di ruang kuliah D2.1 mati dan meneteskan air ke lantai sehingga licin dan mengganggu perkuliahan mahasiswa.', 'Gedung D Ruang D2.1 FISIP', NULL, 1, 'diproses'),
(2, 3, 4, 'pengaduan', 'Tempat Sampah Kantin FISIP Penuh dan Menumpuk', 'Tempat sampah di sekitar selasar kantin sudah menumpuk sejak kemarin sore dan belum diangkut.', 'Kantin FISIP', NULL, 1, 'selesai'),
(3, 4, 2, 'aspirasi', 'Usulan Penyediaan Stopkontak Tambahan di Gazebo', 'Banyak mahasiswa belajar mandiri di gazebo taman FISIP namun kekurangan colokan listrik untuk pengisian daya laptop.', 'Gazebo Taman Dekanat FISIP', NULL, 1, 'menunggu'),
(4, 2, 3, 'pengaduan', 'Lampu Penerangan di Area Parkir Belakang Padam', 'Penerangan di parkiran motor belakang sangat minim saat malam hari, rawan tindakan pencurian helm.', 'Area Parkir Motor Belakang FISIP', NULL, 1, 'diproses'),
(5, 3, 5, 'pengaduan', 'Kendala Antrean Pelayanan Bebas Pustaka Jurusan', 'Antrean pengurusan validasi bebas pustaka jurusan sangat panjang dan perlu sistem nomor antrean digital.', 'Subbag Akademik FISIP', NULL, 0, 'menunggu'),
(6, 4, 2, 'aspirasi', 'Penambahan Kuota Matakuliah Pilihan Semester Genap', 'Mohon kuota kelas untuk matakuliah Komunikasi Politik ditambah karena banyak peminat yang tidak kebagian kelas.', 'Jurusan Ilmu Komunikasi', NULL, 1, 'selesai'),
(7, 2, 1, 'pengaduan', 'Proyektor Ruang C1.3 Warna Buram dan Berkedip', 'Tampilan proyektor di ruangan C1.3 berkedip kemerahan sehingga slide materi kuliah tidak terbaca jelas.', 'Gedung C Ruang C1.3', NULL, 1, 'menunggu'),
(8, 3, 5, 'aspirasi', 'Digitalisasi Legalisir Transkrip Nilai dan Ijazah', 'Diharapkan fakultas menyediakan layanan permohonan legalisir ijazah secara daring melalui formulir resmi.', 'Bagian Kemahasiswaan & Alumni FISIP', NULL, 1, 'diproses'),
(9, 4, 4, 'pengaduan', 'Kran Air Wastafel Toilet Pria Gedung B Bocor', 'Air terus mengalir terbuang karena drat kran toilet pria lantai 1 sudah longgar.', 'Toilet Pria Lt. 1 Gedung B FISIP', NULL, 1, 'selesai'),
(10, 2, 3, 'pengaduan', 'Laporan Kehilangan Kunci Motor di Ruang Baca', 'Tertinggal satu set kunci motor berstiker hitam di meja baca pojok timur.', 'Ruang Baca Lt. 2 FISIP', NULL, 0, 'menunggu')
ON DUPLICATE KEY UPDATE judul=VALUES(judul);

-- 3. Data Riwayat Tanggapan Admin 
INSERT INTO tanggapan (id, laporan_id, admin_id, isi_tanggapan) VALUES
(1, 1, 1, 'Terima kasih atas laporannya. Tim sarana dan prasarana fakultas telah menjadwalkan teknisi pendingin ruangan untuk perbaikan pada hari ini.'),
(2, 2, 1, 'Laporan telah ditindaklanjuti. Petugas kebersihan telah mengosongkan dan membersihkan area tempat sampah kantin pagi ini.'),
(3, 4, 1, 'Pemberitahuan telah diteruskan ke bagian pemeliharaan instalasi listrik. Penggantian bohlam lampu parkir sedang disiapkan.'),
(4, 6, 1, 'Aspirasi telah disampaikan ke Ketua Jurusan. Kelas paralel tambahan telah disetujui dan dibuka pada portal KRS.'),
(5, 9, 1, 'Kran wastafel yang bocor telah diganti dengan suku cadang baru oleh staf perlengkapan.')
ON DUPLICATE KEY UPDATE isi_tanggapan=VALUES(isi_tanggapan);