<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helper\SaiHelpers;

class FilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    
    function getFilterTahunDash(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select distinct substring(periode,1,4) as tahun from dash_klpakun_lap order by substring(periode,1,4) desc";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    public function getTglServer() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select getdate() as tglnow ");						
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

    function getPerInput(Request $request)     
    {
        $this->validate($request, [
            'tanggal' => 'required'
        ]);

        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $query = DB::connection($this->db)->select("select max(periode) as periode_aktif from periode where kode_lokasi='".$kode_lokasi."' ");
        $query = json_decode(json_encode($query),true);
        $periodeAktif = $query[0]['periode_aktif'];
                
        if (intval(substr($periodeAktif,4,2)) > 12 ) {
            $periode = $periodeAktif;
        }
        else {
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
        }

        return $periode;
    }

    
    function getFilterAkun(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select kode_akun,nama from masakun where kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterBidang(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            $tahun = substr($request->periode[1],0,4);
            if($status_admin == "A"){

                $sql="select a.kode_bidang, a.nama from agg_bidang a where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun'";

            }else{
                $sql = "select distinct a.kode_bidang,c.nama 
                from agg_pp a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik'
                left join agg_bidang c on a.kode_bidang=c.kode_bidang and a.kode_lokasi=c.kode_lokasi and a.tahun=c.tahun
                where a.kode_lokasi='".$kode_lokasi."'  and a.tahun='$tahun'
                ";
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function getFilterPp(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            
            $col_array = array('kode_bidang');
            $db_col_name = array('a.kode_bidang');
            $filter = SaiHelpers::filterRpt($request,$col_array,$db_col_name,"","");
            $tahun = substr($request->periode[1],0,4);

            if($status_admin == "A"){
                $sql="select a.kode_pp,a.nama from agg_pp a where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun' $filter ";
            }else{
                $sql="select a.kode_pp,a.nama 
                from agg_pp a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik'
                where a.kode_lokasi='".$kode_lokasi."' and a.tahun='$tahun' $filter ";
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function getFilterProdi(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select kode_pp,nama from pp where kode_lokasi='$kode_lokasi' and kode_pp like '5%' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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
   
    function getFilterPeriodeKeuangan(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select distinct a.periode,dbo.fnNamaBulan(a.periode) as nama from periode a where a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterFS(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select kode_fs,nama from fs where kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterRektor(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select kode_rektor,nama from exs_rektor where kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterFakultas(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }
            if($status_admin == "A"){
                $sql="select kode_fakultas,nama from aka_fakultas where kode_lokasi='$kode_lokasi' ";
            }else{
                $sql = "select a.kode_fakultas,a.nama  
				from aka_fakultas a 
				inner join karyawan b on a.kode_fakultas=substring(b.kode_pp,1,2) and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and b.nik='".$nik."' ";
            }
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterLevel(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = array(
                0 => array('kode' => 1),
                1 => array('kode' => 2),
                2 => array('kode' => 3),
                3 => array('kode' => 4),
                4 => array('kode' => 5)
            );
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterFormat(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = array(
                0 => array('kode' => 'Saldo Akhir'),
                1 => array('kode' => 'Mutasi')
            );
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterYaTidak(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = array(
                0 => array('kode' => "Ya"),
                1 => array('kode' => "Tidak")
            );
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterModul(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->periode) && $request->periode != ""){
                $filter = " and periode='".$request->periode."' ";
            }else{
                $filter = "";
            }
          
            $sql="select distinct modul as kode from gldt where kode_lokasi='$kode_lokasi' $filter";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

    function getFilterBuktiJurnal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode','modul');
            $db_col_name = array('a.periode','a.modul');
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

            $sql="select a.no_bukti,a.keterangan 
            from gldt a $where";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
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

    function getFilterDefaultLabaRugiAgg(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = "-";

            $sql="select max(periode) as periode from periode where kode_lokasi='".$kode_lokasi."' ";
            $res = DB::connection($this->db)->select($sql);
            if(count($res) > 0){
                $periode = $res[0]->periode;
            }

            $kode_bidang = "-";
            $nama_bidang = "-";
            $kode_pp = "-";
            $nama_pp = "-";
            $sql = "select b.kode_pp,b.nama as nama_pp,b.kode_bidang,c.nama as nama_bidang 
            from karyawan a 
            inner join agg_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.tahun='".substr($periode,0,4)."'
            inner join agg_bidang c on b.kode_bidang=c.kode_bidang and b.kode_lokasi=c.kode_lokasi and b.tahun=c.tahun 
            where a.nik='".$nik."' and a.kode_lokasi='".$kode_lokasi."'  ";
            $res2 = DB::connection($this->db)->select($sql);
            if(count($res2) > 0){
                $kode_bidang = $res2[0]->kode_bidang;
                $nama_bidang = $res2[0]->nama_bidang;
                $kode_pp = $res2[0]->kode_pp;
                $nama_pp = $res2[0]->nama_pp;
            }

            $kode_fakultas = "-";
            $nama_fakultas = "-";
            $sql = "select a.kode_fakultas,a.nama as nama_fakultas
            from aka_fakultas a inner join karyawan b on a.kode_fakultas=substring(b.kode_pp,1,2) and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' and b.nik='".$nik."'";
            $res3 = DB::connection($this->db)->select($sql);
            if(count($res3) > 0){
                $kode_fakultas = $res3[0]->kode_fakultas;
                $nama_fakultas = $res3[0]->nama_fakultas;
            }

            $success['status'] = true;
            $success['periode'] = $periode;
            $success['kode_bidang'] = $kode_bidang;
            $success['nama_bidang'] = $nama_bidang;
            $success['kode_pp'] = $kode_pp;
            $success['nama_pp'] = $nama_pp;
            $success['kode_fakultas'] = $kode_fakultas;
            $success['nama_fakultas'] = $nama_fakultas;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
