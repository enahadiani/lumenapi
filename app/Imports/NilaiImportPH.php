<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class NilaiImportPH implements ToModel, WithStartRow
{
    public $sql = 'sqlsrvtarbak';
    public $guard = 'tarbak';
    public $ket = '';

    public function startRow(): int
    {
        return 5;
    }

    public function model(Array $row)
    {
        return $row;
    }
   
}