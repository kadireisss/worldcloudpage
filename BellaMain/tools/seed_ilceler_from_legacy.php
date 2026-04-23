<?php
declare(strict_types=1);

require __DIR__ . '/../database/connect.php';

function loadLegacyMap(array $candidates): array
{
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

    foreach ($candidates as $file) {
        if (!is_file($file)) {
            continue;
        }
        $raw = (string) @file_get_contents($file);
        if ($raw === '') {
            continue;
        }
        $clean = preg_replace('/^\s*var\s+data\s*=\s*/', '', $raw);
        if (is_string($clean) && $clean !== '') {
            $raw = $clean;
        }
        $raw = $extractJsonArray($raw);
        $raw = rtrim($raw, " \t\r\n;");
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

        $rows = [];
        foreach ($decoded as $item) {
            $il = trim((string)($item['il'] ?? ''));
            if ($il === '') {
                continue;
            }
            foreach ((array)($item['ilceleri'] ?? []) as $ilce) {
                $iv = trim((string)$ilce);
                if ($iv === '') {
                    continue;
                }
                $rows[] = [$il, $iv];
            }
        }
        if ($rows) {
            return $rows;
        }
    }
    return [];
}

$db->exec(
    "CREATE TABLE IF NOT EXISTS ilceler (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        il VARCHAR(120) NOT NULL,
        ilce VARCHAR(120) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_il_ilce (il, ilce),
        KEY idx_il (il)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$rows = loadLegacyMap([
    dirname(__DIR__, 2) . '/shopier/iller.js',
    dirname(__DIR__, 2) . '/Dolap/iller.js',
    dirname(__DIR__, 2) . '/turkcell/iller.js',
]);

if (!$rows) {
    fwrite(STDERR, "Legacy il/ilce verisi okunamadi.\n");
    exit(1);
}

$ins = $db->prepare("INSERT IGNORE INTO ilceler (il, ilce) VALUES (:il, :ilce)");
$count = 0;
foreach ($rows as [$il, $ilce]) {
    $ins->execute([':il' => $il, ':ilce' => $ilce]);
    $count += (int) $ins->rowCount();
}

echo "ilceler seed tamam. Eklenen satir: {$count}\n";
