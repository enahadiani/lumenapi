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


class SiswaExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
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
            ->select("select '' as nis, '' as flag_aktif, '' as kode_kelas, '' as kode_akt, '' as nama, '' as tmp_lahir, 
            '' as tgl_lahir, '' as jk, '' as agama, '' as hp_siswa, '' as email, '' as alamat_siswa, '' as nama_wali, 
            '' as alamat_wali, '' as kerja_wali, '' as hp_wali, '' as email_wali, '' as gol_darah, '' as id_bank, '' as nis2,
            '' as kode_pp"));
        }else{
            
            $res = collect(DB::connection('sqlsrvtarbak')
            ->select("select nis, flag_aktif, kode_kelas, kode_akt as angkatan, nama, tmp_lahir as tempat_lahir, tgl_lahir, 
            jk as jenis_kelamin, agama, hp_siswa, email, alamat_siswa, nama_wali, alamat_wali, kerja_wali, hp_wali, email_wali,
            gol_darah, id_bank, nis2, kode_pp, sts_upload, ket_upload, nu 
            from sis_siswa_tmp
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
                    'ID Keuangan',
                    'Status Siswa',
                    'Kelas',
                    'Angkatan',
                    'Nama',
                    'Tempat Lahir',
                    'Tanggal Lahir',
                    'Jenis Kelamin',
                    'Agama',
                    'No. HP Siswa',
                    'Email Siswa',
                    'Alamat Siswa',
                    'Nama Wali',
                    'Alamat Wali',
                    'Pekerjaan Wali',
                    'No. HP Wali',
                    'Email Wali',
                    'Gol. Darah Siswa',
                    'ID Bank',
                    'ID Sekolah',
                    'Kode PP'
                ]
            ];
        }else{
            return [
                [
                    'ID Keuangan',
                    'Status Siswa',
                    'Kelas',
                    'Angkatan',
                    'Nama',
                    'Tempat Lahir',
                    'Tanggal Lahir',
                    'Jenis Kelamin',
                    'Agama',
                    'No. HP Siswa',
                    'Email Siswa',
                    'Alamat Siswa',
                    'Nama Wali',
                    'Alamat Wali',
                    'Pekerjaan Wali',
                    'No. HP Wali',
                    'Email Wali',
                    'Gol. Darah Siswa',
                    'ID Bank',
                    'ID Sekolah',
                    'Kode PP',
                    'Status Upload',
                    'Keterangan Upload',
                    'Nomor Urut'
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
                $event->sheet->getStyle('A1:T1')->applyFromArray($styleArray);
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
