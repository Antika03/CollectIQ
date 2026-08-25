<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PritiImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Collection' => new ReportPrqImport(),
        ];
    }
}