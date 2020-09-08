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
    public $ket = "";

    public function  __construct($nik_user)
    {
        $this->nik_user= $nik_user;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function validateJurnal($kode_akun,$kode_pp,$dc,$ket,$nilai,$kode_lokasi){

        $auth = DB::connection($this->sql)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $this->ket .= "";
        }else{
            $this->ket .= "Kode Akun $kode_akun tidak valid. ";
        }

        $auth2 = DB::connection($this->sql)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi'
        ");
        $auth2 = json_decode(json_encode($auth2),true);
        if(count($auth2) > 0){
            $this->ket .= "";
        }else{
            $this->ket .= "Kode PP $kode_pp tidak valid. ";
        }

        if(floatval($nilai) > 0){
            $this->ket .= "";
        }else{
            $this->ket .= "Nilai tidak valid. ";
        }

        if($ket != ""){
            $this->ket .= "";
        }else{
            $this->ket .= "Keterangan tidak valid. ";
        }

        if($dc == "D" || $dc == "C"){
            $this->ket .= "";
        }else{
            $this->ket .= "DC $dc tidak valid ";
        }

    }

    public function collection(Collection $rows)
    {
        if($data =  Auth::guard($this->guard)->user()){
            $kode_lokasi= $data->kode_lokasi;
            $del1 = DB::connection($this->sql)->table('jurnal_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $this->nik_user)->delete();
            foreach ($rows as $row) 
            {
                $this->ket = "";
                if($row[0] == ""){
                    //
                }else{
                    $this->validateJurnal($row[0],$row[5],$row[2],$row[3],$row[4],$kode_lokasi);
                    if($this->ket != ""){
                        $sts = 0;
                    }else{
                        $sts = 1;
                    }
                    JurnalTmp::create([
                        'kode_akun' => $row[0],
                        'dc' => $row[2],
                        'keterangan' => $row[3],
                        'nilai' => $row[4],
                        'kode_pp' => $row[5],
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $this->nik_user,
                        'tgl_input' => date('Y-m-d H:i:s'),
                        'status' => $sts,
                        'ket_status' => "tes"
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