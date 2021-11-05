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
            ->select("select '' as nik, '' as nama, '' as no_ktp, '' as jk, '' as kode_agama, '' as no_telp, 
            '' as no_hp, '' as tempat, '' as tgl_lahir, '' as alamat,  '' as provinsi, '' as kota,
            '' as kecamatan, '' as kelurahan, '' as kode_pos, '' as t_badan, '' as b_badan, '' as gol_darah, '' as no_kk, 
            '' as status_nikah, '' as tgl_nikah, '' as kode_gol, '' as kode_sdm, '' as kode_unit, '' as kode_loker, 
            '' as tgl_masuk, '' as npwp, '' as no_bpjs, '' as no_bpjs_kerja, '' as kode_profesi, 
            '' as bank, '' as cabang, '' as no_rek, '' as nama_rek,  '' as client, 
            '' as fungsi, '' as skill, '' as no_kontrak, '' as tgl_kontrak, '' as tgl_kontrak_akhir, 
            '' as area, '' as kota_area, '' as fm, '' as bm, '' as loker_client, 
            '' as jabatan_client, '' as atasan_langsung, '' as atasan_t_langsung, '' as kode_jab, '' as kode_strata"));
        }else{
            
            $res = collect(DB::connection('sqlsrvtarbak')
            ->select("select nik, nama, no_ktp, jk, kode_agama, no_telp, no_hp, tempat, convert(varchar(10), tgl_lahir, 101) as tgl_lahir, 
            alamat, provinsi, kota, kecamatan, kelurahan, kode_pos, t_badan, b_badan, gol_darah, no_kk, status_nikah, convert(varchar(10), tgl_nikah, 101) as tgl_nikah,
            kode_gol, kode_sdm, kode_unit, kode_loker, convert(varchar(10), tgl_masuk, 101) as tgl_masuk, npwp, no_bpjs, no_bpjs_kerja, kode_profesi, bank, cabang, 
            no_rek, nama_rek, client, fungsi, skill, no_kontrak, convert(varchar(10), tgl_kontrak, 101) as tgl_kontrak, convert(varchar(10), tgl_kontrak_akhir, 101) as tgl_kontrak_akhir, 
            area, kota_area, fm, bm, loker_client, jabatan_client, atasan_langsung, atasan_t_langsung, kode_jab, kode_strata
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
                    'NIK',
                    'No KTP',
                    'Nama',
                    'Jenis Kelamin',
                    'Agama',
                    'No. Telepon',
                    'No. HP',
                    'Tempat Lahir',
                    'Tgl Lahir',
                    'Alamat',
                    'Provinsi',
                    'Kota',
                    'Kecamatan',
                    'Kelurahan',
                    'Kode POS',
                    'Tinggi Badan',
                    'Berat Badan',
                    'Gol. Darah',
                    'No. KK',
                    'Status Menikah',
                    'Tgl Nikah',
                    'Golongan Karyawan',
                    'Status Karyawan',
                    'Unit Karyawan',
                    'Lokasi Kerja',
                    'Tgl. Masuk',
                    'NPWP',
                    'No. BPJS Kesehatan',
                    'No. BPJS Ketenagakerjaan',
                    'Profesi Karyawan',
                    'Bank',
                    'Cabang',
                    'No. Rekening',
                    'Nama Rekening',
                    'Client',
                    'Fungsi',
                    'Skill',
                    'No. Kontrak',
                    'Tgl. Kontrak',
                    'Tgl. Kontrak Akhir',
                    'Area',
                    'Kota Area',
                    'FM',
                    'BM',
                    'Lokasi Kerja Client',
                    'Jabatan Client',
                    'Atasan Langsung',
                    'Atasan Tidak Langsung',
                    'Pendidikan Terakhir',
                    'Jabatan',
                ],
                [
                    '',
                    '',
                    '',
                    'Pilihan: L/P',
                    'Pilihan : Islam, Kristen, Katolik, Buddha, Hindu, Konghucu, Lainnya.',
                    '',
                    '',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Satuan cm',
                    'Satuan kg',
                    '',
                    '',
                    'Pilihan: Pilihan : Lajang, Cerai, Menikah',
                    'Format tanggal dd/mm/yyyy',
                    'Golongan karyawan, jika tidak ada isi tanda strip (-)',
                    'Pilihan : Kontak/Tetap',
                    'Unit tempat pekerja jika tidak ada isi tanda strip (-)',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]
            ];
        }else{
            return [
                [
                    'NIK',
                    'Nama',
                    'No KTP',
                    'Jenis Kelamin',
                    'Agama',
                    'No. Telepon',
                    'No. HP',
                    'Tempat Lahir',
                    'Tgl Lahir',
                    'Alamat',
                    'Provinsi',
                    'Kota',
                    'Kecamatan',
                    'Kelurahan',
                    'Kode POS',
                    'Tinggi Badan',
                    'Berat Badan',
                    'Gol. Darah',
                    'No. KK',
                    'Status Menikah',
                    'Tgl Nikah',
                    'Golongan Karyawan',
                    'Status Karyawan',
                    'Unit Karyawan',
                    'Lokasi Kerja',
                    'Tgl. Masuk',
                    'NPWP',
                    'No. BPJS Kesehatan',
                    'No. BPJS Ketenagakerjaan',
                    'Profesi Karyawan',
                    'Bank',
                    'Cabang',
                    'No. Rekening',
                    'Nama Rekening',
                    'Client',
                    'Fungsi',
                    'Skill',
                    'No. Kontrak',
                    'Tgl. Kontrak',
                    'Tgl. Kontrak Akhir',
                    'Area',
                    'Kota Area',
                    'FM',
                    'BM',
                    'Lokasi Kerja Client',
                    'Jabatan Client',
                    'Atasan Langsung',
                    'Atasan Tidak Langsung',
                    'Pendidikan Terakhir',
                    'Jabatan',
                    'Status Upload',
                    'Keterangan Upload',
                    'Nomor Urut'
                ],
                [
                    '',
                    '',
                    '',
                    'Pilihan: L/P',
                    'Pilihan : Islam, Kristen, Katolik, Buddha, Hindu, Konghucu, Lainnya.',
                    '',
                    '',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Satuan cm',
                    'Satuan kg',
                    '',
                    '',
                    'Pilihan: Pilihan : Lajang, Cerai, Menikah',
                    'Format tanggal dd/mm/yyyy',
                    'Golongan karyawan, jika tidak ada isi tanda strip (-)',
                    'Pilihan : Kontak/Tetap',
                    'Unit tempat pekerja jika tidak ada isi tanda strip (-)',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Format tanggal dd/mm/yyyy',
                    'Format tanggal dd/mm/yyyy',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
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
                $event->sheet->getStyle('A1:AW2')->applyFromArray($styleArray);
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
