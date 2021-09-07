<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SDMKaryawanImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 3;
    }

    public function model(Array $row)
    {
        return $row;
    }
   
}