<?php include 'includes/header.php';
$step = $_SESSION['fp_step'] ?? 'email';
$err = $success = '';

// Step 1: Submit email
if($_POST['action'] ?? ''==='fp_email'){
    $email = trim($_POST['email']);
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $err='Enter a valid email.'; }
    else {
        $chk=$conn->prepare("SELECT id,name FROM users WHERE email=?");
        $chk->bind_param('s',$email); $chk->execute();
        $u=$chk->get_result()->fetch_assoc();
        if(!$u){ $err='No account found with this email.'; }
        else {
            $_SESSION['fp_email']=$email;
            $_SESSION['fp_name']=$u['name'];
            $otp=generate_otp($conn,$email,'forgot_password');
            send_otp_email($email,$otp,$u['name']);
            $_SESSION['fp_step']='otp'; $step='otp';
            $success='OTP sent to '.$email;
        }
    }
}
// Step 2: Verify OTP
if($_POST['action'] ?? ''==='fp_otp'){
    $email=$_SESSION['fp_email']??'';
    $otp=trim($_POST['otp']);
    if(verify_otp($conn,$email,$otp,'forgot_password')){
        $_SESSION['fp_step']='reset'; $step='reset';
    } else { $step='otp'; $err='Invalid or expired OTP.'; }
}
// Step 3: Reset password
if($_POST['action'] ?? ''==='fp_reset'){
    $email=$_SESSION['fp_email']??'';
    $pass=$_POST['password']; $pass2=$_POST['password2'];
    if(strlen($pass)<8){ $err='Password must be at least 8 characters.'; $step='reset'; }
    elseif($pass!==$pass2){ $err='Passwords do not match.'; $step='reset'; }
    else {
        $hash=password_hash($pass,PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password=? WHERE email=?")->bind_param('ss',$hash,$email) && $conn->query("UPDATE users SET password='$hash' WHERE email='".mysqli_real_escape_string($conn,$email)."'");
        $stmt=$conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param('ss',$hash,$email); $stmt->execute();
        unset($_SESSION['fp_step'],$_SESSION['fp_email'],$_SESSION['fp_name']);
        redirect('login.php?msg=password_reset');
    }
}
?>
<div class="container" style="max-width:440px;padding-top:60px;padding-bottom:60px">
<?php if($step==='email'): ?>
  <form class="form" method="post">
    <input type="hidden" name="action" value="fp_email">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem">ðŸ”</div>
      <h1 style="font-size:1.5rem;margin:8px 0">Forgot Password?</h1>
      <p class="form-subtitle">Enter your email and we'll send a reset code</p>
    </div>
    <?php if($err) echo '<div class="alert error">âš  '.$err.'</div>'; ?>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="you@email.com" required autofocus>
    </div>
    <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">Send Reset Code â†’</button>
    <p style="text-align:center;margin-top:16px;font-size:0.88rem"><a href="login.php" style="color:var(--muted)">â† Back to Login</a></p>
  </form>

<?php elseif($step==='otp'): ?>
  <div class="form" style="text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">ðŸ“§</div>
    <h1 style="font-size:1.5rem;margin-bottom:8px">Enter Reset Code</h1>
    <p style="color:var(--muted);font-size:0.9rem;margin-bottom:28px">Sent to <strong><?=e($_SESSION['fp_email']??'')?></strong></p>
    <?php if($err)    echo '<div class="alert error">âš  '.$err.'</div>'; ?>
    <?php if($success) echo '<div class="alert success">âœ“ '.$success.'</div>'; ?>
    <form method="post" id="otpForm2">
      <input type="hidden" name="action" value="fp_otp">
      <div style="display:flex;gap:10px;justify-content:center;margin-bottom:20px">
        <?php for($i=0;$i<6;$i++): ?>
        <input type="text" class="otp-box2" maxlength="1" inputmode="numeric" pattern="[0-9]"
          style="width:46px;height:56px;text-align:center;font-size:1.4rem;font-weight:700;border:2px solid var(--border);border-radius:10px;background:var(--surface);color:var(--dark);outline:none"
          onfocus="this.style.borderColor='var(--gold)'" onblur="this.style.borderColor='var(--border)'">
        <?php endfor; ?>
        <input type="hidden" name="otp" id="fpOtpHidden">
      </div>
      <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">Verify Code â†’</button>
    </form>
  </div>

<?php else: /* reset */ ?>
  <form class="form" method="post">
    <input type="hidden" name="action" value="fp_reset">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem">âœ…</div>
      <h1 style="font-size:1.5rem;margin:8px 0">Set New Password</h1>
    </div>
    <?php if($err) echo '<div class="alert error">âš  '.$err.'</div>'; ?>
    <div class="form-group">
      <label>New Password</label>
      <input type="password" name="password" placeholder="Minimum 8 characters" required minlength="8">
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <input type="password" name="password2" placeholder="Re-enter password" required>
    </div>
    <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">Update Password â†’</button>
  </form>
<?php endif; ?>
</div>
<script>
var boxes2=document.querySelectorAll('.otp-box2');
boxes2.forEach(function(box,i){
  box.addEventListener('input',function(){ this.value=this.value.replace(/\D/,''); if(this.value&&boxes2[i+1])boxes2[i+1].focus(); collect2(); });
  box.addEventListener('keydown',function(e){ if(e.key==='Backspace'&&!this.value&&boxes2[i-1])boxes2[i-1].focus(); });
  box.addEventListener('paste',function(e){ var d=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6); boxes2.forEach(function(b,j){b.value=d[j]||'';}); collect2(); e.preventDefault(); });
});
function collect2(){ var h=document.getElementById('fpOtpHidden'); if(h)h.value=Array.from(boxes2).map(function(b){return b.value;}).join(''); }
var f2=document.getElementById('otpForm2');
if(f2)f2.addEventListener('submit',function(e){collect2();if(document.getElementById('fpOtpHidden').value.length<6){e.preventDefault();alert('Enter all 6 digits.');}});
</script>
<?php include 'includes/footer.php'; ?>
