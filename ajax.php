<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['kullanici'])) {
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Oturum yok.']);
    exit;
}

$islem     = $_POST['islem'] ?? $_GET['islem'] ?? '';
$kullanici = $_SESSION['kullanici'];
$rol       = $_SESSION['rol'] ?? '';

if ($islem === 'siparis_gonder') {
    $sepet     = json_decode($_POST['sepet'] ?? '[]', true);
    $masa      = trim($_POST['masa'] ?? '');
    $not_metni = trim($_POST['not'] ?? '');

    if (empty($sepet) || $masa === '') {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Masa veya sepet boş.']);
        exit;
    }

    $stmtUrun = $db->prepare("SELECT u.ad, u.fiyat, COALESCE(k.hedef,'barista') AS hedef FROM urunler u LEFT JOIN kategoriler k ON u.kategori_id = k.id WHERE u.id = ? AND u.aktif = 1");
    $stmtSip  = $db->prepare("INSERT INTO siparisler (masa_no, garson, urun_id, urun_adi, urun_fiyat, adet, not_metni, hedef, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'bekliyor')");

    try {
        foreach ($sepet as $item) {
            $stmtUrun->execute([(int)$item['id']]);
            $row = $stmtUrun->fetch();
            if (!$row) continue;
            $stmtSip->execute([
                $masa,
                $kullanici,
                (int)$item['id'],
                $row['ad'],
                $row['fiyat'],
                max(1, (int)($item['adet'] ?? 1)),
                $not_metni,
                $row['hedef'],
            ]);
        }
        echo json_encode(['durum' => 'ok', 'mesaj' => 'Sipariş gönderildi.']);
    } catch (Exception $e) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Sipariş kaydedilemedi: ' . $e->getMessage()]);
    }
    exit;
}

if ($islem === 'siparisleri_getir') {
    $hedef = $_GET['hedef'] ?? $rol;

    if (in_array($hedef, ['barista', 'barmen'], true)) {
        $stmt = $db->prepare("SELECT id, masa_no, garson, urun_adi, urun_fiyat, adet, not_metni, hedef, durum, tarih FROM siparisler WHERE hedef = ? AND durum != 'teslim' ORDER BY tarih ASC");
        $stmt->execute([$hedef]);
    } else {
        $stmt = $db->query("SELECT id, masa_no, garson, urun_adi, urun_fiyat, adet, not_metni, hedef, durum, tarih FROM siparisler WHERE durum != 'teslim' ORDER BY tarih ASC");
    }

    echo json_encode(['durum' => 'ok', 'siparisler' => $stmt->fetchAll()]);
    exit;
}

if ($islem === 'durum_guncelle') {
    $id        = (int)($_POST['id'] ?? 0);
    $yeniDurum = $_POST['durum'] ?? '';

    $akis = [
        'bekliyor'     => 'hazirlaniyor',
        'hazirlaniyor' => 'hazir',
        'hazir'        => 'teslim',
    ];

    if (!$id || !in_array($yeniDurum, ['hazirlaniyor', 'hazir', 'teslim'], true)) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        exit;
    }

    $mevcut = $db->prepare("SELECT durum FROM siparisler WHERE id = ?");
    $mevcut->execute([$id]);
    $siparis = $mevcut->fetch();

    if (!$siparis) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Sipariş bulunamadı.']);
        exit;
    }

    if (($akis[$siparis['durum']] ?? null) !== $yeniDurum) {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Geçersiz durum geçişi.']);
        exit;
    }

    $db->prepare("UPDATE siparisler SET durum = ? WHERE id = ?")->execute([$yeniDurum, $id]);
    echo json_encode(['durum' => 'ok', 'yeni_durum' => $yeniDurum]);
    exit;
}

if ($islem === 'masa_siparisleri') {
    $masa = trim($_GET['masa'] ?? '');
    if ($masa === '') {
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Masa boş.']);
        exit;
    }
    $stmt = $db->prepare("SELECT id, urun_adi, urun_fiyat, adet, not_metni, hedef, durum, tarih FROM siparisler WHERE masa_no = ? AND durum != 'teslim' ORDER BY tarih ASC");
    $stmt->execute([$masa]);
    $rows   = $stmt->fetchAll();
    $toplam = array_sum(array_map(fn($r) => $r['urun_fiyat'] * $r['adet'], $rows));
    echo json_encode(['durum' => 'ok', 'siparisler' => $rows, 'toplam' => round($toplam, 2)]);
    exit;
}

if ($islem === 'yeni_sayisi') {
    $hedef = trim($_GET['hedef'] ?? '');
    if (in_array($hedef, ['barista', 'barmen'], true)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM siparisler WHERE durum = 'bekliyor' AND hedef = ?");
        $stmt->execute([$hedef]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) FROM siparisler WHERE durum = 'bekliyor'");
    }
    echo json_encode(['durum' => 'ok', 'sayi' => (int)$stmt->fetchColumn()]);
    exit;
}

echo json_encode(['durum' => 'hata', 'mesaj' => 'Bilinmeyen işlem.']);