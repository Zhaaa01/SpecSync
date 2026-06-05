<?php
session_start();
require_once 'includes/db.php';

if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $conn = getDB();
        $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
        $user = mysqli_fetch_assoc($res);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$page_title = 'Masuk';
?>
<?php require_once 'includes/header.php'; ?>

<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 24px">
  <div style="width:100%;max-width:420px">

    <div style="text-align:center;margin-bottom:36px">
      <div class="logo" style="font-size:28px;margin-bottom:12px">Spec<span>Sync</span></div>
      <h1 style="font-size:22px;font-weight:700">Selamat datang kembali</h1>
      <p style="font-size:14px;color:var(--text2);margin-top:6px">Masuk ke akun kamu untuk akses wishlist dan perbandingan tersimpan</p>
    </div>

    <?php if ($error): ?>
    <div style="padding:12px 16px;background:rgba(248,81,73,0.1);border:1px solid rgba(248,81,73,0.3);border-radius:10px;color:var(--red);font-size:14px;margin-bottom:20px">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required
          style="width:100%;padding:11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px;transition:border-color 0.2s"
          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
      </div>
      <div>
        <label style="font-size:13px;font-weight:600;color:var(--text2);display:block;margin-bottom:6px">Password</label>
        <div style="position:relative">
          <input type="password" name="password" id="pw-input" placeholder="••••••••" required
            style="width:100%;padding:11px 44px 11px 14px;background:var(--bg2);border:1px solid var(--border2);border-radius:9px;color:var(--text);font-size:15px;transition:border-color 0.2s"
            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border2)'">
          <button type="button" onclick="togglePw()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px" id="pw-toggle">👁</button>
        </div>
        <div style="text-align:right;margin-top:6px"><a href="#" style="font-size:12px;color:var(--text3)">Lupa password?</a></div>
      </div>

      <button type="submit" class="btn-primary" style="padding:13px;font-size:15px;border-radius:10px;margin-top:4px">
        Masuk ke SpecSync →
      </button>
    </form>

    <div style="text-align:center;margin-top:24px;font-size:14px;color:var(--text3)">
      Belum punya akun? <a href="register.php" style="color:var(--accent);font-weight:600">Daftar gratis</a>
    </div>

    <!-- Demo hint -->
    <div style="margin-top:24px;padding:14px;background:var(--bg2);border:1px solid var(--border);border-radius:10px;font-size:12px;color:var(--text3);text-align:center">
      💡 Demo: Daftar akun baru atau gunakan akun yang sudah dibuat
    </div>
  </div>
</div>

<script>
function togglePw() {
  const input = document.getElementById('pw-input');
  const btn = document.getElementById('pw-toggle');
  if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
  else { input.type = 'password'; btn.textContent = '👁'; }
}
</script>

<?php require_once 'includes/footer.php'; ?>
