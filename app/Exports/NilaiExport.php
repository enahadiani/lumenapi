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


class NilaiExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
{
    public function __construct($nik_user,$kode_lokasi,$kode_pp,$type,$kode_kelas= null,$kode_sem= null,$kode_jenis= null,$kode_matpel= null,$kode_kd = null)
    {
        $this->nik_user = $nik_user;
        $this->kode_lokasi = $kode_lokasi;
        $this->kode_pp = $kode_pp;
        $this->kode_kelas = $kode_kelas;
        $this->kode_matpel = $kode_matpel;
        $this->kode_sem = $kode_sem;
        $this->kode_jenis = $kode_jenis;
        $this->kode_kd = $kode_kd;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type == 'template'){
            if($this->kode_kelas != null){
                $res = DB::connection('sqlsrvtarbak')->table('sis_siswa')
                        ->select('sis_siswa.nis','sis_siswa.nama')
                        ->leftJoin('sis_nilai_tmp', function($join)
                         {
                             $join->on('sis_siswa.nis', '=', 'sis_nilai_tmp.nis');
                             $join->on('sis_siswa.kode_lokasi','=','sis_nilai_tmp.kode_lokasi');
                             $join->on('sis_siswa.kode_pp','=','sis_nilai_tmp.kode_pp');
                         })
                        ->where('sis_siswa.kode_lokasi',$this->kode_lokasi)
                        ->where('sis_siswa.kode_kelas',$this->kode_kelas)
                        ->where('sis_siswa.kode_pp',$this->kode_pp)
                        ->get();
            }else{
                $res = DB::connection('sqlsrvtarbak')->table('sis_nilai_tmp')
                        ->select('sis_nilai_tmp.nis','sis_siswa.nama')
                        ->leftJoin('sis_siswa', function($join)
                         {
                             $join->on('sis_nilai_tmp.nis', '=', 'sis_siswa.nis');
                             $join->on('sis_nilai_tmp.kode_lokasi','=','sis_siswa.kode_lokasi');
                             $join->on('sis_nilai_tmp.kode_pp','=','sis_siswa.kode_pp');
                         })
                        ->where('sis_nilai_tmp.kode_lokasi','-')
                        ->where('sis_nilai_tmp.nik_user',$this->nik_user)
                        ->get();
            }
        }else{
            $res = DB::connection('sqlsrvtarbak')->table('sis_nilai_tmp')
                        ->select('sis_nilai_tmp.nis','sis_siswa.nama','sis_nilai_tmp.nilai','sis_nilai_tmp.status','sis_nilai_tmp.keterangan','sis_nilai_tmp.nu')
                        ->leftJoin('sis_siswa', function($join)
                         {
                             $join->on('sis_nilai_tmp.nis', '=', 'sis_siswa.nis');
                             $join->on('sis_nilai_tmp.kode_lokasi','=','sis_siswa.kode_lokasi');
                             $join->on('sis_nilai_tmp.kode_pp','=','sis_siswa.kode_pp');
                         })
                        ->where('sis_nilai_tmp.kode_lokasi',$this->kode_lokasi)
                        ->where('sis_nilai_tmp.nik_user',$this->nik_user)
                        ->where('sis_nilai_tmp.kode_pp',$this->kode_pp)
                        ->get();
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
            // return [
            //     'nis',
            //     'nama',
            //     'nilai'
            // ];
            return [
                ['Mata Pelajaran', 'Kelas','Jenis Penilaian','Semester','KD'],
                [$this->kode_matpel, $this->kode_kelas,$this->kode_jenis,$this->kode_sem,$this->kode_kd],
                ['','','','',''],
                [
                    'nis',
                    'nama',
                    'nilai'
                ]
            ];
        }else{
            return [
                ['Mata Pelajaran', 'Kelas','Jenis Penilaian','Semester','KD'],
                [$this->kode_matpel, $this->kode_kelas,$this->kode_jenis,$this->kode_sem,$this->kode_kd],
                ['','','','',''],
                [
                    'nis',
                    'nama',
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
