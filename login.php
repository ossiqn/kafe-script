<?php
session_start();
require_once 'config.php';

$mesaj    = '';
$basarili = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['islem'] ?? '') === 'giris') {
    $kadi  = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
    $sorgu->execute([$kadi]);
    $kullanici = $sorgu->fetch();
    if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
        $_SESSION['kullanici'] = $kullanici['kullanici_adi'];
        $_SESSION['rol']       = $kullanici['rol'];
        $basarili = true;
        $yonlen   = match($kullanici['rol']) {
            'admin'   => 'admin.php',
            'barista' => 'barista.php',
            'barmen'  => 'barmen.php',
            default   => 'garson.php'
        };
        header("Refresh: 1; url=$yonlen");
    } else {
        $mesaj = "Kullanıcı adı veya şifre hatalı.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Personel Girişi | Lumière</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Poppins:wght@200;300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--gold:#D4AF37;--gold-dim:rgba(212,175,55,0.22);}
*,*::before,*::after{box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{margin:0;font-family:'Poppins',sans-serif;background:#050503;color:#f0ece0;min-height:100vh;display:flex;justify-content:center;align-items:center;overflow:hidden;}
.bg{position:fixed;inset:0;background:url('https://images.unsplash.com/photo-1497935586351-b67a49e012bf?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;filter:blur(12px) brightness(0.3);transform:scale(1.08);z-index:0;}
.wrap{position:relative;z-index:1;width:90%;max-width:400px;}
.card{background:rgba(8,7,5,0.92);border:1px solid var(--gold-dim);border-radius:18px;padding:52px 40px 44px;backdrop-filter:blur(24px);box-shadow:0 32px 64px rgba(0,0,0,0.65),inset 0 1px 0 rgba(212,175,55,0.07);}
.brand{text-align:center;margin-bottom:40px;}
.brand-name{font-family:'Playfair Display',serif;font-size:2em;font-weight:700;color:var(--gold);letter-spacing:4px;display:block;margin-bottom:6px;}
.brand-sub{font-size:0.65em;color:rgba(212,175,55,0.45);letter-spacing:3px;text-transform:uppercase;font-weight:300;}
.brand-line{width:38px;height:1px;background:var(--gold-dim);margin:16px auto 0;}
.fg{margin-bottom:20px;}
.fg label{display:block;font-size:0.65em;font-weight:600;color:rgba(212,175,55,0.55);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;}
.fg input{width:100%;padding:12px 15px;background:rgba(255,255,255,0.025);border:1px solid rgba(255,255,255,0.07);border-radius:9px;color:#f0ece0;font-family:'Poppins',sans-serif;font-size:0.9em;outline:none;transition:border-color .2s,box-shadow .2s;}
.fg input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(212,175,55,0.1);}
input:-webkit-autofill{-webkit-box-shadow:0 0 0 30px #0d0b07 inset !important;-webkit-text-fill-color:#f0ece0 !important;}
.btn-submit{width:100%;padding:13px;margin-top:8px;background:transparent;border:1px solid var(--gold);border-radius:9px;color:var(--gold);font-family:'Poppins',sans-serif;font-size:0.8em;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;cursor:pointer;transition:all .3s;}
.btn-submit:hover{background:var(--gold);color:#080600;box-shadow:0 0 26px rgba(212,175,55,0.28);}
.alert{background:rgba(200,50,50,0.1);border:1px solid rgba(200,50,50,0.18);border-radius:8px;color:#d07070;font-size:0.78em;text-align:center;padding:11px 14px;margin-bottom:20px;}
.back-link{display:block;text-align:center;margin-top:22px;font-size:0.68em;color:rgba(212,175,55,0.3);text-decoration:none;letter-spacing:1.5px;text-transform:uppercase;transition:color .2s;}
.back-link:hover{color:var(--gold);}
.success-screen{position:fixed;inset:0;background:#050503;display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:999;gap:18px;}
.spinner{width:46px;height:46px;border:2px solid rgba(212,175,55,0.12);border-top-color:var(--gold);border-radius:50%;animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.success-text{font-family:'Playfair Display',serif;font-size:1.35em;color:var(--gold);letter-spacing:1px;}
@media(max-width:480px){.card{padding:38px 22px 32px;border-radius:14px}.brand-name{font-size:1.75em}}
</style>
</head>
<body>
<div class="bg"></div>
<?php if($basarili): ?>
<div class="success-screen"><div class="spinner"></div><div class="success-text">Giriş Başarılı</div></div>
<?php else: ?>
<div class="wrap"><div class="card">
<div class="brand">
    <span class="brand-name">LUMIÈRE</span>
    <span class="brand-sub">Personel Portalı</span>
    <div class="brand-line"></div>
</div>
<?php if($mesaj): ?><div class="alert"><?= htmlspecialchars($mesaj) ?></div><?php endif; ?>
<form method="POST" autocomplete="off">
    <input type="hidden" name="islem" value="giris">
    <div class="fg"><label>Kullanıcı Adı</label><input type="text" name="kullanici_adi" required autocomplete="new-password"></div>
    <div class="fg"><label>Parola</label><input type="password" name="sifre" required autocomplete="new-password"></div>
    <button type="submit" class="btn-submit">Sisteme Giriş</button>
</form>
<a href="index.php" class="back-link">← Müşteri Menüsüne Dön</a>
</div></div>
<?php endif; ?>
</body>
</html>