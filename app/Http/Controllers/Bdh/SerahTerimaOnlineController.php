<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SerahTerimaOnlineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi ='$kode_lokasi' ");
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
                    $msg = "Transaksi tidak dapat disimpan karena tanggal di periode tersebut di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif modul $modul belum disetting.";
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

    function doCekPeriode($periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[$periode_aktif]";
                }else{
                    $perNext = $this->nextNPeriode($periode,1);
                    if($perNext == "1"){
                        $perValid = true;
                        $msg = "ok";
                    }else{
                        $perValid = false;
                        $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh melebihi periode aktif sistem.[$periode_aktif]";
                    }
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

    public function getPB(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_pb,a.keterangan 
            from pbh_pb_m a 
            where a.kode_lokasi='".$kode_lokasi."' and a.progress='0' ";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNIK(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select flag from spro where kode_spro in ('NIK_DOK') and kode_lokasi = '".$kode_lokasi."'";		
            $res2 = DB::connection($this->db)->select($sql);	
            if(count($res2) > 0){
                $success['nik_default'] = $res2[0]->flag;
            }else{
                $success['nik_default'] = '-';
            }

            $sql="select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='".$kode_lokasi."'";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'no_pb' => 'required|max:20',
            'catatan' => 'required|max:200',
            'modul' => 'required',
            'nik_terima' => 'required',
            'nama_serah' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $tanggal = date('Y-m-d');
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);

            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-OL".substr($periode,2,4).".", "0001");

            $openAwal = "0"; 
			$openAkhir = "0"; 
			$cek = DB::connection($this->db)->select("select kode_spro,value1,value2 from spro where kode_spro in ('OPEN_JAM') and kode_lokasi = '".$kode_lokasi."'");			
			if (count($cek) > 0){
				$line = $cek[0];																	
				$openAwal = intval($line->value1);								
				$openAkhir = intval($line->value2);								
			}

			$formLock = 0;
			$cek2 = DB::connection($this->db)->select("SELECT cast (substring(CONVERT(VARCHAR(8),GETDATE(),108) ,1,2) as int) as jam_now");	
			if (count($cek2) > 0){
				$line = $cek2[0];				
				if (intval($line->jam_now) < $openAwal || intval($line->jam_now) > $openAkhir) {
					$formLock = 1;					
				}
			}

			$cek3 = DB::connection($this->db)->select("SELECT FORMAT(getdate(), 'dddd') AS hari");	
			if (count($cek3) > 0){
				$line = $cek3[0];
				if ($line->hari == "Sunday" || $line->hari == "Saturday") {
					$formLock = 1;	
				}
			}

			if ($formLock != 1) {

                $ins = DB::connection($this->db)->insert("insert into pbh_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat,ref1) values (?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$tanggal,$periode,$nik,'S',$request->modul,'VEROL',$request->no_pb,$request->catatan,'-',$request->nik_terima,'X',$request->nama_serah));
                    		
				$upd = DB::connection($this->db)->table('pbh_pb_m') 
                ->where('no_pb',$request->no_pb)
                ->where('kode_lokasi',$kode_lokasi)					
                ->update(['progress'=>'S', 'no_fisik'=>$no_bukti]); 
					
				//dokumen
                $arr_dok = array();
                $arr_jenis = array();
                $arr_no_urut = array();
                $i=0;
                $cek = $request->file_dok;
                if(!empty($cek)){
                    if(count($request->nama_file_seb) > 0){
                        //looping berdasarkan nama dok
                        for($i=0;$i<count($request->nama_file_seb);$i++){
                            //cek row i ada file atau tidak
                            if(isset($request->file('file_dok')[$i])){
                                $file = $request->file('file_dok')[$i];
                                //kalo ada cek nama sebelumnya ada atau -
                                if($request->nama_file_seb[$i] != "-"){
                                    //kalo ada hapus yang lama
                                    Storage::disk('s3')->delete('bdh/'.$request->nama_file_seb[$i]);
                                }
                                $nama_dok = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                $dok = $nama_dok;
                                if(Storage::disk('s3')->exists('bdh/'.$dok)){
                                    Storage::disk('s3')->delete('bdh/'.$dok);
                                }
                                Storage::disk('s3')->put('bdh/'.$dok,file_get_contents($file));
                                $arr_dok[] = $dok;
                                $arr_jenis[] = $request->kode_jenis[$i];
                                $arr_no_urut[] = $request->no_urut[$i];
                            }else if($request->nama_file_seb[$i] != "-"){
                                $arr_dok[] = $request->nama_file_seb[$i];
                                $arr_jenis[] = $request->kode_jenis[$i];
                                $arr_no_urut[] = $request->no_urut[$i];
                            }     
                        }
                        
                        $deldok = DB::connection($this->db)->table('pbh_dok') 
                        ->where('no_bukti',$request->no_pb)
                        ->where('kode_lokasi',$kode_lokasi)	
                        ->delete();
                    }
                }
                
                if(count($arr_dok) > 0){

                    for ($i=0; $i < count($arr_dok);$i++){						
                        $ins2[] = DB::connection($this->db)->insert("insert into pbh_dok(no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?)",array($request->no_pb,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,$request->modul,$request->no_pb));
                    }	
                }

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Serah Terima Dokumen berhasil disimpan";

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = "Form tidak bisa digunakan. Akses Form ini Berbatas Waktu.";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Serah Terima Dokumen gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				    
        
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_pb' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.modul,a.keterangan,convert(varchar,a.tanggal,103) as tgl,a.nilai 
            from pbh_pb_m a 
            where a.no_pb = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."' and a.progress='0' ";								
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $modul = $res[0]['modul'];
                
                // data jurnal
                if ($modul == "PBBAU" || $modul == "PBBMHD") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk from pbh_pb_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.no_pb = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "IFREIM" || $modul == "IFCLOSE") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk from hutang_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.jenis = 'BEBAN' and a.no_hutang = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJAJU") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk from panjar_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.jenis = 'BEBAN' and a.no_pj = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJPTG") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk from ptg_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.jenis = 'BEBAN' and a.no_ptg = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
                $rs2 = DB::connection($this->db)->select($strSQL2);
                $res2 = json_decode(json_encode($rs2),true);
    
                $strSQL3 = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,b.keterangan 
                from pbh_rek a
                inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti ='".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";
                $rs3 = DB::connection($this->db)->select($strSQL3);
                $res3 = json_decode(json_encode($rs3),true);
    
                $strSQL7="select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a 
                inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_bukti = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
                $rs7 = DB::connection($this->db)->select($strSQL7);
                $res7 = json_decode(json_encode($rs7),true);
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res2;
                $success['detail_rek'] = $res3;
                $success['detail_dok'] = $res7;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['detail_rek'] = [];
                $success['detail_dok'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_jurnal'] = [];
            $success['detail_rek'] = [];
            $success['detail_dok'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
