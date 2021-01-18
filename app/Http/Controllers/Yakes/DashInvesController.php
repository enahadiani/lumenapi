<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Log;

class DashInvesController extends Controller
{    
    public $successStatus = 200;
    public $db = 'dbsapkug';
    public $guard = 'yakes';

    function dbRowArray($sql){
    
        $query = collect(DB::connection($this->db)->select($sql))->first();
        return $query;
    }

    function dbResultArray($sql){
    
        $query = DB::connection($this->db)->select($sql);
        $query = json_decode(json_encode($sql),true);
        return $query;
    }

    function getTglAkhir($perAkhir = null){
       
        $filter = "";
        if ($perAkhir != ""){
            $filter = " where periode='$perAkhir' ";
        }
        $sql2 = "select max(a.tanggal) as tgl from 
            (
                select tanggal from inv_saham_kkp $filter
                union all 
                select tanggal from inv_rd_kkp  $filter
                union all 
                select tanggal from inv_sp_kkp $filter
                union all 
                select tanggal from inv_depo_kkp $filter
                union all
                select tanggal from inv_tab_kkp $filter
            ) a
            ";
        $rsta = $this->dbRowArray($sql2);
        if($rsta != NULL){
            $tglakhir = $rsta[0]->tgl;     
        }else{
            $tglakhir = "";     
        }
        return $tglakhir;
    }

    function getParam($nik){
        $result = array();
        $param = $this->dbRowArray("select*from inv_filterdash where nik='".$nik."'");
       
        $tgl_akhir = (isset($param->tgl_akhir) ? $param->tgl_akhir : "");
        $kode_plan = (isset($param->kode_plan) ? $param->kode_plan : "");
        $kode_klp = (isset($param->kode_klp) ? $param->kode_klp : "");
        if($tgl_akhir == ""){
            $tgl_akhir = $this->getTglAkhir();
        }
        if($kode_plan == ""){
            $kode_plan = '1';
        }
        if($kode_klp == ""){
            $kode_klp = "5050";
        }
        $result["tgl_akhir"] = $tgl_akhir;
        $result["kode_plan"] = $kode_plan;
        $result["kode_klp"] = $kode_klp;
        return $result;
    }   

    public function getFilKolom(Request $request) {
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $tahun = substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunLalu = intval($tahun)-1;

            $sql = "
            select distinct periode from inv_kelas_dash
            where periode LIKE '$tahun%' and periode not in ('".$tahun."03','".$tahun."06','".$tahun."09','".$tahun."12')
            union all
            select '$tahun'+'Q1' as periode
            union all
            select '$tahun'+'Q2' as periode
            union all
            select '$tahun'+'Q3' as periode
            union all
            select '$tahun'+'Q4' as periode
            union all
            select distinct periode from inv_kelas_dash
            where periode LIKE '$tahunLalu%' and periode not in ('".$tahunLalu."03','".$tahunLalu."06','".$tahunLalu."09','".$tahunLalu."12')
            union all
            select '$tahunLalu'+'Q1' as periode
            union all
            select '$tahunLalu'+'Q2' as periode
            union all
            select '$tahunLalu'+'Q3' as periode
            union all
            select '$tahunLalu'+'Q4' as periode";

            $res = $this->dbResultArray($sql);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getTotalAlokasi(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun = substr($tgl_akhir,0,4);

            $sql = "exec sp_get_real_alokasi '$tgl_akhir','$kode_klp','$kode_plan','$kode_lokasi','$nik'  ";

            $exec = DB::connection($this->db)->update($sql);

            $total = $this->dbRowArray("select sum(nilai) as nwajar from inv_batas_alokasi where kode_plan='$kode_plan' and tahun='$tahun' ");
            $success['total'] = ( isset($total->nwajar) ? floatval($total->nwajar) : 0 );
            $success['status'] = true;
            $success['message'] = "Success!";     
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getAset(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun = substr($tgl_akhir,0,4);

            $sql = "exec sp_get_real_alokasi '$tgl_akhir','$kode_klp','$kode_plan','$kode_lokasi','$nik'  ";

            $exec = DB::connection($this->db)->update($sql);

            $saham = $this->dbRowArray("select isnull(nilai,0) as jum,acuan from inv_batas_alokasi where kode_kelas='SB' and kode_plan='$kode_plan' and tahun='$tahun' ");
            $kas= $this->dbRowArray("select isnull(nilai,0) as jum,acuan from inv_batas_alokasi where kode_kelas='KAS' and kode_plan='$kode_plan' and tahun='$tahun' ");
            $ebt = $this->dbRowArray("select isnull(nilai,0) as jum,acuan from inv_batas_alokasi where kode_kelas='EBT' and kode_plan='$kode_plan' and tahun='$tahun' ");
            $propensa = $this->dbRowArray("select isnull(nilai,0) as jum,acuan  from inv_batas_alokasi where kode_kelas='PRO' and kode_plan='$kode_plan' and tahun='$tahun' ");

            $success["saham"]=(isset($saham->jum) ? $saham->jum : 0);
            $success["kas"]=(isset($kas->jum) ? $kas->jum : 0);
            $success["ebt"]=(isset($ebt->jum) ? $ebt->jum : 0);
            $success["propensa"]=(isset($propensa->jum) ? $propensa->jum : 0);
            $success["saham_acuan"]=(isset($saham->acuan) ? $saham->acuan : 0);
            $success["kas_acuan"]=(isset($kas->acuan) ? $kas->acuan : 0);
            $success["ebt_acuan"]=(isset($ebt->acuan) ? $ebt->acuan : 0);
            $success["propensa_acuan"]=(isset($propensa->acuan) ? $propensa->acuan : 0);

            $total = $this->dbRowArray("select sum(nilai) as nwajar from inv_batas_alokasi where kode_plan='$kode_plan' and tahun='$tahun' ");
            $success["total"] = (isset($total->nwajar) ? $total->nwajar : 0);    
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getPersenAset(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun = substr($tgl_akhir,0,4);

            $sql = "exec sp_get_real_alokasi '$tgl_akhir','$kode_klp','$kode_plan','$kode_lokasi','$nik'  ";

            $exec = DB::connection($this->db)->update($sql);

            $res = $this->dbRowArray("select sum(nilai) as jum from inv_batas_alokasi where kode_plan='$kode_plan' and tahun='$tahun' ");
            
            $res2 = $this->dbRowArray("select sum(sawal_tahun) as jum from inv_batas_alokasi where kode_plan='$kode_plan' and tahun='$tahun' ");
            $jum = (isset($res->jum) ? floatval($res->jum) : 0);
            $nbukuawal = (isset($res2->jum) ? floatval($res2->jum) : 0);
            $persen = ($nbukuawal != 0 ? (($jum-$nbukuawal)/$nbukuawal)*100 : 0);
            $success["persen"]= round($persen,2);
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getBatasAlokasi(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun = substr($tgl_akhir,0,4);

            $sql = "exec sp_get_real_alokasi '$tgl_akhir','$kode_klp','$kode_plan','$kode_lokasi','$nik'  ";

            $exec = DB::connection($this->db)->update($sql);

            $success["saham"] = $this->dbResultArray("select a.kode_kelas,a.bawah,a.acuan,a.atas,a.nilai,a.persen, isnull(b.roi_persen,0) as roi 
            from inv_batas_alokasi a
            left join inv_roi_beban b on a.kode_kelas=b.modul and b.tanggal = '$tgl_akhir'
            where a.kode_kelas='SB' and a.kode_plan ='$kode_plan' and a.tahun='$tahun' ");
            $success["kas"] = $this->dbResultArray("select a.kode_kelas,a.bawah,a.acuan,a.atas,a.nilai,a.persen, isnull(b.roi_persen,0) as roi 
            from inv_batas_alokasi a
            left join inv_roi_beban b on a.kode_kelas=b.modul and b.tanggal = '$tgl_akhir'
            where a.kode_kelas='KAS' and a.kode_plan ='$kode_plan' and a.tahun='$tahun' ");
            $success["ebt"] = $this->dbResultArray("select a.kode_kelas,a.bawah,a.acuan,a.atas,a.nilai,a.persen, isnull(b.roi_persen,0) as roi 
            from inv_batas_alokasi a
            left join inv_roi_beban b on a.kode_kelas=b.modul and b.tanggal = '$tgl_akhir'
            where a.kode_kelas='EBT' and a.kode_plan ='$kode_plan' and a.tahun='$tahun' ");
            $success["pro"] = $this->dbResultArray("select a.kode_kelas,a.bawah,a.acuan,a.atas,a.nilai,a.persen, isnull(b.roi_persen,0) as roi 
            from inv_batas_alokasi a
            left join inv_roi_beban b on a.kode_kelas=b.modul and b.tanggal = '$tgl_akhir'
            where a.kode_kelas='PRO' and a.kode_plan ='$kode_plan' and a.tahun='$tahun' ");
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNOleh(){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun = substr($tgl_akhir,0,4);

            $res = $this->dbRowArray("select sum(jumlah * h_wajar) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' ");
            $res2 = $this->dbRowArray("select sum(jumlah * h_oleh) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan'");
            $nwajar = (isset($res->jum) ? floatval($res->jum) : 0 );
            $noleh = (isset($res->jum) ? floatval($res->jum) : 0 );
            $success["nwajar"] = $nwajar;
            $success["noleh"] = $noleh;
            $success["persen"] = ($nwajar != 0 ? round((($nwajar - $noleh) / $nwajar)*100,2) : 0);
            $success["daftar"] = $this->dbResultArray(" select a.kode_kelola,a.nama,a.gambar, b.jum as noleh, c.jum as nwajar, round(((c.jum-b.jum)/c.jum)*100,2) as persen
            from inv_kelola a
            inner join ( select kode_kelola,sum(jumlah * h_oleh) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' group by kode_kelola ) b
            on a.kode_kelola=b.kode_kelola
            inner join ( select kode_kelola,sum(jumlah * h_wajar) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' group by kode_kelola ) c
            on a.kode_kelola=c.kode_kelola");
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

}
