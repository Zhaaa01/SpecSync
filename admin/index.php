<?php
// admin/index.php — Admin Dashboard
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$page_title = 'Dashboard';
$active_nav = 'dashboard';

// Stats
$total_devices  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM devices WHERE is_active=1"))[0];
$total_users    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];
$total_orders   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders"))[0];
$pending_orders = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'"))[0];
$total_revenue  = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM orders WHERE status IN ('paid','shipped','delivered')"))[0];
$active_promos  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM promos WHERE is_active=1 AND end_date >= CURDATE()"))[0];

// Recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, u.name as user_name, d.name as device_name FROM orders o JOIN users u ON o.user_id=u.id JOIN devices d ON o.device_id=d.id ORDER BY o.created_at DESC LIMIT 8");

// Recent devices
$recent_devices = mysqli_query($conn, "SELECT * FROM devices ORDER BY created_at DESC LIMIT 5");

include '_layout.php';
?>

<!-- Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px">
  <div class="stat-card">
    <div style="font-size:28px;margin-bottom:8px">📱</div>
    <div class="stat-value" style="color:#00d4ff"><?= $total_devices ?></div>
    <div class="stat-label">Perangkat Aktif</div>
  </div>
  <div class="stat-card">
    <div style="font-size:28px;margin-bottom:8px">👥</div>
    <div class="stat-value" style="color:#a855f7"><?= $total_users ?></div>
    <div class="stat-label">Total Pengguna</div>
  </div>
  <div class="stat-card">
    <div style="font-size:28px;margin-bottom:8px">📦</div>
    <div class="stat-value" style="color:#3fb950"><?= $total_orders ?></div>
    <div class="stat-label">Total Pesanan</div>
    <?php if ($pending_orders > 0): ?>
    <div class="stat-change" style="color:#d29922">⚠️ <?= $pending_orders ?> menunggu konfirmasi</div>
    <?php endif; ?>
  </div>
  <div class="stat-card">
    <div style="font-size:28px;margin-bottom:8px">💰</div>
    <div class="stat-value" style="color:#d29922;font-size:20px"><?= formatPrice($total_revenue) ?></div>
    <div class="stat-label">Total Revenue</div>
  </div>
  <div class="stat-card">
    <div style="font-size:28px;margin-bottom:8px">🏷️</div>
    <div class="stat-value" style="color:#f85149"><?= $active_promos ?></div>
    <div class="stat-label">Promo Aktif</div>
  </div>
</div>

<!-- Quick Actions -->
<div style="display:flex;gap:10px;margin-bottom:28px;flex-wrap:wrap">
  <a href="devices.php?action=add" class="btn-primary">+ Tambah Perangkat</a>
  <a href="promos.php?action=add" class="btn-primary" style="background:linear-gradient(135deg,#a855f7,#f85149)">+ Buat Promo</a>
  <a href="orders.php?status=pending" class="btn-primary" style="background:linear-gradient(135deg,#d29922,#f85149)">📦 Pesanan Pending (<?= $pending_orders ?>)</a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

  <!-- Recent Orders -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">📦 Pesanan Terbaru</div>
      <a href="orders.php" class="btn-sm">Lihat Semua</a>
    </div>
    <table>
      <thead><tr>
        <th>ID</th><th>Pembeli</th><th>Produk</th><th>Total</th><th>Status</th><th>Waktu</th>
      </tr></thead>
      <tbody>
      <?php while ($o = mysqli_fetch_assoc($recent_orders)):
        $sc = ['pending'=>'pending','paid'=>'paid','shipped'=>'shipped','delivered'=>'delivered','cancelled'=>'cancelled'][$o['status']] ?? 'pending';
        $sl = ['pending'=>'Menunggu','paid'=>'Dibayar','shipped'=>'Dikirim','delivered'=>'Diterima','cancelled'=>'Batal'][$o['status']] ?? '-';
      ?>
      <tr>
        <td style="font-family:monospace;color:#8b949e">#<?= $o['id'] ?></td>
        <td><?= htmlspecialchars($o['user_name']) ?></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['device_name']) ?></td>
        <td style="font-weight:700"><?= formatPrice($o['amount']) ?></td>
        <td><span class="status-badge status-<?= $sc ?>"><?= $sl ?></span></td>
        <td style="color:#8b949e;font-size:12px"><?= date('d M H:i', strtotime($o['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent Devices -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">📱 Perangkat Terbaru</div>
      <a href="devices.php" class="btn-sm">Kelola</a>
    </div>
    <div style="padding:12px">
    <?php while ($d = mysqli_fetch_assoc($recent_devices)): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;transition:.15s" onmouseover="this.style.background='#21262d'" onmouseout="this.style.background='transparent'">
      <img src="<?= htmlspecialchars($d['image'] ?? '') ?>" style="width:40px;height:40px;object-fit:contain;background:#21262d;border-radius:6px;padding:3px" onerror="this.src='https://via.placeholder.com/40?text=📱'">
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($d['name']) ?></div>
        <div style="font-size:11px;color:#8b949e"><?= formatPrice($d['price']) ?></div>
      </div>
      <a href="devices.php?action=edit&id=<?= $d['id'] ?>" class="btn-sm" style="flex-shrink:0">Edit</a>
    </div>
    <?php endwhile; ?>
    </div>
  </div>

</div>

<?php include '_layout_end.php'; ?>