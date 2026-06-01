<?php

declare(strict_types=1);

/**
 * Backup database sederhana untuk Visi (Phase 2 - ops).
 *
 * Pemakaian:
 *   php backup.php            Buat dump .sql.gz ke storage/backups/
 *   php backup.php --keep=7   Simpan hanya 7 backup terbaru (rotasi)
 *
 * Membutuhkan mysqldump tersedia di PATH (atau set MYSQLDUMP_PATH di .env).
 * Kredensial dibaca dari config/database.php (yang membaca .env).
 *
 * Catatan: untuk backup otomatis, jadwalkan via cron (Linux) atau
 * Task Scheduler (Windows), mis. tiap hari:
 *   0 2 * * * php /path/visi/backup.php --keep=14
 */

define('BASE_PATH', __DIR__);

require BASE_PATH . '/src/Env.php';
Env::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/database.php';

// Parse --keep=N
$keep = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--keep=(\d+)$/', $arg, $m)) {
        $keep = (int) $m[1];
    }
}

$backupDir = BASE_PATH . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$dumpBin = getenv('MYSQLDUMP_PATH') ?: 'mysqldump';
$timestamp = date('Y-m-d_His');
$dbName = (string) $config['dbname'];
$outFile = $backupDir . "/{$dbName}_{$timestamp}.sql";

// Bangun argumen mysqldump secara aman (escapeshellarg per nilai).
$args = [
    '--host=' . $config['host'],
    '--port=' . $config['port'],
    '--user=' . $config['username'],
    '--single-transaction',
    '--quick',
    '--default-character-set=utf8mb4',
    $dbName,
];

// Password lewat env var MYSQL_PWD agar tidak tampil di daftar proses.
$envPrefix = '';
if (($config['password'] ?? '') !== '') {
    putenv('MYSQL_PWD=' . $config['password']);
}

$cmd = escapeshellarg($dumpBin);
foreach ($args as $a) {
    $cmd .= ' ' . escapeshellarg($a);
}
$cmd .= ' > ' . escapeshellarg($outFile);

echo "-> Membuat backup {$dbName} ... ";
exec($cmd . ' 2>&1', $output, $exitCode);

if ($exitCode !== 0 || !is_file($outFile) || filesize($outFile) === 0) {
    fwrite(STDERR, "GAGAL\n");
    fwrite(STDERR, "  Pastikan '{$dumpBin}' tersedia (set MYSQLDUMP_PATH di .env bila perlu).\n");
    if ($output) {
        fwrite(STDERR, '  ' . implode("\n  ", $output) . "\n");
    }
    @unlink($outFile);
    exit(1);
}

// Kompres dengan gzip bila ekstensi tersedia.
if (function_exists('gzopen')) {
    $gzFile = $outFile . '.gz';
    $fp = fopen($outFile, 'rb');
    $gz = gzopen($gzFile, 'wb9');
    if ($fp && $gz) {
        while (!feof($fp)) {
            gzwrite($gz, (string) fread($fp, 1 << 20));
        }
        fclose($fp);
        gzclose($gz);
        unlink($outFile);
        $outFile = $gzFile;
    }
}

echo "OK\n";
echo "   File: {$outFile} (" . number_format(filesize($outFile) / 1024, 1) . " KB)\n";

// Rotasi: simpan hanya N backup terbaru.
if ($keep > 0) {
    $files = glob($backupDir . "/{$dbName}_*.sql*") ?: [];
    usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
    $old = array_slice($files, $keep);
    foreach ($old as $f) {
        unlink($f);
        echo "   Hapus backup lama: " . basename($f) . "\n";
    }
}

exit(0);
