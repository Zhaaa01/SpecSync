</div><!-- /content -->
</div><!-- /main -->

<div id="admin-toast"></div>

<script>
function adminToast(msg, type = 'info') {
  const c = document.getElementById('admin-toast');
  const el = document.createElement('div');
  el.className = `a-toast ${type}`;
  el.textContent = msg;
  c.appendChild(el);
  setTimeout(() => { el.style.opacity='0'; el.style.transition='.3s'; setTimeout(() => el.remove(), 300); }, 3000);
}

// Confirm delete helper
function confirmDelete(msg, url) {
  if (confirm(msg || 'Yakin ingin menghapus?')) window.location = url;
}

// Modal helpers
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// Auto-hide flash messages
document.querySelectorAll('.flash-msg').forEach(el => {
  setTimeout(() => { el.style.opacity='0'; el.style.transition='.4s'; setTimeout(() => el.remove(), 400); }, 4000);
});
</script>
</body>
</html>