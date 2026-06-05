<?php
// orders.php — Riwayat Pesanan User
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php?redirect=orders.php'); exit; }

$page_title = 'Pesanan Saya';
$conn = getDB();
$user_id = intval($_SESSION['user_id']);

$orders = mysqli_query($conn, "SELECT o.*, d.name as device_name, d.image as device_image, d.slug as device_slug, d.brand as device_brand
    FROM orders o
    JOIN devices d ON o.device_id = d.id
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC");

$status_labels = ['pending'=>'Menunggu Pembayaran','paid'=>'Sudah Dibayar','shipped'=>'Sedang Dikirim','delivered'=>'Sudah Diterima','cancelled'=>'Dibatalkan'];
$status_colors = ['pending'=>'#d29922','paid'=>'#3fb950','shipped'=>'#00d4ff','delivered'=>'#a855f7','cancelled'=>'#f85149'];
$pay_labels = ['va_bca'=>'VA BCA','va_bni'=>'VA BNI','va_mandiri'=>'VA Mandiri','va_bri'=>'VA BRI','qris'=>'QRIS','cc'=>'Kartu Kredit'];

require_once 'includes/header.php';
?>

<div style="max-width:840px;margin:0 auto;padding:32px 24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:24px;font-weight:800">📦 Pesanan Saya</h1>
      <p style="font-size:13px;color:var(--text3);margin-top:4px">Riwayat semua transaksi kamu di SpecSync</p>
    </div>
    <a href="catalog.php" class="btn-primary">+ Beli HP Baru</a>
  </div>

  <?php
  $rows = [];
  while ($o = mysqli_fetch_assoc($orders)) $rows[] = $o;
  ?>

  <?php if (empty($rows)): ?>
  <div style="text-align:center;padding:80px 24px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg)">
    <div style="font-size:48px;margin-bottom:16px">📱</div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Belum ada pesanan</div>
    <p style="font-size:14px;color:var(--text3);margin-bottom:24px">Temukan HP impianmu dan mulai berbelanja!</p>
    <a href="catalog.php" class="btn-primary">Jelajahi Katalog →</a>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($rows as $o):
      $status = $o['status'];
      $color = $status_colors[$status] ?? '#8b949e';
      $label = $status_labels[$status] ?? $status;
      $pay_method = $pay_labels[$o['payment_channel'] ?? ''] ?? ($o['payment_channel'] ?: '—');
      $has_discount = !empty($o['discount_amount']) && $o['discount_amount'] > 0;
    ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
      <!-- Order Header -->
      <div style="padding:12px 20px;background:var(--bg3);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <span style="font-family:monospace;font-size:13px;color:var(--text3)">Order #<?= $o['id'] ?></span>
          <span style="font-size:12px;color:var(--text3)"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></span>
          <span style="font-size:12px;color:var(--text2)"><?= $pay_method ?></span>
        </div>
        <span style="display:inline-block;padding:4px 12px;border-radius:6px;font-size:12px;font-weight:700;border:1px solid;border-color:<?= $color ?>33;color:<?= $color ?>;background:<?= $color ?>15"><?= $label ?></span>
      </div>

      <!-- Order Body -->
      <div style="padding:16px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
        <a href="device.php?slug=<?= $o['device_slug'] ?>">
          <img src="<?= htmlspecialchars($o['device_image'] ?? '') ?>" style="width:72px;height:72px;object-fit:contain;background:var(--bg3);border-radius:10px;padding:6px" onerror="this.src='https://via.placeholder.com/72'">
        </a>
        <div style="flex:1;min-width:180px">
          <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase"><?= htmlspecialchars($o['device_brand'] ?? '') ?></div>
          <a href="device.php?slug=<?= $o['device_slug'] ?>" style="font-size:16px;font-weight:700;color:var(--text);display:block;margin-bottom:4px"><?= htmlspecialchars($o['device_name']) ?></a>
          <?php if ($o['shipping_city']): ?>
          <div style="font-size:12px;color:var(--text3)">📍 <?= htmlspecialchars($o['shipping_city']) ?></div>
          <?php endif; ?>
          <?php if ($o['tracking_number']): ?>
          <div style="font-size:12px;color:var(--accent);margin-top:4px">🚚 Resi: <span style="font-family:monospace"><?= htmlspecialchars($o['tracking_number']) ?></span></div>
          <?php endif; ?>
        </div>

        <!-- Price block -->
        <div style="text-align:right">
          <?php if ($has_discount && !empty($o['original_amount'])): ?>
          <div style="font-size:12px;color:var(--text3);text-decoration:line-through"><?= formatPrice($o['original_amount']) ?></div>
          <div style="font-size:12px;color:var(--green)">Hemat <?= formatPrice($o['discount_amount']) ?></div>
          <?php endif; ?>
          <div style="font-size:20px;font-weight:800;color:var(--text)"><?= formatPrice($o['amount']) ?></div>
          <?php if ($status === 'pending'): ?>
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;flex-wrap:wrap">
            <a href="checkout.php?device_id=<?= $o['device_id'] ?>&order_id=<?= $o['id'] ?>"
               style="padding:8px 18px;background:linear-gradient(135deg,var(--accent),var(--purple));color:#000;font-size:13px;font-weight:800;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
              💳 Checkout
            </a>
            <button onclick="cancelOrder(<?= $o['id'] ?>, this)"
               style="padding:8px 14px;background:transparent;border:1px solid rgba(248,81,73,.3);color:#f85149;font-size:13px;font-weight:600;border-radius:8px;cursor:pointer">
              Batalkan
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Payment code (if pending with payment code) -->
      <?php if ($status === 'pending' && !empty($o['payment_code'])): ?>
      <div style="margin:0 20px 16px;padding:12px 16px;background:rgba(210,153,34,.06);border:1px solid rgba(210,153,34,.2);border-radius:10px">
        <div style="font-size:12px;color:var(--amber);font-weight:600;margin-bottom:6px">⏳ Selesaikan Pembayaran</div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <div style="font-size:11px;color:var(--text3)">Kode <?= $pay_method ?>:</div>
          <div style="font-family:monospace;font-size:14px;font-weight:800;color:var(--text);letter-spacing:1px"><?= htmlspecialchars($o['payment_code']) ?></div>
          <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($o['payment_code']) ?>');this.textContent='✓';setTimeout(()=>this.textContent='Salin',2000)" style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);background:transparent;color:var(--text2);font-size:12px;cursor:pointer">Salin</button>
        </div>
        <?php if ($o['payment_expired_at']): ?>
        <div style="font-size:11px;color:var(--text3);margin-top:6px">Berlaku s/d <?= date('d M Y H:i', strtotime($o['payment_expired_at'])) ?></div>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--text3);margin-top:4px;padding:6px 10px;background:var(--bg3);border-radius:6px;margin-top:8px">⚠️ Ini adalah simulasi pembayaran. Tidak ada transaksi nyata yang dilakukan.</div>
      </div>
      <?php elseif ($status === 'pending'): ?>
      <div style="margin:0 20px 16px;padding:10px 16px;background:rgba(0,212,255,.05);border:1px solid rgba(0,212,255,.15);border-radius:10px;font-size:12px;color:var(--text3)">
        🛒 Pesanan menunggu checkout. Klik <strong style="color:var(--accent)">Checkout</strong> untuk memilih metode pembayaran.
      </div>
      <?php endif; ?>

    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
async function cancelOrder(orderId, btn) {
  if (!confirm('Batalkan pesanan ini?')) return;
  btn.disabled = true;
  btn.textContent = '...';
  try {
    const fd = new FormData();
    fd.append('action', 'cancel_order');
    fd.append('order_id', orderId);
    const res = await fetch('api.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.status === 'ok') {
      location.reload();
    } else {
      alert(data.error || 'Gagal membatalkan');
      btn.disabled = false;
      btn.textContent = 'Batalkan';
    }
  } catch(e) {
    alert('Gagal menghubungi server');
    btn.disabled = false;
    btn.textContent = 'Batalkan';
  }
}
</script>
