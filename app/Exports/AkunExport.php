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


class AkunExport implements FromCollection,WithHeadings
{
    public function __construct($nik_user,$kode_lokasi,$type)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
            $res = collect(DB::connection('tokoaws')->select("select kode_akun,nama,modul,jenis,kode_curr,block,status_gar,normal from xmasakun  where kode_lokasi = '-' 
            "));
            
        }else{
            $res = collect(DB::connection('tokoaws')->select("select kode_akun, nama,modul,jenis,kode_curr,block,status_gar,normal,sts_upload,ket_upload,nu 
            from xmasakun where kode_lokasi = '$this->kode_lokasi' and nik_user='$this->nik_user'
            "));
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
           
            return [
                [
                    'kode_akun','nama','modul','jenis','kode_curr','block','status_gar','normal'
                ]
            ];
        }else{
            return [
                [
                    'kode_akun','nama','modul','jenis','kode_curr','block','status_gar','normal',
                    'sts_upload',
                    'ket_upload',
                    'nu'
                ]
            ];
        }

    }
}
