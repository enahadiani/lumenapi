<?php

namespace App\Http\Controllers\Esaku\Aktap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PenghapusanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }


    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
    }

    function namaPeriode($periode){
        $bulan = substr($periode,4,2);
        $tahun = substr($periode,0,4);
        switch ($bulan){
            case 1 : case '1' : case '01': $bulan = "Januari"; break;
            case 2 : case '2' : case '02': $bulan = "Februari"; break;
            case 3 : case '3' : case '03': $bulan = "Maret"; break;
            case 4 : case '4' : case '04': $bulan = "April"; break;
            case 5 : case '5' : case '05': $bulan = "Mei"; break;
            case 6 : case '6' : case '06': $bulan = "Juni"; break;
            case 7 : case '7' : case '07': $bulan = "Juli"; break;
            case 8 : case '8' : case '08': $bulan = "Agustus"; break;
            case 9 : case '9' : case '09': $bulan = "September"; break;
            case 10 : case '10' : case '10': $bulan = "Oktober"; break;
            case 11 : case '11' : case '11': $bulan = "November"; break;
            case 12 : case '12' : case '12': $bulan = "Desember"; break;
            default: $bulan = null;
        }
    
        return $bulan.' '.$tahun;
    }

    
    function nextNPeriode($periode, $n) 
    {
        $bln = floatval(substr($periode,4,2));
        $thn = floatval(substr($periode,0,4));
        for ($i = 1; $i <= $n;$i++){
            if ($bln < 12) $bln++;
            else {
                $bln = 1;
                $thn++;
            }
        }
        if ($bln < 10) $bln = "0".$bln;
        return $thn."".$bln;
    }

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            if ($status == "A") {

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
            }else{

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
            }

            $auth = DB::connection($this->db)->select($strSQL);
            $auth = json_decode(json_encode($auth),true);
            if(count($auth) > 0){
                $perValid = true;
                $msg = "ok";
            }else{
                if ($status == "A") {

                    $strSQL2 = "select per_awal2 as per_awal,per_akhir2 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' ";
                }else{
    
                    $strSQL2 = "select per_awal1 as per_awal,per_akhir1 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."'";
                }
                $get = DB::connection($this->db)->select($strSQL2);
                if(count($get) > 0){
                    $per_awal = $this->namaPeriode($get[0]->per_awal);
                    $per_akhir = $this->namaPeriode($get[0]->per_akhir);
                    $msg = "Transaksi tidak dapat disimpan karena tanggal periode sudah di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif belum disetting ";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            $res = DB::connection($this->db)->select("select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,b.no_fa,a.keterangan,a.nilai1,case a.posted when 'T' then 'Close' else 'Open' end as posted,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input  
            from trans_m a
            inner join fawoapp_d b on a.no_bukti=b.no_woapp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.posted ='F' 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'no_fa' => 'required',
            'akun_deprs' => 'required',
            'harga_perolehan' => 'required',
            'kode_akun' => 'required',
            'akun_beban' => 'required',
            'total_susut' => 'required',
            'kode_ppsusut' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2('AT',$status_admin,$periode);

            if($cek['status']){

                

                    $no_bukti = $this->generateKode("trans_m", "no_bukti", "WO/".substr($periode,2, 4)."/", "0001");
    
                    $ins = DB::connection($this->db)->update("update fa_asset set progress = 'W' where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");

					$ins = DB::connection($this->db)->insert("insert into fawoapp_d(no_woapp,kode_lokasi,no_fa,periode,nilai,nilai_ap,kode_akun,akun_ap,kode_pp,kode_drk,akun_beban) values ('".$no_bukti."','".$kode_lokasi."','".$request->no_fa."','".$periode."',".floatval($request->harga_perolehan).",".floatval($request->total_susut).",'".$request->kode_akun."','".$request->akun_deprs."','".$request->kode_ppsusut."','-','".$request->akun_beban."')");					
					
					$ins = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','AT','WO','F','-','-','".$request->kode_ppsusut."','".$request->tanggal."','".$request->no_dokumen."','".$request->keterangan."','IDR',1,".floatval($request->harga_perolehan).",".floatval($request->total_susut).",0,'".$nik."','-','-','".$request->no_fa."','-','-','".$request->akun_beban."','-','-')");

					$beban = floatval($request->nilai_residu) + floatval($request->nilai_buku);
					$ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_beban."','D',".$beban.",".$beban.",'".$request->keterangan."','AT','BEBAN','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','-','-','-')");		

					$ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',1,'".$request->akun_deprs."','D',".floatval($request->total_susut).",".floatval($request->total_susut).",'".$request->keterangan."','AT','AP','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','".$request->no_fa."','-','-')");
                            					
					$ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',2,'".$request->kode_akun."','C',".floatval($request->harga_perolehan).",".floatval($request->harga_perolehan).",'".$request->keterangan."','AT','AKTAP','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','".$request->no_fa."','-','-')");	

                    DB::connection($this->db)->commit();
    
                    $msg = "Data Penyusutan berhasil disimpan.";
                    $sts = true;
                    $success['no_bukti'] = $no_bukti;

            }else{
                
                DB::connection($this->db)->rollback();
                $msg = $cek['message'];
                $sts = false;
                $success['no_bukti'] = '-';
            }    
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function getAktap(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;
            $sql = "select a.no_fa, a.nama 
            from fa_asset a 
            inner join (select kode_lokasi,no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                    from fa_nilai where periode<='".$periode."' and kode_lokasi='".$kode_lokasi."' group by kode_lokasi,no_fa) zz on a.no_fa=zz.no_fa and a.kode_lokasi=zz.kode_lokasi 							  
            inner join (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut 
                    from fasusut_d group by no_fa,kode_lokasi) b on a.no_fa=b.no_fa and a.kode_lokasi=b.kode_lokasi 
            where a.progress in ('2') and zz.nilai-isnull(tot_susut,0)>=a.nilai_residu and a.kode_lokasi='".$kode_lokasi."'";		
            $res = DB::connection($this->db)->select($sql);	
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

    public function getAkunBeban(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_akun) && $request->kode_akun != ""){
                $filter = " and a.kode_akun='$request->kode_akun' ";
            }else{
                $filter = "";
            }
            $sql = "select a.kode_akun, a.nama from masakun a where a.block='0' and a.kode_lokasi='".$kode_lokasi."' $filter";		
            $res = DB::connection($this->db)->select($sql);	
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

    public function loadData(Request $request)
    {
        $this->validate($request,[
            'periode' => 'required',
            'no_fa' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;
            $sql = "select a.no_seri,a.merk,a.tipe,zz.nilai,a.nilai_residu,isnull(d.tot_susut,0) as tot_susut,(zz.nilai-a.nilai_residu-isnull(d.tot_susut,0)) as nilai_buku, 
              b.kode_akun,x.nama as nama_akun,b.akun_deprs,y.nama as nama_deprs,a.kode_pp,c.nama as nama_pp,a.umur 
            from fa_asset a  
                   inner join (select kode_lokasi,no_fa,sum(case dc when 'D' then nilai else -nilai end) as nilai 
                               from fa_nilai where periode<='".$periode."' and kode_lokasi='".$kode_lokasi."' group by kode_lokasi,no_fa) zz on a.no_fa=zz.no_fa and a.kode_lokasi=zz.kode_lokasi 							  
            inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun 
            inner join pp c on a.kode_pp=c.kode_pp and c.kode_lokasi = '".$kode_lokasi."' 
            inner join masakun x on b.kode_akun=x.kode_akun and x.kode_lokasi = '".$kode_lokasi."' 
            inner join masakun y on b.akun_deprs=y.kode_akun and y.kode_lokasi = '".$kode_lokasi."' 
            left join 
               (select no_fa,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as tot_susut 
            	  from fasusut_d group by no_fa,kode_lokasi) d on a.no_fa=d.no_fa and a.kode_lokasi=d.kode_lokasi 
            where a.no_fa='".$request->no_fa."' and a.kode_lokasi='".$kode_lokasi."'";		
            $res = DB::connection($this->db)->select($sql);	
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

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
            'no_fa' => 'required',
            'akun_deprs' => 'required',
            'harga_perolehan' => 'required',
            'kode_akun' => 'required',
            'akun_beban' => 'required',
            'total_susut' => 'required',
            'kode_ppsusut' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2('AT',$status_admin,$periode);
            $no_bukti = $request->no_bukti;
            
            if($cek['status']){
                
                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                
                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                
                $del3 = DB::connection($this->db)->table('fawoapp_d')->where('kode_lokasi', $kode_lokasi)->where('no_woapp', $no_bukti)->delete();
                
                $upd = DB::connection($this->db)->update("update fa_asset set progress = '2' where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
                
                
                $ins = DB::connection($this->db)->update("update fa_asset set progress = 'W' where no_fa='".$request->no_fa."' and kode_lokasi='".$kode_lokasi."'");
                
                $ins = DB::connection($this->db)->insert("insert into fawoapp_d(no_woapp,kode_lokasi,no_fa,periode,nilai,nilai_ap,kode_akun,akun_ap,kode_pp,kode_drk,akun_beban) values ('".$no_bukti."','".$kode_lokasi."','".$request->no_fa."','".$periode."',".floatval($request->harga_perolehan).",".floatval($request->total_susut).",'".$request->kode_akun."','".$request->akun_deprs."','".$request->kode_ppsusut."','-','".$request->akun_beban."')");					
                
                $ins = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','AT','WO','F','-','-','".$request->kode_ppsusut."','".$request->tanggal."','".$request->no_dokumen."','".$request->keterangan."','IDR',1,".floatval($request->harga_perolehan).",".floatval($request->total_susut).",0,'".$nik."','-','-','".$request->no_fa."','-','-','".$request->akun_beban."','-','-')");
                
                $beban = floatval($request->nilai_residu) + floatval($request->nilai_buku);
                $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_beban."','D',".$beban.",".$beban.",'".$request->keterangan."','AT','BEBAN','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','-','-','-')");		
                
                $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',1,'".$request->akun_deprs."','D',".floatval($request->total_susut).",".floatval($request->total_susut).",'".$request->keterangan."','AT','AP','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','".$request->no_fa."','-','-')");
                
                $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',2,'".$request->kode_akun."','C',".floatval($request->harga_perolehan).",".floatval($request->harga_perolehan).",'".$request->keterangan."','AT','AKTAP','IDR',1,'".$request->kode_ppsusut."','-','-','-','-','-','".$request->no_fa."','-','-')");	
                
                DB::connection($this->db)->commit();
                
                $msg = "Data Penyusutan berhasil diubah.";
                $sts = true;
                $success['no_bukti'] = $no_bukti;
            }else{
                
                DB::connection($this->db)->rollback();
                $msg = $cek['message'];
                $sts = false;
                $success['no_bukti'] = '-';
            }    
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'periode' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            
            $periode = $request->periode;
            $no_bukti = $request->no_bukti;
            $cek = $this->doCekPeriode2('AT',$status_admin,$periode);
            
            if($cek['status']){
                
                    $get = DB::connection($this->db)->select("select no_ref1 as no_fa from trans_m where kode_lokasi='$kode_lokasi' and no_bukti='$no_bukti' ");
                    if(count($get) > 0){
                        $no_fa = $get[0]->no_fa;
                    }else{
                        $no_fa = '-';
                    }

                    $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
        
                    $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
        
                    $del3 = DB::connection($this->db)->table('fawoapp_d')->where('kode_lokasi', $kode_lokasi)->where('no_woapp', $no_bukti)->delete();
                    
                    $upd = DB::connection($this->db)->update("update fa_asset set progress = '2' where no_fa='".$no_fa."' and kode_lokasi='".$kode_lokasi."'");
                    
                    DB::connection($this->db)->commit();
    
                    $msg = "Data Penyusutan berhasil dihapus.";
                    $sts = true;
                    $success['no_bukti'] = $no_bukti;
                    $success['no_fa'] = $no_fa;

            }else{
                
                DB::connection($this->db)->rollback();
                $msg = $cek['message'];
                $sts = false;
                $success['no_bukti'] = '-';
            }    
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Aktiva Tetap gagal dihapus ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = " and no_bukti='$request->no_bukti' ";
            
            $sql = "select no_bukti,no_dokumen,keterangan,no_ref1 as no_fa,tanggal,param1 as akun_beban from trans_m where kode_lokasi='".$kode_lokasi."' $filter";		
            $res = DB::connection($this->db)->select($sql);	
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
}

