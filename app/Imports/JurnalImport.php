<?php

namespace App\Imports;

use App\JurnalTmp;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class JurnalImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        return new JurnalTmp([
            'kode_akun' => $row[0],
            'dc' => $row[2],
            'keterangan' => $row[3],
            'nilai' => $row[4],
            'kode_pp' => $row[5],
            'kode_lokasi' => "x",
            'nik_user' => "x",
            'tgl_input' => NULL,
            'status' => NULL,
            'ket_status' => NULL
        ]);
    }
}