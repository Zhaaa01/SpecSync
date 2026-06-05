<?php
// admin/ai_import.php — AI Spec Importer
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

$page_title = 'AI Import HP';
$active_nav = 'devices';
include '_layout.php';
?>

<div style="max-width:960px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="devices.php" class="btn-sm">← Kembali</a>
    <div>
      <h2 style="font-size:18px;font-weight:700">🤖 AI Spec Importer</h2>
      <p style="font-size:13px;color:var(--text3);margin-top:2px">Masukkan nama HP — AI mencari spesifikasi & harga pasar terkini secara otomatis</p>
    </div>
  </div>

  <!-- API Key hint -->
  <?php
  $hasGemini = (defined('GEMINI_API_KEY') && GEMINI_API_KEY) || getenv('GEMINI_API_KEY');
  ?>
  <?php if (!$hasGemini): ?>
  <div style="margin-bottom:16px;padding:14px 18px;background:rgba(248,81,73,.07);border:1px solid rgba(248,81,73,.25);border-radius:10px;font-size:13px">
    ⚠️ <strong>GEMINI_API_KEY belum dikonfigurasi.</strong> Tambahkan di <code>includes/db.php</code>:<br>
    <code style="display:block;margin-top:8px;padding:8px;background:var(--bg3);border-radius:6px;font-size:12px">define('GEMINI_API_KEY', 'AIza...');</code>
    <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color:var(--accent);margin-top:8px;display:inline-block">→ Dapatkan Gemini API Key (gratis)</a>
  </div>
  <?php else: ?>
  <div style="margin-bottom:16px;padding:10px 16px;background:rgba(63,185,80,.07);border:1px solid rgba(63,185,80,.2);border-radius:10px;font-size:13px;color:var(--green)">
    ✅ <strong>Gemini AI + GSMArena Scraper</strong> aktif — spek diambil langsung dari GSMArena, fallback ke Gemini grounding search
  </div>
  <?php endif; ?>

  <!-- Search box -->
  <div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">🔍 Cari Spesifikasi HP</div></div>
    <div style="padding:20px">
      <div style="display:flex;gap:10px;margin-bottom:12px">
        <input type="text" id="hp-query" class="form-input" placeholder="Contoh: Samsung Galaxy S25 Ultra, iPhone 16 Pro Max, Xiaomi 15 Ultra..."
          style="flex:1" onkeydown="if(event.key==='Enter') fetchSpecs()">
        <button class="btn-primary" onclick="fetchSpecs()" id="fetch-btn" style="white-space:nowrap">
          ✨ Ambil Spesifikasi
        </button>
      </div>

      <!-- Source selector -->
      <div style="display:flex;align-items:center;gap:18px;margin-bottom:14px;padding:12px 16px;background:var(--bg3);border-radius:10px;border:1px solid var(--border2)">
        <span style="font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px">Sumber Data:</span>
        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:600">
          <input type="checkbox" id="src-gsmarena" checked onchange="handleSourceChange()"
            style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
          🌐 GSMArena Scraper
        </label>
        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:600">
          <input type="checkbox" id="src-gemini" checked onchange="handleSourceChange()"
            style="width:16px;height:16px;accent-color:#8b5cf6;cursor:pointer">
          🤖 Gemini AI
        </label>
        <span id="src-hint" style="font-size:11px;color:var(--text3);margin-left:auto"></span>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap" id="quick-chips">
        <?php foreach(['Samsung Galaxy S25 Ultra','iPhone 16 Pro Max','Xiaomi 15 Ultra','POCO X7 Pro','Realme GT 7 Pro','OnePlus 13','Google Pixel 9 Pro'] as $hp): ?>
        <span onclick="quickSearch('<?= $hp ?>')" style="padding:5px 12px;background:var(--bg3);border:1px solid var(--border2);border-radius:20px;font-size:12px;cursor:pointer;color:var(--text2)"><?= $hp ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Status / loading -->
  <div id="status-box" style="display:none;padding:14px 18px;border-radius:10px;font-size:13px;margin-bottom:16px"></div>

  <!-- Result preview -->
  <div id="result-area" style="display:none">
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <div class="card-title">📋 Hasil Spesifikasi</div>
        <div style="display:flex;gap:8px">
          <button class="btn-sm" onclick="editAll()">✏️ Edit</button>
          <button class="btn-primary" onclick="saveDevice()" id="save-btn" style="padding:6px 16px;font-size:13px">💾 Simpan ke Database</button>
        </div>
      </div>
      <div style="padding:20px">
        <div id="spec-preview"></div>
      </div>
    </div>
    <!-- Hidden form for actual submission -->
    <form id="save-form" method="POST" action="devices.php?action=add" style="display:none">
    </form>
  </div>

  <!-- Import history -->
  <div class="card">
    <div class="card-header"><div class="card-title">📜 Riwayat Import Sesi Ini</div></div>
    <div style="padding:16px" id="import-history">
      <div style="font-size:13px;color:var(--text3);text-align:center;padding:12px">Belum ada import dalam sesi ini.</div>
    </div>
  </div>
</div>

<style>
.spec-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.spec-item{background:var(--bg3);border-radius:8px;padding:10px 14px}
.spec-label{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.spec-val{font-size:14px;font-weight:600;color:var(--text)}
.spec-val input{background:transparent;border:none;border-bottom:1px solid var(--border2);color:var(--text);font-size:14px;font-weight:600;font-family:inherit;width:100%;outline:none;padding:0}
.spec-section{font-size:11px;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.8px;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid var(--border)}
</style>

<script>
let currentSpecs = null;
const history = [];

function handleSourceChange() {
  const gsm = document.getElementById('src-gsmarena').checked;
  const gem = document.getElementById('src-gemini').checked;
  const hint = document.getElementById('src-hint');
  if (!gsm && !gem) {
    // Force at least one to stay checked
    document.getElementById('src-gsmarena').checked = true;
    hint.textContent = '⚠️ Minimal satu sumber harus dipilih';
    hint.style.color = 'var(--red, #f85149)';
    return;
  }
  if (gsm && gem)  { hint.textContent = 'GSMArena dulu, fallback ke Gemini'; hint.style.color = 'var(--text3)'; }
  else if (gsm)    { hint.textContent = 'Hanya GSMArena Scraper'; hint.style.color = 'var(--text3)'; }
  else             { hint.textContent = 'Hanya Gemini AI (tanpa scraping)'; hint.style.color = '#8b5cf6'; }
}
// Init hint on load
document.addEventListener('DOMContentLoaded', handleSourceChange);

function quickSearch(name) {
  document.getElementById('hp-query').value = name;
  fetchSpecs();
}

function showStatus(msg, type='info') {
  const box = document.getElementById('status-box');
  const colors = {info:'rgba(0,212,255,.1)',success:'rgba(63,185,80,.1)',error:'rgba(248,81,73,.1)'};
  const borders = {info:'rgba(0,212,255,.2)',success:'rgba(63,185,80,.2)',error:'rgba(248,81,73,.3)'};
  box.style.display='block';
  box.style.background=colors[type];
  box.style.border='1px solid '+borders[type];
  box.innerHTML=msg;
}

async function fetchSpecs() {
  const query = document.getElementById('hp-query').value.trim();
  if (!query) { showStatus('⚠️ Masukkan nama HP terlebih dahulu.','error'); return; }

  const useGSMArena = document.getElementById('src-gsmarena').checked;
  const useGemini   = document.getElementById('src-gemini').checked;

  const btn = document.getElementById('fetch-btn');
  btn.textContent = '⏳ Memproses...';
  btn.disabled = true;
  document.getElementById('result-area').style.display = 'none';

  const srcDesc = useGSMArena && useGemini ? 'GSMArena + Gemini AI'
                : useGSMArena ? 'GSMArena Scraper'
                : 'Gemini AI';
  showStatus('🌐 Mencari spesifikasi <strong>'+query+'</strong> dari '+srcDesc+'... Mohon tunggu.', 'info');

  try {
    const res = await fetch('ai_import_api.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ query, use_gsmarena: useGSMArena, use_gemini: useGemini })
    });
    const data = await res.json();
    console.log('DEBUG AI Import:', data);
    if (data.error) {
      showStatus('❌ '+data.error, 'error');
    } else {
      currentSpecs = data;
      renderPreview(data);
      const srcLabel = (data._source && data._source.includes('gsmarena'))
        ? '🌐 <strong>GSMArena</strong>'
        : '🤖 <strong>Gemini AI</strong>';
      const urlLink = data._gsmarena_url
        ? ` &nbsp;<a href="${data._gsmarena_url}" target="_blank" style="font-size:11px;color:var(--accent)">→ Lihat di GSMArena</a>`
        : '';
      showStatus('✅ Spesifikasi <strong>'+data.name+'</strong> berhasil didapat dari ' + srcLabel + urlLink + '. Periksa dan klik Simpan.', 'success');
      document.getElementById('result-area').style.display = 'block';
    }
  } catch(e) {
    showStatus('❌ Gagal menghubungi AI. Pastikan koneksi internet aktif.', 'error');
  }
  btn.textContent = '✨ Ambil Spesifikasi';
  btn.disabled = false;
}

function renderPreview(d) {
  const sections = [
    {label:'Info Dasar', fields:[
      {k:'name',l:'Nama Perangkat'},{k:'brand',l:'Merek'},{k:'category',l:'Kategori'},
      {k:'price',l:'Harga (Rp)'},{k:'release_year',l:'Tahun Rilis'},{k:'image',l:'URL Gambar'}
    ]},
    {label:'Layar', fields:[
      {k:'screen_size',l:'Ukuran (inci)'},{k:'resolution',l:'Resolusi'},{k:'refresh_rate',l:'Refresh Rate (Hz)'},{k:'display_type',l:'Tipe Panel'}
    ]},
    {label:'Prosesor & Memori', fields:[
      {k:'chipset',l:'Chipset'},{k:'gpu',l:'GPU'},{k:'cpu_cores',l:'CPU Cores'},{k:'ram',l:'RAM (GB)'},{k:'storage',l:'Storage (GB)'}
    ]},
    {label:'Kamera', fields:[
      {k:'main_camera',l:'Kamera Utama (MP)'},{k:'front_camera',l:'Kamera Depan (MP)'},{k:'camera_features',l:'Fitur Kamera'}
    ]},
    {label:'Baterai', fields:[
      {k:'battery',l:'Kapasitas (mAh)'},{k:'charging_speed',l:'Charging (W)'},{k:'has_wireless_charging',l:'Wireless Charging'}
    ]},
    {label:'Konektivitas', fields:[
      {k:'network_5g',l:'5G'},{k:'nfc',l:'NFC'},{k:'wifi_version',l:'WiFi'},{k:'bluetooth_version',l:'Bluetooth'}
    ]},
    {label:'Software', fields:[
      {k:'os',l:'OS'},{k:'os_version',l:'Versi OS'}
    ]},
    {label:'Fisik', fields:[
      {k:'weight',l:'Berat (gram)'},{k:'thickness',l:'Tebal (mm)'},{k:'width',l:'Lebar (mm)'},{k:'height',l:'Tinggi (mm)'},{k:'color_options',l:'Warna'}
    ]},
    {label:'Skor AI', fields:[
      {k:'score_camera',l:'Skor Kamera'},{k:'score_performance',l:'Skor Performa'},{k:'score_battery',l:'Skor Baterai'},{k:'score_design',l:'Skor Desain'}
    ]},
  ];

  let html = '';
  sections.forEach(sec => {
    html += `<div class="spec-section">${sec.label}</div><div class="spec-grid">`;
    sec.fields.forEach(f => {
      const val = d[f.k] !== undefined && d[f.k] !== null ? d[f.k] : '';
      html += `<div class="spec-item"><div class="spec-label">${f.l}</div><div class="spec-val" id="sv-${f.k}">${val}</div></div>`;
    });
    html += '</div>';
  });
  document.getElementById('spec-preview').innerHTML = html;
}

function editAll() {
  const fields = ['name','brand','category','price','release_year','image','screen_size','resolution',
    'refresh_rate','display_type','chipset','gpu','cpu_cores','ram','storage','main_camera','front_camera',
    'camera_features','battery','charging_speed','has_wireless_charging','network_5g','nfc','wifi_version',
    'bluetooth_version','os','os_version','weight','thickness','width','height','color_options',
    'score_camera','score_performance','score_battery','score_design'];
  fields.forEach(k => {
    const el = document.getElementById('sv-'+k);
    if (el && !el.querySelector('input')) {
      const val = el.textContent;
      el.innerHTML = `<input type="text" value="${val}" onchange="currentSpecs['${k}']=this.value">`;
    }
  });
}

function saveDevice() {
  if (!currentSpecs) return;
  const form = document.getElementById('save-form');
  form.innerHTML = '';
  // Add all spec fields as hidden inputs
  for (const [k, v] of Object.entries(currentSpecs)) {
    // Get potentially edited value
    const el = document.getElementById('sv-'+k);
    const editedVal = el ? (el.querySelector('input') ? el.querySelector('input').value : el.textContent) : v;
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = k;
    inp.value = editedVal !== undefined ? editedVal : (v ?? '');
    form.appendChild(inp);
  }
  // Add to history
  addHistory(currentSpecs.name || 'Unknown', currentSpecs.brand || '');
  form.submit();
}

function addHistory(name, brand) {
  const hist = document.getElementById('import-history');
  const item = document.createElement('div');
  item.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px';
  item.innerHTML = `<span style="color:var(--green)">✓</span><strong>${name}</strong><span style="color:var(--text3)">${brand}</span><span style="margin-left:auto;color:var(--text3);font-size:11px">${new Date().toLocaleTimeString('id')}</span>`;
  if (hist.querySelector('div[style*="text-align:center"]')) hist.innerHTML = '';
  hist.prepend(item);
}
</script>

<?php include '_layout_end.php'; ?>
