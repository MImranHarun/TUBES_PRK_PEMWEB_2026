-- ATTENDARA dummy data
-- Import database/attendara.sql first, then run this file ONCE on an empty schema.
-- No DROP/DELETE statements are used. Explicit IDs make related sample rows predictable.
-- Demo credentials (password equals username): admin/admin, guru1/guru1 ... guru5/guru5.
-- Password hashes below were generated with PHP password_hash(..., PASSWORD_DEFAULT).
-- These credentials are for local development/demo only; change them before any deployment.

USE attendara;

START TRANSACTION;

INSERT INTO users (id, username, password_hash, role, is_active) VALUES
(1, 'admin', '$2y$12$saPT7R7q.RvS6gDUSxtU1eObtTUD6gb6facFGFwMPw.g59IN.Sncy', 'admin', TRUE),
(2, 'guru1', '$2y$12$cfOyUrX44u6iht21Zus17O8IocYRLzeohCchK9kpvYYZebnNHKK4C', 'guru', TRUE),
(3, 'guru2', '$2y$12$m4xfuInal2IWFTI8ECW3XOrm.bmjOjDhPe0oFna/8rBudtEKj9II2', 'guru', TRUE),
(4, 'guru3', '$2y$12$o9ZVkkUkf8RrzUiUcYaoyObvkE70dP2QAAtsIZVQ2CwDIgm8G7tgC', 'guru', TRUE),
(5, 'guru4', '$2y$12$FCua6ne.ZSL3Oq58cM2aK.Iet9Wtng4N4Z5HucPy9oLKvuT5uzFby', 'guru', TRUE),
(6, 'guru5', '$2y$12$UHcfzx8Xj7VA4vw4I0W059eTmUpRZd08QWWSDK0z25zefx6q.sjzEO', 'guru', TRUE);

INSERT INTO guru (id, user_id, nomor_induk, nama, email, no_telepon, is_active) VALUES
(1, 2, 'G-2026-001', 'Budi Santoso', 'budi.santoso@example.test', '081200000001', TRUE),
(2, 3, 'G-2026-002', 'Siti Rahmawati', 'siti.rahmawati@example.test', '081200000002', TRUE),
(3, 4, 'G-2026-003', 'Agus Setiawan', 'agus.setiawan@example.test', '081200000003', TRUE),
(4, 5, 'G-2026-004', 'Dewi Anggraini', 'dewi.anggraini@example.test', '081200000004', TRUE),
(5, 6, 'G-2026-005', 'Rizky Pratama', 'rizky.pratama@example.test', '081200000005', TRUE);

INSERT INTO kelas (id, kode, nama, tingkat, is_active) VALUES
(1, 'X-A', 'Kelas X-A', 10, TRUE),
(2, 'X-B', 'Kelas X-B', 10, TRUE),
(3, 'X-C', 'Kelas X-C', 10, TRUE);

INSERT INTO semester (id, kode, nama, tanggal_mulai, tanggal_selesai, status) VALUES
(1, '2025-GENAP', '2025/2026 Genap', '2026-01-05', '2026-06-30', 'selesai'),
(2, '2026-GANJIL', '2026/2027 Ganjil', '2026-07-13', '2026-12-19', 'aktif');

INSERT INTO siswa (id, nomor_induk, nama, jenis_kelamin, tanggal_lahir, is_active) VALUES
(1, 'ATT-2026-001', 'Siswa 01', NULL, NULL, TRUE),
(2, 'ATT-2026-002', 'Siswa 02', NULL, NULL, TRUE),
(3, 'ATT-2026-003', 'Siswa 03', NULL, NULL, TRUE),
(4, 'ATT-2026-004', 'Siswa 04', NULL, NULL, TRUE),
(5, 'ATT-2026-005', 'Siswa 05', NULL, NULL, TRUE),
(6, 'ATT-2026-006', 'Siswa 06', NULL, NULL, TRUE),
(7, 'ATT-2026-007', 'Siswa 07', NULL, NULL, TRUE),
(8, 'ATT-2026-008', 'Siswa 08', NULL, NULL, TRUE),
(9, 'ATT-2026-009', 'Siswa 09', NULL, NULL, TRUE),
(10, 'ATT-2026-010', 'Siswa 10', NULL, NULL, TRUE),
(11, 'ATT-2026-011', 'Siswa 11', NULL, NULL, TRUE),
(12, 'ATT-2026-012', 'Siswa 12', NULL, NULL, TRUE),
(13, 'ATT-2026-013', 'Siswa 13', NULL, NULL, TRUE),
(14, 'ATT-2026-014', 'Siswa 14', NULL, NULL, TRUE),
(15, 'ATT-2026-015', 'Siswa 15', NULL, NULL, TRUE),
(16, 'ATT-2026-016', 'Siswa 16', NULL, NULL, TRUE),
(17, 'ATT-2026-017', 'Siswa 17', NULL, NULL, TRUE),
(18, 'ATT-2026-018', 'Siswa 18', NULL, NULL, TRUE),
(19, 'ATT-2026-019', 'Siswa 19', NULL, NULL, TRUE),
(20, 'ATT-2026-020', 'Siswa 20', NULL, NULL, TRUE),
(21, 'ATT-2026-021', 'Siswa 21', NULL, NULL, TRUE),
(22, 'ATT-2026-022', 'Siswa 22', NULL, NULL, TRUE),
(23, 'ATT-2026-023', 'Siswa 23', NULL, NULL, TRUE),
(24, 'ATT-2026-024', 'Siswa 24', NULL, NULL, TRUE),
(25, 'ATT-2026-025', 'Siswa 25', NULL, NULL, TRUE),
(26, 'ATT-2026-026', 'Siswa 26', NULL, NULL, TRUE),
(27, 'ATT-2026-027', 'Siswa 27', NULL, NULL, TRUE),
(28, 'ATT-2026-028', 'Siswa 28', NULL, NULL, TRUE),
(29, 'ATT-2026-029', 'Siswa 29', NULL, NULL, TRUE),
(30, 'ATT-2026-030', 'Siswa 30', NULL, NULL, TRUE),
(31, 'ATT-2026-031', 'Siswa 31', NULL, NULL, TRUE),
(32, 'ATT-2026-032', 'Siswa 32', NULL, NULL, TRUE),
(33, 'ATT-2026-033', 'Siswa 33', NULL, NULL, TRUE),
(34, 'ATT-2026-034', 'Siswa 34', NULL, NULL, TRUE),
(35, 'ATT-2026-035', 'Siswa 35', NULL, NULL, TRUE),
(36, 'ATT-2026-036', 'Siswa 36', NULL, NULL, TRUE),
(37, 'ATT-2026-037', 'Siswa 37', NULL, NULL, TRUE),
(38, 'ATT-2026-038', 'Siswa 38', NULL, NULL, TRUE),
(39, 'ATT-2026-039', 'Siswa 39', NULL, NULL, TRUE),
(40, 'ATT-2026-040', 'Siswa 40', NULL, NULL, TRUE),
(41, 'ATT-2026-041', 'Siswa 41', NULL, NULL, TRUE),
(42, 'ATT-2026-042', 'Siswa 42', NULL, NULL, TRUE),
(43, 'ATT-2026-043', 'Siswa 43', NULL, NULL, TRUE),
(44, 'ATT-2026-044', 'Siswa 44', NULL, NULL, TRUE),
(45, 'ATT-2026-045', 'Siswa 45', NULL, NULL, TRUE),
(46, 'ATT-2026-046', 'Siswa 46', NULL, NULL, TRUE),
(47, 'ATT-2026-047', 'Siswa 47', NULL, NULL, TRUE),
(48, 'ATT-2026-048', 'Siswa 48', NULL, NULL, TRUE),
(49, 'ATT-2026-049', 'Siswa 49', NULL, NULL, TRUE),
(50, 'ATT-2026-050', 'Siswa 50', NULL, NULL, TRUE),
(51, 'ATT-2026-051', 'Siswa 51', NULL, NULL, TRUE),
(52, 'ATT-2026-052', 'Siswa 52', NULL, NULL, TRUE),
(53, 'ATT-2026-053', 'Siswa 53', NULL, NULL, TRUE),
(54, 'ATT-2026-054', 'Siswa 54', NULL, NULL, TRUE),
(55, 'ATT-2026-055', 'Siswa 55', NULL, NULL, TRUE),
(56, 'ATT-2026-056', 'Siswa 56', NULL, NULL, TRUE),
(57, 'ATT-2026-057', 'Siswa 57', NULL, NULL, TRUE),
(58, 'ATT-2026-058', 'Siswa 58', NULL, NULL, TRUE),
(59, 'ATT-2026-059', 'Siswa 59', NULL, NULL, TRUE),
(60, 'ATT-2026-060', 'Siswa 60', NULL, NULL, TRUE);

-- Same class per student in each semester is recorded as separate history rows.
INSERT INTO anggota_kelas (id, siswa_id, kelas_id, semester_id)
SELECT id, id, CASE WHEN id <= 20 THEN 1 WHEN id <= 40 THEN 2 ELSE 3 END, 1
FROM siswa WHERE id BETWEEN 1 AND 60;

INSERT INTO anggota_kelas (id, siswa_id, kelas_id, semester_id)
SELECT id + 60, id, CASE WHEN id <= 20 THEN 1 WHEN id <= 40 THEN 2 ELSE 3 END, 2
FROM siswa WHERE id BETWEEN 1 AND 60;

-- Five teachers cover three classes; two classes have two assigned teachers.
INSERT INTO penugasan_guru (id, guru_id, kelas_id, semester_id) VALUES
(1, 1, 1, 1), (2, 2, 2, 1), (3, 3, 3, 1), (4, 4, 1, 1), (5, 5, 2, 1),
(6, 1, 1, 2), (7, 2, 2, 2), (8, 3, 3, 2), (9, 4, 1, 2), (10, 5, 2, 2);

INSERT INTO jadwal_mengajar
  (id, penugasan_guru_id, hari, jam_mulai, jam_selesai, berlaku_mulai, berlaku_sampai)
VALUES
(1, 1, 1, '07:00:00', '08:30:00', '2026-01-05', '2026-06-30'),
(2, 2, 2, '08:00:00', '09:30:00', '2026-01-05', '2026-06-30'),
(3, 3, 3, '09:00:00', '10:30:00', '2026-01-05', '2026-06-30'),
(4, 4, 4, '10:00:00', '11:30:00', '2026-01-05', '2026-06-30'),
(5, 5, 5, '11:00:00', '12:30:00', '2026-01-05', '2026-06-30'),
(6, 6, 1, '07:00:00', '08:30:00', '2026-07-13', '2026-12-19'),
(7, 7, 2, '08:00:00', '09:30:00', '2026-07-13', '2026-12-19'),
(8, 8, 3, '09:00:00', '10:30:00', '2026-07-13', '2026-12-19'),
(9, 9, 4, '10:00:00', '11:30:00', '2026-07-13', '2026-12-19'),
(10, 10, 5, '11:00:00', '12:30:00', '2026-07-13', '2026-12-19');

INSERT INTO sesi_pembelajaran
  (id, penugasan_guru_id, jadwal_mengajar_id, tanggal, waktu_mulai, waktu_selesai, status)
VALUES
(1, 1, 1, '2026-04-06', '2026-04-06 07:00:00', '2026-04-06 08:30:00', 'selesai'),
(2, 2, 2, '2026-04-07', '2026-04-07 08:00:00', '2026-04-07 09:30:00', 'selesai'),
(3, 3, 3, '2026-04-08', '2026-04-08 09:00:00', '2026-04-08 10:30:00', 'selesai'),
(4, 4, 4, '2026-04-09', '2026-04-09 10:00:00', '2026-04-09 11:30:00', 'selesai'),
(5, 5, 5, '2026-04-10', '2026-04-10 11:00:00', '2026-04-10 12:30:00', 'selesai'),
(6, 6, 6, '2026-11-02', '2026-11-02 07:00:00', '2026-11-02 08:30:00', 'selesai'),
(7, 7, 7, '2026-11-03', '2026-11-03 08:00:00', '2026-11-03 09:30:00', 'selesai'),
(8, 8, 8, '2026-11-04', '2026-11-04 09:00:00', '2026-11-04 10:30:00', 'selesai'),
(9, 9, 9, '2026-11-05', '2026-11-05 10:00:00', '2026-11-05 11:30:00', 'selesai'),
(10, 10, 10, '2026-11-06', '2026-11-06 11:00:00', '2026-11-06 12:30:00', 'selesai');

-- Sample attendance is manually seeded demo data; there is no automation rule.
INSERT INTO absensi
  (sesi_pembelajaran_id, siswa_id, status, dicatat_oleh_user_id, waktu_dicatat, catatan)
SELECT sp.id, ak.siswa_id, 'hadir', g.user_id, sp.waktu_mulai, NULL
FROM sesi_pembelajaran AS sp
JOIN penugasan_guru AS pg ON pg.id = sp.penugasan_guru_id
JOIN guru AS g ON g.id = pg.guru_id
JOIN anggota_kelas AS ak
  ON ak.kelas_id = pg.kelas_id AND ak.semester_id = pg.semester_id;

-- Sample participation points are seeded for a subset of students as independent entries.
INSERT INTO nilai_keaktifan
  (sesi_pembelajaran_id, siswa_id, poin, dicatat_oleh_user_id, waktu_dicatat, catatan)
SELECT sp.id, ak.siswa_id,
       CASE WHEN MOD(ak.siswa_id, 2) = 0 THEN 2.00 ELSE 1.00 END,
       g.user_id, sp.waktu_mulai, 'Contoh poin keaktifan'
FROM sesi_pembelajaran AS sp
JOIN penugasan_guru AS pg ON pg.id = sp.penugasan_guru_id
JOIN guru AS g ON g.id = pg.guru_id
JOIN anggota_kelas AS ak
  ON ak.kelas_id = pg.kelas_id AND ak.semester_id = pg.semester_id
WHERE MOD(ak.siswa_id, 3) = 0;

-- Four illustrative groups per class in the active semester.
INSERT INTO kelompok (id, kelas_id, semester_id, nama) VALUES
(1, 1, 2, 'Kelompok 1'), (2, 1, 2, 'Kelompok 2'),
(3, 1, 2, 'Kelompok 3'), (4, 1, 2, 'Kelompok 4'),
(5, 2, 2, 'Kelompok 1'), (6, 2, 2, 'Kelompok 2'),
(7, 2, 2, 'Kelompok 3'), (8, 2, 2, 'Kelompok 4'),
(9, 3, 2, 'Kelompok 1'), (10, 3, 2, 'Kelompok 2'),
(11, 3, 2, 'Kelompok 3'), (12, 3, 2, 'Kelompok 4');

-- Split each class's 20 current students into four groups of five.
INSERT INTO anggota_kelompok (kelompok_id, siswa_id)
SELECT ((ak.kelas_id - 1) * 4) + FLOOR(MOD(ak.siswa_id - 1, 20) / 5) + 1,
       ak.siswa_id
FROM anggota_kelas AS ak
WHERE ak.semester_id = 2;

COMMIT;
