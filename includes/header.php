<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="assets/img/Gemini-.png">
  <title><?= isset($page_title) ? $page_title . ' — SpecSync' : 'SpecSync — Bandingkan & Beli Smartphone Terbaik' ?></title>
  <meta name="description" content="Bandingkan spesifikasi smartphone secara detail. Temukan HP terbaik sesuai kebutuhan dan budget kamu.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo"><img src="assets/img/logo.svg" alt="SpecSync" height="36" style="display:block"></a>

    <nav class="nav">
      <a href="index.php" class="<?= $current_page === 'index' ? 'active' : '' ?>">Beranda</a>
      <a href="catalog.php" class="<?= $current_page === 'catalog' ? 'active' : '' ?>">Katalog</a>
      <a href="compare.php" class="<?= $current_page === 'compare' ? 'active' : '' ?>">Bandingkan</a>
      <a href="deals.php" class="<?= $current_page === 'deals' ? 'active' : '' ?>">Promo 🔥</a>
    </nav>

    <div class="header-actions">
      <!-- Dark/Light toggle -->
      <button class="btn-icon theme-toggle" onclick="App.toggleTheme()" aria-label="Ganti tema" title="Ganti tema">
        <svg class="theme-icon dark" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        <svg class="theme-icon light" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>

      <!-- Wishlist -->
      <a href="dashboard.php#wishlist" class="btn-icon" aria-label="Wishlist">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </a>

      <?php if (!empty($_SESSION['user_id'])): ?>
        <!-- Pesanan icon -->
        <a href="orders.php" class="btn-icon <?= $current_page === 'orders' ? 'active' : '' ?>" aria-label="Pesanan Saya" title="Pesanan Saya">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </a>
        <a href="dashboard.php" class="btn-primary">Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn-icon" style="width:auto;padding:0 14px;font-size:13px;font-weight:600">Masuk</a>
        <a href="register.php" class="btn-primary">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Compare bar (fixed bottom) -->
<div class="compare-bar" id="compare-bar">
  <div class="compare-bar-inner">
    <div style="font-size:13px;font-weight:700;color:var(--text2);white-space:nowrap">Bandingkan:</div>
    <div class="compare-slots" id="compare-slots"></div>
    <button class="btn-clear-compare" onclick="App.clearCompare()">Hapus</button>
    <button class="btn-go-compare" onclick="App.goCompare()">Bandingkan Sekarang →</button>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>
