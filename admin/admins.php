<?php
// admin/admins.php — Kelola Admin (hanya superadmin)
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$me = currentAdmin();
// Hanya superadmin yang bisa akses halaman ini
if ($me['role'] !== 'superadmin') {
    header('Location: index.php?err=forbidden'); exit;
}

$conn = getDB();
$action = $_GET['action'] ?? 'list';
$id     = intval($_GET['id'] ?? 0);
$page_title = 'Kelola Admin';
$active_nav = 'admins';
$flash = '';

// ── DELETE ──────────────────────────────────────────
if ($action === 'delete' && $id && $id !== $me['id']) {
    mysqli_query($conn, "UPDATE admins SET is_active=0 WHERE id=$id AND role!='superadmin'");
    header('Location: admins.php?flash=deleted'); exit;
}

// ── TOGGLE ACTIVE ───────────────────────────────────
if ($action === 'toggle' && $id && $id !== $me['id']) {
    mysqli_query($conn, "UPDATE admins SET is_active = NOT is_active WHERE id=$id");
    header('Location: admins.php?flash=updated'); exit;
}

// ── SAVE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['superadmin','editor']) ? $_POST['role'] : 'editor';
    $password = $_POST['password'] ?? '';
    $errors   = [];

    if (!$name) $errors[] = 'Nama wajib diisi.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

    if ($action === 'add') {
        if (!$password || strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        // Check email duplicate
        $exists = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admins WHERE email='$email'"))[0];
        if ($exists) $errors[] = 'Email sudah digunakan.';
    }

    if (empty($errors)) {
        if ($action === 'add') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO admins (name, email, password, role, created_by) VALUES ('$name', '$email', '$hash', '$role', {$me['id']})");
            header('Location: admins.php?flash=added'); exit;
        } elseif ($action === 'edit' && $id) {
            $set = "name='$name', email='$email', role='$role'";
            if ($password && strlen($password) >= 6) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $set .= ", password='$hash'";
            }
            mysqli_query($conn, "UPDATE admins SET $set WHERE id=$id");
            header('Location: admins.php?flash=updated'); exit;
        }
    }
}

$edit_admin = null;
if ($action === 'edit' && $id) {
    $edit_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admins WHERE id=$id"));
}

$admins_list = mysqli_query($conn, "SELECT a.*, b.name as created_by_name FROM admins a LEFT JOIN admins b ON a.created_by=b.id ORDER BY a.created_at ASC");

$flash_map = ['added'=>'✓ Admin baru berhasil ditambahkan!','updated'=>'✓ Data admin berhasil diupdate!','deleted'=>'Admin telah dinonaktifkan.'];
$flash = $flash_map[$_GET['flash'] ?? ''] ?? '';
if (isset($_GET['err']) && $_GET['err'] === 'forbidden') $flash = '⛔ Akses ditolak. Hanya superadmin yang bisa mengelola admin.';

include '_layout.php';

if (in_array($action, ['add','edit'])):
$a = $edit_admin ?? [];
?>

<div style="max-width:560px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="admins.php" class="btn-sm">← Kembali</a>
    <h2 style="font-size:18px;font-weight:700"><?= $action === 'edit' ? 'Edit Admin' : 'Tambah Admin Baru' ?></h2>
  </div>

  <?php if (!empty($errors)): ?>
  <div style="padding:12px 16px;background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.2);border-radius:8px;color:#f85149;font-size:13px;margin-bottom:16px">
    <?php foreach ($errors as $e): ?><div>⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title">👤 Informasi Admin</div></div>
      <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
        <div class="form-group">
          <label class="form-label">Nama Lengkap *</label>
          <input class="form-input" name="name" value="<?= htmlspecialchars($a['name'] ?? '') ?>" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input class="form-input" type="email" name="email" value="<?= htmlspecialchars($a['email'] ?? '') ?>" placeholder="admin@specsync.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-select" name="role">
            <option value="editor" <?= ($a['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor — Bisa kelola konten & pesanan</option>
            <option value="superadmin" <?= ($a['role'] ?? '') === 'superadmin' ? 'selected' : '' ?>>Superadmin — Akses penuh termasuk kelola admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label"><?= $action === 'edit' ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *' ?></label>
          <input class="form-input" type="password" name="password" placeholder="Min. 6 karakter" <?= $action === 'add' ? 'required' : '' ?> autocomplete="new-password">
        </div>
      </div>
    </div>

    <?php if ($action === 'edit' && $id == $me['id']): ?>
    <div style="padding:12px 16px;background:rgba(210,153,34,.08);border:1px solid rgba(210,153,34,.2);border-radius:8px;font-size:13px;color:#d29922;margin-bottom:16px">
      ⚠️ Kamu sedang mengedit akun sendiri.
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px">
      <a href="admins.php" class="btn-sm">Batal</a>
      <button type="submit" class="btn-primary"><?= $action === 'edit' ? '💾 Simpan Perubahan' : '➕ Tambah Admin' ?></button>
    </div>
  </form>
</div>

<?php else: // LIST VIEW ?>

<?php if ($flash): ?>
<div style="padding:12px 16px;background:rgba(63,185,80,.08);border:1px solid rgba(63,185,80,.2);border-radius:8px;color:#3fb950;font-size:13px;margin-bottom:20px"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:18px;font-weight:700">Daftar Admin</h2>
    <p style="font-size:13px;color:#8b949e;margin-top:4px">Kelola akun yang bisa mengakses panel admin</p>
  </div>
  <a href="admins.php?action=add" class="btn-primary">➕ Tambah Admin</a>
</div>

<!-- Role info -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
  <div style="padding:14px 16px;background:rgba(168,85,247,.06);border:1px solid rgba(168,85,247,.2);border-radius:10px">
    <div style="font-size:13px;font-weight:700;color:#a855f7;margin-bottom:4px">👑 Superadmin</div>
    <div style="font-size:12px;color:#8b949e;line-height:1.5">Akses penuh: kelola perangkat, promo, pesanan, ulasan, pengguna, dan admin lain.</div>
  </div>
  <div style="padding:14px 16px;background:rgba(0,212,255,.06);border:1px solid rgba(0,212,255,.2);border-radius:10px">
    <div style="font-size:13px;font-weight:700;color:#00d4ff;margin-bottom:4px">✏️ Editor</div>
    <div style="font-size:12px;color:#8b949e;line-height:1.5">Kelola perangkat, promo, pesanan, dan ulasan. Tidak bisa mengelola akun admin.</div>
  </div>
</div>

<div class="card">
  <table>
    <thead><tr>
      <th>Admin</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Dibuat oleh</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php while ($a = mysqli_fetch_assoc($admins_list)):
      $is_me = $a['id'] == $me['id'];
    ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#00d4ff,#a855f7);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#000;flex-shrink:0"><?= strtoupper(substr($a['name'],0,1)) ?></div>
          <div>
            <div style="font-size:13px;font-weight:700"><?= htmlspecialchars($a['name']) ?> <?= $is_me ? '<span style="font-size:10px;color:#00d4ff">(Kamu)</span>' : '' ?></div>
            <div style="font-size:11px;color:#8b949e">#<?= $a['id'] ?></div>
          </div>
        </div>
      </td>
      <td style="font-size:13px;color:#8b949e"><?= htmlspecialchars($a['email']) ?></td>
      <td>
        <?php if ($a['role'] === 'superadmin'): ?>
        <span style="display:inline-block;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:700;background:rgba(168,85,247,.1);color:#a855f7;border:1px solid rgba(168,85,247,.2)">👑 Superadmin</span>
        <?php else: ?>
        <span style="display:inline-block;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:700;background:rgba(0,212,255,.08);color:#00d4ff;border:1px solid rgba(0,212,255,.15)">✏️ Editor</span>
        <?php endif; ?>
      </td>
      <td style="font-size:12px;color:#8b949e"><?= $a['last_login'] ? date('d M Y H:i', strtotime($a['last_login'])) : 'Belum pernah' ?></td>
      <td>
        <?php if ($a['is_active']): ?>
        <span style="display:inline-block;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:700;background:rgba(63,185,80,.1);color:#3fb950;border:1px solid rgba(63,185,80,.2)">Aktif</span>
        <?php else: ?>
        <span style="display:inline-block;padding:3px 10px;border-radius:5px;font-size:11px;font-weight:700;background:rgba(248,81,73,.1);color:#f85149;border:1px solid rgba(248,81,73,.2)">Nonaktif</span>
        <?php endif; ?>
      </td>
      <td style="font-size:12px;color:#8b949e"><?= htmlspecialchars($a['created_by_name'] ?? '—') ?></td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <a href="admins.php?action=edit&id=<?= $a['id'] ?>" class="btn-sm">Edit</a>
          <?php if (!$is_me && $a['role'] !== 'superadmin'): ?>
          <a href="admins.php?action=toggle&id=<?= $a['id'] ?>" class="btn-sm <?= $a['is_active'] ? 'btn-warning' : 'btn-success' ?>"><?= $a['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></a>
          <?php elseif ($is_me): ?>
          <span style="font-size:11px;color:#484f58;padding:6px 0">Akun sendiri</span>
          <?php else: ?>
          <span style="font-size:11px;color:#484f58;padding:6px 0">Superadmin</span>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php include '_layout_end.php'; ?>
