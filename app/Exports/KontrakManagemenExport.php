<?php

namespace App\Exports;

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


class KontrakManagemenExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
{
    public function __construct($nik_user,$periode,$type)
    {
        $this->nik_user = $nik_user;
        $this->periode = $periode;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
            $res = collect(DB::connection('dbsapkug')->select("select '' as no,'' as indicator,'' as unit,'' as weight,'' as target,'' as real, '' as formula, '' as ach, '' as score, '' as warna "));
        }else{
            
            $res = collect(DB::connection('dbsapkug')->select("select no,indicator,unit,weight,target,real,formula,ach,score,warna,sts_upload,ket_upload,nu 
            from dash_sdm_culture_tmp
            where nik_user ='$this->nik_user' and periode ='$this->periode' 
            order by nu"));
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
            return [
                [
                    'no','indicator','unit','weight','target','real','formula','ach','score','warna'
                ]
            ];
        }else{
            return [
                [
                    'no','indicator','unit','weight','target','real','formula','ach','score','warna',
                    'sts_upload',
                    'ket_upload',
                    'nu'
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
                    // 'fill' => [
                    //     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    //     'rotation' => 0,
                    //     'startColor' => [
                    //         'argb' => 'FFFF00',
                    //     ],
                    //     'endColor' => [
                    //         'argb' => 'FFFF00',
                    //     ],
                    // ],
                ];
                $event->sheet->getStyle('A1:I1')->applyFromArray($styleArray);
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
