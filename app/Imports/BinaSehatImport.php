<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class BinaSehatImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    public function model(Array $row)
    {
        return $row;
    }
   
}