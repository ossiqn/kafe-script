<?php
$host   = 'localhost';
$dbname = 'kafe_sistemi';
$user   = 'root';
$pass   = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['islem'])) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Veritabanı bağlantı hatası.']);
        exit;
    }
    die('Kritik Hata: Veritabanına bağlanılamadı. MySQL açık mı?');
}