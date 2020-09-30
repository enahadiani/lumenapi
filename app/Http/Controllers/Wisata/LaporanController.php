<?php

namespace App\Http\Controllers\Wisata;

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
    public $guard = 'toko';
    public $sql = 'tokoaws';

    function getNamaBulan($bulan) {
        $arrayBulan = array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 
        'September', 'Oktober', 'November', 'Desember');
        return $arrayBulan[$bulan-1];
    }

    function getBulanList() {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            //."$kode_lokasi".//
            $res = DB::connection($this->sql)->select("select distinct bulan from par_kunj_m where kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $dataBulan = array();
            for($i=0;$i<count($res);$i++) {
                $dataBulan[] = array(
                    'kode'=>$res[$i]['bulan'],
                    'nama'=> $this->getNamaBulan(intval($res[$i]['bulan']))
                );
            }

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $dataBulan;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getTahunList() {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            //".$kode_lokasi."//
            $res = DB::connection($this->sql)->select("select distinct tahun from par_kunj_m where kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getMitra($kode) {
        try {
            $res = DB::connection($this->sql)->select("select distinct nama from par_mitra where kode_mitra='".$kode."'");						
            $res= json_decode(json_encode($res),true);
            return $res[0]['nama'];
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getBidang($kode) {
        try {
            $res = DB::connection($this->sql)->select("select distinct nama from par_bidang where kode_bidang='".$kode."'");						
            $res= json_decode(json_encode($res),true);
            return $res[0]['nama'];
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getJenis($kode) {
        try {
            $res = DB::connection($this->sql)->select("select distinct nama from par_jenis where kode_jenis='".$kode."'");						
            $res= json_decode(json_encode($res),true);
            return $res[0]['nama'];
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getSubJenis($kode) {
        try {
            $res = DB::connection($this->sql)->select("select distinct nama from par_subjenis where kode_subjenis='".$kode."'");						
            $res= json_decode(json_encode($res),true);
            return $res[0]['nama'];
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportKunjungan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_bidang', 'kode_mitra', 'kode_jenis', 'kode_subjenis','bulan', 'tahun');
            $db_col_name = array('e.kode_bidang', 'b.kode_mitra', 'd.kode_jenis', 'c.kode_subjenis', 'z.bulan', 'z.tahun');
            $where = "where a.kode_lokasi='$kode_lokasi'";

            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." like '%".$request->input($col_array[$i])[1]."' ";
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

            if($request->input($col_array[0][0]) == '=') {
                $success['bidang'] = $this->getBidang($request->input($col_array[0])[1]);   
            }

            if($request->input($col_array[1][0]) == '=') {
                $success['mitra'] = $this->getMitra($request->input($col_array[1])[1]);   
            }

            if($request->input($col_array[2][0]) == '=') {
                $success['jenis'] = $this->getJenis($request->input($col_array[2])[1]);   
            }

            if($request->input($col_array[3][0]) == '=') {
                $success['subjenis'] = $this->getSubJenis($request->input($col_array[3])[1]);   
            }
            
            $sql="select b.kode_mitra,b.nama,sum(a.jumlah) as kunjungan
                from par_kunj_d a
                inner join par_kunj_m z on a.no_bukti=z.no_bukti and a.kode_lokasi=z.kode_lokasi
                inner join par_mitra b on a.kode_mitra=b.kode_mitra and a.kode_lokasi=b.kode_lokasi
                inner join par_subjenis c on a.kode_subjenis=c.kode_subjenis and a.kode_lokasi=c.kode_lokasi
                inner join par_jenis d on c.kode_jenis=d.kode_jenis and c.kode_lokasi=d.kode_lokasi
                inner join par_bidang e on d.kode_bidang=e.kode_bidang and d.kode_lokasi=e.kode_lokasi
                $where 
                group by b.kode_mitra,b.nama";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['bulan'] = $this->getNamaBulan(intval($request->input($col_array[4])));
                $success['tahun'] = $request->input($col_array[5])[1];
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportBidang(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_bidang');
            $db_col_name = array('a.kode_bidang');
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
            
            $sql="select a.kode_bidang, a.nama from par_bidang a $where";
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
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getReportMitra(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('kode_mitra');
            $db_col_name = array('a.kode_mitra');
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
            
            $sql="select a.kode_mitra, a.nama, a.alamat, a.no_tel, a.pic from par_mitra a $where";
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
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
