<?php
session_start();
require_once 'includes/db.php';

$slug = sanitize($_GET['slug'] ?? '');
$id = intval($_GET['id'] ?? 0);
$conn = getDB();

$where = $slug ? "slug='$slug'" : "id=$id";
$result = mysqli_query($conn, "SELECT * FROM devices WHERE $where AND is_active=1");
$device = mysqli_fetch_assoc($result);

if (!$device) { header('Location: index.php'); exit; }
$device['price_fmt'] = formatPrice($device['price']);

// Get reviews
$reviews_res = mysqli_query($conn, "SELECT r.*, u.name as user_name, u.avatar FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.device_id={$device['id']} ORDER BY r.is_verified_buyer DESC, r.created_at DESC LIMIT 10");
$reviews = [];
while ($r = mysqli_fetch_assoc($reviews_res)) $reviews[] = $r;

// Average rating
$avg_res = mysqli_fetch_row(mysqli_query($conn, "SELECT AVG(rating), COUNT(*) FROM reviews WHERE device_id={$device['id']}"));
$avg_rating = $avg_res[0] ? round($avg_res[0], 1) : 0;
$review_count = $avg_res[1];

$page_title = $device['name'];
?>
<?php require_once 'includes/header.php'; ?>

<div style="max-width:1280px;margin:0 auto;padding:24px">

  <!-- Breadcrumb -->
  <nav style="font-size:13px;color:var(--text3);margin-bottom:24px">
    <a href="index.php" style="color:var(--text3)">Beranda</a> / 
    <a href="catalog.php?brand=<?= urlencode($device['brand']) ?>" style="color:var(--text3)"><?= htmlspecialchars($device['brand']) ?></a> / 
    <span style="color:var(--text)"><?= htmlspecialchars($device['name']) ?></span>
  </nav>

  <!-- Device Header -->
  <div style="display:grid;grid-template-columns:1fr 2fr;gap:32px;margin-bottom:40px" class="device-detail-grid">

    <!-- Image column -->
    <div style="position:sticky;top:84px;height:fit-content">
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px;display:flex;align-items:center;justify-content:center;min-height:320px">
        <img src="<?= htmlspecialchars($device['image'] ?? '') ?>" alt="<?= htmlspecialchars($device['name']) ?>" style="max-height:280px;object-fit:contain;width:100%" onerror="this.src='https://via.placeholder.com/280x280/21262d/666?text=<?= urlencode($device['brand']) ?>'">
      </div>

      <!-- Score radar summary -->
      <div style="margin-top:16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px">
        <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:14px">Skor SpecSync</div>
        <?php
        $scores = [
          ['Kamera', $device['score_camera'], '📸'],
          ['Performa', $device['score_performance'], '⚡'],
          ['Baterai', $device['score_battery'], '🔋'],
          ['Desain', $device['score_design'], '✨'],
        ];
        foreach ($scores as [$label, $val, $icon]):
        ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <div style="font-size:14px;width:16px"><?= $icon ?></div>
          <div style="font-size:12px;color:var(--text2);width:72px"><?= $label ?></div>
          <div style="flex:1;height:5px;background:var(--bg3);border-radius:3px;overflow:hidden">
            <div style="height:100%;width:<?= ($val/10)*100 ?>%;background:linear-gradient(90deg,var(--accent),rgba(0,212,255,0.5));border-radius:3px;transition:width 0.8s ease"></div>
          </div>
          <div style="font-size:12px;font-weight:700;color:var(--accent);width:24px;text-align:right"><?= $val ?></div>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--border);margin-top:14px;padding-top:14px;display:flex;justify-content:space-between;align-items:center">
          <div style="font-size:12px;color:var(--text2)">Skor Keseluruhan</div>
          <?php $overall = round(array_sum(array_column($scores, 1)) / 4, 1); ?>
          <div style="font-size:20px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $overall ?>/10</div>
        </div>
      </div>
    </div>

    <!-- Info column -->
    <div>
      <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
        <?php if ($device['network_5g']): ?><span class="badge badge-5g">5G</span><?php endif; ?>
        <?php if ($device['category'] === 'flagship'): ?><span class="badge badge-flagship">Flagship</span><?php endif; ?>
        <span class="badge" style="background:var(--bg3);color:var(--text2);border:1px solid var(--border)"><?= $device['release_year'] ?></span>
      </div>

      <div style="font-size:14px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px"><?= htmlspecialchars($device['brand']) ?></div>
      <h1 style="font-size:clamp(22px,3vw,32px);font-weight:800;line-height:1.2;margin-bottom:16px"><?= htmlspecialchars($device['name']) ?></h1>

      <?php if ($review_count > 0): ?>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
        <div style="display:flex;gap:3px">
          <?php for ($i=1;$i<=5;$i++): ?>
          <span style="font-size:16px;color:<?= $i <= round($avg_rating) ? 'var(--amber)' : 'var(--bg4)' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span style="font-weight:700"><?= $avg_rating ?></span>
        <span style="color:var(--text3);font-size:13px">(<?= $review_count ?> ulasan)</span>
      </div>
      <?php endif; ?>

      <div style="margin-bottom:24px">
        <div style="font-size:13px;color:var(--text3);margin-bottom:4px">Harga Mulai</div>
        <div style="font-size:36px;font-weight:800;letter-spacing:-1px"><?= $device['price_fmt'] ?></div>
      </div>

      <!-- Action buttons -->
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px">
        <button onclick="App.addToCompare(<?= $device['id'] ?>, '<?= addslashes($device['name']) ?>', '<?= addslashes($device['image'] ?? '') ?>')" class="btn-primary" style="padding:12px 24px;font-size:15px">
          ⚖️ Tambah ke Perbandingan
        </button>
<?php
$in_wishlist = false;
if (!empty($_SESSION['user_id'])) {
    $wl_check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id=".intval($_SESSION['user_id'])." AND device_id=".intval($device['id']));
    $in_wishlist = mysqli_num_rows($wl_check) > 0;
}
?>
        <button class="btn-wishlist <?= $in_wishlist ? 'active' : '' ?>" data-wishlist-id="<?= $device['id'] ?>" onclick="App.toggleWishlist(<?= $device['id'] ?>, this)" style="position:static;width:auto;padding:12px 20px;font-size:15px;background:var(--bg2);border:1px solid var(--border2);border-radius:10px;color:var(--text2)">
          <?= $in_wishlist ? '♥' : '♡' ?> Wishlist
        </button>
        <button onclick="setPriceAlert()" style="padding:12px 20px;font-size:14px;background:var(--bg2);border:1px solid var(--border2);border-radius:10px;color:var(--text2);font-weight:600;cursor:pointer">
          🔔 Alert Harga
        </button>
        <button onclick="beliSekarang()" id="btn-beli" class="btn-primary" style="padding:12px 28px;font-size:15px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));color:#000;border-radius:10px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px">
          🛒 Beli Sekarang
        </button>
      </div>

      <!-- Quick specs -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
        <?php
        $quick = [
          ['Layar', $device['screen_size'] . '" ' . ($device['display_type'] ?? ''), '📱'],
          ['Chipset', $device['chipset'], '⚡'],
          ['RAM', $device['ram'] . 'GB', '💾'],
          ['Storage', $device['storage'] . 'GB', '💿'],
          ['Kamera', $device['main_camera'] . 'MP', '📷'],
          ['Baterai', number_format($device['battery']) . ' mAh', '🔋'],
          ['Charge', $device['charging_speed'] . 'W', '⚡'],
          ['Berat', $device['weight'] . 'g', '⚖️'],
        ];
        foreach ($quick as [$label, $val, $icon]):
        ?>
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:12px">
          <div style="font-size:16px;margin-bottom:4px"><?= $icon ?></div>
          <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:0.3px"><?= $label ?></div>
          <div style="font-size:13px;font-weight:700;margin-top:2px;line-height:1.3"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Full Specs Table -->
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:40px">
    <div style="padding:20px 24px;border-bottom:1px solid var(--border)">
      <h2 style="font-size:18px;font-weight:700">Spesifikasi Lengkap</h2>
    </div>
    <table style="width:100%;border-collapse:collapse">
      <?php
      $all_specs = [
        'Layar' => [
          'Ukuran Layar' => $device['screen_size'] . ' inci',
          'Resolusi' => $device['resolution'],
          'Refresh Rate' => $device['refresh_rate'] . ' Hz',
          'Tipe Panel' => $device['display_type'],
        ],
        'Performa' => [
          'Chipset' => $device['chipset'],
          'CPU Cores' => $device['cpu_cores'] . ' core',
          'GPU' => $device['gpu'],
          'RAM' => $device['ram'] . ' GB',
          'Storage' => $device['storage'] . ' GB',
        ],
        'Kamera' => [
          'Kamera Utama' => $device['main_camera'] . ' MP',
          'Kamera Selfie' => $device['front_camera'] . ' MP',
          'Fitur Kamera' => $device['camera_features'],
        ],
        'Baterai' => [
          'Kapasitas' => number_format($device['battery']) . ' mAh',
          'Kecepatan Charge' => $device['charging_speed'] . ' W',
          'Wireless Charging' => $device['has_wireless_charging'] ? 'Ya' : 'Tidak',
        ],
        'Desain' => [
          'Dimensi' => $device['width'] . ' × ' . $device['height'] . ' × ' . $device['thickness'] . ' mm',
          'Berat' => $device['weight'] . ' gram',
          'Warna' => $device['color_options'],
        ],
        'Konektivitas' => [
          '5G' => $device['network_5g'] ? 'Ya' : 'Tidak',
          'WiFi' => $device['wifi_version'],
          'Bluetooth' => $device['bluetooth_version'],
          'NFC' => $device['nfc'] ? 'Ya' : 'Tidak',
        ],
        'Software' => [
          'OS' => $device['os'] . ' ' . $device['os_version'],
        ],
      ];
      $alt = false;
      foreach ($all_specs as $group => $specs):
      ?>
      <tr><td colspan="2" style="padding:10px 24px;font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;background:var(--bg);border-top:1px solid var(--border)"><?= $group ?></td></tr>
      <?php foreach ($specs as $label => $val): if (!$val) continue; $alt = !$alt; ?>
      <tr style="background:<?= $alt ? 'var(--bg2)' : 'transparent' ?>">
        <td style="padding:12px 24px;font-size:13px;color:var(--text2);width:40%;border-bottom:1px solid var(--border)"><?= $label ?></td>
        <td style="padding:12px 24px;font-size:14px;font-weight:600;border-bottom:1px solid var(--border)"><?= htmlspecialchars($val) ?></td>
      </tr>
      <?php endforeach; endforeach; ?>
    </table>
  </div>

  <!-- Reviews -->
  <div style="margin-bottom:40px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h2 style="font-size:20px;font-weight:700">Ulasan Pengguna</h2>
      <?php if (!empty($_SESSION['user_id'])): ?>
      <button onclick="toggleReviewForm()" class="btn-primary" style="font-size:13px;padding:8px 18px">Tulis Ulasan</button>
      <?php else: ?>
      <a href="login.php" style="font-size:13px;color:var(--accent)">Login untuk ulasan →</a>
      <?php endif; ?>
    </div>

    <!-- Review form (hidden by default) -->
    <div id="review-form" style="display:none;background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px">
      <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">Tulis Ulasan untuk <?= htmlspecialchars($device['name']) ?></h3>
      <div style="margin-bottom:16px">
        <div style="font-size:13px;color:var(--text2);margin-bottom:8px">Rating:</div>
        <div id="star-rating" style="display:flex;gap:6px;font-size:28px">
          <?php for ($i=1;$i<=5;$i++): ?>
          <span class="star-btn" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)" style="cursor:pointer;color:var(--bg4);transition:color 0.1s">★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" id="rating-val" value="0">
      </div>
      <input type="text" id="review-title" placeholder="Judul ulasan singkat" style="width:100%;padding:10px 14px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;margin-bottom:10px">
      <textarea id="review-body" rows="4" placeholder="Tulis pengalaman kamu dengan HP ini..." style="width:100%;padding:10px 14px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:14px;resize:vertical"></textarea>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
        <button onclick="toggleReviewForm()" style="padding:9px 18px;border-radius:8px;background:transparent;border:1px solid var(--border2);color:var(--text2);font-size:13px;cursor:pointer">Batal</button>
        <button onclick="submitReview()" class="btn-primary" style="padding:9px 18px;font-size:13px">Kirim Ulasan</button>
      </div>
    </div>

    <?php if (!empty($reviews)): ?>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($reviews as $review): ?>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:var(--accent)">
              <?= strtoupper(substr($review['user_name'], 0, 1)) ?>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700"><?= htmlspecialchars($review['user_name']) ?>
                <?php if ($review['is_verified_buyer']): ?>
                <span style="font-size:10px;background:rgba(63,185,80,0.1);color:var(--green);border:1px solid rgba(63,185,80,0.2);padding:2px 7px;border-radius:4px;font-weight:700;margin-left:6px">✓ Pembeli Terverifikasi</span>
                <?php endif; ?>
              </div>
              <div style="font-size:12px;color:var(--text3)"><?= date('d M Y', strtotime($review['created_at'])) ?></div>
            </div>
          </div>
          <div style="display:flex;gap:2px">
            <?php for ($i=1;$i<=5;$i++): ?>
            <span style="font-size:14px;color:<?= $i <= $review['rating'] ? 'var(--amber)' : 'var(--bg4)' ?>">★</span>
            <?php endfor; ?>
          </div>
        </div>
        <?php if ($review['title']): ?><div style="font-size:15px;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($review['title']) ?></div><?php endif; ?>
        <div style="font-size:14px;color:var(--text2);line-height:1.6"><?= nl2br(htmlspecialchars($review['body'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:48px;color:var(--text3)">
      <div style="font-size:40px;margin-bottom:12px">💬</div>
      <div style="font-size:15px;font-weight:600">Belum ada ulasan</div>
      <div style="font-size:13px;margin-top:6px">Jadilah yang pertama memberi ulasan!</div>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Price Alert Modal (inline) -->
<div id="price-alert-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:300;align-items:center;justify-content:center" onclick="this.style.display='none'">
  <div style="background:var(--bg2);border:1px solid var(--border2);border-radius:var(--radius-lg);padding:28px;max-width:400px;width:90%;margin:24px" onclick="event.stopPropagation()">
    <h3 style="font-size:17px;font-weight:700;margin-bottom:8px">🔔 Alert Turun Harga</h3>
    <p style="font-size:13px;color:var(--text2);margin-bottom:20px">Beritahu saya ketika harga <?= htmlspecialchars($device['name']) ?> turun di bawah:</p>
    <div style="position:relative;margin-bottom:16px">
      <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--text3)">Rp</span>
      <input type="number" id="alert-price" placeholder="<?= $device['price'] ?>" style="width:100%;padding:10px 14px 10px 32px;background:var(--bg3);border:1px solid var(--border2);border-radius:8px;color:var(--text);font-size:15px">
    </div>
    <div style="display:flex;gap:10px">
      <button onclick="document.getElementById('price-alert-modal').style.display='none'" style="flex:1;padding:10px;border-radius:8px;background:transparent;border:1px solid var(--border2);color:var(--text2);font-size:13px;cursor:pointer">Batal</button>
      <button id="alert-submit-btn" onclick="submitAlert()" class="btn-primary" style="flex:2;padding:10px;font-size:13px">Aktifkan Alert</button>
    </div>
  </div>
</div>

<style>
@media (max-width:768px) {
  .device-detail-grid { grid-template-columns: 1fr !important; }
}
</style>

<script>
const deviceId = <?= $device['id'] ?>;

function toggleReviewForm() {
  const form = document.getElementById('review-form');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function setRating(val) {
  document.getElementById('rating-val').value = val;
  document.querySelectorAll('.star-btn').forEach((s, i) => {
    s.style.color = i < val ? 'var(--amber)' : 'var(--bg4)';
  });
}

async function submitReview() {
  const rating = parseInt(document.getElementById('rating-val').value);
  const title = document.getElementById('review-title').value.trim();
  const body = document.getElementById('review-body').value.trim();
  if (!rating) { App.toast('Pilih rating bintang dulu', 'error'); return; }
  if (!body) { App.toast('Tulis ulasan kamu dulu', 'error'); return; }
  // POST to review API (would be a separate endpoint)
  App.toast('Ulasan dikirim! Menunggu moderasi.', 'success');
  toggleReviewForm();
}

function setPriceAlert() {
  <?php if (empty($_SESSION['user_id'])): ?>
  App.toast('Login dulu untuk mengaktifkan alert harga', 'error');
  window.location = 'login.php';
  <?php else: ?>
  document.getElementById('price-alert-modal').style.display = 'flex';
  <?php endif; ?>
}

function beliSekarang() {
  const isLoggedIn = <?= empty($_SESSION['user_id']) ? 'false' : 'true' ?>;
  if (!isLoggedIn) {
    App.toast('Login dulu untuk melanjutkan pembelian', 'error');
    setTimeout(() => window.location = 'login.php?redirect=' + encodeURIComponent('device.php?id=<?= $device['id'] ?>'), 1200);
    return;
  }
  window.location = 'checkout.php?device_id=<?= $device['id'] ?>';
}

async function submitAlert() {
  const price = document.getElementById('alert-price').value;
  if (!price || parseInt(price) <= 0) { App.toast('Masukkan harga target yang valid', 'error'); return; }
  const btn = document.getElementById('alert-submit-btn');
  btn.textContent = '...';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('action', 'set_price_alert');
    fd.append('device_id', <?= $device['id'] ?>);
    fd.append('target_price', price);
    const res = await fetch('api.php', { method: 'POST', body: fd });
    const data = await res.json();
    document.getElementById('price-alert-modal').style.display = 'none';
    if (data.status === 'ok') {
      App.toast('🔔 ' + data.message, 'success');
    } else {
      App.toast(data.error || 'Gagal menyimpan alert', 'error');
    }
  } catch(e) {
    App.toast('Gagal menyimpan alert', 'error');
  }
  btn.textContent = 'Aktifkan Alert';
  btn.disabled = false;
}
</script>

<?php require_once 'includes/footer.php'; ?>
