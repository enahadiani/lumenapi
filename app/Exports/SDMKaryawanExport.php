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


class SDMKaryawanExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
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
            $res = collect(DB::connection('sqlsrvtarbak')
            ->select("select '' as nik, '' as nama, '' as status_nikah, '' as tempat, '' as tgl_lahir, '' as jk, 
            '' as t_badan, '' as b_badan, '' as gol_darah, '' as kode_agama,  '' as alamat, '' as kelurahan,
            '' as kecamatan, '' as kota, '' as provinsi, '' as kode_pos, '' as no_ktp, '' as no_hp, '' as email, 
            '' as kode_strata, '' as client, '' as area, '' as kota_area, '' as fm, '' as bm, '' as loker, 
            '' as jabatan, '' as skill, '' as fungsi, '' as no_kontrak, '' as tgl_kontrak, 
            '' as tgl_kontrak_akhir, '' as bank, '' as no_rek, '' as nama_rek,  '' as gaji_pokok, 
            '' as tunj_jabatan, '' as tunj_penampilan, '' as tunj_gondola, '' as tunj_taman, '' as tunj_kompetensi, 
            '' as tunj_skill, '' as tunj_patroli, '' as tunj_lembur, '' as tunj_masakerja, '' as no_bpsj, 
            '' as no_bpsj_kerja"));
        }else{
            
            $res = collect(DB::connection('sqlsrvtarbak')
            ->select("select nik, nama, status_nikah, tempat, tgl_lahir, jk, t_badan, b_badan, gol_darah, kode_agama, 
            alamat, kelurahan, kecamatan, kota, provinsi, kode_pos, no_ktp, no_hp, email, kode_strata, client, area, 
            kota_area, fm, bm, loker, jabatan, skill, fungsi, no_kontrak, tgl_kontrak, tgl_kontrak_akhir, bank, 
            no_rek, nama_rek, gaji_pokok, tunj_jabatan, tunj_penampilan, tunj_gondola, tunj_taman, tunj_kompetensi, 
            tunj_skill, tunj_patroli, tunj_lembur, tunj_masakerja, no_bpjs, no_bpjs_kerja 
            from hr_karyawan_tmp, sts_upload, ket_upload, nu
            where nik_user ='$this->nik_user' 
            order by nu"));
                        
        }
        return $res;
    }

    public function headings(): array
    {
        if($this->type == 'template'){
            return [
                [
                    'nik',
                    'nama',
                    'status_nikah',
                    'tempat',
                    'tgl_lahir',
                    'jk',
                    't_badan',
                    'b_badan',
                    'gol_darah',
                    'kode_agama',
                    'alamat',
                    'kelurahan',
                    'kecamatan',
                    'kota',
                    'provinsi',
                    'kode_pos',
                    'no_ktp',
                    'no_hp',
                    'email',
                    'kode_strata',
                    'client',
                    'area',
                    'kota_area',
                    'fm',
                    'bm',
                    'kode_loker',
                    'kode_jab',
                    'skill',
                    'fungsi',
                    'no_kontrak',
                    'tgl_kontrak',
                    'tgl_kontrak_akhir',
                    'bank',
                    'no_rek',
                    'nama_rek',
                    'gaji_pokok',
                    'tunj_jabatan',
                    'tunj_penampilan',
                    'tunj_gondola',
                    'tunj_taman',
                    'tunj_kompetensi',
                    'tunj_skill',
                    'tunj_patroli',
                    'tunj_lembur',
                    'tunj_masakerja',
                    'no_bpjs',
                    'no_bpjs_kerja',
                ]
            ];
        }else{
            return [
                [
                    'nik',
                    'nama',
                    'status_nikah',
                    'tempat',
                    'tgl_lahir',
                    'jk',
                    't_badan',
                    'b_badan',
                    'gol_darah',
                    'kode_agama',
                    'alamat',
                    'kelurahan',
                    'kecamatan',
                    'kota',
                    'provinsi',
                    'kode_pos',
                    'no_ktp',
                    'no_hp',
                    'email',
                    'kode_strata',
                    'client',
                    'area',
                    'kota_area',
                    'fm',
                    'bm',
                    'loker',
                    'jabatan',
                    'skill',
                    'fungsi',
                    'no_kontrak',
                    'tgl_kontrak',
                    'tgl_kontrak_akhir',
                    'bank',
                    'no_rek',
                    'nama_rek',
                    'gaji_pokok',
                    'tunj_jabatan',
                    'tunj_penampilan',
                    'tunj_gondola',
                    'tunj_taman',
                    'tunj_kompetensi',
                    'tunj_skill',
                    'tunj_patroli',
                    'tunj_lembur',
                    'tunj_masakerja',
                    'no_bpjs',
                    'no_bpjs_kerja',
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
                $event->sheet->getStyle('A1:AU1')->applyFromArray($styleArray);
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
