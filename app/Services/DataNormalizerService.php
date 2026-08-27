<?php

namespace App\Services;

class DataNormalizerService
{
    /**
     * Daftar blacklist string yang bukan nama personil AR (channel pembayaran, bank, merchant, loket, catatan pelanggan)
     */
    private static array $nonArBlacklist = [
        'BANK', 'MANDIRI', 'BRI', 'BCA', 'BNI', 'BSM', 'SYARIAH', 'DANAMON', 'PERMATA', 'BTN', 'NIAGA', 'NISP',
        'POS', 'ALFAMART', 'INDOMART', 'INDOMARET', 'TOKOPEDIA', 'SHOPEE', 'BLIBLI', 'FINNET', 'LINKAJA', 'MYBCA',
        'BIMASAKTI', 'KOPEGTEL', 'DIGITAL PAYMENT', 'PAYMENT', 'LOKET', 'MAGNA', 'VALUE STREAM', 'RAHARJA',
        'TELKOMSEL', 'INDIC', 'TOKYO', 'KANTOR DESA', 'PEMILIK BARU', 'TOKO ADE', 'BRILINK', 'BUMDES', 'WARUNG',
        '#N/A', '#REF!', 'UNKNOWN', 'TIM PUSAT', 'PDK', 'CBT', 'PT ', 'CV '
    ];

    /**
     * Peta normalisasi nama variasi AR ke Master Canonical AR
     */
    private static array $canonicalArMap = [
        // TATANG
        'TATANG'                => 'Tatang',
        'TATANG '               => 'Tatang',
        'TATANG.'               => 'Tatang',
        'TATANGG'               => 'Tatang',
        'TATNG'                 => 'Tatang',
        'TATANG CIREBON'        => 'Tatang',

        // SAYUS
        'SAYUS'                 => 'Sayus Supriyanto',
        'SAYUS S'               => 'Sayus Supriyanto',
        'SAYUS.S'               => 'Sayus Supriyanto',
        'SAYUS. S'              => 'Sayus Supriyanto',
        'SAYUS S.'              => 'Sayus Supriyanto',
        'SAYUSS'                => 'Sayus Supriyanto',
        'SAYYS S'               => 'Sayus Supriyanto',
        'SAYYS.S'               => 'Sayus Supriyanto',
        'SAYUS SUPRIYANTO'      => 'Sayus Supriyanto',
        'SAYUS SUPRIYSNTO'      => 'Sayus Supriyanto',

        // SANTI
        'SANTI'                 => 'Santi Surahman',
        'SANTI S'               => 'Santi Surahman',
        'SANTI SURAHMAN'        => 'Santi Surahman',

        // WAHYU
        'WAHYU'                 => 'Wahyu Mulyadi',
        'WAHYU M'               => 'Wahyu Mulyadi',
        'WAHYU MULYADI'         => 'Wahyu Mulyadi',
        'WAHYU MULYADI CAFFE KAHAJI' => 'Wahyu Mulyadi',
        'WAHYU IHWAYUDA'        => 'Wahyu Ihwayuda',

        // YAYAT
        'YAYAT'                 => 'Yayat Ruhiyat',
        'YAYAT RUHIYAT'         => 'Yayat Ruhiyat',

        // FAJAR
        'FAJAR'                 => 'Fajar Ramdhani Ishak',
        'FAJAR R.I'             => 'Fajar Ramdhani Ishak',
        'FAJAR RI'              => 'Fajar Ramdhani Ishak',
        'FAJAR RAMDHANI'        => 'Fajar Ramdhani Ishak',
        'FAJAR RAMDHANI ISHAK'  => 'Fajar Ramdhani Ishak',

        // RAFLI
        'RAFLI'                 => 'Rafli Zulfikar',
        'RAFLI Z'               => 'Rafli Zulfikar',
        'RAFLI ZULFIKAR'        => 'Rafli Zulfikar',

        // BAMBANG
        'BAMBANG'               => 'Bambang',

        // IDA
        'IDA'                   => 'Ida Herlina',
        'IDA HERLINA'           => 'Ida Herlina',

        // YANA
        'YANA'                  => 'Yana Suryana',
        'YANA SURYANA'          => 'Yana Suryana',
        'YANA YANASURYANA'      => 'Yana Suryana',

        // VINA / FINA
        'VINA'                  => 'Vina Aprilia',
        'VINA APRIL'            => 'Vina Aprilia',
        'VINA APRILIA'          => 'Vina Aprilia',
        'FINA'                  => 'Vina Aprilia',
        'AEP VINA APRILIA'      => 'Vina Aprilia',

        // AHMAD
        'AHMAD'                 => 'Ahmad Ali Subarkah',
        'AHMAD ALI S'           => 'Ahmad Ali Subarkah',
        'AHMAD ALI SUBARKAH'    => 'Ahmad Ali Subarkah',

        // AGNES
        'AGNES'                 => 'Agnes Prawesti Puspa Lestari',
        'AGNES PRAWESTI'        => 'Agnes Prawesti Puspa Lestari',
        'AGNES PRAWESTI PUSPA LESTARI' => 'Agnes Prawesti Puspa Lestari',

        // MERIN
        'MERIN'                 => 'Merin Meriani',
        'MERIN MERIANI'         => 'Merin Meriani',

        // NOVI
        'NOVI'                  => 'Novi',

        // AR LAINNYA
        'NINA ROSANA'           => 'Nina Rosana',
        'NURHAYATI'             => 'Nurhayati',
        'SHOKIKAH'              => 'Shokikah',
        'SONI YUNIAR'           => 'Soni Yuniar',
        'WISNU'                 => 'Wisnu',
        'DEA RAUDHAH JANNAH SUDRAJAT' => 'Dea Raudhah Jannah Sudrajat',
        'DINDA NINENGAH SANDRA' => 'Dinda Ninengah Sandra',
        'MOHAMAD ALLAN SADAT'   => 'Mohamad Allan Sadat',
        'BUNGA'                 => 'Bunga',
        'APIW'                  => 'Apiw',
        'REVA'                  => 'Reva',
        'NIZAR'                 => 'Nizar',
        'SANDI'                 => 'Sandi',
        'SHOFIRA'               => 'Shofira',
        'FATTAH'                => 'Fattah Taobah Santana',
        'FATTAH TAOBAH SANTANA' => 'Fattah Taobah Santana',
        'GINA SULISTIANTI'      => 'Gina Sulistianti',
        'ARYA SAPURA FIRMANSYAH'=> 'Arya Sapura Firmansyah',
        'ARYA SAPURA F'         => 'Arya Sapura Firmansyah',
        'ARYA SAPURA FIRMANSYAG'=> 'Arya Sapura Firmansyah',
        'MOHAMAD RIZALDY'       => 'Mohamad Rizaldy',
        'AEP KUSNADI'           => 'Aep Kusnadi',
        'REYNALDI SAPUTRA'      => 'Reynaldi Saputra',
        'MUZIA ALIF LUKMAN'     => 'Muzia Alif Lukman',
        'DIINAN NUR KH'         => 'Diinan Nur Kh',
        'DIINAN NUR'            => 'Diinan Nur Kh',
        'DIINAN NUR KH & SITI AISYAH' => 'Diinan Nur Kh',
    ];

    /**
     * Memeriksa apakah string merupakan channel pembayaran / bukan AR
     */
    public static function isNonArChannel(?string $raw): bool
    {
        if (empty($raw)) {
            return true;
        }

        $upper = strtoupper(trim($raw));

        if (strlen($upper) < 2 || $upper === '-' || $upper === '0' || $upper === 'NONE') {
            return true;
        }

        foreach (self::$nonArBlacklist as $black) {
            if (str_contains($upper, $black)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisasi Nama AR ke Master Canonical Name
     */
    public static function normalizeArName(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        $cleaned = trim($raw);
        $upper = strtoupper($cleaned);

        // Hapus karakter pemisah yang tidak perlu
        $upper = str_replace([';', '-', '_', '/'], ' ', $upper);
        $upper = preg_replace('/\s+/', ' ', $upper);
        $upper = trim($upper);

        // Cek apakah channel pembayaran atau bukan orang
        if (self::isNonArChannel($upper)) {
            return null;
        }

        // Cek mapping langsung
        if (isset(self::$canonicalArMap[$upper])) {
            return self::$canonicalArMap[$upper];
        }

        // Cek tanpa titik
        $noDots = str_replace('.', '', $upper);
        $noDots = preg_replace('/\s+/', ' ', $noDots);
        $noDots = trim($noDots);
        if (isset(self::$canonicalArMap[$noDots])) {
            return self::$canonicalArMap[$noDots];
        }

        // Fuzzy matching untuk variasi yang mirip
        if (str_contains($upper, 'SAYUS')) return 'Sayus Supriyanto';
        if (str_contains($upper, 'SANTI')) return 'Santi Surahman';
        if (str_contains($upper, 'TATANG')) return 'Tatang';
        if (str_contains($upper, 'WAHYU')) return 'Wahyu Mulyadi';
        if (str_contains($upper, 'YAYAT')) return 'Yayat Ruhiyat';
        if (str_contains($upper, 'FAJAR')) return 'Fajar Ramdhani Ishak';
        if (str_contains($upper, 'RAFLI')) return 'Rafli Zulfikar';
        if (str_contains($upper, 'BAMBANG')) return 'Bambang';
        if (str_contains($upper, 'IDA HERLINA') || $upper === 'IDA') return 'Ida Herlina';
        if (str_contains($upper, 'YANA')) return 'Yana Suryana';
        if (str_contains($upper, 'VINA') || str_contains($upper, 'FINA')) return 'Vina Aprilia';
        if (str_contains($upper, 'AHMAD ALI') || $upper === 'AHMAD') return 'Ahmad Ali Subarkah';
        if (str_contains($upper, 'AGNES')) return 'Agnes Prawesti Puspa Lestari';
        if (str_contains($upper, 'MERIN')) return 'Merin Meriani';
        if (str_contains($upper, 'NOVI') && !str_contains($upper, 'NOVIA')) return 'Novi';
        if (str_contains($upper, 'ARYA SAPURA')) return 'Arya Sapura Firmansyah';
        if (str_contains($upper, 'FATTAH')) return 'Fattah Taobah Santana';

        return ucwords(strtolower($cleaned));
    }

    /**
     * Normalisasi Status Caring (CONTACTED, UNCONTACTED, NO CARING)
     */
    public static function normalizeCaringStatus(?string $raw): string
    {
        if (empty($raw)) {
            return 'UNCONTACTED';
        }

        $upper = strtoupper(trim($raw));
        $upper = preg_replace('/\s+/', ' ', $upper);

        if (str_contains($upper, 'CONTACTED') || str_contains($upper, 'CONTACT') || str_contains($upper, 'TERHUBUNG')) {
            if (str_contains($upper, 'UN') || str_contains($upper, 'NOT') || str_contains($upper, 'TIDAK')) {
                return 'UNCONTACTED';
            }
            return 'CONTACTED';
        }

        if (str_contains($upper, 'NO CARING') || str_contains($upper, 'BELUM CARING') || str_contains($upper, 'NOT CARING')) {
            return 'NO CARING';
        }

        if (str_contains($upper, 'UNCONTACT') || str_contains($upper, 'RNA') || str_contains($upper, 'BUSY') || str_contains($upper, 'REJECT')) {
            return 'UNCONTACTED';
        }

        return 'UNCONTACTED';
    }

    /**
     * Normalisasi Status Bayar (PAID, UNPAID)
     */
    public static function normalizePaymentStatus(?string $raw): string
    {
        if (empty($raw)) {
            return 'UNPAID';
        }

        $upper = strtoupper(trim($raw));

        if (str_contains($upper, 'PAID') || str_contains($upper, 'LUNAS') || str_contains($upper, 'SUDAH BAYAR')) {
            if (str_contains($upper, 'UN') || str_contains($upper, 'NOT') || str_contains($upper, 'BELUM')) {
                return 'UNPAID';
            }
            return 'PAID';
        }

        return 'UNPAID';
    }

    /**
     * Normalisasi Kategori Tagihan (PRANPC, Eksisting, PSB, WINBACK)
     */
    public static function normalizeBillCategory(?string $raw): string
    {
        if (empty($raw)) {
            return 'Eksisting';
        }

        $upper = strtoupper(trim($raw));

        if (str_contains($upper, 'PRANPC') || str_contains($upper, 'PRA NPC') || str_contains($upper, 'PRA-NPC')) {
            return 'PRANPC';
        }
        if (str_contains($upper, 'PSB') || str_contains($upper, 'PASANG BARU')) {
            return 'PSB';
        }
        if (str_contains($upper, 'WINBACK') || str_contains($upper, 'WIN BACK')) {
            return 'WINBACK';
        }

        return 'Eksisting';
    }

    /**
     * Normalisasi Kategori Visit
     */
    public static function normalizeVisitCategory(?string $raw): string
    {
        if (empty($raw) || trim($raw) === '-' || trim($raw) === '') {
            return '-';
        }

        $upper = strtoupper(trim($raw));

        if (str_contains($upper, 'JB') || str_contains($upper, 'JANJI BAYAR') || str_contains($upper, 'PTP')) {
            return 'Janji Bayar (PTP)';
        }
        if (str_contains($upper, 'BAYAR') || str_contains($upper, 'LUNAS') || str_contains($upper, 'CBT & PDK') || str_contains($upper, 'PDK')) {
            return 'Sudah Bayar / PDK';
        }
        if (str_contains($upper, 'TOLAK') || str_contains($upper, 'REJECT') || str_contains($upper, 'ENGGAN')) {
            return 'Tolak Bayar';
        }
        if (str_contains($upper, 'KOSONG') || str_contains($upper, 'TUTUP') || str_contains($upper, 'RUMAH KOSONG')) {
            return 'Rumah / Usaha Kosong';
        }
        if (str_contains($upper, 'PINDAH') || str_contains($upper, 'ALAMAT')) {
            return 'Pindah Alamat';
        }
        if (str_contains($upper, 'CABUT') || str_contains($upper, 'BONGKAR') || str_contains($upper, 'BERHENTI')) {
            return 'Permintaan Cabut';
        }

        return ucwords(strtolower(trim($raw)));
    }

    /**
     * Normalisasi Voice of Customer (VOC)
     */
    public static function normalizeVoc(?string $raw): string
    {
        if (empty($raw) || trim($raw) === '-' || trim($raw) === '') {
            return 'General Caring';
        }

        $upper = strtoupper(trim($raw));

        if (str_contains($upper, 'JANJI BAYAR') || str_contains($upper, 'PTP')) {
            return 'Customer - Janji Bayar';
        }
        if (str_contains($upper, 'RNA') || str_contains($upper, 'RING NO ANSWER') || str_contains($upper, 'TIDAK ANGKAT')) {
            return 'RNA (Ring No Answer)';
        }
        if (str_contains($upper, 'TIDAK AKTIF') || str_contains($upper, 'NOMOR MATI') || str_contains($upper, 'SALAH SAMBUNG')) {
            return 'No Tidak Aktif / Salah Sambung';
        }
        if (str_contains($upper, 'SUDAH BAYAR') || str_contains($upper, 'LUNAS')) {
            return 'Sudah Bayar';
        }
        if (str_contains($upper, 'REJECT') || str_contains($upper, 'DITOLAK')) {
            return 'Panggilan Ditolak (Rejected)';
        }
        if (str_contains($upper, 'BUSY') || str_contains($upper, 'BUSSY') || str_contains($upper, 'SIBUK')) {
            return 'Nomor Sibuk (Busy)';
        }
        if (str_contains($upper, 'CABUT') || str_contains($upper, 'BERHENTI')) {
            return 'Permintaan Berhenti Berlangganan';
        }
        if (str_contains($upper, 'GANGGUAN') || str_contains($upper, 'RUSAK') || str_contains($upper, 'LAMBAT')) {
            return 'Keluhan Gangguan Layanan';
        }
        if (str_contains($upper, 'KENDALA KEUANGAN') || str_contains($upper, 'EFISIENSI')) {
            return 'Kendala Keuangan / Efisiensi';
        }

        return trim($raw);
    }

    /**
     * Normalisasi nomor telepon Indonesia (multi-separator support, 08xxx / 628xxx format)
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $parts = preg_split('/[;,\|\/\n\r]+/', trim($phone));

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '-' || $part === '0') {
                continue;
            }

            $cleaned = preg_replace('/[^\d]/', '', $part);

            if (str_starts_with($cleaned, '62') && strlen($cleaned) >= 10) {
                $cleaned = '0' . substr($cleaned, 2);
            } elseif (str_starts_with($cleaned, '8') && strlen($cleaned) >= 9) {
                $cleaned = '0' . $cleaned;
            }

            if (strlen($cleaned) >= 10 && strlen($cleaned) <= 14 && str_starts_with($cleaned, '08')) {
                return $cleaned;
            }

            if (strlen($cleaned) >= 9 && strlen($cleaned) <= 12 && str_starts_with($cleaned, '0')) {
                return $cleaned;
            }
        }

        return null;
    }

    /**
     * Normalisasi nama Witel
     */
    public static function normalizeWitel(?string $raw): string
    {
        if (empty($raw)) {
            return 'PRIANGAN TIMUR';
        }

        $upper = strtoupper(trim($raw));

        if (str_contains($upper, 'PRIANGAN TIMUR') || str_contains($upper, 'TASIK') || str_contains($upper, 'CIREBON') || str_contains($upper, 'GARUT')) {
            return 'PRIANGAN TIMUR';
        }
        if (str_contains($upper, 'PRIANGAN BARAT') || str_contains($upper, 'SUKABUMI')) {
            return 'PRIANGAN BARAT';
        }
        if (str_contains($upper, 'BANDUNG')) {
            return 'BANDUNG';
        }
        if (str_contains($upper, 'BEKASI') || str_contains($upper, 'KARAWANG')) {
            return 'BEKASI KARAWANG';
        }
        if (str_contains($upper, 'BANTEN') || str_contains($upper, 'SERANG')) {
            return 'BANTEN';
        }
        if (str_contains($upper, 'JAKARTA INNER')) {
            return 'JAKARTA INNER';
        }
        if (str_contains($upper, 'JAKARTA CENTRUM')) {
            return 'JAKARTA CENTRUM';
        }
        if (str_contains($upper, 'JAKARTA OUTER')) {
            return 'JAKARTA OUTER';
        }

        return $upper;
    }
}
