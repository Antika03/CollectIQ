<?php

namespace App\Services;

use ZipArchive;
use XMLReader;
use Illuminate\Support\Facades\Log;

class XlsxSheetExtractor
{
    /**
     * Ekstrak sheet tertentu dari XLSX menjadi file CSV standar dengan normalisasi data & angka
     */
    public static function extractSheetToCsv(string $xlsxPath, string $targetSheetName, string $outputCsvPath): array
    {
        $startTime = microtime(true);
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== TRUE) {
            throw new \Exception("Gagal membuka file XLSX: {$xlsxPath}");
        }

        // 1. Dapatkan mapping sheet name -> xml file path dari workbook.xml
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        $rels = [];
        if ($relsXml) {
            $xmlRels = simplexml_load_string($relsXml);
            foreach ($xmlRels->Relationship as $rel) {
                $rels[(string)$rel['Id']] = (string)$rel['Target'];
            }
        }

        $targetXmlPath = null;
        if ($workbookXml) {
            $xmlWb = simplexml_load_string($workbookXml);
            foreach ($xmlWb->sheets->sheet as $sheet) {
                $name = (string)$sheet['name'];
                if (strcasecmp(trim($name), trim($targetSheetName)) === 0) {
                    $rId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    $target = $rels[$rId] ?? '';
                    $targetXmlPath = 'xl/' . ltrim($target, '/');
                    break;
                }
            }
        }

        if (!$targetXmlPath || !$zip->locateName($targetXmlPath)) {
            $zip->close();
            throw new \Exception("Sheet '{$targetSheetName}' tidak ditemukan di dalam file XLSX.");
        }

        // 2. Load Shared Strings jika ada
        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent) {
            $xmlSs = simplexml_load_string($ssContent);
            foreach ($xmlSs->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
            unset($ssContent, $xmlSs);
        }

        // 3. Extract sheet XML ke temporary file untuk XMLReader streaming
        $tempSheetFile = storage_path('app/temp_' . md5($targetSheetName) . '.xml');
        file_put_contents($tempSheetFile, $zip->getFromName($targetXmlPath));
        $zip->close();

        // 4. Buka output CSV
        $outFp = fopen($outputCsvPath, 'w');
        if (!$outFp) {
            @unlink($tempSheetFile);
            throw new \Exception("Gagal membuat file output CSV: {$outputCsvPath}");
        }

        // Tulis BOM UTF-8
        fprintf($outFp, chr(0xEF).chr(0xBB).chr(0xBF));

        $xmlReader = new XMLReader();
        $xmlReader->open($tempSheetFile);

        $rowCount = 0;
        $maxCols = 0;

        while ($xmlReader->read()) {
            if ($xmlReader->nodeType === XMLReader::ELEMENT && $xmlReader->name === 'row') {
                $rowXml = simplexml_load_string($xmlReader->readOuterXml());
                $rowCount++;

                $rowValues = [];
                $maxColIdx = 0;

                foreach ($rowXml->c as $c) {
                    $cellRef = (string)$c['r']; // e.g. "A1", "D12"
                    preg_match('/([A-Z]+)(\d+)/', $cellRef, $m);
                    $colLetters = $m[1] ?? 'A';

                    // Convert colLetters (A, B, ..., Z, AA, AB) to 0-based integer
                    $colIdx = 0;
                    $len = strlen($colLetters);
                    for ($i = 0; $i < $len; $i++) {
                        $colIdx = $colIdx * 26 + (ord($colLetters[$i]) - 64);
                    }
                    $colIdx--; // 0-based

                    $val = '';
                    $type = (string)$c['t'];
                    if ($type === 's') {
                        $idx = (int)$c->v;
                        $val = $sharedStrings[$idx] ?? '';
                    } elseif (isset($c->v)) {
                        $rawVal = (string)$c->v;
                        // Handle scientific notation e.g. 1.31232105078E11 or 2.31830731E9
                        if (is_numeric($rawVal) && (stripos($rawVal, 'e+') !== false || stripos($rawVal, 'e') !== false)) {
                            // If it's a large integer representation, format without scientific notation
                            $val = sprintf('%.0f', (float)$rawVal);
                        } else {
                            $val = $rawVal;
                        }
                    }

                    $rowValues[$colIdx] = trim($val);
                    if ($colIdx > $maxColIdx) {
                        $maxColIdx = $colIdx;
                    }
                }

                if ($maxColIdx > $maxCols) {
                    $maxCols = $maxColIdx;
                }

                // Fill missing columns with empty string
                $filledRow = [];
                $totalCols = max($maxCols, $maxColIdx);
                for ($ci = 0; $ci <= $totalCols; $ci++) {
                    $filledRow[$ci] = $rowValues[$ci] ?? '';
                }

                fputcsv($outFp, $filledRow);
            }
        }

        $xmlReader->close();
        fclose($outFp);
        @unlink($tempSheetFile);

        $duration = round(microtime(true) - $startTime, 2);

        return [
            'sheet_name'   => $targetSheetName,
            'total_rows'   => $rowCount,
            'total_cols'   => $maxCols + 1,
            'duration_sec' => $duration,
            'csv_path'     => $outputCsvPath,
        ];
    }
}
