<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ArusKasImport implements ToModel, WithStartRow, WithCalculatedFormulas
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