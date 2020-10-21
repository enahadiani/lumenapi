<?php

namespace App\Exports;

use App\NilaiTmpPH;
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


class NilaiExportPH implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
{
    public function __construct($nik_user,$kode_lokasi,$kode_pp,$type,$kode_kelas= null,$kode_sem= null,$kode_matpel= null,$kode_kd = null)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->kode_pp = $kode_pp;
        $this->kode_kelas = $kode_kelas;
        $this->kode_matpel = $kode_matpel;
        $this->kode_sem = $kode_sem;
        $this->kode_kd = $kode_kd;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
           
            $res = DB::connection('sqlsrvtarbak')->select("select a.nis,a.nama 
                    from sis_siswa a 
                    where a.kode_kelas ='$this->kode_kelas' and a.kode_lokasi='$this->kode_lokasi' and a.kode_pp ='$this->kode_pp'
                    order by a.nama
            ");
            $res = collect($res);

            
        }else{
            $res = DB::connection('sqlsrvtarbak')->select("select a.nis,b.nama,a.nilai,a.kode_jenis,a.status,a.keterangan,a.nu
            from sis_nilai_tmp2 a 
            left join sis_siswa b on a.nis=b.nis and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.nik_user ='$this->nik_user' and a.kode_lokasi='$this->kode_lokasi' and a.kode_pp ='$this->kode_pp'
            order by a.nama
            ");
            $res = collect($res);
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
            return [
                ['Mata Pelajaran', 'Kelas','Semester','KD'],
                [$this->kode_matpel, $this->kode_kelas,$this->kode_sem,$this->kode_kd],
                ['','','','',''],
                [
                    'nis',
                    'nama',
                    'kode_jenis',
                    'nilai'
                ]
            ];
        }else{
            return [
                ['Mata Pelajaran', 'Kelas','Semester','KD'],
                [$this->kode_matpel, $this->kode_kelas,$this->kode_sem,$this->kode_kd],
                ['','','','',''],
                [
                    'nis',
                    'nama', 
                    'kode_jenis',
                    'nilai',
                    'status',
                    'keterangan',
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
                $event->sheet->getStyle('A1:E2')->applyFromArray($styleArray);
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
