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

    
    function getNrcLajurBulan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_akun','kode_neraca','kode_fs');
            $db_col_name = array('a.periode','a.kode_akun','b.kode_neraca','b.kode_fs');
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

            
            $nik_user=$request->nik_user;
            $tahun=$request->input('periode')[1];

            $sqlex="exec sp_glma_tahun_dw_mutasi_tmp '$kode_lokasi','$tahun','$nik_user' ";
            $res = DB::connection($this->sql)->update($sqlex);

            $mutasi="";
            if($request->input('jenis')[1] != ""){

                if ($request->input('jenis')[1] == "Tidak")
                {
                    $mutasi="and (a.so_awal<>0 or a.n01<>0 or a.n02<>0 or a.n03<>0 or a.n04<>0 or a.n05<>0 or a.n06<>0 or a.n07<>0 or a.n08<>0 or a.n09<>0 or a.n10<>0 or a.n11<>0 or a.n12<>0 or a.n13<>0 or a.n14<>0 or a.n15<>0 or a.n16<>0 or a.total<>0) ";
                }
            }

            $sql="select a.kode_akun,a.nama,a.kode_lokasi,a.so_awal,a.n01,a.n02,a.n03,a.n04,a.n05,a.n06,a.n07,a.n08,a.n09,a.n10,a.n11,a.n12,a.n13,a.n14,a.n15,a.n16,a.total
            from glma12_tmp a $where and a.nik_user='$nik_user' $mutasi
            order by a.kode_akun";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data']=$res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']=[];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiBulan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','a.kode_fs');
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
            $nik_user=$request->nik_user;
            $tahun=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $level=$request->input('level')[1];
            $sql="exec sp_lr_tahun_dw '$kode_fs','L','S','$level','$tahun','$kode_lokasi','$nik_user'  ";
            $res = DB::connection($this->sql)->update($sql);

            $id = (isset($request->id) && ($request->id != "") ? $request->id : "-");
            if($id == "-"){
                
                $sql="select a.kode_lokasi,a.kode_neraca,a.nama,a.level_spasi,a.tipe,a.kode_fs,
                case jenis_akun when  'Pendapatan' then -a.n1 else a.n1 end as n01,
                case jenis_akun when  'Pendapatan' then -a.n2 else a.n2 end as n02,
                case jenis_akun when  'Pendapatan' then -a.n3 else a.n3 end as n03,
                case jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end as n04,
                case jenis_akun when  'Pendapatan' then -a.n5 else a.n5 end as n05,
                case jenis_akun when  'Pendapatan' then -a.n6 else a.n6 end as n06,
                case jenis_akun when  'Pendapatan' then -a.n7 else a.n7 end as n07,
                case jenis_akun when  'Pendapatan' then -a.n8 else a.n8 end as n08,
                case jenis_akun when  'Pendapatan' then -a.n9 else a.n9 end as n09,
                case jenis_akun when  'Pendapatan' then -a.n10 else a.n10 end as n10,
                case jenis_akun when  'Pendapatan' then -a.n11 else a.n11 end as n11,
                case jenis_akun when  'Pendapatan' then -a.n12 else a.n12 end as n12,
                case jenis_akun when  'Pendapatan' then -a.n13 else a.n13 end as n13,
                case jenis_akun when  'Pendapatan' then -a.n14 else a.n14 end as n14,
                case jenis_akun when  'Pendapatan' then -a.n15 else a.n15 end as n15,
                case jenis_akun when  'Pendapatan' then -a.n16 else a.n16 end as n16,
                case jenis_akun when  'Pendapatan' then -a.n17 else a.n17 end as n17
                from neraca_tmp a 
                where nik_user='$nik_user' and modul='L' 
                order by rowindex";
            }else{
                $sql="select a.kode_akun,a.nama,
                case c.jenis_akun when  'Pendapatan' then -a.n01 else a.n01 end as n01,
                case c.jenis_akun when  'Pendapatan' then -a.n02 else a.n02 end as n02,
                case c.jenis_akun when  'Pendapatan' then -a.n03 else a.n03 end as n03,
                case c.jenis_akun when  'Pendapatan' then -a.n04 else a.n04 end as n04,
                case c.jenis_akun when  'Pendapatan' then -a.n05 else a.n05 end as n05,
                case c.jenis_akun when  'Pendapatan' then -a.n06 else a.n06 end as n06,
                case c.jenis_akun when  'Pendapatan' then -a.n07 else a.n07 end as n07,
                case c.jenis_akun when  'Pendapatan' then -a.n08 else a.n08 end as n08,
                case c.jenis_akun when  'Pendapatan' then -a.n09 else a.n09 end as n09,
                case c.jenis_akun when  'Pendapatan' then -a.n10 else a.n10 end as n10,
                case c.jenis_akun when  'Pendapatan' then -a.n11 else a.n11 end as n11,
                case c.jenis_akun when  'Pendapatan' then -a.n12 else a.n12 end as n12,
                case c.jenis_akun when  'Pendapatan' then -a.n13 else a.n13 end as n13,
                case c.jenis_akun when  'Pendapatan' then -a.n14 else a.n14 end as n14,
                case c.jenis_akun when  'Pendapatan' then -a.n15 else a.n15 end as n15,
                case c.jenis_akun when  'Pendapatan' then -a.n16 else a.n16 end as n16,
                case c.jenis_akun when  'Pendapatan' then -a.total else a.total end as total,3 as level_spasi
                from glma12_tmp a
                inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                inner join neraca c on b.kode_neraca=c.kode_neraca and b.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi
                where b.kode_fs='$kode_fs' and b.kode_lokasi='$kode_lokasi' and b.kode_neraca='$id' and a.nik_user='$nik_user' 
                order by a.kode_akun ";
            }

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;    
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
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
