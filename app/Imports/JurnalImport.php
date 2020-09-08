<?php

namespace App\Imports;

use App\JurnalTmp;
// use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JurnalImport implements ToCollection, WithStartRow
{
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function startRow(): int
    {
        return 2;
    }

    // public function cekValidBaris($isi,$kode_lokasi){
    //     $ket = "";
    //     $auth = DB::connection($this->sql)->select("
    //         select kode_akun as kode, 'Kode Akun tidak valid' as error_msg from masakun where kode_lokasi='$kode_lokasi' and kode_akun not in (select kode_akun from masakun where kode_lokasi='$kode_lokasi')
    //         union all
    //         select kode_pp as kode, 'Kode PP tidak valid' as error_msg from agg_abt_tmp where kode_lokasi='$kode_lokasi' and kode_pp not in (select kode_pp from pp where kode_lokasi='$kode_lokasi')
    //         union all
    //         select kode_drk as kode, 'Kode Drk tidak valid' as error_msg from agg_abt_tmp where kode_lokasi='$kode_lokasi' and kode_drk not in (select kode_drk from drk where kode_lokasi='$kode_lokasi' and tahun='$tahun')
    //     ");
    //     $auth = json_decode(json_encode($auth),true);
    //     if(count($auth) > 0){
    //         for($i=0;$i<$auth;$i++){

    //         }
    //         return "";
    //     }else{
    //         return "";
    //     }
    // }

    public function collection(Collection $rows)
    {
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            foreach ($rows as $row) 
            {
                if($row[0] == ""){
                    //
                }else{
                    $ket = "";

                    JurnalTmp::create([
                        'kode_akun' => $row[0],
                        'dc' => $row[2],
                        'keterangan' => $row[3],
                        'nilai' => $row[4],
                        'kode_pp' => $row[5],
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $nik,
                        'tgl_input' => date('Y-m-d H:i:s'),
                        'status' => 1,
                        'ket_status' => '-'
                    ]);
                }
            }
        }
    }
    

    // public function model(array $row)
    // {
    //     return new JurnalTmp([
    //         'kode_akun' => $row[0],
    //         'dc' => $row[2],
    //         'keterangan' => $row[3],
    //         'nilai' => $row[4],
    //         'kode_pp' => $row[5],
    //         'kode_lokasi' => "x",
    //         'nik_user' => "x",
    //         'tgl_input' => NULL,
    //         'status' => NULL,
    //         'ket_status' => NULL
    //     ]);
    // }
}