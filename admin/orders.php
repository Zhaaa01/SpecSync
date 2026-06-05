<?php
// admin/orders.php — Kelola Pesanan
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn = getDB();
$page_title = 'Pesanan';
$active_nav = 'orders';

// ── UPDATE STATUS ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = intval($_POST['order_id']);
    $status = in_array($_POST['status'], ['pending','paid','shipped','delivered','cancelled']) ? $_POST['status'] : 'pending';
    $tracking = sanitize($_POST['tracking_number'] ?? '');
    mysqli_query($conn, "UPDATE orders SET status='$status', tracking_number='$tracking'".($status==='paid'?", paid_at=NOW()":'')." WHERE id=$oid");
    header('Location: orders.php?flash=updated&status='.$_GET['status']); exit;
}

// ── FILTER ────────────────────────────────────────────
$status_filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where = 'WHERE 1';
if ($status_filter) $where .= " AND o.status='$status_filter'";
if ($search) $where .= " AND (u.name LIKE '%$search%' OR d.name LIKE '%$search%' OR o.id='$search')";

$orders = mysqli_query($conn, "SELECT o.*, u.name as user_name, u.email as user_email, d.name as device_name, d.image as device_image 
    FROM orders o 
    JOIN users u ON o.user_id=u.id 
    JOIN devices d ON o.device_id=d.id 
    $where ORDER BY o.created_at DESC LIMIT 100");

$status_counts = [];
$res = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
while ($r = mysqli_fetch_assoc($res)) $status_counts[$r['status']] = $r['cnt'];

$flash = '';
if ($_GET['flash'] ?? '' === 'updated') $flash = '✓ Status pesanan berhasil diupdate!';

include '_layout.php';
?>

<?php if ($flash): ?>
<div class="flash-msg" style="padding:12px 16px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);border-radius:8px;color:#3fb950;font-size:13px;margin-bottom:20px"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<!-- Status filter tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap">
  <?php
  $tab_labels = [''=> 'Semua','pending'=>'⏳ Menunggu','paid'=>'✓ Dibayar','shipped'=>'🚚 Dikirim','delivered'=>'✅ Diterima','cancelled'=>'✕ Batal'];
  foreach ($tab_labels as $s => $l):
    $cnt = $s ? ($status_counts[$s] ?? 0) : array_sum($status_counts);
  ?>
  <a href="orders.php?status=<?= $s ?>" style="padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;border:1px solid <?= $status_filter === $s ? '#00d4ff' : '#30363d' ?>;color:<?= $status_filter === $s ? '#00d4ff' : '#8b949e' ?>;background:<?= $status_filter === $s ? 'rgba(0,212,255,.08)' : 'transparent' ?>">
    <?= $l ?> <span style="margin-left:4px;background:#21262d;padding:1px 6px;border-radius:10px;font-size:11px"><?= $cnt ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Search -->
<form method="GET" style="display:flex;gap:8px;margin-bottom:20px">
  <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
  <input class="form-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari ID, nama pembeli, nama HP..." style="max-width:340px">
  <button type="submit" class="btn-sm">Cari</button>
  <?php if ($search): ?><a href="orders.php?status=<?= $status_filter ?>" class="btn-sm">✕</a><?php endif; ?>
</form>

<div class="card">
  <table>
    <thead><tr>
      <th>ID</th><th>Pembeli</th><th>Produk</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php while ($o = mysqli_fetch_assoc($orders)):
      $sc_map = ['pending'=>'pending','paid'=>'paid','shipped'=>'shipped','delivered'=>'delivered','cancelled'=>'cancelled'];
      $sl_map = ['pending'=>'Menunggu','paid'=>'Dibayar','shipped'=>'Dikirim','delivered'=>'Diterima','cancelled'=>'Batal'];
      $pay_labels = ['va_bca'=>'VA BCA','va_bni'=>'VA BNI','va_mandiri'=>'VA Mandiri','va_bri'=>'VA BRI','qris'=>'QRIS','cc'=>'Kartu Kredit'];
    ?>
    <tr>
      <td style="font-family:monospace;color:#8b949e;font-size:12px">#<?= $o['id'] ?></td>
      <td>
        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($o['user_name']) ?></div>
        <div style="font-size:11px;color:#8b949e"><?= htmlspecialchars($o['user_email']) ?></div>
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <img src="<?= htmlspecialchars($o['device_image'] ?? '') ?>" style="width:36px;height:36px;object-fit:contain;background:#21262d;border-radius:5px;padding:2px" onerror="this.src='https://via.placeholder.com/36?text=📱'">
          <span style="font-size:13px;font-weight:500"><?= htmlspecialchars($o['device_name']) ?></span>
        </div>
      </td>
      <td style="font-weight:700"><?= formatPrice($o['amount']) ?></td>
      <td style="font-size:12px;color:#8b949e">
        <?= $pay_labels[$o['payment_channel'] ?? ''] ?? ($o['payment_channel'] ?: '—') ?>
        <?php if ($o['payment_code']): ?>
        <div style="font-family:monospace;font-size:11px;margin-top:2px"><?= htmlspecialchars(substr($o['payment_code'],0,16)) ?>…</div>
        <?php endif; ?>
      </td>
      <td><span class="status-badge status-<?= $sc_map[$o['status']] ?>"><?= $sl_map[$o['status']] ?></span></td>
      <td style="font-size:12px;color:#8b949e"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
      <td>
        <button class="btn-sm" onclick="openUpdateModal(<?= $o['id'] ?>, '<?= $o['status'] ?>', '<?= htmlspecialchars(addslashes($o['tracking_number'] ?? '')) ?>')">Update</button>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="modal-update">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <div class="modal-title">Update Status Pesanan #<span id="modal-order-id"></span></div>
      <button class="modal-close" onclick="closeModal('modal-update')">✕</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="order_id" id="form-order-id">
        <div class="form-group">
          <label class="form-label">Status Baru</label>
          <select class="form-select" name="status" id="form-status">
            <option value="pending">⏳ Menunggu Pembayaran</option>
            <option value="paid">✓ Sudah Dibayar</option>
            <option value="shipped">🚚 Sedang Dikirim</option>
            <option value="delivered">✅ Sudah Diterima</option>
            <option value="cancelled">✕ Dibatalkan</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nomor Resi (opsional)</label>
          <input class="form-input" name="tracking_number" id="form-tracking" placeholder="JNE123456789" style="font-family:monospace">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-sm" onclick="closeModal('modal-update')">Batal</button>
        <button type="submit" class="btn-primary">Simpan Update</button>
      </div>
    </form>
  </div>
</div>

<script>
function openUpdateModal(id, status, tracking) {
  document.getElementById('modal-order-id').textContent = id;
  document.getElementById('form-order-id').value = id;
  document.getElementById('form-status').value = status;
  document.getElementById('form-tracking').value = tracking;
  openModal('modal-update');
}
</script>

<?php include '_layout_end.php'; ?>