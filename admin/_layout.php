<?php
// admin/_layout.php — shared admin UI shell
// Usage: include after setting $page_title and $active_nav
$admin = currentAdmin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($page_title ?? 'Admin') ?> — SpecSync Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#0d1117;color:#e6edf3;display:flex;min-height:100vh}
    a{color:inherit;text-decoration:none}
    ::-webkit-scrollbar{width:6px;height:6px}
    ::-webkit-scrollbar-track{background:#161b22}
    ::-webkit-scrollbar-thumb{background:#30363d;border-radius:3px}

    /* Sidebar */
    .sidebar{width:240px;background:#161b22;border-right:1px solid #30363d;display:flex;flex-direction:column;flex-shrink:0;position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto}
    .sidebar-logo{padding:24px 20px 16px;border-bottom:1px solid #21262d}
    .sidebar-logo .logo{font-size:20px;font-weight:800}
    .sidebar-logo .logo span{background:linear-gradient(135deg,#00d4ff,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .sidebar-logo .badge{font-size:10px;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:.8px;margin-top:2px}
    .nav-section{padding:16px 12px 8px;font-size:10px;font-weight:700;color:#484f58;text-transform:uppercase;letter-spacing:.8px}
    .nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:14px;font-weight:500;color:#8b949e;cursor:pointer;transition:all .15s;margin:1px 8px}
    .nav-item:hover{background:#21262d;color:#e6edf3}
    .nav-item.active{background:rgba(0,212,255,.1);color:#00d4ff;font-weight:600}
    .nav-item .icon{width:18px;text-align:center;flex-shrink:0}
    .nav-badge{margin-left:auto;background:#21262d;color:#8b949e;font-size:11px;font-weight:700;padding:1px 7px;border-radius:10px}
    .sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid #21262d}
    .admin-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
    .admin-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#00d4ff,#a855f7);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#000;flex-shrink:0}
    .admin-name{font-size:13px;font-weight:700}
    .admin-role{font-size:11px;color:#8b949e;text-transform:capitalize}
    .btn-logout{display:block;width:100%;padding:8px;background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.2);border-radius:8px;color:#f85149;font-size:13px;font-weight:600;text-align:center;cursor:pointer;transition:all .15s}
    .btn-logout:hover{background:rgba(248,81,73,.2)}

    /* Main content */
    .main{margin-left:240px;flex:1;min-height:100vh;display:flex;flex-direction:column}
    .topbar{padding:16px 28px;border-bottom:1px solid #21262d;display:flex;align-items:center;justify-content:space-between;background:#161b22;position:sticky;top:0;z-index:50}
    .topbar-title{font-size:18px;font-weight:700}
    .content{padding:28px;flex:1}

    /* Cards & Tables */
    .stat-card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:20px}
    .stat-value{font-size:28px;font-weight:800}
    .stat-label{font-size:13px;color:#8b949e;margin-top:4px}
    .stat-change{font-size:12px;margin-top:6px}
    .card{background:#161b22;border:1px solid #30363d;border-radius:12px}
    .card-header{padding:18px 20px;border-bottom:1px solid #21262d;display:flex;align-items:center;justify-content:space-between}
    .card-title{font-size:15px;font-weight:700}
    table{width:100%;border-collapse:collapse}
    th{padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #21262d;background:#0d1117}
    td{padding:13px 16px;font-size:13px;border-bottom:1px solid #161b22;vertical-align:middle}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:#21262d}

    /* Buttons */
    .btn-primary{padding:9px 18px;background:linear-gradient(135deg,#00d4ff,#a855f7);border:none;border-radius:8px;color:#000;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;font-family:inherit}
    .btn-primary:hover{opacity:.85}
    .btn-sm{padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid #30363d;background:transparent;color:#8b949e;font-family:inherit;transition:all .15s}
    .btn-sm:hover{background:#21262d;color:#e6edf3}
    .btn-danger{color:#f85149;border-color:rgba(248,81,73,.3)}
    .btn-danger:hover{background:rgba(248,81,73,.1);color:#f85149}
    .btn-success{color:#3fb950;border-color:rgba(63,185,80,.3)}
    .btn-success:hover{background:rgba(63,185,80,.1);color:#3fb950}
    .btn-warning{color:#d29922;border-color:rgba(210,153,34,.3)}
    .btn-warning:hover{background:rgba(210,153,34,.1);color:#d29922}

    /* Form elements */
    .form-group{margin-bottom:16px}
    .form-label{font-size:13px;font-weight:600;color:#8b949e;display:block;margin-bottom:6px}
    .form-input,.form-select,.form-textarea{width:100%;padding:9px 12px;background:#21262d;border:1px solid #30363d;border-radius:8px;color:#e6edf3;font-size:14px;font-family:inherit;transition:border-color .2s;outline:none}
    .form-input:focus,.form-select:focus,.form-textarea:focus{border-color:#00d4ff}
    .form-textarea{resize:vertical;min-height:80px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}

    /* Badge/Status */
    .status-badge{display:inline-block;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:700}
    .status-active{background:rgba(63,185,80,.1);color:#3fb950}
    .status-inactive{background:rgba(248,81,73,.1);color:#f85149}
    .status-pending{background:rgba(210,153,34,.1);color:#d29922}
    .status-paid{background:rgba(0,212,255,.1);color:#00d4ff}
    .status-shipped{background:rgba(168,85,247,.1);color:#a855f7}
    .status-delivered{background:rgba(63,185,80,.1);color:#3fb950}
    .status-cancelled{background:rgba(248,81,73,.1);color:#f85149}

    /* Modal */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;padding:24px}
    .modal-overlay.open{display:flex}
    .modal{background:#161b22;border:1px solid #30363d;border-radius:16px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto}
    .modal-header{padding:20px 24px;border-bottom:1px solid #21262d;display:flex;align-items:center;justify-content:space-between}
    .modal-title{font-size:16px;font-weight:700}
    .modal-close{background:none;border:none;color:#8b949e;font-size:20px;cursor:pointer;line-height:1}
    .modal-body{padding:24px}
    .modal-footer{padding:16px 24px;border-top:1px solid #21262d;display:flex;justify-content:flex-end;gap:10px}

    /* Toast */
    #admin-toast{position:fixed;bottom:24px;right:24px;z-index:999;display:flex;flex-direction:column;gap:8px}
    .a-toast{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:600;animation:slideIn .3s ease;max-width:320px}
    .a-toast.success{background:#1c2b1e;border:1px solid rgba(63,185,80,.3);color:#3fb950}
    .a-toast.error{background:#2b1b1b;border:1px solid rgba(248,81,73,.3);color:#f85149}
    .a-toast.info{background:#1b2230;border:1px solid rgba(0,212,255,.2);color:#58a6ff}
    @keyframes slideIn{from{transform:translateX(20px);opacity:0}to{transform:none;opacity:1}}

    @media(max-width:900px){.sidebar{transform:translateX(-100%)}.main{margin-left:0}}
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo">Spec<span>Sync</span></div>
    <div class="badge">Admin Panel</div>
  </div>

  <div class="nav-section">Utama</div>
  <a href="index.php" class="nav-item <?= ($active_nav ?? '') === 'dashboard' ? 'active' : '' ?>">
    <span class="icon">📊</span> Dashboard
  </a>

  <div class="nav-section">Konten</div>
  <a href="devices.php" class="nav-item <?= ($active_nav ?? '') === 'devices' ? 'active' : '' ?>">
    <span class="icon">📱</span> Perangkat
  </a>
  <a href="ai_import.php" class="nav-item <?= ($active_nav ?? '') === 'ai_import' ? 'active' : '' ?>" style="background:linear-gradient(90deg,rgba(0,212,255,.05),transparent)">
    <span class="icon">🤖</span> AI Import HP
  </a>
  <a href="promos.php" class="nav-item <?= ($active_nav ?? '') === 'promos' ? 'active' : '' ?>">
    <span class="icon">🏷️</span> Promo & Deals
  </a>

  <div class="nav-section">Transaksi</div>
  <a href="orders.php" class="nav-item <?= ($active_nav ?? '') === 'orders' ? 'active' : '' ?>">
    <span class="icon">📦</span> Pesanan
    <?php
    // Quick count pending orders
    if (function_exists('getDB')) {
        $conn = getDB();
        $pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'"));
        if ($pending[0] > 0): ?>
    <span class="nav-badge"><?= $pending[0] ?></span>
    <?php endif; } ?>
  </a>
  <a href="users.php" class="nav-item <?= ($active_nav ?? '') === 'users' ? 'active' : '' ?>">
    <span class="icon">👥</span> Pengguna
  </a>
  <a href="reviews.php" class="nav-item <?= ($active_nav ?? '') === 'reviews' ? 'active' : '' ?>">
    <span class="icon">⭐</span> Ulasan
  </a>
  <?php if (($me['role'] ?? '') === 'superadmin'): ?>
  <div class="nav-section">Pengaturan</div>
  <a href="admins.php" class="nav-item <?= ($active_nav ?? '') === 'admins' ? 'active' : '' ?>">
    <span class="icon">👥</span> Kelola Admin
  </a>
  <?php endif; ?>

  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar"><?= strtoupper(substr($admin['name'], 0, 1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($admin['name']) ?></div>
        <div class="admin-role"><?= $admin['role'] ?></div>
      </div>
    </div>
    <a href="logout.php" class="btn-logout">Keluar dari Admin</a>
  </div>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title"><?= htmlspecialchars($page_title ?? '') ?></div>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="../index.php" target="_blank" style="font-size:13px;color:#8b949e">Lihat Website ↗</a>
    </div>
  </div>
  <div class="content">