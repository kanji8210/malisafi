<?php
// Simple verifier: scans PHP files and reports error_log occurrences
// that are not guarded by "if (defined('WP_DEBUG') && WP_DEBUG)" within
// the previous 8 lines.
$root = __DIR__ . '/..';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$files = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') continue;
    // skip vendor/composer and tools/ directory
    if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (strpos($path, DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR) !== false) continue;
    $files[] = $path;
}
$issues = [];
foreach ($files as $file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'error_log(') !== false) {
            // look back up to 8 lines for WP_DEBUG guard
            $guard_found = false;
            for ($k = 1; $k <= 8; $k++) {
                $idx = $i - $k;
                if ($idx < 0) break;
                $prev = $lines[$idx];
                if (strpos($prev, "defined('WP_DEBUG')") !== false || strpos($prev, 'WP_DEBUG') !== false) {
                    $guard_found = true;
                    break;
                }
                // also check if within a multi-line if block opening earlier
                if (preg_match("/if\s*\(.*WP_DEBUG.*\)/", $prev)) {
                    $guard_found = true;
                    break;
                }
            }
            if (!$guard_found) {
                $issues[] = [
                    'file' => $file,
                    'line' => $i+1,
                    'snippet' => trim($line)
                ];
            }
        }
    }
}
// Print results
if (empty($issues)) {
    echo "OK: No unguarded error_log() occurrences found.\n";
    exit(0);
}
foreach ($issues as $iss) {
    echo "UNGARDED: {$iss['file']}:{$iss['line']} -> {$iss['snippet']}\n";
}
exit(1);
