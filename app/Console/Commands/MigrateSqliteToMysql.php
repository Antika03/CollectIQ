<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class MigrateSqliteToMysql extends Command
{
    protected $signature = 'db:migrate-sqlite-to-mysql
                            {--fresh : Kosongkan tabel data MySQL sebelum import}
                            {--force : Lewati prompt konfirmasi interaktif}';

    protected $description = 'Migrasi seluruh data dari SQLite lokal ke MySQL Railway';

    public function handle()
    {
        $this->info('==============================================');
        $this->info(' MIGRASI SQLITE -> MYSQL RAILWAY');
        $this->info('==============================================');
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 1. Cek file SQLite
        |--------------------------------------------------------------------------
        */

        $sqlitePath = database_path('database.sqlite');

        if (!file_exists($sqlitePath)) {
            $this->error("File SQLite tidak ditemukan:");
            $this->error($sqlitePath);
            return self::FAILURE;
        }

        $this->info("SQLite source:");
        $this->line($sqlitePath);
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 2. Koneksi SQLite
        |--------------------------------------------------------------------------
        */

        try {
            $sqlite = new PDO(
                'sqlite:' . $sqlitePath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $this->info('✓ SQLite berhasil dibuka');
        } catch (Throwable $e) {
            $this->error('Gagal membuka SQLite:');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Cek koneksi MySQL Railway
        |--------------------------------------------------------------------------
        */

        try {
            DB::connection('mysql')->getPdo();

            $mysqlDatabase = DB::connection('mysql')
                ->getDatabaseName();

            $this->info('✓ MySQL Railway berhasil terhubung');
            $this->line("Database: {$mysqlDatabase}");
        } catch (Throwable $e) {
            $this->error('Gagal konek ke MySQL Railway:');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 4. Daftar tabel yang akan dimigrasikan
        |--------------------------------------------------------------------------
        |
        | migrations tidak ikut dipindahkan karena schema MySQL
        | sudah dibuat menggunakan migration Laravel.
        |
        */

        $tables = [
            'users',
            'ar_agents',
            'customers',
            'visits',
            'promise_to_pays',
            'risk_score_logs',
            'follow_up_recommendations',
            'telegram_reminders',
            'viseepro_data',
            'caring_logs',
            'settings',
            'witel_performances',
        ];

        /*
        |--------------------------------------------------------------------------
        | 5. Tampilkan jumlah data SQLite
        |--------------------------------------------------------------------------
        */

        $this->info('DATA SQLITE LOKAL');
        $this->line('------------------------------');

        $totalRows = 0;

        foreach ($tables as $table) {

            $exists = $sqlite->query("
                SELECT name
                FROM sqlite_master
                WHERE type = 'table'
                AND name = '{$table}'
            ")->fetchColumn();

            if (!$exists) {
                $this->warn("{$table}: tabel tidak ditemukan");
                continue;
            }

            $count = (int) $sqlite
                ->query("SELECT COUNT(*) FROM `{$table}`")
                ->fetchColumn();

            $totalRows += $count;

            $this->line(
                str_pad($table, 35) . ': ' . number_format($count)
            );
        }

        $this->newLine();

        $this->info(
            'TOTAL ROW SQLITE: ' . number_format($totalRows)
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | 6. Konfirmasi
        |--------------------------------------------------------------------------
        */

        if (!$this->option('force')) {
            if (!$this->option('fresh')) {
                if (!$this->confirm(
                    'Import data ke MySQL tanpa menghapus data MySQL yang sudah ada?'
                )) {
                    $this->warn('Migrasi dibatalkan.');
                    return self::SUCCESS;
                }
            } else {
                $this->warn(
                    'MODE FRESH AKTIF: data pada tabel target akan dikosongkan terlebih dahulu.'
                );

                if (!$this->confirm(
                    'Yakin ingin menghapus data lama MySQL dan menggantinya dengan SQLite?'
                )) {
                    $this->warn('Migrasi dibatalkan.');
                    return self::SUCCESS;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Disable foreign key MySQL
        |--------------------------------------------------------------------------
        */

        DB::connection('mysql')->statement(
            'SET FOREIGN_KEY_CHECKS=0'
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Hapus data lama jika --fresh
        |--------------------------------------------------------------------------
        */

        if ($this->option('fresh')) {

            $this->newLine();
            $this->info('Mengosongkan tabel MySQL...');

            foreach ($tables as $table) {

                try {

                    DB::connection('mysql')
                        ->table($table)
                        ->truncate();

                    $this->line("✓ {$table}");

                } catch (Throwable $e) {

                    $this->warn(
                        "Tidak dapat truncate {$table}: " .
                        $e->getMessage()
                    );
                }
            }

            $this->newLine();
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Import data
        |--------------------------------------------------------------------------
        */

        foreach ($tables as $table) {

            $this->newLine();

            $this->info("IMPORT: {$table}");

            $exists = $sqlite->query("
                SELECT name
                FROM sqlite_master
                WHERE type = 'table'
                AND name = '{$table}'
            ")->fetchColumn();

            if (!$exists) {
                $this->warn("Tabel {$table} tidak ada di SQLite.");
                continue;
            }

            $count = (int) $sqlite
                ->query("SELECT COUNT(*) FROM `{$table}`")
                ->fetchColumn();

            if ($count === 0) {
                $this->line('Tidak ada data.');
                continue;
            }

            $offset = 0;
            $chunkSize = 500;

            $imported = 0;

            while ($offset < $count) {

                $stmt = $sqlite->query("
                    SELECT *
                    FROM `{$table}`
                    LIMIT {$chunkSize}
                    OFFSET {$offset}
                ");

                $rows = $stmt->fetchAll();

                if (empty($rows)) {
                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Normalisasi boolean SQLite
                |--------------------------------------------------------------------------
                */

                foreach ($rows as &$row) {

                    foreach ($row as $column => $value) {

                        if (is_bool($value)) {
                            $row[$column] = $value ? 1 : 0;
                        }
                    }
                }

                unset($row);

                /*
                |--------------------------------------------------------------------------
                | Insert ke MySQL
                |--------------------------------------------------------------------------
                */

                DB::connection('mysql')
                    ->table($table)
                    ->insert($rows);

                $imported += count($rows);
                $offset += count($rows);

                $percentage = $count > 0
                    ? round(($imported / $count) * 100, 1)
                    : 100;

                $this->line(
                    "  {$imported}/{$count} ({$percentage}%)"
                );
            }

            $this->info(
                "✓ {$table} selesai: " . number_format($imported)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Enable foreign keys
        |--------------------------------------------------------------------------
        */

        DB::connection('mysql')->statement(
            'SET FOREIGN_KEY_CHECKS=1'
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Verifikasi
        |--------------------------------------------------------------------------
        */

        $this->newLine();
        $this->info('==============================================');
        $this->info(' VERIFIKASI DATA');
        $this->info('==============================================');
        $this->newLine();

        $allCorrect = true;

        foreach ($tables as $table) {

            $sqliteCount = (int) $sqlite
                ->query("SELECT COUNT(*) FROM `{$table}`")
                ->fetchColumn();

            $mysqlCount = (int) DB::connection('mysql')
                ->table($table)
                ->count();

            if ($sqliteCount === $mysqlCount) {

                $this->info(
                    "✓ {$table}: " .
                    number_format($mysqlCount) .
                    ' / ' .
                    number_format($sqliteCount)
                );

            } else {

                $this->error(
                    "✗ {$table}: MySQL=" .
                    number_format($mysqlCount) .
                    ' | SQLite=' .
                    number_format($sqliteCount)
                );

                $allCorrect = false;
            }
        }

        $this->newLine();

        if ($allCorrect) {

            $this->info('==============================================');
            $this->info(' MIGRASI BERHASIL 100%');
            $this->info('==============================================');

            return self::SUCCESS;
        }

        $this->error('==============================================');
        $this->error(' ADA DATA YANG TIDAK SESUAI');
        $this->error('==============================================');

        return self::FAILURE;
    }
}