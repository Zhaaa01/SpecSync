<p align="center">
  <h1 align="center">📱 SpecSync</h1>
  <p align="center">Platform perbandingan & pembelian smartphone berbasis PHP dengan AI Import otomatis dari GSMArena</p>
  <p align="center">
    <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white"/>
    <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql&logoColor=white"/>
    <img src="https://img.shields.io/badge/Gemini-AI-4285F4?style=flat-square&logo=google&logoColor=white"/>
    <img src="https://img.shields.io/badge/GSMArena-Scraper-1E90FF?style=flat-square"/>
    <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square"/>
  </p>
</p>

---

## 📸 Screenshot

> Ganti link di bawah ini dengan screenshot asli proyekmu setelah upload ke GitHub.
> Caranya: upload gambar ke folder `docs/screenshots/` lalu ubah path-nya.

| Homepage | Perbandingan |
|:---:|:---:|
| ![Homepage](docs/screenshots/homepage.png) | ![Compare](docs/screenshots/compare.png) |

| Katalog | AI Import Admin |
|:---:|:---:|
| ![Catalog](docs/screenshots/catalog.png) | ![AI Import](docs/screenshots/ai_import.png) |

---

## ✨ Tentang Proyek

**SpecSync** adalah platform web berbasis PHP murni untuk **membandingkan spesifikasi smartphone** secara visual dan detail. Dilengkapi **Admin Panel** dengan fitur **AI Import** yang bisa mengambil data spesifikasi secara otomatis dari **GSMArena** dan/atau **Gemini AI** — cukup ketik nama HP, data langsung masuk ke database.

> 🚫 Tidak membutuhkan framework apapun — langsung jalan di XAMPP.

---

## 🚀 Fitur Utama

### 🖥️ Halaman Publik

| Fitur | Deskripsi |
|---|---|
| 🔍 Live Search | Autosuggest real-time dengan foto, merek & harga |
| ⚖️ Perbandingan Canggih | Highlight pemenang otomatis, filter perbedaan, visualisasi ukuran fisik |
| 📊 Score Bar | Grafik perbandingan Kamera / Performa / Baterai / Desain |
| 🎯 Filter Persona | Cepat filter: Gaming · Foto · Baterai · Budget |
| 🌙 Dark / Light Mode | Toggle tema dengan transisi smooth |
| ⭐ Ulasan & Rating | Star rating + badge Pembeli Terverifikasi |
| 🔗 Share Comparison | Salin URL perbandingan ke clipboard |
| 💰 Price Alert | Notifikasi turun harga (butuh login) |
| 📦 Order & Resi | Riwayat transaksi + tracking nomor resi |

### 🔧 Admin Panel

| Fitur | Deskripsi |
|---|---|
| 🤖 AI Import Spesifikasi | Ketik nama HP → otomatis ambil dari GSMArena + Gemini AI |
| ☑️ Pilih Sumber Data | Checkbox untuk aktifkan/nonaktifkan GSMArena Scraper & Gemini AI |
| ✅ Validasi Nama Device | Sistem otomatis tolak data yang tidak sesuai nama HP yang dicari |
| 📋 Kelola Devices | CRUD lengkap untuk data smartphone |
| 👥 Kelola Users | Manajemen pengguna & admin |
| 💬 Moderasi Ulasan | Review & hapus ulasan pengguna |
| 🏷️ Manajemen Promo | Kelola kode promo & diskon |
| 📦 Kelola Order | Monitor & update status pesanan |

---

## 🛠️ Tech Stack

```
Backend   : PHP 8.x + MySQLi (tanpa framework)
Database  : MySQL / MariaDB
Frontend  : Vanilla JS + CSS Custom Properties
AI        : Google Gemini API (opsional)
Scraping  : GSMArena HTML Parser
Font      : Inter (Google Fonts)
Server    : Apache (XAMPP / Laragon / shared hosting)
```

---

## 📁 Struktur Folder

```
specsync2_new/
├── index.php              ← Homepage + Hero + Live Search
├── catalog.php            ← Katalog lengkap + filter sidebar
├── compare.php            ← Perbandingan canggih
├── device.php             ← Detail spesifikasi + ulasan
├── dashboard.php          ← Dashboard user
├── login.php / register.php / logout.php
├── api.php                ← REST API (AJAX: search, wishlist, dll)
├── specsync.sql           ← Schema + sample data (9 smartphone)
│
├── admin/
│   ├── index.php          ← Dashboard admin
│   ├── ai_import.php      ← ✨ Halaman AI Import spesifikasi
│   ├── ai_import_api.php  ← Backend scraper GSMArena + Gemini
│   ├── devices.php        ← Kelola data HP
│   ├── users.php          ← Kelola pengguna
│   ├── orders.php         ← Kelola pesanan
│   ├── reviews.php        ← Moderasi ulasan
│   └── promos.php         ← Manajemen promo
│
├── assets/
│   ├── css/style.css      ← Semua styling (dark/light mode)
│   └── js/app.js          ← Logic utama
│
├── docs/
│   └── screenshots/       ← Taruh screenshot di sini
│
└── includes/
    ├── db.php             ← Koneksi database + konfigurasi API key
    ├── db.example.php     ← Template konfigurasi (tanpa data asli)
    ├── header.php         ← Header + navigasi + compare bar
    └── footer.php         ← Footer + script loader
```

---

## ⚙️ Cara Instalasi

### 1. Clone repository

```bash
git clone https://github.com/USERNAME/specsync.git
cd specsync
```

### 2. Import database

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Buat database baru bernama `specsync`
3. Import file `specsync.sql`

### 3. Konfigurasi

Duplikat `includes/db.example.php` → rename jadi `includes/db.php`, lalu isi:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // password database kamu
define('DB_NAME', 'specsync');

// Opsional — untuk fitur AI Import dengan Gemini
// Dapatkan gratis di: https://aistudio.google.com/app/apikey
define('GEMINI_API_KEY', 'ISI_API_KEY_DISINI');
```

> ⚠️ Tanpa Gemini API Key, AI Import tetap bisa digunakan dengan mode **GSMArena Scraper Only**.

### 4. Setup admin

Buka: `http://localhost/specsync/admin/setup.php`

### 5. Akses

| URL | Halaman |
|---|---|
| `http://localhost/specsync/` | Homepage |
| `http://localhost/specsync/catalog.php` | Katalog HP |
| `http://localhost/specsync/admin/` | Admin Panel |

---

## 🤖 Cara Pakai AI Import

1. Login ke Admin Panel → menu **AI Import**
2. Pilih sumber data:
   - ☑️ **GSMArena Scraper** — data akurat langsung dari gsmarena.com
   - ☑️ **Gemini AI** — fallback AI jika scraper tidak menemukan
3. Ketik nama HP, contoh: `Samsung Galaxy S25 Ultra`
4. Klik **Ambil Spesifikasi** → periksa preview → **Simpan ke Database**

---

## 📱 Sample Data (9 HP bawaan)

| # | Device | Kategori |
|---|---|---|
| 1 | Samsung Galaxy S25 Ultra | Flagship |
| 2 | Apple iPhone 16 Pro Max | Flagship |
| 3 | Xiaomi 14 Ultra | Flagship |
| 4 | Google Pixel 9 Pro | Flagship |
| 5 | OnePlus 13 | Flagship |
| 6 | Samsung Galaxy A56 | Mid-range |
| 7 | Realme GT 7 Pro | Mid-range |
| 8 | POCO X7 Pro | Mid-range |
| 9 | Redmi Note 14 Pro+ | Mid-range |

---

## 🗺️ Roadmap

- [ ] Integrasi Payment Gateway (Midtrans/Xendit)
- [ ] Email notifikasi price alert (cron job)
- [ ] PWA + push notification
- [ ] Comparison embed widget untuk blog
- [ ] Forum diskusi / thread komentar

---

## 📄 Lisensi

Proyek ini menggunakan lisensi [MIT](LICENSE).

---

<p align="center">Dibuat dengan ☕ dan PHP murni</p>
