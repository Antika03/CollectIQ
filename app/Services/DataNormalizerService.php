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
     * Daftar prefix seluler operator telekomunikasi Indonesia (WhatsApp-capable)
     */
    private static array $cellularPrefixes = [
        // Telkomsel (Halo, SimPATI, Loop, AS, by.U)
        '0811' => 'Telkomsel', '0812' => 'Telkomsel', '0813' => 'Telkomsel',
        '0821' => 'Telkomsel', '0822' => 'Telkomsel', '0823' => 'Telkomsel',
        '0851' => 'Telkomsel', '0852' => 'Telkomsel', '0853' => 'Telkomsel',

        // Indosat Ooredoo Hutchison (IM3, Mentari, Matrix, 3/Tri)
        '0814' => 'Indosat', '0815' => 'Indosat', '0816' => 'Indosat',
        '0855' => 'Indosat', '0856' => 'Indosat', '0857' => 'Indosat', '0858' => 'Indosat',
        '0895' => '3 (Tri)', '0896' => '3 (Tri)', '0897' => '3 (Tri)',
        '0898' => '3 (Tri)', '0899' => '3 (Tri)',

        // XL Axiata (XL, Axis)
        '0817' => 'XL Axiata', '0818' => 'XL Axiata', '0819' => 'XL Axiata',
        '0859' => 'XL Axiata', '0877' => 'XL Axiata', '0878' => 'XL Axiata',
        '0831' => 'Axis', '0832' => 'Axis', '0833' => 'Axis', '0838' => 'Axis',

        // Smartfren
        '0881' => 'Smartfren', '0882' => 'Smartfren', '0883' => 'Smartfren',
        '0884' => 'Smartfren', '0885' => 'Smartfren', '0886' => 'Smartfren',
        '0887' => 'Smartfren', '0888' => 'Smartfren', '0889' => 'Smartfren',
    ];

    /**
     * Kode area telepon rumah / PSTN fixed line Jawa Barat & sekitarnya (bukan seluler)
     */
    private static array $pstnAreaCodes = [
        '021', '022', '0231', '0232', '0233', '0234', '0260', '0261', '0262',
        '0263', '0264', '0265', '0266', '0267', '0281', '0282', '0283', '0284',
        '0285', '0286', '0287', '0289', '024', '0271', '0274', '031'
    ];

    /**
     * Evaluasi satu nomor telepon terhadap kriteria validitas seluler Indonesia & kandidat WhatsApp
     */
    public static function evaluateIndonesianMobilePhone(?string $phone): array
    {
        if (empty($phone)) {
            return ['valid' => false, 'phone' => null, 'score' => 0, 'reason' => 'Kosong', 'provider' => null];
        }

        $cleaned = preg_replace('/[^\d]/', '', (string)$phone);

        // Normalisasi format internasional ke format nasional 08...
        if (str_starts_with($cleaned, '62') && strlen($cleaned) >= 10) {
            $cleaned = '0' . substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8') && strlen($cleaned) >= 9) {
            $cleaned = '0' . $cleaned;
        }

        $len = strlen($cleaned);

        // Cek panjang umum nomor seluler Indonesia (10-13 digit)
        if ($len < 10 || $len > 13) {
            return ['valid' => false, 'phone' => $cleaned, 'score' => 0, 'reason' => 'Panjang nomor tidak sesuai seluler (10-13 digit)', 'provider' => null];
        }

        // Cek apakah nomor fixed line / PSTN
        foreach (self::$pstnAreaCodes as $pstn) {
            if (str_starts_with($cleaned, $pstn)) {
                return ['valid' => false, 'phone' => $cleaned, 'score' => 0, 'reason' => 'Nomor Fixed Line / Telepon Rumah (PSTN)', 'provider' => 'PSTN'];
            }
        }

        // Cek pola dummy / berulang / sekuensial fiktif
        if (preg_match('/^(\d)\1{7,}$/', $cleaned) ||
            $cleaned === '08123456789' ||
            $cleaned === '081234567890' ||
            $cleaned === '081200000000' ||
            $cleaned === '080000000000' ||
            $cleaned === '089999999999') {
            return ['valid' => false, 'phone' => $cleaned, 'score' => 0, 'reason' => 'Nomor fiktif / placeholder berulang', 'provider' => null];
        }

        // Harus diawali dengan '08'
        if (!str_starts_with($cleaned, '08')) {
            return ['valid' => false, 'phone' => $cleaned, 'score' => 0, 'reason' => 'Bukan awalan seluler 08', 'provider' => null];
        }

        $prefix4 = substr($cleaned, 0, 4);
        $provider = self::$cellularPrefixes[$prefix4] ?? null;

        $score = 0;
        if ($provider !== null) {
            $score += 60; // Prefix operator resmi Indonesia yang dikenal
        } else {
            $score += 20; // Awalan 08 tetapi belum terdaftar dalam map
        }

        // Skor panjang ideal nomor seluler WhatsApp (11 atau 12 digit)
        if ($len === 11 || $len === 12) {
            $score += 30;
        } elseif ($len === 10 || $len === 13) {
            $score += 15;
        }

        return [
            'valid'    => true,
            'phone'    => $cleaned,
            'score'    => $score,
            'reason'   => 'Kandidat Nomor Seluler Valid',
            'provider' => $provider,
        ];
    }

    /**
     * Memilih satu kandidat nomor HP terbaik dari berbagai sumber data (multi-number selection)
     * Tidak pernah membuat nomor palsu atau mengembalikan nomor yang tidak valid.
     */
    public static function selectBestCandidatePhone(...$sources): ?string
    {
        $evaluated = self::getDistinctCandidatePhones(...$sources);

        if (empty($evaluated)) {
            return null;
        }

        return $evaluated[0]['phone'] ?? null;
    }

    /**
     * Mendapatkan daftar nomor kontak kandidat yang valid dan diurutkan dari skor tertinggi
     */
    public static function getDistinctCandidatePhones(...$sources): array
    {
        $rawCandidates = [];

        foreach ($sources as $source) {
            if (empty($source)) {
                continue;
            }

            if (is_array($source)) {
                foreach ($source as $item) {
                    if (is_string($item) || is_numeric($item)) {
                        $parts = preg_split('/[;,\|\/\n\r]+/', (string)$item);
                        foreach ($parts as $p) {
                            $t = trim($p);
                            if (!empty($t) && $t !== '-' && $t !== '0') {
                                $rawCandidates[] = $t;
                            }
                        }
                    }
                }
            } elseif (is_string($source) || is_numeric($source)) {
                $parts = preg_split('/[;,\|\/\n\r]+/', (string)$source);
                foreach ($parts as $p) {
                    $t = trim($p);
                    if (!empty($t) && $t !== '-' && $t !== '0') {
                        $rawCandidates[] = $t;
                    }
                }
            }
        }

        $seen = [];
        $validList = [];

        foreach ($rawCandidates as $raw) {
            $eval = self::evaluateIndonesianMobilePhone($raw);
            if ($eval['valid'] && !empty($eval['phone'])) {
                $phone = $eval['phone'];
                if (!isset($seen[$phone])) {
                    $seen[$phone] = true;
                    $validList[] = $eval;
                }
            }
        }

        // Urutkan kandidat berdasarkan skor tertinggi
        usort($validList, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $validList;
    }

    /**
     * Normalisasi nomor telepon Indonesia (multi-separator support, 08xxx / 628xxx format)
     */
    public static function normalizePhone(?string $phone): ?string
    {
        return self::selectBestCandidatePhone($phone);
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
