-- Jalankan satu kali setelah 01_schema.sql; tidak menghapus data.
-- Jika gagal karena duplikat, rapikan data yang sudah ada sebelum mengulang.
USE akademik;
ALTER TABLE GURU ADD UNIQUE KEY uq_guru_email (email), ADD UNIQUE KEY uq_guru_nip (nip);
ALTER TABLE KELAS ADD UNIQUE KEY uq_nama_kelas (nama_kelas);
ALTER TABLE SISWA ADD UNIQUE KEY uq_nisn (nisn);
ALTER TABLE MATA_PELAJARAN ADD UNIQUE KEY uq_kode_mapel (kode_mapel);
ALTER TABLE PERIODE_PENILAIAN ADD UNIQUE KEY uq_periode (tahun_ajaran, semester);
ALTER TABLE PENUGASAN_MENGAJAR ADD UNIQUE KEY uq_penugasan (id_guru, id_kelas, id_mapel, id_periode);
