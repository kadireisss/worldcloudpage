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
    $norm = static function (string $s): string {
        $s = trim($s);
        $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
        $tr = ['ı' => 'i', 'İ' => 'i', 'ş' => 's', 'Ş' => 's', 'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c'];
        return strtr($s, $tr);
    };
    if ($action === 'ilce') {
        if (isset($map[$il])) {
            echo json_encode(array_values($map[$il]), JSON_UNESCAPED_UNICODE);
            return;
        }
        $needle = $norm($il);
        foreach ($map as $ilName => $ilceler) {
            if ($norm((string)$ilName) === $needle) {
                echo json_encode(array_values((array)$ilceler), JSON_UNESCAPED_UNICODE);
                return;
            }
        }
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        return;
    }
    echo json_encode(array_keys($map), JSON_UNESCAPED_UNICODE);
}

function bellla_http_get_json(string $url, int $timeoutSec = 8): ?array
{
    $body = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'PANZER-iller/1.0',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status < 200 || $status >= 300) {
            return null;
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSec,
                'header' => "User-Agent: PANZER-iller/1.0\r\nAccept: application/json\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
    }

    if (!is_string($body) || trim($body) === '') {
        return null;
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function bellla_pick_data_rows(array $decoded): array
{
    foreach (['data', 'result', 'results', 'items'] as $key) {
        if (isset($decoded[$key]) && is_array($decoded[$key])) {
            return $decoded[$key];
        }
    }
    $isList = array_keys($decoded) === range(0, count($decoded) - 1);
    return $isList ? $decoded : [];
}

function bellla_extract_provinces(array $rows): array
{
    $items = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = trim((string)($row['name'] ?? $row['il'] ?? $row['province'] ?? ''));
        if ($name === '') {
            continue;
        }
        $id = (int)($row['id'] ?? $row['provinceId'] ?? $row['plaka'] ?? 0);
        $items[] = ['id' => $id, 'name' => $name];
    }
    return $items;
}

function bellla_extract_districts(array $rows): array
{
    $list = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = trim((string)($row['name'] ?? $row['ilce'] ?? $row['district'] ?? ''));
        if ($name !== '') {
            $list[] = $name;
        }
    }
    $list = array_values(array_unique($list));
    sort($list, SORT_LOCALE_STRING);
    return $list;
}

function bellla_fetch_from_public_api(string $action, string $il = ''): array
{
    $baseUrls = [
        'https://api.turkiyeapi.dev/v1',
        'https://turkiyeapi.dev/v1',
    ];

    if ($action === 'il') {
        foreach ($baseUrls as $base) {
            $decoded = bellla_http_get_json($base . '/provinces?fields=id,name&limit=200');
            if (!is_array($decoded)) {
                continue;
            }
            $rows = bellla_pick_data_rows($decoded);
            $provinces = bellla_extract_provinces($rows);
            if (!$provinces) {
                continue;
            }
            usort($provinces, static fn($a, $b) => strcasecmp((string)$a['name'], (string)$b['name']));
            return array_values(array_map(static fn($p) => (string)$p['name'], $provinces));
        }
        return [];
    }

    if ($il === '') {
        return [];
    }

    // ilce icin once district endpoint'i dene
    foreach ($baseUrls as $base) {
        $url = $base . '/districts?province=' . rawurlencode($il) . '&fields=name&limit=1200';
        $decoded = bellla_http_get_json($url);
        if (!is_array($decoded)) {
            continue;
        }
        $rows = bellla_pick_data_rows($decoded);
        $districts = bellla_extract_districts($rows);
        if ($districts) {
            return $districts;
        }
    }

    // district endpoint bos donerse province detayi icindeki districts alanini dene
    foreach ($baseUrls as $base) {
        $decodedProv = bellla_http_get_json($base . '/provinces?name=' . rawurlencode($il));
        if (!is_array($decodedProv)) {
            continue;
        }
        $rows = bellla_pick_data_rows($decodedProv);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '' || strcasecmp($name, $il) !== 0) {
                continue;
            }
            $districts = bellla_extract_districts((array)($row['districts'] ?? []));
            if ($districts) {
                return $districts;
            }
        }
    }

    return [];
}

try {
    if ($action === 'ilce') {
        $il = trim((string)($_GET['il'] ?? ''));
        if ($il === '') {
            echo json_encode([]);
            exit;
        }
        $list = bellla_fetch_from_public_api('ilce', $il);
        if (!$list) {
            bellla_output_fallback('ilce', $il);
            exit;
        }
        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $list = bellla_fetch_from_public_api('il');
    if (!$list) {
        bellla_output_fallback('il');
        exit;
    }
    echo json_encode($list, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    bellla_output_fallback($action, trim((string)($_GET['il'] ?? '')));
}
