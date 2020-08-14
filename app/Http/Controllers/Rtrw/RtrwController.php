<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RtrwController extends Controller
{
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard3 = 'warga';

    public function toBulan($bln)
	{
	  $bulan=$bln;
	  switch ($bulan) 
	  {
	    case "01":
	      $tmp="Januari";
	      break;
		case "02":
		  $tmp="Februari";
	      break;
		case "03":
	      $tmp="Maret";
	      break;
		case "04":
	      $tmp="April";
	      break;
		case "05":
	      $tmp="Mei";
	      break;
		case "06":
	      $tmp="Juni";
	      break;
		case "07":
	      $tmp="Juli";
	      break;
		case "08":
	      $tmp="Agustus";
	      break;  
		case "09":
	      $tmp="September";
	      break;  
		case "10":
	      $tmp="Oktober";
	      break;  
		case "11":
	      $tmp="November";
	      break;  
		case "12":
	      $tmp="Desember";
	      break;  
		case "13":
	      $tmp="Desember 2";
	      break;    
	     case "14":
	      $tmp="Desember 3";	      
	      break;    
	    case "15":
	      $tmp="Desember 4";	      
	      break;    
	    case "16":
	      $tmp="Desember 5";	      
	      break;    
	  }
	  return $tmp;
    }
    
    public function getMenu(Request $request){
        $this->validate($request, [
            'kode_menu' => 'required',
            'jenis_menu' => 'required',
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_menu = $request->kode_menu;
            $sql="select a.*,b.form  from menu a left join m_form b on a.kode_form=b.kode_form where kode_klp = '$kode_menu' and a.jenis_menu='$request->jenis_menu' order by kode_klp, rowindex ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMenu2(Request $request){
        $this->validate($request, [
            'kode_menu' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_menu = $request->kode_menu;
            $sql="select a.*,b.form  from menu a left join m_form b on a.kode_form=b.kode_form where kode_klp = '$kode_menu' and a.jenis_menu='tengah' order by kode_klp, rowindex ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTahun(){
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select distinct a.tahun from (select (substring(a.periode,1,4)) as tahun 
            from gldt a 
            inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.kode_akun in ('11101','11201','11202') 
                ) a
            order by a.tahun desc ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBulan(){
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql=" select distinct (substring(a.periode,5,2)) as bulan,datename(m,cast(substring(a.periode,1,4)+'-'+substring(a.periode,5,2)+'-'+'01' as datetime)) as nama
            from (select  a.periode 
            from gldt a 
            inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.kode_akun in ('11101','11201','11202')
            ) a
            order by (substring(a.periode,5,2)) desc  ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTahunBill(Request $request){
        $this->validate($request, [
            'kode_menu' => 'required',
            'kode_pp'=>'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_menu)){
                if($request->kode_menu == "MOBILERW"){
                    $filter = "";
                }else{
                    $filter = " and kode_pp='$request->kode_pp' ";
                }
            }

            $sql= "select distinct (substring(periode,1,4)) as tahun from rt_bill_d where kode_lokasi='$kode_lokasi' and kode_jenis='IWAJIB' and flag_aktif='1' $filter order by substring(periode,1,4) desc ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPeriodeSetor(){

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = date('Ym');
            $sql="select 'all' as periode union all select '$periode' as periode union all select distinct periode from rt_setor_m where kode_lokasi='$kode_lokasi' order by periode desc ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAkun(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_pp = $request->kode_pp;
            $periode = date('Ym');
            $sql="select akun_kas from pp where kode_pp='$kode_pp'";
            $cek = DB::connection($this->sql)->select($sql);
            $cek = json_decode(json_encode($cek),true);
            $kode_akun = $cek[0]['akun_kas'];

            $sql="select a.kode_akun,a.nama 
            from masakun a 
            inner join relakun_pp c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi and c.kode_pp='$kode_pp'
            inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag in ('001','009') where a.kode_lokasi='$kode_lokasi'   ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['kode_akun'] = $kode_akun;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['kode_akun'] = '';
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRekapRw(Request $request){
        $this->validate($request, [
            'tahun' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            // sql penerimaan
            $sql="select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'D' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.periode like '$tahun%' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENERIMAAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            order by b.jenis,b.idx";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sqlto1="select sum(a.total) as total from (select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'D' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.periode like '$tahun%' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENERIMAAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            ) a ";
            $resto1 = DB::connection($this->sql)->select($sqlto1);
            if(count($resto1) > 0){
                $total1 = $resto1[0]->total;
            }else{
                $total1 = 0;
            }

            // sql pengeluaran 
            $sql2 = "select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'C' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.periode like '$tahun%' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENGELUARAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            order by b.jenis,b.idx";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);


            $sqlto2="select sum(a.total) as total from (select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'C' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and a.periode like '$tahun%' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENGELUARAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            ) a ";
            $resto2 = DB::connection($this->sql)->select($sqlto2);
            if(count($resto2) > 0){
                $total2 = $resto2[0]->total;
            }else{
                $total2 = 0;
            }

            $net = $total1-$total2;

            // sql saldo
            $sql3 = "select sum(a.so_akhir) as so_akhir from glma_pp a where a.kode_lokasi ='$kode_lokasi' and a.periode like '$tahun%' and a.kode_akun in ('11101','11201','11202')
            ";
            $res3 = DB::connection($this->sql)->select($sql3);
            if(count($res3) > 0){
                $saldo = $res3[0]->so_akhir;
            }else{
                $saldo = 0;
            }

            $saldo_ak = intval($saldo)+$net;
            
            if((count($res) > 0) || (count($res2) > 0) || (count($res3) > 0) ){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['penerimaan'] = $res;
                $success['pengeluaran'] = $res2;
                $success['mutasi'] = $net;
                $success['saldo_akhir'] = $saldo_ak;
                $success['saldo_awal'] = intval($saldo);
                $success['message'] = "Success!";
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['penerimaan'] = [];
                $success['pengeluaran'] = [];
                $success['mutasi'] = $net;
                $success['saldo_akhir'] = $saldo_ak;
                $success['saldo_awal'] = intval($saldo);
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailRekapRw(Request $request){
        $this->validate($request, [
            'tahun' => 'required',
            'kode_drk' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            $kode_drk = $request->kode_drk;

            $sql="select convert(varchar,tanggal,103) as tgl,keterangan,dc,nilai as nilai1,jenis,tgl_input
            from gldt where kode_akun in ('11101','11201','11202') and kode_lokasi='$kode_lokasi' and kode_drk ='$kode_drk' and periode like '$tahun%'
            order by tgl_input desc";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRekapBulananRw(Request $request){
        $this->validate($request, [
            'tahun' => 'required',
            'bulan' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            $bulan = $request->bulan;
            // sql penerimaan
            $sql="select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'D' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and substring(a.periode,1,4) = '$tahun' and substring(a.periode,5,2) = '$bulan' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENERIMAAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            order by b.jenis,b.idx";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sqlto1="select sum(a.total) as total from (select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'D' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and substring(a.periode,1,4) = '$tahun' and substring(a.periode,5,2) = '$bulan' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENERIMAAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            ) a ";
            $resto1 = DB::connection($this->sql)->select($sqlto1);
            if(count($resto1) > 0){
                $total1 = $resto1[0]->total;
            }else{
                $total1 = 0;
            }

            // sql pengeluaran 
            $sql2 = "select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'C' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and substring(a.periode,1,4) = '$tahun' and substring(a.periode,5,2) = '$bulan' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENGELUARAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            order by b.jenis,b.idx";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sqlto2="select sum(a.total) as total from (select a.kode_drk,b.nama,b.jenis,b.idx,sum(case a.dc when 'C' then nilai else -nilai end) as total
            from gldt a inner join trans_ref b on a.kode_drk=b.kode_ref and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi ='$kode_lokasi' and substring(a.periode,1,4) = '$tahun' and substring(a.periode,5,2) = '$bulan' and a.kode_akun in ('11101','11201','11202') and b.jenis ='PENGELUARAN'
            group by a.kode_drk,b.nama,b.jenis,b.idx
            ) a ";
            $resto2 = DB::connection($this->sql)->select($sqlto2);
            if(count($resto2) > 0){
                $total2 = $resto2[0]->total;
            }else{
                $total2 = 0;
            }

            $net = $total1-$total2;

            // sql saldo
            $sql3 = "select sum(a.so_akhir) as so_akhir from glma_pp a where a.kode_lokasi ='$kode_lokasi' and substring(a.periode,1,4) = '$tahun' and substring(a.periode,5,2) = '$bulan' and a.kode_akun in ('11101','11201','11202')
            ";
            
            $res3 = DB::connection($this->sql)->select($sql3);
            if(count($res3) > 0){
                $saldo = $res3[0]->so_akhir;
            }else{
                $saldo = 0;
            }

            $saldo_ak = intval($saldo)+$net;

            if((count($res) > 0) || (count($res2) > 0) || (count($res3) > 0) ){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['penerimaan'] = $res;
                $success['pengeluaran'] = $res2;
                $success['mutasi'] = $net;
                $success['saldo_akhir'] = $saldo_ak;
                $success['saldo_awal'] = intval($saldo);
                $success['message'] = "Success!";
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['penerimaan'] = [];
                $success['pengeluaran'] = [];
                $success['mutasi'] = $net;
                $success['saldo_akhir'] = $saldo_ak;
                $success['saldo_awal'] = intval($saldo);
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailRekapBulananRw(Request $request){
        $this->validate($request, [
            'tahun' => 'required',
            'kode_drk' => 'required',
            'bulan' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            $kode_drk = $request->kode_drk;
            $bulan = $request->bulan;

            $sql="select convert(varchar,tanggal,103) as tgl,keterangan,dc,nilai as nilai1,jenis,tgl_input
            from gldt where kode_akun in ('11101','11201','11202') and kode_lokasi='$kode_lokasi' and kode_drk ='$kode_drk' and substring(periode,1,4) = '$tahun' and substring(periode,5,2) = '$bulan'
            order by tgl_input desc";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRiwayatTrans(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $this->validate($request, [
                'kode_pp' => 'required',
                'kode_akun' => 'required',
                'tahun' => 'required'
            ]);
        }else if($data =  Auth::guard($this->guard3)->user()){
            $nik = $data->no_rumah;
            $kode_lokasi= $data->kode_lokasi;
            $kode_pp= $data->kode_pp;
            $this->validate($request, [
                'tahun' => 'required'
            ]);
            $request->merge([
                'kode_pp' => $kode_pp,
            ]);
            $req = $this->getAkun($request);
            if(count($req->original['data']) > 0){
                $request->merge([
                    'kode_akun' => $req->original['data'][0]['kode_akun'],
                ]);
            }else{
                $request->merge([
                    'kode_akun' => '-',
                ]);
            }

        }
        try {
                        
            $tahun = $request->tahun;
            $kode_pp = $request->kode_pp;
            $kode_akun = $request->kode_akun;

            $sql="select sum(nilai) as saldo from
            (
                select so_akhir as nilai from glma_pp where kode_akun ='$kode_akun' and kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
                union 
                select sum(case dc when 'D' then nilai else -nilai end) as nilai 
                from gldt where kode_akun ='$kode_akun' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
            ) a
            ";
            $cek = DB::connection($this->sql)->select($sql);
            $cek = json_decode(json_encode($cek),true);
            $saldo = $cek[0]['saldo'];

            $sql="select top 10 convert(varchar,tanggal,103) as tgl,keterangan,dc,nilai as nilai1,jenis,tgl_input
            from gldt where kode_akun ='$kode_akun' and kode_pp ='$kode_pp' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
            order by tgl_input desc ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if((count($res) > 0)){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['saldo'] = $saldo;
                $success['tahun'] = $tahun;
                $success['message'] = "Success!";
            }
            else{
            
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['saldo'] = $saldo;
                $success['tahun'] = $tahun;
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRiwayatTransDetail(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'tahun' => 'required',
            'page' => 'required',
            'nextpage' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            $kode_pp = $request->kode_pp;
            $kode_akun = $request->kode_akun;
            
            $sql="select sum(nilai) as saldo from
            (
                select so_akhir as nilai from glma_pp where kode_akun ='$kode_akun' and kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
                union 
                select sum(case dc when 'D' then nilai else -nilai end) as nilai 
                from gldt where kode_akun ='$kode_akun' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
            ) a
            ";
            $cek = DB::connection($this->sql)->select($sql);
            $cek = json_decode(json_encode($cek),true);
            $saldo = $cek[0]['saldo'];

            $sql2="select convert(varchar,tanggal,103) as tgl,keterangan,dc,nilai as nilai1,jenis,tgl_input
            from gldt where kode_akun ='$kode_akun' and kode_pp ='$kode_pp' and kode_lokasi='$kode_lokasi' and periode like '$tahun%'
            order by tgl_input desc ";
            $row = DB::connection($this->sql)->select($sql2);
            $row = json_decode(json_encode($row),true);
            $torecord = count($row);

            $success['jumpage'] = ceil($torecord/20);
            $success['page'] = $request->page;
            $nextpage = $request->nextpage;

            $sql = $sql2." 
            OFFSET ".$nextpage." ROWS FETCH NEXT 20 ROWS ONLY";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if((count($res) > 0)){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['saldo'] = $saldo;
                $success['tahun'] = $tahun;
                $success['message'] = "Success!";
            }
            else{
            
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['saldo'] = $saldo;
                $success['tahun'] = $tahun;
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRiwayatIuran(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_rumah' => 'required',
            'periode' => 'required'
        ]);
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $kode_pp = $request->kode_pp;
            $kode_rumah = $request->kode_rumah;
            $periode = $request->periode;

            $sql="select sum(nilai) as saldo from 
            (
                select sum(a.nilai_rt+a.nilai_rw) as nilai
                from rt_bill_d a
                where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.periode <='$periode' and a.kode_jenis='IWAJIB' and a.flag_aktif='1'
                union 
                select -sum(a.nilai_rt+a.nilai_rw) as nilai
                from rt_angs_d a
                where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.periode_bill <='$periode' and a.kode_jenis='IWAJIB'
            ) a ";

            $cek = DB::connection($this->sql)->select($sql);
            $cek = json_decode(json_encode($cek),true);
            $saldo = $cek[0]['saldo'];

            $sql="select a.no_bukti, a.keterangan, convert(varchar,a.tanggal,105) as tgl,a.nilai1 as nilai1 from trans_m a 
            where a.periode <= '$periode' and a.kode_lokasi='$kode_lokasi' and a.param1='$kode_rumah' and a.param2='IWAJIB'
            order by a.no_bukti desc";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['saldo'] = $saldo;
                $success['message'] = "Success!";
            }
            else{
            
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['saldo'] = $saldo;
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailIuran(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
            $this->validate($request, [
                'kode_pp' => 'required',
                'kode_rumah' => 'required',
                'tahun' => 'required'
                ]);
        }else if($data =  Auth::guard($this->guard3)->user()){
            $nik = $data->no_rumah;
            $kode_lokasi= $data->kode_lokasi;
            $this->validate($request, [
                'tahun' => 'required'
            ]);
            $kode_pp = $data->kode_pp;
            $kode_rumah = $data->no_rumah;
            $request->merge([
                'kode_pp' => $kode_pp,
                'kode_rumah' => $kode_rumah,
            ]);
        }
        try {
            
            
            $kode_pp = $request->kode_pp;
            $kode_rumah = $request->kode_rumah;
            $periode = $request->periode;
            $tahun = $request->tahun;
            
            $sql="select  case when substring(a.periode,5,2) = '01' then 'JAN'
            when substring(a.periode,5,2) = '02' then 'FEB'
            when substring(a.periode,5,2) = '03' then 'MAR'
            when substring(a.periode,5,2) = '04' then 'APR'
            when substring(a.periode,5,2) = '05' then 'MEI'
            when substring(a.periode,5,2) = '06' then 'JUN'
            when substring(a.periode,5,2) = '07' then 'JUL'
            when substring(a.periode,5,2) = '08' then 'AGS'
            when substring(a.periode,5,2) = '09' then 'SEP'
            when substring(a.periode,5,2) = '10' then 'OKT'
            when substring(a.periode,5,2) = '11' then 'NOV'
            when substring(a.periode,5,2) = '12' then 'DES'
            end as periode,(a.nilai_rt+a.nilai_rw) as bill,isnull(b.bayar,0) as bayar,isnull(b.tanggal,'-') as tanggal
            from rt_bill_d a 
            left join (
                select a.periode_bill,a.kode_lokasi,a.kode_rumah,convert(varchar,b.tanggal,103) as tanggal,sum(a.nilai_rt+a.nilai_rw) as bayar
                from rt_angs_d a 
                inner join trans_m b on a.no_angs=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.periode_bill like '$tahun%' and a.kode_jenis='IWAJIB' 
                group by a.periode_bill,a.kode_lokasi,a.kode_rumah,b.tanggal
            ) b on a.periode=periode_bill and a.kode_lokasi=b.kode_lokasi and a.kode_rumah=b.kode_rumah 
            where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.periode like '$tahun%' and a.kode_jenis='IWAJIB' and a.flag_aktif='1'
            order by a.periode";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
            
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ubahPassword(Request $request){
        $this->validate($request, [
            'password_lama' => 'required',
            'password_baru' => 'required',
            'password_confirm' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $password_lama = $request->password_lama;
            $password_baru = $request->password_baru;
            $password_confirm = $request->password_confirm;

            $sql="select nik, pass from hakakses where nik='$nik' and kode_lokasi='$kode_lokasi' and pass='$password_lama'";
            $cek = DB::connection($this->sql)->select($sql);
            $cek = json_decode(json_encode($cek),true);

            if(count($cek) > 0){
                $up_data = $password_baru;
                $konfir_data = $password_confirm;
                if ($up_data == $konfir_data){

                    DB::connection($this->sql)->beginTransaction();

                    $upd = DB::connection($this->sql)->table('hakakses')
                    ->where('nik', $nik)    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('pass', $password_lama)
                    ->update(['pass' => $password_baru, 'password'=>app('hash')->make($password_baru)]);

                    
                    if($upd){
                        $success['status'] = TRUE;
                        $success['message'] = 'Data berhasil disimpan';
                        DB::connection($this->sql)->commit();
                    }else{
                        $success['status'] = FALSE;
                        $success['message'] = "Data gagal disimpan ke database";
                        DB::connection($this->sql)->rollback();
                    }
                }else{
                    $success['status'] = FALSE;
                    $success['message'] = "error input";
                    $success['error_input'][0] = "Password baru dan konfirmasi password tidak sama ! ";
                }			
            }else{
                $success['status'] = 3;
                $success['alert'] = "error input";
                $success['error_input'][0] = "Password lama salah ! Harap input password yang benar. ";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBlok(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_menu' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->kode_menu == "MOBILERW"){
                $filter = "";
                $success['blok'] = '';
            }else{

                $filter = " and kode_pp='$request->kode_pp' ";

                $sql="select kode_rumah from hakakses where kode_lokasi='$kode_lokasi' and nik='$nik' ";
                $cek = DB::connection($this->sql)->select($sql);
                $cek = json_decode(json_encode($cek),true);
                $kode_rumah=$cek[0]['kode_rumah'];
    
                $sqlBlok="select blok from rt_rumah where kode_lokasi='$kode_lokasi' and kode_rumah='$kode_rumah' ";
                $cek = DB::connection($this->sql)->select($sqlBlok);
                $cek = json_decode(json_encode($cek),true);
                $blok=$cek[0]['blok'];
                $success['blok'] = $blok;
            }

            $kode_pp = $request->kode_pp;
            $sql = "select blok from rt_blok where kode_lokasi='$kode_lokasi' $filter order by blok ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKartuIuran(Request $request){
        $this->validate($request, [
            'blok' => 'required',
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            $blok = $request->blok;

            $sql="select a.kode_rumah,case when sum(nilai) < 0 then 0 else sum(nilai)end as saldo from 
            (
                select a.kode_rumah,sum(a.nilai_rt+a.nilai_rw) as nilai
                from rt_bill_d a
                inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi 
                where a.kode_lokasi ='$kode_lokasi' and b.blok ='$blok' and a.periode <='$periode' and a.kode_jenis='IWAJIB' and a.flag_aktif='1'
                group by a.kode_rumah
                union 
                select a.kode_rumah,-sum(a.nilai_rt+a.nilai_rw) as nilai
                from rt_angs_d a
                inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi ='$kode_lokasi' and b.blok ='$blok' and a.periode_bill <='$periode' and a.kode_jenis='IWAJIB'
                group by a.kode_rumah
                ) a
                group by a.kode_rumah 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRefAkun(Request $request){
        $this->validate($request, [
            'jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if($request->jenis == 'Keluar'){
                $jenis="PENGELUARAN";
            }else{
                $jenis="PENERIMAAN";
            }
            $sql="select a.kode_ref, a.nama 
            from trans_ref a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik'
            where a.jenis='$jenis' and a.kode_lokasi='$kode_lokasi'";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanKas(Request $request)
    {
        $this->validate($request, [
            'kode_akun' => 'required',
            'kode_pp' => 'required',
            'kode_jenis' => 'required',
            'kode_ref' => 'required',
            'keterangan' => 'required',
            'nilai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->kode_jenis == 'Keluar'){
                $jenis="BK";
            }else{
                $jenis="BM";
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-".$jenis.$per.".";
            $query = DB::connection($this->sql)->select("select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' ");
            $query = json_decode(json_encode($query),true);
            
            $id = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            $sql="select a.nama,a.akun_debet,a.akun_kredit,a.kode_pp, b.kode_gar as gar_debet,c.kode_gar as gar_kredit 
            from trans_ref a 
            inner join masakun b on a.akun_debet=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join masakun c on a.akun_kredit=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
            where a.kode_ref='".$request->kode_ref."' and a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $akunDebet=$res[0]['akun_debet'];
            $akunKredit=$res[0]['akun_kredit'];

            if($request->kode_jenis == 'Keluar'){
                $akunKredit = $request->kode_akun;
            }else{
                $akunDebet = $request->kode_akun;
            }

            $ins = DB::connection($this->sql)->insert('insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBDUAL','T','-','-',$request->kode_pp,date('Y-m-d H:i:s'),'-',$request->keterangan,'IDR','1',$request->nilai,0,0,$nik,'-','-','-','-','-',$request->kode_ref,'TUNAI',$jenis]);

            $ins2 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunDebet,'D',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            $ins3 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunKredit,'C',$request->nilai,$request->nilai,$request->keterangan,'KB',$jenis,'IDR',1,$request->kode_pp,$request->kode_ref,'-','-','-','-','-','-','-']);

            $ins4 = DB::connection($this->sql)->update("insert into gldt (no_bukti,no_urut,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,kurs,nilai_curr,tgl_input,nik_user,kode_cust,kode_proyek,kode_task,kode_vendor,kode_lokarea,nik) select no_bukti,nu,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,1,nilai,tgl_input,nik_user,'-','-','-','-','-','-' from trans_j 
            where kode_lokasi='".$kode_lokasi."' and no_bukti='".$id."' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kas berhasil disimpan. No Bukti: ".$id;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kas gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function getBayarIuran(Request $request){
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(isset($request->blok)){
                $filter = " and b.blok='$request->blok' ";
            }else{
                $filter = "";
            }
            $periode = date('Ym');

            $sql="select a.kode_rumah,a.saldo,isnull(b.nilai,0) as bayar 
            from (
                select a.kode_rumah,a.kode_lokasi,case when sum(a.nilai) < 0 then 0 else sum(a.nilai)end as saldo
                from 
                (
                    select a.kode_rumah,a.kode_lokasi,sum(a.nilai_rt+a.nilai_rw) as nilai
                    from rt_bill_d a
                    inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi 
                    where a.kode_lokasi ='$kode_lokasi' $filter and a.periode <='$periode' and a.kode_jenis='IWAJIB' and a.flag_aktif='1'
                    group by a.kode_rumah,a.kode_lokasi
                    union all
                    select a.kode_rumah,a.kode_lokasi,-sum(a.nilai_rt+a.nilai_rw) as nilai
                    from rt_angs_d a
                    inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi ='$kode_lokasi' $filter and a.periode_bill <='$periode' and a.kode_jenis='IWAJIB'
                    group by a.kode_rumah,a.kode_lokasi
                ) a
                group by a.kode_rumah,a.kode_lokasi
            ) a
            left join (	select a.kode_rumah,a.kode_lokasi,sum(a.nilai_rt+a.nilai_rw) as nilai
                        from rt_angs_d a
                        inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi ='$kode_lokasi' $filter
                        and a.kode_jenis='IWAJIB' and a.no_setor='-'
                        group by a.kode_rumah,a.kode_lokasi
            ) b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
            ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanIuran(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_rumah' => 'required',
            'kode_akun' => 'required',
            'bayar' => 'required',
            'status_bayar' => 'required',
            'total_rw' => 'required',
            'total_rt' => 'required',
            'periode_bill.*' => 'required',
            // 'toggle.*' => 'required',
            'nilai_rw.*' => 'required',
            'nilai_rt.*' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $jenis="BM";

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-".$jenis.$per.".";
            $query = DB::connection($this->sql)->select("select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' ");
            $query = json_decode(json_encode($query),true);
            
            $id = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            $sql="select a.kode_pp,a.akun_kas,a.akun_kastitip, a.akun_titip,a.akun_pdpt 
            from pp a inner join rt_rumah b on a.kode_pp=b.rt and a.kode_lokasi=b.kode_lokasi 
            where b.kode_rumah='".$request->kode_rumah."' and a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $akunTitip=$res[0]['akun_titip'];

            $akunKas = $request->kode_akun;
            $keterangan ="Penerimaan Iuran Wajib atas rumah ".$request->kode_rumah." periode ".$periode;

            $ins = DB::connection($this->sql)->insert('insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'RTRW','KBIUR','T','0','0',$request->kode_pp,date('Y-m-d H:i:s'),'-',$keterangan,'IDR','1',$request->bayar,0,0,'-','-','-',$request->status_bayar,'-','-',$request->kode_rumah,'IWAJIB','-']);

            $nilai_iur= intval($request->total_rw)+intval($request->total_rt);

            $ins2 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunKas,'D',$nilai_iur,$nilai_iur,$keterangan,'RTRW','KBRW','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-']);

            $ins3 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunTitip,'C',$nilai_iur,$nilai_iur,$keterangan,'RTRW','TITIP','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-']);

            for($a=0; $a<count($request->periode_bill);$a++){
                // if ($_POST['toggle'][$a] == "on"){
                $sqldet[$a] =  DB::connection($this->sql)->insert("insert into rt_angs_d (no_angs,kode_rumah,kode_jenis,periode_bill,periode_angs,nilai_rt,nilai_rw,kode_lokasi,kode_pp,dc,modul,jenis,no_setor) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array(
                                $id,$request->kode_rumah,'IWAJIB',$request->periode[$a],$periode,$request->nilai_rt[$a],$request->nilai_rw[$a],$kode_lokasi,$request->kode_pp,'D','KBIUR','KAS','-'));
                    	
                // }
            }

            $ins4 = DB::connection($this->sql)->update("insert into gldt (no_bukti,no_urut,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,kurs,nilai_curr,tgl_input,nik_user,kode_cust,kode_proyek,kode_task,kode_vendor,kode_lokarea,nik) select no_bukti,nu,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,1,nilai,tgl_input,nik_user,'-','-','-','-','-','-' from trans_j 
            where kode_lokasi='".$kode_lokasi."' and no_bukti='".$id."' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Pembayaran Iuran berhasil disimpan. No Bukti: ".$id;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Pembayaran Iuran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function getBayarIuranRw(Request $request){
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            if(isset($request->blok)){
                $filter = " and b.blok='$request->blok' ";
            }else{
                $filter = "";
            }
            $periode = date('Ym');
            
            $sql="select a.kode_rumah,a.saldo,isnull(b.nilai,0) as bayar 
            from (
                select a.kode_rumah,a.kode_lokasi,case when sum(a.nilai) < 0 then 0 else sum(a.nilai)end as saldo
                from 
                (
                    select a.kode_rumah,a.kode_lokasi,sum(a.nilai_rt+a.nilai_rw) as nilai
                    from rt_bill_d a
                    inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi 
                    where a.kode_lokasi ='$kode_lokasi' $filter and a.periode <='$periode' and a.kode_jenis='IWAJIB' and a.flag_aktif='1'
                    group by a.kode_rumah,a.kode_lokasi
                    union all
                    select a.kode_rumah,a.kode_lokasi,-sum(a.nilai_rt+a.nilai_rw) as nilai
                    from rt_angs_d a
                    inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi ='$kode_lokasi' $filter and a.periode_bill <='$periode' and a.kode_jenis='IWAJIB'
                    group by a.kode_rumah,a.kode_lokasi
                ) a
                group by a.kode_rumah,a.kode_lokasi
            ) a
            left join (	select a.kode_rumah,a.kode_lokasi,sum(a.nilai_rt+a.nilai_rw) as nilai
                        from rt_angs_d a
                        inner join rt_rumah b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
                        where a.kode_lokasi ='$kode_lokasi' $filter
                        and a.kode_jenis='IWAJIB' and a.no_setor='-'
                        group by a.kode_rumah,a.kode_lokasi
            ) b on a.kode_rumah=b.kode_rumah and a.kode_lokasi=b.kode_lokasi
            ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanIuranRw(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_rumah' => 'required',
            'kode_akun' => 'required',
            'bayar' => 'required',
            'status_bayar' => 'required',
            'total_rw' => 'required',
            'total_rt' => 'required',
            'periode_bill.*' => 'required',
            // 'toggle.*' => 'required',
            'nilai_rw.*' => 'required',
            'nilai_rt.*' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $jenis="BM";

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-".$jenis.$per.".";
            $query = DB::connection($this->sql)->select("select right(isnull(max(no_bukti),'0000'),".strlen($str_format).")+1 as id from trans_m where no_bukti like '$prefix%' ");
            $query = json_decode(json_encode($query),true);
            
            $id = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            //id setor
            $prefix2=$kode_lokasi."-STR".$per.".";
            $query2 = DB::connection($this->sql)->select("select right(isnull(max(no_setor),'0000'),".strlen($str_format).")+1 as id from rt_setor_m where no_setor like '$prefix2%' ");
            $query2 = json_decode(json_encode($query2),true);

            $id_setor = $prefix2.str_pad($query2[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            $sql="select a.kode_pp,a.akun_kas,a.akun_kastitip, a.akun_titip,a.akun_pdpt,b.rt 
            from pp a inner join rt_rumah b on a.kode_pp=b.rt and a.kode_lokasi=b.kode_lokasi 
            where b.kode_rumah='".$request->kode_rumah."' and a.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            $akunTitip=$res[0]['akun_titip'];
            $rt=$res[0]['rt'];
            $kode_drk = "ST".substr($rt,1);

            $akunKas = $request->kode_akun;
            $keterangan ="Penerimaan Iuran Wajib atas rumah ".$request->kode_rumah." periode ".$periode;

            $ins = DB::connection($this->sql)->insert('insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'RTRW','KBIUR','T','0','0',$rt,date('Y-m-d H:i:s'),'-',$keterangan,'IDR','1',$request->bayar,0,0,'-','-','-',$request->status_bayar,'-','-',$request->kode_rumah,'IWAJIB','-']);

            $nilai_iur= intval($request->total_rw)+intval($request->total_rt);

            $ins2 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunKas,'D',$nilai_iur,$nilai_iur,$keterangan,'RTRW','KBRW','IDR',1,$request->kode_pp,$kode_drk,'-','-','-','-','-','-','-']);

            $ins3 = DB::connection($this->sql)->insert('insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'-',date('Y-m-d H:i:s'),0,$akunTitip,'C',$nilai_iur,$nilai_iur,$keterangan,'RTRW','TITIP','IDR',1,$rt,$kode_drk,'-','-','-','-','-','-','-']);

            for($a=0; $a<count($request->periode_bill);$a++){
                // if ($_POST['toggle'][$a] == "on"){
                $sqldet[$a] =  DB::connection($this->sql)->insert("insert into rt_angs_d (no_angs,kode_rumah,kode_jenis,periode_bill,periode_angs,nilai_rt,nilai_rw,kode_lokasi,kode_pp,dc,modul,jenis,no_setor) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array(
                                $id,$request->kode_rumah,'IWAJIB',$request->periode[$a],$periode,$request->nilai_rt[$a],$request->nilai_rw[$a],$kode_lokasi,$rt,'D','KBIUR','KAS',$id_setor));
                    	
                // }
            }

            $ins4 = DB::connection($this->sql)->update("insert into gldt (no_bukti,no_urut,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,kurs,nilai_curr,tgl_input,nik_user,kode_cust,kode_proyek,kode_task,kode_vendor,kode_lokarea,nik) select no_bukti,nu,kode_lokasi,modul,jenis,no_dokumen,tanggal,kode_akun,dc,nilai,keterangan,kode_pp,periode,kode_drk,kode_curr,1,nilai,tgl_input,nik_user,'-','-','-','-','-','-' from trans_j 
            where kode_lokasi='".$kode_lokasi."' and no_bukti='".$id."' ");

            //SIMPAN SETORAN

            $keterangan = "Setoran bulan ".$this->toBulan(date('m'));
            $jml_iuran = 1;
            $sumbangan = 100000;
            $gaji=1200000;
            $kasRT= $request->total_rt - $sumbangan;
            $kasRW= $request->bayar - $request->total_rt - $gaji;
            $setor= $kasRW+$sumbangan;


            $ins5 = DB::connection($this->sql)->insert("insert into rt_setor_m (no_setor,kode_lokasi,tanggal,keterangan,kode_pp,modul,periode,nilai,tgl_input,nik_user,no_kas, jml_iuran,sumbangan,gaji_bersih,kas_rt,kas_rw ) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($id_setor,$kode_lokasi,date('Y-m-d H:i:s'),$keterangan,$rt,'IWAJIB',$periode,$request->total_rw,date('Y-m-d H:i:s'),$nik,'-', $jml_iuran,$sumbangan,$gaji,$kasRT,$kasRW));	
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Pembayaran Iuran berhasil disimpan. No Bukti: ".$id." No Setor: ".$id_setor;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Pembayaran Iuran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function getDetailBayar(Request $request){
        $this->validate($request, [
            'kode_rumah' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_rumah = $request->kode_rumah;

            $sql="select a.periode,a.nilai_rt-isnull(b.nilai_rt,0) as nilai_rt,a.nilai_rw-isnull(b.nilai_rw,0) as nilai_rw,(a.nilai_rt+a.nilai_rw) as bill,a.nilai_rt+a.nilai_rw - (isnull(b.nilai_rt+b.nilai_rw,0)) as bayar
            from rt_bill_d a 
            left join (
                select periode_bill,kode_lokasi,kode_rumah,sum(nilai_rt) as nilai_rt,sum(nilai_rw) as nilai_rw,sum(nilai_rt+nilai_rw) as bayar
                from rt_angs_d 
                where kode_lokasi ='$kode_lokasi' and kode_rumah ='$kode_rumah' and kode_jenis='IWAJIB' 
                group by periode_bill,kode_lokasi,kode_rumah
            ) b on a.periode=periode_bill and a.kode_lokasi=b.kode_lokasi and a.kode_rumah=b.kode_rumah 
            where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.kode_jenis='IWAJIB' and (a.nilai_rt+a.nilai_rw) - isnull(b.bayar,0) > 0 and a.flag_aktif='1'
            order by a.periode";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailBayarRw(Request $request){
        $this->validate($request, [
            'kode_rumah' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_rumah = $request->kode_rumah;

            $sql="select a.periode,a.nilai_rt-isnull(b.nilai_rt,0) as nilai_rt,a.nilai_rw-isnull(b.nilai_rw,0) as nilai_rw,(a.nilai_rt+a.nilai_rw) as bill,a.nilai_rt+a.nilai_rw - (isnull(b.nilai_rt+b.nilai_rw,0)) as bayar 
            from rt_bill_d a 
            left join (
                select periode_bill,kode_lokasi,kode_rumah,sum(nilai_rt) as nilai_rt,sum(nilai_rw) as nilai_rw,sum(nilai_rt+nilai_rw) as bayar
                from rt_angs_d 
                where kode_lokasi ='$kode_lokasi' and kode_rumah ='$kode_rumah' and kode_jenis='IWAJIB' 
                group by periode_bill,kode_lokasi,kode_rumah
            ) b on a.periode=periode_bill and a.kode_lokasi=b.kode_lokasi and a.kode_rumah=b.kode_rumah 
            where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$kode_rumah' and a.kode_jenis='IWAJIB' and (a.nilai_rt+a.nilai_rw) - isnull(b.bayar,0) > 0 and a.flag_aktif='1'
            order by a.periode";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSetoran(Request $request){
        $this->validate($request, [
            'kode_rumah' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_rumah = $request->kode_rumah;
            $kode_pp = $request->kode_pp;

            $sql=" select kode_lokasi,kode_rumah,sum(nilai_rt) as nilai_rt,sum(nilai_rw) as nilai_rw,sum(nilai_rt+nilai_rw) as bayar
            from rt_angs_d where kode_lokasi ='$kode_lokasi' and kode_pp='$kode_pp' and no_setor='-' and kode_jenis='IWAJIB' group by kode_lokasi,kode_rumah
            "; 
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function simpanSetoran(Request $request)
    {
        $this->validate($request, [
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'bayar' => 'required',
            'status_bayar' => 'required',
            'total_rw' => 'required',
            'total_rt' => 'required',
            'kode_rumah.*' => 'required',
            'nilai_rw.*' => 'required',
            'nilai_rt.*' => 'required',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-STR".$per.".";

            $query = DB::connection($this->sql)->select("select right(isnull(max(no_setor),'0000'),".strlen($str_format).")+1 as id from rt_setor_m where no_setor like '$prefix%' ");
            $query = json_decode(json_encode($query),true);
            
            $id = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

            $keterangan = "Setoran bulan ".$this->toBulan(date('m'));
            $jml_iuran = count($request->kode_rumah);
            $sumbangan = 100000;
            $gaji=1200000;
            $kasRT= $request->total_rt - $sumbangan;
            $kasRW = $request->bayar - $request->total_rt - $gaji;
            $setor= $kasRW +$sumbangan;

            $ins = DB::connection($this->sql)->insert("insert into rt_setor_m (no_setor,kode_lokasi,tanggal,keterangan,kode_pp,modul,periode,nilai,tgl_input,nik_user,no_kas, jml_iuran,sumbangan,gaji_bersih,kas_rt,kas_rw ) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($id,$kode_lokasi,date('Y-m-d H:i:s'),$keterangan,$request->kode_pp,'IWAJIB',$periode,$request->total_rw,date('Y-m-d H:i:s'),$nik,'-', $jml_iuran,$sumbangan,$gaji,$kasRT,$kasRW));	

            for($i=0;$i < count($request->kode_rumah);$i++){
                $upd[$i] = DB::connection($this->sql)->table('rt_angs_d')
                    ->where('no_setor', '-')    
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('kode_rumah', $request->kode_rumah[$i])
                    ->where('kode_jenis', 'IWAJIB')
                    ->update(['no_setor' => $id]);
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data setoran berhasil disimpan. No Bukti:".$id;
                
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data setoran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
                
    }

    public function getRekapSetoran(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->periode)){

                $periode = $request->periode;
            }else{
                $periode = "";
            }
            $kode_pp = $request->kode_pp;
            if($periode == "" or $periode == "all"){
                $filter = "";
            }else{
                $filter = " and periode='$periode' ";
            }

            $sql="select no_setor,convert(varchar,tanggal,103) as tanggal,isnull(sum(nilai),0)+isnull(sum(kas_rt),0)+isnull(sum(sumbangan),0) as total from rt_setor_m where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp' and modul='IWAJIB' $filter  group by no_setor,tanggal order by no_setor desc ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailRekapSetoran(Request $request){
        $this->validate($request, [
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik = $data->no_rumah;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->no_setor)){
                $no_setor = $request->no_setor;
            }else{
                $no_setor = "";
            }
            $kode_pp = $request->kode_pp;
            if($no_setor == "" or $no_setor == "all"){
                $filter = "";
            }else{
                $filter = " and no_setor='$no_setor' ";
            }

            $sql="
            select kode_rumah,periode_bill,sum(nilai_rt)+sum(nilai_rw) as total
            from rt_angs_d
            where kode_lokasi ='$kode_lokasi' and kode_pp='$kode_pp' and kode_jenis='IWAJIB' $filter
            group by kode_rumah,periode_bill order by kode_rumah,periode_bill ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
            }
            else{
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    
}
