<?php
/**
 * SpecSync Admin Setup — Jalankan SEKALI untuk set password admin
 * Hapus file ini setelah selesai setup!
 * URL: http://localhost/specsync/admin/setup.php
 */

require_once '../includes/db.php';
$conn = getDB();
$msg = '';
$type = '';

// Cek tabel admins ada atau tidak
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
$table_exists = mysqli_num_rows($table_check) > 0;

// Cek kolom apa saja yang ada (supaya tidak error kalau kolom belum lengkap)
$has_is_active = false;
$has_created_by = false;
if ($table_exists) {
    $cols_res = mysqli_query($conn, "SHOW COLUMNS FROM admins");
    while ($col = mysqli_fetch_assoc($cols_res)) {
        if ($col['Field'] === 'is_active')   $has_is_active = true;
        if ($col['Field'] === 'created_by')  $has_created_by = true;
    }
}

// Jika tabel ada tapi kolom is_active belum ada, tambahkan dulu
if ($table_exists && !$has_is_active) {
    mysqli_query($conn, "ALTER TABLE admins ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    $has_is_active = true;
}
if ($table_exists && !$has_created_by) {
    mysqli_query($conn, "ALTER TABLE admins ADD COLUMN created_by INT NULL");
    $has_created_by = true;
}

// Jika tabel belum ada sama sekali, buat sekarang
if (!$table_exists) {
    $create = mysqli_query($conn, "CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('superadmin','editor') DEFAULT 'editor',
        avatar VARCHAR(500),
        is_active TINYINT(1) DEFAULT 1,
        last_login TIMESTAMP NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    if ($create) {
        $table_exists = true;
        $has_is_active = true;
        $msg = '✅ Tabel admins berhasil dibuat otomatis!';
        $type = 'success';
    } else {
        $msg = '❌ Gagal buat tabel: ' . mysqli_error($conn);
        $type = 'error';
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table_exists) {
    $name     = trim($_POST['name'] ?? 'Super Admin');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '⚠️ Email tidak valid.'; $type = 'warn';
    } elseif (!$password || strlen($password) < 6) {
        $msg = '⚠️ Password minimal 6 karakter.'; $type = 'warn';
    } elseif ($password !== $confirm) {
        $msg = '⚠️ Password dan konfirmasi tidak sama.'; $type = 'warn';
    } else {
        $hash       = password_hash($password, PASSWORD_DEFAULT);
        $email_safe = mysqli_real_escape_string($conn, $email);
        $name_safe  = mysqli_real_escape_string($conn, $name);

        $existing = mysqli_fetch_row(mysqli_query($conn, "SELECT id FROM admins WHERE email='$email_safe'"));
        if ($existing) {
            mysqli_query($conn, "UPDATE admins SET name='$name_safe', password='$hash', role='superadmin', is_active=1 WHERE email='$email_safe'");
            $msg = '✅ Password berhasil diperbarui! <a href="login.php" style="color:#00d4ff;font-weight:700">Login sekarang →</a>';
        } else {
            mysqli_query($conn, "INSERT INTO admins (name, email, password, role, is_active) VALUES ('$name_safe', '$email_safe', '$hash', 'superadmin', 1)");
            $msg = '✅ Akun admin berhasil dibuat! <a href="login.php" style="color:#00d4ff;font-weight:700">Login sekarang →</a>';
        }
        $type = 'success';
    }
}

// Ambil daftar admin (pakai SELECT yang aman tanpa kolom yang mungkin belum ada)
$admins = [];
if ($table_exists) {
    $select_cols = "id, name, email, role";
    if ($has_is_active) $select_cols .= ", is_active";
    $res = mysqli_query($conn, "SELECT $select_cols FROM admins ORDER BY id ASC");
    while ($r = mysqli_fetch_assoc($res)) $admins[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Setup — SpecSync</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',sans-serif;background:#0d1117;color:#e6edf3;min-height:100vh;padding:40px 24px}
    .wrap{max-width:560px;margin:0 auto}
    h1{font-size:22px;font-weight:800;margin-bottom:6px}
    .badge{display:inline-block;background:rgba(248,81,73,.15);color:#f85149;border:1px solid rgba(248,81,73,.3);padding:4px 12px;border-radius:6px;font-size:12px;font-weight:700;margin-bottom:20px}
    .card{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:24px;margin-bottom:16px}
    label{font-size:13px;font-weight:600;color:#8b949e;display:block;margin-bottom:6px;margin-top:14px}
    input{width:100%;padding:10px 14px;background:#21262d;border:1px solid #30363d;border-radius:8px;color:#e6edf3;font-size:14px;outline:none;font-family:inherit}
    input:focus{border-color:#00d4ff}
    .btn{padding:11px 24px;background:linear-gradient(135deg,#00d4ff,#a855f7);border:none;border-radius:8px;color:#000;font-size:14px;font-weight:700;cursor:pointer;margin-top:18px;width:100%;font-family:inherit}
    .msg{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;line-height:1.6}
    .msg.success{background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.25);color:#3fb950}
    .msg.error{background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.25);color:#f85149}
    .msg.warn{background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.25);color:#d29922}
    table{width:100%;border-collapse:collapse;font-size:13px}
    th{text-align:left;padding:8px 12px;color:#8b949e;font-size:11px;text-transform:uppercase;border-bottom:1px solid #21262d}
    td{padding:10px 12px;border-bottom:1px solid #21262d}
    tr:last-child td{border-bottom:none}
    .warn-box{background:rgba(210,153,34,.08);border:1px solid rgba(210,153,34,.2);border-radius:8px;padding:12px 16px;font-size:12px;color:#d29922;margin-top:12px;line-height:1.6}
  </style>
</head>
<body>
<div class="wrap">
  <h1>🛠️ SpecSync Admin Setup</h1>
  <div class="badge">⚠️ Hapus file ini setelah setup selesai</div>

  <?php if ($msg): ?>
  <div class="msg <?= $type ?>"><?= $msg ?></div>
  <?php endif; ?>

  <div class="card">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:4px">Buat / Reset Akun Admin</h2>
    <p style="font-size:13px;color:#8b949e;margin-top:4px">Isi form di bawah untuk membuat akun superadmin pertama.</p>
    <form method="POST">
      <label>Nama Admin</label>
      <input name="name" value="<?= htmlspecialchars($_POST['name'] ?? 'Super Admin') ?>" placeholder="Nama lengkap" required>
      <label>Email Admin</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@specsync.com') ?>" placeholder="admin@specsync.com" required>
      <label>Password</label>
      <input type="password" name="password" placeholder="Min. 6 karakter" autocomplete="new-password" required>
      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" placeholder="Ulangi password" autocomplete="new-password" required>
      <button type="submit" class="btn">✅ Simpan Akun Admin</button>
    </form>
  </div>

  <?php if (!empty($admins)): ?>
  <div class="card">
    <h2 style="font-size:15px;font-weight:700;margin-bottom:14px">Admin Terdaftar (<?= count($admins) ?>)</h2>
    <table>
      <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><?= $has_is_active ? '<th>Status</th>' : '' ?></tr></thead>
      <tbody>
      <?php foreach ($admins as $a): ?>
      <tr>
        <td style="color:#8b949e"><?= $a['id'] ?></td>
        <td style="font-weight:600"><?= htmlspecialchars($a['name']) ?></td>
        <td style="color:#8b949e;font-size:12px"><?= htmlspecialchars($a['email']) ?></td>
        <td><span style="color:<?= ($a['role']==='superadmin')?'#a855f7':'#00d4ff' ?>;font-size:12px;font-weight:700"><?= $a['role'] ?></span></td>
        <?php if ($has_is_active): ?>
        <td><?= $a['is_active'] ? '<span style="color:#3fb950;font-size:12px">Aktif</span>' : '<span style="color:#f85149;font-size:12px">Nonaktif</span>' ?></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="warn-box">
    🔒 <strong>Penting:</strong> Hapus file <code>admin/setup.php</code> setelah berhasil login untuk keamanan.
  </div>
</div>
</body>
</html>
