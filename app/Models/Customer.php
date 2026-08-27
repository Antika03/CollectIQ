<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_internet',
        'ncli',
        'nama_pelanggan',
        'alamat',
        'sto',
        'datel',
        'nama_layanan_internet',
        'no_hp_terbaru',
        'email',
        'tipe_hunian_terbaru',
        'saldo_piutang',
        'umur_customer',
        'is_pranpc',
        'bill_category',
        'assigned_ar_agent_id',
        'risk_score',
        'risk_level',
        'last_visit_at',
        'total_visits',
        'broken_ptp_count',
        'pending_ptp_count',
    ];

    protected $casts = [
        'saldo_piutang' => 'decimal:2',
        'is_pranpc'     => 'boolean',
        'last_visit_at' => 'datetime',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function latestVisit()
    {
        return $this->hasOne(Visit::class)->latestOfMany('tanggal_input');
    }

    public function assignedArAgent()
    {
        return $this->belongsTo(ArAgent::class, 'assigned_ar_agent_id');
    }

    public function viseeproData()
    {
        return $this->hasMany(ViseeproData::class);
    }

    public function promiseToPays()
    {
        return $this->hasMany(PromiseToPay::class);
    }

    public function riskScoreLogs()
    {
        return $this->hasMany(RiskScoreLog::class);
    }

    public function caringLogs()
    {
        return $this->hasMany(CaringLog::class);
    }

    /**
     * Format nomor HP agar kompatibel dengan tautan WhatsApp (wa.me/62...)
     */
    public function getFormattedWaNumberAttribute(): ?string
    {
        if (empty($this->no_hp_terbaru)) {
            return null;
        }

        // Hapus karakter selain angka
        $clean = preg_replace('/[^0-9]/', '', (string)$this->no_hp_terbaru);
        if (empty($clean)) {
            return null;
        }

        // Normalisasi format Indonesia
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }

        return $clean;
    }

    /**
     * Template pesan penagihan & caring WhatsApp resmi Telkom Witel Priangan Timur
     */
    public function getWaMessageTemplateAttribute(): string
    {
        $nomorInternet = $this->nomor_internet ?: '-';
        $namaPelanggan = $this->nama_pelanggan ?: '-';

        return "Selamat siang Bapak/Ibu.\n\n"
            . "Perkenalkan, saya dari Tim Collection Telkom Indonesia.\n\n"
            . "Mohon izin menghubungi terkait layanan Indibiz dengan nomor internet {$nomorInternet} atas nama {$namaPelanggan}\n"
            . "Berdasarkan data kami, saat ini masih terdapat tagihan layanan yang belum dilakukan pembayaran. Apakah ada kendala yang menyebabkan pembayaran belum dapat dilakukan, Bapak/Ibu?\n\n"
            . "Apabila berkenan, mohon diinformasikan perkiraan tanggal pembayaran yang akan dilakukan agar dapat kami bantu monitoring.\n\n"
            . "Sebagai informasi, pembayaran dapat dilakukan melalui Mobile Banking, Internet Banking, ATM, Indomaret, Alfamart, Tokopedia, GoPay, Kantor Pos, maupun kanal pembayaran resmi lainnya.\n\n"
            . "Terima kasih atas perhatian dan kerja sama Bapak/Ibu. Semoga sehat selalu dan aktivitasnya berjalan lancar.\n\n"
            . "INFO PENTING :\n"
            . "1. WASPADA terhadap oknum Teknisi yang datang dengan alasan menarik ONT untuk menggantikan dengan yang baru.\n"
            . "2. Tidak disarankan menitip pembayaran melalui Account Manager (AM) & Account Representative (AR) atau Petugas yang datang.\n\n"
            . "Terima kasih\n"
            . "Telkom Witel Priangan Timur";
    }

    /**
     * URL tautan langsung ke WhatsApp dengan nomor dan teks pesan otomatis
     */
    public function getWaUrlAttribute(): ?string
    {
        $phone = $this->formatted_wa_number;
        if (!$phone) {
            return null;
        }

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($this->wa_message_template);
    }
}