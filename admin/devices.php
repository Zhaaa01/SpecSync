<?php
// admin/devices.php — Kelola Perangkat
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn   = getDB();
$action = $_GET['action'] ?? 'list';
$id     = intval($_GET['id'] ?? 0);
$page_title = 'Perangkat';
$active_nav = 'devices';
$flash = '';

// ── DELETE ──────────────────────────────────────────────
if ($action === 'delete' && $id) {
    mysqli_query($conn, "UPDATE devices SET is_active=0 WHERE id=$id");
    header('Location: devices.php?flash=deleted'); exit;
}

// ── RESTORE ─────────────────────────────────────────────
if ($action === 'restore' && $id) {
    mysqli_query($conn, "UPDATE devices SET is_active=1 WHERE id=$id");
    header('Location: devices.php?flash=restored'); exit;
}

// ── SAVE (add / edit) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add','edit'])) {
    $f = []; // collected fields
    $str_fields = ['name','brand','slug','image','category','resolution','display_type','chipset','gpu','camera_features',
                   'wifi_version','bluetooth_version','os','os_version','color_options'];
    $int_fields = ['release_year','refresh_rate','ram','storage','main_camera','front_camera','battery','charging_speed',
                   'weight','cpu_cores','network_5g','nfc','has_wireless_charging'];
    $float_fields = ['price','screen_size','thickness','width','height','score_camera','score_performance','score_battery','score_design'];

    foreach ($str_fields as $fld) $f[$fld] = sanitize($_POST[$fld] ?? '');
    foreach ($int_fields as $fld) $f[$fld] = intval($_POST[$fld] ?? 0);
    foreach ($float_fields as $fld) $f[$fld] = floatval(str_replace(',','.',($_POST[$fld] ?? 0)));

    if (!$f['slug']) $f['slug'] = strtolower(preg_replace('/[^a-z0-9]+/','-', $f['name']));

    $cols = array_map(fn($k) => "$k='". (is_float($f[$k]) ? $f[$k] : (is_int($f[$k]) ? $f[$k] : $f[$k])) ."'", array_keys($f));
    // Build SET string safely
    $set = implode(',', array_map(function($k) use ($f) {
        $v = $f[$k];
        return "$k='$v'";
    }, array_keys($f)));

    if ($action === 'add') {
        mysqli_query($conn, "INSERT INTO devices SET $set, is_active=1");
        $new_id = mysqli_insert_id($conn);
        header('Location: devices.php?flash=added'); exit;
    } else {
        mysqli_query($conn, "UPDATE devices SET $set WHERE id=$id");
        header('Location: devices.php?flash=updated'); exit;
    }
}

// ── LOAD device for edit ─────────────────────────────────
$device = null;
if (in_array($action, ['edit']) && $id) {
    $r = mysqli_query($conn, "SELECT * FROM devices WHERE id=$id");
    $device = mysqli_fetch_assoc($r);
}

// ── LIST ────────────────────────────────────────────────
$search = sanitize($_GET['search'] ?? '');
$show_inactive = $_GET['inactive'] ?? 0;
$where_active = $show_inactive ? '' : 'WHERE is_active=1';
$where_search = $search ? ($show_inactive ? "WHERE " : "AND ") . "(name LIKE '%$search%' OR brand LIKE '%$search%')" : '';
$devices_list = mysqli_query($conn, "SELECT * FROM devices $where_active $where_search ORDER BY id DESC");

if ($_GET['flash'] ?? '') {
    $flash_map = ['added'=>'✓ Perangkat berhasil ditambahkan!','updated'=>'✓ Perangkat berhasil diupdate!','deleted'=>'Perangkat dinonaktifkan.','restored'=>'✓ Perangkat diaktifkan kembali.'];
    $flash = $flash_map[$_GET['flash']] ?? '';
}

include '_layout.php';

// ── FORM (add/edit) ──────────────────────────────────────
if (in_array($action, ['add','edit'])):
$d = $device ?? [];
$is_edit = $action === 'edit';
?>

<div style="max-width:900px">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="devices.php" class="btn-sm">← Kembali</a>
  <h2 style="font-size:18px;font-weight:700"><?= $is_edit ? 'Edit Perangkat: '.htmlspecialchars($d['name'] ?? '') : 'Tambah Perangkat Baru' ?></h2>
</div>

<form method="POST" style="display:flex;flex-direction:column;gap:0">

  <!-- Basic Info -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">📋 Informasi Dasar</div></div>
    <div style="padding:20px">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nama Perangkat *</label>
          <input class="form-input" name="name" value="<?= htmlspecialchars($d['name'] ?? '') ?>" placeholder="Samsung Galaxy S25 Ultra" required>
        </div>
        <div class="form-group">
          <label class="form-label">Merek *</label>
          <input class="form-input" name="brand" value="<?= htmlspecialchars($d['brand'] ?? '') ?>" placeholder="Samsung" required>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Slug URL (auto-generate jika kosong)</label>
          <input class="form-input" name="slug" value="<?= htmlspecialchars($d['slug'] ?? '') ?>" placeholder="samsung-galaxy-s25-ultra">
        </div>
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select class="form-select" name="category">
            <?php foreach (['flagship','midrange','budget','gaming'] as $cat): ?>
            <option value="<?= $cat ?>" <?= ($d['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Harga (Rp) *</label>
          <input class="form-input" name="price" type="number" value="<?= $d['price'] ?? '' ?>" placeholder="19999000" required>
        </div>
        <div class="form-group">
          <label class="form-label">Tahun Rilis</label>
          <input class="form-input" name="release_year" type="number" value="<?= $d['release_year'] ?? date('Y') ?>" placeholder="2025">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">URL Gambar</label>
        <input class="form-input" name="image" value="<?= htmlspecialchars($d['image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
      </div>
    </div>
  </div>

  <!-- Display -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">📱 Layar</div></div>
    <div style="padding:20px">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Ukuran (inci)</label>
          <input class="form-input" name="screen_size" type="number" step="0.01" value="<?= $d['screen_size'] ?? '' ?>" placeholder="6.9">
        </div>
        <div class="form-group">
          <label class="form-label">Resolusi</label>
          <input class="form-input" name="resolution" value="<?= htmlspecialchars($d['resolution'] ?? '') ?>" placeholder="3088x1440">
        </div>
        <div class="form-group">
          <label class="form-label">Refresh Rate (Hz)</label>
          <input class="form-input" name="refresh_rate" type="number" value="<?= $d['refresh_rate'] ?? '' ?>" placeholder="120">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Tipe Panel</label>
        <input class="form-input" name="display_type" value="<?= htmlspecialchars($d['display_type'] ?? '') ?>" placeholder="Dynamic AMOLED 2X">
      </div>
    </div>
  </div>

  <!-- Performance -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">⚡ Performa</div></div>
    <div style="padding:20px">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Chipset</label>
          <input class="form-input" name="chipset" value="<?= htmlspecialchars($d['chipset'] ?? '') ?>" placeholder="Snapdragon 8 Elite">
        </div>
        <div class="form-group">
          <label class="form-label">GPU</label>
          <input class="form-input" name="gpu" value="<?= htmlspecialchars($d['gpu'] ?? '') ?>" placeholder="Adreno 830">
        </div>
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">CPU Cores</label>
          <input class="form-input" name="cpu_cores" type="number" value="<?= $d['cpu_cores'] ?? '' ?>" placeholder="8">
        </div>
        <div class="form-group">
          <label class="form-label">RAM (GB)</label>
          <input class="form-input" name="ram" type="number" value="<?= $d['ram'] ?? '' ?>" placeholder="12">
        </div>
        <div class="form-group">
          <label class="form-label">Storage (GB)</label>
          <input class="form-input" name="storage" type="number" value="<?= $d['storage'] ?? '' ?>" placeholder="256">
        </div>
      </div>
    </div>
  </div>

  <!-- Camera -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">📸 Kamera</div></div>
    <div style="padding:20px">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Kamera Utama (MP)</label>
          <input class="form-input" name="main_camera" type="number" value="<?= $d['main_camera'] ?? '' ?>" placeholder="200">
        </div>
        <div class="form-group">
          <label class="form-label">Kamera Depan (MP)</label>
          <input class="form-input" name="front_camera" type="number" value="<?= $d['front_camera'] ?? '' ?>" placeholder="12">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Fitur Kamera</label>
        <input class="form-input" name="camera_features" value="<?= htmlspecialchars($d['camera_features'] ?? '') ?>" placeholder="OIS, 100x Space Zoom, Night Mode">
      </div>
    </div>
  </div>

  <!-- Battery -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">🔋 Baterai</div></div>
    <div style="padding:20px">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Kapasitas (mAh)</label>
          <input class="form-input" name="battery" type="number" value="<?= $d['battery'] ?? '' ?>" placeholder="5000">
        </div>
        <div class="form-group">
          <label class="form-label">Kecepatan Charge (W)</label>
          <input class="form-input" name="charging_speed" type="number" value="<?= $d['charging_speed'] ?? '' ?>" placeholder="45">
        </div>
        <div class="form-group">
          <label class="form-label">Wireless Charging</label>
          <select class="form-select" name="has_wireless_charging">
            <option value="0" <?= empty($d['has_wireless_charging']) ? 'selected' : '' ?>>Tidak</option>
            <option value="1" <?= !empty($d['has_wireless_charging']) ? 'selected' : '' ?>>Ya</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Design -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">✨ Desain & Dimensi</div></div>
    <div style="padding:20px">
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px">
        <?php foreach ([['Berat (g)','weight'],['Tebal (mm)','thickness'],['Lebar (mm)','width'],['Tinggi (mm)','height']] as [$lbl,$fld]): ?>
        <div class="form-group">
          <label class="form-label"><?= $lbl ?></label>
          <input class="form-input" name="<?= $fld ?>" type="number" step="0.01" value="<?= $d[$fld] ?? '' ?>">
        </div>
        <?php endforeach; ?>
        <div class="form-group">
          <label class="form-label">NFC</label>
          <select class="form-select" name="nfc">
            <option value="0" <?= empty($d['nfc']) ? 'selected' : '' ?>>Tidak</option>
            <option value="1" <?= !empty($d['nfc']) ? 'selected' : '' ?>>Ya</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Connectivity & OS -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">📡 Konektivitas & OS</div></div>
    <div style="padding:20px">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">5G</label>
          <select class="form-select" name="network_5g">
            <option value="0" <?= empty($d['network_5g']) ? 'selected' : '' ?>>Tidak</option>
            <option value="1" <?= !empty($d['network_5g']) ? 'selected' : '' ?>>Ya</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Versi WiFi</label>
          <input class="form-input" name="wifi_version" value="<?= htmlspecialchars($d['wifi_version'] ?? '') ?>" placeholder="Wi-Fi 7">
        </div>
        <div class="form-group">
          <label class="form-label">Bluetooth</label>
          <input class="form-input" name="bluetooth_version" value="<?= htmlspecialchars($d['bluetooth_version'] ?? '') ?>" placeholder="5.4">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Sistem Operasi</label>
          <input class="form-input" name="os" value="<?= htmlspecialchars($d['os'] ?? '') ?>" placeholder="Android">
        </div>
        <div class="form-group">
          <label class="form-label">Versi OS</label>
          <input class="form-input" name="os_version" value="<?= htmlspecialchars($d['os_version'] ?? '') ?>" placeholder="15">
        </div>
      </div>
    </div>
  </div>

  <!-- Scores -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-header"><div class="card-title">⭐ Skor SpecSync (1–10)</div></div>
    <div style="padding:20px">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
        <?php foreach ([['Kamera','score_camera'],['Performa','score_performance'],['Baterai','score_battery'],['Desain','score_design']] as [$lbl,$fld]): ?>
        <div class="form-group">
          <label class="form-label"><?= $lbl ?></label>
          <input class="form-input" name="<?= $fld ?>" type="number" step="0.1" min="0" max="10" value="<?= $d[$fld] ?? '' ?>" placeholder="8.5">
        </div>
        <?php endforeach; ?>
      </div>
      <div style="font-size:12px;color:#8b949e;margin-top:4px">💡 Biarkan kosong untuk auto-kalkulasi berdasarkan spesifikasi</div>
    </div>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn-primary" style="padding:12px 28px;font-size:14px"><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Perangkat' ?></button>
    <a href="devices.php" class="btn-sm" style="padding:12px 20px">Batal</a>
  </div>
</form>
</div>

<?php else: // ── LIST VIEW ── ?>

<?php if ($flash): ?>
<div class="flash-msg" style="padding:12px 16px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);border-radius:8px;color:#3fb950;font-size:13px;margin-bottom:20px"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;flex:1;min-width:240px">
    <input class="form-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama/merek..." style="max-width:300px">
    <button type="submit" class="btn-sm">Cari</button>
    <?php if ($search): ?><a href="devices.php" class="btn-sm">✕ Reset</a><?php endif; ?>
  </form>
  <a href="?action=add" class="btn-primary">+ Tambah Perangkat</a>
  <a href="?inactive=1" class="btn-sm">Lihat Nonaktif</a>
</div>

<div class="card">
  <table>
    <thead><tr>
      <th style="width:50px">ID</th>
      <th>Perangkat</th>
      <th>Kategori</th>
      <th>Harga</th>
      <th>Skor</th>
      <th>Status</th>
      <th style="width:140px">Aksi</th>
    </tr></thead>
    <tbody>
    <?php while ($d = mysqli_fetch_assoc($devices_list)):
      $overall = round(($d['score_camera'] + $d['score_performance'] + $d['score_battery'] + $d['score_design']) / 4, 1);
    ?>
    <tr>
      <td style="color:#8b949e;font-family:monospace"><?= $d['id'] ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <img src="<?= htmlspecialchars($d['image'] ?? '') ?>" style="width:40px;height:40px;object-fit:contain;background:#21262d;border-radius:6px;padding:3px" onerror="this.src='https://via.placeholder.com/40?text=📱'">
          <div>
            <div style="font-weight:600"><?= htmlspecialchars($d['name']) ?></div>
            <div style="font-size:11px;color:#8b949e"><?= htmlspecialchars($d['brand']) ?> · <?= $d['release_year'] ?></div>
          </div>
        </div>
      </td>
      <td><span class="status-badge" style="background:rgba(0,212,255,.1);color:#00d4ff"><?= ucfirst($d['category']) ?></span></td>
      <td style="font-weight:700"><?= formatPrice($d['price']) ?></td>
      <td style="font-weight:700;color:#00d4ff"><?= $overall ?>/10</td>
      <td>
        <span class="status-badge <?= $d['is_active'] ? 'status-active' : 'status-cancelled' ?>"><?= $d['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="?action=edit&id=<?= $d['id'] ?>" class="btn-sm">Edit</a>
          <?php if ($d['is_active']): ?>
          <a href="?action=delete&id=<?= $d['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Nonaktifkan perangkat ini?')">Nonaktif</a>
          <?php else: ?>
          <a href="?action=restore&id=<?= $d['id'] ?>" class="btn-sm btn-success">Aktifkan</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php include '_layout_end.php'; ?>