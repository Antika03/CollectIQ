<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CaringLog;
use App\Models\Visit;

class ChurnRiskService
{
    /**
     * Hitung Churn Risk Indicator untuk seorang pelanggan
     * Menggunakan rule-based engine transparan (bukan klaim ML)
     */
    public static function evaluateCustomer(Customer $customer): array
    {
        $score = 0;
        $reasons = [];
        $recommendation = 'Layanan Rutin';

        // Parameter 1: Saldo Piutang / Aging
        if ($customer->saldo_piutang > 1000000) {
            $score += 35;
            $reasons[] = 'Saldo piutang tinggi (> Rp 1 Juta)';
        } elseif ($customer->saldo_piutang > 500000) {
            $score += 20;
            $reasons[] = 'Saldo piutang > Rp 500 Ribu';
        }

        if (str_contains($customer->umur_customer ?? '', '> 12') || str_contains($customer->umur_customer ?? '', '10-12')) {
            $score += 25;
            $reasons[] = 'Umur tunggakan kritis (> 10-12 Bulan)';
        } elseif (str_contains($customer->umur_customer ?? '', '07-09')) {
            $score += 15;
            $reasons[] = 'Umur tunggakan sedang (7-9 Bulan)';
        }

        // Parameter 2: Respons Caring Terakhir (VOC)
        $latestCaring = CaringLog::where('customer_id', $customer->id)
            ->orWhere('nomor_internet', $customer->nomor_internet)
            ->latest('tanggal_caring')
            ->first();

        if ($latestCaring) {
            $voc = strtolower($latestCaring->voc ?? '');
            if (str_contains($voc, 'cabut') || str_contains($voc, 'berhenti') || str_contains($voc, 'putus')) {
                $score += 40;
                $reasons[] = 'Caring: Pelanggan meminta cabut/berhenti berlangganan';
            } elseif (str_contains($voc, 'tidak aktif') || str_contains($voc, 'rejected')) {
                $score += 25;
                $reasons[] = 'Caring: Nomor tidak aktif / panggilan ditolak';
            } elseif (str_contains($voc, 'bussy') || str_contains($voc, 'rna')) {
                $score += 15;
                $reasons[] = 'Caring: Tidak terhubung (Ring No Answer / Sibuk)';
            }
        }

        // Parameter 3: Ketiadaan Nomor Telepon Valid
        if (empty($customer->no_hp_terbaru) || !preg_match('/^\d{9,15}$/', $customer->no_hp_terbaru)) {
            $score += 15;
            $reasons[] = 'Kontak nomor HP tidak valid di master data';
        }

        // Parameter 4: Riwayat Visit & Broken PTP
        if ($customer->broken_ptp_count > 0) {
            $score += 20;
            $reasons[] = "Terdapat {$customer->broken_ptp_count} janji bayar (PTP) tidak terealisasi";
        }

        if ($customer->total_visits > 3 && $customer->pending_ptp_count === 0) {
            $score += 15;
            $reasons[] = 'Kunjungan berulang (> 3x) tanpa komitmen pembayaran';
        }

        // Tentukan Level & Rekomendasi
        if ($score >= 70) {
            $level = 'CRITICAL';
            $recommendation = 'Prioritas Visit Khusus & Intervensi Retensi / Winback Segera';
        } elseif ($score >= 45) {
            $level = 'HIGH';
            $recommendation = 'Follow-up Collection Langsung & Konfirmasi Janji Bayar';
        } elseif ($score >= 25) {
            $level = 'MEDIUM';
            $recommendation = 'Caring Ulang via Kanal Alternatif & Monitoring Tagihan';
        } else {
            $level = 'LOW';
            $recommendation = 'Monitoring Rutin / Pelayanan Reguler';
        }

        return [
            'score'          => min($score, 100),
            'level'          => $level,
            'reasons'        => $reasons,
            'recommendation' => $recommendation,
        ];
    }
}
