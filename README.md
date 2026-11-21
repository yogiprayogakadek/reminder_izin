# SIPERIZ

Aplikasi internal untuk mencatat izin perusahaan, memantau tanggal kedaluwarsa, dan mengirim pengingat WhatsApp melalui WAHA.

Data awal sengaja kosong. Seeder hanya membuat satu akun admin, aturan reminder global, template standar, dan pengaturan dasar. Tidak ada izin, kategori, atau kontak contoh.

## Kebutuhan

- PHP 8.4 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, dan `zip`
- Composer 2
- Node.js 20 atau lebih baru
- MySQL 8

## Menyiapkan aplikasi

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Buat database dan pengguna MySQL, lalu sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.

```sql
CREATE DATABASE siperiz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'siperiz'@'localhost' IDENTIFIED BY 'kata-sandi-anda';
GRANT ALL PRIVILEGES ON siperiz.* TO 'siperiz'@'localhost';
FLUSH PRIVILEGES;
```

Jalankan migrasi dan seeder.

```bash
php artisan migrate --seed
```

Jalankan server lokal.

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Akun admin pertama

Nilai bawaan seeder:

- Email: `admin@siperiz.test`
- Kata sandi: `Admin#Siperiz2026`

Ubah `SIPERIZ_ADMIN_EMAIL` dan `SIPERIZ_ADMIN_PASSWORD` di `.env` sebelum menjalankan `migrate --seed` jika ingin akun berbeda. Setelah masuk, ganti kata sandi melalui halaman Profil.

## Menjalankan reminder

Reminder tidak dikirim oleh proses web. Dua proses berikut harus berjalan terus:

```bash
php artisan schedule:work
php artisan queue:work --tries=3
```

Scheduler memeriksa aturan setiap menit. Worker mengirim antrean ke WAHA. Pada server Linux, jalankan keduanya melalui systemd atau supervisor. Konfigurasi server tidak termasuk dalam repositori ini.

Jam reminder mengikuti zona waktu pada Pengaturan > Aplikasi. Nilai awal adalah `Asia/Makassar` dan pukul `08:00`.

## Menghubungkan WAHA

1. Jalankan server WAHA dan pastikan dapat dijangkau dari server aplikasi.
2. Buka Pengaturan > Koneksi WAHA.
3. Isi base URL, API key, nama sesi, dan webhook secret.
4. Simpan lalu jalankan uji koneksi. Sesi harus berstatus `WORKING`.
5. Daftarkan URL webhook `https://domain-anda/api/webhooks/waha` di pengaturan global WAHA untuk event `session.status` dan `message.ack`.

API key dan webhook secret disimpan terenkripsi. Halaman pengaturan hanya menampilkan empat karakter terakhir.

Reminder otomatis hanya dibuat jika koneksi lengkap dan opsi pengiriman aktif. Reminder manual tetap dapat dikirim dari halaman detail izin.

## Peran pengguna

| Peran | Akses |
| --- | --- |
| Admin | Semua fitur, termasuk pengguna, kategori, kontak, aturan, template, WAHA, pengaturan, dan audit |
| Sekretaris | Izin, kontak, reminder manual, riwayat pengiriman, dan laporan |
| HRD | Izin, kontak, dan laporan |
| Viewer | Dashboard, daftar izin, detail izin, dan laporan |

Kontak penerima WhatsApp tidak memerlukan akun aplikasi. Kontak dapat ditautkan ke akun pengguna agar juga menerima notifikasi di dalam aplikasi.

## Pengujian

Siapkan database terpisah, misalnya `siperiz_test`, lalu jalankan:

```bash
php artisan test
```

Konfigurasi koneksi pengujian ada di `phpunit.xml`. Tes memakai MySQL, bukan SQLite, agar perilaku kolom tanggal dan indeks sama dengan aplikasi.

## Struktur singkat

- `app/Livewire`: komponen halaman dan form
- `app/Services/Reminders`: mesin penentu reminder dan penyusun pesan
- `app/Services/Waha`: klien HTTP WAHA
- `app/Jobs/SendReminderJob.php`: pengiriman antrean dan pembaruan status
- `resources/views/livewire`: tampilan halaman
- `resources/css/app.css`: token Tailwind dan utilitas komponen
- `database/seeders/DatabaseSeeder.php`: akun admin, aturan global, template, pengaturan

## Berkas yang tidak termasuk

Repositori ini hanya berisi kode aplikasi. Konfigurasi Apache atau Nginx, SSL, DNS, firewall, dan layanan systemd disiapkan terpisah saat deployment.
