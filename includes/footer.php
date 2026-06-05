<?php // includes/footer.php ?>

<footer style="border-top:1px solid var(--border);padding:40px 24px;margin-top:40px;">
  <div style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:32px">
    <div>
      <div class="logo" style="margin-bottom:12px;font-size:20px">Spec<span style="-webkit-text-fill-color:var(--text2)">Sync</span></div>
      <p style="font-size:13px;color:var(--text3);line-height:1.7">Platform perbandingan dan pembelian smartphone terpercaya. Data spesifikasi akurat, ulasan nyata.</p>
    </div>
    <div>
      <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px">Produk</div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <a href="catalog.php" style="font-size:13px;color:var(--text2)">Katalog HP</a>
        <a href="compare.php" style="font-size:13px;color:var(--text2)">Bandingkan</a>
        <a href="deals.php" style="font-size:13px;color:var(--text2)">🔥 Promo Hari Ini</a>
      </div>
    </div>
    <div>
      <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px">Akun</div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <a href="login.php" style="font-size:13px;color:var(--text2)">Masuk</a>
        <a href="register.php" style="font-size:13px;color:var(--text2)">Daftar</a>
        <a href="dashboard.php" style="font-size:13px;color:var(--text2)">Dashboard</a>
        <a href="orders.php" style="font-size:13px;color:var(--text2)">Pesanan Saya</a>
      </div>
    </div>
    <div>
      <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px">Tentang</div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <a href="#" style="font-size:13px;color:var(--text2)">Tentang SpecSync</a>
        <a href="#" style="font-size:13px;color:var(--text2)">Kebijakan Privasi</a>
        <a href="#" style="font-size:13px;color:var(--text2)">Kontak</a>
      </div>
    </div>
  </div>
  <div style="max-width:1280px;margin:32px auto 0;padding-top:24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <p style="font-size:12px;color:var(--text3)">© 2025 SpecSync. Data spesifikasi untuk keperluan edukasi.</p>
    <p style="font-size:12px;color:var(--text3)">Dibuat dengan ♥ untuk pengguna Indonesia</p>
  </div>
</footer>

<script src="assets/js/app.js"></script>
<script>
  // Initialize wishlist button states on page load
  document.addEventListener('DOMContentLoaded', () => App.initWishlistButtons());
</script>
</body>
</html>
