<?php
/**
 * Seeds the `ilceler` table with Turkey's il/ilçe data.
 * Only runs if the table is empty. Called once from router.php.
 */
function pzr_seed_ilceler(PDO $conn): void
{
    try {
        $count = $conn->query("SELECT COUNT(*) FROM ilceler")->fetchColumn();
        if ((int)$count > 0) return;
    } catch (Throwable $e) {
        return; // table might not exist yet
    }

    // Extract INSERT statements from sdn.sql
    $sqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sdn.sql';
    if (!is_file($sqlFile)) return;

    $content = @file_get_contents($sqlFile);
    if ($content === false) return;

    // Find the INSERT INTO `ilceler` block
    if (preg_match('/INSERT INTO `ilceler`[^;]+;/s', $content, $m)) {
        try {
            $conn->exec($m[0]);
        } catch (Throwable $e) {
            // silent — might be partial data or encoding issue
        }
    }
}
