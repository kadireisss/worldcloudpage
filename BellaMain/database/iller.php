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

    $extractJsonArray = static function (string $raw): string {
        $start = strpos($raw, '[');
        if ($start === false) {
            return $raw;
        }
        $depth = 0;
        $inString = false;
        $escaped = false;
        $len = strlen($raw);
        for ($i = $start; $i < $len; $i++) {
            $ch = $raw[$i];
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                continue;
            }
            if ($ch === '"') {
                $inString = !$inString;
                continue;
            }
            if ($inString) {
                continue;
            }
            if ($ch === '[') {
                $depth++;
                continue;
            }
            if ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($raw, $start, ($i - $start) + 1);
                }
            }
        }
        return $raw;
    };

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
        // Bazı legacy dosyalarda UTF-8 bozuk byte olabiliyor; /u kullanma.
        $clean = preg_replace('/^\s*var\s+data\s*=\s*/', '', $raw);
        if (is_string($clean) && $clean !== '') {
            $raw = $clean;
        }
        // shopier/iller.js gibi dosyalarda data dizisinden sonra jQuery kodu var.
        $raw = $extractJsonArray($raw);
        $raw = rtrim((string) $raw, " \t\r\n;");
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) && function_exists('mb_convert_encoding')) {
            $rawUtf8 = @mb_convert_encoding($raw, 'UTF-8', 'UTF-8, ISO-8859-9, Windows-1254');
            if (is_string($rawUtf8) && $rawUtf8 !== '') {
                $decoded = json_decode($rawUtf8, true);
            }
        }
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
