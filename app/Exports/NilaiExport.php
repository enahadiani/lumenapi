<?php

namespace App\Exports;

use App\NilaiTmp;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class NilaiExport implements FromCollection, WithHeadings
{
    public function __construct($nik_user,$kode_lokasi,$kode_pp,$no_bukti)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->kode_pp = $kode_pp;
        $this->no_bukti = $no_bukti;
    }

    public function collection()
    {
        return NilaiTmp::select('nis','nilai','status','keterangan','nu')->where('nik_user', $this->nik_user)->where('kode_lokasi', $this->kode_lokasi)->where('kode_pp', $this->kode_pp)->where('no_bukti', $this->no_bukti)->orderBy('nu')->get();
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nilai',
            'Status',
            'Keterangan Status',
            'No Urut'
        ];

    }

    public function columnFormats(): array
    {
        return [
            'A' => DataType::TYPE_STRING
        ];
    }
}
