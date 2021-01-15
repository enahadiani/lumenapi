<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class SawalExport implements FromCollection, WithHeadings
{
    public function __construct($nik_user,$kode_lokasi,$type,$periode=NULL)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->periode = $periode;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
            $res = collect(DB::connection('tokoaws')->select("select a.kode_akun,a.debet,a.kredit from xsawal2 a where kode_lokasi = '-' 
            "));
            
        }else{
            $res = collect(DB::connection('tokoaws')->select("select a.kode_akun,a.debet,a.kredit,a.sts_upload,a.ket_upload,a.nu 
            from xsawal2 a where a.kode_lokasi = '$this->kode_lokasi' and a.nik_user='$this->nik_user' and a.periode='$this->periode'
            "));
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
           
            return [
                [
                    'kode_akun',
                    'debet',
                    'kredit'
                ]
            ];
        }else{
            return [
                [
                    'kode_akun',
                    'debet',
                    'kredit',
                    'sts_upload',
                    'ket_upload',
                    'nu'
                ]
            ];
        }

    }
}
