<?php
// payment.php — Simulasi halaman pembayaran
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: catalog.php'); exit; }

$conn = getDB();
$user_id    = intval($_SESSION['user_id']);
$device_id  = intval($_POST['device_id'] ?? 0);
$promo_id   = intval($_POST['promo_id'] ?? 0) ?: null;
$discount   = floatval($_POST['discount_amount'] ?? 0);
$final      = floatval($_POST['final_price'] ?? 0);
$channel    = sanitize($_POST['payment_channel'] ?? '');

// Validate device
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM devices WHERE id=$device_id AND is_active=1"));
if (!$d || !$channel) { header('Location: catalog.php'); exit; }

$valid_channels = ['va_bca','va_bni','va_mandiri','va_bri','qris','cc'];
if (!in_array($channel, $valid_channels)) { header('Location: catalog.php'); exit; }

// Sanitize shipping info
$sh_name    = sanitize($_POST['shipping_name'] ?? '');
$sh_phone   = sanitize($_POST['shipping_phone'] ?? '');
$sh_city    = sanitize($_POST['shipping_city'] ?? '');
$sh_address = sanitize($_POST['shipping_address'] ?? '');
$notes      = sanitize($_POST['notes'] ?? '');

$original   = $d['price'];
if ($final <= 0 || $final > $original) $final = $original;

// Generate payment code
function genVA($bank) {
    $prefix = ['va_bca'=>'8277','va_bni'=>'8899','va_mandiri'=>'8908','va_bri'=>'26215'];
    return ($prefix[$bank] ?? '8000') . rand(100000000000, 999999999999);
}
function genQRIS() {
    return 'QRIS' . strtoupper(bin2hex(random_bytes(12)));
}

$payment_code = '';
if (str_starts_with($channel, 'va_')) {
    $payment_code = genVA($channel);
} elseif ($channel === 'qris') {
    $payment_code = genQRIS();
} else {
    $payment_code = 'CC-SIM-' . strtoupper(bin2hex(random_bytes(6)));
}

$expired_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
$promo_str  = $promo_id ? $promo_id : 'NULL';

// Create order
$q = "INSERT INTO orders (user_id, device_id, amount, original_amount, discount_amount, status, payment_method, payment_channel, payment_code, payment_expired_at, shipping_name, shipping_phone, shipping_address, shipping_city, notes, promo_id)
      VALUES ($user_id, $device_id, $final, $original, $discount, 'pending', '$channel', '$channel', '$payment_code', '$expired_at', '$sh_name', '$sh_phone', '$sh_address', '$sh_city', '$notes', $promo_str)";
mysqli_query($conn, $q);
$order_id = mysqli_insert_id($conn);

// Update promo usage count
if ($promo_id) {
    mysqli_query($conn, "UPDATE promos SET used_count = used_count + 1 WHERE id=$promo_id");
}

$page_title = 'Pembayaran — Order #' . $order_id;

$pay_names = ['va_bca'=>'Virtual Account BCA','va_bni'=>'Virtual Account BNI','va_mandiri'=>'Virtual Account Mandiri','va_bri'=>'Virtual Account BRI','qris'=>'QRIS','cc'=>'Kartu Kredit'];
$bank_logos = ['va_bca'=>'🟦','va_bni'=>'🟧','va_mandiri'=>'🟡','va_bri'=>'🟦','qris'=>'📱','cc'=>'💳'];

require_once 'includes/header.php';
?>

<div style="max-width:600px;margin:0 auto;padding:40px 24px;text-align:center">

  <div style="font-size:56px;margin-bottom:16px">
    <?php if ($channel === 'qris'): ?>📱<?php elseif ($channel === 'cc'): ?>💳<?php else: ?>🏦<?php endif; ?>
  </div>

  <div style="display:inline-block;padding:6px 16px;border-radius:8px;background:rgba(210,153,34,.1);border:1px solid rgba(210,153,34,.2);color:var(--amber);font-size:13px;font-weight:700;margin-bottom:16px">
    ⏳ Menunggu Pembayaran
  </div>

  <h1 style="font-size:22px;font-weight:800;margin-bottom:6px">Pesanan #<?= $order_id ?> Dibuat!</h1>
  <p style="color:var(--text2);font-size:14px;margin-bottom:28px">Selesaikan pembayaran sebelum <strong><?= date('d M Y H:i', strtotime($expired_at)) ?></strong></p>

  <!-- Payment Details Box -->
  <div style="background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;text-align:left">

    <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px"><?= $pay_names[$channel] ?></div>

    <?php if (str_starts_with($channel, 'va_')): ?>
    <!-- VA Payment -->
    <div style="text-align:center;padding:16px 0">
      <div style="font-size:13px;color:var(--text2);margin-bottom:8px">Nomor Virtual Account</div>
      <div style="font-size:28px;font-weight:800;letter-spacing:3px;color:var(--accent);background:var(--bg3);padding:16px 20px;border-radius:10px;font-family:monospace" id="va-number"><?= $payment_code ?></div>
      <button onclick="copyVA()" style="margin-top:10px;padding:8px 18px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer" id="copy-btn">📋 Salin Nomor VA</button>
    </div>
    <div style="background:var(--bg3);border-radius:10px;padding:14px;margin-top:12px">
      <div style="font-size:12px;font-weight:700;color:var(--text3);margin-bottom:8px">Cara Bayar:</div>
      <ol style="font-size:13px;color:var(--text2);padding-left:16px;line-height:1.8">
        <li>Buka aplikasi mobile banking atau ATM <?= strtoupper(str_replace('va_','',$channel)) ?></li>
        <li>Pilih menu <strong>Transfer</strong> → <strong>Virtual Account</strong></li>
        <li>Masukkan nomor VA di atas</li>
        <li>Masukkan nominal: <strong><?= formatPrice($final) ?></strong></li>
        <li>Konfirmasi dan selesaikan pembayaran</li>
      </ol>
    </div>

    <?php elseif ($channel === 'qris'): ?>
    <!-- QRIS -->
    <div style="text-align:center;padding:16px 0">
      <div style="font-size:13px;color:var(--text2);margin-bottom:14px">Scan QR Code di bawah ini</div>
      <!-- QR Code visual simulation -->
      <div style="display:inline-block;background:#fff;padding:16px;border-radius:12px;margin-bottom:12px">
        <svg width="160" height="160" viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
          <!-- Simplified QR-like pattern for demo -->
          <rect width="160" height="160" fill="white"/>
          <!-- Corner squares -->
          <rect x="10" y="10" width="50" height="50" fill="none" stroke="#000" stroke-width="6"/>
          <rect x="20" y="20" width="30" height="30" fill="#000"/>
          <rect x="100" y="10" width="50" height="50" fill="none" stroke="#000" stroke-width="6"/>
          <rect x="110" y="20" width="30" height="30" fill="#000"/>
          <rect x="10" y="100" width="50" height="50" fill="none" stroke="#000" stroke-width="6"/>
          <rect x="20" y="110" width="30" height="30" fill="#000"/>
          <!-- Data modules (decorative) -->
          <?php
          srand($order_id);
          for ($i = 0; $i < 80; $i++) {
              $x = rand(0,14)*10+5; $y = rand(0,14)*10+5;
              if ($x < 65 && $y < 65) continue;
              if ($x >= 95 && $y < 65) continue;
              if ($x < 65 && $y >= 95) continue;
              echo "<rect x='$x' y='$y' width='8' height='8' fill='#000'/>";
          }
          ?>
          <!-- Center logo area -->
          <rect x="65" y="65" width="30" height="30" fill="white"/>
          <rect x="68" y="68" width="24" height="24" fill="#00d4ff" rx="4"/>
          <text x="80" y="83" font-family="Arial" font-weight="bold" font-size="10" fill="white" text-anchor="middle">SS</text>
        </svg>
      </div>
      <div style="font-size:12px;color:var(--text3)">Berlaku untuk semua e-wallet & mobile banking</div>
    </div>
    <div style="background:var(--bg3);border-radius:10px;padding:14px">
      <div style="font-size:12px;font-weight:700;color:var(--text3);margin-bottom:8px">Cara Bayar:</div>
      <ol style="font-size:13px;color:var(--text2);padding-left:16px;line-height:1.8">
        <li>Buka GoPay / OVO / Dana / ShopeePay / Mobile Banking</li>
        <li>Pilih <strong>Bayar dengan QR / QRIS</strong></li>
        <li>Scan QR code di atas</li>
        <li>Nominal akan otomatis terisi: <strong><?= formatPrice($final) ?></strong></li>
        <li>Konfirmasi pembayaran</li>
      </ol>
    </div>

    <?php else: ?>
    <!-- Credit Card -->
    <div style="text-align:center;padding:8px 0 16px">
      <div style="font-size:13px;color:var(--text2);margin-bottom:14px">Detail kartu yang kamu masukkan</div>
      <div style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:14px;padding:20px 24px;text-align:left;max-width:320px;margin:0 auto;box-shadow:0 8px 32px rgba(0,0,0,.4)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
          <div style="font-size:14px;font-weight:800;color:#00d4ff">SpecSync Pay</div>
          <div style="font-size:22px">💳</div>
        </div>
        <div style="font-size:16px;font-weight:700;letter-spacing:3px;color:#e6edf3;font-family:monospace;margin-bottom:16px">•••• •••• •••• ••••</div>
        <div style="display:flex;justify-content:space-between">
          <div style="font-size:11px;color:#8b949e">NAMA PEMILIK<br><span style="color:#e6edf3;font-size:13px;font-weight:600"><?= strtoupper(htmlspecialchars($_SESSION['user_name'] ?? 'CARDHOLDER')) ?></span></div>
          <div style="font-size:11px;color:#8b949e;text-align:right">BERLAKU S/D<br><span style="color:#e6edf3;font-size:13px;font-weight:600">••/••</span></div>
        </div>
      </div>
    </div>
    <div style="background:var(--bg3);border-radius:10px;padding:14px;margin-top:4px">
      <div style="font-size:13px;color:var(--green);font-weight:600">✓ Simulasi persetujuan kartu kredit</div>
      <div style="font-size:12px;color:var(--text2);margin-top:4px">Tidak ada data kartu yang diproses. Ini adalah simulasi untuk keperluan demo.</div>
    </div>
    <?php endif; ?>

    <!-- Amount Row -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)">
      <span style="font-size:14px;color:var(--text2)">Total Bayar</span>
      <span style="font-size:22px;font-weight:800;color:var(--accent)"><?= formatPrice($final) ?></span>
    </div>
    <?php if ($discount > 0): ?>
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--green);margin-top:4px">
      <span>Hemat dari promo</span>
      <span>− <?= formatPrice($discount) ?></span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Product summary -->
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;margin-bottom:24px;display:flex;align-items:center;gap:14px;text-align:left">
    <img src="<?= htmlspecialchars($d['image'] ?? '') ?>" style="width:56px;height:56px;object-fit:contain;background:var(--bg3);border-radius:8px;padding:4px" onerror="this.src='https://via.placeholder.com/56'">
    <div>
      <div style="font-size:15px;font-weight:700"><?= htmlspecialchars($d['name']) ?></div>
      <div style="font-size:12px;color:var(--text2)">Dikirim ke: <?= htmlspecialchars($sh_name) ?>, <?= htmlspecialchars($sh_city) ?></div>
    </div>
    <div style="margin-left:auto;text-align:right">
      <div style="font-size:11px;color:var(--text3)">Order ID</div>
      <div style="font-size:14px;font-weight:700;font-family:monospace">#<?= $order_id ?></div>
    </div>
  </div>

  <!-- SIMULATE: Confirm payment button (demo only) -->
  <div style="background:rgba(63,185,80,.06);border:1px dashed rgba(63,185,80,.3);border-radius:var(--radius-lg);padding:20px;margin-bottom:24px">
    <div style="font-size:13px;font-weight:700;color:var(--green);margin-bottom:6px">🧪 Mode Simulasi</div>
    <p style="font-size:13px;color:var(--text2);margin-bottom:14px">Ini adalah ujicoba — tidak ada pembayaran nyata. Klik tombol di bawah untuk mensimulasikan pembayaran berhasil.</p>
    <button onclick="simulatePayment()" class="btn-primary" style="width:100%;padding:13px;font-size:15px;border-radius:10px" id="sim-btn">
      ✅ Simulasikan Pembayaran Berhasil
    </button>
  </div>

  <a href="dashboard.php?tab=orders" style="font-size:14px;color:var(--text2)">Lihat semua pesanan di Dashboard →</a>
</div>

<script>
<?php if (str_starts_with($channel, 'va_')): ?>
function copyVA() {
  const va = document.getElementById('va-number').textContent.trim();
  navigator.clipboard?.writeText(va).then(() => {
    document.getElementById('copy-btn').textContent = '✓ Tersalin!';
    setTimeout(() => document.getElementById('copy-btn').textContent = '📋 Salin Nomor VA', 2000);
  });
}
<?php endif; ?>

async function simulatePayment() {
  const btn = document.getElementById('sim-btn');
  btn.disabled = true;
  btn.textContent = '⏳ Memproses...';
  await new Promise(r => setTimeout(r, 2000));
  const res = await fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=simulate_payment&order_id=<?= $order_id ?>`
  });
  const data = await res.json();
  if (data.success) {
    window.location = 'order_success.php?id=<?= $order_id ?>';
  } else {
    btn.disabled = false;
    btn.textContent = '✅ Simulasikan Pembayaran Berhasil';
    alert(data.error || 'Gagal, coba lagi');
  }
}

// Countdown timer
const expiry = new Date('<?= $expired_at ?>').getTime();
function updateTimer() {
  const now = Date.now();
  const diff = expiry - now;
  if (diff <= 0) { document.getElementById('timer')?.remove(); return; }
  const h = Math.floor(diff / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  const s = Math.floor((diff % 60000) / 1000);
  const el = document.getElementById('timer');
  if (el) el.textContent = `Sisa waktu: ${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
}
setInterval(updateTimer, 1000);
</script>

<?php require_once 'includes/footer.php'; ?>