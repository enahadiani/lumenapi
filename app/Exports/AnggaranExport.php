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


class AnggaranExport implements FromCollection, WithHeadings
{
    public function __construct($nik_user,$kode_lokasi,$type,$tahun=NULL)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->tahun = $tahun;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
            $res = collect(DB::connection('dbsapkug')->select("select a.kode_akun,a.kode_pp,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9,a.n10,a.n11,a.n12 from anggaran_tmp a where kode_lokasi = '-' 
            "));
            
        }else{
            $res = collect(DB::connection('dbsapkug')->select("select a.kode_akun,a.kode_pp,a.n1,a.n2,a.n3,a.n4,a.n5,a.n6,a.n7,a.n8,a.n9,a.n10,a.n11,a.n12,a.status,a.keterangan,a.nu 
            from anggaran_tmp a where a.kode_lokasi = '$this->kode_lokasi' and a.nik_user='$this->nik_user' and a.tahun='$this->tahun'
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
                    'kode_pp',
                    'n1',
                    'n2',
                    'n3',
                    'n4',
                    'n5',
                    'n6',
                    'n7',
                    'n8',
                    'n9',
                    'n10',
                    'n11',
                    'n12'
                ]
            ];
        }else{
            return [
                [
                    'kode_akun',
                    'kode_pp',
                    'n1',
                    'n2',
                    'n3',
                    'n4',
                    'n5',
                    'n6',
                    'n7',
                    'n8',
                    'n9',
                    'n10',
                    'n11',
                    'n12',
                    'status',
                    'keterangan',
                    $this->tahun
                ]
            ];
        }

    }
}
