<?php

namespace App\Http\Controllers\DashYpt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    function getLabaRugiAggPP(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','kode_fs','kode_pp');
            $db_col_name = array('a.periode','a.kode_fs','a.kode_pp');
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
            $periode=$request->input('periode')[1];
            $kode_fs=$request->input('kode_fs')[1];
            $kode_pp=$request->input('kode_pp')[1];
            $lev=$request->input('level')[1];
            $tahun = substr($periode,0,4);
            $bln = substr($periode,4,2);
            $tahunseb = intval($tahun)-1;

            $sql="select a.kode_pp,a.nama from pp a where kode_pp='$kode_pp' order by a.kode_pp ";
            $rs = DB::connection($this->db)->select($sql);
            $nama_pp = "-";
            if(count($rs) > 0){
                $nama_pp = $rs[0]->nama;
            }
            
            $sql="select a.kode_neraca,a.kode_fs,a.kode_lokasi,a.nama,a.tipe,a.level_spasi,a.kode_pp,
            case a.jenis_akun when  'Pendapatan' then -a.n1 else a.n1 end as n1,
            case a.jenis_akun when  'Pendapatan' then -a.n2 else a.n2 end as n2,
            case a.jenis_akun when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case a.jenis_akun when  'Pendapatan' then -a.n4 else a.n4 end as n4,
            case a.jenis_akun when  'Pendapatan' then -a.n5 else a.n5 end as n5,
            case a.jenis_akun when  'Pendapatan' then -a.n6 else a.n6 end as n6,
            case a.jenis_akun when  'Pendapatan' then -a.n7 else a.n7 end as n7
            from exs_neraca_pp a
            $where and a.modul='L' and a.level_lap<=$lev 
            order by a.rowindex";
            $res = DB::connection($this->db)->select($sql);
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
                // $success['sql'] = $sql;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getLabaRugiAggPPDetail(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nik_user=$request->nik_user;

            $col_array = array('periode','kode_fs');
            $db_col_name = array('a.periode','b.kode_fs');
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

            $id = isset($request->id) ? $request->id : '-';
            $periode = $request->periode[1];
            $kode = $request->kode;

            $sql = "select a.kode_akun,c.nama,b.kode_fs,
            case c.jenis when 'Pendapatan' then -a.n1 else a.n1 end as n1, 
            case c.jenis when 'Pendapatan' then -a.n2 else a.n2 end as n2, 
            case c.jenis when  'Pendapatan' then -a.n3 else a.n3 end as n3,
            case c.jenis when  'Pendapatan' then -a.n4 else a.n4 end as n4,
            case c.jenis when  'Pendapatan' then -a.n5 else a.n5 end as n5,
            case c.jenis when  'Pendapatan' then -a.n6 else a.n6 end as n6,
            case c.jenis when  'Pendapatan' then -a.n7 else a.n7 end as n7,
            case c.jenis when  'Pendapatan' then -a.n9 else a.n9 end as n9,
            case c.jenis when  'Pendapatan' then -a.n10 else a.n10 end as n10,
            case c.jenis when  'Pendapatan' then -a.n11 else a.n11 end as n11,
            case c.jenis when  'Pendapatan' then -a.n12 else a.n12 end as n12,
            case c.jenis when  'Pendapatan' then -a.n13 else a.n13 end as n13,
            case c.jenis when  'Pendapatan' then -a.n14 else a.n14 end as n14, 
            3 as level_spasi
            from exs_glma_gar_pp a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            $where and b.kode_neraca='$id' and a.periode='$periode' and a.kode_pp='$kode'
            order by a.kode_akun" ;
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['data'] = $res;
                $success['status'] = true;
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


}
