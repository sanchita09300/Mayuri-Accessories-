<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$host = 'localhost'; $user = 'root'; $pass = ''; $db = 'mayuri_store';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die('Database connection failed: ' . $conn->connect_error); }
$conn->set_charset('utf8mb4');

// Safe auto-migrations for professional ecommerce features.
try {
    $conn->query("CREATE TABLE IF NOT EXISTS product_images (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, media_type ENUM('image','video') DEFAULT 'image', sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(product_id))");
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS media_type ENUM('image','video') DEFAULT 'image'");
    $conn->query("ALTER TABLE orders MODIFY order_status ENUM('Placed','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Placed'");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_charge DECIMAL(10,2) DEFAULT 0");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT ''");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount DECIMAL(10,2) DEFAULT 0");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(80) DEFAULT ''");
    $conn->query("CREATE TABLE IF NOT EXISTS coupons (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NOT NULL UNIQUE, discount_type ENUM('percent','flat') DEFAULT 'percent', discount_value DECIMAL(10,2) NOT NULL, min_order DECIMAL(10,2) DEFAULT 0, active TINYINT(1) DEFAULT 1, expires_at DATE NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS product_waitlist (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, user_id INT NOT NULL, customer_name VARCHAR(120) NOT NULL, email VARCHAR(150) DEFAULT '', phone VARCHAR(30) DEFAULT '', status ENUM('waiting','notified') DEFAULT 'waiting', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY unique_wait (product_id,user_id), INDEX(product_id), INDEX(user_id))");
    $conn->query("CREATE TABLE IF NOT EXISTS otp_verifications (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL, otp_code VARCHAR(6) NOT NULL, type ENUM('register','forgot_password') DEFAULT 'register', expires_at DATETIME NOT NULL, used TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(email), INDEX(type))");
    $conn->query("INSERT IGNORE INTO coupons(code,discount_type,discount_value,min_order,active) VALUES ('MAYURI10','percent',10,500,1),('WELCOME50','flat',50,300,1)");
} catch (Exception $ex) { error_log('MAYURI migration error: '.$ex->getMessage()); }

// Boys section columns. Separate block + SHOW COLUMNS check so it works on both MySQL and MariaDB.
try {
    if($conn->query("SHOW COLUMNS FROM products LIKE 'section'")->num_rows === 0) $conn->query("ALTER TABLE products ADD COLUMN section VARCHAR(20) NOT NULL DEFAULT 'main'");
    if($conn->query("SHOW COLUMNS FROM products LIKE 'mrp'")->num_rows === 0) $conn->query("ALTER TABLE products ADD COLUMN mrp DECIMAL(10,2) NULL DEFAULT NULL");
} catch (Exception $ex) { error_log('MAYURI boys migration error: '.$ex->getMessage()); }

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url){ if(!headers_sent()){ header('Location: '.$url); exit; } echo "<script>window.location.href='".addslashes($url)."';</script>"; exit; }
function is_logged_in(){ return isset($_SESSION['user_id']); }
function is_admin(){ return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function cart_count(){ return array_sum($_SESSION['cart'] ?? []); }
function require_admin(){ if(!is_admin()) redirect('../login.php'); }
function require_login(){ if(!is_logged_in()) redirect('login.php'); }
function csrf_token(){ if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_field(){ return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(){ if($_SERVER['REQUEST_METHOD']==='POST' && (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']))){ die('Invalid security token. Please go back and try again.'); } }
function delivery_charge($subtotal){ return $subtotal >= 500 ? 0 : 49; }
function estimate_delivery_text(){ return date('d M Y', strtotime('+3 days')).' - '.date('d M Y', strtotime('+5 days')); }
function apply_coupon_amount($conn,$code,$subtotal){
    $code = strtoupper(trim($code)); if($code==='') return [0,''];
    $stmt=$conn->prepare("SELECT * FROM coupons WHERE code=? AND active=1 AND (expires_at IS NULL OR expires_at>=CURDATE()) LIMIT 1");
    $stmt->bind_param('s',$code); $stmt->execute(); $c=$stmt->get_result()->fetch_assoc();
    if(!$c || $subtotal < (float)$c['min_order']) return [0,'Invalid coupon or minimum order not reached.'];
    $amount = $c['discount_type']==='percent' ? ($subtotal*(float)$c['discount_value']/100) : (float)$c['discount_value'];
    return [min($amount,$subtotal),'Coupon applied successfully.'];
}

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/fast2sms_config.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

function send_otp_email($email, $otp, $name = ''){
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try { $mail->isSMTP(); $mail->Host=SMTP_HOST; $mail->SMTPAuth=true; $mail->Username=SMTP_USERNAME; $mail->Password=SMTP_PASSWORD; $mail->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=SMTP_PORT; $mail->CharSet='UTF-8'; $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME); $mail->addAddress($email, $name ?: ''); $safeName=e($name ?: 'Valued Customer'); $mail->Subject='MAYURI - Your Verification Code'; $mail->isHTML(true); $mail->Body='<div style="font-family:Arial,sans-serif;max-width:420px;margin:auto;padding:24px;border:1px solid #eee;border-radius:10px"><h2 style="color:#b8860b;letter-spacing:2px;text-align:center">MAYURI</h2><p>Hello '.$safeName.',</p><p>Your verification code is:</p><p style="font-size:32px;font-weight:bold;letter-spacing:6px;text-align:center;color:#333">'.e($otp).'</p><p style="color:#777;font-size:13px">This code expires in 10 minutes. Do not share it with anyone.</p></div>'; $mail->AltBody="Your MAYURI verification code is: $otp"; $mail->send(); return true; }
    catch(Exception $e){ error_log('MAYURI mail error: '.$mail->ErrorInfo); error_log("MAYURI OTP email failed for $email. OTP: $otp"); return false; }
}
function send_otp_sms($phone,$otp){ require_once __DIR__.'/fast2sms_config.php'; $phone=preg_replace('/\D/','',$phone); if(strlen($phone)==12 && substr($phone,0,2)=='91') $phone=substr($phone,2); if(strlen($phone)!=10) return ['status'=>false,'message'=>'Invalid phone number.']; $postData=['route'=>'otp','variables_values'=>$otp,'numbers'=>$phone]; $curl=curl_init(); curl_setopt_array($curl,[CURLOPT_URL=>'https://www.fast2sms.com/dev/bulkV2',CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($postData),CURLOPT_HTTPHEADER=>['authorization: '.FAST2SMS_API_KEY,'accept: */*','cache-control: no-cache','content-type: application/json']]); $response=curl_exec($curl); $error=curl_error($curl); $httpCode=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl); if($error) return ['status'=>false,'message'=>'cURL Error: '.$error]; return ['status'=>$httpCode==200,'http_code'=>$httpCode,'response'=>json_decode($response,true),'raw_response'=>$response]; }
function generate_otp($conn,$email,$type='register'){
    $otp = str_pad((string)random_int(100000,999999), 6, '0', STR_PAD_LEFT);

    // Session backup: even if DB insert fails/timezone differs, correct OTP will still verify on same browser.
    $_SESSION['last_otp'][$type][$email] = [
        'code' => $otp,
        'expires' => time() + 600,
        'used' => false
    ];

    try{
        $stmt=$conn->prepare("UPDATE otp_verifications SET used=1 WHERE email=? AND type=?");
        $stmt->bind_param('ss',$email,$type);
        $stmt->execute();

        // Use MySQL server time to avoid PHP timezone vs MySQL NOW() mismatch.
        $stmt=$conn->prepare("INSERT INTO otp_verifications(email,otp_code,type,expires_at) VALUES(?,?,?,DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->bind_param('sss',$email,$otp,$type);
        $stmt->execute();
    }catch(Exception $ex){
        error_log('OTP DB error: '.$ex->getMessage());
    }
    error_log("MAYURI OTP for $email: $otp");
    return $otp;
}
function verify_otp($conn,$email,$otp,$type='register'){
    $otp = preg_replace('/\D/', '', (string)$otp);
    if(strlen($otp) !== 6) return false;

    // First verify from DB.
    try{
        $stmt=$conn->prepare("SELECT id FROM otp_verifications WHERE email=? AND otp_code=? AND type=? AND used=0 AND expires_at>NOW() ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('sss',$email,$otp,$type);
        $stmt->execute();
        $row=$stmt->get_result()->fetch_assoc();
        if($row){
            $stmt=$conn->prepare("UPDATE otp_verifications SET used=1 WHERE id=?");
            $stmt->bind_param('i',$row['id']);
            $stmt->execute();
            if(isset($_SESSION['last_otp'][$type][$email])) $_SESSION['last_otp'][$type][$email]['used'] = true;
            return true;
        }
    }catch(Exception $ex){
        error_log('OTP verify DB error: '.$ex->getMessage());
    }

    // Backup verification from session.
    $saved = $_SESSION['last_otp'][$type][$email] ?? null;
    if($saved && empty($saved['used']) && time() <= (int)$saved['expires'] && hash_equals((string)$saved['code'], $otp)){
        $_SESSION['last_otp'][$type][$email]['used'] = true;
        return true;
    }
    return false;
}
