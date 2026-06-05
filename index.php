<?php
session_start();
require_once 'includes/db.php';

$page_title = 'Beranda';

$conn = getDB();

// Featured devices — top by performance
$featured = mysqli_query($conn, "SELECT id, name, brand, slug, price, image, category, screen_size, ram, storage, battery, main_camera, score_camera, score_performance, score_battery, score_design, network_5g, release_year FROM devices WHERE is_active=1 ORDER BY score_performance DESC LIMIT 8");
$devices = [];
while ($row = mysqli_fetch_assoc($featured)) {
    $row['price_fmt'] = formatPrice($row['price']);
    $row['overall_score'] = round(($row['score_camera'] + $row['score_performance'] + $row['score_battery'] + $row['score_design']) / 4, 1);
    $devices[] = $row;
}

// Newest devices
$newest_res = mysqli_query($conn, "SELECT id, name, brand, slug, price, image, category, screen_size, ram, storage, battery, main_camera, score_camera, score_performance, score_battery, score_design, network_5g, release_year FROM devices WHERE is_active=1 ORDER BY release_year DESC, created_at DESC LIMIT 4");
$newest = [];
while ($row = mysqli_fetch_assoc($newest_res)) {
    $row['price_fmt'] = formatPrice($row['price']);
    $row['overall_score'] = round(($row['score_camera'] + $row['score_performance'] + $row['score_battery'] + $row['score_design']) / 4, 1);
    $newest[] = $row;
}

// Active promos
$promos_res = mysqli_query($conn, "SELECT * FROM promos WHERE is_active=1 AND start_date<=CURDATE() AND end_date>=CURDATE() ORDER BY created_at DESC LIMIT 3");
$promos = [];
if ($promos_res) {
    while ($row = mysqli_fetch_assoc($promos_res)) $promos[] = $row;
}

// Stats
$total_devices = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM devices WHERE is_active=1"))[0];
$brands        = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT brand) FROM devices WHERE is_active=1"))[0];
$total_reviews = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reviews"))[0] ?? 0;
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section style="background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,212,255,0.12) 0%, transparent 70%);">
  <div class="hero">
    <div class="hero-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Platform Perbandingan Smartphone #1 Indonesia
    </div>
    <h1>Temukan HP <span class="highlight">Sempurna</span><br>untuk Kamu</h1>
    <p>Bandingkan spesifikasi secara detail, baca ulasan nyata, dan beli dengan percaya diri.</p>

    <!-- Live Search -->
    <div class="search-container">
      <div class="search-input-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="main-search" class="search-input" placeholder="Cari Samsung, iPhone, Xiaomi, POCO... (min. 2 huruf)" autocomplete="off" spellcheck="false">
        <span style="font-size:12px;color:var(--text3);white-space:nowrap">Tekan ESC untuk tutup</span>
      </div>
      <div class="search-dropdown" id="search-dropdown"></div>
    </div>
  </div>

  <!-- Stats bar -->
  <div style="display:flex;justify-content:center;gap:48px;padding:0 24px 48px;flex-wrap:wrap">
    <div style="text-align:center">
      <div style="font-size:28px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $total_devices ?>+</div>
      <div style="font-size:13px;color:var(--text3);margin-top:2px">Perangkat</div>
    </div>
    <div style="text-align:center">
      <div style="font-size:28px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $brands ?>+</div>
      <div style="font-size:13px;color:var(--text3);margin-top:2px">Merek</div>
    </div>
    <div style="text-align:center">
      <div style="font-size:28px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= $total_reviews ?>+</div>
      <div style="font-size:13px;color:var(--text3);margin-top:2px">Ulasan</div>
    </div>
    <div style="text-align:center">
      <div style="font-size:28px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">100%</div>
      <div style="font-size:13px;color:var(--text3);margin-top:2px">Data Terverifikasi</div>
    </div>
  </div>
</section>

<!-- ── PROMO BANNER SECTION ───────────────────────────────────────── -->
<?php if (!empty($promos)): ?>
<div class="section" style="padding-top:0;padding-bottom:8px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
      <h2 style="font-size:20px;font-weight:800">🔥 Promo & Flash Sale</h2>
      <p style="font-size:13px;color:var(--text3);margin-top:4px">Penawaran terbatas — jangan sampai ketinggalan!</p>
    </div>
    <a href="deals.php" style="font-size:13px;font-weight:600;color:var(--accent)">Lihat semua →</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
    <?php foreach ($promos as $promo):
      $days_left = (int)ceil((strtotime($promo['end_date']) - time()) / 86400);
      $is_percent = $promo['discount_type'] === 'percent';
      $discount_label = $is_percent ? $promo['discount_value'].'%' : formatPrice($promo['discount_value']);
    ?>
    <div style="background:linear-gradient(135deg,rgba(0,212,255,.06),rgba(168,85,247,.06));border:1px solid rgba(0,212,255,.2);border-radius:var(--radius-lg);padding:20px;position:relative;overflow:hidden">
      <div style="position:absolute;top:0;right:0;width:80px;height:80px;background:linear-gradient(135deg,rgba(0,212,255,.1),rgba(168,85,247,.1));border-radius:0 0 0 80px"></div>
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
        <span style="display:inline-block;background:linear-gradient(135deg,var(--accent),var(--purple));color:#000;font-size:11px;font-weight:800;padding:3px 10px;border-radius:5px">HEMAT <?= $discount_label ?></span>
        <span style="font-size:11px;color:var(--text3)"><?= $days_left ?> hari lagi</span>
      </div>
      <div style="font-size:16px;font-weight:800;margin-bottom:6px;color:var(--text)"><?= htmlspecialchars($promo['title']) ?></div>
      <div style="font-size:13px;color:var(--text2);margin-bottom:12px;line-height:1.5"><?= htmlspecialchars($promo['description'] ?: 'Gunakan kode promo saat checkout') ?></div>
      <div style="display:flex;align-items:center;gap:10px">
        <div style="background:var(--bg2);border:1px dashed var(--border2);padding:6px 14px;border-radius:7px;font-family:monospace;font-size:15px;font-weight:800;color:var(--accent);letter-spacing:1px"><?= htmlspecialchars($promo['promo_code']) ?></div>
        <button onclick="copyPromo('<?= htmlspecialchars($promo['promo_code']) ?>', this)" style="padding:6px 12px;border-radius:7px;background:transparent;border:1px solid var(--border);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer">Salin</button>
      </div>
      <?php if ($promo['min_purchase'] > 0): ?>
      <div style="font-size:11px;color:var(--text3);margin-top:8px">Min. pembelian <?= formatPrice($promo['min_purchase']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── HP TERBARU SECTION ─────────────────────────────────────────── -->
<?php if (!empty($newest)): ?>
<div class="section" style="padding-top:24px;padding-bottom:8px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div>
      <h2 style="font-size:20px;font-weight:800">✨ HP Terbaru <?= date('Y') ?></h2>
      <p style="font-size:13px;color:var(--text3);margin-top:4px">Rilis terbaru yang baru masuk database kami</p>
    </div>
    <a href="catalog.php?sort=newest" style="font-size:13px;font-weight:600;color:var(--accent)">Lihat semua →</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php foreach ($newest as $d): ?>
    <a href="device.php?slug=<?= $d['slug'] ?>" style="text-decoration:none;display:block;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;transition:border-color .2s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
        <img src="<?= htmlspecialchars($d['image'] ?? '') ?>" style="width:52px;height:52px;object-fit:contain;background:var(--bg3);border-radius:8px;padding:4px" onerror="this.src='https://via.placeholder.com/52?text=📱'">
        <div>
          <div style="font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase"><?= htmlspecialchars($d['brand']) ?></div>
          <div style="font-size:14px;font-weight:700;color:var(--text);line-height:1.3"><?= htmlspecialchars($d['name']) ?></div>
        </div>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div style="font-size:15px;font-weight:800;color:var(--accent)"><?= $d['price_fmt'] ?></div>
        <div style="display:flex;align-items:center;gap:4px">
          <span style="font-size:11px;color:var(--text3)">Skor</span>
          <span style="font-size:14px;font-weight:800;color:var(--text)"><?= $d['overall_score'] ?></span>
        </div>
      </div>
      <div style="display:flex;gap:4px;margin-top:10px;flex-wrap:wrap">
        <span class="spec-chip"><?= $d['ram'] ?>GB RAM</span>
        <span class="spec-chip"><?= $d['main_camera'] ?>MP</span>
        <?php if ($d['network_5g']): ?><span class="badge badge-5g" style="font-size:10px;padding:2px 6px">5G</span><?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── PERSONA FILTER + MAIN GRID ─────────────────────────────────── -->
<div class="persona-filter" id="persona-filter">
  <button class="persona-btn active" data-persona="" onclick="filterPersona(this, '')">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Semua
  </button>
  <button class="persona-btn" data-persona="gaming" onclick="filterPersona(this, 'gaming')">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="13" r="1"/><circle cx="18" cy="11" r="1"/><rect x="2" y="8" width="20" height="8" rx="2"/></svg>
    Gaming Berat
  </button>
  <button class="persona-btn" data-persona="photo" onclick="filterPersona(this, 'photo')">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
    Fotografi
  </button>
  <button class="persona-btn" data-persona="battery" onclick="filterPersona(this, 'battery')">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="18" height="11" rx="2"/><path d="M22 11v3"/><path d="M7 11v2"/><path d="M12 11v2"/></svg>
    Baterai Awet
  </button>
  <button class="persona-btn" data-persona="budget" onclick="filterPersona(this, 'budget')">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    Budget Pelajar
  </button>
</div>

<!-- Sort Bar -->
<div class="section" style="padding-bottom:16px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div id="result-label" style="font-size:14px;color:var(--text2)">Menampilkan <strong id="result-count" style="color:var(--text)"><?= count($devices) ?></strong> perangkat</div>
    <div style="display:flex;align-items:center;gap:10px">
      <label style="font-size:13px;color:var(--text3)">Urutkan:</label>
      <select id="sort-select" onchange="sortDevices(this.value)" style="padding:7px 12px;border-radius:8px;background:var(--bg2);border:1px solid var(--border2);color:var(--text);font-size:13px;cursor:pointer">
        <option value="score_performance">Performa Terbaik</option>
        <option value="price_desc">Harga Tertinggi</option>
        <option value="price_asc">Harga Terendah</option>
        <option value="score_camera">Kamera Terbaik</option>
        <option value="score_battery">Baterai Terbaik</option>
        <option value="newest">Terbaru</option>
      </select>
    </div>
  </div>
</div>

<!-- Devices Grid -->
<div class="section" style="padding-top:0">
  <div class="devices-grid" id="devices-grid">
    <?php foreach ($devices as $d): ?>
    <div class="device-card" data-id="<?= $d['id'] ?>" data-score-cam="<?= $d['score_camera'] ?>" data-score-perf="<?= $d['score_performance'] ?>" data-score-bat="<?= $d['score_battery'] ?>" data-price="<?= $d['price'] ?>" data-battery="<?= $d['battery'] ?>">
      <div class="card-image-wrap">
        <img class="card-image" src="<?= htmlspecialchars($d['image'] ?? '') ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/200x200/21262d/666?text=<?= urlencode($d['brand']) ?>'">
        <div class="card-badges">
          <?php if ($d['network_5g']): ?><span class="badge badge-5g">5G</span><?php endif; ?>
          <?php if ($d['category'] === 'flagship'): ?><span class="badge badge-flagship">Flagship</span><?php endif; ?>
          <?php if ($d['category'] === 'gaming'): ?><span class="badge badge-gaming">Gaming</span><?php endif; ?>
        </div>
        <button class="btn-wishlist" data-wishlist-id="<?= $d['id'] ?>" onclick="App.toggleWishlist(<?= $d['id'] ?>, this)" aria-label="Tambah ke wishlist">♡</button>
      </div>
      <div class="card-body">
        <div class="card-brand"><?= htmlspecialchars($d['brand']) ?></div>
        <div class="card-name"><?= htmlspecialchars($d['name']) ?></div>

        <div class="card-scores">
          <div class="score-item"><div class="score-label">Kamera</div><div class="score-value"><?= $d['score_camera'] ?></div></div>
          <div class="score-item"><div class="score-label">Performa</div><div class="score-value"><?= $d['score_performance'] ?></div></div>
          <div class="score-item"><div class="score-label">Baterai</div><div class="score-value"><?= $d['score_battery'] ?></div></div>
          <div class="score-item"><div class="score-label">Desain</div><div class="score-value"><?= $d['score_design'] ?></div></div>
        </div>

        <div class="card-specs">
          <span class="spec-chip"><?= $d['ram'] ?>GB RAM</span>
          <span class="spec-chip"><?= $d['storage'] ?>GB</span>
          <span class="spec-chip"><?= $d['main_camera'] ?>MP</span>
          <span class="spec-chip"><?= number_format($d['battery']) ?> mAh</span>
          <span class="spec-chip"><?= $d['screen_size'] ?>"</span>
        </div>

        <div class="card-footer">
          <div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:2px">Mulai dari</div>
            <div class="card-price"><?= $d['price_fmt'] ?></div>
          </div>
          <div style="display:flex;gap:6px">
            <a href="device.php?slug=<?= $d['slug'] ?>" class="btn-primary" style="padding:6px 14px;font-size:13px;font-weight:700">Detail</a>
            <button class="btn-compare" data-compare-id="<?= $d['id'] ?>" onclick="App.addToCompare(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>', '<?= addslashes($d['image'] ?? '') ?>')">+ Bandingkan</button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <div id="index-pagination-wrap" style="display:none;margin-top:28px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div id="index-pagination-info" style="font-size:13px;color:var(--text3)"></div>
      <div id="index-pagination-controls" style="display:flex;align-items:center;gap:6px"></div>
    </div>
  </div>
</div>

<!-- ── WHY TRUST SECTION ───────────────────────────────────────────── -->
<div class="section">
  <hr class="divider">
  <div style="text-align:center;margin-bottom:24px;margin-top:32px">
    <h2 style="font-size:22px;font-weight:800">Kenapa Percaya SpecSync?</h2>
    <p style="font-size:14px;color:var(--text3);margin-top:8px">Kami hadir untuk bantu kamu ambil keputusan terbaik</p>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
    <?php
    $features = [
      ['icon'=>'🔍','title'=>'Spesifikasi Akurat','desc'=>'Data bersumber langsung dari produsen dan diverifikasi tim kami'],
      ['icon'=>'⚡','title'=>'Update Real-time','desc'=>'Harga dan stok diperbarui setiap hari dari toko resmi'],
      ['icon'=>'🛡️','title'=>'Ulasan Terverifikasi','desc'=>'Badge "Pembeli Terverifikasi" hanya untuk yang benar-benar membeli'],
      ['icon'=>'💳','title'=>'Pembayaran Aman','desc'=>'Simulasi metode pembayaran lengkap: VA, QRIS, Kartu Kredit'],
    ];
    foreach ($features as $f):
    ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px">
      <div style="font-size:28px;margin-bottom:12px"><?= $f['icon'] ?></div>
      <div style="font-size:15px;font-weight:700;margin-bottom:8px"><?= $f['title'] ?></div>
      <div style="font-size:13px;color:var(--text2);line-height:1.6"><?= $f['desc'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── CTA SECTION (belum login) ─────────────────────────────────── -->
<?php if (empty($_SESSION['user_id'])): ?>
<div class="section">
  <div style="background:linear-gradient(135deg,rgba(0,212,255,.08),rgba(168,85,247,.08));border:1px solid rgba(0,212,255,.15);border-radius:var(--radius-lg);padding:40px;text-align:center">
    <div style="font-size:36px;margin-bottom:12px">🚀</div>
    <h2 style="font-size:22px;font-weight:800;margin-bottom:8px">Mulai Pengalaman Lengkap</h2>
    <p style="font-size:14px;color:var(--text2);margin-bottom:24px;max-width:480px;margin-left:auto;margin-right:auto">Daftar gratis untuk simpan wishlist, tulis ulasan, bandingkan lebih banyak HP, dan nikmati promo eksklusif.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="register.php" class="btn-primary" style="font-size:15px;padding:12px 28px">Daftar Gratis →</a>
      <a href="login.php" style="padding:12px 28px;border-radius:var(--radius);border:1px solid var(--border);color:var(--text2);font-size:15px;font-weight:600">Sudah punya akun</a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function copyPromo(code, btn) {
  navigator.clipboard.writeText(code).then(() => {
    btn.textContent = '✓ Tersalin!';
    btn.style.color = 'var(--green)';
    btn.style.borderColor = 'rgba(63,185,80,.4)';
    setTimeout(() => { btn.textContent = 'Salin'; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
  });
}

let currentPersona = '';
let currentSort = 'score_performance';
let allIndexDevices = [];
let indexCurrentPage = 1;
const INDEX_PAGE_SIZE = 12;

function indexTotalPages() {
  return Math.ceil(allIndexDevices.length / INDEX_PAGE_SIZE);
}

async function filterPersona(btn, persona) {
  document.querySelectorAll('.persona-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentPersona = persona;
  await loadDevices();
}

async function sortDevices(sort) {
  currentSort = sort;
  await loadDevices();
}

async function loadDevices() {
  const grid = document.getElementById('devices-grid');
  grid.innerHTML = '<div class="loading-grid">' + Array(6).fill('<div class="skeleton loading-card"></div>').join('') + '</div>';

  let url = `api.php?action=catalog&sort=${currentSort}`;
  if (currentPersona) url += `&persona=${currentPersona}`;

  try {
    const res = await fetch(url);
    allIndexDevices = await res.json();
    indexCurrentPage = 1;
    renderIndexPage();
  } catch(e) {
    grid.innerHTML = '<p style="color:var(--text3);text-align:center;padding:48px">Gagal memuat data</p>';
  }
}

function renderIndexPage() {
  const total = allIndexDevices.length;
  const pages = indexTotalPages();
  const start = (indexCurrentPage - 1) * INDEX_PAGE_SIZE;
  const end = Math.min(start + INDEX_PAGE_SIZE, total);
  const pageDevices = allIndexDevices.slice(start, end);

  document.getElementById('result-count').textContent = total;
  renderDevices(pageDevices, document.getElementById('devices-grid'));
  renderIndexPagination(pages, total, start, end);

  if (indexCurrentPage > 1) {
    document.getElementById('devices-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function renderIndexPagination(pages, total, start, end) {
  const wrap = document.getElementById('index-pagination-wrap');
  const controls = document.getElementById('index-pagination-controls');
  const info = document.getElementById('index-pagination-info');

  if (pages <= 1) {
    wrap.style.display = 'none';
    return;
  }

  wrap.style.display = 'flex';
  info.textContent = `Halaman ${indexCurrentPage} dari ${pages} (${start + 1}–${end} dari ${total} perangkat)`;

  const pageNums = [];
  for (let i = 1; i <= pages; i++) {
    if (i === 1 || i === pages || (i >= indexCurrentPage - 1 && i <= indexCurrentPage + 1)) {
      pageNums.push(i);
    } else if (pageNums[pageNums.length - 1] !== '...') {
      pageNums.push('...');
    }
  }

  controls.innerHTML = `
    <button class="pg-btn" onclick="goIndexPage(${indexCurrentPage - 1})" ${indexCurrentPage === 1 ? 'disabled' : ''} title="Sebelumnya">‹</button>
    ${pageNums.map(p =>
      p === '...'
        ? `<span class="pg-ellipsis">…</span>`
        : `<button class="pg-btn ${p === indexCurrentPage ? 'active' : ''}" onclick="goIndexPage(${p})">${p}</button>`
    ).join('')}
    <button class="pg-btn" onclick="goIndexPage(${indexCurrentPage + 1})" ${indexCurrentPage === pages ? 'disabled' : ''} title="Berikutnya">›</button>
  `;
}

function goIndexPage(page) {
  const pages = indexTotalPages();
  if (page < 1 || page > pages) return;
  indexCurrentPage = page;
  renderIndexPage();
}

function renderDevices(devices, grid) {
  if (!devices.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">📱</div><div style="font-size:16px;font-weight:600">Tidak ada HP yang cocok</div><div style="font-size:14px;margin-top:8px">Coba filter lain</div></div>';
    return;
  }
  const wishlist = JSON.parse(localStorage.getItem('ss_wishlist') || '[]');
  const compareList = JSON.parse(localStorage.getItem('ss_compare') || '[]');

  grid.innerHTML = devices.map(d => `
    <div class="device-card">
      <div class="card-image-wrap">
        <img class="card-image" src="${d.image || ''}" alt="${d.name}" loading="lazy" onerror="this.src='https://via.placeholder.com/200x200/21262d/666?text=${encodeURIComponent(d.brand)}'">
        <div class="card-badges">
          ${d.network_5g ? '<span class="badge badge-5g">5G</span>' : ''}
          ${d.category === 'flagship' ? '<span class="badge badge-flagship">Flagship</span>' : ''}
          ${d.category === 'gaming' ? '<span class="badge badge-gaming">Gaming</span>' : ''}
        </div>
        <button class="btn-wishlist ${wishlist.includes(d.id) ? 'active' : ''}" data-wishlist-id="${d.id}" onclick="App.toggleWishlist(${d.id}, this)">${wishlist.includes(d.id) ? '♥' : '♡'}</button>
      </div>
      <div class="card-body">
        <div class="card-brand">${d.brand}</div>
        <div class="card-name">${d.name}</div>
        <div class="card-scores">
          <div class="score-item"><div class="score-label">Kamera</div><div class="score-value">${d.score_camera}</div></div>
          <div class="score-item"><div class="score-label">Performa</div><div class="score-value">${d.score_performance}</div></div>
          <div class="score-item"><div class="score-label">Baterai</div><div class="score-value">${d.score_battery}</div></div>
          <div class="score-item"><div class="score-label">Desain</div><div class="score-value">${d.score_design}</div></div>
        </div>
        <div class="card-specs">
          <span class="spec-chip">${d.ram}GB RAM</span>
          <span class="spec-chip">${d.storage}GB</span>
          <span class="spec-chip">${d.main_camera}MP</span>
          <span class="spec-chip">${parseInt(d.battery).toLocaleString()} mAh</span>
          <span class="spec-chip">${d.screen_size}"</span>
        </div>
        <div class="card-footer">
          <div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:2px">Mulai dari</div>
            <div class="card-price">${d.price_fmt}</div>
          </div>
          <div style="display:flex;gap:6px">
            <a href="device.php?slug=${d.slug}" class="btn-primary" style="padding:6px 14px;font-size:13px;font-weight:700">Detail</a>
            <button class="btn-compare ${compareList.find(c => c.id == d.id) ? 'added' : ''}" data-compare-id="${d.id}" onclick="App.addToCompare(${d.id}, '${d.name.replace(/'/g,"\\'")}', '${(d.image||'').replace(/'/g,"\\'")}')">
              ${compareList.find(c => c.id == d.id) ? '✓ Dibandingkan' : '+ Bandingkan'}
            </button>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}
</script>

<?php require_once 'includes/footer.php'; ?>