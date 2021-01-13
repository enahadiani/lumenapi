<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanKeuLanjutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $sql = 'tokoaws';

    function getNeracaKomparasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];
            $kode_fs = $request->input('kode_fs')[1];
            $periode2 = $request->input('periode2')[1];

            $sql= "exec sp_neraca2_dw '$kode_fs','N','K','1','$periode','$periode2','$kode_lokasi','$nik_user'  ";
            $res = DB::connection($this->sql)->getPdo()->exec($sql);

            $sql3 = "select '$kode_lokasi' as kode_lokasi,kode_neraca1,kode_neraca2,nama1,tipe1,nilai1,level_spasi1,nama2,tipe2,nilai2,level_spasi2,nilai3,nilai4 
            from neraca_skontro where nik_user='$nik_user' order by rowindex ";
         
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                $success["auth_status"] = 2;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiKomparasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $nik_user = $request->nik_user;
            $periode = $request->input('periode')[1];
            $kode_fs = $request->input('kode_fs')[1];
            $periode2 = $request->input('periode2')[1];

            $sql= "exec sp_neraca2_dw '$kode_fs','L','S','1','$periode','$periode2','$kode_lokasi','$nik_user' ";
            $res = DB::connection($this->sql)->getPdo()->exec($sql);

            $sql3 = "select kode_neraca,kode_fs,kode_lokasi,nama,tipe,level_spasi,
            case jenis_akun when  'Pendapatan' then -n1 else n1 end as n1,
            case jenis_akun when  'Pendapatan' then -n2 else n2 end as n2
            from neraca_tmp 
            where modul='L' and nik_user='$nik_user' 
            order by rowindex  ";
         
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res3) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                $success["auth_status"] = 2;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getCOA(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_fs');
            $db_col_name = array('a.kode_fs');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
            $nik_user = $request->nik_user;

            $sql = "select a.kode_neraca,a.nama,a.modul,a.tipe,b.kode_akun,c.nama as nama_akun,a.level_spasi,a.kode_induk,a.kode_fs
            from neraca a
            left join relakun b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi
            left join masakun c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            $where and a.modul='A'
            order by a.rowindex
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 ="select a.kode_neraca,a.nama,a.modul,a.tipe,b.kode_akun,c.nama as nama_akun,a.level_spasi,a.kode_induk,a.kode_fs
            from neraca a
            left join relakun b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi
            left join masakun c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            $where and a.modul='P'
            order by a.rowindex";

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 ="select a.kode_neraca,a.nama,a.modul,a.tipe,b.kode_akun,c.nama as nama_akun,a.level_spasi,a.kode_induk,a.kode_fs
            from neraca a
            left join relakun b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi
            left join masakun c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            $where and a.modul='L'
            order by a.rowindex";
         
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['data3'] = $res3;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['data2'] = [];
                $success['data3'] = [];
                $success['status'] = true;
                $success["auth_status"] = 2;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
