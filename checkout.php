<?php
// checkout.php — FIXED
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php?redirect='.urlencode($_SERVER['REQUEST_URI'])); exit; }

$device_id = intval($_GET['device_id'] ?? $_POST['device_id'] ?? 0);
if (!$device_id) { header('Location: catalog.php'); exit; }

$conn = getDB();
$user_id = intval($_SESSION['user_id']);

$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM devices WHERE id=$device_id AND is_active=1"));
if (!$d) { header('Location: catalog.php'); exit; }
$d['price_fmt'] = formatPrice($d['price']);

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));

// ── Handle promo code check ────────────────────────────────
$promo = null;
$promo_error = '';
$discount_amount = 0;
$applied_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_promo'])) {
    $code = strtoupper(sanitize($_POST['promo_code'] ?? ''));
    if ($code) {
        $res = mysqli_query($conn, "SELECT * FROM promos WHERE promo_code='$code' AND is_active=1 AND start_date<=CURDATE() AND end_date>=CURDATE() AND (usage_limit IS NULL OR used_count < usage_limit) AND (category='all' OR category='{$d['category']}') LIMIT 1");
        $promo = mysqli_fetch_assoc($res);
        if (!$promo) {
            $promo_error = 'Kode promo tidak valid, sudah kedaluwarsa, atau tidak berlaku untuk HP ini.';
        } elseif ($d['price'] < $promo['min_purchase']) {
            $promo_error = 'Harga HP di bawah minimum pembelian untuk promo ini (min. '.formatPrice($promo['min_purchase']).')';
            $promo = null;
        } else {
            $applied_code = $code;
        }
    }
}

if ($promo) {
    if ($promo['discount_type'] === 'percent') {
        $discount_amount = $d['price'] * ($promo['discount_value'] / 100);
        if ($promo['max_discount'] && $discount_amount > $promo['max_discount']) {
            $discount_amount = $promo['max_discount'];
        }
    } else {
        $discount_amount = $promo['discount_value'];
    }
    $discount_amount = min($discount_amount, $d['price']);
}

$final_price = $d['price'] - $discount_amount;

$page_title = 'Checkout — ' . $d['name'];
require_once 'includes/header.php';
?>

<div style="max-width:900px;margin:0 auto;padding:32px 24px">
  <nav style="font-size:13px;color:var(--text3);margin-bottom:24px">
    <a href="index.php" style="color:var(--text3)">Beranda</a> /
    <a href="device.php?slug=<?= $d['slug'] ?>" style="color:var(--text3)"><?= htmlspecialchars($d['name']) ?></a> /
    <span>Checkout</span>
  </nav>

  <h1 style="font-size:24px;font-weight:800;margin-bottom:28px">Checkout</h1>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:24px" class="checkout-grid">

    <!-- LEFT: Combined single form -->
    <div>

    <!-- Product summary -->
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">Produk</div>
      <div style="display:flex;gap:14px;align-items:center">
        <img src="<?= htmlspecialchars($d['image'] ?? '') ?>" style="width:72px;height:72px;object-fit:contain;background:var(--bg3);border-radius:10px;padding:6px" onerror="this.src='https://via.placeholder.com/72'">
        <div style="flex:1">
          <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase"><?= htmlspecialchars($d['brand']) ?></div>
          <div style="font-size:16px;font-weight:700"><?= htmlspecialchars($d['name']) ?></div>
          <div style="font-size:15px;font-weight:800;color:var(--accent);margin-top:2px"><?= $d['price_fmt'] ?></div>
        </div>
      </div>
    </div>

    <!-- Promo code — inside main form now, uses JS to submit via fetch, no page reload -->
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">🎟️ Kode Promo</div>
      <div style="display:flex;gap:10px">
        <input type="text" id="promo-input" value="<?= htmlspecialchars($applied_code) ?>" placeholder="Masukkan kode promo"
          style="flex:1;padding:10px 14px;background:var(--bg3);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:14px;font-family:inherit"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'" oninput="this.value=this.value.toUpperCase()">
        <button type="button" onclick="applyPromo()" class="btn-primary" style="padding:10px 18px;font-size:13px" id="promo-btn">Pakai</button>
      </div>
      <div id="promo-msg" style="margin-top:8px;font-size:13px">
        <?php if ($promo_error): ?>
        <span style="color:var(--red)">⚠️ <?= htmlspecialchars($promo_error) ?></span>
        <?php elseif ($promo): ?>
        <span style="color:var(--green)">✓ Promo <strong><?= htmlspecialchars($promo['promo_code']) ?></strong> berhasil! Hemat <?= formatPrice($discount_amount) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- SINGLE CHECKOUT FORM -->
    <form method="POST" action="payment.php" id="checkout-form">
      <input type="hidden" name="device_id" value="<?= $device_id ?>">
      <input type="hidden" name="promo_id" id="f-promo-id" value="<?= $promo['id'] ?? '' ?>">
      <input type="hidden" name="discount_amount" id="f-discount" value="<?= $discount_amount ?>">
      <input type="hidden" name="final_price" id="f-final" value="<?= $final_price ?>">
      <input type="hidden" name="promo_code" id="f-promo-code" value="<?= htmlspecialchars($applied_code) ?>">

      <!-- Shipping Info -->
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px">
        <div style="font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">📦 Informasi Pengiriman</div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
              <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:5px">Nama Penerima *</label>
              <input type="text" name="shipping_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                style="width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
            </div>
            <div>
              <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:5px">No. HP *</label>
              <input type="tel" name="shipping_phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx" required
                style="width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
            </div>
          </div>
          <div>
            <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:5px">Kota *</label>
            <input type="text" name="shipping_city" placeholder="Jakarta Selatan" required
              style="width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
          </div>
          <div>
            <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:5px">Alamat Lengkap *</label>
            <textarea name="shipping_address" rows="3" placeholder="Jl. Contoh No. 1, Kecamatan, RT/RW" required
              style="width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit;resize:vertical" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'"></textarea>
          </div>
          <div>
            <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:5px">Catatan (opsional)</label>
            <input type="text" name="notes" placeholder="Warna, varian, atau instruksi khusus..."
              style="width:100%;padding:9px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:inherit" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
          </div>
        </div>
      </div>

      <!-- Payment Method -->
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px">
        <div style="font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">💳 Metode Pembayaran</div>
        <div style="display:flex;flex-direction:column;gap:8px" id="payment-methods">
          <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-top:4px">Transfer Virtual Account</div>
          <?php
          $methods = [
            ['va_bca',    '🏦', 'Virtual Account BCA',    'Bayar via ATM/Mobile Banking BCA'],
            ['va_bni',    '🏦', 'Virtual Account BNI',    'Bayar via ATM/Mobile Banking BNI'],
            ['va_mandiri','🏦', 'Virtual Account Mandiri','Bayar via ATM/Mobile Banking Mandiri'],
            ['va_bri',    '🏦', 'Virtual Account BRI',    'Bayar via ATM/Mobile Banking BRI'],
          ];
          foreach ($methods as [$val,$icon,$label,$desc]):
          ?>
          <label class="pay-method" data-val="<?= $val ?>">
            <input type="radio" name="payment_channel" value="<?= $val ?>" style="display:none">
            <span style="font-size:20px"><?= $icon ?></span>
            <div style="flex:1">
              <div style="font-size:14px;font-weight:600"><?= $label ?></div>
              <div style="font-size:12px;color:var(--text3)"><?= $desc ?></div>
            </div>
            <span class="pay-check">✓</span>
          </label>
          <?php endforeach; ?>
          <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-top:8px">Pembayaran Lainnya</div>
          <label class="pay-method" data-val="qris">
            <input type="radio" name="payment_channel" value="qris" style="display:none">
            <span style="font-size:20px">📱</span>
            <div style="flex:1">
              <div style="font-size:14px;font-weight:600">QRIS</div>
              <div style="font-size:12px;color:var(--text3)">Scan QR dengan semua e-wallet & mobile banking</div>
            </div>
            <span class="pay-check">✓</span>
          </label>
          <label class="pay-method" data-val="cc">
            <input type="radio" name="payment_channel" value="cc" style="display:none">
            <span style="font-size:20px">💳</span>
            <div style="flex:1">
              <div style="font-size:14px;font-weight:600">Kartu Kredit / Debit</div>
              <div style="font-size:12px;color:var(--text3)">Visa, Mastercard, JCB</div>
            </div>
            <span class="pay-check">✓</span>
          </label>
        </div>
        <!-- CC form -->
        <div id="cc-form" style="display:none;margin-top:16px;padding:16px;background:var(--bg3);border-radius:10px">
          <div style="display:grid;grid-template-columns:1fr;gap:10px">
            <div>
              <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:4px">Nomor Kartu</label>
              <input type="text" id="cc-number" placeholder="0000 0000 0000 0000" maxlength="19"
                style="width:100%;padding:9px 12px;background:var(--bg2);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:monospace;letter-spacing:2px" oninput="formatCardNumber(this)">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
              <div>
                <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:4px">Masa Berlaku</label>
                <input type="text" id="cc-expiry" placeholder="MM/YY" maxlength="5"
                  style="width:100%;padding:9px 12px;background:var(--bg2);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:monospace" oninput="formatExpiry(this)">
              </div>
              <div>
                <label style="font-size:12px;font-weight:600;color:var(--text3);display:block;margin-bottom:4px">CVV</label>
                <input type="text" id="cc-cvv" placeholder="•••" maxlength="4"
                  style="width:100%;padding:9px 12px;background:var(--bg2);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;font-family:monospace">
              </div>
            </div>
          </div>
          <div style="margin-top:10px;font-size:11px;color:var(--text3)">🔐 Ini hanya simulasi — tidak ada data kartu yang tersimpan</div>
        </div>
        <!-- Validation message -->
        <div id="pay-method-msg" style="margin-top:10px;font-size:13px;color:var(--red);display:none">⚠️ Pilih metode pembayaran terlebih dahulu.</div>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;padding:14px;font-size:16px;border-radius:12px;margin-bottom:12px" id="pay-btn" onclick="return validateCheckout()">
        Lanjut ke Pembayaran →
      </button>
      <div style="text-align:center;font-size:12px;color:var(--text3)">🔒 Simulasi pembayaran — tidak ada transaksi nyata</div>
    </form>

    </div><!-- /left -->

    <!-- RIGHT: Order Summary -->
    <div>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;position:sticky;top:88px">
        <div style="font-size:14px;font-weight:700;margin-bottom:16px">Ringkasan Pesanan</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
          <div style="display:flex;justify-content:space-between;font-size:14px">
            <span style="color:var(--text2)"><?= htmlspecialchars($d['name']) ?></span>
            <span><?= $d['price_fmt'] ?></span>
          </div>
          <div id="summary-discount-row" style="display:<?= $discount_amount > 0 ? 'flex' : 'none' ?>;justify-content:space-between;font-size:14px;color:var(--green)">
            <span>Diskon (<span id="summary-code"><?= htmlspecialchars($applied_code) ?></span>)</span>
            <span>− <span id="summary-discount-val"><?= formatPrice($discount_amount) ?></span></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text3)">
            <span>Pengiriman</span>
            <span style="color:var(--green)">Gratis</span>
          </div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:14px;display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:15px;font-weight:700">Total</span>
          <span style="font-size:22px;font-weight:800;color:var(--accent)" id="summary-total"><?= formatPrice($final_price) ?></span>
        </div>

        <?php
        $avail_promos = mysqli_query($conn, "SELECT title, promo_code, discount_type, discount_value FROM promos WHERE is_active=1 AND start_date<=CURDATE() AND end_date>=CURDATE() AND (category='all' OR category='{$d['category']}') AND min_purchase<={$d['price']} AND (usage_limit IS NULL OR used_count < usage_limit) LIMIT 3");
        $promos_arr = [];
        while ($ap = mysqli_fetch_assoc($avail_promos)) $promos_arr[] = $ap;
        if ($promos_arr && !$promo):
        ?>
        <div style="margin-top:14px">
          <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Promo Tersedia</div>
          <?php foreach ($promos_arr as $ap):
            $disc_str = $ap['discount_type'] === 'percent' ? $ap['discount_value'].'%' : formatPrice($ap['discount_value']);
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--bg3);border-radius:7px;margin-bottom:6px;cursor:pointer" onclick="fillPromo('<?= htmlspecialchars($ap['promo_code']) ?>')">
            <div>
              <code style="font-size:12px;font-weight:700;color:var(--accent)"><?= htmlspecialchars($ap['promo_code']) ?></code>
              <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($ap['title']) ?></div>
            </div>
            <span style="font-size:13px;font-weight:700;color:var(--green)">-<?= $disc_str ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /grid -->
</div>

<style>
.pay-method{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--border2);border-radius:10px;cursor:pointer;transition:all .15s}
.pay-method:hover{border-color:var(--accent);background:var(--bg3)}
.pay-method.selected{border-color:var(--accent);background:rgba(0,212,255,.05)}
.pay-check{font-size:14px;color:var(--accent);display:none;font-weight:800}
.pay-method.selected .pay-check{display:block}
@media(max-width:700px){.checkout-grid{grid-template-columns:1fr !important}}
</style>

<script>
const DEVICE_ID = <?= $device_id ?>;
const DEVICE_PRICE = <?= $d['price'] ?>;

// Payment method selection
document.querySelectorAll('.pay-method').forEach(m => {
  m.addEventListener('click', function() {
    document.querySelectorAll('.pay-method').forEach(x => x.classList.remove('selected'));
    this.classList.add('selected');
    this.querySelector('input[type="radio"]').checked = true;
    document.getElementById('cc-form').style.display = this.dataset.val === 'cc' ? 'block' : 'none';
    document.getElementById('pay-method-msg').style.display = 'none';
  });
});

function validateCheckout() {
  const selected = document.querySelector('input[name="payment_channel"]:checked');
  if (!selected) {
    document.getElementById('pay-method-msg').style.display = 'block';
    document.getElementById('payment-methods').scrollIntoView({behavior:'smooth'});
    return false;
  }
  return true;
}

function fillPromo(code) {
  document.getElementById('promo-input').value = code;
  applyPromo();
}

async function applyPromo() {
  const code = document.getElementById('promo-input').value.trim();
  if (!code) return;
  const btn = document.getElementById('promo-btn');
  btn.textContent = '...';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('device_id', DEVICE_ID);
    fd.append('promo_code', code);
    fd.append('check_promo', '1');
    const res = await fetch('checkout.php?device_id='+DEVICE_ID, {method:'POST',body:fd});
    const html = await res.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    // Extract promo result from response
    const msgEl = doc.getElementById('promo-msg');
    const newMsg = msgEl ? msgEl.innerHTML : '';
    document.getElementById('promo-msg').innerHTML = newMsg;

    // Update hidden fields from parsed page
    const fPromoId = doc.getElementById('f-promo-id');
    const fDiscount = doc.getElementById('f-discount');
    const fFinal = doc.getElementById('f-final');
    const fCode = doc.getElementById('f-promo-code');
    const summaryTotal = doc.getElementById('summary-total');
    const summaryDiscount = doc.getElementById('summary-discount-row');
    const summaryCode = doc.getElementById('summary-code');
    const summaryVal = doc.getElementById('summary-discount-val');

    if (fPromoId) document.getElementById('f-promo-id').value = fPromoId.value;
    if (fDiscount) document.getElementById('f-discount').value = fDiscount.value;
    if (fFinal) {
      document.getElementById('f-final').value = fFinal.value;
    }
    if (fCode) document.getElementById('f-promo-code').value = fCode.value;
    if (summaryTotal) document.getElementById('summary-total').textContent = summaryTotal.textContent;
    if (summaryDiscount) document.getElementById('summary-discount-row').style.display = summaryDiscount.style.display;
    if (summaryCode) document.getElementById('summary-code').textContent = summaryCode.textContent;
    if (summaryVal) document.getElementById('summary-discount-val').textContent = summaryVal.textContent;
  } catch(e) {
    document.getElementById('promo-msg').innerHTML = '<span style="color:var(--red)">⚠️ Gagal mengecek promo.</span>';
  }
  btn.textContent = 'Pakai';
  btn.disabled = false;
}

function formatCardNumber(el) {
  let v = el.value.replace(/\D/g,'').substring(0,16);
  el.value = v.replace(/(.{4})/g,'$1 ').trim();
}
function formatExpiry(el) {
  let v = el.value.replace(/\D/g,'');
  if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
  el.value = v;
}
</script>

<?php require_once 'includes/footer.php'; ?>
