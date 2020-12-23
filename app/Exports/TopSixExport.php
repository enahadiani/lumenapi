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


class TopSixExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
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
            $res = collect(DB::connection('dbsapkug')->select("select '' as no,'' as jenis,'' as nama,'' as penderita_before,'' as penderita_now,'' as biaya_before,'' as biaya_now,'' as yoy_jiwa_before,'' as yoy_jiwa_now,'' as yoy_biaya_before,'' as yoy_biaya_now,'' as rata2_before, '' as rata2_now "));
        }else{
            
            $res = collect(DB::connection('dbsapkug')->select("select no,jenis,nama,
            penderita_before,penderita_now,biaya_before,biaya_now,yoy_jiwa_before,yoy_jiwa_now,yoy_biaya_before,yoy_biaya_now,rata2_before,rata2_now,sts_upload,ket_upload,nu 
            from dash_top_icd_tmp
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
                    'no','jenis','nama','penderita_before','penderita_now','biaya_before','biaya_now','yoy_jiwa_before','yoy_jiwa_now','yoy_biaya_before','yoy_biaya_now','rata2_before','rata2_now'
                ]
            ];
        }else{
            return [
                [
                    'no','jenis','nama','penderita_before','penderita_now','biaya_before','biaya_now','yoy_jiwa_before','yoy_jiwa_now','yoy_biaya_before','yoy_biaya_now','rata2_before','rata2_now',
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
