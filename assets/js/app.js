// assets/js/app.js

const App = {
  compareList: JSON.parse(localStorage.getItem('ss_compare') || '[]'),
  wishlist: JSON.parse(localStorage.getItem('ss_wishlist') || '[]'),

  init() {
    this.initTheme();
    this.initSearch();
    this.renderCompareBar();
    this.bindEvents();
  },

  // ── Theme ──────────────────────────────────────────────
  initTheme() {
    const saved = localStorage.getItem('ss_theme');
    if (saved === 'light') document.body.classList.add('light-mode');
  },

  toggleTheme() {
    document.body.classList.toggle('light-mode');
    const isLight = document.body.classList.contains('light-mode');
    localStorage.setItem('ss_theme', isLight ? 'light' : 'dark');
  },

  // ── Live Search ────────────────────────────────────────
  searchTimeout: null,
  initSearch() {
    const input = document.getElementById('main-search');
    const dropdown = document.getElementById('search-dropdown');
    if (!input) return;

    input.addEventListener('input', (e) => {
      clearTimeout(this.searchTimeout);
      const q = e.target.value.trim();
      if (q.length < 2) { dropdown.classList.remove('show'); return; }
      this.searchTimeout = setTimeout(() => this.doSearch(q), 220);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-container')) dropdown.classList.remove('show');
    });
  },

  async doSearch(q) {
    const dropdown = document.getElementById('search-dropdown');
    dropdown.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text3);font-size:13px">Mencari...</div>';
    dropdown.classList.add('show');
    try {
      const res = await fetch(`api.php?action=search&q=${encodeURIComponent(q)}`);
      const data = await res.json();
      this.renderSearchResults(data, dropdown);
    } catch (e) {
      dropdown.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text3);font-size:13px">Gagal memuat hasil</div>';
    }
  },

  renderSearchResults(devices, dropdown) {
    if (!devices.length) {
      dropdown.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text3);font-size:13px">Tidak ada hasil</div>';
      return;
    }
    dropdown.innerHTML = devices.map(d => `
      <div class="search-item" onclick="window.location='device.php?slug=${d.slug}'">
        <img src="${d.image || 'assets/img/placeholder.png'}" alt="${d.name}" loading="lazy" onerror="this.src='assets/img/placeholder.png'">
        <div class="search-item-info">
          <div class="search-item-name">${d.name}</div>
          <div class="search-item-brand">${d.brand} · ${d.category}</div>
        </div>
        <div class="search-item-price">${d.price_fmt}</div>
        <div class="search-item-actions" onclick="event.stopPropagation()">
          <button class="search-item-action add-compare" onclick="App.addToCompare(${d.id}, '${d.name}', '${d.image}'); document.getElementById('search-dropdown').classList.remove('show')">
            + Bandingkan
          </button>
        </div>
      </div>
    `).join('');
  },

  // ── Compare Bar ────────────────────────────────────────
  addToCompare(id, name, image) {
    if (this.compareList.find(d => d.id == id)) {
      this.toast('Sudah ada di perbandingan', 'info'); return;
    }
    if (this.compareList.length >= 2) {
      this.toast('Maksimal 2 perangkat untuk dibandingkan', 'error'); return;
    }
    this.compareList.push({ id, name, image });
    this.saveCompare();
    this.renderCompareBar();
    this.updateCardButtons();
    this.toast(`${name} ditambahkan ke perbandingan ✓`, 'success');
  },

  removeFromCompare(id) {
    this.compareList = this.compareList.filter(d => d.id != id);
    this.saveCompare();
    this.renderCompareBar();
    this.updateCardButtons();
  },

  clearCompare() {
    this.compareList = [];
    this.saveCompare();
    this.renderCompareBar();
    this.updateCardButtons();
  },

  saveCompare() {
    localStorage.setItem('ss_compare', JSON.stringify(this.compareList));
  },

  renderCompareBar() {
    const bar = document.getElementById('compare-bar');
    if (!bar) return;

    if (this.compareList.length === 0) { bar.classList.remove('visible'); return; }
    bar.classList.add('visible');

    const slots = [0, 1].map(i => {
      const d = this.compareList[i];
      if (!d) return `<div class="compare-slot empty"><span style="font-size:12px;color:var(--text3)">+ Tambah perangkat</span></div>`;
      return `
        <div class="compare-slot">
          <img src="${d.image || 'assets/img/placeholder.png'}" alt="${d.name}" onerror="this.src='assets/img/placeholder.png'">
          <div class="compare-slot-name">${d.name}</div>
          <button class="compare-slot-remove" onclick="App.removeFromCompare(${d.id})" aria-label="Hapus">✕</button>
        </div>`;
    }).join('');

    document.getElementById('compare-slots').innerHTML = slots;
  },

  goCompare() {
    if (this.compareList.length < 2) { this.toast('Tambahkan 2 perangkat untuk dibandingkan', 'error'); return; }
    window.location = `compare.php?d1=${this.compareList[0].id}&d2=${this.compareList[1].id}`;
  },

  updateCardButtons() {
    document.querySelectorAll('[data-compare-id]').forEach(btn => {
      const id = parseInt(btn.dataset.compareId);
      const inList = this.compareList.find(d => d.id == id);
      btn.classList.toggle('added', !!inList);
      btn.textContent = inList ? '✓ Dibandingkan' : '+ Bandingkan';
    });
  },

  // ── Wishlist ───────────────────────────────────────────
  async toggleWishlist(deviceId, btn) {
    try {
      const fd = new FormData();
      fd.append('action', 'wishlist_toggle');
      fd.append('device_id', deviceId);
      const res = await fetch('api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.error === 'Login required') {
        this.toast('Login dulu untuk menyimpan wishlist', 'error');
        window.location = 'login.php';
        return;
      }
      if (data.status === 'added') {
        this.wishlist.push(deviceId);
        btn.classList.add('active');
        this.toast('Ditambahkan ke wishlist ♥', 'success');
      } else if (data.status === 'removed') {
        const idx = this.wishlist.indexOf(deviceId);
        if (idx > -1) this.wishlist.splice(idx, 1);
        btn.classList.remove('active');
        this.toast('Dihapus dari wishlist');
      }
      localStorage.setItem('ss_wishlist', JSON.stringify(this.wishlist));
    } catch(e) {
      this.toast('Gagal menyimpan wishlist', 'error');
    }
  },

  initWishlistButtons() {
    document.querySelectorAll('[data-wishlist-id]').forEach(btn => {
      const id = parseInt(btn.dataset.wishlistId);
      if (this.wishlist.includes(id)) btn.classList.add('active');
    });
  },

  // ── Toast ──────────────────────────────────────────────
  toast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${icons[type] || ''}</span> ${msg}`;
    container.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; el.style.transition = '0.3s'; setTimeout(() => el.remove(), 300); }, 2800);
  },

  // ── General events ─────────────────────────────────────
  bindEvents() {
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') document.getElementById('search-dropdown')?.classList.remove('show');
    });
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());
