-- ============================================================
--  LUMIÈRE CAFÉ — Tam Veritabanı Kurulum Scripti
--  Tablolar: ayarlar, kategoriler, urunler, kullanicilar,
--            siparisler, siparis_log
--  Sipariş akışı: bekliyor → hazirlaniyor → hazir → teslim
-- ============================================================

CREATE DATABASE IF NOT EXISTS kafe_sistemi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE kafe_sistemi;

-- ============================================================
-- 1. AYARLAR
-- ============================================================
CREATE TABLE IF NOT EXISTS ayarlar (
    id                INT            AUTO_INCREMENT PRIMARY KEY,
    kafe_adi          VARCHAR(255)   NOT NULL DEFAULT 'Lumière Café',
    hakkimizda        TEXT,
    adres             VARCHAR(255),
    telefon           VARCHAR(50),
    eposta            VARCHAR(100),
    instagram         VARCHAR(100),
    personel_referans VARCHAR(100),
    guncellendi       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. KATEGORİLER
--    hedef: hangi istasyona gideceğini belirler
-- ============================================================
CREATE TABLE IF NOT EXISTS kategoriler (
    id     INT           AUTO_INCREMENT PRIMARY KEY,
    ad     VARCHAR(100)  NOT NULL,
    hedef  ENUM('barista','barmen') NOT NULL DEFAULT 'barista',
    sira   INT           NOT NULL DEFAULT 0,
    aktif  TINYINT(1)    NOT NULL DEFAULT 1,
    UNIQUE KEY uq_kategori_ad (ad)
);

-- ============================================================
-- 3. ÜRÜNLER
-- ============================================================
CREATE TABLE IF NOT EXISTS urunler (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT             DEFAULT NULL,
    kategori    VARCHAR(100)    DEFAULT '',
    ad          VARCHAR(255)    NOT NULL,
    aciklama    VARCHAR(500)    DEFAULT '',
    fiyat       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    aktif       TINYINT(1)      NOT NULL DEFAULT 1,
    olusturuldu TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_urun_kategori
        FOREIGN KEY (kategori_id) REFERENCES kategoriler(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- ============================================================
-- 4. KULLANICILAR
--    Roller: admin, garson, barista, barmen
-- ============================================================
CREATE TABLE IF NOT EXISTS kullanicilar (
    id            INT           AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50)   NOT NULL,
    sifre         VARCHAR(255)  NOT NULL,
    rol           ENUM('admin','garson','barista','barmen') NOT NULL DEFAULT 'garson',
    aktif         TINYINT(1)    NOT NULL DEFAULT 1,
    olusturuldu   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kullanici_adi (kullanici_adi)
);

-- ============================================================
-- 5. SİPARİŞLER
--    Durum akışı (barista & barmen için aynı):
--      bekliyor → hazirlaniyor → hazir → teslim
-- ============================================================
CREATE TABLE IF NOT EXISTS siparisler (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    masa_no     VARCHAR(50)     NOT NULL,
    garson      VARCHAR(100)    NOT NULL,
    urun_id     INT             DEFAULT NULL,
    urun_adi    VARCHAR(255)    NOT NULL,
    urun_fiyat  DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    adet        INT             NOT NULL DEFAULT 1,
    not_metni   TEXT,
    hedef       ENUM('barista','barmen') NOT NULL DEFAULT 'barista',
    durum       ENUM('bekliyor','hazirlaniyor','hazir','teslim') NOT NULL DEFAULT 'bekliyor',
    tarih       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    guncellendi TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hedef_durum (hedef, durum),
    INDEX idx_tarih (tarih),
    INDEX idx_masa (masa_no)
);

-- ============================================================
-- 6. SİPARİŞ LOG
--    Her durum değişikliğini kayıt altına alır.
--    Admin panelinde geçmiş takibi için kullanılır.
-- ============================================================
CREATE TABLE IF NOT EXISTS siparis_log (
    id           INT         AUTO_INCREMENT PRIMARY KEY,
    siparis_id   INT         NOT NULL,
    eski_durum   ENUM('bekliyor','hazirlaniyor','hazir','teslim') DEFAULT NULL,
    yeni_durum   ENUM('bekliyor','hazirlaniyor','hazir','teslim') NOT NULL,
    degistiren   VARCHAR(100),           -- kullanıcı adı (session'dan)
    tarih        TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_siparis_id (siparis_id),
    CONSTRAINT fk_log_siparis
        FOREIGN KEY (siparis_id) REFERENCES siparisler(id)
        ON DELETE CASCADE
);

-- ============================================================
-- 7. VARSAYILAN VERİLER — AYARLAR
-- ============================================================
INSERT IGNORE INTO ayarlar
    (id, kafe_adi, hakkimizda, adres, telefon, eposta, instagram, personel_referans)
VALUES (
    1,
    'Lumière Café',
    'Modern, zarif ve nitelikli kahvenin değişmez adresi. Özenle seçilmiş çekirdeklerimiz ve usta baristalarımızla size unutulmaz bir deneyim sunuyoruz.',
    'Alsancak Mah. Kıbrıs Şehitleri Cad. No:123 Konak/İzmir',
    '+90 555 123 45 67',
    'hello@lumierecafe.com',
    '@lumierecafe',
    NULL
);

-- ============================================================
-- 8. VARSAYILAN VERİLER — KATEGORİLER
-- ============================================================
INSERT IGNORE INTO kategoriler (ad, hedef, sira, aktif) VALUES
('İmza Kahveler',  'barista', 1, 1),
('Sıcak İçecekler','barista', 2, 1),
('Soğuk Kahveler', 'barista', 3, 1),
('Tatlılar',       'barista', 4, 1),
('Alkoller',       'barmen',  5, 1),
('Soft İçecekler', 'barmen',  6, 1);

-- ============================================================
-- 9. VARSAYILAN VERİLER — ÜRÜNLER
-- ============================================================
INSERT IGNORE INTO urunler (kategori_id, kategori, ad, aciklama, fiyat, aktif) VALUES
-- Barista ürünleri
(1, 'İmza Kahveler',   'Lumière Blend Espresso', 'Özel kavrum, çikolata ve fındık notaları', 110.00, 1),
(1, 'İmza Kahveler',   'V60 Pour Over',           'Etiyopya Yirgacheffe, çiçeksi aromalar',   130.00, 1),
(1, 'İmza Kahveler',   'Flat White',              'Çift ristretto, kadifemsi süt',             120.00, 1),
(2, 'Sıcak İçecekler', 'Latte',                   'Espresso, buharda ısıtılmış süt',           105.00, 1),
(2, 'Sıcak İçecekler', 'Cappuccino',              'Çift shot, köpüklü süt',                   100.00, 1),
(2, 'Sıcak İçecekler', 'Türk Kahvesi',            'Geleneksel pişirim, isteğe göre şeker',     90.00, 1),
(3, 'Soğuk Kahveler',  'Cold Brew',               '18 saat soğuk demleme',                    115.00, 1),
(3, 'Soğuk Kahveler',  'Iced Latte',              'Espresso, soğuk süt, buz',                 110.00, 1),
(3, 'Soğuk Kahveler',  'Frappé',                  'Espresso, süt, buz, köpük',                120.00, 1),
(4, 'Tatlılar',        'Artisan Kruvasan',        'Gerçek tereyağlı, taze pişmiş',             95.00, 1),
(4, 'Tatlılar',        'Tiramisu',                'Ev yapımı, mascarpone krem',               145.00, 1),
(4, 'Tatlılar',        'Cheesecake',              'New York usulü, mevsim meyveli',           155.00, 1),
-- Barmen ürünleri
(5, 'Alkoller',        'Aperol Spritz',           'Aperol, prosecco, soda',                   220.00, 1),
(5, 'Alkoller',        'Negroni',                 'Campari, gin, sweet vermouth',             250.00, 1),
(5, 'Alkoller',        'Mojito',                  'Beyaz rom, nane, lime, soda',              230.00, 1),
(5, 'Alkoller',        'House Wine',              'Şarap seçkisi (kırmızı/beyaz/rosé)',       180.00, 1),
(5, 'Alkoller',        'Craft Beer',              'Günün bira seçkisi',                       120.00, 1),
(6, 'Soft İçecekler',  'Limonata',                'Taze sıkılmış, nane',                       85.00, 1),
(6, 'Soft İçecekler',  'Meyveli Soda',            'Taze meyve, soda',                          80.00, 1),
(6, 'Soft İçecekler',  'Taze Sıkılmış Portakal',  'Taze portakal suyu',                        90.00, 1);

-- ============================================================
-- 10. VARSAYILAN VERİLER — KULLANICILAR
--     Tüm şifreler: "password"
--     password_hash('password', PASSWORD_BCRYPT)
-- ============================================================
INSERT IGNORE INTO kullanicilar (kullanici_adi, sifre, rol, aktif) VALUES
('admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   1),
('garson1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garson',  1),
('garson2',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garson',  1),
('barista1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barista', 1),
('barmen1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barmen',  1);

-- ============================================================
-- 11. AJAX.PHP İÇİN REFERANS
--     Aşağıdaki sorgular ajax.php'de kullanılır.
--     Bu script doğrudan SQL değil, yorum olarak bırakıldı.
-- ============================================================

/*
  --- ajax.php: siparisleri_getir (barista veya barmen) ---

  SELECT id, masa_no, garson, urun_adi, adet, not_metni, durum, tarih
  FROM siparisler
  WHERE hedef = :hedef
    AND durum IN ('bekliyor','hazirlaniyor','hazir')
  ORDER BY tarih ASC;

  --- ajax.php: siparis_gonder (garson) ---

  INSERT INTO siparisler
    (masa_no, garson, urun_id, urun_adi, urun_fiyat, adet, not_metni, hedef, durum)
  VALUES
    (:masa, :garson, :urun_id, :urun_adi, :fiyat, :adet, :not, :hedef, 'bekliyor');

  --- ajax.php: durum_guncelle ---

  Akış: bekliyor → hazirlaniyor → hazir → teslim

  -- Önce eski durumu al:
  SELECT durum FROM siparisler WHERE id = :id;

  -- Güncelle:
  UPDATE siparisler SET durum = :yeni_durum WHERE id = :id;

  -- Loga yaz:
  INSERT INTO siparis_log (siparis_id, eski_durum, yeni_durum, degistiren)
  VALUES (:id, :eski_durum, :yeni_durum, :session_kullanici);

  --- Garson: teslim edildi butonu ---
  UPDATE siparisler SET durum = 'teslim' WHERE id = :id AND durum = 'hazir';

*/

-- ============================================================
-- FIN
-- ============================================================