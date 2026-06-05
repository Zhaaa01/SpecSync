<?php
// Salin file ini → rename jadi db.php → isi dengan data asli kamu

// ── Koneksi Database ──────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // ← isi password database kamu
define('DB_NAME', 'specsync');

// ── Gemini API Key (Opsional) ─────────────────────────────────────────────────
// Digunakan untuk fitur AI Import spesifikasi HP
// Dapatkan GRATIS di: https://aistudio.google.com/app/apikey
// Jika dikosongkan, AI Import tetap bisa pakai mode GSMArena Scraper Only
define('GEMINI_API_KEY', '');
