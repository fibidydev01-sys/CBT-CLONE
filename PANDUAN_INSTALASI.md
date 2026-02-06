# PANDUAN INSTALASI CBT NUSANTARA
## Single School Edition - XAMPP

---

## PERSYARATAN SISTEM

| Komponen | Versi Minimum |
|----------|---------------|
| XAMPP | 8.1+ (PHP 8.1 + MySQL 8.0 / MariaDB 10.4+) |
| Browser | Chrome / Edge / Firefox terbaru |
| RAM | 2 GB (minimal), 4 GB (rekomendasi) |
| Disk | 500 MB free space |

---

## LANGKAH 1: DOWNLOAD & INSTALL XAMPP

Jika belum install XAMPP:

1. Download XAMPP dari https://www.apachefriends.org/download.html
2. Pilih versi **PHP 8.1** atau lebih baru
3. Install dengan default setting (biasanya ke `C:\xampp`)
4. Setelah install, buka **XAMPP Control Panel**

---

## LANGKAH 2: START APACHE & MYSQL

1. Buka **XAMPP Control Panel** (biasanya ada shortcut di Desktop atau di `C:\xampp\xampp-control.exe`)
2. Klik tombol **Start** pada baris **Apache**
   - Pastikan muncul tulisan hijau "Running"
   - Jika error port 80, kemungkinan Skype atau IIS menggunakan port tersebut
3. Klik tombol **Start** pada baris **MySQL**
   - Pastikan muncul tulisan hijau "Running"

```
┌─────────────────────────────────────────────┐
│  XAMPP Control Panel                        │
├──────────┬──────────┬───────────────────────┤
│ Module   │ Status   │ Actions               │
├──────────┼──────────┼───────────────────────┤
│ Apache   │ Running  │ [Stop] [Admin]        │  ← HARUS RUNNING
│ MySQL    │ Running  │ [Stop] [Admin]        │  ← HARUS RUNNING
│ FileZilla│          │ [Start]               │  ← tidak perlu
│ Mercury  │          │ [Start]               │  ← tidak perlu
│ Tomcat   │          │ [Start]               │  ← tidak perlu
└──────────┴──────────┴───────────────────────┘
```

### Troubleshooting Port Conflict

Jika Apache gagal start karena port 80 sudah dipakai:

**Cara 1: Matikan program yang pakai port 80**
- Buka CMD sebagai Administrator
- Ketik: `netstat -aon | findstr :80`
- Cari PID yang pakai port 80, matikan di Task Manager

**Cara 2: Ganti port Apache**
- Buka XAMPP Control Panel → klik **Config** di baris Apache → pilih `httpd.conf`
- Cari baris `Listen 80`, ganti jadi `Listen 8080`
- Cari baris `ServerName localhost:80`, ganti jadi `ServerName localhost:8080`
- Restart Apache
- Akses pakai `http://localhost:8080/cbt/` (bukan `localhost/cbt/`)

---

## LANGKAH 3: COPY FOLDER PROJECT KE HTDOCS

### Lokasi htdocs:

| OS | Path |
|----|------|
| Windows | `C:\xampp\htdocs\` |
| Mac | `/Applications/XAMPP/htdocs/` |
| Linux | `/opt/lampp/htdocs/` |

### Cara Copy:

1. Buka File Explorer
2. Navigate ke `C:\xampp\htdocs\`
3. **Copy SELURUH folder project** ke sini
4. **RENAME folder menjadi `cbt`**

```
C:\xampp\htdocs\
├── dashboard/          ← bawaan XAMPP (biarkan)
├── cbt/                ← FOLDER PROJECT ANDA ← TARUH DI SINI!
│   ├── admin/
│   ├── api/
│   ├── assets/
│   ├── config/
│   ├── guru/
│   ├── includes/
│   ├── pengawas/
│   ├── siswa/
│   ├── uploads/
│   ├── database.sql
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── 403.php
│   └── .htaccess
```

> **PENTING**: Folder HARUS bernama **`cbt`** karena base URL di sistem sudah di-set ke `/cbt/`. Jika ingin nama folder lain, baca bagian "Kustomisasi" di bawah.

---

## LANGKAH 4: BUAT DATABASE

### Cara 1: Via phpMyAdmin (RECOMMENDED - Paling Mudah)

1. Buka browser
2. Ketik di address bar: **http://localhost/phpmyadmin/**
3. Login phpMyAdmin:
   - **Username**: `root`
   - **Password**: _(kosong, tidak perlu diisi)_
   - Langsung klik **Go** / **Login**

> **Note**: XAMPP default MySQL user adalah `root` tanpa password. Ini sudah sesuai dengan konfigurasi di `config/database.php`.

4. Setelah masuk phpMyAdmin, klik tab **SQL** di menu atas

```
┌──────────────────────────────────────────┐
│  phpMyAdmin                              │
├──────────────────────────────────────────┤
│  [Databases] [SQL] [Status] [Users]      │
│                      ↑                   │
│              KLIK TAB INI               │
└──────────────────────────────────────────┘
```

5. Buka file `database.sql` yang ada di folder project dengan Notepad
   - Lokasi: `C:\xampp\htdocs\cbt\database.sql`
6. **Copy SEMUA isi** file tersebut (Ctrl+A → Ctrl+C)
7. **Paste** ke text area SQL di phpMyAdmin (Ctrl+V)
8. Klik tombol **Go** / **Execute** di pojok kanan bawah

```
┌──────────────────────────────────────────┐
│  Run SQL query/queries on server:        │
│  ┌──────────────────────────────────────┐│
│  │ CREATE DATABASE IF NOT EXISTS        ││
│  │ `cbt_nusantara` ...                  ││  ← PASTE SEMUA
│  │                                      ││     ISI database.sql
│  │                                      ││     DI SINI
│  └──────────────────────────────────────┘│
│                                [Go]      │  ← KLIK GO
└──────────────────────────────────────────┘
```

9. Jika berhasil, akan muncul pesan hijau dan di sidebar kiri akan muncul database **`cbt_nusantara`**

### Cara 2: Via Import File (Alternatif)

1. Buka **http://localhost/phpmyadmin/**
2. Klik tab **Import** di menu atas
3. Klik **Choose File** / **Pilih File**
4. Pilih file `C:\xampp\htdocs\cbt\database.sql`
5. Klik **Go** / **Execute**

### Cara 3: Via Command Line (Advanced)

```bash
# Buka CMD / Terminal
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\cbt\database.sql
```

### Verifikasi Database Berhasil Dibuat

Di phpMyAdmin, klik database `cbt_nusantara` di sidebar kiri. Harus ada **12 tabel**:

```
cbt_nusantara
├── admin            (1 row - akun admin default)
├── bank_soal        (kosong)
├── guru             (kosong)
├── jawaban_siswa    (kosong)
├── kelas            (9 rows - kelas VII-IX A/B/C)
├── log_aktivitas    (kosong)
├── opsi_jawaban     (kosong)
├── pengawas         (kosong)
├── sesi_ujian       (kosong)
├── settings         (22 rows - pengaturan default)
├── siswa            (kosong)
├── soal             (kosong)
└── ujian            (kosong)
    ujian_kelas      (kosong)
```

---

## LANGKAH 5: BUKA APLIKASI

1. Buka browser (Chrome/Edge direkomendasikan)
2. Ketik di address bar:

```
http://localhost/cbt/
```

3. Akan muncul **Landing Page** CBT Nusantara
4. Klik tombol **Masuk**

---

## LANGKAH 6: LOGIN PERTAMA KALI

### Akun Admin Default:

```
┌──────────────────────────────┐
│   USERNAME:  admin           │
│   PASSWORD:  admin123        │
└──────────────────────────────┘
```

Setelah login, Anda akan masuk ke **Dashboard Admin**.

---

## LANGKAH 7: SETUP AWAL (Urutan yang Disarankan)

Setelah login sebagai admin, lakukan setup berikut **secara berurutan**:

### 7.1 Pengaturan Sekolah
1. Klik menu **Settings** / **Pengaturan** di sidebar
2. Tab **Sekolah**:
   - Isi **Nama Sekolah** (contoh: "SMP Negeri 1 Jakarta")
   - Isi **Deskripsi Portal** (contoh: "Portal Ujian Online")
   - Klik **Simpan**
3. Tab **Logo** (opsional):
   - Upload logo sekolah (JPG/PNG, max 2MB)

### 7.2 Tambah Kelas (Jika Perlu)
- Menu **Kelas** di sidebar
- Default sudah ada 9 kelas (VII-IX A/B/C)
- Tambah/edit/hapus sesuai kebutuhan

### 7.3 Tambah Guru
1. Menu **Guru** di sidebar
2. Klik **Tambah Guru**
3. Isi: Nama, Username, Password
4. Contoh:
   ```
   Nama     : Budi Santoso
   Username : budi
   Password : guru123
   ```

### 7.4 Tambah Pengawas
1. Menu **Pengawas** di sidebar
2. Klik **Tambah Pengawas**
3. Isi: Nama, Username, Password
4. Contoh:
   ```
   Nama     : Siti Aminah
   Username : siti
   Password : pengawas123
   ```

### 7.5 Tambah Siswa
1. Menu **Siswa** di sidebar
2. Klik **Tambah Siswa**
3. Isi: Nama, Username, Password, Kelas
4. Contoh:
   ```
   Nama     : Ahmad Rizki
   Username : ahmad
   Password : siswa123
   Kelas    : IX A
   ```
5. Ulangi untuk semua siswa

### 7.6 Buat Bank Soal
1. Menu **Bank Soal** di sidebar
2. Klik **Tambah Bank Soal**
3. Isi:
   ```
   Kode Soal  : MTK-IX-01
   Nama Bank  : Matematika Kelas 9 - Bab 1
   ```

### 7.7 Tambah Soal ke Bank Soal
1. Klik **Kelola Soal** pada bank soal yang sudah dibuat
2. Klik **Tambah Soal**
3. Untuk soal **Pilihan Ganda**:
   - Isi teks soal
   - Isi Opsi A, B, C, D, E
   - Pilih jawaban yang benar
4. Untuk soal **Esai**:
   - Pilih tipe "Esai"
   - Isi teks soal saja
5. Ulangi sampai semua soal terisi

### 7.8 Buat Ujian
1. Menu **Ujian** di sidebar
2. Klik **Buat Ujian**
3. Isi:
   ```
   Nama Ujian    : UTS Matematika Kelas 9
   Bank Soal     : MTK-IX-01 - Matematika Kelas 9
   Tanggal       : 2026-02-10
   Jam Mulai     : 08:00
   Jam Selesai   : 10:00
   Jumlah Soal   : 20
   Alokasi Waktu : 90 (menit)
   Kelas         : ☑ IX A  ☑ IX B  ☑ IX C
   ```
4. Klik **Simpan** → Status awal: **Draft**

### 7.9 Atur Kunci Jawaban
1. Edit ujian → Tab **Kunci Jawaban**
2. Isi kunci untuk setiap soal PG
3. Simpan

### 7.10 Aktifkan Ujian
1. Di daftar ujian, klik tombol **Aktifkan** (ikon play hijau)
2. Status berubah menjadi **Aktif**
3. Sistem otomatis membuat sesi ujian untuk semua siswa di kelas terpilih

---

## LANGKAH 8: GENERATE TOKEN (Sebelum Ujian Dimulai)

1. Menu **Token** di sidebar (atau Pengawas bisa generate dari panelnya)
2. Klik **Generate Token Baru**
3. Akan muncul token 6 digit, contoh: `482917`
4. **Bagikan token ini ke siswa** (tulis di papan tulis / proyektor)
5. Token berlaku sesuai durasi yang di-set (default: 100 menit)

---

## LANGKAH 9: SISWA MENGERJAKAN UJIAN

### Proses Ujian Siswa:

1. Siswa buka browser → `http://localhost/cbt/`
2. Login dengan username & password siswa
3. Di dashboard, klik **Masuk Ujian** pada ujian yang tersedia
4. Masukkan **Token** yang diberikan pengawas
5. Klik **Mulai Ujian** → Baca aturan → Konfirmasi
6. Kerjakan soal (timer berjalan)
7. Klik **Submit** setelah selesai
8. Nilai langsung dihitung dan ditampilkan

### Anti-Cheat Aktif Saat Ujian:
- Berpindah tab → Penalti waktu dikurangi
- Klik kanan → Diblokir
- Copy/Paste → Diblokir
- F12/DevTools → Diblokir
- Melebihi batas pelanggaran → Auto submit

---

## LANGKAH 10: MONITORING & LAPORAN

### Monitoring Real-time:
- Admin: Menu **Monitoring** → Lihat status semua siswa secara live
- Pengawas: Menu **Monitoring** → Sama seperti admin
- Auto-refresh setiap 10 detik

### Laporan:
- Menu **Laporan** → Pilih ujian → Detail
- Lihat statistik, distribusi nilai, per-kelas, per-siswa
- Export ke **Excel (CSV)** atau **PDF (Print)**

---

## AKUN LOGIN PER ROLE

| Role | URL Dashboard | Contoh Login |
|------|--------------|--------------|
| Admin | `localhost/cbt/admin/` | admin / admin123 |
| Guru | `localhost/cbt/guru/` | (buat sendiri) |
| Pengawas | `localhost/cbt/pengawas/` | (buat sendiri) |
| Siswa | `localhost/cbt/siswa/` | (buat sendiri) |

> **Note**: Semua role login dari halaman yang sama (`localhost/cbt/login.php`). Sistem otomatis mendeteksi role berdasarkan tabel mana yang cocok.

---

## AKSES JARINGAN LOKAL (LAN)

Agar siswa bisa akses dari komputer lain di jaringan yang sama (lab komputer):

### 1. Cari IP Address Komputer Server

```bash
# Windows: Buka CMD, ketik:
ipconfig

# Cari "IPv4 Address", contoh: 192.168.1.100
```

### 2. Izinkan Firewall

```
Windows Firewall → Allow → Apache HTTP Server → Private & Public
```

Atau matikan Windows Firewall sementara (untuk testing).

### 3. Siswa Akses Dari Komputer Lain

```
http://192.168.1.100/cbt/
```

Ganti `192.168.1.100` dengan IP server yang sebenarnya.

### 4. Tips Jaringan Lab:
- Semua komputer harus di **network/WiFi yang sama**
- Server (yang install XAMPP) harus tetap menyala
- Gunakan kabel LAN untuk koneksi lebih stabil
- Jika pakai WiFi, pastikan bandwidth cukup

---

## KUSTOMISASI

### Ganti Nama Folder (Bukan "cbt")

Jika ingin folder bernama selain `cbt`, misal `ujian`:

1. Rename folder di htdocs menjadi `ujian`
2. Edit file `config/session.php`, cari baris:
   ```php
   function base_url($path = '') {
       return '/cbt/' . ltrim($path, '/');
   }
   ```
   Ganti `/cbt/` menjadi `/ujian/`:
   ```php
   function base_url($path = '') {
       return '/ujian/' . ltrim($path, '/');
   }
   ```
3. Di file yang sama, ganti juga redirect login:
   ```php
   header('Location: /ujian/login.php');
   // dan
   header('Location: /ujian/403.php');
   // dan
   header('Location: /ujian/login.php?error=session_hijacked');
   ```

### Ganti Password MySQL

Jika MySQL Anda punya password (bukan default kosong):

1. Edit file `config/database.php`
2. Ubah baris:
   ```php
   define('DB_PASS', '');
   ```
   Menjadi:
   ```php
   define('DB_PASS', 'password_anda');
   ```

### Ganti Timezone

Default timezone adalah WIB (UTC+7). Untuk WITA atau WIT:

Edit `config/database.php`:
```php
// WIB (default)
$conn->query("SET time_zone = '+07:00'");

// WITA
$conn->query("SET time_zone = '+08:00'");

// WIT
$conn->query("SET time_zone = '+09:00'");
```

---

## TROUBLESHOOTING

### Error: "Koneksi Database Gagal"
- Pastikan MySQL sudah **Running** di XAMPP Control Panel
- Pastikan database `cbt_nusantara` sudah dibuat
- Cek `config/database.php` → username/password benar

### Error: "404 Not Found"
- Pastikan folder bernama `cbt` (bukan CBT-CLONE atau nama lain)
- Pastikan ada di `C:\xampp\htdocs\cbt\`
- Pastikan Apache sudah Running

### Error: "403 Forbidden"
- Pastikan ada file `.htaccess` di folder `cbt`
- Buka XAMPP Control Panel → Apache → Config → `httpd.conf`
- Cari `AllowOverride None`, ganti jadi `AllowOverride All`
- Restart Apache

### Halaman Blank Putih
- Cek PHP error log di `C:\xampp\php\logs\php_error_log`
- Atau tambahkan di awal file PHP yang error:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

### Upload Gagal (Soal/Logo)
- Pastikan folder `uploads/` writable
- Cek `php.ini` → `upload_max_filesize` minimal `32M`
- Atau cek `.htaccess` sudah mengatur limit upload

### Token Tidak Bisa Generate
- Pastikan sudah login sebagai admin/pengawas
- Pastikan setting `token_enabled` = 1

### Siswa Tidak Bisa Login
- Cek akun siswa di menu **Siswa** → pastikan **Status Aktif**
- Pastikan bukan **Blocked**
- Jika maintenance mode aktif, nonaktifkan dulu di Settings

---

## STRUKTUR FILE LENGKAP

```
cbt/
├── admin/                    # Panel Admin
│   ├── index.php             # Dashboard
│   ├── bank_soal/            # CRUD Bank Soal
│   ├── guru/                 # CRUD Guru
│   ├── kelas/                # CRUD Kelas
│   ├── laporan/              # Laporan + Export
│   ├── pengawas/             # CRUD Pengawas
│   ├── settings/             # Pengaturan Sistem
│   ├── siswa/                # CRUD Siswa
│   ├── soal/                 # CRUD Soal
│   ├── token/                # Token Management
│   └── ujian/                # CRUD Ujian + Monitoring
│
├── api/                      # AJAX Endpoints
│   ├── get_sisa_waktu.php    # Sync timer
│   ├── log_activity.php      # Log anti-cheat
│   └── save_jawaban.php      # Save jawaban realtime
│
├── assets/                   # Static Files
│   ├── css/
│   │   ├── style.css         # Main stylesheet
│   │   └── responsive.css    # Responsive breakpoints
│   └── js/
│       └── main.js           # Global JavaScript
│
├── config/                   # Konfigurasi
│   ├── database.php          # Koneksi MySQL
│   ├── session.php           # Session + Auth
│   └── settings.php          # Settings loader
│
├── guru/                     # Panel Guru
│   ├── index.php             # Dashboard
│   ├── bank_soal/            # CRUD (own data only)
│   ├── laporan/              # Laporan (own only)
│   ├── soal/                 # CRUD (own only)
│   └── ujian/                # CRUD (own only)
│
├── includes/                 # Shared Templates
│   ├── footer.php
│   ├── functions.php         # Helper functions
│   ├── header.php
│   ├── navbar.php
│   └── sidebar.php
│
├── pengawas/                 # Panel Pengawas
│   ├── index.php             # Dashboard
│   ├── log_aktivitas.php     # Log viewer
│   ├── monitoring.php        # Real-time monitoring
│   ├── reset_sesi.php        # Reset session siswa
│   └── token.php             # Generate token
│
├── siswa/                    # Panel Siswa
│   ├── index.php             # Dashboard
│   ├── profil/               # Profil + Ubah Password
│   └── ujian/                # Flow ujian
│       ├── token.php         # Input token
│       ├── mulai.php         # Konfirmasi mulai
│       ├── soal.php          # Kerjakan soal
│       ├── submit.php        # Submit handler
│       └── hasil.php         # Hasil ujian
│
├── uploads/                  # User Uploads
│   ├── import/               # Import CSV
│   ├── logo/                 # Logo sekolah
│   └── soal/                 # Gambar soal
│
├── .htaccess                 # Apache config
├── 403.php                   # Forbidden page
├── database.sql              # Database setup
├── index.php                 # Landing page
├── login.php                 # Login page
└── logout.php                # Logout handler
```

---

## QUICK START (RANGKUMAN CEPAT)

```
1. Start XAMPP → Apache + MySQL ON
2. Copy folder ke C:\xampp\htdocs\cbt\
3. Buka http://localhost/phpmyadmin/ → Tab SQL → Paste isi database.sql → Go
4. Buka http://localhost/cbt/
5. Login: admin / admin123
6. Settings → Isi nama sekolah
7. Tambah Guru, Pengawas, Siswa
8. Buat Bank Soal → Tambah Soal → Buat Ujian → Aktifkan
9. Generate Token → Bagikan ke siswa
10. Siswa login → Masuk ujian → Kerjakan → Submit → Selesai!
```

---

*CBT Nusantara - Single School Edition v2.0*
*Dibuat dengan PHP 8.1 + MySQL + Bootstrap 5*
