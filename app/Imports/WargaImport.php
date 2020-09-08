<?php

namespace App\Imports;

use App\AdminWarga;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WargaImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    public function model(array $row)
    {
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $cek= DB::connection($this->sql)->select("select no_bukti from rt_warga_d where kode_lokasi='$kode_lokasi' and no_rumah='".$row['alamat']."' ");
            $cek = json_decode(json_encode($cek),true);
            if(count($cek) > 0){
                $no_bukti = $cek[0]['no_bukti'];
            }else{
                
                $str_format="0000";
                $periode=date('Y').date('m');
                $per=date('y').date('m');
                $prefix="WR".$per;
                $sql="select right(isnull(max(no_bukti),'00000'),".strlen($str_format).")+1 as id from rt_warga_d where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
                $get = DB::connection($this->sql)->select($sql);
                $get = json_decode(json_encode($get),true);
                $no_bukti = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }
            $rs= DB::connection($this->sql)->select("select blok,rt from rt_rumah where kode_lokasi='$kode_lokasi' and kode_rumah='".$row['alamat']."' ");
            $rs = json_decode(json_encode($rs),true);
            if(count($rs) > 0){
                $rt = $rs[0]['rt'];
                $blok = $rs[0]['blok'];
            }else{
                $rt = "-";
                $blok = "-";
            }

            $res = DB::connection($this->sql)->select("select max(no_urut) as nu from rt_warga_d where no_rumah ='".$row['alamat']."' and kode_lokasi='$kode_lokasi' ");
            $no_urut = intval($res[0]->nu)+1;

            $pass = substr($row['no_hp'],6);
            $password = app('hash')->make($pass);

            return new AdminWarga([
                'kode_blok'=>$blok,
                'no_rumah'=>$row['alamat'],
                'no_urut'=>$no_urut,
                'nama'=>$row['nama'],
                'alias'=>$row['nama_panggilan'],
                'nik'=>"-",
                'kode_jk'=>"-",
                'kode_agama'=>"-",
                'no_hp'=>$row['no_hp'],
                'no_bukti'=>$no_bukti,
                'kode_lokasi'=>$kode_lokasi,
                'kode_pp'=>$rt,
                'foto'=>"-",
                'pass'=>$pass,
                'password'=>$password
            ]);
        }
    }

    // public function headings(): array
    // {

    //     return [
    //         'No',
    //         'Kode Barang',
    //         'Jumlah'
    //     ];

    // }
}
