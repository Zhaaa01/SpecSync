<?php
session_start();
require_once 'includes/db.php';
$page_title = 'Katalog Smartphone';
$conn = getDB();

// Get brands for filter
$brands_res = mysqli_query($conn, "SELECT DISTINCT brand FROM devices WHERE is_active=1 ORDER BY brand");
$brands = [];
while ($r = mysqli_fetch_assoc($brands_res)) $brands[] = $r['brand'];

// Stats
$total = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM devices WHERE is_active=1"))[0];
?>
<?php require_once 'includes/header.php'; ?>

<div style="max-width:1280px;margin:0 auto;padding:32px 24px">

  <div style="margin-bottom:28px">
    <h1 style="font-size:28px;font-weight:800">Katalog Smartphone</h1>
    <p style="color:var(--text2);margin-top:6px"><?= $total ?> perangkat dari <?= count($brands) ?> merek terkemuka</p>
  </div>

  <div style="display:grid;grid-template-columns:220px 1fr;gap:24px" id="catalog-layout">

    <!-- Sidebar Filters -->
    <aside style="height:fit-content;position:sticky;top:80px">
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
          <div style="font-size:15px;font-weight:700">Filter</div>
          <button onclick="resetFilters()" style="font-size:12px;color:var(--accent);background:none;border:none;cursor:pointer;font-weight:600">Reset</button>
        </div>

        <!-- Price Range -->
        <div style="margin-bottom:20px">
          <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px">Harga</div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <?php
            $price_ranges = [
              ['Semua', '0', '99999999'],
              ['< Rp 3 Juta', '0', '2999999'],
              ['Rp 3–7 Juta', '3000000', '6999999'],
              ['Rp 7–15 Juta', '7000000', '14999999'],
              ['Rp 15–25 Juta', '15000000', '24999999'],
              ['> Rp 25 Juta', '25000000', '99999999'],
            ];
            foreach ($price_ranges as [$label, $min, $max]):
            ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:5px 0">
              <input type="radio" name="price_range" value="<?= $min ?>_<?= $max ?>" onchange="applyFilters()" <?= ($min === '0' && $max === '99999999') ? 'checked' : '' ?>
                style="accent-color:var(--accent);cursor:pointer">
              <span style="font-size:13px;color:var(--text2)"><?= $label ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Brand -->
        <div style="margin-bottom:20px">
          <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px">Merek</div>
          <div style="display:flex;flex-direction:column;gap:4px;max-height:200px;overflow-y:auto">
            <?php foreach ($brands as $brand): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
              <input type="checkbox" name="brand_filter" value="<?= htmlspecialchars($brand) ?>" onchange="applyFilters()" style="accent-color:var(--accent);cursor:pointer">
              <span style="font-size:13px;color:var(--text2)"><?= htmlspecialchars($brand) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Features -->
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px">Fitur</div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
              <input type="checkbox" id="filter-5g" onchange="applyFilters()" style="accent-color:var(--accent)">
              <span style="font-size:13px;color:var(--text2)">5G</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
              <input type="checkbox" id="filter-wireless" onchange="applyFilters()" style="accent-color:var(--accent)">
              <span style="font-size:13px;color:var(--text2)">Wireless Charging</span>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:4px 0">
              <input type="checkbox" id="filter-nfc" onchange="applyFilters()" style="accent-color:var(--accent)">
              <span style="font-size:13px;color:var(--text2)">NFC</span>
            </label>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div>
      <!-- Sort & view bar -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
        <div id="catalog-count" style="font-size:14px;color:var(--text2)">Memuat...</div>
        <div style="display:flex;align-items:center;gap:10px">
          <label style="font-size:13px;color:var(--text3)">Urutkan:</label>
          <select id="catalog-sort" onchange="applyFilters()" style="padding:7px 12px;border-radius:8px;background:var(--bg2);border:1px solid var(--border2);color:var(--text);font-size:13px;cursor:pointer">
            <option value="score_performance">Performa Terbaik</option>
            <option value="price_asc">Harga ↑</option>
            <option value="price_desc">Harga ↓</option>
            <option value="score_camera">Kamera Terbaik</option>
            <option value="score_battery">Baterai Terbaik</option>
            <option value="newest">Terbaru</option>
          </select>
        </div>
      </div>

      <!-- Persona quick filters -->
      <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
        <button class="persona-btn active" data-persona="" onclick="setPersona(this,'')">Semua</button>
        <button class="persona-btn" data-persona="gaming" onclick="setPersona(this,'gaming')">🎮 Gaming</button>
        <button class="persona-btn" data-persona="photo" onclick="setPersona(this,'photo')">📸 Foto</button>
        <button class="persona-btn" data-persona="battery" onclick="setPersona(this,'battery')">🔋 Baterai</button>
        <button class="persona-btn" data-persona="budget" onclick="setPersona(this,'budget')">💰 Budget</button>
      </div>

      <!-- Device grid -->
      <div class="devices-grid" id="catalog-grid">
        <div style="grid-column:1/-1;text-align:center;padding:64px;color:var(--text3)">
          <div style="font-size:40px;margin-bottom:12px">⚙️</div>
          <div>Memuat katalog...</div>
        </div>
      </div>

      <!-- Pagination -->
      <div id="pagination-wrap" style="display:none;margin-top:32px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div id="pagination-info" style="font-size:13px;color:var(--text3)"></div>
          <div id="pagination-controls" style="display:flex;align-items:center;gap:6px"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width: 768px) {
  #catalog-layout { grid-template-columns: 1fr !important; }
  aside { position: static !important; }
}
</style>

<style>
.pg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 8px;
  border-radius: 8px;
  border: 1px solid var(--border2);
  background: var(--bg2);
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
}
.pg-btn:hover:not(:disabled) {
  border-color: var(--accent);
  color: var(--accent);
}
.pg-btn.active {
  background: var(--accent);
  border-color: var(--accent);
  color: #000;
}
.pg-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.pg-ellipsis {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: var(--text3);
  font-size: 13px;
}
</style>

<script>
let currentPersona = '';
let allDevices = [];
let currentPage = 1;
const PAGE_SIZE = 12;

function totalPages() {
  return Math.ceil(allDevices.length / PAGE_SIZE);
}

async function applyFilters() {
  const sort = document.getElementById('catalog-sort').value;
  const priceRange = document.querySelector('input[name="price_range"]:checked')?.value || '0_99999999';
  const [minP, maxP] = priceRange.split('_');
  const brands = [...document.querySelectorAll('input[name="brand_filter"]:checked')].map(e => e.value);
  const only5g = document.getElementById('filter-5g').checked;

  let url = `api.php?action=catalog&sort=${sort}&min_price=${minP}&max_price=${maxP}`;
  if (currentPersona) url += `&persona=${currentPersona}`;

  const grid = document.getElementById('catalog-grid');
  grid.innerHTML = '<div style="grid-column:1/-1">' + Array(6).fill('<div class="skeleton loading-card"></div>').join('') + '</div>';

  try {
    const res = await fetch(url);
    allDevices = await res.json();

    // Client-side brand & feature filter
    if (brands.length) allDevices = allDevices.filter(d => brands.includes(d.brand));
    if (only5g) allDevices = allDevices.filter(d => d.network_5g == 1);

    currentPage = 1;
    renderPage();
  } catch(e) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--text3)">Gagal memuat data</div>';
  }
}

function renderPage() {
  const grid = document.getElementById('catalog-grid');

  if (!allDevices.length) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:64px;color:var(--text3)"><div style="font-size:48px;margin-bottom:16px">🔍</div><div style="font-size:16px;font-weight:600">Tidak ada HP yang cocok</div><button onclick="resetFilters()" style="margin-top:16px;padding:10px 20px;border-radius:8px;background:var(--accent);border:none;color:#000;font-weight:600;cursor:pointer">Reset Filter</button></div>`;
    document.getElementById('catalog-count').innerHTML = '<strong>0</strong> perangkat ditemukan';
    document.getElementById('pagination-wrap').style.display = 'none';
    return;
  }

  const total = allDevices.length;
  const pages = totalPages();
  const start = (currentPage - 1) * PAGE_SIZE;
  const end = Math.min(start + PAGE_SIZE, total);
  const pageDevices = allDevices.slice(start, end);

  const wishlist = JSON.parse(localStorage.getItem('ss_wishlist') || '[]');
  const compareList = JSON.parse(localStorage.getItem('ss_compare') || '[]');

  grid.innerHTML = pageDevices.map(d => `
    <div class="device-card">
      <div class="card-image-wrap">
        <img class="card-image" src="${d.image||''}" alt="${d.name}" loading="lazy" onerror="this.src='https://via.placeholder.com/200x200/21262d/666?text=${encodeURIComponent(d.brand)}'">
        <div class="card-badges">
          ${d.network_5g ? '<span class="badge badge-5g">5G</span>' : ''}
          ${d.category === 'flagship' ? '<span class="badge badge-flagship">Flagship</span>' : ''}
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
        </div>
        <div class="card-footer">
          <div>
            <div style="font-size:11px;color:var(--text3);margin-bottom:2px">Mulai dari</div>
            <div class="card-price">${d.price_fmt}</div>
          </div>
          <div style="display:flex;gap:6px">
            <a href="device.php?slug=${d.slug}" class="btn-primary" style="padding:7px 14px;font-size:13px;font-weight:700">Detail</a>
            <button class="btn-compare ${compareList.find(c=>c.id==d.id)?'added':''}" data-compare-id="${d.id}"
              onclick="App.addToCompare(${d.id},'${d.name.replace(/'/g,"\\'")}','${(d.image||'').replace(/'/g,"\\'")}')">
              ${compareList.find(c=>c.id==d.id)?'✓ Dibandingkan':'+ Bandingkan'}
            </button>
          </div>
        </div>
      </div>
    </div>
  `).join('');

  document.getElementById('catalog-count').innerHTML =
    `Menampilkan <strong>${start + 1}–${end}</strong> dari <strong>${total}</strong> perangkat`;

  renderPagination(pages);

  // Scroll to top of grid smoothly
  if (currentPage > 1) {
    document.getElementById('catalog-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function renderPagination(pages) {
  const wrap = document.getElementById('pagination-wrap');
  const controls = document.getElementById('pagination-controls');
  const info = document.getElementById('pagination-info');

  if (pages <= 1) {
    wrap.style.display = 'none';
    return;
  }

  wrap.style.display = 'flex';
  info.textContent = `Halaman ${currentPage} dari ${pages}`;

  // Build page number array with ellipsis
  const pageNums = [];
  for (let i = 1; i <= pages; i++) {
    if (i === 1 || i === pages || (i >= currentPage - 1 && i <= currentPage + 1)) {
      pageNums.push(i);
    } else if (pageNums[pageNums.length - 1] !== '...') {
      pageNums.push('...');
    }
  }

  controls.innerHTML = `
    <button class="pg-btn" onclick="goPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} title="Sebelumnya">‹</button>
    ${pageNums.map(p =>
      p === '...'
        ? `<span class="pg-ellipsis">…</span>`
        : `<button class="pg-btn ${p === currentPage ? 'active' : ''}" onclick="goPage(${p})">${p}</button>`
    ).join('')}
    <button class="pg-btn" onclick="goPage(${currentPage + 1})" ${currentPage === pages ? 'disabled' : ''} title="Berikutnya">›</button>
  `;
}

function goPage(page) {
  const pages = totalPages();
  if (page < 1 || page > pages) return;
  currentPage = page;
  renderPage();
}

function setPersona(btn, persona) {
  document.querySelectorAll('.persona-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentPersona = persona;
  applyFilters();
}

function resetFilters() {
  document.querySelector('input[name="price_range"][value="0_99999999"]').checked = true;
  document.querySelectorAll('input[name="brand_filter"]').forEach(c => c.checked = false);
  document.getElementById('filter-5g').checked = false;
  document.getElementById('filter-wireless').checked = false;
  document.getElementById('filter-nfc').checked = false;
  currentPersona = '';
  document.querySelectorAll('.persona-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
  applyFilters();
}

// Load on page ready
document.addEventListener('DOMContentLoaded', applyFilters);
</script>

<?php require_once 'includes/footer.php'; ?>