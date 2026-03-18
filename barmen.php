<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['kullanici']) || $_SESSION['rol'] !== 'barmen') { header('Location: login.php'); exit; }
$ayarlar = $db->query("SELECT kafe_adi FROM ayarlar LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Barmen | <?= htmlspecialchars($ayarlar['kafe_adi'] ?? 'Lumière') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root[data-theme="dark"]{--bg:#080705;--bg2:#0d0b08;--card:#0f0d09;--card2:#131008;--text:#ede8df;--text2:#a09070;--text3:#5a4e3a;--border:#1e1a12;--border2:#2a2418;--gold:#D4AF37;--gold-bg:rgba(212,175,55,0.08);--gold-glow:rgba(212,175,55,0.2);--red:#e05555;--red-bg:rgba(224,85,85,0.1);--green:#4caf82;--green-bg:rgba(76,175,130,0.1);--orange:#e8873a;--orange-bg:rgba(232,135,58,0.1);--purple:#a07ee8;--purple-bg:rgba(160,126,232,0.1);--sh:0 2px 16px rgba(0,0,0,0.35);--r:13px;--rs:8px;--t:0.2s ease;}
:root[data-theme="light"]{--bg:#f4f1eb;--bg2:#ede9e0;--card:#fff;--card2:#faf8f4;--text:#2a2318;--text2:#6b5e4a;--text3:#a89880;--border:#e8e0d0;--border2:#d4c8b4;--gold:#9a7d3a;--gold-bg:rgba(154,125,58,0.09);--gold-glow:rgba(154,125,58,0.2);--red:#c0392b;--red-bg:rgba(192,57,43,0.08);--green:#2d7a4f;--green-bg:rgba(45,122,79,0.08);--orange:#c07030;--orange-bg:rgba(192,112,48,0.08);--purple:#6a4eba;--purple-bg:rgba(106,78,186,0.08);--sh:0 2px 16px rgba(42,35,24,0.07);--r:13px;--rs:8px;--t:0.2s ease;}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:14px;transition:background var(--t),color var(--t);}
::-webkit-scrollbar{width:5px;}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px;}

.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 20px;height:54px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:50;}
.tb-logo{font-family:'Playfair Display',serif;font-size:1.1em;font-weight:700;color:var(--gold);letter-spacing:2px;}
.tb-role{font-size:0.68em;background:var(--purple-bg);border:1px solid var(--border2);padding:3px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:1px;color:var(--purple);}
.tb-yeni{font-size:0.75em;background:var(--red-bg);color:var(--red);border:1px solid var(--border2);padding:3px 12px;border-radius:20px;font-weight:600;}
.tb-user{font-size:0.75em;color:var(--text3);margin-left:auto;}
.tb-user span{color:var(--gold);font-weight:600;}
.tb-btn{width:34px;height:34px;background:var(--bg2);border:1px solid var(--border);border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:0.95em;color:var(--text2);transition:all var(--t);}
.tb-btn:hover{border-color:var(--gold);color:var(--gold);}
.tb-link{font-size:0.72em;color:var(--text3);text-decoration:none;padding:5px 10px;border:1px solid var(--border);border-radius:6px;transition:all var(--t);}
.tb-link:hover{border-color:var(--red);color:var(--red);}

.main{max-width:1100px;margin:0 auto;padding:24px 20px;}
.filter-row{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-btn{padding:6px 16px;border-radius:20px;font-size:0.75em;font-weight:600;cursor:pointer;border:1px solid var(--border2);background:transparent;color:var(--text2);transition:all var(--t);}
.filter-btn:hover,.filter-btn.active{background:var(--gold-bg);border-color:var(--gold);color:var(--gold);}
.filter-btn.red.active{background:var(--red-bg);border-color:var(--red);color:var(--red);}
.filter-btn.orange.active{background:var(--orange-bg);border-color:var(--orange);color:var(--orange);}
.filter-btn.green.active{background:var(--green-bg);border-color:var(--green);color:var(--green);}

.board{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
.kolon{background:var(--card2);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;}
.kolon-hd{padding:13px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.kolon-hd h3{font-family:'Playfair Display',serif;font-size:0.95em;font-weight:600;flex:1;}
.kolon-hd.red h3{color:var(--red);}
.kolon-hd.orange h3{color:var(--orange);}
.kolon-hd.green h3{color:var(--green);}
.kolon-cnt{font-size:0.68em;font-weight:700;padding:2px 8px;border-radius:20px;}
.kolon-hd.red .kolon-cnt{background:var(--red-bg);color:var(--red);}
.kolon-hd.orange .kolon-cnt{background:var(--orange-bg);color:var(--orange);}
.kolon-hd.green .kolon-cnt{background:var(--green-bg);color:var(--green);}
.kolon-body{padding:10px;min-height:120px;}

.kart{background:var(--card);border:1px solid var(--border);border-radius:var(--rs);padding:13px 14px;margin-bottom:8px;transition:transform var(--t),box-shadow var(--t);}
.kart:hover{transform:translateY(-1px);box-shadow:var(--sh);}
.kart-top{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;}
.kart-masa{font-family:'Playfair Display',serif;font-size:1em;font-weight:700;color:var(--purple);}
.kart-garson{font-size:0.7em;color:var(--text3);margin-top:2px;}
.kart-sure{font-size:0.68em;color:var(--text3);margin-left:auto;white-space:nowrap;}
.kart-sure.gecikti{color:var(--red);font-weight:600;}
.kart-urun{font-size:0.88em;font-weight:600;margin-bottom:3px;}
.kart-adet{font-size:0.75em;color:var(--purple);}
.kart-not{font-size:0.76em;color:var(--text3);background:var(--bg2);border:1px solid var(--border);border-radius:5px;padding:5px 8px;margin-top:6px;line-height:1.4;}
.kart-btn{width:100%;margin-top:10px;padding:8px;border:none;border-radius:var(--rs);font-family:'Poppins';font-size:0.77em;font-weight:700;letter-spacing:0.5px;cursor:pointer;transition:all var(--t);}
.kart-btn.hazirla{background:var(--orange-bg);color:var(--orange);border:1px solid var(--orange);}
.kart-btn.hazirla:hover{background:var(--orange);color:#fff;}
.kart-btn.hazir{background:var(--green-bg);color:var(--green);border:1px solid var(--green);}
.kart-btn.hazir:hover{background:var(--green);color:#fff;}

.bos-durum{text-align:center;padding:28px;color:var(--text3);font-size:0.8em;opacity:.6;}

.toast-w{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast{background:var(--card);border:1px solid var(--border2);border-radius:var(--rs);padding:11px 16px;font-size:0.8em;font-weight:500;display:flex;align-items:center;gap:9px;box-shadow:0 10px 36px rgba(0,0,0,0.55);animation:tin .25s ease;min-width:200px;}
.toast.ok{border-left:3px solid var(--green);}
.toast.yeni{border-left:3px solid var(--purple);}
@keyframes tin{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}

@media(max-width:900px){.board{grid-template-columns:1fr 1fr;}}
@media(max-width:600px){.board{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="topbar">
    <span class="tb-logo">LUMIÈRE</span>
    <span class="tb-role">🍸 Barmen</span>
    <span class="tb-yeni" id="yeniSayac">— yeni</span>
    <div class="tb-user"><span><?= htmlspecialchars($_SESSION['kullanici']) ?></span></div>
    <button class="tb-btn" id="themeBtn" onclick="toggleTheme()">☀️</button>
    <a href="logout.php" class="tb-link">Çıkış</a>
</div>

<div class="main">
    <div class="filter-row">
        <button class="filter-btn red active" data-f="bekliyor" onclick="setFilter('bekliyor',this)">Bekliyor</button>
        <button class="filter-btn orange" data-f="hazirlaniyor" onclick="setFilter('hazirlaniyor',this)">Hazırlanıyor</button>
        <button class="filter-btn green" data-f="hazir" onclick="setFilter('hazir',this)">Hazır</button>
        <button class="filter-btn" data-f="hepsi" onclick="setFilter('hepsi',this)">Hepsi</button>
    </div>

    <div class="board">
        <div class="kolon">
            <div class="kolon-hd red"><h3>Bekliyor</h3><span class="kolon-cnt" id="cnt-bekliyor">0</span></div>
            <div class="kolon-body" id="kol-bekliyor"><div class="bos-durum">Yeni sipariş yok</div></div>
        </div>
        <div class="kolon">
            <div class="kolon-hd orange"><h3>Hazırlanıyor</h3><span class="kolon-cnt" id="cnt-hazirlaniyor">0</span></div>
            <div class="kolon-body" id="kol-hazirlaniyor"><div class="bos-durum">Hazırlanan yok</div></div>
        </div>
        <div class="kolon">
            <div class="kolon-hd green"><h3>Hazır</h3><span class="kolon-cnt" id="cnt-hazir">0</span></div>
            <div class="kolon-body" id="kol-hazir"><div class="bos-durum">Hazır sipariş yok</div></div>
        </div>
    </div>
</div>

<div class="toast-w" id="toastW"></div>

<script>
let aktifFilter = 'bekliyor';
let oncekiIds   = new Set();

function setFilter(f, btn) {
    aktifFilter = f;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['bekliyor','hazirlaniyor','hazir'].forEach(k => {
        const kol = document.getElementById('kol-' + k);
        if (kol) kol.closest('.kolon').style.display = (f === 'hepsi' || f === k) ? '' : 'none';
    });
}

function sure(tarih) {
    const diff = Math.floor((Date.now() - new Date(tarih)) / 60000);
    return diff < 1 ? 'Az önce' : diff + ' dk';
}

function geciktiMi(tarih) {
    return (Date.now() - new Date(tarih)) / 60000 > 8;
}

async function durumGuncelle(id, yeniDurum) {
    const fd = new FormData();
    fd.append('islem', 'durum_guncelle');
    fd.append('id', id);
    fd.append('durum', yeniDurum);
    await fetch('ajax.php', { method: 'POST', body: fd });
    yukle();
    toast('Durum güncellendi', 'ok');
}

async function yukle() {
    try {
        const r = await fetch('ajax.php?islem=siparisleri_getir&hedef=barmen');
        const d = await r.json();
        if (d.durum !== 'ok') return;

        const siparisler = d.siparisler;
        const mevcutIds  = new Set(siparisler.map(s => s.id));
        let yeniVar = false;
        mevcutIds.forEach(id => { if (!oncekiIds.has(id)) yeniVar = true; });
        if (oncekiIds.size > 0 && yeniVar) toast('🔔 Yeni sipariş geldi!', 'yeni');
        oncekiIds = mevcutIds;

        const bekliyor     = siparisler.filter(s => s.durum === 'bekliyor');
        const hazirlaniyor = siparisler.filter(s => s.durum === 'hazirlaniyor');
        const hazir        = siparisler.filter(s => s.durum === 'hazir');

        document.getElementById('cnt-bekliyor').textContent     = bekliyor.length;
        document.getElementById('cnt-hazirlaniyor').textContent = hazirlaniyor.length;
        document.getElementById('cnt-hazir').textContent        = hazir.length;

        const yeniSayac = document.getElementById('yeniSayac');
        yeniSayac.textContent = bekliyor.length + ' yeni';
        yeniSayac.style.display = bekliyor.length > 0 ? '' : 'none';

        renderKolon('kol-bekliyor', bekliyor, 'bekliyor');
        renderKolon('kol-hazirlaniyor', hazirlaniyor, 'hazirlaniyor');
        renderKolon('kol-hazir', hazir, 'hazir');
    } catch(e) {}
}

function renderKolon(id, items, durum) {
    const el = document.getElementById(id);
    if (!items.length) { el.innerHTML = '<div class="bos-durum">Boş</div>'; return; }
    el.innerHTML = items.map(s => {
        const gc = geciktiMi(s.tarih);
        let btnHtml = '';
        if (durum === 'bekliyor')     btnHtml = `<button class="kart-btn hazirla" onclick="durumGuncelle(${s.id},'hazirlaniyor')">Hazırlamaya Başla</button>`;
        if (durum === 'hazirlaniyor') btnHtml = `<button class="kart-btn hazir" onclick="durumGuncelle(${s.id},'hazir')">Hazır ✓</button>`;
        if (durum === 'hazir')        btnHtml = `<button class="kart-btn" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green);opacity:.6;cursor:default">Garson Bekliyor</button>`;
        return `
        <div class="kart">
            <div class="kart-top">
                <div>
                    <div class="kart-masa">Masa ${s.masa_no}</div>
                    <div class="kart-garson">${s.garson}</div>
                </div>
                <div class="kart-sure ${gc ? 'gecikti' : ''}">${sure(s.tarih)}${gc ? ' ⚠' : ''}</div>
            </div>
            <div class="kart-urun">${s.urun_adi}</div>
            <div class="kart-adet">× ${s.adet}</div>
            ${s.not_metni ? `<div class="kart-not">📝 ${s.not_metni}</div>` : ''}
            ${btnHtml}
        </div>`;
    }).join('');
}

function toast(msg, tip) {
    const w = document.getElementById('toastW');
    const t = document.createElement('div');
    t.className = `toast ${tip}`;
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3500);
}

function toggleTheme() {
    const root = document.documentElement;
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    document.getElementById('themeBtn').textContent = next === 'dark' ? '☀️' : '🌙';
    localStorage.setItem('lm_bm_theme', next);
}

window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('lm_bm_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    document.getElementById('themeBtn').textContent = saved === 'dark' ? '☀️' : '🌙';
    yukle();
    setInterval(yukle, 5000);
});
</script>
</body>
</html>
