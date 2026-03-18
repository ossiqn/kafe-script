<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['kullanici']) || $_SESSION['rol'] !== 'garson') { header('Location: login.php'); exit; }

$ayarlar = $db->query("SELECT kafe_adi FROM ayarlar LIMIT 1")->fetch();
$urunler = $db->query("SELECT u.*, k.hedef, k.sira as kat_sira FROM urunler u LEFT JOIN kategoriler k ON u.kategori_id=k.id WHERE u.aktif=1 ORDER BY k.sira ASC, u.ad ASC")->fetchAll();
$kategoriler = [];
foreach ($urunler as $u) {
    $kat = $u['kategori'] ?: 'Diğer';
    $kategoriler[$kat][] = $u;
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Garson | <?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root[data-theme="light"]{--bg:#f4f1eb;--bg2:#ede9e0;--card:#fff;--card2:#faf8f4;--text:#2a2318;--text2:#6b5e4a;--text3:#a89880;--border:#e8e0d0;--border2:#d4c8b4;--gold:#9a7d3a;--gold-bg:rgba(154,125,58,0.09);--gold-glow:rgba(154,125,58,0.2);--red:#c0392b;--red-bg:rgba(192,57,43,0.08);--green:#2d7a4f;--green-bg:rgba(45,122,79,0.08);--sh:0 2px 16px rgba(42,35,24,0.07);--sh-lg:0 10px 36px rgba(42,35,24,0.11);--inp:#fff;--inp-b:#d4c8b4;--r:13px;--rs:8px;--t:0.2s ease;}
:root[data-theme="dark"]{--bg:#080705;--bg2:#0d0b08;--card:#0f0d09;--card2:#131008;--text:#ede8df;--text2:#a09070;--text3:#5a4e3a;--border:#1e1a12;--border2:#2a2418;--gold:#D4AF37;--gold-bg:rgba(212,175,55,0.08);--gold-glow:rgba(212,175,55,0.2);--red:#e05555;--red-bg:rgba(224,85,85,0.08);--green:#4caf82;--green-bg:rgba(76,175,130,0.08);--sh:0 2px 16px rgba(0,0,0,0.35);--sh-lg:0 10px 36px rgba(0,0,0,0.55);--inp:rgba(255,255,255,0.02);--inp-b:#2a2418;--r:13px;--rs:8px;--t:0.2s ease;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);height:100vh;display:flex;flex-direction:column;overflow:hidden;font-size:14px;transition:background var(--t),color var(--t);}
::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px;}

.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 20px;height:54px;display:flex;align-items:center;gap:12px;flex-shrink:0;}
.tb-logo{font-family:'Playfair Display',serif;font-size:1.1em;font-weight:700;color:var(--gold);letter-spacing:2px;}
.tb-role{font-size:0.68em;color:var(--text3);background:var(--gold-bg);border:1px solid var(--border2);padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:1px;}
.tb-user{font-size:0.75em;color:var(--text3);margin-left:auto;}
.tb-user span{color:var(--gold);font-weight:600;}
.tb-btn{width:34px;height:34px;background:var(--bg2);border:1px solid var(--border);border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.95em;color:var(--text2);transition:all var(--t);}
.tb-btn:hover{border-color:var(--gold);color:var(--gold);}
.tb-link{font-size:0.72em;color:var(--text3);text-decoration:none;padding:5px 10px;border:1px solid var(--border);border-radius:6px;transition:all var(--t);}
.tb-link:hover{border-color:var(--red);color:var(--red);}

.layout{flex:1;display:flex;overflow:hidden;}

.menu-panel{width:340px;flex-shrink:0;background:var(--card);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;}
.mp-head{padding:14px 16px;border-bottom:1px solid var(--border);}
.mp-head-title{font-family:'Playfair Display',serif;font-size:1em;font-weight:600;color:var(--gold);margin-bottom:10px;}
.masa-row{display:flex;gap:8px;align-items:center;}
.masa-label{font-size:0.68em;font-weight:600;color:var(--text3);letter-spacing:1px;text-transform:uppercase;white-space:nowrap;}
.masa-input{flex:1;padding:8px 12px;background:var(--inp);border:1px solid var(--inp-b);border-radius:var(--rs);color:var(--text);font-family:'Poppins';font-size:0.88em;outline:none;transition:border-color var(--t);}
.masa-input:focus{border-color:var(--gold);}
.kat-tabs{display:flex;gap:4px;overflow-x:auto;padding:10px 16px;border-bottom:1px solid var(--border);flex-shrink:0;}
.kat-tabs::-webkit-scrollbar{height:3px;}
.kat-tab{padding:5px 12px;border-radius:20px;font-size:0.72em;font-weight:600;cursor:pointer;border:1px solid var(--border2);background:transparent;color:var(--text2);white-space:nowrap;transition:all var(--t);}
.kat-tab:hover{border-color:var(--gold);color:var(--gold);}
.kat-tab.active{background:var(--gold-bg);border-color:var(--gold);color:var(--gold);}
.urun-list{flex:1;overflow-y:auto;padding:8px;}
.urun-item{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:var(--rs);cursor:pointer;transition:background var(--t);border:1px solid transparent;margin-bottom:3px;}
.urun-item:hover{background:var(--gold-bg);border-color:var(--border2);}
.urun-item:active{transform:scale(0.98);}
.ui-name{flex:1;font-size:0.86em;font-weight:500;line-height:1.3;}
.ui-desc{font-size:0.72em;color:var(--text3);margin-top:2px;}
.ui-price{font-family:'Playfair Display',serif;font-size:0.95em;color:var(--gold);font-weight:600;white-space:nowrap;}
.ui-add{width:28px;height:28px;background:var(--gold-bg);border:1px solid var(--border2);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:1.2em;color:var(--gold);flex-shrink:0;transition:all var(--t);}
.urun-item:hover .ui-add{background:var(--gold);color:#fff;border-color:var(--gold);}

.siparis-panel{flex:1;display:flex;flex-direction:column;overflow:hidden;}
.sp-head{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--card);display:flex;align-items:center;gap:10px;}
.sp-head-title{font-family:'Playfair Display',serif;font-size:1em;font-weight:600;color:var(--text);flex:1;}
.sepet-bos{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--text3);font-size:0.82em;opacity:.6;}
.sepet-bos-ic{font-size:2.5em;opacity:.3;}
.sepet-list{flex:1;overflow-y:auto;padding:12px 20px;}
.sepet-item{display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--card2);border:1px solid var(--border);border-radius:var(--rs);margin-bottom:8px;transition:all var(--t);}
.si-name{flex:1;font-size:0.85em;font-weight:500;}
.si-desc{font-size:0.72em;color:var(--text3);}
.si-qty{display:flex;align-items:center;gap:6px;}
.si-qty-btn{width:26px;height:26px;background:var(--bg2);border:1px solid var(--border2);border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.9em;color:var(--text2);transition:all var(--t);font-family:'Poppins';}
.si-qty-btn:hover{background:var(--gold-bg);color:var(--gold);border-color:var(--gold);}
.si-qty-val{font-size:0.85em;font-weight:600;min-width:18px;text-align:center;}
.si-price{font-family:'Playfair Display',serif;font-size:0.9em;color:var(--gold);white-space:nowrap;font-weight:600;}
.si-del{width:26px;height:26px;background:var(--red-bg);border:1px solid transparent;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.8em;color:var(--red);transition:all var(--t);}
.si-del:hover{background:var(--red);color:#fff;}
.sp-foot{padding:14px 20px;border-top:1px solid var(--border);background:var(--card);}
.not-row{margin-bottom:12px;}
.not-label{font-size:0.65em;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:6px;display:block;}
.not-input{width:100%;padding:9px 12px;background:var(--inp);border:1px solid var(--inp-b);border-radius:var(--rs);color:var(--text);font-family:'Poppins';font-size:0.84em;outline:none;resize:none;transition:border-color var(--t);}
.not-input:focus{border-color:var(--gold);}
.toplam-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.toplam-lbl{font-size:0.72em;color:var(--text3);text-transform:uppercase;letter-spacing:1px;}
.toplam-val{font-family:'Playfair Display',serif;font-size:1.35em;color:var(--gold);font-weight:700;}
.gonder-btn{width:100%;padding:13px;background:var(--gold);color:#fff;border:none;border-radius:var(--rs);font-family:'Poppins';font-size:0.85em;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:all var(--t);}
.gonder-btn:hover:not(:disabled){filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 6px 20px var(--gold-glow);}
.gonder-btn:disabled{opacity:.5;cursor:not-allowed;}

.toast-w{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast{background:var(--card);border:1px solid var(--border2);border-radius:var(--rs);padding:11px 16px;font-size:0.8em;font-weight:500;display:flex;align-items:center;gap:9px;box-shadow:var(--sh-lg);animation:tin .25s ease;min-width:220px;}
.toast.ok{border-left:3px solid var(--green);}
.toast.err{border-left:3px solid var(--red);}
@keyframes tin{from{opacity:0;transform:translateX(16px)}to{opacity:1;transform:none}}

@media(max-width:700px){
    .layout{flex-direction:column;}
    .menu-panel{width:100%;height:55vh;border-right:none;border-bottom:1px solid var(--border);}
    .siparis-panel{height:45vh;}
}
</style>
</head>
<body>
<div class="topbar">
    <span class="tb-logo">LUMIÈRE</span>
    <span class="tb-role">Garson</span>
    <div class="tb-user"><span><?= htmlspecialchars($_SESSION['kullanici']) ?></span></div>
    <button class="tb-btn" id="themeBtn" onclick="toggleTheme()">🌙</button>
    <a href="logout.php" class="tb-link">Çıkış</a>
</div>

<div class="layout">
    <div class="menu-panel">
        <div class="mp-head">
            <div class="mp-head-title">Sipariş Oluştur</div>
            <div class="masa-row">
                <span class="masa-label">Masa</span>
                <input type="text" class="masa-input" id="masaNo" placeholder="Masa numarası girin">
            </div>
        </div>
        <div class="kat-tabs" id="katTabs">
            <button class="kat-tab active" onclick="filterKat('hepsi', this)">Tümü</button>
            <?php foreach(array_keys($kategoriler) as $kat): ?>
            <button class="kat-tab" onclick="filterKat('<?= htmlspecialchars(addslashes($kat)) ?>', this)"><?= htmlspecialchars($kat) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="urun-list" id="urunList">
            <?php foreach($urunler as $u): ?>
            <div class="urun-item" data-kat="<?= htmlspecialchars($u['kategori']) ?>" onclick="sepeteEkle(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['ad'])) ?>',<?= $u['fiyat'] ?>,'<?= htmlspecialchars(addslashes($u['aciklama'] ?? '')) ?>')">
                <div style="flex:1">
                    <div class="ui-name"><?= htmlspecialchars($u['ad']) ?></div>
                    <?php if($u['aciklama']): ?><div class="ui-desc"><?= htmlspecialchars($u['aciklama']) ?></div><?php endif; ?>
                </div>
                <div class="ui-price"><?= number_format($u['fiyat'],2) ?> ₺</div>
                <div class="ui-add">+</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="siparis-panel">
        <div class="sp-head">
            <div class="sp-head-title">Sepet</div>
            <span id="sepetSayac" style="font-size:0.75em;color:var(--text3)">0 ürün</span>
            <button class="tb-btn" onclick="sepetiTemizle()" title="Sepeti Temizle" style="margin-left:auto">🗑</button>
        </div>

        <div class="sepet-bos" id="sepetBos">
            <div class="sepet-bos-ic">☕</div>
            <div>Sepet boş</div>
            <div style="font-size:.75em;color:var(--text3)">Soldan ürün seçin</div>
        </div>

        <div class="sepet-list" id="sepetList" style="display:none"></div>

        <div class="sp-foot">
            <div class="not-row">
                <label class="not-label">Sipariş Notu</label>
                <textarea class="not-input" id="sipNotMetni" rows="2" placeholder="Özel istek, allerji vb..."></textarea>
            </div>
            <div class="toplam-row">
                <span class="toplam-lbl">Toplam</span>
                <span class="toplam-val" id="toplamVal">0,00 ₺</span>
            </div>
            <button class="gonder-btn" id="gonderBtn" onclick="siparisGonder()" disabled>
                Siparişi Gönder
            </button>
        </div>
    </div>
</div>

<div class="toast-w" id="toastW"></div>

<script>
let sepet = [];

function filterKat(kat, btn) {
    document.querySelectorAll('.kat-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.urun-item').forEach(el => {
        el.style.display = (kat === 'hepsi' || el.dataset.kat === kat) ? 'flex' : 'none';
    });
}

function sepeteEkle(id, ad, fiyat, aciklama) {
    const mevcut = sepet.find(i => i.id === id);
    if (mevcut) {
        mevcut.adet++;
    } else {
        sepet.push({ id, ad, fiyat: parseFloat(fiyat), aciklama, adet: 1 });
    }
    sepetGuncelle();
    toast(`${ad} eklendi`, 'ok');
}

function sepetGuncelle() {
    const list = document.getElementById('sepetList');
    const bos  = document.getElementById('sepetBos');
    const sayac = document.getElementById('sepetSayac');
    const btn   = document.getElementById('gonderBtn');

    if (sepet.length === 0) {
        bos.style.display  = 'flex';
        list.style.display = 'none';
        btn.disabled = true;
        sayac.textContent = '0 ürün';
        document.getElementById('toplamVal').textContent = '0,00 ₺';
        return;
    }

    bos.style.display  = 'none';
    list.style.display = 'block';
    btn.disabled = false;

    let toplam = 0;
    let html = '';
    sepet.forEach((item, idx) => {
        const ara = item.fiyat * item.adet;
        toplam += ara;
        html += `
        <div class="sepet-item">
            <div style="flex:1">
                <div class="si-name">${item.ad}</div>
                ${item.aciklama ? `<div class="si-desc">${item.aciklama}</div>` : ''}
            </div>
            <div class="si-qty">
                <button class="si-qty-btn" onclick="adetDegistir(${idx},-1)">−</button>
                <span class="si-qty-val">${item.adet}</span>
                <button class="si-qty-btn" onclick="adetDegistir(${idx},1)">+</button>
            </div>
            <div class="si-price">${ara.toFixed(2)} ₺</div>
            <button class="si-del" onclick="sepetSil(${idx})">✕</button>
        </div>`;
    });

    list.innerHTML = html;
    const toplamSayac = sepet.reduce((s, i) => s + i.adet, 0);
    sayac.textContent = toplamSayac + ' ürün';
    document.getElementById('toplamVal').textContent = toplam.toFixed(2).replace('.', ',') + ' ₺';
}

function adetDegistir(idx, delta) {
    sepet[idx].adet += delta;
    if (sepet[idx].adet <= 0) sepet.splice(idx, 1);
    sepetGuncelle();
}

function sepetSil(idx) {
    sepet.splice(idx, 1);
    sepetGuncelle();
}

function sepetiTemizle() {
    sepet = [];
    sepetGuncelle();
}

async function siparisGonder() {
    const masa = document.getElementById('masaNo').value.trim();
    const not  = document.getElementById('sipNotMetni').value.trim();

    if (!masa) { toast('Masa numarası girin!', 'err'); document.getElementById('masaNo').focus(); return; }
    if (sepet.length === 0) { toast('Sepet boş!', 'err'); return; }

    const btn = document.getElementById('gonderBtn');
    btn.disabled = true;
    btn.textContent = 'Gönderiliyor...';

    const fd = new FormData();
    fd.append('islem', 'siparis_gonder');
    fd.append('masa', masa);
    fd.append('not', not);
    fd.append('sepet', JSON.stringify(sepet));

    try {
        const r = await fetch('ajax.php', { method: 'POST', body: fd });
        const d = await r.json();
        if (d.durum === 'ok') {
            toast('✓ Sipariş gönderildi!', 'ok');
            sepetiTemizle();
            document.getElementById('sipNotMetni').value = '';
        } else {
            toast(d.mesaj || 'Hata!', 'err');
        }
    } catch(e) {
        toast('Bağlantı hatası!', 'err');
    }

    btn.disabled = false;
    btn.textContent = 'Siparişi Gönder';
}

function toast(msg, tip) {
    const w = document.getElementById('toastW');
    const t = document.createElement('div');
    t.className = `toast ${tip}`;
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2800);
}

function toggleTheme() {
    const root = document.documentElement;
    const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    root.setAttribute('data-theme', next);
    document.getElementById('themeBtn').textContent = next === 'light' ? '🌙' : '☀️';
    localStorage.setItem('lm_theme', next);
}

window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('lm_theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    document.getElementById('themeBtn').textContent = saved === 'light' ? '🌙' : '☀️';
});
</script>
</body>
</html>
