<?php
require_once __DIR__ . '/config/google_config.php';
include 'includes/header.php';

// Reset pending registration only when user intentionally clicks Start Over
if (isset($_GET['reset'])) {
    unset($_SESSION['reg_step'], $_SESSION['pending_reg'], $_SESSION['last_otp']);
    redirect('register.php');
}

$step = $_SESSION['reg_step'] ?? 'form'; // form ÃƒÂ¢?? otp ÃƒÂ¢?? done
$err = '';
$success = '';

// ÃƒÂ¢??ÃƒÂ¢?? STEP 1: Submit registration form ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
if($_SERVER['REQUEST_METHOD']==='POST'){ verify_csrf(); }
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='register'){
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = preg_replace('/\D/','',$_POST['phone'] ?? '');
    $pass  = $_POST['password'];
    $pass2 = $_POST['password2'];

    if(!$name || !$email || !$phone || !$pass){
        $err = 'Please fill all fields.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $err = 'Enter a valid email address.';
    } elseif(strlen($phone) !== 10){
        $err = 'Enter a valid 10-digit Indian mobile number.';
    } elseif(strlen($pass) < 8){
        $err = 'Password must be at least 8 characters.';
    } elseif($pass !== $pass2){
        $err = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param('s',$email); $chk->execute();
        if($chk->get_result()->num_rows > 0){
            $err = 'This email is already registered. <a href="login.php">Sign in instead</a>.';
        } else {
            // Store pending registration in session
            $_SESSION['pending_reg'] = [
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'pass'  => password_hash($pass, PASSWORD_DEFAULT),
            ];
            $otp = generate_otp($conn, $email, 'register');
            $emailSent = send_otp_email($email, $otp, $name);
            $sms_result = send_otp_sms($phone, $otp);
            $smsSent = !empty($sms_result['status']);
            $_SESSION['reg_step'] = 'otp';
            $step = 'otp';
            $success = "A 6-digit verification code has been sent to <strong>$email</strong> and mobile number <strong>+91 $phone</strong>.";
            if(!$emailSent && !$smsSent){
                $success = "OTP generated, but email/SMS sending failed. Check config/API key and PHP error log.";
            } elseif(!$smsSent){
                $success .= " SMS sending failed, please check Fast2SMS setup.";
            } elseif(!$emailSent){
                $success .= " Email sending failed, but SMS was sent.";
            }
        }
    }
}

// ÃƒÂ¢??ÃƒÂ¢?? STEP 2: Verify OTP ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='verify_otp'){
    $email = $_SESSION['pending_reg']['email'] ?? '';
    $otp   = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    if(!$email){ redirect('register.php'); }
    if(verify_otp($conn, $email, $otp, 'register')){
        $r = $_SESSION['pending_reg'];
        $stmt = $conn->prepare("INSERT INTO users(name,email,phone,password,role,is_verified) VALUES(?,?,?,?, 'user', 1)");
        $stmt->bind_param('ssss',$r['name'],$r['email'],$r['phone'],$r['pass']);
        if($stmt->execute()){
            unset($_SESSION['pending_reg'], $_SESSION['reg_step'], $_SESSION['last_otp']);
            // Auto login
            $uid = $conn->insert_id;
            $_SESSION['user_id'] = $uid;
            $_SESSION['name']    = $r['name'];
            $_SESSION['email']   = $r['email'];
            $_SESSION['role']    = 'user';
            redirect('index.php');
        } else {
            $err = 'Registration failed. Please try again.';
        }
    } else {
        $step = 'otp';
        $err  = 'Invalid or expired OTP. Please try again.';
    }
}

// ÃƒÂ¢??ÃƒÂ¢?? STEP 2: Resend OTP ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='resend_otp'){
    $email = $_SESSION['pending_reg']['email'] ?? '';
    $name  = $_SESSION['pending_reg']['name']  ?? '';
    if($email){
        $otp = generate_otp($conn, $email, 'register');
        $phone = $_SESSION['pending_reg']['phone'] ?? '';
        $emailSent = send_otp_email($email, $otp, $name);
        $sms_result = send_otp_sms($phone, $otp);
        $smsSent = !empty($sms_result['status']);

        $step = 'otp';
        $success = 'A new OTP has been sent to your email and phone.';
        if(!$emailSent && !$smsSent){ $success = 'OTP generated, but email/SMS sending failed. Check config/API key and PHP error log.'; }
        elseif(!$smsSent){ $success .= ' SMS sending failed, please check Fast2SMS setup.'; }
        elseif(!$emailSent){ $success .= ' Email sending failed, but SMS was sent.'; }
    }
}
?>

<div class="container" style="max-width:480px;padding-top:60px;padding-bottom:60px">
  <?php if($step === 'form'): ?>
  <!-- ===== REGISTRATION FORM ===== -->
  <form class="form" method="post" id="regForm">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="register">
    <div style="text-align:center;margin-bottom:28px">
      <div style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;letter-spacing:0.3em;color:var(--gold);text-transform:uppercase;margin-bottom:16px">MAYURI</div>
      <h1 style="font-size:1.6rem">Create Account</h1>
      <p class="form-subtitle">Join MAYURI and discover your perfect accessories</p>
    </div>
    <?php if($err) echo '<div class="alert error">  '.$err.'</div>'; ?>


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

    <div class="form-group">
      <label>Full Name</label>
      <input name="name" placeholder="Your full name" required autofocus value="<?=e($_POST['name']??'')?>">
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="you@email.com" required value="<?=e($_POST['email']??'')?>">
      <small style="color:var(--muted);font-size:0.78rem">A verification code will be sent here</small>
    </div>
    <div class="form-group">
      <label>Mobile Number</label>
      <div style="display:flex;gap:8px;align-items:center">
        <span style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 12px;font-size:0.88rem;color:var(--muted);white-space:nowrap">+91</span>
        <input type="tel" name="phone" placeholder="10-digit mobile number" maxlength="10" pattern="[6-9][0-9]{9}" required value="<?=e($_POST['phone']??'')?>" style="flex:1">
      </div>
      <small style="color:var(--muted);font-size:0.78rem">Enter 10-digit Indian mobile number (starts with 6-9)</small>
    </div>
    <div class="form-group">
      <label>Password</label>
      <div style="position:relative">
        <input type="password" name="password" id="pass1" placeholder="Minimum 8 characters" required minlength="8">
        <button type="button" onclick="togglePwd('pass1',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:1rem"></button>
      </div>
      <!-- Password strength bar -->
      <div id="strength-bar" style="margin-top:6px;height:4px;border-radius:2px;background:var(--border);overflow:hidden">
        <div id="strength-fill" style="height:100%;width:0;transition:width 0.3s,background 0.3s;border-radius:2px"></div>
      </div>
      <small id="strength-label" style="font-size:0.76rem;color:var(--muted)"></small>
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <div style="position:relative">
        <input type="password" name="password2" id="pass2" placeholder="Re-enter your password" required>
        <button type="button" onclick="togglePwd('pass2',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:1rem"></button>
      </div>
      <small id="match-label" style="font-size:0.76rem"></small>
    </div>
    <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px;margin-top:4px">
      Send Verification Code ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢
    </button>
    <p style="text-align:center;margin-top:20px;font-size:0.88rem;color:var(--muted)">
      Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600">Sign in </a>
    </p>
  </form>

  <?php else: /* step === otp */ ?>
  <!-- ===== OTP VERIFICATION ===== -->
  <div class="form" style="text-align:center">
    <div style="font-family:'Cormorant Garamond',serif;font-size:1.1rem;letter-spacing:0.3em;color:var(--gold);text-transform:uppercase;margin-bottom:16px">MAYURI</div>
    <div style="font-size:2.5rem;margin-bottom:12px"></div>
    <h1 style="font-size:1.5rem;margin-bottom:8px">Check Your OTP</h1>
    <p style="color:var(--muted);font-size:0.9rem;margin-bottom:28px">
      We've sent a 6-digit code to<br><strong><?=e($_SESSION['pending_reg']['email']??'')?></strong><br><strong>+91 <?=e($_SESSION['pending_reg']['phone']??'')?></strong>
    </p>
    <?php if($err)    echo '<div class="alert error" style="text-align:left">ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã‚Â¡Ãƒâ€šÃ‚Â  '.$err.'</div>'; ?>
    <?php if($success) echo '<div class="alert success" style="text-align:left">ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ '.$success.'</div>'; ?>

    <form method="post" id="otpForm">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="verify_otp">
      <div style="display:flex;gap:10px;justify-content:center;margin-bottom:20px">
        <?php for($i=0;$i<6;$i++): ?>
        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]"
          style="width:46px;height:56px;text-align:center;font-size:1.4rem;font-weight:700;border:2px solid var(--border);border-radius:10px;background:var(--surface);color:var(--dark);outline:none;transition:border-color 0.2s"
          onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'">
        <?php endfor; ?>
        <input type="hidden" name="otp" id="otpHidden">
      </div>
      <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">
        Verify & Create Account 
      </button>
    </form>

    <form method="post" style="margin-top:16px">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="resend_otp">
      <p style="font-size:0.85rem;color:var(--muted)">
        Didn't receive it? <button type="submit" style="background:none;border:none;color:var(--primary);font-weight:600;cursor:pointer;font-size:0.85rem;padding:0" id="resendBtn">Resend Code</button>
        <span id="resendTimer" style="color:var(--muted)"> (wait <span id="countdown">60</span>s)</span>
      </p>
    </form>
    <p style="font-size:0.82rem;color:var(--muted);margin-top:8px">
      <a href="register.php?reset=1" style="color:var(--muted)">Start over</a>
    </p>
  </div>
  <?php endif; ?>
</div>

<script>
// Password toggle
function togglePwd(id, btn){
  var f = document.getElementById(id);
  f.type = f.type==='password'?'text':'password';
  btn.textContent = f.type==='password'?'ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚Â':'ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ‚ÂÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂÃƒÂ°Ã…Â¸Ã¢â‚¬â€Ã‚Â¨';
}
// Password strength
var p1 = document.getElementById('pass1');
var p2 = document.getElementById('pass2');
if(p1){
  p1.addEventListener('input',function(){
    var v=this.value, score=0;
    if(v.length>=8) score++;
    if(/[A-Z]/.test(v)) score++;
    if(/[0-9]/.test(v)) score++;
    if(/[^A-Za-z0-9]/.test(v)) score++;
    var fill=document.getElementById('strength-fill');
    var lbl=document.getElementById('strength-label');
    var colors=['#e74c3c','#e67e22','#f1c40f','#27ae60'];
    var labels=['Weak','Fair','Good','Strong'];
    fill.style.width=(score*25)+'%';
    fill.style.background=colors[score-1]||'#ddd';
    lbl.textContent=score>0?labels[score-1]:'';
    lbl.style.color=colors[score-1]||'var(--muted)';
  });
}
if(p2){
  p2.addEventListener('input',function(){
    var lbl=document.getElementById('match-label');
    if(!this.value) { lbl.textContent=''; return; }
    if(this.value===p1.value){ lbl.textContent='ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ Passwords match'; lbl.style.color='#27ae60'; }
    else { lbl.textContent='ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Passwords do not match'; lbl.style.color='#e74c3c'; }
  });
}
// OTP boxes ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬ÂÃƒâ€šÃ‚Â auto-advance
var boxes = document.querySelectorAll('.otp-box');
boxes.forEach(function(box, i){
  box.addEventListener('input', function(){
    this.value = this.value.replace(/\D/,'');
    if(this.value && boxes[i+1]) boxes[i+1].focus();
    collectOtp();
  });
  box.addEventListener('keydown', function(e){
    if(e.key==='Backspace' && !this.value && boxes[i-1]) boxes[i-1].focus();
    if(e.key==='ArrowLeft' && boxes[i-1]) boxes[i-1].focus();
    if(e.key==='ArrowRight' && boxes[i+1]) boxes[i+1].focus();
  });
  box.addEventListener('paste', function(e){
    var data = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
    boxes.forEach(function(b,j){ b.value=data[j]||''; });
    if(boxes[5]) boxes[5].focus();
    collectOtp();
    e.preventDefault();
  });
});
function collectOtp(){
  var h = document.getElementById('otpHidden');
  if(h) h.value = Array.from(boxes).map(function(b){return b.value;}).join('');
}
var otpForm = document.getElementById('otpForm');
if(otpForm) otpForm.addEventListener('submit',function(e){ collectOtp(); if(document.getElementById('otpHidden').value.length<6){ e.preventDefault(); alert('Please enter all 6 digits.'); }});

// Resend countdown
var resendBtn = document.getElementById('resendBtn');
var timerEl   = document.getElementById('resendTimer');
var cdEl      = document.getElementById('countdown');
if(resendBtn){
  resendBtn.disabled = true;
  resendBtn.style.opacity = '0.5';
  var secs = 60;
  var cd = setInterval(function(){
    secs--;
    if(cdEl) cdEl.textContent = secs;
    if(secs<=0){
      clearInterval(cd);
      resendBtn.disabled=false;
      resendBtn.style.opacity='1';
      if(timerEl) timerEl.style.display='none';
    }
  },1000);
}
</script>
<?php include 'includes/footer.php'; ?>
