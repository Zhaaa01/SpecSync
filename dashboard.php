<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$conn = getDB();
$user_id = intval($_SESSION['user_id']);

// Get user info
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($user_res);

// Wishlist
$wl_res = mysqli_query($conn, "SELECT d.id, d.name, d.brand, d.slug, d.price, d.image, d.score_camera, d.score_performance, d.score_battery, d.score_design FROM wishlist w JOIN devices d ON w.device_id=d.id WHERE w.user_id=$user_id ORDER BY w.added_at DESC");
$wishlist = [];
while ($r = mysqli_fetch_assoc($wl_res)) {
    $r['price_fmt'] = formatPrice($r['price']);
    $wishlist[] = $r;
}

// Saved comparisons
$comp_res = mysqli_query($conn, "SELECT sc.*, d1.name as d1_name, d1.slug as d1_slug, d2.name as d2_name, d2.slug as d2_slug FROM saved_comparisons sc JOIN devices d1 ON sc.device1_id=d1.id JOIN devices d2 ON sc.device2_id=d2.id WHERE sc.user_id=$user_id ORDER BY sc.created_at DESC LIMIT 10");
$saved_comps = [];
while ($r = mysqli_fetch_assoc($comp_res)) $saved_comps[] = $r;

// Price alerts
$alert_res = mysqli_query($conn, "SELECT pa.*, d.name as device_name, d.slug, d.price, d.image FROM price_alerts pa JOIN devices d ON pa.device_id=d.id WHERE pa.user_id=$user_id ORDER BY pa.created_at DESC");
$alerts = [];
while ($r = mysqli_fetch_assoc($alert_res)) {
    $r['target_price_fmt'] = formatPrice($r['target_price']);
    $r['current_price_fmt'] = formatPrice($r['price']);
    $alerts[] = $r;
}

// Orders
$order_res = mysqli_query($conn, "SELECT o.*, d.name as device_name, d.image FROM orders o JOIN devices d ON o.device_id=d.id WHERE o.user_id=$user_id ORDER BY o.created_at DESC LIMIT 20");
$orders = [];
while ($r = mysqli_fetch_assoc($order_res)) {
    $r['amount_fmt'] = formatPrice($r['amount']);
    $orders[] = $r;
}

$page_title = 'Dashboard';

$tab = $_GET['tab'] ?? 'wishlist';
?>
<?php require_once 'includes/header.php'; ?>

<div style="max-width:1200px;margin:0 auto;padding:32px 24px">

  <!-- Profile Header -->
  <div style="display:flex;align-items:center;gap:20px;margin-bottom:36px;padding-bottom:28px;border-bottom:1px solid var(--border)">
    <div style="width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:#000;flex-shrink:0">
      <?= strtoupper(substr($user['name'], 0, 1)) ?>
    </div>
    <div>
      <h1 style="font-size:24px;font-weight:800"><?= htmlspecialchars($user['name']) ?></h1>
      <div style="font-size:14px;color:var(--text3)"><?= htmlspecialchars($user['email']) ?></div>
    </div>
    <div style="margin-left:auto;display:flex;gap:16px;text-align:center">
      <div><div style="font-size:22px;font-weight:800;color:var(--accent)"><?= count($wishlist) ?></div><div style="font-size:12px;color:var(--text3)">Wishlist</div></div>
      <div><div style="font-size:22px;font-weight:800;color:var(--purple)"><?= count($saved_comps) ?></div><div style="font-size:12px;color:var(--text3)">Perbandingan</div></div>
      <div><div style="font-size:22px;font-weight:800;color:var(--amber)"><?= count($alerts) ?></div><div style="font-size:12px;color:var(--text3)">Alert Harga</div></div>
    </div>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:1px solid var(--border)">
    <?php
    $tabs = [
      'wishlist' => ['♥ Wishlist', count($wishlist)],
      'comparisons' => ['⚖️ Perbandingan Tersimpan', count($saved_comps)],
      'alerts' => ['🔔 Alert Harga', count($alerts)],
      'orders' => ['📦 Riwayat Transaksi', count($orders)],
    ];
    foreach ($tabs as $t_id => [$t_label, $t_count]):
    ?>
    <a href="dashboard.php?tab=<?= $t_id ?>" style="padding:10px 18px;border-radius:8px 8px 0 0;font-size:14px;font-weight:<?= $tab === $t_id ? '700' : '500' ?>;color:<?= $tab === $t_id ? 'var(--accent)' : 'var(--text2)' ?>;border-bottom:2px solid <?= $tab === $t_id ? 'var(--accent)' : 'transparent' ?>;transition:all 0.2s;display:flex;align-items:center;gap:6px">
      <?= $t_label ?>
      <?php if ($t_count > 0): ?><span style="background:var(--bg3);padding:1px 7px;border-radius:10px;font-size:11px;font-weight:700"><?= $t_count ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Wishlist Tab -->
  <?php if ($tab === 'wishlist'): ?>
  <?php if (empty($wishlist)): ?>
    <div style="text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">♡</div><div style="font-size:16px;font-weight:600">Wishlist kosong</div><div style="font-size:14px;margin-top:8px">Tambahkan HP incaran kamu dari halaman katalog</div><a href="index.php" class="btn-primary" style="display:inline-block;margin-top:20px">Jelajahi HP</a></div>
  <?php else: ?>
  <div class="devices-grid">
    <?php foreach ($wishlist as $d): ?>
    <div class="device-card">
      <div class="card-image-wrap">
        <img class="card-image" src="<?= htmlspecialchars($d['image'] ?? '') ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/200x200/21262d/666'">
        <button class="btn-wishlist active" onclick="removeWishlist(<?= $d['id'] ?>, this)" style="position:absolute;top:12px;right:12px">♥</button>
      </div>
      <div class="card-body">
        <div class="card-brand"><?= htmlspecialchars($d['brand']) ?></div>
        <div class="card-name"><?= htmlspecialchars($d['name']) ?></div>
        <div class="card-footer">
          <div class="card-price"><?= $d['price_fmt'] ?></div>
          <div style="display:flex;gap:6px">
            <a href="device.php?slug=<?= $d['slug'] ?>" class="btn-compare" style="background:var(--bg3);color:var(--text2);border-color:var(--border)">Detail</a>
            <button class="btn-compare" onclick="App.addToCompare(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>', '<?= addslashes($d['image'] ?? '') ?>')">+ Bandingkan</button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Saved Comparisons Tab -->
  <?php elseif ($tab === 'comparisons'): ?>
  <?php if (empty($saved_comps)): ?>
    <div style="text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">⚖️</div><div style="font-size:16px;font-weight:600">Belum ada perbandingan tersimpan</div><a href="compare.php" class="btn-primary" style="display:inline-block;margin-top:20px">Mulai Bandingkan</a></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($saved_comps as $c): ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="flex:1;min-width:200px">
        <div style="font-size:15px;font-weight:700"><?= htmlspecialchars($c['label'] ?: $c['d1_name'] . ' vs ' . $c['d2_name']) ?></div>
        <div style="font-size:12px;color:var(--text3);margin-top:3px"><?= date('d M Y H:i', strtotime($c['created_at'])) ?></div>
      </div>
      <div style="display:flex;gap:10px">
        <a href="compare.php?d1=<?= $c['device1_id'] ?>&d2=<?= $c['device2_id'] ?>" class="btn-compare">Lihat Ulang</a>
        <a href="compare.php?token=<?= $c['share_token'] ?>" class="btn-compare" style="background:var(--bg3);color:var(--text2);border-color:var(--border)">🔗 Link Share</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Price Alerts Tab -->
  <?php elseif ($tab === 'alerts'): ?>
  <?php if (empty($alerts)): ?>
    <div style="text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">🔔</div><div style="font-size:16px;font-weight:600">Belum ada alert harga aktif</div><div style="font-size:14px;margin-top:8px">Buka halaman HP dan klik "Alert Harga"</div></div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($alerts as $a): ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <img src="<?= htmlspecialchars($a['image'] ?? '') ?>" style="width:52px;height:52px;object-fit:contain;background:var(--bg3);border-radius:8px;padding:4px" onerror="this.src='https://via.placeholder.com/52x52'">
      <div style="flex:1;min-width:160px">
        <div style="font-size:14px;font-weight:700"><?= htmlspecialchars($a['device_name']) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:4px">Harga saat ini: <strong><?= $a['current_price_fmt'] ?></strong></div>
        <div style="font-size:12px;color:var(--amber);margin-top:2px">Target alert: <strong><?= $a['target_price_fmt'] ?></strong></div>
      </div>
      <div>
        <?php if ($a['is_triggered']): ?>
        <span style="padding:5px 12px;border-radius:6px;background:rgba(63,185,80,0.1);color:var(--green);font-size:12px;font-weight:700">✓ Alert Terpicu!</span>
        <?php else: ?>
        <span style="padding:5px 12px;border-radius:6px;background:var(--bg3);color:var(--text3);font-size:12px;font-weight:700">🔔 Aktif</span>
        <?php endif; ?>
      </div>
      <a href="device.php?slug=<?= $a['slug'] ?>" class="btn-compare">Lihat HP</a>
      <button onclick="deleteAlert(<?= $a['device_id'] ?>, this)" style="padding:6px 12px;border-radius:7px;background:transparent;border:1px solid var(--border2);color:var(--text3);font-size:12px;cursor:pointer" title="Hapus alert">✕</button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Orders Tab -->
  <?php elseif ($tab === 'orders'): ?>
  <?php if (empty($orders)): ?>
    <div style="text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">📦</div><div style="font-size:16px;font-weight:600">Belum ada transaksi</div></div>
  <?php else: ?>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;background:var(--bg2);border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border)">
      <thead><tr style="background:var(--bg3)">
        <th style="padding:12px 16px;text-align:left;font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Produk</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Total</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Status</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Resi</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Tanggal</th>
      </tr></thead>
      <tbody>
      <?php foreach ($orders as $o):
        $status_colors = ['pending'=>'amber','paid'=>'blue','shipped'=>'purple','delivered'=>'green','cancelled'=>'red'];
        $sc = $status_colors[$o['status']] ?? 'gray';
        $status_labels = ['pending'=>'Menunggu','paid'=>'Dibayar','shipped'=>'Dikirim','delivered'=>'Diterima','cancelled'=>'Dibatalkan'];
      ?>
      <tr style="border-top:1px solid var(--border)">
        <td style="padding:14px 16px">
          <div style="display:flex;align-items:center;gap:10px">
            <img src="<?= htmlspecialchars($o['image'] ?? '') ?>" style="width:40px;height:40px;object-fit:contain;background:var(--bg3);border-radius:6px;padding:3px" onerror="this.src='https://via.placeholder.com/40x40'">
            <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($o['device_name']) ?></div>
          </div>
        </td>
        <td style="padding:14px 16px;font-size:14px;font-weight:700"><?= $o['amount_fmt'] ?></td>
        <td style="padding:14px 16px">
          <span style="padding:4px 10px;border-radius:5px;font-size:12px;font-weight:700;background:rgba(0,0,0,0.1);color:var(--<?= $sc ?>)"><?= $status_labels[$o['status']] ?></span>
        </td>
        <td style="padding:14px 16px;font-size:13px;font-family:var(--mono);color:var(--text2)"><?= $o['tracking_number'] ? htmlspecialchars($o['tracking_number']) : '—' ?></td>
        <td style="padding:14px 16px;font-size:12px;color:var(--text3)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>

<script>
async function removeWishlist(deviceId, btn) {
  const fd = new FormData();
  fd.append('action', 'wishlist_toggle');
  fd.append('device_id', deviceId);
  const res = await fetch('api.php', { method:'POST', body: fd });
  const data = await res.json();
  if (data.status === 'removed') {
    const card = btn.closest('.device-card');
    card.style.opacity = '0';
    card.style.transition = 'opacity 0.3s';
    setTimeout(() => { card.remove(); }, 300);
    App.toast('Dihapus dari wishlist');
    // Update counter
    const counter = document.querySelector('[data-counter="wishlist"]');
    if (counter) counter.textContent = Math.max(0, parseInt(counter.textContent) - 1);
  }
}

async function deleteAlert(deviceId, btn) {
  if (!confirm('Hapus alert harga ini?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_price_alert');
  fd.append('device_id', deviceId);
  const res = await fetch('api.php', { method:'POST', body: fd });
  const data = await res.json();
  if (data.status === 'deleted') {
    const row = btn.closest('div[style*="background:var(--bg2)"]');
    if (row) { row.style.opacity = '0'; row.style.transition = 'opacity 0.3s'; setTimeout(() => row.remove(), 300); }
    App.toast('Alert dihapus');
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
