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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
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
            
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNBuku(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNABHari(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSPIHari(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSPIHari2(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getPortofolio(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getSahamSektor(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailPerSaham(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDaftarRD(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailRD(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDOC(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDepo(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getNABDepoHari(){
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
                
                $response["data"][] = array("type"=>"areaspline","name" => $row["kode_kelola"],"color"=>$colors[$row["kode_kelola"]], "data" => $result[$row["kode_kelola"]],"showInLegend"=>true );
                $i++;
            
            }

            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDaftarSP(){
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }

    public function getDetailSP(){
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
            $sp = dbResultArray($sqlsp);

            $response["daftar"] = $sp;

            $sql = "select a.kode_spkelola,a.tanggal as tgl,
            sum(a.jumlah*a.h_wajar) as total
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun' and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra'
            group by a.kode_spkelola,a.tanggal
            order by a.kode_spkelola,a.tanggal
            ";
            $pembagi = 1000000000;
            $rs = execute($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            if($rs->RecordCount() > 0){
                while ($row = $rs->FetchNextObject(false)){
                    $result[$row->kode_spkelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));
                    
                }
            }

            $sqlc = "select distinct kode_spkelola
            from inv_sp_kkp
            ";
            $resc = dbResultArray($sqlc);
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
            $res = dbResultArray($sql2);
            $response["NAB"] = array();
            foreach($res as $row){
                
                $response["NAB"][] = array("type"=>"area","name" => $row["kode_spkelola"],"color"=>$colors[$row["kode_spkelola"]], "data" => $result[$row["kode_spkelola"]],"showInLegend"=>true );
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
            $rs = execute($sql);
            $color = array('#727276','#7cb5ec','#ff6f69','#8085e9', '#f15c80','#2b908f','#f45b5b','#058DC7', '#6AF9C4','#f39c12', '#24CBE5');
            $i=0;
            if($rs->RecordCount() > 0){
                while ($row = $rs->FetchNextObject(false)){
                    $result[$row->kode_spkelola][] = array($row->tgl,round(floatval($row->total)/$pembagi,2));
                    $result[$row->kode_spkelola."SPI_Buku"][] = array($row->tgl,round(floatval($row->total2)/$pembagi,2));
                    
                }
            }
 
            $sqlc = "select distinct kode_spkelola
            from inv_sp_kkp
            ";
            $resc = dbResultArray($sqlc);
            $i=0;
            $colors = array();
            foreach($resc as $row){
                
                $colors[$row["kode_spkelola"]] = $color[$i];
                $colors[$row["kode_spkelola"]."SPI_Buku"] = $color[$i+1];
                $i++;
            }
            
            $response["colors"]=$colors;

            $sql2 = "select distinct a.kode_spkelola
            from inv_sp_kkp a 
            where substring(a.periode,1,4)='$tahun'
            and a.kode_plan='$kode_plan' and a.kode_mitra='$kode_mitra' ";
            $res = dbResultArray($sql2);
            $response["SPI"] = array();
            foreach($res as $row){
                
                $response["SPI"][] = array("type"=>"area","name" => $row["kode_spkelola"]." SPI Perolehan","color"=>$colors[$row["kode_spkelola"]], "data" => $result[$row["kode_spkelola"]],"showInLegend"=>true,"visible"=> false );
                $response["SPI"][] = array("type"=>"area","name" => $row["kode_spkelola"]." SPI Buku","color"=>$colors[$row["kode_spkelola"]."SPI_Buku"], "data" => $result[$row["kode_spkelola"]."SPI_Buku"],"showInLegend"=>true );
                $i++;
            
            }

            $success["daftar"] = $res;
            $success['status'] = true;
            $success['message'] = "Sukses!";
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }      
    }
}
