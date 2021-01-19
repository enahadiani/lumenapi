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
    
        $res = DB::connection($this->db)->select($sql);
        $res = json_decode(json_encode($res),true);
        return $res;
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
                --union all
                --select tanggal from inv_tab_kkp $filter
            ) a
            ";
        $rsta = $this->dbRowArray($sql2);
        if($rsta != NULL){
            $tglakhir = $rsta->tgl;     
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
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getFilterPlan(Request $request) {
        
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

            $sql = "select kode_plan,nama from inv_plan";
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
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getFilterKlp(Request $request) {
        
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

            $sql = "select distinct kode_klp from inv_persen";
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
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getTotalAlokasi(Request $request){
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
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getAset(Request $request){
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getPersenAset(Request $request){
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getBatasAlokasi(Request $request){
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getRoiKkp(Request $request){
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

            $sql1= "select roi_total,cash_out,pdpt,beban_inves,spi from inv_roi_kkp where tanggal='$tgl_akhir' and kode_plan='$kode_plan'";
            $success["data"] = $this->dbResultArray($sql1);

            $sql2 = "select *
            from  inv_roi_total where kode_plan='$kode_plan' and tanggal='$tgl_akhir'";
            $row = $this->dbResultArray($sql2);

            $success["roi_total"] = $row[0]["roi_ytd"];

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNOleh(Request $request){
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNBuku(Request $request){
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
            $res2 = $this->dbRowArray("select sum(jumlah * h_buku) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan'");
            $nwajar = (isset($res->jum) ? floatval($res->jum) : 0 );
            $nbuku = (isset($res->jum) ? floatval($res->jum) : 0 );
            $success["nwajar"] = $nwajar;
            $success["nbuku"] = $nbuku;
            $success["persen"] = ($nwajar != 0 ? round((($nwajar - $nbuku) / $nwajar)*100,2) : 0);
            $success["daftar"] = $this->dbResultArray(" select a.kode_kelola,a.nama,a.gambar, b.jum as nbuku, c.jum as nwajar, round(((c.jum-b.jum)/c.jum)*100,2) as persen
            from inv_kelola a
            inner join ( select kode_kelola,sum(jumlah * h_buku) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' group by kode_kelola ) b
            on a.kode_kelola=b.kode_kelola
            inner join ( select kode_kelola,sum(jumlah * h_wajar) as jum from inv_saham_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' group by kode_kelola ) c
            on a.kode_kelola=c.kode_kelola");
            
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNABHari(Request $request){
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
            $periode = $tahun.substr($tgl_akhir,5,2);

            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == 0){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between '".$request->tanggal[0]."' and '".$request->tanggal[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 1){
                    $filter = " where kode_plan='".$request->kode_plan."' and periode between '".$request->periode[0]."' and '".$request->periode[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 2){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) between '".$request->tahun[0]."' and '".$request->tahun[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }
                //YTD
                if($request->jenis == 3){
                    $tahun = substr($request->tanggal[0],0,4);

                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$tahun."-01-01') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                // YOY
                if($request->jenis == 4){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$request->tanggal[0]."') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."'";
                }

                if($request->jenis == 5){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($request->tanggal[0],0,4)."' and kode_kelola='".$request->kode_kelola."' ";
                }
            }else{
                $filter = "where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($periode,0,4)."' and kode_kelola='".$request->kode_kelola."' ";
            }

            $sql = "select kode_kelola,tanggal as tgl,
            sum(jumlah*h_wajar) as total
            from inv_saham_kkp $filter
            group by kode_kelola,tanggal
            order by kode_kelola,tanggal
            ";

            $pembagi = 1000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            $result = array();
            if(count($rs) > 0){
                foreach ($rs as $row){
                    // $date = new DateTime($row->tgl, new DateTimeZone("UTC"));
                    // $date->getTimestamp()
                    $result[$row->kode_kelola][] = array($row->tgl,round(floatval($row->total),2));
                    
                }
            }

            $sqlc = "select distinct kode_kelola
            from inv_saham_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_kelola"]] = $color[$i];
                $i++;
            }

            // $colors = array('BHN'=>'#727276','YKT'=>'#7cb5ec','SCH'=>'#ff6f69');
            $sql2 = "select distinct kode_kelola
            from inv_saham_kkp $filter
            ";
            $res = $this->dbResultArray($sql2);
            $success["data"] = array();
            foreach($res as $row){
                
                $success["data"][] = array("type"=>"area","name" => $row["kode_kelola"],"color"=>$colors[$row["kode_kelola"]], "data" => $result[$row["kode_kelola"]],"showInLegend"=>true );
                $i++;
            
            }

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSPIHari(Request $request){
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
            $periode = $tahun.substr($tgl_akhir,5,2);

            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == 0){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between '".$request->tanggal[0]."' and '".$request->tanggal[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 1){
                    $filter = " where kode_plan='".$request->kode_plan."' and periode between '".$request->periode[0]."' and '".$request->periode[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 2){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) between '".$request->tahun[0]."' and '".$request->tahun[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }
                //YTD
                if($request->jenis == 3){
                    $tahun = substr($request->tanggal[0],0,4);

                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$tahun."-01-01') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                // YOY
                if($request->jenis == 4){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$request->tanggal[0]."') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."'";
                }

                if($request->jenis == 5){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($request->tanggal[0],0,4)."' and kode_kelola='".$request->kode_kelola."' ";
                }
            }else{
                $filter = "where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($periode,0,4)."' and kode_kelola='".$request->kode_kelola."' ";
            }

            $sql = "select kode_kelola,tanggal as tgl,
            sum(jumlah*h_wajar)-sum(jumlah*h_oleh) as total,sum(jumlah*h_wajar)-sum(jumlah*h_buku) as total2
            from inv_saham_kkp $filter
            group by kode_kelola,tanggal
            order by kode_kelola,tanggal asc
            ";

            $pembagi = 1000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            $result = array();
            if(count($rs) > 0){
                foreach ($rs as $row){
                    // $date = new DateTime($row->tgl, new DateTimeZone("UTC"));
                    // $date->getTimestamp()
                    $result[$row->kode_kelola][] = array($row->tgl,round(floatval($row->total),2));
                    $result[$row->kode_kelola."SPI_Buku"][] = array($row->tgl,round(floatval($row->total2),2));
                    
                }
            }

            $sqlc = "select distinct kode_kelola
            from inv_saham_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_kelola"]] = $color[$i];
                $colors[$row["kode_kelola"]."SPI_Buku"] = $color[$i+1];
                $i++;
            }

            // $colors = array('BHN'=>'#727276','YKT'=>'#7cb5ec','SCH'=>'#ff6f69');
            $sql2 = "select distinct kode_kelola
            from inv_saham_kkp $filter
            ";
            $res = $this->dbResultArray($sql2);
            $success["data"] = array();
            foreach($res as $row){
                
                $success["data"][] = array("type"=>"area","name" => $row["kode_kelola"]." SPI Perolehan","color"=>$colors[$row["kode_kelola"]], "data" => $result[$row["kode_kelola"]],"showInLegend"=>true,"visible"=> false );
                $success["data"][] = array("type"=>"area","name" => $row["kode_kelola"]." SPI Buku","color"=>$colors[$row["kode_kelola"]."SPI_Buku"], "data" => $result[$row["kode_kelola"]."SPI_Buku"],"showInLegend"=>true );
                $i++;
            
            }

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSPIHari2(Request $request){
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
            $periode = $tahun.substr($tgl_akhir,5,2);

            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == 0){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between '".$request->tanggal[0]."' and '".$request->tanggal[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 1){
                    $filter = " where kode_plan='".$request->kode_plan."' and periode between '".$request->periode[0]."' and '".$request->periode[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 2){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) between '".$request->tahun[0]."' and '".$request->tahun[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }
                //YTD
                if($request->jenis == 3){
                    $tahun = substr($request->tanggal[0],0,4);

                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$tahun."-01-01') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                // YOY
                if($request->jenis == 4){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$request->tanggal[0]."') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."'";
                }

                if($request->jenis == 5){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($request->tanggal[0],0,4)."' and kode_kelola='".$request->kode_kelola."' ";
                }
            }else{
                $filter = "where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($periode,0,4)."' and kode_kelola='".$request->kode_kelola."' ";
            }

            $sql = "select kode_kelola,tanggal as tgl,sum(jumlah*h_wajar) as total2
            from inv_saham_kkp $filter
            group by kode_kelola,tanggal
            order by kode_kelola,tanggal asc
            ";

            $pembagi = 1000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            $result = array();
            if(count($rs) > 0){
                foreach ($rs as $row){
                    $result[$row->kode_kelola."n_wajar"][] = array($row->tgl,round(floatval($row->total2),2));
                    
                }
            }

            $sqlc = "select distinct kode_kelola
            from inv_saham_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_kelola"]] = $color[$i];
                $colors[$row["kode_kelola"]."n_wajar"] = $color[$i+1];
                $i++;
            }

            $success["colors"]=$colors;

            $sql2 = "select distinct kode_kelola
            from inv_saham_kkp $filter
            ";
            $res = $this->dbResultArray($sql2);
            $success["data"] = array();
            foreach($res as $row){
                $success["data"][] = array("type"=>"area","name" => $row["kode_kelola"]." Nilai Wajar","color"=>$colors[$row["kode_kelola"]."n_wajar"], "data" => $result[$row["kode_kelola"]."n_wajar"],"showInLegend"=>true );
                $i++;
            
            }

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getPortofolio(Request $request){
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

            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $sql2="with t(kode_subkelas,nama, n3,nu) 
            as 
            ( 
                select a.kode_subkelas,b.nama,sum(nab) as n3,b.nu
                from inv_roi_subkelas a 
                left join inv_subkelas b on a.kode_subkelas=b.kode_subkelas
                where modul='KAS' and tanggal='$tgl_akhir'
                group by a.kode_subkelas,b.nama,b.nu
            )
            select kode_subkelas,nama,n3,n3 * 100/(select sum(n3) from t) as persen
            from t
            order by nu";

            $kas = $this->dbResultArray($sql2);
            $success["kas_chart"] = array();
            $i=0;
            foreach($kas as $k){
                $tmK=$k["nama"];
                $success["kas_chart"][] = array("name" => $tmK,"y"=>round(floatval($k["n3"])),"key"=>"kas_rowke".$i,"color"=>$color[$i]);
                $i++;
            }

            $sql3="with t(kode_subkelas,nama, n3,nu) 
            as 
            ( 
                select a.kode_subkelas,b.nama,sum(nab) as n3,b.nu
                from inv_roi_subkelas a 
                left join inv_subkelas b on a.kode_subkelas=b.kode_subkelas
                where modul='EBT' and tanggal='$tgl_akhir'
                group by a.kode_subkelas,b.nama,b.nu
            )
            select kode_subkelas,nama,n3,n3 * 100/(select sum(n3) from t) as persen
            from t
            order by nu";

            $ebt = $this->dbResultArray($sql3);
            $success["ebt_chart"] = array();
            $dataebt = array();
            $j=0;
            foreach($ebt as $e){
                $tmE=$e["nama"];
                if($tmE == " RD Campuran"){
                    if($kom == "5050"){
                        $nilai = ($e["n3"]*50)/100;
                    }else{
                        $nilai = ($e["n3"]*30)/100;
                    }
                }else{
                    $nilai = $e["n3"];
                }
                $success["ebt_chart"][] = array("name" => $tmE,"y"=>round(floatval($nilai)),"key"=>"ebt_rowke".$j,"color"=>$color[$j]);
                $dataebt[] = array("kode_subkelas"=>$e["kode_subkelas"],"nama"=>$e["nama"],"n3"=>$nilai,"persen"=>$e["persen"]);
                $j++;
            }

            $sql4="with t(kode_subkelas,nama, n3,nu) 
            as 
            ( 
                select a.kode_subkelas,b.nama,sum(nab) as n3,b.nu
                from inv_roi_subkelas a 
                left join inv_subkelas b on a.kode_subkelas=b.kode_subkelas
                where modul='SB' and tanggal='$tgl_akhir'
                group by a.kode_subkelas,b.nama,b.nu
            )
            select kode_subkelas,nama,n3,n3 * 100/(select sum(n3) from t) as persen
            from t
            order by nu";
            $sb = $this->dbResultArray($sql4);

            $success["sb_chart"] = array();
            $datasb = array();
            $z=0;
            foreach($sb as $s){
                $tmS=$s["nama"];
                if($tmS == "RD Campuran"){
                    if($kom == "5050"){
                        $nilai = ($s["n3"]*50)/100;
                    }else{
                        $nilai = ($s["n3"]*70)/100;
                    }
                }else{
                    $nilai = $s["n3"];
                }
                $success["sb_chart"][] = array("name" => $tmS,"y"=>round(floatval($nilai)),"key"=>"sb_rowke".$z,"color"=>$color[$z]);
                $datasb[] = array("kode_subkelas"=>$s["kode_subkelas"],"nama"=>$s["nama"],"n3"=>$nilai,"persen"=>$s["persen"]);
                $z++;
            }
            $sql5 = "with t(kode_mitra,nama, n3) 
            as 
            ( 
                select a.kode_mitra,a.nama, isnull(b.jum,0) as n3 
                from inv_mitra a 
                left join (select kode_mitra,sum(jumlah * h_wajar) as jum 
                       from inv_sp_kkp 
                       where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' 
                       group by kode_mitra ) b on a.kode_mitra = b.kode_mitra
            )
            select kode_mitra,nama,n3, n3 * 100.0/(select sum(n3) from t) as persen
            from t
            order by kode_mitra; ";
            $pro = $this->dbResultArray($sql5);
            
            $success["pro_chart"] = array();
            $y=0;
            foreach($pro as $p){
                $tmP = $p["nama"];
                $success["pro_chart"][] = array("name" => $tmP,"y"=>round(floatval($p["n3"])),"key"=>"pro_rowke".$y,"color"=>$color[$y]);
                $y++;
            }


            $success["kas"] = $kas;
            $success["ebt"] = $dataebt;
            $success["sb"] = $datasb;
            $success["pro"] = $pro;
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSahamSektor(Request $request){
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
            $kode_fs = "FS3";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $res = $this->dbRowArray("select sum(a.nilai) as total
            from (select a.kode_sahamklp,a.nama, isnull(b.nilai,0) as nilai
                    from inv_sahamklp a 
                    left join (
                            select b.kode_sahamklp,sum(a.jumlah*a.h_wajar) as nilai
                            from inv_saham_kkp a
                            left join inv_saham b on a.kode_saham=b.kode_saham
                            where a.kode_plan='$kode_plan' and a.tanggal='$tgl_akhir'
                            group by b.kode_sahamklp
                    ) b on a.kode_sahamklp=b.kode_sahamklp
                    where a.kode_sahamklp not in ('S1000')
            ) a
            ");

            $total = (isset($res->total) ? floatval($res->total) : 0);
            $sektor = $this->dbResultArray("select a.kode_sahamklp,a.nama, isnull(b.nilai,0) as nilai, isnull(c.jum_kelola,0) as jum_kelola,(isnull(b.nilai,0)/".$total.")*100 as persen 
            from inv_sahamklp a 
            left join (
                    select b.kode_sahamklp,sum(a.jumlah*a.h_wajar) as nilai
                    from inv_saham_kkp a
                    left join inv_saham b on a.kode_saham=b.kode_saham
                    where a.kode_plan='$kode_plan' and a.tanggal='$tgl_akhir'
                    group by b.kode_sahamklp
            ) b on a.kode_sahamklp=b.kode_sahamklp
            left join (
                select a.kode_sahamklp, count(a.kode_kelola) as jum_kelola from (
                select distinct b.kode_sahamklp,a.kode_kelola 
                from inv_saham_kkp a
                left join inv_saham b on a.kode_saham=b.kode_saham
                where a.kode_plan='$kode_plan' and a.tanggal='$tgl_akhir'
                ) a
                group by a.kode_sahamklp
            ) c on a.kode_sahamklp=c.kode_sahamklp
            where a.kode_sahamklp not in ('S1000') ");

            $success["sektor"] = $sektor;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailPerSaham(Request $request){
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
            $kode_fs = "FS3";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $rs = DB::connection($this->db)->select("select top 5 a.kode_saham, sum(a.jumlah) as jum, sum(a.jumlah * a.h_wajar) as nilai, (sum(a.jumlah * a.h_wajar)/sum(a.jumlah)) as harga
            from inv_saham_kkp a 
            left join inv_saham b on a.kode_saham=b.kode_saham
            where a.kode_plan='$kode_plan' and a.tanggal='$tgl_akhir' and b.kode_sahamklp='$request->kode_sahamklp'
            group by a.kode_saham 
			having sum(a.jumlah) <> 0
            order by (sum(a.jumlah * a.h_wajar)/sum(a.jumlah)) desc
            ");
            $success["daftar"] = array();
            $success["daftar3"] = array();
            foreach($rs as $row){
                $success["daftar"][] = (array)$row;
                $success["daftar3"][] = (array)$row;
                $rs1 =  DB::connection($this->db)->select("select a.kode_kelola, a.kode_saham, a.jumlah,a.h_oleh,a.h_buku,a.h_wajar,((a.h_wajar-a.h_oleh)/a.h_wajar) * 100 as pers_oleh,((a.h_wajar-a.h_buku)/a.h_wajar) * 100 as pers_buku from inv_saham_kkp a
                left join inv_saham b on a.kode_saham=b.kode_saham
                where a.kode_plan='$kode_plan' and a.tanggal='$tgl_akhir' and b.kode_sahamklp='$request->kode_sahamklp' and a.h_oleh <> 0 and a.kode_saham ='$row->kode_saham'");

                foreach($rs1 as $row2){
                    $hasil[] = (array)$row2;
                }
            }
            $success["daftar2"] = $hasil;

            $grouping = array();
            $series = array();
            $color = array('SCH'=>'#727276','BHN'=>'#7cb5ec','YKT'=>'#ff6f69');
            $i=0;
            foreach($hasil as $r){
                if (!isset($grouping[$r["kode_saham"]])){
                    $tmp = array("data" => array());
                    $i++;
                }
                $tmp["data"][] = array("type"=>"column","name"=> $r["kode_kelola"],"data"=>array(round(floatval($r["pers_oleh"]),2),round(floatval($r["pers_buku"]),2)),"color"=>$color[$r["kode_kelola"]]);
                $grouping[$r["kode_saham"]] = $tmp;
            }

            $success["series"] = array();
            foreach($success["daftar3"] as $r){
                $success["series"][] = $grouping[$r["kode_saham"]];
            }
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDaftarRD(Request $request){
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
            $kode_fs = "FS3";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $rdkelola = $request->rdkelola;
            $orderfilter = $request->orderfilter;
            $ascdesc = $request->ascdesc;

            if($kode_rdklp == 'RDCM'){
                if($kode_klp == '5050'){
                    $filterdata = "*0.5";
                }else{
                    if($jenis == 'SB'){
                        $filterdata = "*0.7";
                    }else{
                        $filterdata = "*0.3";
                    }
                }
            }else{
                $filterdata = '';
            }

            if($rdkelola == "" OR $rdkelola == "all"){
                $fikelola = "";
            }else{
                $fikelola = " and a.kode_rdkelola = '$rdkelola' ";
            }

            switch($orderfilter){
                case 'nama' :
                    $orderby = "a.nama";
                break;
                case 'nama_kelola' :
                    $orderby = "c.nama";
                break;
                case 'nab_unit' :
                    $orderby = "round(isnull(b.nab_unit,0)$filterdata,4)";
                break;
                case 'spi_buku' :
                    $orderby = "round(isnull(b.spi_buku,0)$filterdata,4)";
                break;
                default :
                    $orderby = "";
                break;
            }
            if($orderby == ""){
                $order = "";
            }else{
                $order = " order by $orderby $ascdesc";
            }

            $sql = "select a.kode_rd,a.nama,round(isnull(b.nab_unit,0)$filterdata,4) as nab_unit,round(isnull(b.spi_buku,0)$filterdata,4) as spi_buku,c.nama as nama_kelola,c.gambar
            from inv_rd a
            left join (select a.kode_rd,(sum(a.h_wajar*a.jumlah)/sum(a.jumlah)) as nab_unit,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_buku
                        from inv_rd_kkp a 
                        where 
                        a.tanggal='$tgl_akhir' 
                        and a.kode_plan='$kode_plan'
                        group by a.kode_rd
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_rd=b.kode_rd
            left join inv_rdkelola c on a.kode_rdkelola=c.kode_rdkelola
            where a.kode_rdklp='$kode_rdklp' and isnull(b.nab_unit,0) <> 0 $fikelola $order ";
            $res = $this->dbResultArray($sql);

            $success["daftar"] = $res;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailRD(Request $request){
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
            $kode_fs = "FS3";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $kode_rdklp = $request->kode_rdklp;
            $jenis = $request->jenis;

            if($kode_rdklp == 'RDCM'){
                if($kode_klp == '5050'){
                    $filterdata = "*0.5";
                }else{
                    if($jenis == 'SB'){
                        $filterdata = "*0.7";
                    }else{
                        $filterdata = "*0.3";
                    }
                }
            }else{
                $filterdata = '';
            }

            if($rdkelola == "" OR $rdkelola == "all"){
                $fikelola = "";
                $fikelola2 = "";
            }else{
                $fikelola = " and a.kode_rdkelola = '$rdkelola' ";
                $fikelola2 = " and b.kode_rdkelola = '$rdkelola' ";
            }

            switch($orderfilter){
                case 'nama' :
                    $orderby = "a.nama";
                break;
                case 'nama_kelola' :
                    $orderby = "c.nama";
                break;
                case 'nab_unit' :
                    $orderby = "isnull(b.nab_unit,0)$filterdata";
                break;
                case 'spi_buku' :
                    $orderby = "isnull(b.spi_unit,0)$filterdata";
                break;
                default :
                    $orderby = "";
                break;
            }
            if($orderby == ""){
                $order = "";
            }else{
                $order = " order by $orderby $ascdesc";
            }
            
            $success = array();
            
            $success["order"] = $orderfilter."-".$orderby."-".$ascdesc;

            $res = $this->dbResultArray("select top 1 a.kode_rd,a.nama,isnull(b.nab_unit,0)$filterdata as nab_unit, isnull(b.nbuku_unit,0)$filterdata as nbuku_unit,isnull(b.spi_buku,0)$filterdata as spi_buku,c.nama as nama_kelola,isnull(b.jum_unit,0)$filterdata as jum_unit,isnull(b.nbuku,0)$filterdata as nbuku,isnull(b.spi_unit,0)$filterdata as ytd
            from inv_rd a
            left join (select a.kode_rd,sum(a.jumlah) as jum_unit,(sum(a.h_wajar*a.jumlah)/sum(a.jumlah)) as nab_unit,(sum(a.h_buku*a.jumlah)/sum(a.jumlah)) as nbuku_unit,sum(a.h_buku*a.jumlah) as nbuku,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah)) as spi_buku,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_unit
                        from inv_rd_kkp a 
                        where 
                        a.tanggal='$tgl_akhir' 
                        and a.kode_plan='$kode_plan'
                        group by a.kode_rd
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_rd=b.kode_rd
            left join inv_rdkelola c on a.kode_rdkelola=c.kode_rdkelola
            where a.kode_rdklp='$kode_rdklp' and isnull(b.jum_unit,0) <> 0 $fikelola $order");

            if($request->kode_rd ==""){
                $filter_rd = " and a.kode_rd ='".$res[0]["kode_rd"]."' ";
            }else{
                $filter_rd = " and a.kode_rd ='".$request->kode_rd."' ";
            }

            $sqlrd = "select a.kode_rd,a.nama, isnull(b.nbuku_unit,0)$filterdata as nbuku_unit,isnull(b.spi_buku,0)$filterdata as spi_buku,c.nama as nama_kelola,isnull(b.jum_unit,0)$filterdata as jum_unit,isnull(b.nbuku,0)$filterdata as nbuku, isnull(b.spi_unit,0)$filterdata as ytd
            from inv_rd a
            left join (select a.kode_rd,sum(a.jumlah) as jum_unit,(sum(a.h_buku*a.jumlah)/sum(a.jumlah)) as nbuku_unit,sum(a.h_buku*a.jumlah) as nbuku,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah)) as spi_buku,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_unit
                        from inv_rd_kkp a 
                        where 
                        a.tanggal='$tgl_akhir' 
                        and a.kode_plan='$kode_plan'
                        group by a.kode_rd
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_rd=b.kode_rd
            left join inv_rdkelola c on a.kode_rdkelola=c.kode_rdkelola
            where a.kode_rdklp='$kode_rdklp' $filter_rd  $fikelola ";
            $reksadana = dbResultArray($sqlrd);

            $success['filterx'] = $sqlrd;

            $success["daftar"] = $reksadana;

            $sql = "select a.kode_kelola,a.tanggal as tgl,
            sum(a.jumlah*a.h_wajar)$filterdata as total
            from inv_rd_kkp a 
            left join inv_rd b on a.kode_rd=b.kode_rd
            where substring(a.periode,1,4)='$tahun' and a.kode_plan='$kode_plan' and b.kode_rdklp='$kode_rdklp' $filter_rd $fikelola2
            group by a.kode_kelola,a.tanggal
            order by a.kode_kelola,a.tanggal
            ";
            $pembagi = 1000000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            if(count($rs) > 0){
                foreach ($rs as $row){
                    if($kode_rdklp == "RDPD"){
                        $result[$row->kode_kelola][] = array($row->tgl,floatval($row->total));
                    }else{
                        $result[$row->kode_kelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));
                    }
                    
                }
            }

            $sqlc = "select distinct kode_kelola
            from inv_rd_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_kelola"]] = $color[$i];
                $i++;
            }

            $sql2 = "select distinct a.kode_kelola
            from inv_rd_kkp a 
            left join inv_rd b on a.kode_rd=b.kode_rd
            where substring(a.periode,1,4)='$tahun'
            and a.kode_plan='$kode_plan' and b.kode_rdklp='$kode_rdklp' $filter_rd $fikelola2
            ";
            $res = $this->dbResultArray($sql2);
            $success["NAB"] = array();
            foreach($res as $row){
                
                $success["NAB"][] = array("type"=>"area","name" => $row["kode_kelola"],"color"=>$colors[$row["kode_kelola"]], "data" => $result[$row["kode_kelola"]],"showInLegend"=>true );
                $i++;
            
            }

            $success["ROI"] = array();
            
            $success['ket'] = "( dalam Miliar Rupiah )";
            if($kode_rdklp == 'RDPD'){
                $success['ket'] = "";

                $sql = "select a.kode_kelola,a.tanggal as tgl,
                (sum(a.jumlah*a.h_wajar)$filterdata)-(sum(a.jumlah*a.h_oleh)$filterdata) as total,(sum(a.jumlah*a.h_wajar)$filterdata)-(sum(a.jumlah*a.h_buku)$filterdata) as total2,sum(a.roi_persen) as total3
                from inv_rd_kkp a 
                left join inv_rd b on a.kode_rd=b.kode_rd
                where substring(a.periode,1,4)='$tahun'
                and a.kode_plan='$kode_plan' and b.kode_rdklp='$kode_rdklp' $filter_rd $fikelola2
                group by a.kode_kelola,a.tanggal
                order by a.kode_kelola,a.tanggal asc
                ";

                $pembagi = 1000000000;
                $rs = DB::connection($this->db)->select($sql);
                $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
                $i=0;
                if($rs->RecordCount() > 0){
                    foreach ($rs as $row){
                        $result[$row->kode_kelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));

                        $result[$row->kode_kelola."ROI"][] = array($row->tgl,floatval($row->total3));
                        
                    }
                }
    
                $sqlc = "select distinct kode_kelola
                from inv_rd_kkp
                ";
                $resc = $this->dbResultArray($sqlc);
                $i=0;
                $colors = array();
                foreach($resc as $row){
                    
                    $colors[$row["kode_kelola"]."ROI"] = $color[$i];
                    $i++;
                }
                
                $success["colors"]=$colors;

                $sql2 = "select distinct a.kode_kelola
                from inv_rd_kkp a 
                left join inv_rd b on a.kode_rd=b.kode_rd
                where substring(a.periode,1,4)='$tahun'
                and a.kode_plan='$kode_plan' and b.kode_rdklp='$kode_rdklp' $filter_rd $fikelola2
                ";
                $res = $this->dbResultArray($sql2);
               
                foreach($res as $row){
                    
                    $success["ROI"][] = array("type"=>"area","name" => $row["kode_kelola"]." ROI","color"=>$colors[$row["kode_kelola"]."ROI"], "data" => $result[$row["kode_kelola"]."ROI"],"showInLegend"=>true );
                    $i++;
                
                }
            }
         
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDOC(Request $request){
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
            $res = $this->dbRowArray("select sum(nilai_depo) as nilai from inv_depo_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' and jenis='DOC' ");
            $nilai = (isset($res->nilai) ? floatval($res->nilai) : 0);
            $success["nilai"] = $nilai;
            $success["daftar"] = $this->dbResultArray("select a.kode_kelola,a.nama,a.gambar, b.jum as nilai, case when $nilai > 0 then (b.jum/".$nilai.")*100 else 0 as persen
            from inv_kelola a
            inner join ( select kode_kelola,sum(nilai_depo) as jum from inv_depo_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan'  and jenis='DOC'
                        group by kode_kelola ) b on a.kode_kelola=b.kode_kelola");
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDepo(Request $request){
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
            $res = $this->dbRowArray("select sum(nilai_depo) as nilai from inv_depo_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan' and jenis='DOC' ");
            $nilai = (isset($res->nilai) ? floatval($res->nilai) : 0);
            $success["nilai"] = $nilai;
            $success["daftar"] = $this->dbResultArray("select a.kode_kelola,a.nama,a.gambar, b.jum as nilai, case when $nilai > 0 then (b.jum/".$nilai.")*100 else 0 as persen
            from inv_kelola a
            inner join ( select kode_kelola,sum(nilai_depo) as jum from inv_depo_kkp where tanggal = '$tgl_akhir' and kode_plan='$kode_plan'  and jenis='Deposito'
                        group by kode_kelola ) b on a.kode_kelola=b.kode_kelola");
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNABDepoHari(Request $request){
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
            $periode = $tahun.substr($tgl_akhir,5,2);

            if(isset($request->jenis) && $request->jenis != ""){
                if($request->jenis == 0){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between '".$request->tanggal[0]."' and '".$request->tanggal[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 1){
                    $filter = " where kode_plan='".$request->kode_plan."' and periode between '".$request->periode[0]."' and '".$request->periode[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                if($request->jenis == 2){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) between '".$request->tahun[0]."' and '".$request->tahun[1]."' and kode_kelola='".$request->kode_kelola."' ";
                }
                //YTD
                if($request->jenis == 3){
                    $tahun = substr($request->tanggal[0],0,4);

                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$tahun."-01-01') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."' ";
                }

                // YOY
                if($request->jenis == 4){
                    $filter = " where kode_plan='".$request->kode_plan."' and tanggal between DATEADD(YEAR, -1, '".$request->tanggal[0]."') and '".$request->tanggal[0]."' and kode_kelola='".$request->kode_kelola."'";
                }

                if($request->jenis == 5){
                    $filter = " where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($request->tanggal[0],0,4)."' and kode_kelola='".$request->kode_kelola."' ";
                }
            }else{
                $filter = "where kode_plan='".$request->kode_plan."' and substring(periode,1,4) = '".substr($periode,0,4)."' and kode_kelola='".$request->kode_kelola."' ";
            }

            $sql = "select kode_kelola,tanggal as tgl,
            sum(nilai_depo) as total
            from inv_depo_kkp $filter
            group by kode_kelola,tanggal
            order by kode_kelola,tanggal
            ";

            $pembagi = 1000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            $result = array();
            if(count($rs) > 0){
                foreach ($rs as $row){
                    $result[$row->kode_kelola][] = array($row->tgl,round(floatval($row->total),2));
                    
                }
            }

            $sqlc = "select distinct kode_kelola
            from inv_depo_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_kelola"]] = $color[$i];
                $i++;
            }

            $sql2 = "select distinct kode_kelola
            from inv_depo_kkp $filter
            ";
            $res = $this->dbResultArray($sql2);
            $success["data"] = array();
            foreach($res as $row){
                
                $success["data"][] = array("type"=>"areaspline","name" => $row["kode_kelola"],"color"=>$colors[$row["kode_kelola"]], "data" => $result[$row["kode_kelola"]],"showInLegend"=>true );
                $i++;
            
            }

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDaftarSP(Request $request){
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
            $kode_fs = "FS3";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $spkelola = $request->spkelola;
            $orderfilter = $request->orderfilter;
            $ascdesc = $request->ascdesc;

            if($spkelola == "" OR $spkelola == "all"){
                $fikelola = "";
            }else{
                $fikelola = " and a.kode_mitra = '$spkelola' ";
            }

            switch($orderfilter){
                case 'nama_kelola' :
                    $orderby = "a.nama";
                break;
                case 'nab_unit' :
                    $orderby = "isnull(b.h_wajar,0)";
                break;
                case 'spi_buku' :
                    $orderby = "isnull(b.spi_buku,0)";
                break;
                default :
                    $orderby = "";
                break;
            }
            if($orderby == ""){
                $order = "";
            }else{
                $order = " order by $orderby $ascdesc";
            }

            $sql = "select a.kode_mitra,a.nama,isnull(b.h_wajar,0) as h_wajar,round(isnull(b.spi_buku,0),4) as spi_buku,a.gambar
            from inv_mitra a
            left join (select a.kode_mitra,sum(a.h_wajar) as h_wajar,sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah) as spi,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_buku
                        from inv_sp_kkp a 
                        where a.tanggal='$tgl_akhir' and a.kode_plan='$kode_plan'
                        group by a.kode_mitra
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_mitra=b.kode_mitra
            where isnull(b.h_wajar,0) <> 0 $fikelola $order";
            $res = $this->dbResultArray($sql);

            $success["daftar"] = $res;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailSP(Request $request){
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
            $tahunsblum = intval($tahun)-1;
            $tglSblum= $tahunsblum."-12-31";
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $kode_fs = "FS3";

            $kode_mitra = $request->kode_mitra;
            $orderfilter = $request->orderfilter;
            $ascdesc = $request->ascdesc;

            if($kode_mitra == "" OR $kode_mitra == "all"){
                $fikelola = "";
            }else{
                $fikelola = " and a.kode_mitra = '$kode_mitra' ";
            }

            switch($orderfilter){
                case 'nama_kelola' :
                    $orderby = "a.nama";
                break;
                case 'nab_unit' :
                    $orderby = "isnull(b.h_wajar,0)";
                break;
                case 'spi_buku' :
                    $orderby = "isnull(b.spi_buku,0)";
                break;
                default :
                    $orderby = "";
                break;
            }
            if($orderby == ""){
                $order = "";
            }else{
                $order = " order by $orderby $ascdesc";
            }

            $sql = "select top 1 a.kode_mitra,a.nama,isnull(b.h_wajar,0) as h_wajar,round(isnull(b.spi_buku,0),4) as spi_buku
            from inv_mitra a
            left join (select a.kode_mitra,sum(a.h_wajar) as h_wajar,sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah) as spi,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_buku
                        from inv_sp_kkp a 
                        where a.tanggal='$tgl_akhir' and a.kode_plan='$kode_plan'
                        group by a.kode_mitra
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_mitra=b.kode_mitra
            where isnull(b.h_wajar,0) <> 0  $fikelola $order";
            $mitra = $this->dbResultArray($sql);
            if($kode_mitra == "" OR $kode_mitra == "all"){
                $kode_mitra = $mitra[0]["kode_mitra"];
            }else{
                $kode_mitra = $kode_mitra;
            }

            $sqltotal = "select sum(a.h_wajar*a.jumlah) as total from inv_sp_kkp a where a.tanggal='$tgl_akhir' and a.kode_plan='$kode_plan'   ";
            $sptot = $this->dbRowArray($sqltotal);

            $sqlsp = "select a.kode_mitra,a.nama, isnull(b.nbuku_unit,0) as nbuku_unit,round(isnull(b.spi_buku,0),4) as spi_buku,isnull(b.jum_unit,0) as jum_unit,isnull(b.nwajar,0) as nwajar,".round($sptot["total"])." as total,(isnull(b.nwajar,0)/".round($sptot["total"]).")*100 as persen_sp,isnull(b.spi_unit,0) as ytd
            from inv_mitra a
            left join (select a.kode_mitra,sum(a.jumlah) as jum_unit,(sum(a.h_buku*a.jumlah)/sum(a.jumlah)) as nbuku_unit,sum(a.h_wajar*a.jumlah) as nwajar,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah)) as spi_buku,(sum(a.h_wajar*a.jumlah)-sum(a.h_buku*a.jumlah))/sum(a.h_wajar*a.jumlah)*100 as spi_unit
                        from inv_sp_kkp a 
                        where 
                        a.tanggal='$tgl_akhir' 
                        and a.kode_plan='$kode_plan'
                        group by a.kode_mitra
                        having sum(a.h_wajar*a.jumlah) <> 0 and sum(a.jumlah) <> 0
                       ) b on a.kode_mitra=b.kode_mitra
            where a.kode_mitra='$kode_mitra'  ";
            $sp = $this->dbResultArray($sqlsp);

            $success["daftar"] = $sp;

            $sql = "select a.kode_spkelola,a.tanggal as tgl,
            sum(a.jumlah*a.h_wajar) as total
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun' and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra'
            group by a.kode_spkelola,a.tanggal
            order by a.kode_spkelola,a.tanggal
            ";
            $pembagi = 1000000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            if(count($rs) > 0){
                foreach ($rs as $row){
                    $result[$row->kode_spkelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));
                    
                }
            }

            $sqlc = "select distinct kode_spkelola
            from inv_sp_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                $colors[$row["kode_spkelola"]] = $color[$i];
                $i++;
            }

            $sql2 = "select distinct a.kode_spkelola
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun'
            and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra'";
            $res = $this->dbResultArray($sql2);
            $success["NAB"] = array();
            foreach($res as $row){
                
                $success["NAB"][] = array("type"=>"area","name" => $row["kode_spkelola"],"color"=>$colors[$row["kode_spkelola"]], "data" => $result[$row["kode_spkelola"]],"showInLegend"=>true );
                $i++;
            
            }

            $sql = "select a.kode_spkelola,a.tanggal as tgl,
            (sum(a.jumlah*a.h_wajar))-(sum(a.jumlah*a.h_oleh)) as total,(sum(a.jumlah*a.h_wajar))-(sum(a.jumlah*a.h_buku)) as total2
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun'
            and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra' 
            group by a.kode_spkelola,a.tanggal
            order by a.kode_spkelola,a.tanggal asc
            ";

            $pembagi = 1000000000;
            $rs = DB::connection($this->db)->select($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            if(count($rs) > 0){
                foreach ($rs as $row){
                    $result[$row->kode_spkelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));
                    $result[$row->kode_spkelola."SPI_Buku"][] = array($row->tgl,round(floatval($row->total2)/$pembagi,2));
                    
                }
            }
 
            $sqlc = "select distinct kode_spkelola
            from inv_sp_kkp
            ";
            $resc = $this->dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                
                $colors[$row["kode_spkelola"]] = $color[$i];
                $colors[$row["kode_spkelola"]."SPI_Buku"] = $color[$i+1];
                $i++;
            }
            
            $success["colors"]=$colors;

            $sql2 = "select distinct a.kode_spkelola
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun'
            and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra' ";
            $res = $this->dbResultArray($sql2);
            $success["SPI"] = array();
            foreach($res as $row){
                
                $success["SPI"][] = array("type"=>"area","name" => $row["kode_spkelola"]." SPI Perolehan","color"=>$colors[$row["kode_spkelola"]], "data" => $result[$row["kode_spkelola"]],"showInLegend"=>true,"visible"=> false );
                $success["SPI"][] = array("type"=>"area","name" => $row["kode_spkelola"]." SPI Buku","color"=>$colors[$row["kode_spkelola"]."SPI_Buku"], "data" => $result[$row["kode_spkelola"]."SPI_Buku"],"showInLegend"=>true );
                $i++;
            
            }

            $success["daftar"] = $res;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    // SKIP SEBAGIAN LANGSUNG KE DASHBOARD BARU

    public function getBMark(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $tahunLalu= intval($tahun)-1;
            $periode = $tahun.substr($tgl_akhir,5,2);
            $periodeLalu = $tahunLalu.substr($tgl_akhir,5,2);

            $warna =array('#FF2D55','#42B9FE','#5856d6','#4CD964');

            $filter = "where periode between '".$periodeLalu."' and '$periode' ";

            $sql = "select tanggal as tgl,
            bindo as total
            from inv_bmark $filter
            order by tanggal
            ";
            $rs=DB::connection($this->db)->select($sql);

            $result['bindo'] = array();
            if(count($rs) > 0){
                foreach ($rs as $row){
                    $result['bindo'][] = array($row->tgl,floatval($row->total));
                }
            }

            $success["data"][0] = array("type"=>"spline","name" => 'BINDO', "color"=>$warna[0],"data" => $result['bindo'],"showInLegend"=>true,"turboThreshold"=>5000 );

            $sql = "select tanggal as tgl,
            ihsg as total
            from inv_bmark $filter
            order by tanggal
            ";
            $rs2=DB::connection($this->db)->select($sql);

            if(count($rs2) > 0){
                foreach ($rs2 as $row){
                    $result['jci'][] = array($row->tgl,floatval($row->total));
                }
            }

            $success["data"][1] = array("type"=>"spline","yAxis"=>1,"name" => 'JCI', "color"=>$warna[1],"data" => $result['jci'],"showInLegend"=>true,"turboThreshold"=>5000 );
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function simpanFilterKolom(Request $request){
        $this->validate($request, [
            'kolom1' => 'required',
            'kolom2' => 'required',
            'kolom3' => 'required',
            'kolom4' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
           
            $del = DB::connection($this->sql)->table('inv_filter_kolom')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik', $request->nik)
            ->delete();
            
            $ins = DB::connection($this->sql)->insert("insert into inv_filter_kolom (kode_lokasi,kode_kolom,kode_form,isi_kolom,nik,flag_grafik) values ('$kode_lokasi','k1','ALOKASI','".$request->kolom1."','$nik_user','0'),('$kode_lokasi','k2','ALOKASI','".$request->kolom2."','$nik_user','0'),('$kode_lokasi','k3','ALOKASI','".$request->kolom3."','$nik_user','0'),('$kode_lokasi','k4','ALOKASI','".$request->kolom4."','$nik_user','0'); ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Filter berhasil diubah";
            
            return response()->json($success, $this->successStatus);                 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getTableAlokasi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $tahunLalu= intval($tahun)-1;
            $periode = $tahun.substr($tgl_akhir,5,2);
            $periodeLalu = $tahunLalu.substr($tgl_akhir,5,2);

            $cek = DB::connection($this->db)->select("select kode_kolom,isi_kolom,flag_grafik from inv_filter_kolom where kode_lokasi='$kode_lokasi' and nik='$nik' and kode_form='ALOKASI' ");
            if(count($cek) > 0){
                foreach($cek as $r){
                    $kolom[] = (array) $r;
                }
                $kol1 = $kolom[0]["isi_kolom"];
                $kol2 = $kolom[1]["isi_kolom"];
                $kol3 = $kolom[2]["isi_kolom"];
                $kol4 = $kolom[3]["isi_kolom"];
            }else{
                $kol1 = '';
                $kol2 = '';
                $kol3 = '';
                $kol4 = '';
            }

            if($kol1 != ""){
                switch(substr($kol1,4,2)){
                    case "Q1" :
                        $select = "round(a.nab_bulan,0) as c1";
                        $fil1 = "where a.periode = '".substr($kol1,0,4)."03' and b.tahun='".substr($kol1,0,4)."' ";
                    break;
                    case "Q2" :
                        $select = "round(a.nab_bulan,0) as c1";
                        $fil1 = "where a.periode = '".substr($kol1,0,4)."06' and b.tahun='".substr($kol1,0,4)."' ";
                    break;
                    case "Q3" :
                        $select = "round(a.nab_bulan,0) as c1";
                        $fil1 = "where a.periode = '".substr($kol1,0,4)."09' and b.tahun='".substr($kol1,0,4)."' ";
                    break;
                    case "Q4" :
                        $select = "round(a.nab_bulan,0) as c1";
                        $fil1 = "where a.periode = '".substr($kol1,0,4)."12' and b.tahun='".substr($kol1,0,4)."' ";
                    break;
                    default :
                        $select = "round(a.nab_bulan,0) as c1";
                        $fil1 = "where a.periode ='$kol1' and b.tahun='".substr($kol1,0,4)."' ";
                    break;
                }

                $success['label1'] = $kol1;
                $sql ="select distinct a.kode_kelas,b.nama, $select,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                $fil1 and a.kode_plan='$kode_plan'
                order by b.nu";
            }else{
                $sql ="select distinct a.kode_kelas,b.nama, round(a.sawal,0) as c1,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                where a.periode ='$periode' and b.tahun='$tahun' and a.kode_plan='$kode_plan'
                order by b.nu";
                
                $success['label1'] = $tahunLalu."12";            

            }

            $success["data1"] = $this->dbResultArray($sql);

            if($kol2 != ""){
                switch(substr($kol2,4,2)){
                    case "Q1" :
                        $select = "round(a.nab_bulan,0) as c2";
                        $fil2 = "where a.periode = '".substr($kol2,0,4)."03' and b.tahun='".substr($kol2,0,4)."' ";
                    break;
                    case "Q2" :
                        $select = "round(a.nab_bulan,0) as c2";
                        $fil2 = "where a.periode = '".substr($kol2,0,4)."06' and b.tahun='".substr($kol2,0,4)."' ";
                    break;
                    case "Q3" :
                        $select = "round(a.nab_bulan,0) as c2";
                        $fil2 = "where a.periode = '".substr($kol2,0,4)."09' and b.tahun='".substr($kol2,0,4)."' ";
                    break;
                    case "Q4" :
                        $select = "round(a.nab_bulan,0) as c2";
                        $fil2 = "where a.periode = '".substr($kol2,0,4)."12' and b.tahun='".substr($kol2,0,4)."' ";
                    break;
                    default :
                        $select = "round(a.nab_bulan,0) as c2";
                        $fil2 = "where a.periode ='$kol2' and b.tahun='".substr($kol2,0,4)."' ";
                    break;
                }
                
                
                $success['label2'] = $kol2;
                $sql2 ="select distinct a.kode_kelas,b.nama, $select,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                $fil2 and a.kode_plan='$kode_plan'
                order by b.nu";
            }else{
                
                $success['label2'] = $tahun."Q1";
                $sql2 ="select distinct a.kode_kelas,b.nama, round(a.nab_bulan,0) as c2,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                where a.periode ='".$tahun."03' and b.tahun='$tahun' and a.kode_plan='$kode_plan'
                order by b.nu";
            }
            $success["data2"] = $this->dbResultArray($sql2);

            if($kol3 != ""){
                switch(substr($kol3,4,2)){
                    case "Q1" :
                        $select = "round(a.nab_bulan,0) as c3";
                        $fil3 = "where a.periode = '".substr($kol3,0,4)."03' and b.tahun='".substr($kol3,0,4)."' ";
                    break;
                    case "Q2" :
                        $select = "round(a.nab_bulan,0) as c3";
                        $fil3 = "where a.periode = '".substr($kol3,0,4)."06' and b.tahun='".substr($kol3,0,4)."' ";
                    break;
                    case "Q3" :
                        $select = "round(a.nab_bulan,0) as c3";
                        $fil3 = "where a.periode = '".substr($kol3,0,4)."09' and b.tahun='".substr($kol3,0,4)."' ";
                    break;
                    case "Q4" :
                        $select = "round(a.nab_bulan,0) as c3";
                        $fil3 = "where a.periode = '".substr($kol3,0,4)."12' and b.tahun='".substr($kol3,0,4)."' ";
                    break;
                    default :
                        $select = "round(a.nab_bulan,0) as c3";
                        $fil3 = "where a.periode ='$kol3' and b.tahun='".substr($kol3,0,4)."' ";
                    break;
                }
                
                $success['label3'] = $kol3;
                $sql3 ="select distinct a.kode_kelas,b.nama, $select,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                $fil3 and a.kode_plan='$kode_plan'
                order by b.nu";
            }else{
                
                $success['label3'] = $tahun."Q2";
                $sql3 ="select distinct a.kode_kelas,b.nama, round(a.nab_bulan,0) as c3,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                where a.periode ='".$tahun."06' and b.tahun='$tahun' and a.kode_plan='$kode_plan'
                order by b.nu";
            }

            $success["data3"] = $this->dbResultArray($sql3);
            
            if($kol4 != ""){
                switch(substr($kol4,4,2)){
                    case "Q1" :
                        $select = "round(a.nab_bulan,0) as c4";
                        $fil4 = "where a.periode = '".substr($kol4,0,4)."03' and b.tahun='".substr($kol4,0,4)."' ";
                    break;
                    case "Q2" :
                        $select = "round(a.nab_bulan),0) as c4";
                        $fil4 = "where a.periode = '".substr($kol4,0,4)."06' and b.tahun='".substr($kol4,0,4)."' ";
                    break;
                    case "Q3" :
                        $select = "round(a.nab_bulan,0) as c4";
                        $fil4 = "where a.periode = '".substr($kol4,0,4)."09' and b.tahun='".substr($kol4,0,4)."' ";
                    break;
                    case "Q4" :
                        $select = "round(a.nab_bulan,0) as c4";
                        $fil4 = "where a.periode = '".substr($kol4,0,4)."12' and b.tahun='".substr($kol4,0,4)."' ";
                    break;
                    default :
                        $select = "round(a.nab_bulan,0) as c4";
                        $fil4 = "where a.periode ='$kol4' and b.tahun='".substr($kol4,0,4)."' ";
                    break;
                }
                
                
                $success['label4'] = $kol4;
                $sql4 ="select distinct a.kode_kelas,b.nama, $select,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                $fil4 and a.kode_plan='$kode_plan'
                order by b.nu";
            }else{
                
                $success['label4'] = $tahun."Q3";
                $sql4 ="select distinct a.kode_kelas,b.nama, round(a.nab_bulan,0) as c4,b.nu
                from inv_kelas_dash a 
                left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
                where a.periode ='".$tahun."09' and b.tahun='$tahun' and a.kode_plan='$kode_plan'
                order by b.nu";
            }

            $success["data4"] = $this->dbResultArray($sql4);

            $success['label5'] = $periode;
            
            $sqlt="select round(sum(a.sawal),0) as c1,round(sum(a.nab_bulan),0) as nab_now
            from(
            select distinct a.sawal,c.nab as nab_bulan
            from inv_kelas_dash a 
            left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
			left join inv_roi_beban c on a.kode_kelas=c.modul and c.tanggal='$tgl_akhir'
            where a.periode='$periode'  and b.tahun='$tahun' and a.kode_plan='$kode_plan'
            ) a ";
            
            $success["total"] =$this->dbResultArray($sqlt);

            $sql5="select a.kode_kelas,b.nama,round(a.sawal,0) as c1,round(c.nab,0) as nab_now,a.bawah,a.acuan,a.atas,b.nu 
            from inv_kelas_dash a 
            left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas and a.kode_plan=b.kode_plan  
			left join inv_roi_beban c on a.kode_kelas=c.modul and c.tanggal='$tgl_akhir'
            where a.periode = '$periode' and b.tahun='$tahun' and a.kode_plan='$kode_plan'
            order by b.nu";

            $success["data5"] = $this->dbResultArray($sql5);
            $success['series1'] = array();
            $success['series2'] = array();
            
            $c1=0;$c2=0;$c3=0;$c4=0;$sawal=0;
            $i=0;
            foreach($success['data5'] as $row){

                array_push($success['series1'],round(floatval($row["c1"])/1000000000000,2));
                array_push($success['series2'],round(floatval($row["nab_now"])/1000000000000,2));
                $sawal += $row["c1"];
                $c1 += (isset($success["data1"][$i]["c1"]) ? $success["data1"][$i]["c1"] : 0);
                $c2 += (isset($success["data2"][$i]["c2"]) ? $success["data2"][$i]["c2"] : 0);
                $c3 += (isset($success["data3"][$i]["c3"]) ? $success["data3"][$i]["c3"] : 0);
                $c4 += (isset($success["data4"][$i]["c4"]) ? $success["data4"][$i]["c4"] : 0);
                $i++;
            }

            $success["total"][0]["sawal"] = $sawal;
            $success["total"][0]["c1"] = $c1;
            $success["total"][0]["c2"] = $c2;
            $success["total"][0]["c3"] = $c3;
            $success["total"][0]["c4"] = $c4;

            array_push($success['series1'],round(floatval($success["total"][0]["sawal"])/1000000000000,2));
            array_push($success['series2'],round(floatval($success["total"][0]["nab_now"])/1000000000000,2));
            $success['sql']=$sql;
            $success['sql2']=$sql2;
            $success['sql3']=$sql3;
            $success['sql4']=$sql4;
            $success['sql5']=$sql5;
            $success['name2']=$periode;
            $success['name1']=$tahunLalu."12";
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getRealHasil(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);

            $sql = "select distinct a.kode_kelas,b.nama,a.rka_pdpt,a.rka_spi, a.rka_pdpt+a.rka_spi as rka_gross,a.rka_beban,(a.rka_pdpt+a.rka_spi)-rka_beban as rka_net,a.pdpt,a.spi,beban,a.pdpt+a.spi-a.beban as net,case when a.rka_pdpt <> 0 then ((a.pdpt-a.rka_pdpt)/abs(a.rka_pdpt)*100)+100 else 0 end as real_pdpt,(((a.beban-a.rka_beban)/abs(a.rka_beban))*100)+100 as real_beban,
            ((((a.pdpt+a.spi-a.beban)-((rka_pdpt+rka_spi)-rka_beban))/abs((rka_pdpt+rka_spi)-rka_beban))*100)+100 as real_net ,b.nu
                        from inv_kelas_dash a
                        left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas
                        where a.periode='$periode' and b.tahun='$tahun'
                        order by b.nu ";

            $success["daftar"] = $this->dbResultArray($sql);
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getROIReal(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);

            $sql = "select distinct a.kode_kelas,b.nama,a.roi_rka,a.roi_kelas as real_rka,case when a.roi_rka = 0 then 0 else (((a.roi_kelas-a.roi_rka)/abs(a.roi_rka))+1)*100 end as rka_capai,a.roi_bmark,a.roi_kelas as real_bmark,case when a.roi_bmark = 0 then 0 else (((a.roi_kelas-a.roi_bmark)/abs(a.roi_bmark))+1)*100 end as bmark_capai,a.acuan,b.nu
            from inv_kelas_dash a
            left join inv_batas_alokasi b on a.kode_kelas=b.kode_kelas
            where a.periode='$periode' and a.kode_plan='$kode_plan' and b.tahun='$tahun'
            order by b.nu ";
            $success["daftar"] = $this->dbResultArray($sql);

            $sql2 = "select roi_totrka,roi_tot,roi_totbmark
            from  inv_kelas_dash where kode_plan='$kode_plan' and periode='$periode' ";
            $success["total"] = $this->dbResultArray($sql2);

            $sql = "select nilai
            from inv_rka a
            where a.kode_kelas='ROITOTAL' and a.modul='ROI' and periode='$periode' and kode_plan='$kode_plan'";
            $success["total_roi"] = $this->dbResultArray($sql);
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getPlanAset(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $sql = "
            with  CTE as
                    (
                    select  datepart(year, '$tahunAwal-12-31') as yr
                    union all
                    select  yr +1
                    from    CTE
                    where   yr < datepart(year, '$tahunAkhir-12-31')
                    )
            select  convert(varchar(4),yr) as yr
            from    CTE
            union all
            select convert(varchar(3),DATENAME(month, '$tgl_akhir'))+'-'+substring(convert(varchar(4),year('$tgl_akhir')),3,2) as yr
            ";

            $category = DB::connection($this->db)->select($sql);
            $d=array();
            $tgl = array();
            $i=0;
            foreach($category as $row){
                array_push($d,$row->yr);
                if($i==10){
                    array_push($tgl,$tgl_akhir);
                }
                else{
                    array_push($tgl,$row->yr.'-12-31');
                }
                $i++;
            }

            $success["category"] = $d;

            $sql2 = "
            select 'plan' as kode,
                   sum(case when tanggal = '".$tgl[0]."' then nab/1000000000000 else 0 end) as n1,
                   sum(case when tanggal = '".$tgl[1]."' then nab/1000000000000 else 0 end) as n2,
                   sum(case when tanggal = '".$tgl[2]."' then nab/1000000000000 else 0 end) as n3,
                   sum(case when tanggal = '".$tgl[3]."' then nab/1000000000000 else 0 end) as n4,
                   sum(case when tanggal = '".$tgl[4]."' then nab/1000000000000 else 0 end) as n5,
                   sum(case when tanggal = '".$tgl[5]."' then nab/1000000000000 else 0 end) as n6,
                   sum(case when tanggal = '".$tgl[6]."' then nab/1000000000000 else 0 end) as n7,
                   sum(case when tanggal = '".$tgl[7]."' then nab/1000000000000 else 0 end) as n8,
                   sum(case when tanggal = '".$tgl[8]."' then nab/1000000000000 else 0 end) as n9,
                   sum(case when tanggal = '".$tgl[9]."' then nab/1000000000000 else 0 end) as n10,
                   sum(case when tanggal = '".$tgl[10]."' then nab/1000000000000 else 0 end) as n13
            from inv_roi_total
            where kode_plan='$kode_plan'
            union all
            select 'jci' as kode,
                sum(case when tanggal = '".$tgl[0]."' then ihsg else 0 end) as n1,
                sum(case when tanggal = '".$tgl[1]."' then ihsg else 0 end) as n2,
                sum(case when tanggal = '".$tgl[2]."' then ihsg else 0 end) as n3,
                sum(case when tanggal = '".$tgl[3]."' then ihsg else 0 end) as n4,
                sum(case when tanggal = '".$tgl[4]."' then ihsg else 0 end) as n5,
                sum(case when tanggal = '".$tgl[5]."' then ihsg else 0 end) as n6,
                sum(case when tanggal = '".$tgl[6]."' then ihsg else 0 end) as n7,
                sum(case when tanggal = '".$tgl[7]."' then ihsg else 0 end) as n8,
                sum(case when tanggal = '".$tgl[8]."' then ihsg else 0 end) as n9,
                sum(case when tanggal = '".$tgl[9]."' then ihsg else 0 end) as n10,
                sum(case when tanggal = '".$tgl[10]."' then ihsg else 0 end) as n13
            from inv_bmark
            union all
            select 'kewajiban' as kode,
                sum(case when tahun = '".$d[0]."12' then persen else 0 end) as n1,
                sum(case when tahun = '".$d[1]."12' then persen else 0 end) as n2,
                sum(case when tahun = '".$d[2]."12' then persen else 0 end) as n3,
                sum(case when tahun = '".$d[3]."12' then persen else 0 end) as n4,
                sum(case when tahun = '".$d[4]."12' then persen else 0 end) as n5,
                sum(case when tahun = '".$d[5]."12' then persen else 0 end) as n6,
                sum(case when tahun = '".$d[6]."12' then persen else 0 end) as n7,
                sum(case when tahun = '".$d[7]."12' then persen else 0 end) as n8,
                sum(case when tahun = '".$d[8]."12' then persen else 0 end) as n9,
                sum(case when tahun = '".$d[9]."12' then persen else 0 end) as n10,
                sum(case when tahun = '".$periode."' then persen else 0 end) as n13
            from inv_aktuaria
            where kode_plan='$kode_plan'
            ";

            $res =  DB::connection($this->db)->select($sql2);
            $dt[0] = array();
            $dt[1] = array();
            $dt[2] = array();
            $i=0;
            foreach($res as $row){
                
                array_push($dt[$i],floatval($row->n1));
                array_push($dt[$i],floatval($row->n2));
                array_push($dt[$i],floatval($row->n3));
                array_push($dt[$i],floatval($row->n4));
                array_push($dt[$i],floatval($row->n5));
                array_push($dt[$i],floatval($row->n6));
                array_push($dt[$i],floatval($row->n7));
                array_push($dt[$i],floatval($row->n8));
                array_push($dt[$i],floatval($row->n9));
                array_push($dt[$i],floatval($row->n10));
                array_push($dt[$i],floatval($row->n13));
                $i++;
            }
            
            $success["series"][0]= array(
                "name"=>"Plan Aset", "type"=>"column","color"=>"#66ff33","yAxis"=>1, "data"=>$dt[0],
                "dataLabels" => array("color"=>'black',"verticalAlign"=>"top")
            );

            $success["series"][1] = array(
                "name"=>"JCI", "type"=>"line","color"=>"red","data"=>$dt[1],
                "dataLabels" => array("color"=>'red')
            );

            $success["series"][2]= array(
                "name"=>"Kewajiban Aktuaria", "yAxis"=>1,"type"=>"line","color"=>"#4274FE","data"=>$dt[2],
                "dataLabels" => array("color"=>'#4274FE')
            );  

            $sql = "select * from inv_aktuaria where tahun not like '$tahun%' and substring(tahun,5,2)='12'
            union 
            select * from inv_aktuaria where tahun = '$periode' 
            order by tahun ";
            $res2 =  DB::connection($this->db)->select($sql);
            
            $df = array();
            $rkd = array();
            foreach($res2 as $row){
                array_push($df,$row->df);
                array_push($rkd,$row->rkd);   
            }
            $success['df'] =$df;
            $success['rkd'] =$rkd;    
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKinerja(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $sql = "select kode_kelas,roi_bmark from inv_kelas_dash where periode ='$periode' and kode_kelas='SB' and kode_plan='$kode_plan'";
            $resjci = $this->dbRowArray($sql);
            $jci = (isset($resjci->roi_bmark) ? floatval($resjci->roi_bmark) : 0);

            $sql = " select * from (
            select a.kode_kelola as kode,b.nama,a.nab/1000000000 as nab,a.roi_persen 
            from inv_sahammi_kkp a 
            left join inv_kelola b on a.kode_kelola=b.kode_kelola
            where a.periode ='$periode' and a.tanggal ='$tgl_akhir' and a.kode_plan='$kode_plan'
            union all
            select a.kode_rd as kode,a.nama,round(b.jumlah*h_wajar,0)/1000000000 as nab,b.roi_persen 
            from inv_rd a
            inner join inv_rd_kkp b on a.kode_rd=b.kode_rd and b.kode_plan='$kode_plan' and b.tanggal='$tgl_akhir'
            where a.kode_rdklp in ('RDSH') and round(b.jumlah*h_wajar,0)>0 
            ) a order by a.roi_persen desc
            ";

            $res = DB::connection($this->db)->select($sql);
            $dt[0] = array();
            $dt[1] = array();
            $dt[2] = array();
            $category = array();
            $i=0;
            foreach($res as $row){
                
                array_push($dt[0],floatval($row->nab));
                array_push($dt[1],floatval($row->roi_persen));
                array_push($dt[2],$jci);
                array_push($category,$row->nama);
                $i++;
            }

            $success["series"][0]= array(
                "name"=> 'Nilai Wajar', "type"=>'column',"colorByPoint"=>true,"data"=>$dt[0],"dataLabels"=> array(
                    "color"=> 'black',
                    "verticalAlign"=>'top',
                )           
            );

            $success["series"][1] = array(
                "name"=> 'Kinerja', "type"=>'line',"color"=>'blue',"yAxis"=>1,"data"=>$dt[1],"dataLabels"=> array(
                    "color"=> 'blue',
                )   
            );

            $success["series"][2]= array(
                "name"=> 'JCI', "type"=>'spline',"color"=>'red',"yAxis"=>1,"data"=>$dt[2],"dashStyle"=>'dash',"dataLabels"=> array(
                    "color"=> 'red',
                )   
            );  

            $success['category']= $category;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKinerjaETF(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $sql = "select kode_kelas,roi_bmark from inv_kelas_dash where periode ='$periode' and kode_kelas='SB' and kode_plan='$kode_plan'";
            
            $resjci = $this->dbRowArray($sql);
            $jci = (isset($resjci->roi_bmark) ? floatval($resjci->roi_bmark) : 0);


            $sql = " select * from (
                select a.kode_rd as kode,a.nama,round(b.jumlah*h_wajar,0)/1000000000 as nab,b.roi_persen 
                from inv_rd a
                inner join inv_rd_kkp b on a.kode_rd=b.kode_rd and b.kode_plan='$kode_plan' and b.tanggal='$tgl_akhir'
                where a.kode_rdklp in ('RETF') and round(b.jumlah*h_wajar,0)>0 
                ) a order by a.roi_persen desc
                ";

            $res = DB::connection($this->db)->select($sql);
            $dt[0] = array();
            $dt[1] = array();
            $dt[2] = array();
            $category = array();
            $i=0;
            foreach($res as $row){
                
                array_push($dt[0],floatval($row->nab));
                array_push($dt[1],floatval($row->roi_persen));
                array_push($dt[2],$jci);
                array_push($category,$row->nama);
                $i++;
            }
            
            $success["series"][0]= array(
                "name"=> 'Nilai Wajar', "type"=>'column',"colorByPoint"=>true,"data"=>$dt[0],"dataLabels"=> array(
                    "color"=> 'black',
                    "verticalAlign"=>'top',
                )           
            );

            $success["series"][1] = array(
                "name"=> 'Kinerja', "type"=>'line',"color"=>'blue',"yAxis"=>1,"data"=>$dt[1],"dataLabels"=> array(
                    "color"=> 'blue',
                )   
            );

            $success["series"][2]= array(
                "name"=> 'JCI', "type"=>'spline',"color"=>'red',"yAxis"=>1,"data"=>$dt[2],"dashStyle"=>'dash',"dataLabels"=> array(
                    "color"=> 'red',
                )   
            );  

            $success['category']= $category;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKinerjaBindo(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $sql = "select kode_kelas,roi_bmark from inv_kelas_dash where periode ='$periode' and kode_kelas='EBT' and kode_plan='$kode_plan'";
            
            $resjci = $this->dbRowArray($sql);
            $jci = (isset($resjci->roi_bmark) ? floatval($resjci->roi_bmark) : 0);


            $sql = "select a.kode_rd,a.nama,round(b.jumlah*h_wajar,0)/1000000000 as nab,b.roi_persen from inv_rd a
            inner join inv_rd_kkp b on a.kode_rd=b.kode_rd and b.kode_plan='$kode_plan' and b.tanggal='$tgl_akhir'
            where a.kode_rdklp='RDPD' and round(b.jumlah*h_wajar,0)>0
            order by b.roi_persen desc
            ";

            $res = DB::connection($this->db)->select($sql);
            $dt[0] = array();
            $dt[1] = array();
            $dt[2] = array();
            $category = array();
            $i=0;
            foreach($res as $row){
                
                array_push($dt[0],floatval($row->nab));
                array_push($dt[1],floatval($row->roi_persen));
                array_push($dt[2],$jci);
                array_push($category,$row->nama);
                $i++;
            }
            
            $success["series"][0]= array(
                "name"=> 'Nilai Wajar', "type"=>'column',"colorByPoint"=>true,"data"=>$dt[0],"dataLabels"=> array(
                    "color"=> 'black',
                    "verticalAlign"=>'top',
                )           
            );

            $success["series"][1] = array(
                "name"=> 'Kinerja', "type"=>'line',"color"=>'blue',"yAxis"=>1,"data"=>$dt[1],"dataLabels"=> array(
                    "color"=> 'blue',
                )   
            );

            $success["series"][2]= array(
                "name"=> 'JCI', "type"=>'spline',"color"=>'red',"yAxis"=>1,"data"=>$dt[2],"dashStyle"=>'dash',"dataLabels"=> array(
                    "color"=> 'red',
                )   
            );  

            $success['category']= $category;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKinerjaBMark(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $sql = "select kode_kelas,roi_bmark from inv_kelas_dash where periode ='$periode' and kode_kelas='SB' and kode_plan='$kode_plan'";
            $resjci = $this->dbRowArray($sql);

            $sql = "select kode_kelas,roi_bmark from inv_kelas_dash where periode ='$periode' and kode_kelas='EBT' and kode_plan='$kode_plan'";
            $resbindo = $this->dbRowArray($sql);

            if($kode_klp == "5050"){
                $bagi1 = 50/100; 
                $bagi2 = 50/100; 
            }else if($kode_klp == "3070"){
                $bagi1 = 30/100; 
                $bagi2 = 70/100; 
            }
            $jci = (isset($resjci->roi_bmark) ? floatval($resjci->roi_bmark) : 0);
            $bindo = (isset($resbindo->roi_bmark) ? floatval($resbindo->roi_bmark) : 0);
            $bc = ($jci*$bagi1) + ($bindo*$bagi2); 


            $sql = "select a.kode_rd,a.nama,round(b.jumlah*h_wajar,0)/1000000000 as nab,b.roi_persen from inv_rd a
            inner join inv_rd_kkp b on a.kode_rd=b.kode_rd and b.kode_plan='$kode_plan' and b.tanggal='$tgl_akhir'
            where a.kode_rdklp='RDCM' and round(b.jumlah*h_wajar,0)>0
            order by b.roi_persen desc
            ";

            $res = DB::connection($this->db)->select($sql);
            $dt[0] = array();
            $dt[1] = array();
            $dt[2] = array();
            $category = array();
            $i=0;
            foreach($res as $row){
                
                array_push($dt[0],floatval($row->nab));
                array_push($dt[1],floatval($row->roi_persen));
                array_push($dt[2],floatval($bc));
                array_push($category,$row->nama);
                $i++;
            }
            
            $success["series"][0]= array(
                "name"=> 'Nilai Wajar', "type"=>'column',"colorByPoint"=>true,"data"=>$dt[0],"dataLabels"=> array(
                    "color"=> 'black',
                    "verticalAlign"=>'top',
                )           
            );

            $success["series"][1] = array(
                "name"=> 'Kinerja', "type"=>'line',"color"=>'blue',"yAxis"=>1,"data"=>$dt[1],"dataLabels"=> array(
                    "color"=> 'blue',
                )   
            );

            $success["series"][2]= array(
                "name"=> 'JCI', "type"=>'spline',"color"=>'red',"yAxis"=>1,"data"=>$dt[2],"dashStyle"=>'dash',"dataLabels"=> array(
                    "color"=> 'red',
                )   
            );  

            $success['category']= $category;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getCashOut(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];

            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-10;
            $tahunAkhir = intval($tahun)-1;

            $par = "b1";
            $pas = intval(substr($periode,4,2))+1;
            for($i=2;$i<$pas;$i++){
                $par.="+b$i";
            }


            $sql = "select sum(case when jenis='BINVES' then $par else 0 end ) as binves,
            sum(case when jenis='BKES' then $par else 0 end ) as bkes,
            sum(case when jenis='CAPEX' then $par else 0 end ) as capex, 
            sum(case when jenis='CC' then $par else 0 end ) as cc, 
            sum(case when jenis='OPEX' then $par else 0 end ) as opex, 
            sum(case when jenis='PDPT' then $par else 0 end ) as pdpt,
            sum(case when jenis='SPI' then $par else 0 end ) as spi    
            from inv_beban_inves
            where tahun='$tahun'
            ";
            $success['daftar']= $this->dbResultArray($sql);
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }
    
    public function getGlobalIndex(Request $request){
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
            $tahunLalu = intval($tahun)-1;
            $periode2 = $tahun.substr($tgl_akhir,5,2);
            $periode1 = $tahunLalu."12";
            $bulan = substr($periode2,4,2);

            $sql = "select distinct kode_bmark, nama, nu,sum(case when periode = '$periode1' then nilai else 0 end ) as nilai1,sum(case when periode = '$periode2' then nilai else 0 end ) as nilai2 
            from inv_index_d
            where kode_lokasi='$kode_lokasi' and jenis_index='GLOBAL'
            group by kode_bmark,nama,nu
            order by nu
            ";
            $success['daftar']= $this->dbResultArray($sql);

            $sql = "select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'$tahun-$bulan-01')+1,0)) ,112),7,2) as tglakhir ";
            $cek = DB::connection($this->db)->select($sql);
            if(count($cek)>0){
                $tgl = $tahun."-".$bulan."-".$cek->fields[0];
                $sql = "select ihsg,dwjg,sgc,hsi,nikkei,lq45 from inv_bmark where tanggal ='$tahunLalu-12-31' ";
                $success['nil1'] = $this->dbResultArray($sql);
                $sql = "select ihsg,dwjg,sgc,hsi,nikkei,lq45 from inv_bmark where tanggal ='$tgl' ";
                $success['nil2'] = $this->dbResultArray($sql);
            }
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getBondIndex(Request $request){
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
            $tahunLalu = intval($tahun)-1;
            $periode2 = $tahun.substr($tgl_akhir,5,2);
            $periode1 = $tahunLalu."12";
            $bulan = substr($periode2,4,2);

            $sql = "select distinct kode_bmark, nama, nu,sum(case when periode = '$periode1' then nilai else 0 end ) as nilai1,sum(case when periode = '$periode2' then nilai else 0 end ) as nilai2 
            from inv_index_d
            where kode_lokasi='$kode_lokasi' and jenis_index='BOND'
            group by kode_bmark,nama,nu
            order by nu
            ";
            $success['daftar']= $this->dbResultArray($sql);

            $sql = "select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'$tahun-$bulan-01')+1,0)) ,112),7,2) as tglakhir ";
            $cek = DB::connection($this->db)->select($sql);
            if(count($cek)>0){
                $tgl = $tahun."-".$bulan."-".$cek->fields[0];
                $sql = "select yy10ind,yy10us,yy10jp from inv_bmark where tanggal ='$tahunLalu-12-31' ";
                $success['nil1'] = $this->dbResultArray($sql);
                $sql = "select yy10ind,yy10us,yy10jp from inv_bmark where tanggal ='$tgl' ";
                $success['nil2'] = $this->dbResultArray($sql);
            }
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKatalis(Request $request){
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
            $tahunLalu = intval($tahun)-1;
            $periode = $tahun.substr($tgl_akhir,5,2);

            $sql = "select a.katalis_positif,a.katalis_negatif 
            from inv_issue_d a
            inner join inv_issue_m b on a.id=b.id and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' and b.jenis='Global'
            ";
            $success['global']= $this->dbResultArray($sql);

            $sql2 = "
            select a.katalis_positif,a.katalis_negatif 
            from inv_issue_d a
            inner join inv_issue_m b on a.id=b.id and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.periode='$periode' and b.jenis='Domestik'
             ";
            $success['domestik']= $this->dbResultArray($sql2);
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function updateTgl(Request $request){
        $this->validate($request,[
            'tanggal' => 'required|date_format:Y-m-d'
        ]);
        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "select * from inv_filterdash where nik='$nik' ";
            $res = DB::connection($this->db)->select($sql);
            if(count($res) > 0){
                $sql2 = "update inv_filterdash set tgl_akhir='".$request->tanggal."' where nik='$nik' ";
                $update = DB::connection($this->db)->update($sql2);
            }else{
                $sql2 = "insert into inv_filterdash (tgl_akhir,kode_klp,kode_plan,nik,kode_lokasi)
                values ('".$request->tanggal."','5050','1','$nik','$kode_lokasi')  ";
                $insert = DB::connection($this->db)->insert($sql2);
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Update tanggal berhasil";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function updateParam(Request $request){
       
        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            
            $sql = "select * from inv_filterdash where nik='$nik' ";
            $res = DB::connection($this->db)->select($sql);
            if(count($res) > 0){
                $sql2 = "update inv_filterdash set kode_klp='".$request->kode_klp."', kode_plan='".$request->kode_plan."' where nik='$nik' ";
                $update = DB::connection($this->db)->update($sql2);
            }else{
                $sql2 = "insert into inv_filterdash (tgl_akhir,kode_klp,kode_plan,nik,kode_lokasi)
                values ('".$tgl_akhir."','$request->kode_klp','$request->kode_plan','$nik','$kode_lokasi')  ";
                $insert = DB::connection($this->db)->insert($sql2);
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Update filter berhasil";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getTenor(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            
            $sql = "select z.kode_tenor,z.fair_sum,z.fair_sum / (sum(z.fair_sum) over (partition by null) ) * 100 as persen
            from 
            (
            
            select f.kode_tenor,f.bawah,sum(isnull(h.h_wajar,100) * x.fair_sum) / 100 as fair_sum
            from 
            (
            select a.kode_jenis,sum(a.nilai-isnull(b.jual,0))  as fair_sum
            from 
                (	
                    select a.kode_rdkelola,a.kode_jenis,sum(a.nilai)  as nilai 
                    from inv_obli_d a inner join inv_oblibeli_m b on a.no_beli=b.no_beli
                    where b.tanggal<='$tgl_akhir'
                    group by a.kode_rdkelola,a.kode_jenis 
                ) a 
                    
                left join 
                (
                    select b.kode_rdkelola,a.kode_jenis,sum(a.n_oleh) as jual  
                    from inv_oblijual_d a  inner join inv_oblijual_m b on a.no_oblijual=b.no_oblijual 
                    where b.tanggal<='$tgl_akhir'
                    group by b.kode_rdkelola,a.kode_jenis 
                ) b on a.kode_rdkelola=b.kode_rdkelola and a.kode_jenis=b.kode_jenis 
            
            where a.kode_rdkelola like '%' 
            group by a.kode_jenis
            ) x 
            
            inner join inv_oblijenis c on x.kode_jenis=c.kode_jenis
            inner join inv_obli_tenor f on round(cast (datediff(day,'$tgl_akhir',c.tgl_selesai) as float) / 360,2) > f.bawah and round(cast (datediff(day,'$tgl_akhir',c.tgl_selesai) as float) / 360,2) <= f.atas
            
            left join (select kode_jenis,h_wajar 
                        from inv_obli_harga where tanggal='$tgl_akhir'
                        ) h on c.kode_jenis=h.kode_jenis
            
            group by f.kode_tenor,f.bawah
            
            ) z  
            order by z.bawah ";
            $res = DB::connection($this->db)->select($sql);
            $ctg = array();
            $dt = array();
            foreach($res as $row){
                $ctg[] = $row->kode_tenor;
                $dt[] = array("y"=>floatval($row->persen),"nil"=>floatval($row->fair_sum),"key"=>$row->kode_tenor);
            }
            
            $success['ctg'] = $ctg;
            $success['series'][0] = array("name"=>"Tenor","data"=>$dt,"color"=>"#ffcc00");
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKomposisi(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            
            $sql = "select i.jenis,sum(isnull(h.h_wajar,100) * x.fair_sum) / 100 as fair_sum
            from 
            (
            select a.kode_jenis,sum(a.nilai-isnull(b.jual,0))  as fair_sum
            from 
                (	
                    select a.kode_rdkelola,a.kode_jenis,sum(a.nilai)  as nilai 
                    from inv_obli_d a inner join inv_oblibeli_m b on a.no_beli=b.no_beli
                    where b.tanggal<='$tgl_akhir'
                    group by a.kode_rdkelola,a.kode_jenis 
                ) a 
                    
                left join 
                (
                    select b.kode_rdkelola,a.kode_jenis,sum(a.n_oleh) as jual  
                    from inv_oblijual_d a  inner join inv_oblijual_m b on a.no_oblijual=b.no_oblijual 
                    where b.tanggal<='$tgl_akhir'
                    group by b.kode_rdkelola,a.kode_jenis 
                ) b on a.kode_rdkelola=b.kode_rdkelola and a.kode_jenis=b.kode_jenis 
            
            where a.kode_rdkelola like '%' 
            group by a.kode_jenis
            ) x 
            
            inner join inv_oblijenis c on x.kode_jenis=c.kode_jenis
            inner join inv_obligor i on i.kode_obligor=c.kode_obligor
            left join (select kode_jenis,h_wajar 
                        from inv_obli_harga where tanggal='$tgl_akhir'
                        ) h on c.kode_jenis=h.kode_jenis
            
            group by i.jenis ";
            $res = DB::connection($this->db)->select($sql);
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $dt = array();
            
            $i=0;$total=0;
            foreach($res as $row){
                $dt[] = array("name"=>$row->jenis,"y"=>floatval($row->fair_sum),"color"=>$color[$i]);
                $i++;
                $total+=floatval($row->fair_sum);
            }
            $success['total']=$total;
            $success['series'][0] = array("name"=>"Komposisi","data"=>$dt);

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getRating(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $sql = "select j.nu,e.kategori,sum(isnull(h.h_wajar,100) * x.fair_sum) as fair_price, sum(isnull(h.h_wajar,100) * x.fair_sum) / 100 as fair_sum
            from 
            (
            select a.kode_jenis,sum(a.nilai-isnull(b.jual,0))  as fair_sum
            from 
                (	
                    select a.kode_rdkelola,a.kode_jenis,sum(a.nilai)  as nilai 
                    from inv_obli_d a inner join inv_oblibeli_m b on a.no_beli=b.no_beli
                    where b.tanggal<='$tgl_akhir'
                    group by a.kode_rdkelola,a.kode_jenis 
                ) a 
                    
                left join 
                (
                    select b.kode_rdkelola,a.kode_jenis,sum(a.n_oleh) as jual  
                    from inv_oblijual_d a  inner join inv_oblijual_m b on a.no_oblijual=b.no_oblijual 
                    where b.tanggal<='$tgl_akhir'
                    group by b.kode_rdkelola,a.kode_jenis 
                ) b on a.kode_rdkelola=b.kode_rdkelola and a.kode_jenis=b.kode_jenis 
            
            where a.kode_rdkelola like '%' 
            group by a.kode_jenis
            ) x 
            
            inner join inv_oblijenis c on x.kode_jenis=c.kode_jenis
            inner join inv_obli_rating e on c.kode_rating=e.kode_rating
            inner join inv_obli_ratingkat j on e.kategori=j.kategori
            
            left join (select kode_jenis,h_wajar 
                        from inv_obli_harga where tanggal='$tgl_akhir'
                        ) h on c.kode_jenis=h.kode_jenis
            group by e.kategori,j.nu
			having (sum(isnull(h.h_wajar,100) * x.fair_sum) / 100) <> 0
            order by j.nu";

            $res = DB::connection($this->db)->select($sql);
            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $dt = array();
            
            $i=0;$total=0;
            foreach($res as $row){
                $dt[] = array("name"=>$row->kategori,"y"=>floatval($row->fair_sum),"fair_price"=>floatval($row->fair_price),"color"=>$color[$i]);
                $i++;
                $total+=floatval($row->fair_sum);
            }
            $success['total']=$total;
            $success['series'][0] = array("name"=>"Komposisi","data"=>$dt);

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getKomposisiTenor(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $periode = $tahun.substr($tgl_akhir,5,2);
            $tahunAwal = intval($tahun)-9;
            $tahunAkhir = intval($tahun)-1;
            $exec = array();

            $sql = "exec sp_gen_obli_aset '$tgl_akhir','$nik' ";
            $exec = DB::connection($this->db)->update($sql);

            $sql = " select a.tahun,sum(a.nilai)/1000000000 as pokok,sum(a.kupon)/1000000000 as kupon , b.cc
            from inv_obliaset_tmp a 
            inner join inv_obliaset_cc b on a.tahun=b.tahun
            where a.nik_user ='$nik'
            group by a.tahun,b.cc
            order by a.tahun
            ";

            $res = DB::connection($this->db)->select($sql);
            $ctg = array();
            $dt[0] = array();
            $dt[1] = array();
            $i=0;
            foreach($res as $row){
                array_push($ctg,$row->tahun);
                array_push($dt[0],floatval($row->pokok));
                array_push($dt[1],floatval($row->kupon));
                $i++;
            }

            $success["ctg"] = $ctg;

            $color = array('#007AFF', '#FFCC00', '#4CD964', '#9F9F9F', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1');
            $success["series"][0] = array(
                "name"=>"Kupon", "type"=>"column","color"=>$color[0],"data"=>$dt[1]
            );

            $success["series"][1]= array(
                "name"=>"Pokok", "type"=>"column","color"=>$color[1], "data"=>$dt[0]
            );

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getTableObli(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $param = $this->getParam($nik);
            $tgl_akhir = $param['tgl_akhir'];
            $kode_plan = $param['kode_plan'];
            $kode_klp = $param['kode_klp'];
            $tahun= substr($tgl_akhir,0,4);
            $tahun= substr($tgl_akhir,0,4);
            $tahunLalu = intval($tahun)-1;
            $periode = $tahun.substr($tgl_akhir,5,2);
            $exec = array();

            $sqlt="select b.nama as reksadana,
            round(sum(z.fair_sum),0) as money_market,
            sum(z.wi_durmi)  as avg_duration,
            sum(z.wi_yieldmi) as avg_yield,
            sum(z.wi_kuponmi) as avg_coupon
            from 
            (
            
                select x.kode_rdkelola, 
                x.fair_sum, 	
                x.fair_sum / sum(x.fair_sum) over (partition by x.kode_rdkelola) as wi_fsmi,
                x.mo_duration * (x.fair_sum / sum(x.fair_sum ) over (partition by x.kode_rdkelola)) as wi_durmi,
                x.yield * (x.fair_sum / sum(x.fair_sum ) over (partition by x.kode_rdkelola)) as wi_yieldmi,
                x.kupon * (x.fair_sum / sum(x.fair_sum ) over (partition by x.kode_rdkelola)) as wi_kuponmi
            
                from (
                
                    select a.no_beli,a.kode_rdkelola,a.kode_jenis,a.nilai-isnull(b.jual,0) as nominal, c.tgl_selesai,
                    a.p_price as face_value, c.persen as kupon,e.jenis,f.kategori as rating,
                    cast (datediff(day,'$tgl_akhir',c.tgl_selesai) as float) / (case when e.jenis = 'PEMERINTAH' then 365 else 360 end) as tenor, 
                    case when e.jenis = 'PEMERINTAH' then 2 else 4 end as frek, 
                    case when e.jenis = 'PEMERINTAH' then 1 else 2 end as basis,
                    isnull(h.h_wajar,100) as price, 
                    isnull(h.yield,0) as yield,
                    
                    
                    dbo.mduration (c.persen/100, (cast (datediff(day,'$tgl_akhir',c.tgl_selesai) as float) / (case when e.jenis = 'PEMERINTAH' then 365 else 360 end)), 
                    case when e.jenis = 'PEMERINTAH' then 2 else 4 end, a.p_price/100 , isnull(h.h_wajar,100)/100,isnull(h.yield,0)/100  ) as mo_duration,
                    
                    ((a.nilai-isnull(b.jual,0)) * (isnull(h.h_wajar,100))/100) as fair_sum
                    
                    from 
                            (	
                                select b.no_beli,b.tgl_settl,a.kode_rdkelola,a.kode_jenis,a.nilai,a.p_price 
                                from inv_obli_d a inner join inv_oblibeli_m b on a.no_beli=b.no_beli
                                where b.tanggal<='$tgl_akhir'	
                            ) a 
                                
                            left join 
                            (
                                select a.no_beli,b.kode_rdkelola,a.kode_jenis,sum(a.n_oleh) as jual  
                                from inv_oblijual_d a  inner join inv_oblijual_m b on a.no_oblijual=b.no_oblijual 
                                where b.tanggal<='$tgl_akhir'
                                group by a.no_beli,b.kode_rdkelola,a.kode_jenis 
                            ) b on a.no_beli=b.no_beli and a.kode_rdkelola=b.kode_rdkelola and a.kode_jenis=b.kode_jenis 
                            
                            inner join inv_oblijenis c on a.kode_jenis=c.kode_jenis
                            inner join inv_obligor e on c.kode_obligor=e.kode_obligor
                            inner join inv_obli_rating f on c.kode_rating=f.kode_rating 
                            left join (select kode_jenis,h_wajar,yield 
                                        from inv_obli_harga where tanggal='$tgl_akhir'
                                        ) h on c.kode_jenis=h.kode_jenis
                                            
                    where a.nilai-isnull(b.jual,0) > 0
                    
                ) x	
                
                
            ) z
            inner join inv_rdkelola b on z.kode_rdkelola = b.kode_rdkelola
            group by b.nama";

            $success["daftar"] = $this->dbResultArray($sqlt);

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }
}
