<?php
// admin/login.php
session_start();
require_once '../includes/db.php';

if (!empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $conn = getDB();
        $email_safe = mysqli_real_escape_string($conn, $email);
        $res   = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email_safe' AND is_active=1 LIMIT 1");

        if (!$res) {
            $error = 'Database error: ' . mysqli_error($conn) . ' — Pastikan sudah import database_update.sql';
        } else {
            $admin = mysqli_fetch_assoc($res);
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                mysqli_query($conn, "UPDATE admins SET last_login=NOW() WHERE id={$admin['id']}");
                header('Location: index.php');
                exit;
            } else {
                // Cek apakah email ada tapi password salah, atau memang tidak ada
                $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, is_active FROM admins WHERE email='$email_safe' LIMIT 1"));
                if (!$check) {
                    $error = 'Email tidak ditemukan. Jalankan <a href="setup.php" style="color:#f85149;text-decoration:underline">setup.php</a> untuk membuat akun admin.';
                } elseif (!$check['is_active']) {
                    $error = 'Akun admin ini dinonaktifkan. Hubungi superadmin.';
                } else {
                    $error = 'Password salah.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — SpecSync</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#0d1117;color:#e6edf3;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .card{background:#161b22;border:1px solid #30363d;border-radius:16px;padding:40px;width:100%;max-width:400px;margin:24px}
    .logo{font-size:24px;font-weight:800;margin-bottom:6px}
    .logo span{background:linear-gradient(135deg,#00d4ff,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    label{font-size:13px;font-weight:600;color:#8b949e;display:block;margin-bottom:6px;margin-top:14px}
    input{width:100%;padding:11px 14px;background:#21262d;border:1px solid #30363d;border-radius:9px;color:#e6edf3;font-size:15px;font-family:inherit;transition:border-color .2s;outline:none}
    input:focus{border-color:#00d4ff}
    .btn{width:100%;padding:13px;background:linear-gradient(135deg,#00d4ff,#a855f7);border:none;border-radius:10px;color:#000;font-size:15px;font-weight:700;cursor:pointer;margin-top:20px;font-family:inherit;transition:opacity .2s}
    .btn:hover{opacity:.9}
    .error{padding:11px 14px;background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);border-radius:8px;color:#f85149;font-size:13px;margin-bottom:16px;line-height:1.6}
    .badge{display:inline-block;background:rgba(0,212,255,.1);color:#00d4ff;border:1px solid rgba(0,212,255,.2);padding:3px 10px;border-radius:6px;font-size:11px;font-weight:700;margin-bottom:24px}
    .setup-link{margin-top:16px;padding:12px;background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.15);border-radius:8px;font-size:12px;color:#8b949e;text-align:center;line-height:1.7}
    .back{display:block;text-align:center;margin-top:16px;font-size:13px;color:#8b949e;text-decoration:none}
    .back:hover{color:#00d4ff}
  </style>
</head>
<body>
<div class="card">
  <div class="logo">Spec<span>Sync</span></div>
  <div class="badge">🔐 Admin Panel</div>
  <h2 style="font-size:20px;font-weight:700;margin-bottom:4px">Masuk sebagai Admin</h2>
  <p style="font-size:13px;color:#8b949e;margin-bottom:24px">Akses dashboard pengelolaan konten & transaksi</p>

  <?php if ($error): ?>
  <div class="error">⚠️ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Email Admin</label>
    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@specsync.com" required autocomplete="username">
    <label>Password</label>
    <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
    <button type="submit" class="btn">Masuk ke Panel Admin →</button>
  </form>

  <div class="setup-link">
    🛠️ Pertama kali setup? Buka <a href="setup.php" style="color:#00d4ff;font-weight:700">setup.php</a> untuk buat akun admin.<br>
    Pastikan sudah import <strong>database_update.sql</strong> di phpMyAdmin.
  </div>
  <a href="../index.php" class="back">← Kembali ke SpecSync</a>
</div>
</body>
</html>
