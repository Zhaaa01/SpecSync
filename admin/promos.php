<?php
// admin/promos.php — Kelola Promo
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$conn   = getDB();
$action = $_GET['action'] ?? 'list';
$id     = intval($_GET['id'] ?? 0);
$page_title = 'Promo & Deals';
$active_nav = 'promos';
$flash = '';

// ── DELETE ─────────────────────────────────
if ($action === 'delete' && $id) {
    mysqli_query($conn, "DELETE FROM promos WHERE id=$id");
    header('Location: promos.php?flash=deleted'); exit;
}

// ── TOGGLE ACTIVE ──────────────────────────
if ($action === 'toggle' && $id) {
    mysqli_query($conn, "UPDATE promos SET is_active = NOT is_active WHERE id=$id");
    header('Location: promos.php?flash=updated'); exit;
}

// ── SAVE ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = currentAdmin();
    $title         = sanitize($_POST['title'] ?? '');
    $description   = sanitize($_POST['description'] ?? '');
    $promo_code    = strtoupper(sanitize($_POST['promo_code'] ?? ''));
    $discount_type = in_array($_POST['discount_type'] ?? '', ['percent','fixed']) ? $_POST['discount_type'] : 'percent';
    $discount_val  = floatval($_POST['discount_value'] ?? 0);
    $min_purchase  = intval($_POST['min_purchase'] ?? 0);
    $max_discount  = $_POST['max_discount'] ? intval($_POST['max_discount']) : 'NULL';
    $category      = sanitize($_POST['category'] ?? 'all');
    $start_date    = sanitize($_POST['start_date'] ?? date('Y-m-d'));
    $end_date      = sanitize($_POST['end_date'] ?? date('Y-m-d'));
    $usage_limit   = $_POST['usage_limit'] ? intval($_POST['usage_limit']) : 'NULL';
    $is_active     = intval($_POST['is_active'] ?? 1);
    $device_id     = intval($_POST['device_id'] ?? 0) ?: 'NULL';
    $banner_image  = sanitize($_POST['banner_image'] ?? '');

    $max_d_str = is_numeric($max_discount) ? $max_discount : 'NULL';
    $u_lim_str = is_numeric($usage_limit) ? $usage_limit : 'NULL';
    $dev_str   = is_numeric($device_id) ? $device_id : 'NULL';

    if ($action === 'edit' && $id) {
        mysqli_query($conn, "UPDATE promos SET title='$title',description='$description',promo_code='$promo_code',
            discount_type='$discount_type',discount_value=$discount_val,min_purchase=$min_purchase,
            max_discount=$max_d_str,category='$category',start_date='$start_date',end_date='$end_date',
            usage_limit=$u_lim_str,is_active=$is_active,device_id=$dev_str,banner_image='$banner_image'
            WHERE id=$id");
    } else {
        mysqli_query($conn, "INSERT INTO promos (title,description,promo_code,discount_type,discount_value,
            min_purchase,max_discount,category,start_date,end_date,usage_limit,is_active,device_id,banner_image,created_by)
            VALUES ('$title','$description','$promo_code','$discount_type',$discount_val,$min_purchase,
            $max_d_str,'$category','$start_date','$end_date',$u_lim_str,$is_active,$dev_str,'$banner_image',{$admin['id']})");
    }
    header('Location: promos.php?flash=' . ($action === 'edit' ? 'updated' : 'added'));
    exit;
}

// ── LOAD for edit ──────────────────────────
$promo = null;
if ($action === 'edit' && $id) {
    $promo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM promos WHERE id=$id"));
}

$promos_list = mysqli_query($conn, "SELECT p.*, a.name as created_by_name FROM promos p JOIN admins a ON p.created_by=a.id ORDER BY p.created_at DESC");

$flash_map = ['added'=>'✓ Promo berhasil dibuat!','updated'=>'✓ Promo berhasil diupdate!','deleted'=>'Promo dihapus.'];
$flash = $flash_map[$_GET['flash'] ?? ''] ?? '';

include '_layout.php';

if (in_array($action, ['add','edit'])):
$pr = $promo ?? [];
?>

<div style="max-width:700px">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="promos.php" class="btn-sm">← Kembali</a>
  <h2 style="font-size:18px;font-weight:700"><?= $action === 'edit' ? 'Edit Promo' : 'Buat Promo Baru' ?></h2>
</div>

<form method="POST">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><div class="card-title">🏷️ Detail Promo</div></div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:4px">
      <div class="form-group">
        <label class="form-label">Judul Promo *</label>
        <input class="form-input" name="title" value="<?= htmlspecialchars($pr['title'] ?? '') ?>" placeholder="Flash Sale Flagship 10%" required>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea class="form-textarea" name="description" placeholder="Deskripsi singkat promo..."><?= htmlspecialchars($pr['description'] ?? '') ?></textarea>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Kode Promo</label>
          <input class="form-input" name="promo_code" value="<?= htmlspecialchars($pr['promo_code'] ?? '') ?>" placeholder="FLAGSHIP10" style="font-family:monospace;text-transform:uppercase">
        </div>
        <div class="form-group">
          <label class="form-label">Berlaku untuk Kategori</label>
          <select class="form-select" name="category">
            <?php foreach (['all'=>'Semua','flagship'=>'Flagship','midrange'=>'Midrange','budget'=>'Budget','gaming'=>'Gaming'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($pr['category'] ?? 'all') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Tipe Diskon</label>
          <select class="form-select" name="discount_type" onchange="updateDiscountLabel(this.value)">
            <option value="percent" <?= ($pr['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>Persen (%)</option>
            <option value="fixed" <?= ($pr['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Nominal Tetap (Rp)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="discount-label">Nilai Diskon</label>
          <input class="form-input" name="discount_value" type="number" step="0.01" value="<?= $pr['discount_value'] ?? '' ?>" placeholder="10" required>
        </div>
      </div>
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Min. Pembelian (Rp)</label>
          <input class="form-input" name="min_purchase" type="number" value="<?= $pr['min_purchase'] ?? 0 ?>" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Maks. Diskon (Rp, opsional)</label>
          <input class="form-input" name="max_discount" type="number" value="<?= $pr['max_discount'] ?? '' ?>" placeholder="Kosongkan = tak terbatas">
        </div>
        <div class="form-group">
          <label class="form-label">Limit Penggunaan</label>
          <input class="form-input" name="usage_limit" type="number" value="<?= $pr['usage_limit'] ?? '' ?>" placeholder="Kosongkan = tak terbatas">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Tanggal Mulai</label>
          <input class="form-input" name="start_date" type="date" value="<?= $pr['start_date'] ?? date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Berakhir</label>
          <input class="form-input" name="end_date" type="date" value="<?= $pr['end_date'] ?? '' ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Banner Image URL (opsional)</label>
        <input class="form-input" name="banner_image" value="<?= htmlspecialchars($pr['banner_image'] ?? '') ?>" placeholder="https://...">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-select" name="is_active">
          <option value="1" <?= ($pr['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
          <option value="0" <?= ($pr['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px">
    <button type="submit" class="btn-primary" style="padding:12px 28px;font-size:14px"><?= $action === 'edit' ? 'Simpan' : 'Buat Promo' ?></button>
    <a href="promos.php" class="btn-sm" style="padding:12px 20px">Batal</a>
  </div>
</form>
</div>

<script>
function updateDiscountLabel(type) {
  document.getElementById('discount-label').textContent = type === 'percent' ? 'Nilai Diskon (%)' : 'Nilai Diskon (Rp)';
}
</script>

<?php else: // LIST ?>

<?php if ($flash): ?>
<div class="flash-msg" style="padding:12px 16px;background:rgba(63,185,80,.1);border:1px solid rgba(63,185,80,.2);border-radius:8px;color:#3fb950;font-size:13px;margin-bottom:20px"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <a href="?action=add" class="btn-primary">+ Buat Promo Baru</a>
</div>

<div class="card">
  <table>
    <thead><tr>
      <th>Promo</th><th>Kode</th><th>Diskon</th><th>Berlaku</th><th>Penggunaan</th><th>Status</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php while ($p = mysqli_fetch_assoc($promos_list)):
      $is_expired = strtotime($p['end_date']) < time();
      $disc = $p['discount_type'] === 'percent' ? $p['discount_value'].'%' : formatPrice($p['discount_value']);
    ?>
    <tr>
      <td>
        <div style="font-weight:600"><?= htmlspecialchars($p['title']) ?></div>
        <div style="font-size:11px;color:#8b949e"><?= ucfirst($p['category']) ?> · by <?= htmlspecialchars($p['created_by_name']) ?></div>
      </td>
      <td><code style="background:#21262d;padding:3px 8px;border-radius:5px;font-size:12px"><?= htmlspecialchars($p['promo_code'] ?: '—') ?></code></td>
      <td style="font-weight:700;color:#3fb950"><?= $disc ?></td>
      <td style="font-size:12px;color:#8b949e">
        <?= date('d M Y', strtotime($p['start_date'])) ?><br>
        s/d <?= date('d M Y', strtotime($p['end_date'])) ?>
        <?php if ($is_expired): ?><div style="color:#f85149;font-size:11px">✕ Kedaluwarsa</div><?php endif; ?>
      </td>
      <td style="font-size:13px"><?= $p['used_count'] ?> / <?= $p['usage_limit'] ?: '∞' ?></td>
      <td><span class="status-badge <?= $p['is_active'] && !$is_expired ? 'status-active' : 'status-inactive' ?>"><?= $p['is_active'] && !$is_expired ? 'Aktif' : 'Nonaktif' ?></span></td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-sm">Edit</a>
          <a href="?action=toggle&id=<?= $p['id'] ?>" class="btn-sm <?= $p['is_active'] ? 'btn-warning' : 'btn-success' ?>"><?= $p['is_active'] ? 'Nonaktif' : 'Aktif' ?></a>
          <a href="?action=delete&id=<?= $p['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus promo ini?')">Hapus</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>
<?php include '_layout_end.php'; ?>