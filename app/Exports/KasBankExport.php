<?php

namespace App\Exports;

use App\KasBankTmp;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class KasBankExport implements FromCollection, WithHeadings
{
    public function __construct($nik_user,$kode_lokasi)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
    }

    public function collection()
    {
        return KasBankTmp::select('kode_akun','dc','keterangan','nilai','kode_pp','status','ket_status','nu')->where('nik_user', $this->nik_user)->where('kode_lokasi', $this->kode_lokasi)->orderBy('nu')->get();
    }

    public function headings(): array
    {
        return [
            'kode_akun',
            'dc',
            'keterangan',
            'nilai',
            'kode_pp',
            'status',
            'keterangan_status',
            'no_urut'
        ];

    }

    public function columnFormats(): array
    {
        return [
            'A' => DataType::TYPE_STRING,
            'E' => DataType::TYPE_STRING,
        ];
    }
}
