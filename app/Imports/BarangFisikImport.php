<?php

namespace App\Imports;

use App\BarangFisik;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;
// use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BarangFisikImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if($data =  Auth::guard('toko')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            return new BarangFisik([
                'kode_lokasi' => $kode_lokasi,
                'nu' => $row['no'],
                'kode_barang' => $row['kode'],
                'jumlah' => $row['jumlah'],
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
