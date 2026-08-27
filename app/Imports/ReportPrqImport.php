<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\ArAgent;
use App\Models\Visit;
use App\Services\DataNormalizerService;
use App\Services\CustomerSyncService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReportPrqImport implements ToCollection
{
    public int $processedCount = 0;
    public int $createdVisits  = 0;
    public int $updatedVisits  = 0;

    public function collection(Collection $rows)
    {
        $rows->shift();

        DB::transaction(function () use ($rows) {
            $this->processRows($rows);
        });
    }

    private function processRows(Collection $rows)
    {
        $agentCache = [];
        foreach (ArAgent::all() as $ag) {
            $agentCache[strtoupper($ag->name)] = $ag;
        }

        foreach ($rows as $row) {
            if (empty($row[0])) {
                continue;
            }

            $rawSnd = trim((string)$row[0]);
            $snd = CustomerSyncService::cleanSnd($rawSnd);

            if (!$snd) {
                continue;
            }

            $layanan = !empty($row[2]) ? trim((string)$row[2]) : null;
            $rawAr = !empty($row[3]) ? trim((string)$row[3]) : null;
            $namaPelanggan = !empty($row[4]) ? trim((string)$row[4]) : null;
            $rawHp = !empty($row[5]) ? trim((string)$row[5]) : null;
            $cleanHp = DataNormalizerService::normalizePhone($rawHp);
            $tipeHunian = !empty($row[6]) ? trim((string)$row[6]) : null;
            $chatId = !empty($row[11]) ? trim((string)$row[11]) : null;

            // AR Agent
            $arAgent = null;
            if ($rawAr) {
                $canonicalAr = DataNormalizerService::normalizeArName($rawAr);
                if ($canonicalAr) {
                    $key = strtoupper($canonicalAr);
                    if (isset($agentCache[$key])) {
                        $arAgent = $agentCache[$key];
                        if (!empty($chatId) && empty($arAgent->chat_id_telegram)) {
                            $arAgent->chat_id_telegram = $chatId;
                            $arAgent->save();
                        }
                    } else {
                        $arAgent = ArAgent::create([
                            'name'              => $canonicalAr,
                            'chat_id_telegram'  => $chatId,
                            'is_active'         => true,
                        ]);
                        $agentCache[$key] = $arAgent;
                    }
                }
            }

            // Customer
            $customer = Customer::where('nomor_internet', $snd)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'nomor_internet'        => $snd,
                    'nama_pelanggan'        => ($namaPelanggan && $namaPelanggan !== '-') ? $namaPelanggan : 'Pelanggan ' . $snd,
                    'nama_layanan_internet' => $layanan ?: 'Internet',
                    'no_hp_terbaru'         => $cleanHp,
                    'tipe_hunian_terbaru'   => $tipeHunian,
                    'assigned_ar_agent_id'  => $arAgent?->id,
                ]);
            } else {
                $updates = [];
                if (!empty($cleanHp) && empty($customer->no_hp_terbaru)) {
                    $updates['no_hp_terbaru'] = $cleanHp;
                }
                if (!empty($namaPelanggan) && $namaPelanggan !== '-' && ($customer->nama_pelanggan === '-' || strlen($namaPelanggan) > strlen((string)$customer->nama_pelanggan))) {
                    $updates['nama_pelanggan'] = $namaPelanggan;
                }
                if (!empty($layanan) && empty($customer->nama_layanan_internet)) {
                    $updates['nama_layanan_internet'] = $layanan;
                }
                if (!empty($tipeHunian) && empty($customer->tipe_hunian_terbaru)) {
                    $updates['tipe_hunian_terbaru'] = $tipeHunian;
                }
                if (!empty($arAgent) && empty($customer->assigned_ar_agent_id)) {
                    $updates['assigned_ar_agent_id'] = $arAgent->id;
                }
                if (!empty($updates)) {
                    $customer->update($updates);
                }
            }

            // Tanggal Input
            $tanggalInput = now();
            if (!empty($row[1])) {
                try {
                    $tanggalInput = Carbon::createFromFormat('d/m/Y H:i:s', trim((string)$row[1]));
                } catch (\Throwable $e) {
                    try {
                        $tanggalInput = Carbon::createFromFormat('d/m/Y', trim((string)$row[1]));
                    } catch (\Throwable $e2) {
                        $tanggalInput = now();
                    }
                }
            }

            // Hasil Visit & PTP
            $hasilVisit = !empty($row[7]) ? trim((string)$row[7]) : 'Belum Diisi';
            $kategoriVisit = !empty($row[8]) ? DataNormalizerService::normalizeVisitCategory($row[8]) : '-';
            $isPtp = str_contains(strtolower($hasilVisit), 'janji') ||
                     str_contains(strtolower($kategoriVisit), 'janji') ||
                     str_contains(strtolower($kategoriVisit), 'jb') ||
                     str_contains(strtolower($hasilVisit), 'ptp');

            $collectId = 'PRQ-' . $snd . '-' . $tanggalInput->format('Ymd');

            $visit = Visit::updateOrCreate(
                [
                    'collect_id' => $collectId,
                ],
                [
                    'customer_id'          => $customer->id,
                    'ar_agent_id'          => $arAgent?->id,
                    'tanggal_input'        => $tanggalInput,
                    'hasil_visit'          => $hasilVisit,
                    'kategori_visit'       => $kategoriVisit,
                    'keterangan_visit'     => !empty($row[9]) ? trim((string)$row[9]) : '-',
                    'foto_url'             => !empty($row[10]) ? trim((string)$row[10]) : null,
                    'no_hp_snapshot'       => $cleanHp ?: $rawHp,
                    'tipe_hunian_snapshot' => $tipeHunian,
                    'is_ptp'               => $isPtp,
                ]
            );

            $customer->update([
                'last_visit_at' => $tanggalInput,
                'total_visits'  => $customer->visits()->count(),
            ]);

            $this->processedCount++;
            if ($visit->wasRecentlyCreated) {
                $this->createdVisits++;
            } else {
                $this->updatedVisits++;
            }
        }
    }
}