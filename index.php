<?php
require_once 'config.php';

$ayarlar = $db->query("SELECT * FROM ayarlar LIMIT 1")->fetch();
$urunler = $db->query("SELECT u.*, k.sira as kat_sira FROM urunler u LEFT JOIN kategoriler k ON u.kategori_id=k.id WHERE u.aktif=1 ORDER BY k.sira ASC, u.ad ASC")->fetchAll();

$kategoriler = [];
foreach ($urunler as $u) {
    $kat = $u['kategori'] ?: 'Diğer';
    $kategoriler[$kat][] = $u;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?> | Menü</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@200;300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #D4AF37;
    --gold-dim: rgba(212,175,55,0.25);
    --dark-glass: rgba(5,5,5,0.88);
    --light-glass: rgba(255,255,255,0.03);
}

*,*::before,*::after { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
body { margin:0; padding:0; font-family:'Poppins',sans-serif; color:#f0ece0; background:#050503; scroll-behavior:smooth; overflow-x:hidden; }

.parallax-bg { position:fixed; top:0; left:0; width:100%; height:100vh; background:url('https://images.unsplash.com/photo-1497935586351-b67a49e012bf?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat; background-attachment:fixed; z-index:-2; }
.overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:linear-gradient(to bottom,rgba(0,0,0,0.88) 0%,rgba(5,5,3,0.96) 100%); z-index:-1; }

.navbar { display:flex; justify-content:space-between; align-items:center; padding:26px 8vw; background:transparent; position:fixed; width:100%; top:0; z-index:100; transition:all 0.5s ease; }
.navbar.scrolled { background:var(--dark-glass); border-bottom:1px solid rgba(212,175,55,0.1); backdrop-filter:blur(16px); padding:16px 8vw; box-shadow:0 10px 30px rgba(0,0,0,0.5); }
.logo { font-family:'Playfair Display',serif; font-size:clamp(1.2em,4vw,2em); font-weight:700; color:var(--gold); letter-spacing:2px; text-transform:uppercase; text-decoration:none; line-height:1.2; }
.nav-links { display:flex; gap:40px; }
.nav-links a { color:#ccc; text-decoration:none; font-size:0.82em; font-weight:400; text-transform:uppercase; letter-spacing:2px; transition:0.3s; position:relative; }
.nav-links a::after { content:''; position:absolute; width:0; height:1px; bottom:-5px; left:50%; transform:translateX(-50%); background:var(--gold); transition:width 0.3s; }
.nav-links a:hover { color:var(--gold); }
.nav-links a:hover::after { width:100%; }
.nav-btn { background:transparent; color:var(--gold); border:1px solid var(--gold-dim); padding:10px 24px; font-size:0.8em; font-family:'Poppins',sans-serif; cursor:pointer; transition:0.35s; text-transform:uppercase; letter-spacing:2px; text-decoration:none; border-radius:4px; }
.nav-btn:hover { background:var(--gold); color:#080600; box-shadow:0 0 22px rgba(212,175,55,0.28); }

.hero { text-align:center; height:100vh; min-height:500px; display:flex; flex-direction:column; justify-content:center; align-items:center; position:relative; padding:0 6vw; }
.hero h1 { font-family:'Playfair Display',serif; font-size:clamp(2.5em,10vw,5.8em); font-weight:400; margin:0 0 18px; color:#fff; letter-spacing:2px; line-height:1.1; }
.hero p { font-size:clamp(0.9em,2.5vw,1.15em); color:#999; max-width:580px; line-height:1.9; font-weight:300; letter-spacing:0.8px; }
.hero-line { width:40px; height:1px; background:var(--gold); margin:22px auto; opacity:0.5; }

.scroll-indicator { position:absolute; bottom:38px; left:50%; transform:translateX(-50%); display:flex; flex-direction:column; align-items:center; opacity:0.7; }
.mouse { width:24px; height:38px; border:2px solid var(--gold-dim); border-radius:12px; position:relative; display:block; }
.wheel { width:4px; height:7px; background:var(--gold); border-radius:2px; position:absolute; top:6px; left:50%; transform:translateX(-50%); animation:scrollMouse 1.5s infinite; }
.touch-swipe { width:28px; height:42px; border:2px solid rgba(212,175,55,0.3); border-radius:18px; position:relative; display:none; justify-content:center; }
.touch-dot { width:13px; height:13px; background:var(--gold); border-radius:50%; position:absolute; bottom:6px; animation:swipeUp 1.8s infinite ease-in-out; box-shadow:0 0 8px rgba(212,175,55,0.5); }

@keyframes scrollMouse { 0%{opacity:1;transform:translateX(-50%) translateY(0)} 100%{opacity:0;transform:translateX(-50%) translateY(14px)} }
@keyframes swipeUp { 0%{transform:translateY(0) scale(1);opacity:.8} 50%{transform:translateY(-16px) scale(.85);opacity:1} 100%{transform:translateY(-22px) scale(.5);opacity:0} }

.section { padding:100px 5vw 60px; max-width:1200px; margin:0 auto; width:100%; }
.section-title { font-family:'Playfair Display',serif; font-size:clamp(1.8em,7vw,2.8em); font-weight:400; color:var(--gold); text-align:center; margin-bottom:50px; position:relative; display:block; width:100%; }
.section-title::after { content:''; position:absolute; bottom:-12px; left:50%; transform:translateX(-50%); width:38px; height:1px; background:var(--gold); }

.category-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:28px; }
.category-card { background:var(--light-glass); border:1px solid rgba(255,255,255,0.04); padding:44px 22px; text-align:center; cursor:pointer; transition:all 0.4s ease; position:relative; overflow:hidden; border-radius:12px; }
.category-card::before { content:''; position:absolute; inset:0; border:1px solid var(--gold); opacity:0; transition:0.4s; transform:scale(0.94); border-radius:12px; }
.category-card:hover { background:rgba(0,0,0,0.5); box-shadow:0 15px 40px rgba(0,0,0,0.7); }
.category-card:hover::before { opacity:0.45; transform:scale(1); }
.category-card h3 { font-family:'Playfair Display',serif; font-size:clamp(1.35em,5vw,1.7em); color:#fff; margin:0 0 10px; font-weight:400; transition:0.4s; }
.category-card:hover h3 { color:var(--gold); transform:translateY(-4px); }
.category-card p { color:#666; font-size:0.75em; margin:0; text-transform:uppercase; letter-spacing:3px; font-weight:300; }

.reveal { opacity:0; transform:translateY(28px); transition:all 0.75s cubic-bezier(0.5,0,0,1); }
.reveal.active { opacity:1; transform:translateY(0); }

.menu-modal-overlay { display:flex; position:fixed; inset:0; background:rgba(0,0,0,0.93); z-index:999; justify-content:center; align-items:center; backdrop-filter:blur(16px); opacity:0; visibility:hidden; transition:all 0.35s ease; padding:14px; }
.menu-modal-overlay.show { opacity:1; visibility:visible; }
.menu-modal-box { background:#070705; width:100%; max-width:780px; max-height:86vh; padding:38px 40px; border:1px solid rgba(212,175,55,0.18); position:relative; overflow-y:auto; transform:translateY(18px); opacity:0; transition:all 0.45s cubic-bezier(0.16,1,0.3,1); border-radius:16px; }
.menu-modal-overlay.show .menu-modal-box { transform:translateY(0); opacity:1; }
.close-btn { position:sticky; top:-18px; float:right; color:#666; font-size:2em; cursor:pointer; transition:0.3s; background:#070705; width:38px; height:38px; display:flex; justify-content:center; align-items:center; z-index:10; font-weight:200; border-radius:50%; line-height:0; }
.close-btn:hover { color:var(--gold); transform:rotate(90deg); }
.menu-modal-box h2 { font-family:'Playfair Display',serif; margin:0 0 32px; color:var(--gold); font-size:clamp(1.7em,5vw,2.3em); text-align:center; font-weight:400; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.04); padding-bottom:14px; }
.modal-items { display:grid; grid-template-columns:1fr; gap:14px; }
.menu-item { display:flex; justify-content:space-between; align-items:flex-end; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:14px; transition:0.3s; }
.menu-item:hover { border-bottom-color:rgba(212,175,55,0.35); }
.item-details { flex:1; padding-right:14px; }
.item-details h3 { font-family:'Playfair Display',serif; margin:0 0 5px; font-size:clamp(1.05em,4vw,1.25em); color:#f0ece0; font-weight:400; letter-spacing:0.3px; }
.item-details p { margin:0; font-size:0.82em; color:#777; line-height:1.55; font-weight:300; }
.item-price { font-family:'Playfair Display',serif; font-size:clamp(1.15em,4vw,1.35em); color:var(--gold); white-space:nowrap; font-style:italic; }

.contact-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:28px; background:var(--light-glass); padding:40px; border-radius:16px; border:1px solid rgba(255,255,255,0.03); }
.contact-info { text-align:center; }
.contact-info h3 { color:var(--gold); font-family:'Playfair Display',serif; font-size:1.4em; margin-top:0; margin-bottom:18px; font-weight:400; }
.contact-info p { color:#888; line-height:1.8; margin-bottom:14px; font-size:0.87em; font-weight:300; }
.contact-info strong { color:#e8e0d0; font-weight:400; font-family:'Playfair Display',serif; letter-spacing:1px; display:block; margin-bottom:3px; }

.footer { text-align:center; padding:28px 20px; margin-top:48px; color:#444; font-size:0.72em; letter-spacing:1px; text-transform:uppercase; border-top:1px solid rgba(255,255,255,0.02); }
.footer a { color:var(--gold); text-decoration:none; transition:0.3s; }
.footer a:hover { color:#fff; }

.menu-modal-box::-webkit-scrollbar { width:4px; }
.menu-modal-box::-webkit-scrollbar-track { background:transparent; }
.menu-modal-box::-webkit-scrollbar-thumb { background:rgba(212,175,55,0.25); border-radius:10px; }

@media(max-width:768px) {
    .navbar { padding:14px 5vw; background:var(--dark-glass); border-bottom:1px solid rgba(212,175,55,0.08); backdrop-filter:blur(12px); }
    .nav-links { display:none; }
    .nav-btn { padding:8px 14px; font-size:0.74em; }
    .mouse { display:none; }
    .touch-swipe { display:flex; }
    .section { padding:78px 5vw 38px; }
    .section-title { margin-bottom:36px; }
    .category-grid { grid-template-columns:1fr; gap:13px; }
    .category-card { padding:24px 16px; }
    .menu-modal-box { padding:24px 18px 14px; max-height:92vh; border-radius:12px; }
    .close-btn { top:-14px; right:-4px; font-size:1.9em; width:34px; height:34px; box-shadow:0 0 10px #070705; }
    .menu-item { flex-direction:column; align-items:flex-start; gap:7px; padding-bottom:14px; }
    .item-price { align-self:flex-start; }
    .contact-grid { padding:24px 14px; gap:22px; }
}
</style>
</head>
<body>

<div class="parallax-bg"></div>
<div class="overlay"></div>

<div class="navbar" id="navbar">
    <a href="#" class="logo"><?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?></a>
    <div class="nav-links">
        <a href="#hakkimizda">Hakkımızda</a>
        <a href="#menu">Menü</a>
        <a href="#iletisim">İletişim</a>
    </div>
    <a href="login.php" class="nav-btn">Personel</a>
</div>

<div class="hero" id="hakkimizda">
    <h1 class="reveal"><?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?></h1>
    <div class="hero-line"></div>
    <p class="reveal"><?= nl2br(htmlspecialchars($ayarlar['hakkimizda'] ?? '')) ?></p>
    <div class="scroll-indicator">
        <div class="mouse"><div class="wheel"></div></div>
        <div class="touch-swipe"><div class="touch-dot"></div></div>
    </div>
</div>

<div class="section" id="menu">
    <div class="section-title reveal">Menü Koleksiyonu</div>
    <div class="category-grid">
        <?php if(!empty($kategoriler)): ?>
            <?php foreach($kategoriler as $kat_adi => $urun_listesi): ?>
            <div class="category-card reveal" onclick="openModal('<?= htmlspecialchars(addslashes($kat_adi)) ?>')">
                <h3><?= htmlspecialchars($kat_adi) ?></h3>
                <p><?= count($urun_listesi) ?> Ürün</p>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;color:#444;width:100%;grid-column:1/-1;padding:40px 0;font-size:.9em;letter-spacing:1px;text-transform:uppercase">Menü hazırlanıyor.</p>
        <?php endif; ?>
    </div>
</div>

<div class="section" id="iletisim">
    <div class="section-title reveal">Bize Ulaşın</div>
    <div class="contact-grid reveal">
        <div class="contact-info">
            <h3>Lokasyon & İletişim</h3>
            <p><strong>Adres</strong><?= htmlspecialchars($ayarlar['adres'] ?? '—') ?></p>
            <p><strong>Telefon</strong><?= htmlspecialchars($ayarlar['telefon'] ?? '—') ?></p>
            <p><strong>Dijital</strong><?= htmlspecialchars($ayarlar['eposta'] ?? '—') ?><br><?= htmlspecialchars($ayarlar['instagram'] ?? '—') ?></p>
        </div>
        <div class="contact-info">
            <h3>Ziyaret Saatleri</h3>
            <p><strong>Hafta İçi</strong>08:00 – 23:00</p>
            <p><strong>Hafta Sonu</strong>09:00 – 00:00</p>
        </div>
    </div>
</div>

<div class="footer reveal">
    &copy; <?= date('Y') ?> <?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?>
</div>

<div class="menu-modal-overlay" id="menuModal" onclick="closeModal(event)">
    <div class="menu-modal-box" id="modalBox">
        <span class="close-btn" onclick="closeModal(event,true)">&times;</span>
        <h2 id="modalTitle"></h2>
        <div class="modal-items" id="modalItems"></div>
    </div>
</div>

<script>
const menuData = <?= json_encode($kategoriler, JSON_UNESCAPED_UNICODE) ?>;

window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 30);
});

function reveal() {
    document.querySelectorAll('.reveal').forEach(el => {
        if (el.getBoundingClientRect().top < window.innerHeight - 40) el.classList.add('active');
    });
}
window.addEventListener('scroll', reveal);
reveal();

function openModal(kat) {
    document.getElementById('modalTitle').innerText = kat;
    const cont = document.getElementById('modalItems');
    cont.innerHTML = '';
    (menuData[kat] || []).forEach(u => {
        const d = document.createElement('div');
        d.className = 'menu-item';
        d.innerHTML = `<div class="item-details"><h3>${u.ad}</h3><p>${u.aciklama||''}</p></div><div class="item-price">${parseFloat(u.fiyat).toFixed(2)} ₺</div>`;
        cont.appendChild(d);
    });
    document.getElementById('menuModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(e, force=false) {
    const modal = document.getElementById('menuModal');
    if (force || !document.getElementById('modalBox').contains(e.target)) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', e => { if (e.key==='Escape') closeModal({target:null},true); });
</script>

</body>
</html>