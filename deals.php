<?php
// deals.php — Halaman Promo Publik
session_start();
require_once 'includes/db.php';

$page_title = 'Promo & Flash Sale';
$conn = getDB();

$promos_res = mysqli_query($conn, "SELECT * FROM promos WHERE is_active=1 AND start_date<=CURDATE() AND end_date>=CURDATE() ORDER BY created_at DESC");
$promos = [];
if ($promos_res) while ($r = mysqli_fetch_assoc($promos_res)) $promos[] = $r;

require_once 'includes/header.php';
?>

<div style="max-width:960px;margin:0 auto;padding:32px 24px">
  <div style="margin-bottom:28px">
    <h1 style="font-size:28px;font-weight:800">🔥 Promo & Flash Sale</h1>
    <p style="font-size:14px;color:var(--text3);margin-top:6px">Kode promo aktif — salin dan gunakan saat checkout</p>
  </div>

  <?php if (empty($promos)): ?>
  <div style="text-align:center;padding:80px 24px;color:var(--text3)">
    <div style="font-size:48px;margin-bottom:16px">🎁</div>
    <div style="font-size:18px;font-weight:600;margin-bottom:8px">Belum ada promo aktif</div>
    <div style="font-size:14px">Cek kembali nanti ya!</div>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    <?php foreach ($promos as $promo):
      $days_left = (int)ceil((strtotime($promo['end_date']) - time()) / 86400);
      $is_percent = $promo['discount_type'] === 'percent';
      $discount_label = $is_percent ? $promo['discount_value'].'%' : formatPrice($promo['discount_value']);
      $usage_pct = $promo['usage_limit'] ? round(($promo['used_count'] / $promo['usage_limit']) * 100) : 0;
    ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
      <!-- Header gradient strip -->
      <div style="background:linear-gradient(135deg,rgba(0,212,255,.15),rgba(168,85,247,.15));padding:16px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <span style="background:linear-gradient(135deg,var(--accent),var(--purple));color:#000;font-size:12px;font-weight:800;padding:3px 12px;border-radius:5px">HEMAT <?= $discount_label ?></span>
          <span style="font-size:12px;color:var(--text3)">⏰ <?= $days_left ?> hari lagi</span>
        </div>
        <div style="font-size:17px;font-weight:800;color:var(--text)"><?= htmlspecialchars($promo['title']) ?></div>
      </div>
      <div style="padding:16px 20px">
        <?php if ($promo['description']): ?>
        <p style="font-size:13px;color:var(--text2);margin-bottom:14px;line-height:1.6"><?= htmlspecialchars($promo['description']) ?></p>
        <?php endif; ?>

        <!-- Syarat -->
        <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:16px">
          <?php if ($promo['min_purchase'] > 0): ?>
          <div style="font-size:12px;color:var(--text3)">✓ Min. pembelian <?= formatPrice($promo['min_purchase']) ?></div>
          <?php endif; ?>
          <?php if ($promo['max_discount']): ?>
          <div style="font-size:12px;color:var(--text3)">✓ Maks. potongan <?= formatPrice($promo['max_discount']) ?></div>
          <?php endif; ?>
          <?php if ($promo['category'] !== 'all'): ?>
          <div style="font-size:12px;color:var(--text3)">✓ Berlaku untuk kategori <strong><?= ucfirst($promo['category']) ?></strong></div>
          <?php endif; ?>
          <div style="font-size:12px;color:var(--text3)">✓ Berlaku s/d <?= date('d M Y', strtotime($promo['end_date'])) ?></div>
        </div>

        <?php if ($promo['usage_limit']): ?>
        <!-- Usage bar -->
        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3);margin-bottom:5px">
            <span>Tersisa <?= $promo['usage_limit'] - $promo['used_count'] ?> kuota</span>
            <span><?= $usage_pct ?>% terpakai</span>
          </div>
          <div style="height:5px;background:var(--bg3);border-radius:3px;overflow:hidden">
            <div style="height:100%;width:<?= $usage_pct ?>%;background:linear-gradient(90deg,var(--accent),var(--purple));border-radius:3px"></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Promo code copy -->
        <div style="display:flex;align-items:center;gap:8px">
          <div id="code-<?= $promo['id'] ?>" style="flex:1;background:var(--bg3);border:1px dashed rgba(0,212,255,.4);padding:9px 14px;border-radius:8px;font-family:monospace;font-size:16px;font-weight:800;color:var(--accent);letter-spacing:1.5px;text-align:center"><?= htmlspecialchars($promo['promo_code']) ?></div>
          <button onclick="copyPromo('<?= htmlspecialchars($promo['promo_code']) ?>', this)" style="padding:9px 16px;border-radius:8px;background:var(--accent);color:#000;border:none;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">Salin Kode</button>
        </div>

        <a href="catalog.php" style="display:block;text-align:center;margin-top:12px;font-size:13px;color:var(--text2);text-decoration:none;padding:8px;border-radius:8px;border:1px solid var(--border)" onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text2)'">Belanja sekarang →</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function copyPromo(code, btn) {
  navigator.clipboard.writeText(code).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓ Tersalin!';
    btn.style.background = 'var(--green)';
    setTimeout(() => { btn.textContent = orig; btn.style.background = 'var(--accent)'; }, 2000);
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
