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
        $this->info("Automatic daily Telegram reminder telah dinonaktifkan sesuai requirement sistem.");
        $this->line("Reminder pelanggan dapat dikelola secara mandiri melalui menu Reminder Center.");
        return Command::SUCCESS;
    }
}
