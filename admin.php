<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['kullanici']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mesaj    = '';
$mesajTip = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['ayar_guncelle'])) {
        $db->prepare("UPDATE ayarlar SET kafe_adi=?,hakkimizda=?,adres=?,telefon=?,eposta=?,instagram=?,personel_referans=? WHERE id=1")
           ->execute([$_POST['kafe_adi'],$_POST['hakkimizda'],$_POST['adres'],$_POST['telefon'],$_POST['eposta'],$_POST['instagram'],$_POST['personel_referans']??'']);
        $mesaj = 'Ayarlar güncellendi.'; $mesajTip = 'ok';
    }

    if (isset($_POST['kategori_ekle'])) {
        $katAd = trim($_POST['kategori_adi']);
        if ($katAd !== '') {
            try {
                $mevcut = $db->prepare("SELECT id FROM kategoriler WHERE ad = ?");
                $mevcut->execute([$katAd]);
                if ($mevcut->fetch()) {
                    $mesaj = '"'.$katAd.'" zaten mevcut.'; $mesajTip = 'warn';
                } else {
                    $sira = (int)$db->query("SELECT COALESCE(MAX(sira),0)+1 FROM kategoriler")->fetchColumn();
                    $db->prepare("INSERT INTO kategoriler (ad, sira) VALUES (?,?)")->execute([$katAd, $sira]);
                    $mesaj = '"'.$katAd.'" kategorisi oluşturuldu.'; $mesajTip = 'ok';
                }
            } catch (PDOException $e) {
                $mesaj = 'Kategori eklenemedi.'; $mesajTip = 'err';
            }
        }
    }

    if (isset($_POST['kategori_duzenle'])) {
        $db->prepare("UPDATE kategoriler SET ad=?, sira=? WHERE id=?")
           ->execute([trim($_POST['kat_ad']), (int)$_POST['kat_sira'], (int)$_POST['kat_id']]);
        $db->prepare("UPDATE urunler SET kategori=? WHERE kategori_id=?")->execute([trim($_POST['kat_ad']), (int)$_POST['kat_id']]);
        $mesaj = 'Kategori güncellendi.'; $mesajTip = 'ok';
    }

    if (isset($_POST['urun_ekle'])) {
        $katId  = (int)$_POST['kategori_id'];
        $katAdi = '';
        if ($katId > 0) {
            $r = $db->prepare("SELECT ad FROM kategoriler WHERE id=?");
            $r->execute([$katId]);
            $katAdi = $r->fetchColumn() ?: '';
        }
        $db->prepare("INSERT INTO urunler (kategori_id,kategori,ad,aciklama,fiyat) VALUES (?,?,?,?,?)")
           ->execute([$katId ?: null, $katAdi, trim($_POST['ad']), trim($_POST['aciklama']), $_POST['fiyat']]);
        $mesaj = '"'.htmlspecialchars(trim($_POST['ad'])).'" eklendi.'; $mesajTip = 'ok';
    }

    if (isset($_POST['urun_duzenle'])) {
        $katId  = (int)$_POST['kategori_id'];
        $katAdi = '';
        if ($katId > 0) {
            $r = $db->prepare("SELECT ad FROM kategoriler WHERE id=?");
            $r->execute([$katId]);
            $katAdi = $r->fetchColumn() ?: '';
        }
        $db->prepare("UPDATE urunler SET kategori_id=?,kategori=?,ad=?,aciklama=?,fiyat=? WHERE id=?")
           ->execute([$katId ?: null, $katAdi, trim($_POST['ad']), trim($_POST['aciklama']), $_POST['fiyat'], (int)$_POST['urun_id']]);
        $mesaj = 'Ürün güncellendi.'; $mesajTip = 'ok';
    }

    if (isset($_POST['kullanici_ekle'])) {
        try {
            $db->prepare("INSERT INTO kullanicilar (kullanici_adi,sifre,rol) VALUES (?,?,?)")
               ->execute([trim($_POST['kadi']), password_hash($_POST['sifre'], PASSWORD_DEFAULT), $_POST['rol']]);
            $mesaj = '"'.htmlspecialchars(trim($_POST['kadi'])).'" eklendi.'; $mesajTip = 'ok';
        } catch (PDOException $e) {
            $mesaj = 'Bu kullanıcı adı zaten mevcut.'; $mesajTip = 'err';
        }
    }

    if (isset($_POST['sifre_guncelle'])) {
        $db->prepare("UPDATE kullanicilar SET sifre=? WHERE id=?")
           ->execute([password_hash($_POST['yeni_sifre'], PASSWORD_DEFAULT), (int)$_POST['user_id']]);
        $mesaj = 'Şifre güncellendi.'; $mesajTip = 'ok';
    }
}

if (isset($_GET['sil_urun'])) {
    $db->prepare("DELETE FROM urunler WHERE id=?")->execute([(int)$_GET['sil_urun']]);
    header('Location: admin.php?tab=urunler&msj=urun_silindi'); exit;
}
if (isset($_GET['sil_kat'])) {
    $kid = (int)$_GET['sil_kat'];
    $db->prepare("UPDATE urunler SET kategori_id=NULL, kategori='' WHERE kategori_id=?")->execute([$kid]);
    $db->prepare("DELETE FROM kategoriler WHERE id=?")->execute([$kid]);
    header('Location: admin.php?tab=urunler&msj=kat_silindi'); exit;
}
if (isset($_GET['sil_user'])) {
    $db->prepare("DELETE FROM kullanicilar WHERE id=?")->execute([(int)$_GET['sil_user']]);
    header('Location: admin.php?tab=kullanicilar&msj=user_silindi'); exit;
}

$ayarlar      = $db->query("SELECT * FROM ayarlar LIMIT 1")->fetch();
$kategoriler  = $db->query("SELECT * FROM kategoriler ORDER BY sira ASC, ad ASC")->fetchAll();
$urunler      = $db->query("SELECT u.*, k.ad AS kat_adi FROM urunler u LEFT JOIN kategoriler k ON u.kategori_id=k.id ORDER BY k.sira ASC, u.ad ASC")->fetchAll();
$kullanicilar = $db->query("SELECT * FROM kullanicilar ORDER BY rol ASC")->fetchAll();
$siparisler   = $db->query("SELECT * FROM siparisler ORDER BY tarih DESC LIMIT 20")->fetchAll();

$urunlerByKat = [];
foreach ($urunler as $u) {
    $key = $u['kategori'] ?: 'Kategorisiz';
    $urunlerByKat[$key][] = $u;
}

$toplamUrun      = count($urunler);
$toplamKategori  = count($kategoriler);
$toplamPersonel  = count($kullanicilar);
$toplamSiparis   = (int)$db->query("SELECT COUNT(*) FROM siparisler")->fetchColumn();
$bekleyenSiparis = (int)$db->query("SELECT COUNT(*) FROM siparisler WHERE durum=0")->fetchColumn();

if (isset($_GET['msj'])) {
    $mm = ['urun_silindi'=>['Ürün kaldırıldı.','warn'],'kat_silindi'=>['Kategori silindi.','warn'],'user_silindi'=>['Kullanıcı silindi.','warn']];
    if (isset($mm[$_GET['msj']])) [$mesaj,$mesajTip] = $mm[$_GET['msj']];
}

$aktifTab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yönetim | <?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root[data-theme="light"] {
    --bg:        #f4f1eb;
    --bg2:       #ede9e0;
    --sidebar:   #ffffff;
    --card:      #ffffff;
    --card2:     #faf8f4;
    --text:      #2a2318;
    --text2:     #6b5e4a;
    --text3:     #a89880;
    --border:    #e8e0d0;
    --border2:   #d4c8b4;
    --gold:      #9a7d3a;
    --gold2:     #c4a052;
    --gold-bg:   rgba(154,125,58,0.09);
    --gold-glow: rgba(154,125,58,0.2);
    --red:       #c0392b;
    --red-bg:    rgba(192,57,43,0.08);
    --green:     #2d7a4f;
    --green-bg:  rgba(45,122,79,0.08);
    --sh:        0 2px 16px rgba(42,35,24,0.07);
    --sh-lg:     0 10px 36px rgba(42,35,24,0.11);
    --inp:       #ffffff;
    --inp-b:     #d4c8b4;
    --sw:        260px;
    --r:         13px;
    --rs:        8px;
    --t:         0.2s ease;
}
:root[data-theme="dark"] {
    --bg:        #080705;
    --bg2:       #0d0b08;
    --sidebar:   #0a0906;
    --card:      #0f0d09;
    --card2:     #131008;
    --text:      #ede8df;
    --text2:     #a09070;
    --text3:     #5a4e3a;
    --border:    #1e1a12;
    --border2:   #2a2418;
    --gold:      #D4AF37;
    --gold2:     #e8c84a;
    --gold-bg:   rgba(212,175,55,0.08);
    --gold-glow: rgba(212,175,55,0.2);
    --red:       #e05555;
    --red-bg:    rgba(224,85,85,0.08);
    --green:     #4caf82;
    --green-bg:  rgba(76,175,130,0.08);
    --sh:        0 2px 16px rgba(0,0,0,0.35);
    --sh-lg:     0 10px 36px rgba(0,0,0,0.55);
    --inp:       rgba(255,255,255,0.02);
    --inp-b:     #2a2418;
    --sw:        260px;
    --r:         13px;
    --rs:        8px;
    --t:         0.2s ease;
}

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; font-size:14px; transition:background var(--t),color var(--t); }
::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--border2); border-radius:4px; }

.sidebar { width:var(--sw); background:var(--sidebar); border-right:1px solid var(--border); display:flex; flex-direction:column; flex-shrink:0; transition:background var(--t),border-color var(--t); }
.sb-brand { padding:30px 22px 24px; border-bottom:1px solid var(--border); text-align:center; }
.sb-logo { font-family:'Playfair Display',serif; font-size:1.5em; font-weight:700; color:var(--gold); letter-spacing:3px; display:block; margin-bottom:4px; }
.sb-sub { font-size:0.63em; color:var(--text3); letter-spacing:2.5px; text-transform:uppercase; }
.sb-nav { padding:18px 12px; flex:1; overflow-y:auto; }
.sb-label { font-size:0.6em; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--text3); padding:12px 10px 5px; }
.nb { display:flex; align-items:center; gap:10px; width:100%; padding:10px 12px; background:transparent; border:none; color:var(--text2); text-align:left; cursor:pointer; border-radius:var(--rs); margin-bottom:2px; font-family:'Poppins'; font-size:0.8em; font-weight:500; transition:all var(--t); text-decoration:none; position:relative; }
.nb:hover { background:var(--gold-bg); color:var(--gold); }
.nb.active { background:var(--gold-bg); color:var(--gold); font-weight:600; }
.nb.active::before { content:''; position:absolute; left:0; top:18%; bottom:18%; width:3px; background:var(--gold); border-radius:0 3px 3px 0; }
.nb-ic { font-size:1em; width:18px; text-align:center; flex-shrink:0; }
.nb-badge { margin-left:auto; background:var(--gold); color:var(--sidebar); font-size:0.65em; font-weight:700; padding:2px 7px; border-radius:20px; min-width:20px; text-align:center; }
.sb-foot { padding:14px 12px; border-top:1px solid var(--border); display:flex; flex-direction:column; gap:2px; }
.nb-red { color:var(--red) !important; }
.nb-red:hover { background:var(--red-bg) !important; }

.main { flex:1; display:flex; flex-direction:column; overflow:hidden; }
.topbar { background:var(--card); border-bottom:1px solid var(--border); padding:0 28px; height:56px; display:flex; align-items:center; gap:12px; flex-shrink:0; transition:background var(--t),border-color var(--t); }
.tb-title { flex:1; font-family:'Playfair Display',serif; font-size:1.15em; font-weight:600; letter-spacing:0.2px; }
.tb-user { font-size:0.75em; color:var(--text3); background:var(--gold-bg); border:1px solid var(--border2); padding:5px 12px; border-radius:20px; }
.tb-user span { color:var(--gold); font-weight:600; }
.theme-btn { width:36px; height:36px; background:var(--bg2); border:1px solid var(--border); border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:1em; transition:all var(--t); color:var(--text2); }
.theme-btn:hover { border-color:var(--gold); color:var(--gold); }

.content { flex:1; overflow-y:auto; padding:26px 28px; }

.tab-pane { display:none; animation:fu .28s ease; }
.tab-pane.active { display:block; }
@keyframes fu { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }

.ph { display:flex; align-items:center; gap:12px; margin-bottom:22px; }
.ph-title { font-family:'Playfair Display',serif; font-size:1.65em; font-weight:700; flex:1; }
.ph-title span { color:var(--gold); }

.sg { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.sc { background:var(--card); border:1px solid var(--border); border-radius:var(--r); padding:20px 22px; box-shadow:var(--sh); position:relative; overflow:hidden; transition:transform var(--t),box-shadow var(--t),border-color var(--t); cursor:default; }
.sc:hover { transform:translateY(-2px); box-shadow:var(--sh-lg); border-color:var(--border2); }
.sc::after { content:''; position:absolute; top:-18px; right:-18px; width:70px; height:70px; border-radius:50%; opacity:0.07; pointer-events:none; }
.sc-g::after { background:var(--gold); }
.sc-gr::after { background:var(--green); }
.sc-r::after { background:var(--red); }
.sc-b::after { background:#4a90d9; }
.sc-icon { font-size:1.4em; margin-bottom:9px; }
.sc-val { font-family:'Playfair Display',serif; font-size:2em; font-weight:700; line-height:1; margin-bottom:4px; }
.sc-lbl { font-size:0.68em; color:var(--text3); text-transform:uppercase; letter-spacing:1.5px; font-weight:500; }
.sc-sub { font-size:0.7em; color:var(--gold); margin-top:4px; font-weight:500; }

.panel { background:var(--card); border:1px solid var(--border); border-radius:var(--r); box-shadow:var(--sh); margin-bottom:18px; overflow:hidden; transition:background var(--t),border-color var(--t); }
.ph2 { padding:16px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
.ph2 h3 { font-family:'Playfair Display',serif; font-size:1.05em; font-weight:600; color:var(--gold); flex:1; margin:0; }
.pb { padding:22px; }
.pb-s { padding:16px 22px; }

.g2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.g3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
.sp2 { grid-column:span 2; }

.fg { margin-bottom:16px; }
.fl { display:block; font-size:0.67em; font-weight:600; color:var(--text3); margin-bottom:6px; text-transform:uppercase; letter-spacing:1.2px; }
.fi,.fs,.ft { width:100%; padding:10px 13px; background:var(--inp); border:1px solid var(--inp-b); border-radius:var(--rs); color:var(--text); font-family:'Poppins'; font-size:0.88em; outline:none; transition:border-color var(--t),box-shadow var(--t); }
.fi:focus,.fs:focus,.ft:focus { border-color:var(--gold); box-shadow:0 0 0 3px var(--gold-glow); }
.ft { resize:vertical; min-height:80px; line-height:1.6; }
.fs { cursor:pointer; }
input:-webkit-autofill { -webkit-box-shadow:0 0 0 30px var(--inp) inset !important; -webkit-text-fill-color:var(--text) !important; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:var(--rs); font-family:'Poppins'; font-size:0.77em; font-weight:600; cursor:pointer; border:none; transition:all var(--t); letter-spacing:0.4px; text-decoration:none; white-space:nowrap; }
.btn-gold { background:var(--gold); color:#fff; }
.btn-gold:hover { filter:brightness(1.1); transform:translateY(-1px); box-shadow:0 5px 18px var(--gold-glow); }
.btn-out { background:transparent; border:1px solid var(--border2); color:var(--text2); }
.btn-out:hover { border-color:var(--gold); color:var(--gold); background:var(--gold-bg); }
.btn-del { background:var(--red-bg); border:1px solid transparent; color:var(--red); }
.btn-del:hover { background:var(--red); color:#fff; }
.btn-ok { background:var(--green-bg); border:1px solid transparent; color:var(--green); }
.btn-ok:hover { background:var(--green); color:#fff; }
.btn-sm { padding:5px 10px; font-size:0.72em; }
.btn-full { width:100%; justify-content:center; padding:12px; }

.dt { width:100%; border-collapse:collapse; }
.dt th { text-align:left; font-size:0.65em; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:var(--text3); padding:11px 14px; border-bottom:2px solid var(--border); white-space:nowrap; }
.dt td { padding:13px 14px; border-bottom:1px solid var(--border); font-size:0.84em; vertical-align:middle; }
.dt tr:last-child td { border-bottom:none; }
.dt tbody tr { transition:background var(--t); }
.dt tbody tr:hover { background:var(--bg2); }

.badge { display:inline-flex; align-items:center; font-size:0.67em; font-weight:700; padding:3px 9px; border-radius:20px; letter-spacing:0.4px; text-transform:uppercase; }
.bg-g { background:var(--gold-bg); color:var(--gold); border:1px solid rgba(154,125,58,.18); }
.bg-gr { background:var(--green-bg); color:var(--green); border:1px solid rgba(45,122,79,.18); }
.bg-r { background:var(--red-bg); color:var(--red); border:1px solid rgba(192,57,43,.18); }
.bg-n { background:var(--bg2); color:var(--text3); border:1px solid var(--border); }

.kat-hd { display:flex; align-items:center; gap:10px; padding:13px 18px; background:var(--card2); border-bottom:1px solid var(--border); cursor:pointer; user-select:none; transition:background var(--t); }
.kat-hd:hover { background:var(--bg2); }
.kat-name { font-family:'Playfair Display',serif; font-size:0.98em; font-weight:600; color:var(--gold); flex:1; }
.kat-cnt { font-size:0.7em; color:var(--text3); }
.kat-arr { font-size:0.7em; color:var(--text3); transition:transform var(--t); }
.kat-hd.open .kat-arr { transform:rotate(180deg); }
.kat-body.hidden { display:none; }

.modal-ov { position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); z-index:1000; display:none; align-items:center; justify-content:center; padding:16px; }
.modal-ov.open { display:flex; }
.modal-box { background:var(--card); border:1px solid var(--border2); border-radius:16px; width:100%; max-width:500px; box-shadow:var(--sh-lg); animation:fu .2s ease; overflow:hidden; }
.mh { padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
.mh h4 { font-family:'Playfair Display',serif; font-size:1.05em; font-weight:600; color:var(--gold); flex:1; margin:0; }
.mx { width:28px; height:28px; background:var(--bg2); border:1px solid var(--border); border-radius:7px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:0.9em; color:var(--text3); transition:all var(--t); }
.mx:hover { background:var(--red-bg); color:var(--red); border-color:transparent; }
.mb { padding:20px 22px; }
.mf { padding:14px 22px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }

.toast-w { position:fixed; top:18px; right:18px; z-index:9999; }
.toast { background:var(--card); border:1px solid var(--border2); border-radius:var(--rs); padding:12px 16px; font-size:0.8em; font-weight:500; display:flex; align-items:center; gap:9px; box-shadow:var(--sh-lg); animation:fu .28s ease; min-width:240px; }
.toast.ok  { border-left:3px solid var(--green); }
.toast.err { border-left:3px solid var(--red); }
.toast.warn { border-left:3px solid var(--gold); }

.empty { text-align:center; padding:44px 20px; color:var(--text3); }
.empty-ic { font-size:2.2em; opacity:.35; margin-bottom:10px; }
.empty-t { font-family:'Playfair Display',serif; font-size:1.1em; color:var(--text2); margin-bottom:5px; }
.empty-d { font-size:0.78em; }

.fw6 { font-weight:600; }
.tc-g { color:var(--gold); }
.tc-2 { color:var(--text2); }
.tc-3 { color:var(--text3); }
.tc-r { color:var(--red); }
.txt-s { font-size:0.78em; }
.mt8 { margin-top:8px; }
.mt12 { margin-top:12px; }
.mt16 { margin-top:16px; }
.mb16 { margin-bottom:16px; }
.flex { display:flex; }
.fxc { display:flex; align-items:center; }
.gap6 { gap:6px; }
.gap8 { gap:8px; }

@media(max-width:1100px) { .sg { grid-template-columns:repeat(2,1fr); } .g3 { grid-template-columns:1fr 1fr; } }
@media(max-width:860px) {
    body { flex-direction:column; overflow:auto; height:auto; }
    .sidebar { width:100%; border-right:none; border-bottom:1px solid var(--border); flex-direction:row; flex-wrap:wrap; }
    .sb-brand { padding:14px 18px; border-bottom:none; border-right:1px solid var(--border); }
    .sb-nav { flex:1; display:flex; flex-wrap:wrap; padding:8px; }
    .sb-label { display:none; }
    .nb { width:auto; margin:2px; padding:8px 11px; font-size:0.74em; }
    .nb.active::before { display:none; }
    .sb-foot { flex-direction:row; flex-wrap:wrap; border-top:none; border-left:1px solid var(--border); padding:8px; }
    .main { min-height:0; }
    .content { padding:16px; }
    .topbar { padding:0 16px; }
    .g2,.g3 { grid-template-columns:1fr; }
    .sg { grid-template-columns:1fr 1fr; }
    .sp2 { grid-column:span 1; }
}
@media(max-width:480px) {
    .sg { grid-template-columns:1fr 1fr; gap:10px; }
    .pb,.pb-s { padding:14px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sb-brand">
        <span class="sb-logo">LUMIÈRE</span>
        <span class="sb-sub">Yönetim Paneli</span>
    </div>
    <nav class="sb-nav">
        <div class="sb-label">Ana</div>
        <button class="nb <?= $aktifTab==='dashboard'?'active':'' ?>" onclick="sw('dashboard')">
            <span class="nb-ic">◈</span>Özet
        </button>
        <div class="sb-label" style="margin-top:8px">İçerik</div>
        <button class="nb <?= $aktifTab==='urunler'?'active':'' ?>" onclick="sw('urunler')">
            <span class="nb-ic">✦</span>Menü & Ürünler
            <?php if($toplamUrun>0): ?><span class="nb-badge"><?= $toplamUrun ?></span><?php endif; ?>
        </button>
        <button class="nb <?= $aktifTab==='ayarlar'?'active':'' ?>" onclick="sw('ayarlar')">
            <span class="nb-ic">◎</span>Kafe Ayarları
        </button>
        <div class="sb-label" style="margin-top:8px">Sistem</div>
        <button class="nb <?= $aktifTab==='kullanicilar'?'active':'' ?>" onclick="sw('kullanicilar')">
            <span class="nb-ic">◇</span>Ekip & Yetki
            <span class="nb-badge"><?= $toplamPersonel ?></span>
        </button>
    </nav>
    <div class="sb-foot">
        <a href="index.php" target="_blank" class="nb"><span class="nb-ic">↗</span>Siteyi Gör</a>
        <a href="logout.php" class="nb nb-red"><span class="nb-ic">⊗</span>Çıkış</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="tb-title" id="tb-title">Özet</div>
        <div class="fxc gap8">
            <div class="tb-user">👤 <span><?= htmlspecialchars($_SESSION['kullanici']) ?></span></div>
            <button class="theme-btn" id="themeBtn" onclick="toggleTheme()">🌙</button>
        </div>
    </div>

    <div class="content">

    <div class="tab-pane <?= $aktifTab==='dashboard'?'active':'' ?>" id="tab-dashboard">
        <div class="ph">
            <div class="ph-title">Hoş geldin, <span><?= htmlspecialchars($_SESSION['kullanici']) ?></span></div>
        </div>
        <div class="sg">
            <div class="sc sc-g"><div class="sc-icon">☕</div><div class="sc-val"><?= $toplamUrun ?></div><div class="sc-lbl">Menüde Ürün</div><div class="sc-sub"><?= $toplamKategori ?> kategori</div></div>
            <div class="sc sc-b"><div class="sc-icon">◇</div><div class="sc-val"><?= $toplamPersonel ?></div><div class="sc-lbl">Personel</div><div class="sc-sub">Kayıtlı hesap</div></div>
            <div class="sc sc-gr"><div class="sc-icon">📋</div><div class="sc-val"><?= $toplamSiparis ?></div><div class="sc-lbl">Toplam Sipariş</div><div class="sc-sub"><?= $bekleyenSiparis ?> bekleyen</div></div>
            <div class="sc sc-g"><div class="sc-icon">✦</div><div class="sc-val"><?= $toplamKategori ?></div><div class="sc-lbl">Menü Kategorisi</div><div class="sc-sub">Aktif bölüm</div></div>
        </div>
        <div class="g2">
            <div class="panel">
                <div class="ph2"><h3>Menü Kategorileri</h3><button class="btn btn-out btn-sm" onclick="sw('urunler')">Yönet →</button></div>
                <div class="pb-s">
                    <?php if(empty($kategoriler)): ?>
                    <div class="empty" style="padding:20px"><div class="empty-ic">☕</div><div class="empty-t">Henüz kategori yok</div></div>
                    <?php else: ?>
                    <table class="dt">
                        <thead><tr><th>Kategori</th><th>Ürün</th><th>Fiyat Aralığı</th></tr></thead>
                        <tbody>
                        <?php foreach($kategoriler as $k):
                            $items = $urunlerByKat[$k['ad']] ?? [];
                            $fiyatlar = array_column($items,'fiyat');
                            $mn = $fiyatlar ? min($fiyatlar) : 0;
                            $mx2 = $fiyatlar ? max($fiyatlar) : 0;
                        ?>
                        <tr>
                            <td><span class="badge bg-g"><?= htmlspecialchars($k['ad']) ?></span></td>
                            <td class="fw6"><?= count($items) ?></td>
                            <td class="tc-3"><?= $mn==$mx2 ? number_format($mn,2).' ₺' : number_format($mn,2).'–'.number_format($mx2,2).' ₺' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel">
                <div class="ph2">
                    <h3>Son Siparişler</h3>
                    <?php if($bekleyenSiparis>0): ?><span class="badge bg-r">⚡ <?= $bekleyenSiparis ?> Bekleyen</span><?php endif; ?>
                </div>
                <div class="pb-s">
                    <?php if(empty($siparisler)): ?>
                    <div class="empty" style="padding:20px"><div class="empty-ic">📋</div><div class="empty-t">Sipariş yok</div></div>
                    <?php else: ?>
                    <table class="dt">
                        <thead><tr><th>Masa</th><th>Durum</th><th>Tarih</th></tr></thead>
                        <tbody>
                        <?php foreach(array_slice($siparisler,0,8) as $s): ?>
                        <tr>
                            <td class="fw6">Masa <?= htmlspecialchars($s['masa_no']) ?></td>
                            <td>
                                <?php if($s['durum']==0): ?><span class="badge bg-r">Bekliyor</span>
                                <?php elseif($s['durum']==1): ?><span class="badge bg-g">Hazırlanıyor</span>
                                <?php else: ?><span class="badge bg-gr">Tamamlandı</span><?php endif; ?>
                            </td>
                            <td class="tc-3"><?= date('d.m H:i',strtotime($s['tarih'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="panel">
            <div class="ph2"><h3>Ekip</h3><button class="btn btn-out btn-sm" onclick="sw('kullanicilar')">Yönet →</button></div>
            <div class="pb-s">
                <table class="dt">
                    <thead><tr><th>Ad</th><th>Rol</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach($kullanicilar as $k):
                        $rb=['admin'=>'bg-r','garson'=>'bg-g','barista'=>'bg-gr'];
                        $rl=['admin'=>'Admin','garson'=>'Garson','barista'=>'Barista'];
                    ?>
                    <tr>
                        <td class="fw6"><?= htmlspecialchars($k['kullanici_adi']) ?></td>
                        <td><span class="badge <?= $rb[$k['rol']]??'bg-n' ?>"><?= $rl[$k['rol']]??$k['rol'] ?></span></td>
                        <td class="tc-3 txt-s"><?= $k['kullanici_adi']===$_SESSION['kullanici']?'● Aktif':'' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane <?= $aktifTab==='urunler'?'active':'' ?>" id="tab-urunler">
        <div class="ph">
            <div class="ph-title">Menü <span>& Ürünler</span></div>
            <button class="btn btn-out btn-sm" onclick="openModal('katEkleModal')">+ Yeni Kategori</button>
            <button class="btn btn-gold" onclick="openModal('urunEkleModal')">+ Yeni Ürün</button>
        </div>

        <?php if(empty($kategoriler) && empty($urunlerByKat)): ?>
        <div class="panel"><div class="pb"><div class="empty"><div class="empty-ic">☕</div><div class="empty-t">Menü Boş</div><div class="empty-d">Önce kategori, sonra ürün ekleyin.</div></div></div></div>
        <?php else: ?>

        <?php foreach($kategoriler as $kat):
            $items = $urunlerByKat[$kat['ad']] ?? [];
        ?>
        <div class="panel mb16">
            <div class="kat-hd open" onclick="toggleKat(this)">
                <span class="kat-name"><?= htmlspecialchars($kat['ad']) ?></span>
                <span class="kat-cnt"><?= count($items) ?> ürün</span>
                <button class="btn btn-out btn-sm" onclick="event.stopPropagation();openKatEdit(<?= $kat['id'] ?>,'<?= htmlspecialchars(addslashes($kat['ad'])) ?>',<?= $kat['sira'] ?>)" style="margin-right:6px">✎</button>
                <a href="admin.php?sil_kat=<?= $kat['id'] ?>" class="btn btn-del btn-sm" onclick="event.stopPropagation();return confirm('Kategoriyi sil? Ürünler kategorisiz kalır.')">✕</a>
                <span class="kat-arr" style="margin-left:8px">▼</span>
            </div>
            <div class="kat-body">
                <?php if(empty($items)): ?>
                <div class="empty" style="padding:20px 0"><div class="empty-d">Bu kategoride ürün yok. Ürün eklerken bu kategoriyi seçin.</div></div>
                <?php else: ?>
                <table class="dt">
                    <thead><tr><th style="width:32%">Ürün</th><th style="width:30%">Açıklama</th><th style="width:14%">Fiyat</th><th style="width:24%">İşlemler</th></tr></thead>
                    <tbody>
                    <?php foreach($items as $u): ?>
                    <tr>
                        <td class="fw6"><?= htmlspecialchars($u['ad']) ?></td>
                        <td class="tc-3"><?= htmlspecialchars($u['aciklama']?:'—') ?></td>
                        <td class="tc-g fw6"><?= number_format($u['fiyat'],2) ?> ₺</td>
                        <td>
                            <div class="fxc gap6">
                                <button class="btn btn-out btn-sm" onclick="openUrunEdit(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['ad'])) ?>',<?= (int)($u['kategori_id']??0) ?>,'<?= $u['fiyat'] ?>','<?= htmlspecialchars(addslashes($u['aciklama'])) ?>')">✎ Düzenle</button>
                                <a href="admin.php?sil_urun=<?= $u['id'] ?>" class="btn btn-del btn-sm" onclick="return confirm('\"<?= htmlspecialchars(addslashes($u['ad'])) ?>\" silinsin mi?')">✕</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(isset($urunlerByKat['Kategorisiz']) || isset($urunlerByKat[''])): ?>
        <?php $uncatItems = array_merge($urunlerByKat['Kategorisiz']??[],$urunlerByKat['']??[]); ?>
        <?php if(!empty($uncatItems)): ?>
        <div class="panel mb16">
            <div class="kat-hd open" onclick="toggleKat(this)">
                <span class="kat-name tc-3">Kategorisiz</span>
                <span class="kat-cnt"><?= count($uncatItems) ?> ürün</span>
                <span class="kat-arr" style="margin-left:8px">▼</span>
            </div>
            <div class="kat-body">
                <table class="dt">
                    <thead><tr><th>Ürün</th><th>Açıklama</th><th>Fiyat</th><th>İşlemler</th></tr></thead>
                    <tbody>
                    <?php foreach($uncatItems as $u): ?>
                    <tr>
                        <td class="fw6"><?= htmlspecialchars($u['ad']) ?></td>
                        <td class="tc-3"><?= htmlspecialchars($u['aciklama']?:'—') ?></td>
                        <td class="tc-g fw6"><?= number_format($u['fiyat'],2) ?> ₺</td>
                        <td>
                            <div class="fxc gap6">
                                <button class="btn btn-out btn-sm" onclick="openUrunEdit(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['ad'])) ?>',0,'<?= $u['fiyat'] ?>','<?= htmlspecialchars(addslashes($u['aciklama'])) ?>')">✎ Düzenle</button>
                                <a href="admin.php?sil_urun=<?= $u['id'] ?>" class="btn btn-del btn-sm" onclick="return confirm('Silinsin mi?')">✕</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <div class="tab-pane <?= $aktifTab==='ayarlar'?'active':'' ?>" id="tab-ayarlar">
        <div class="ph"><div class="ph-title">Kafe <span>Ayarları</span></div></div>
        <div class="g2">
            <div class="panel">
                <div class="ph2"><h3>İletişim Bilgileri</h3></div>
                <div class="pb">
                    <form method="POST">
                        <input type="hidden" name="hakkimizda" value="<?= htmlspecialchars($ayarlar['hakkimizda']??'') ?>">
                        <div class="fg"><label class="fl">Mekan İsmi</label><input type="text" name="kafe_adi" class="fi" value="<?= htmlspecialchars($ayarlar['kafe_adi']??'') ?>"></div>
                        <div class="fg"><label class="fl">Telefon</label><input type="text" name="telefon" class="fi" value="<?= htmlspecialchars($ayarlar['telefon']??'') ?>"></div>
                        <div class="fg"><label class="fl">E-Posta</label><input type="text" name="eposta" class="fi" value="<?= htmlspecialchars($ayarlar['eposta']??'') ?>"></div>
                        <div class="fg"><label class="fl">Instagram</label><input type="text" name="instagram" class="fi" value="<?= htmlspecialchars($ayarlar['instagram']??'') ?>"></div>
                        <div class="fg"><label class="fl">Adres</label><input type="text" name="adres" class="fi" value="<?= htmlspecialchars($ayarlar['adres']??'') ?>"></div>
                        <div class="fg"><label class="fl">Personel Davet Kodu</label><input type="text" name="personel_referans" class="fi" value="<?= htmlspecialchars($ayarlar['personel_referans']??'') ?>"></div>
                        <button type="submit" name="ayar_guncelle" class="btn btn-gold btn-full">Güncelle</button>
                    </form>
                </div>
            </div>
            <div class="panel">
                <div class="ph2"><h3>Mekan Hikayesi</h3></div>
                <div class="pb">
                    <form method="POST">
                        <input type="hidden" name="kafe_adi" value="<?= htmlspecialchars($ayarlar['kafe_adi']??'') ?>">
                        <input type="hidden" name="telefon" value="<?= htmlspecialchars($ayarlar['telefon']??'') ?>">
                        <input type="hidden" name="eposta" value="<?= htmlspecialchars($ayarlar['eposta']??'') ?>">
                        <input type="hidden" name="instagram" value="<?= htmlspecialchars($ayarlar['instagram']??'') ?>">
                        <input type="hidden" name="adres" value="<?= htmlspecialchars($ayarlar['adres']??'') ?>">
                        <input type="hidden" name="personel_referans" value="<?= htmlspecialchars($ayarlar['personel_referans']??'') ?>">
                        <div class="fg">
                            <label class="fl">Ana Sayfa Metni</label>
                            <textarea name="hakkimizda" class="ft" rows="13"><?= htmlspecialchars($ayarlar['hakkimizda']??'') ?></textarea>
                        </div>
                        <p class="tc-3 txt-s mb16">Bu metin müşteri ana sayfasında görünür.</p>
                        <button type="submit" name="ayar_guncelle" class="btn btn-gold btn-full">Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane <?= $aktifTab==='kullanicilar'?'active':'' ?>" id="tab-kullanicilar">
        <div class="ph"><div class="ph-title">Ekip <span>& Yetki</span></div></div>
        <div class="g2">
            <div class="panel">
                <div class="ph2"><h3>Yeni Personel</h3></div>
                <div class="pb">
                    <form method="POST" autocomplete="off">
                        <div class="fg"><label class="fl">Kullanıcı Adı</label><input type="text" name="kadi" class="fi" required autocomplete="new-password"></div>
                        <div class="fg"><label class="fl">Parola</label><input type="password" name="sifre" class="fi" required autocomplete="new-password"></div>
                        <div class="fg">
                            <label class="fl">Rol</label>
                            <select name="rol" class="fs">
                                <option value="garson">Garson</option>
                                <option value="barista">Barista</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="kullanici_ekle" class="btn btn-gold btn-full">Kaydet</button>
                    </form>
                </div>
            </div>
            <div class="panel">
                <div class="ph2"><h3>Kayıtlı Personel</h3><span class="badge bg-g"><?= $toplamPersonel ?></span></div>
                <div class="pb-s">
                    <?php if(empty($kullanicilar)): ?>
                    <div class="empty" style="padding:20px"><div class="empty-ic">◇</div><div class="empty-t">Personel yok</div></div>
                    <?php else: ?>
                    <table class="dt">
                        <thead><tr><th>Ad</th><th>Rol</th><th>İşlemler</th></tr></thead>
                        <tbody>
                        <?php foreach($kullanicilar as $k):
                            $rb=['admin'=>'bg-r','garson'=>'bg-g','barista'=>'bg-gr'];
                            $rl=['admin'=>'Admin','garson'=>'Garson','barista'=>'Barista'];
                        ?>
                        <tr>
                            <td class="fw6">
                                <?= htmlspecialchars($k['kullanici_adi']) ?>
                                <?php if($k['kullanici_adi']===$_SESSION['kullanici']): ?><span class="badge bg-gr" style="margin-left:6px;font-size:.58em">Siz</span><?php endif; ?>
                            </td>
                            <td><span class="badge <?= $rb[$k['rol']]??'bg-n' ?>"><?= $rl[$k['rol']]??$k['rol'] ?></span></td>
                            <td>
                                <div class="fxc gap6">
                                    <button class="btn btn-out btn-sm" onclick="openPassModal(<?= $k['id'] ?>,'<?= htmlspecialchars(addslashes($k['kullanici_adi'])) ?>')">🔑</button>
                                    <?php if($k['kullanici_adi']!==$_SESSION['kullanici']): ?>
                                    <a href="admin.php?sil_user=<?= $k['id'] ?>" class="btn btn-del btn-sm" onclick="return confirm('\"<?= htmlspecialchars(addslashes($k['kullanici_adi'])) ?>\" silinsin mi?')">✕</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    </div>
</div>

<div class="modal-ov" id="katEkleModal">
    <div class="modal-box">
        <div class="mh"><h4>+ Yeni Kategori</h4><div class="mx" onclick="closeModal('katEkleModal')">✕</div></div>
        <form method="POST">
            <div class="mb">
                <div class="fg"><label class="fl">Kategori Adı</label><input type="text" name="kategori_adi" class="fi" required placeholder="Örn: Yemekler, Tatlılar, Soğuk İçecekler..."></div>
                <p class="tc-3 txt-s">Bu kategori menüde bir bölüm olarak görünecek.</p>
            </div>
            <div class="mf">
                <button type="button" class="btn btn-out" onclick="closeModal('katEkleModal')">Vazgeç</button>
                <button type="submit" name="kategori_ekle" class="btn btn-gold">Oluştur</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-ov" id="katEditModal">
    <div class="modal-box">
        <div class="mh"><h4>✎ Kategori Düzenle</h4><div class="mx" onclick="closeModal('katEditModal')">✕</div></div>
        <form method="POST">
            <input type="hidden" name="kat_id" id="kat_id">
            <div class="mb">
                <div class="fg"><label class="fl">Kategori Adı</label><input type="text" name="kat_ad" id="kat_ad" class="fi" required></div>
                <div class="fg"><label class="fl">Sıra</label><input type="number" name="kat_sira" id="kat_sira" class="fi" min="0"></div>
            </div>
            <div class="mf">
                <button type="button" class="btn btn-out" onclick="closeModal('katEditModal')">Vazgeç</button>
                <button type="submit" name="kategori_duzenle" class="btn btn-gold">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-ov" id="urunEkleModal">
    <div class="modal-box">
        <div class="mh"><h4>+ Yeni Ürün</h4><div class="mx" onclick="closeModal('urunEkleModal')">✕</div></div>
        <form method="POST">
            <div class="mb">
                <div class="g2">
                    <div class="fg"><label class="fl">Ürün Adı *</label><input type="text" name="ad" class="fi" required placeholder="Örn: Filtre Kahve"></div>
                    <div class="fg">
                        <label class="fl">Kategori *</label>
                        <select name="kategori_id" class="fs" required>
                            <option value="">— Seçin —</option>
                            <?php foreach($kategoriler as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label class="fl">Fiyat (₺) *</label><input type="number" step="0.01" min="0" name="fiyat" class="fi" required placeholder="0.00"></div>
                    <div class="fg"><label class="fl">Açıklama</label><input type="text" name="aciklama" class="fi" placeholder="Kısa içerik bilgisi"></div>
                </div>
            </div>
            <div class="mf">
                <button type="button" class="btn btn-out" onclick="closeModal('urunEkleModal')">Vazgeç</button>
                <button type="submit" name="urun_ekle" class="btn btn-gold">Menüye Ekle</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-ov" id="urunEditModal">
    <div class="modal-box">
        <div class="mh"><h4>✎ Ürün Düzenle</h4><div class="mx" onclick="closeModal('urunEditModal')">✕</div></div>
        <form method="POST">
            <input type="hidden" name="urun_id" id="edit_uid">
            <div class="mb">
                <div class="g2">
                    <div class="fg"><label class="fl">Ürün Adı *</label><input type="text" name="ad" id="edit_ad" class="fi" required></div>
                    <div class="fg">
                        <label class="fl">Kategori *</label>
                        <select name="kategori_id" id="edit_katid" class="fs">
                            <option value="">— Kategorisiz —</option>
                            <?php foreach($kategoriler as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label class="fl">Fiyat (₺) *</label><input type="number" step="0.01" min="0" name="fiyat" id="edit_fiyat" class="fi" required></div>
                    <div class="fg"><label class="fl">Açıklama</label><input type="text" name="aciklama" id="edit_aciklama" class="fi"></div>
                </div>
            </div>
            <div class="mf">
                <button type="button" class="btn btn-out" onclick="closeModal('urunEditModal')">Vazgeç</button>
                <button type="submit" name="urun_duzenle" class="btn btn-gold">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-ov" id="passModal">
    <div class="modal-box">
        <div class="mh"><h4>🔑 Şifre Güncelle</h4><div class="mx" onclick="closeModal('passModal')">✕</div></div>
        <form method="POST">
            <input type="hidden" name="user_id" id="pass_uid">
            <div class="mb">
                <div class="fg"><label class="fl">Personel</label><input type="text" id="pass_uname" class="fi" disabled></div>
                <div class="fg"><label class="fl">Yeni Parola</label><input type="password" name="yeni_sifre" class="fi" required autocomplete="new-password"></div>
            </div>
            <div class="mf">
                <button type="button" class="btn btn-out" onclick="closeModal('passModal')">Vazgeç</button>
                <button type="submit" name="sifre_guncelle" class="btn btn-gold">Güncelle</button>
            </div>
        </form>
    </div>
</div>

<?php if($mesaj): ?>
<div class="toast-w" id="toastWrap">
    <div class="toast <?= $mesajTip ?>">
        <?= $mesajTip==='ok'?'✓':($mesajTip==='err'?'✕':'⚠') ?>
        <?= htmlspecialchars($mesaj) ?>
    </div>
</div>
<?php endif; ?>

<script>
const TITLES = {dashboard:'Özet',urunler:'Menü & Ürünler',ayarlar:'Kafe Ayarları',kullanicilar:'Ekip & Yetki'};

function sw(id) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nb').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+id).classList.add('active');
    const btn = document.querySelector(`button[onclick="sw('${id}')"]`);
    if (btn) btn.classList.add('active');
    document.getElementById('tb-title').textContent = TITLES[id] || id;
    history.replaceState(null,'','?tab='+id);
}

function toggleTheme() {
    const root = document.documentElement;
    const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    root.setAttribute('data-theme', next);
    document.getElementById('themeBtn').textContent = next === 'light' ? '🌙' : '☀️';
    localStorage.setItem('lm_theme', next);
}

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function toggleKat(hd) {
    hd.classList.toggle('open');
    hd.nextElementSibling.classList.toggle('hidden');
}

function openKatEdit(id, ad, sira) {
    document.getElementById('kat_id').value   = id;
    document.getElementById('kat_ad').value   = ad;
    document.getElementById('kat_sira').value = sira;
    openModal('katEditModal');
}

function openUrunEdit(id, ad, katId, fiyat, aciklama) {
    document.getElementById('edit_uid').value      = id;
    document.getElementById('edit_ad').value       = ad;
    document.getElementById('edit_katid').value    = katId;
    document.getElementById('edit_fiyat').value    = fiyat;
    document.getElementById('edit_aciklama').value = aciklama;
    openModal('urunEditModal');
}

function openPassModal(uid, uname) {
    document.getElementById('pass_uid').value   = uid;
    document.getElementById('pass_uname').value = uname;
    openModal('passModal');
}

document.querySelectorAll('.modal-ov').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-ov.open').forEach(m => m.classList.remove('open'));
});

window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('lm_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    document.getElementById('themeBtn').textContent = saved === 'light' ? '🌙' : '☀️';
    const urlTab = new URLSearchParams(location.search).get('tab') || 'dashboard';
    document.getElementById('tb-title').textContent = TITLES[urlTab] || 'Özet';
    const tw = document.getElementById('toastWrap');
    if (tw) setTimeout(() => { tw.style.transition='opacity .35s'; tw.style.opacity='0'; setTimeout(()=>tw.remove(),350); }, 3500);
});
</script>
</body>
</html>