<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftTimestampsToWib extends Command
{
    protected $signature = 'db:shift-wib {--dry : Show queries only}';
    protected $description = 'Geser semua kolom datetime/timestamp existing +7 jam (UTC -> WIB). Jalankan sekali saja.';

    // ponytail: skip tabel internal Laravel yang tidak relevan bisnis
    private array $skipTables = [
        'migrations', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'sessions', 'password_reset_tokens', 'personal_access_tokens',
    ];

    public function handle(): int
    {
        $db = DB::connection()->getDatabaseName();
        $cols = DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('datetime','timestamp')",
            [$db]
        );

        if (!$this->option('dry') && !$this->confirm("Geser +7 jam untuk semua kolom datetime di DB '$db'? Backup dulu!")) {
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($cols as $c) {
            if (in_array($c->TABLE_NAME, $this->skipTables, true)) continue;
            $sql = "UPDATE `{$c->TABLE_NAME}` SET `{$c->COLUMN_NAME}` = DATE_ADD(`{$c->COLUMN_NAME}`, INTERVAL 7 HOUR) WHERE `{$c->COLUMN_NAME}` IS NOT NULL";
            $this->line($sql);
            if (!$this->option('dry')) {
                $affected = DB::update($sql);
                $this->info("  -> $affected row updated");
                $count += $affected;
            }
        }

        $this->info("Done. Total updated: $count");
        return self::SUCCESS;
    }
}
