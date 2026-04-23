<?php
/**
 * PANZER · sahibinden v3 — ortak DB router
 * Eski "sdn" yerine projenin ortak jakartaxdw DB'sine baglanir.
 */
$_pzr_cfg = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
          . 'BellaMain' . DIRECTORY_SEPARATOR
          . 'database' . DIRECTORY_SEPARATOR
          . 'config.php';

if (!is_file($_pzr_cfg)) {
    http_response_code(500);
    die('Config bulunamadi.');
}

require $_pzr_cfg;

$dbname   = $dbName;
$user     = $dbUser;
$password = $dbPass;
$dsn      = "mysql:host={$dbHost};charset=utf8;dbname={$dbname}";

/**
 * Ensure v3 listing tables exist in the shared DB.
 * Safe to call multiple times — runs DDL only once per request.
 */
function pzr_ensure_listing_tables(PDO $conn): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $conn->exec("
    CREATE TABLE IF NOT EXISTS `ilan_telefon` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `seller_name` varchar(100) NOT NULL,
      `seller_phone` varchar(100) NOT NULL,
      `ilanurl` varchar(255) NOT NULL,
      `ilanad` varchar(255) NOT NULL,
      `ilanfiyat` varchar(255) NOT NULL,
      `ilan_resim` longtext NOT NULL,
      `il` varchar(100) NOT NULL,
      `ilce` varchar(100) NOT NULL,
      `mahalle` varchar(100) NOT NULL,
      `ilan_no` varchar(10) NOT NULL,
      `ilan_date` varchar(50) NOT NULL,
      `marka` varchar(100) NOT NULL,
      `model` varchar(100) NOT NULL,
      `os` varchar(10) NOT NULL,
      `storage` varchar(50) NOT NULL,
      `inches` varchar(25) NOT NULL,
      `ram` varchar(25) NOT NULL,
      `backcamera` varchar(25) NOT NULL,
      `frontcamera` varchar(25) NOT NULL,
      `color` varchar(100) NOT NULL,
      `garanti` varchar(100) NOT NULL,
      `fromwho` varchar(255) NOT NULL,
      `swap` varchar(255) NOT NULL,
      `status` varchar(255) NOT NULL,
      `description` longtext NOT NULL,
      `hizmetbedeli` varchar(255) NOT NULL,
      `banks` varchar(255) NOT NULL,
      `hesapsahibi` varchar(255) NOT NULL,
      `iban` varchar(255) NOT NULL,
      `hesapno` varchar(255) NOT NULL,
      `subekodu` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `ilanurl` (`ilanurl`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    $conn->exec("
    CREATE TABLE IF NOT EXISTS `ilan_playstation` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `seller_name` varchar(100) NOT NULL,
      `seller_phone` varchar(100) NOT NULL,
      `ilanurl` varchar(255) NOT NULL,
      `ilanad` varchar(255) NOT NULL,
      `ilanfiyat` varchar(255) NOT NULL,
      `ilan_resim` longtext NOT NULL,
      `il` varchar(100) NOT NULL,
      `ilce` varchar(100) NOT NULL,
      `mahalle` varchar(100) NOT NULL,
      `ilan_no` varchar(10) NOT NULL,
      `ilan_date` varchar(50) NOT NULL,
      `marka` varchar(255) NOT NULL,
      `case_type` varchar(255) NOT NULL,
      `storage` varchar(255) NOT NULL,
      `os` varchar(255) NOT NULL,
      `ps5_model` varchar(255) NOT NULL,
      `renk` varchar(255) NOT NULL,
      `garanti` varchar(255) NOT NULL,
      `fromwho` varchar(255) NOT NULL,
      `swap` varchar(255) NOT NULL,
      `status` varchar(255) NOT NULL,
      `description` longtext NOT NULL,
      `hizmetbedeli` varchar(255) NOT NULL,
      `banks` varchar(255) NOT NULL,
      `hesapsahibi` varchar(255) NOT NULL,
      `iban` varchar(255) NOT NULL,
      `hesapno` varchar(255) NOT NULL,
      `subekodu` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `ilanurl` (`ilanurl`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
}
