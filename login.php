<?php
require_once 'config/database.php';
require_once __DIR__ . '/config/google_config.php';

$err = '';
$msg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'password_reset') {
        $msg = 'Password updated successfully! Please sign in.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password'])) {

        $verified = isset($u['is_verified']) ? (int)$u['is_verified'] : 1;

        if (!$verified) {
            $_SESSION['pending_reg'] = [
                'name'  => $u['name'],
                'email' => $u['email'],
                'phone' => $u['phone'] ?? '',
                'pass'  => $u['password'],
            ];

            $otp = generate_otp($conn, $u['email'], 'register');
            send_otp_email($u['email'], $otp, $u['name']);

            $_SESSION['reg_step'] = 'otp';
            redirect('register.php');
        }

        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name']    = $u['name'];
        $_SESSION['email']   = $u['email'];
        $_SESSION['role']    = $u['role'];

        redirect($u['role'] === 'admin' ? 'admin/index.php' : 'index.php');

    } else {
        $err = 'Incorrect email or password. Please try again.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container" style="max-width:440px;padding-top:60px;padding-bottom:60px">
  <form class="form" method="post" autocomplete="on">
    <?=csrf_field()?>

    <div style="text-align:center;margin-bottom:28px">
      <div style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;letter-spacing:0.3em;color:var(--gold);text-transform:uppercase;margin-bottom:16px">
        MAYURI
      </div>
      <h1>Welcome Back</h1>
      <p class="form-subtitle">Sign in to your MAYURI account</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert success">&#10003; <?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
      <div class="alert error">&#9888; <?= e($err) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label>Email Address</label>
      <input 
        type="email" 
        name="email" 
        placeholder="you@email.com" 
        required 
        autofocus
        value="<?= e($_POST['email'] ?? '') ?>"
      >
    </div>

    <div class="form-group">
      <label>Password</label>

      <div style="position:relative;width:100%;">
        <input 
          type="password" 
          name="password" 
          id="loginPass" 
          placeholder="Your password" 
          required
          style="width:100%;padding-right:50px;position:relative;z-index:1;"
        >

        <button 
          type="button"
          id="togglePassword"
          style="
            position:absolute;
            right:12px;
            top:50%;
            transform:translateY(-50%);
            background:transparent;
            border:none;
            cursor:pointer;
            color:var(--muted);
            font-size:18px;
            z-index:5;
            width:28px;
            height:28px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:0;
          "
        >&#128065;</button>
      </div>

      <div style="text-align:right;margin-top:6px">
        <a href="forgot_password.php" style="font-size:0.82rem;color:var(--primary)">
          Forgot password?
        </a>
      </div>
    </div>

    <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">
      Sign In &rarr;
    </button>


<!-- Google Sign-In -->
<div style="margin:22px 0 18px">
  <div style="display:flex;align-items:center;gap:10px;color:var(--muted);font-size:.78rem;margin-bottom:12px">
    <span style="height:1px;background:var(--border);flex:1"></span>
    <span>OR CONTINUE WITH</span>
    <span style="height:1px;background:var(--border);flex:1"></span>
  </div>
  <div id="googleSignInButton" style="display:flex;justify-content:center;min-height:44px"></div>
  <div id="googleSignInError" class="alert error" style="display:none;margin-top:10px;text-align:left"></div>
</div>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
(function () {
  const clientId = <?= json_encode(defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '') ?>;
  const csrfToken = <?= json_encode(csrf_token()) ?>;
  const button = document.getElementById('googleSignInButton');
  const errorBox = document.getElementById('googleSignInError');

  function showGoogleError(message) {
    if (!errorBox) return;
    errorBox.textContent = message || 'Google sign-in failed. Please try again.';
    errorBox.style.display = 'block';
  }

  function handleGoogleCredential(response) {
    if (!response || !response.credential) {
      showGoogleError('Google sign-in was not completed.');
      return;
    }

    button.style.opacity = '0.6';
    button.style.pointerEvents = 'none';

    const body = new URLSearchParams();
    body.set('credential', response.credential);
    body.set('csrf_token', csrfToken);

    fetch('google_login.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
      body: body.toString(),
      credentials: 'same-origin'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data && data.ok) {
        window.location.href = data.redirect || 'index.php';
        return;
      }
      throw new Error((data && data.message) || 'Google sign-in failed.');
    })
    .catch(function (err) {
      button.style.opacity = '1';
      button.style.pointerEvents = 'auto';
      showGoogleError(err.message);
    });
  }

  function initGoogle() {
    if (!clientId || clientId.indexOf('PASTE_YOUR') === 0) {
      showGoogleError('Google Login is not configured. Add a valid Google Client ID in config/google_config.php.');
      return;
    }
    if (!window.google || !google.accounts || !google.accounts.id || !button) return;

    google.accounts.id.initialize({
      client_id: clientId,
      callback: handleGoogleCredential,
      auto_select: false,
      cancel_on_tap_outside: true
    });

    google.accounts.id.renderButton(button, {
      type: 'standard',
      theme: 'outline',
      size: 'large',
      text: 'continue_with',
      shape: 'rectangular',
      width: Math.min(380, Math.max(280, button.parentElement ? button.parentElement.clientWidth : 380))
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGoogle);
  } else {
    initGoogle();
  }
  window.addEventListener('load', initGoogle, {once: true});
})();
</script>

    <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--muted)">
      New here?
      <a href="register.php" style="color:var(--primary);font-weight:600">
        Create an account &rarr;
      </a>
    </p>

  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passInput = document.getElementById('loginPass');
    const toggleBtn = document.getElementById('togglePassword');

    if (toggleBtn && passInput) {
        toggleBtn.addEventListener('click', function () {
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleBtn.textContent = '\u{1F576}';
            } else {
                passInput.type = 'password';
                toggleBtn.innerHTML = '&#128065;';
            }
            passInput.focus();
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>