<?php
session_start();
require_once 'includes/db.php';

// Get devices from query params or share token
$token = sanitize($_GET['token'] ?? '');
$d1_id = intval($_GET['d1'] ?? 0);
$d2_id = intval($_GET['d2'] ?? 0);

$conn = getDB();

if ($token) {
    $res = mysqli_query($conn, "SELECT * FROM saved_comparisons WHERE share_token='$token'");
    $comp = mysqli_fetch_assoc($res);
    if ($comp) { $d1_id = $comp['device1_id']; $d2_id = $comp['device2_id']; }
}

$d1 = $d2 = null;
if ($d1_id) {
    $r = mysqli_query($conn, "SELECT * FROM devices WHERE id=$d1_id AND is_active=1");
    $d1 = mysqli_fetch_assoc($r);
    if ($d1) $d1['price_fmt'] = formatPrice($d1['price']);
}
if ($d2_id) {
    $r = mysqli_query($conn, "SELECT * FROM devices WHERE id=$d2_id AND is_active=1");
    $d2 = mysqli_fetch_assoc($r);
    if ($d2) $d2['price_fmt'] = formatPrice($d2['price']);
}

$page_title = $d1 && $d2 ? $d1['name'] . ' vs ' . $d2['name'] : 'Bandingkan';

// Define spec groups for comparison table
$spec_groups = [
    'Layar' => [
        'Ukuran Layar' => ['screen_size', 'float', 'inci', 'higher'],
        'Resolusi' => ['resolution', 'string', '', 'none'],
        'Refresh Rate' => ['refresh_rate', 'int', 'Hz', 'higher'],
        'Tipe Panel' => ['display_type', 'string', '', 'none'],
    ],
    'Performa' => [
        'Chipset' => ['chipset', 'string', '', 'none'],
        'CPU Cores' => ['cpu_cores', 'int', 'core', 'higher'],
        'GPU' => ['gpu', 'string', '', 'none'],
        'RAM' => ['ram', 'int', 'GB', 'higher'],
        'Storage' => ['storage', 'int', 'GB', 'higher'],
    ],
    'Kamera' => [
        'Kamera Utama' => ['main_camera', 'int', 'MP', 'higher'],
        'Kamera Selfie' => ['front_camera', 'int', 'MP', 'higher'],
        'Fitur Kamera' => ['camera_features', 'string', '', 'none'],
    ],
    'Baterai' => [
        'Kapasitas Baterai' => ['battery', 'int', 'mAh', 'higher'],
        'Kecepatan Charge' => ['charging_speed', 'int', 'W', 'higher'],
        'Wireless Charging' => ['has_wireless_charging', 'bool', '', 'bool'],
    ],
    'Desain' => [
        'Berat' => ['weight', 'int', 'gram', 'lower'],
        'Ketebalan' => ['thickness', 'float', 'mm', 'lower'],
        'Lebar' => ['width', 'float', 'mm', 'lower'],
        'Tinggi' => ['height', 'float', 'mm', 'lower'],
    ],
    'Konektivitas' => [
        'Jaringan 5G' => ['network_5g', 'bool', '', 'bool'],
        'WiFi' => ['wifi_version', 'string', '', 'none'],
        'Bluetooth' => ['bluetooth_version', 'string', '', 'none'],
        'NFC' => ['nfc', 'bool', '', 'bool'],
    ],
    'Software' => [
        'Sistem Operasi' => ['os', 'string', '', 'none'],
        'Versi OS' => ['os_version', 'string', '', 'none'],
    ],
    'Skor SpecSync' => [
        'Skor Kamera' => ['score_camera', 'float', '/10', 'higher'],
        'Skor Performa' => ['score_performance', 'float', '/10', 'higher'],
        'Skor Baterai' => ['score_battery', 'float', '/10', 'higher'],
        'Skor Desain' => ['score_design', 'float', '/10', 'higher'],
    ],
];

function getWinner($val1, $val2, $mode) {
    if ($mode === 'none' || $val1 === null || $val2 === null) return '';
    if ($mode === 'bool') { return ''; }
    $v1 = floatval($val1); $v2 = floatval($val2);
    if ($v1 == $v2) return '';
    if ($mode === 'higher') return $v1 > $v2 ? 'd1' : 'd2';
    if ($mode === 'lower') return $v1 < $v2 ? 'd1' : 'd2';
    return '';
}

function formatVal($val, $type, $unit) {
    if ($val === null || $val === '') return '<span style="color:var(--text3)">—</span>';
    if ($type === 'bool') return $val ? '<span style="color:var(--green)">✓ Ya</span>' : '<span style="color:var(--red)">✗ Tidak</span>';
    if ($type === 'float') return number_format(floatval($val), 1) . ($unit ? " $unit" : '');
    if ($type === 'int') return number_format(intval($val)) . ($unit ? " $unit" : '');
    return htmlspecialchars($val) . ($unit ? " $unit" : '');
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="compare-page">

<?php if (!$d1 || !$d2): ?>
<!-- Empty state - no devices selected -->
<div style="text-align:center;padding:80px 24px">
  <div style="font-size:64px;margin-bottom:24px">📱</div>
  <h1 style="font-size:28px;font-weight:800;margin-bottom:12px">Bandingkan Dua Smartphone</h1>
  <p style="color:var(--text2);font-size:16px;margin-bottom:32px">Cari HP dari beranda atau katalog, lalu klik "+ Bandingkan"</p>
  <a href="index.php" class="btn-primary" style="display:inline-block;font-size:15px;padding:12px 28px">Mulai dari Beranda →</a>
</div>

<?php else: ?>

<!-- Sticky compare header -->
<div class="compare-header-strip">
  <div class="compare-devices-row">
    <div style="font-size:13px;font-weight:700;color:var(--text3)">PERBANDINGAN</div>
    <div class="compare-device-col">
      <img src="<?= htmlspecialchars($d1['image'] ?? '') ?>" alt="<?= htmlspecialchars($d1['name']) ?>" onerror="this.src='https://via.placeholder.com/60x60/21262d/666'">
      <div class="compare-device-col-info">
        <div class="compare-device-col-name"><?= htmlspecialchars($d1['name']) ?></div>
        <div class="compare-device-col-price"><?= $d1['price_fmt'] ?></div>
      </div>
    </div>
    <div class="compare-device-col">
      <img src="<?= htmlspecialchars($d2['image'] ?? '') ?>" alt="<?= htmlspecialchars($d2['name']) ?>" onerror="this.src='https://via.placeholder.com/60x60/21262d/666'">
      <div class="compare-device-col-info">
        <div class="compare-device-col-name"><?= htmlspecialchars($d2['name']) ?></div>
        <div class="compare-device-col-price"><?= $d2['price_fmt'] ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Action buttons -->
<div class="compare-actions-bar" style="margin-top:24px">
  <button class="btn-toggle" id="btn-diff" onclick="toggleDiff()" title="Tampilkan hanya perbedaan">
    🔍 Tampilkan Hanya Perbedaan
  </button>
  <button class="btn-toggle" id="btn-winner" onclick="toggleWinner()" title="Sorot pemenang">
    👑 Sorot Pemenang
  </button>
  <button class="btn-toggle btn-share" onclick="shareComparison()">
    🔗 Bagikan Perbandingan
  </button>
  <div style="margin-left:auto;display:flex;gap:8px">
    <a href="index.php" class="btn-toggle">← Ganti HP</a>
    <?php if (!empty($_SESSION['user_id'])): ?>
    <button class="btn-toggle" onclick="saveComparison()">💾 Simpan</button>
    <?php endif; ?>
  </div>
</div>

<!-- Score Visual Cards -->
<div class="score-section" style="margin-top:8px">
  <div class="score-grid">
    <?php
    $score_cats = ['Kamera'=>['score_camera','📸'], 'Performa'=>['score_performance','⚡'], 'Baterai'=>['score_battery','🔋'], 'Desain'=>['score_design','✨']];
    foreach ($score_cats as $label => [$field, $icon]):
      $v1 = floatval($d1[$field]); $v2 = floatval($d2[$field]);
    ?>
    <div class="score-card">
      <div class="score-card-label"><?= $icon ?> Skor <?= $label ?></div>
      <div class="score-bars">
        <div class="score-bar-row">
          <div class="score-bar-label" style="color:var(--accent);font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(explode(' ', $d1['name'])[0] . ' ' . (explode(' ', $d1['name'])[1] ?? '')) ?></div>
          <div class="score-bar-track"><div class="score-bar-fill phone1" style="width:<?= ($v1/10)*100 ?>%"></div></div>
          <div class="score-bar-num phone1"><?= $v1 ?></div>
        </div>
        <div class="score-bar-row">
          <div class="score-bar-label" style="color:var(--purple);font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(explode(' ', $d2['name'])[0] . ' ' . (explode(' ', $d2['name'])[1] ?? '')) ?></div>
          <div class="score-bar-track"><div class="score-bar-fill phone2" style="width:<?= ($v2/10)*100 ?>%"></div></div>
          <div class="score-bar-num phone2"><?= $v2 ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Visual Size Comparison -->
<?php if ($d1['width'] && $d1['height'] && $d2['width'] && $d2['height']): ?>
<div class="size-viz-wrap">
  <div class="size-viz-title">📐 Perbandingan Ukuran Fisik (Skala Proporsional)</div>
  <div class="size-viz" id="size-viz"></div>
  <div style="display:flex;justify-content:center;gap:48px;margin-top:16px">
    <div class="size-label phone1">
      <?= htmlspecialchars(explode(' ', $d1['name'])[0]) ?><br>
      <span class="size-dims"><?= $d1['width'] ?> × <?= $d1['height'] ?> × <?= $d1['thickness'] ?> mm · <?= $d1['weight'] ?>g</span>
    </div>
    <div class="size-label phone2">
      <?= htmlspecialchars(explode(' ', $d2['name'])[0]) ?><br>
      <span class="size-dims"><?= $d2['width'] ?> × <?= $d2['height'] ?> × <?= $d2['thickness'] ?> mm · <?= $d2['weight'] ?>g</span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Spec Comparison Table -->
<div class="compare-table-wrap">
  <table class="compare-table" id="compare-table">
    <thead>
      <tr>
        <th style="width:200px">Spesifikasi</th>
        <th style="color:var(--accent)"><?= htmlspecialchars($d1['name']) ?></th>
        <th style="color:var(--purple)"><?= htmlspecialchars($d2['name']) ?></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($spec_groups as $group_name => $specs): ?>
      <tr class="section-row"><td colspan="3"><?= $group_name ?></td></tr>
      <?php foreach ($specs as $label => [$field, $type, $unit, $mode]):
        $v1 = $d1[$field] ?? null; $v2 = $d2[$field] ?? null;
        $winner = getWinner($v1, $v2, $mode);
        $same = ($type !== 'bool' && $type !== 'string') ? (floatval($v1) === floatval($v2)) : ($v1 === $v2);
        $diff_class = $same ? 'same-row' : 'diff-row';
      ?>
      <tr class="spec-row <?= $diff_class ?>" data-same="<?= $same ? '1' : '0' ?>">
        <td><?= $label ?></td>
        <td class="<?= $winner === 'd1' ? 'winner' : '' ?>">
          <?= formatVal($v1, $type, $unit) ?>
          <?php if ($winner === 'd1'): ?><span class="winner-badge">👑 Lebih Baik</span><?php endif; ?>
        </td>
        <td class="<?= $winner === 'd2' ? 'winner' : '' ?>">
          <?= formatVal($v2, $type, $unit) ?>
          <?php if ($winner === 'd2'): ?><span class="winner-badge">👑 Lebih Baik</span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Verdict -->
<?php
$total1 = ($d1['score_camera'] + $d1['score_performance'] + $d1['score_battery'] + $d1['score_design']) / 4;
$total2 = ($d2['score_camera'] + $d2['score_performance'] + $d2['score_battery'] + $d2['score_design']) / 4;
$winner_device = $total1 > $total2 ? $d1 : ($total2 > $total1 ? $d2 : null);
?>
<?php if ($winner_device): ?>
<div style="margin:32px 0;padding:24px;background:rgba(63,185,80,0.08);border:1px solid rgba(63,185,80,0.2);border-radius:var(--radius-lg)">
  <div style="font-size:13px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">👑 Skor Keseluruhan</div>
  <div style="font-size:18px;font-weight:700">
    <span style="color:var(--green)"><?= htmlspecialchars($winner_device['name']) ?></span>
    unggul secara keseluruhan dengan skor rata-rata <strong><?= round(max($total1, $total2), 1) ?>/10</strong>
    vs <strong><?= round(min($total1, $total2), 1) ?>/10</strong>
  </div>
  <div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap">
    <a href="device.php?slug=<?= $d1['slug'] ?>" class="btn-compare" style="font-size:14px;padding:10px 18px">Detail <?= htmlspecialchars(explode(' ', $d1['name'])[0]) ?></a>
    <a href="device.php?slug=<?= $d2['slug'] ?>" class="btn-compare" style="font-size:14px;padding:10px 18px">Detail <?= htmlspecialchars(explode(' ', $d2['name'])[0]) ?></a>
  </div>
</div>
<?php endif; ?>

<script>
const d1Data = <?= json_encode($d1) ?>;
const d2Data = <?= json_encode($d2) ?>;

// Build visual size comparison
(function() {
  const viz = document.getElementById('size-viz');
  if (!viz) return;
  const maxH = d1Data.height > d2Data.height ? d1Data.height : d2Data.height;
  const scale = 180 / maxH;
  [d1Data, d2Data].forEach((d, i) => {
    const w = Math.round(d.width * scale);
    const h = Math.round(d.height * scale);
    const sw = Math.round((d.width - 8) * scale);
    const sh = Math.round((d.height - 12) * scale);
    const wrapper = document.createElement('div');
    wrapper.style.cssText = `display:flex;flex-direction:column;align-items:center`;
    const phone = document.createElement('div');
    phone.className = `size-phone phone${i+1}`;
    phone.style.cssText = `width:${w}px;height:${h}px;border-radius:${Math.round(16*scale)}px`;
    const screen = document.createElement('div');
    screen.className = 'size-phone-screen';
    phone.appendChild(screen);
    wrapper.appendChild(phone);
    viz.appendChild(wrapper);
  });
})();

// Toggle: show only differences
let showDiffOnly = false;
function toggleDiff() {
  showDiffOnly = !showDiffOnly;
  const btn = document.getElementById('btn-diff');
  btn.classList.toggle('active', showDiffOnly);
  document.querySelectorAll('.spec-row').forEach(row => {
    if (showDiffOnly && row.dataset.same === '1') row.classList.add('hidden');
    else row.classList.remove('hidden');
  });
  App.toast(showDiffOnly ? '🔍 Hanya perbedaan ditampilkan' : 'Semua spesifikasi ditampilkan');
}

// Toggle winner highlight
let showWinner = true;
function toggleWinner() {
  showWinner = !showWinner;
  const btn = document.getElementById('btn-winner');
  btn.classList.toggle('active', showWinner);
  document.querySelectorAll('td.winner').forEach(td => {
    td.style.background = showWinner ? '' : 'transparent';
    td.style.borderLeft = showWinner ? '' : 'none';
  });
}

// Share comparison
function shareComparison() {
  const url = window.location.href;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => App.toast('🔗 Link disalin ke clipboard!', 'success'));
  } else {
    prompt('Salin link berikut:', url);
  }
}

// Save comparison (requires login)
async function saveComparison() {
  try {
    const res = await fetch('api.php?action=save_comparison', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=save_comparison&device1_id=${d1Data.id}&device2_id=${d2Data.id}&label=${encodeURIComponent(d1Data.name + ' vs ' + d2Data.name)}`
    });
    const data = await res.json();
    if (data.token) App.toast('💾 Perbandingan disimpan!', 'success');
    else App.toast(data.error || 'Gagal menyimpan', 'error');
  } catch(e) {
    App.toast('Gagal menyimpan', 'error');
  }
}
</script>

<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
