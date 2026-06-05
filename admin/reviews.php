<?php
// admin/reviews.php
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$page_title = 'Ulasan';
$active_nav = 'reviews';

if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM reviews WHERE id=$rid");
    header('Location: reviews.php?flash=deleted'); exit;
}

$reviews = mysqli_query($conn, "SELECT r.*, u.name as user_name, d.name as device_name, d.slug FROM reviews r JOIN users u ON r.user_id=u.id JOIN devices d ON r.device_id=d.id ORDER BY r.created_at DESC LIMIT 100");

$flash = $_GET['flash'] ?? '' === 'deleted' ? 'Ulasan dihapus.' : '';
include '_layout.php';
?>

<?php if ($flash): ?>
<div class="flash-msg" style="padding:12px 16px;background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.2);border-radius:8px;color:#f85149;font-size:13px;margin-bottom:20px"><?= $flash ?></div>
<?php endif; ?>

<div class="card">
  <table>
    <thead><tr>
      <th>Pengguna</th><th>Produk</th><th>Rating</th><th>Ulasan</th><th>Pembeli?</th><th>Tanggal</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
    <tr>
      <td style="font-weight:600"><?= htmlspecialchars($r['user_name']) ?></td>
      <td style="font-size:12px"><a href="../device.php?slug=<?= $r['slug'] ?>" target="_blank" style="color:#00d4ff"><?= htmlspecialchars($r['device_name']) ?></a></td>
      <td>
        <span style="color:#d29922">
          <?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5-$r['rating']) ?>
        </span>
      </td>
      <td style="max-width:240px">
        <?php if ($r['title']): ?><div style="font-weight:600;font-size:13px"><?= htmlspecialchars($r['title']) ?></div><?php endif; ?>
        <div style="font-size:12px;color:#8b949e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['body']) ?></div>
      </td>
      <td><?= $r['is_verified_buyer'] ? '<span style="color:#3fb950;font-size:12px">✓ Ya</span>' : '<span style="color:#8b949e;font-size:12px">—</span>' ?></td>
      <td style="font-size:12px;color:#8b949e"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
      <td><a href="?delete=<?= $r['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus ulasan ini?')">Hapus</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include '_layout_end.php'; ?>