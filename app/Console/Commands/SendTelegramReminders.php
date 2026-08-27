<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class SendTelegramReminders extends Command
{
    protected $signature = 'collectiq:send-telegram-reminders {--force : Abaikan pengecekan waktu setting dan kirim sekarang} {--dry-run : Uji generasi pesan tanpa mengirim ke API Telegram}';
    protected $description = 'Kirim reminder penagihan & monitoring otomatis ke AR Agent via Telegram';

    public function handle(TelegramService $telegramService): int
    {
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("Memulai proses Telegram Reminder untuk AR (Force: " . ($force ? 'YES' : 'NO') . ", Dry-run: " . ($dryRun ? 'YES' : 'NO') . ")...");

        $result = $telegramService->sendDailyReminders($force);

        if ($result['success']) {
            $this->info($result['message']);
            return Command::SUCCESS;
        } else {
            $this->warn($result['message']);
            return Command::FAILURE;
        }
    }
}
