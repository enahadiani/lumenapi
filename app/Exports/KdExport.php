<?php

namespace App\Exports;

use App\NilaiTmp;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;


class KdExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
{
    public function __construct($nik_user,$kode_lokasi,$kode_pp,$type,$kode_tingkat= null,$kode_sem= null,$kode_matpel= null,$kode_ta= null)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->kode_pp = $kode_pp;
        $this->kode_matpel = $kode_matpel;
        $this->kode_ta = $kode_ta;
        $this->kode_tingkat = $kode_tingkat;
        $this->kode_sem = $kode_sem;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
           
            $res = collect(DB::connection('sqlsrvtarbak')->select("select a.kode_kd,a.nama,a.kkm from sis_kd_tmp a where a.kode_lokasi='-' "));
            
        }else{
           
            $res = collect(DB::connection('sqlsrvtarbak')->select("select a.kode_kd,a.nama,a.kkm,a.status,a.keterangan,a.nu 
            from sis_kd_tmp a
            where a.nik_user ='$this->nik_user' and a.kode_lokasi ='$this->kode_lokasi' and a.kode_pp ='$this->kode_pp' and a.kode_sem ='$this->kode_sem' and a.kode_ta ='$this->kode_ta' and a.kode_matpel ='$this->kode_matpel' and a.kode_tingkat ='$this->kode_tingkat' order by a.kode_kd"));
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
            return [
                ['Mata Pelajaran', 'Tingkat','Tahun Ajaran','Semester'],
                [$this->kode_matpel, $this->kode_tingkat,$this->kode_ta,$this->kode_sem],
                ['','','',''],
                [
                    'kode_kd',
                    'nama',
                    'kkm'
                ]
            ];
        }else{
            return [
                ['Mata Pelajaran', 'Tingkat','Tahun Ajaran','Semester'],
                [$this->kode_matpel, $this->kode_tingkat,$this->kode_ta,$this->kode_sem],
                ['','','',''],
                [
                    'kode_kd',
                    'nama',
                    'kkm'
                ]
            ];
        }

    }

    public function registerEvents(): array
    {
        
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $styleArray = [
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'rotation' => 0,
                        'startColor' => [
                            'argb' => 'FFFF00',
                        ],
                        'endColor' => [
                            'argb' => 'FFFF00',
                        ],
                    ],
                ];
                $event->sheet->getStyle('A1:D2')->applyFromArray($styleArray);
            },
        ];
        
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT
        ];
    }
}
