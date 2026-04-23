<?php
/**
 * PANZER · Il/Ilce ortak endpoint
 * Tum scriptlerin sipariş formundaki il/ilçe select'leri buradan DB'den çeker.
 *
 * Kullanim:
 *   GET /BellaMain/database/iller.php?action=il
 *     -> ["Adana","Adiyaman",...]
 *   GET /BellaMain/database/iller.php?action=ilce&il=Adana
 *     -> ["Aladağ","Ceyhan",...]
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // 1 gun cache (il/ilçe değişmez)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require __DIR__ . '/connect.php';

$action = $_GET['action'] ?? 'il';

/**
 * DB'deki ilceler tablosu yoksa/boşsa legacy JS datasından oku.
 */
function bellla_load_legacy_il_ilce_data(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $candidates = [
        dirname(__DIR__, 2) . '/shopier/iller.js',
        dirname(__DIR__, 2) . '/Dolap/iller.js',
        dirname(__DIR__, 2) . '/turkcell/iller.js',
    ];
    foreach ($candidates as $file) {
        if (!is_file($file)) {
            continue;
        }
        $raw = (string) @file_get_contents($file);
        if ($raw === '') {
            continue;
        }
        $raw = preg_replace('/^\s*var\s+data\s*=\s*/u', '', $raw);
        $raw = rtrim((string) $raw, " \t\r\n;");
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            continue;
        }
        $map = [];
        foreach ($decoded as $row) {
            $il = trim((string) ($row['il'] ?? ''));
            if ($il === '') {
                continue;
            }
            $ilceler = [];
            foreach ((array) ($row['ilceleri'] ?? []) as $ilce) {
                $iv = trim((string) $ilce);
                if ($iv !== '') {
                    $ilceler[] = $iv;
                }
            }
            if ($ilceler) {
                sort($ilceler, SORT_LOCALE_STRING);
            }
            $map[$il] = array_values(array_unique($ilceler));
        }
        if ($map) {
            ksort($map, SORT_LOCALE_STRING);
            $cache = $map;
            return $cache;
        }
    }

    $cache = [];
    return $cache;
}

function bellla_output_fallback(string $action, string $il = ''): void
{
    $map = bellla_load_legacy_il_ilce_data();
    if ($action === 'ilce') {
        echo json_encode(array_values($map[$il] ?? []), JSON_UNESCAPED_UNICODE);
        return;
    }
    echo json_encode(array_keys($map), JSON_UNESCAPED_UNICODE);
}

try {
    if ($action === 'ilce') {
        $il = trim((string)($_GET['il'] ?? ''));
        if ($il === '') {
            echo json_encode([]);
            exit;
        }
        $list = [];
        try {
            $stmt = $db->prepare("SELECT DISTINCT ilce FROM ilceler WHERE il = :il ORDER BY ilce ASC");
            $stmt->execute([':il' => $il]);
            foreach ($stmt as $row) {
                $val = trim((string)($row['ilce'] ?? ''));
                if ($val !== '') {
                    $list[] = $val;
                }
            }
        } catch (\Throwable $e) {
            $list = [];
        }
        if (!$list) {
            bellla_output_fallback('ilce', $il);
            exit;
        }
        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // varsayilan: il listesi
    $list = [];
    try {
        $stmt = $db->query("SELECT DISTINCT il FROM ilceler ORDER BY il ASC");
        foreach ($stmt as $row) {
            $val = trim((string)($row['il'] ?? ''));
            if ($val !== '') {
                $list[] = $val;
            }
        }
    } catch (\Throwable $e) {
        $list = [];
    }
    if (!$list) {
        bellla_output_fallback('il');
        exit;
    }
    echo json_encode($list, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    bellla_output_fallback($action, trim((string)($_GET['il'] ?? '')));
}
