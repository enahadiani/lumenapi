<?php

namespace App\Exports;

use App\User;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

class UsersExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     // if($data =  Auth::guard('user')->user()){
    //     //     $nik= $data->nik;
    //     //     $kode_lokasi= $data->kode_lokasi;

    //     //     $user = DB::connection('sqlsrv')->select("select a.kode_klp_menu, a.nik, a.nama, a.status_admin, a.klp_akses, a.kode_lokasi,b.nama as nmlok, c.kode_pp,d.nama as nama_pp,
	// 	// 	b.kode_lokkonsol,d.kode_bidang, c.foto,isnull(e.form,'-') as path_view,b.logo,c.no_telp,c.jabatan
    //     //     from hakakses a 
    //     //     inner join lokasi b on b.kode_lokasi = a.kode_lokasi 
    //     //     left join karyawan c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi 
    //     //     left join pp d on c.kode_pp=d.kode_pp and c.kode_lokasi=d.kode_lokasi 
    //     //     left join m_form e on a.path_view=e.kode_form 
    //     //     where a.nik= '$nik' 
    //     //     ");
    //     //     $user = json_decode(json_encode($user),true);
    //     //     return $user;
    //     // }
    //     return new Collection([
    //         [1, 2, 3],
    //         [4, 5, 6]
    //     ]);
    // }

    public function collection()
    {
        return User::all();
    }

    public function headings(): array

    {

        return [

            'Kode Klp Menu',
            'NIK',
            'Nama',
            'Status Admin',
            'Kode Lokasi',
            'Klp Akses',
            'Menu Mobile',
            'Path View',
            'Kode Menu Lab',
        ];

    }
}
