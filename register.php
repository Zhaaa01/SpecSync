<?php
session_start();
require_once 'includes/db.php';

if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $conn = getDB();
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Email sudah terdaftar. Silakan masuk.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed')");
            $new_id = mysqli_insert_id($conn);
            $_SESSION['user_id'] = $new_id;
            $_SESSION['user_name'] = $name;
            header('Location: dashboard.php');
            exit;
        }
    }
}

$page_title = 'Daftar';
?>
<?php require_once 'includes/header.php'; ?>

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 24px">
  <div style="width:100%;max-width:440px">

    <div style="text-align:center;margin-bottom:36px">
      <div class="logo" style="font-size:28px;margin-bottom:12px">Spec<span>Sync</span></div>
      <h1 style="font-size:22px;font-weight:700">Buat akun gratis</h1>
      <p style="font-size:14px;color:var(--text2);margin-top:6px">Simpan wishlist, bandingkan HP, dan aktifkan alert harga</p>
    </div>

    <?php if ($error): ?>
    <div style="padding:12px 16px;background:rgba(248,81,73,0.1);border:1px solid rgba(248,81,73,0.3);border-radius:10px;color:var(--red);font-size:14px;margin-bottom:20px">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Nama Lengkap</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Budi Santoso" required
          style="width:100%;padding:11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
      </div>
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required
          style="width:100%;padding:11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
      </div>
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Password</label>
        <input type="password" name="password" id="pw1" placeholder="Min. 8 karakter" required
          style="width:100%;padding:11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'"
          oninput="checkStrength(this.value)">
        <!-- Password strength indicator -->
        <div style="display:flex;gap:4px;margin-top:6px" id="strength-bars">
          <div style="height:3px;flex:1;background:var(--bg4);border-radius:2px" id="s1"></div>
          <div style="height:3px;flex:1;background:var(--bg4);border-radius:2px" id="s2"></div>
          <div style="height:3px;flex:1;background:var(--bg4);border-radius:2px" id="s3"></div>
          <div style="height:3px;flex:1;background:var(--bg4);border-radius:2px" id="s4"></div>
        </div>
        <div id="strength-label" style="font-size:11px;color:var(--text3);margin-top:3px"></div>
      </div>
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Konfirmasi Password</label>
        <input type="password" name="confirm" id="pw2" placeholder="Ulangi password" required
          style="width:100%;padding:11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px"
          onfocus="this.style.borderColor='var(--accent)'" onblur="checkMatch()">
        <div id="match-msg" style="font-size:12px;margin-top:4px"></div>
      </div>

      <button type="submit" class="btn-primary" style="padding:13px;font-size:15px;border-radius:10px;margin-top:4px">
        Buat Akun Gratis →
      </button>
    </form>

    <div style="text-align:center;margin-top:24px;font-size:14px;color:var(--text3)">
      Sudah punya akun? <a href="login.php" style="color:var(--accent);font-weight:600">Masuk</a>
    </div>
  </div>
</div>

<script>
function checkStrength(pw) {
  const bars = ['s1','s2','s3','s4'];
  const colors = ['var(--red)','var(--amber)','var(--accent)','var(--green)'];
  const labels = ['','Lemah','Sedang','Kuat','Sangat Kuat'];
  let score = 0;
  if (pw.length >= 8) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  bars.forEach((id, i) => {
    document.getElementById(id).style.background = i < score ? colors[score-1] : 'var(--bg4)';
  });
  const lbl = document.getElementById('strength-label');
  lbl.textContent = score > 0 ? labels[score] : '';
  lbl.style.color = score > 0 ? colors[score-1] : 'var(--text3)';
}

function checkMatch() {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  const msg = document.getElementById('match-msg');
  if (!p2) { msg.textContent = ''; return; }
  if (p1 === p2) { msg.textContent = '✓ Password cocok'; msg.style.color = 'var(--green)'; }
  else { msg.textContent = '✕ Password tidak cocok'; msg.style.color = 'var(--red)'; }
}
</script>

<?php require_once 'includes/footer.php'; ?>
