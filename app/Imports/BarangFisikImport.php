<?php

namespace App\Imports;

use App\BarangFisik;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class BarangFisikImport implements ToModel, WithStartRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        if($data =  Auth::guard('toko')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            
            return new BarangFisik([
                'kode_lokasi' => $kode_lokasi,
                'nu' => $row[0],
                'kode_barang' => $row[1],
                'jumlah' => $row[2],
                'nik_user' => $nik
            ]);
        }
    }

    // public function headings(): array
    // {

    //     return [
    //         'No',
    //         'Kode Barang',
    //         'Jumlah'
    //     ];

    // }
}
