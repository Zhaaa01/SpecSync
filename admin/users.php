<?php
// admin/users.php
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$page_title = 'Pengguna';
$active_nav = 'users';

$search = sanitize($_GET['search'] ?? '');
$where = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$users = mysqli_query($conn, "SELECT u.*, (SELECT COUNT(*) FROM wishlist WHERE user_id=u.id) as wl_count, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count FROM users u $where ORDER BY u.created_at DESC");

include '_layout.php';
?>

<form method="GET" style="display:flex;gap:8px;margin-bottom:20px">
  <input class="form-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/email..." style="max-width:320px">
  <button type="submit" class="btn-sm">Cari</button>
  <?php if ($search): ?><a href="users.php" class="btn-sm">✕ Reset</a><?php endif; ?>
</form>

<div class="card">
  <table>
    <thead><tr>
      <th>ID</th><th>Nama</th><th>Email</th><th>Wishlist</th><th>Pesanan</th><th>Bergabung</th>
    </tr></thead>
    <tbody>
    <?php while ($u = mysqli_fetch_assoc($users)): ?>
    <tr>
      <td style="color:#8b949e;font-family:monospace"><?= $u['id'] ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#00d4ff,#a855f7);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#000;flex-shrink:0">
            <?= strtoupper(substr($u['name'],0,1)) ?>
          </div>
          <div style="font-weight:600"><?= htmlspecialchars($u['name']) ?></div>
        </div>
      </td>
      <td style="color:#8b949e"><?= htmlspecialchars($u['email']) ?></td>
      <td style="color:#00d4ff;font-weight:700">♥ <?= $u['wl_count'] ?></td>
      <td style="color:#3fb950;font-weight:700">📦 <?= $u['order_count'] ?></td>
      <td style="font-size:12px;color:#8b949e"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include '_layout_end.php'; ?>