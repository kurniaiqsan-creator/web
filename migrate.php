<?php

declare(strict_types=1);

/**
 * Migration runner sederhana untuk Visi.
 *
 * Pemakaian:
 *   php migrate.php up        Jalankan semua migrasi yang belum dipakai.
 *   php migrate.php status    Tampilkan status tiap file migrasi (applied/pending).
 *   php migrate.php           Sama dengan `up`.
 *
 * Migrasi dibaca dari folder database/migrations/*.sql (urut secara natural).
 * Riwayat dicatat di tabel `schema_migrations`. Statement dipisah `;`.
 *
 * Catatan: jalankan SETELAH database & tabel inti dibuat (database/schema.sql).
 */

define('BASE_PATH', __DIR__);

require BASE_PATH . '/src/Env.php';
Env::load(BASE_PATH . '/.env');

require BASE_PATH . '/src/Database.php';

$command = $argv[1] ?? 'up';
$migrationsDir = BASE_PATH . '/database/migrations';

if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Folder migrasi tidak ditemukan: {$migrationsDir}\n");
    exit(1);
}

try {
    $pdo = Database::getInstance();
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal konek database: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Pastikan database sudah dibuat (database/schema.sql) dan .env benar.\n");
    exit(1);
}

ensureMigrationsTable();

$files = glob($migrationsDir . '/*.sql') ?: [];
natsort($files);
$files = array_values($files);

switch ($command) {
    case 'status':
        printStatus($files);
        break;
    case 'up':
        runUp($files);
        break;
    default:
        fwrite(STDERR, "Perintah tidak dikenal: {$command}\n");
        fwrite(STDERR, "Gunakan: php migrate.php [up|status]\n");
        exit(1);
}

exit(0);

// ===== Helpers =====

function ensureMigrationsTable(): void
{
    Database::query(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    );
}

/** @return array<string,bool> nama migrasi yang sudah dipakai */
function appliedMigrations(): array
{
    $rows = Database::fetchAll("SELECT migration FROM schema_migrations");
    $map = [];
    foreach ($rows as $r) {
        $map[$r['migration']] = true;
    }
    return $map;
}

/** @param string[] $files */
function printStatus(array $files): void
{
    $applied = appliedMigrations();
    if (empty($files)) {
        echo "Tidak ada file migrasi.\n";
        return;
    }
    echo "Status migrasi:\n";
    foreach ($files as $file) {
        $name = basename($file);
        $mark = isset($applied[$name]) ? '[applied]' : '[pending]';
        echo "  {$mark} {$name}\n";
    }
}

/** @param string[] $files */
function runUp(array $files): void
{
    $applied = appliedMigrations();
    $pending = array_filter($files, static fn($f) => !isset($applied[basename($f)]));

    if (empty($pending)) {
        echo "Tidak ada migrasi baru. Database sudah up-to-date.\n";
        return;
    }

    foreach ($pending as $file) {
        $name = basename($file);
        echo "-> Menjalankan {$name} ... ";

        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "GAGAL (tidak bisa baca file)\n";
            exit(1);
        }

        $statements = splitSqlStatements($sql);

        try {
            Database::beginTransaction();
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') {
                    continue;
                }
                Database::getInstance()->exec($stmt);
            }
            // Catatan: DDL (CREATE/ALTER/DROP) memicu implicit commit di MySQL,
            // sehingga transaksi bisa sudah tertutup di sini. Insert riwayat lalu
            // commit hanya jika transaksi masih aktif (hindari "no active transaction").
            Database::insert('schema_migrations', ['migration' => $name]);
            if (Database::getInstance()->inTransaction()) {
                Database::commit();
            }
            echo "OK\n";
        } catch (Throwable $e) {
            // DDL di MySQL auto-commit; rollback mungkin tidak penuh, tapi tetap dicoba.
            if (Database::getInstance()->inTransaction()) {
                Database::rollback();
            }
            echo "GAGAL\n";
            fwrite(STDERR, "  Error pada {$name}: " . $e->getMessage() . "\n");
            exit(1);
        }
    }

    echo "Selesai. " . count($pending) . " migrasi dijalankan.\n";
}

/**
 * Pisah SQL multi-statement berdasarkan `;` di akhir baris.
 * Mengabaikan baris komentar `--` dan `#`. Cukup untuk migrasi DDL sederhana
 * (tidak mendukung stored procedure / DELIMITER kustom).
 *
 * @return string[]
 */
function splitSqlStatements(string $sql): array
{
    // Buang komentar baris penuh supaya tidak mengganggu pemisahan.
    $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];
    $clean = [];
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $clean[] = $line;
    }
    $joined = implode("\n", $clean);

    $parts = explode(';', $joined);
    return array_map('trim', $parts);
}
