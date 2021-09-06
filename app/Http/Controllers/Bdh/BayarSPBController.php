<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class BayarSPBController extends Controller
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
                    $perNext = $this->nextNPeriode($periode);
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

    public function generateNo(Request $request) {
        $this->validate($request, [    
            'tanggal' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-BK".substr($periode,2,4).".", "0001");

            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.no_kas,convert(varchar,a.tanggal,103) as tgl,a.jenis,a.no_dokumen,a.keterangan,a.nilai 
            from kas_m a 	
            left join ( 
            	select no_kas,kode_lokasi from yk_kasdrop_d where no_kasterima <> '-' and kode_lokasi='".$kode_lokasi."' 
            )	b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            where b.no_kas is null and a.kode_lokasi='".$kode_lokasi."' and a.modul = 'KBSPB' and a.posted ='F'";

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

    public function getAkunKasBank(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            where b.kode_flag in ('001','009') and b.kode_lokasi='$kode_lokasi' ";

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
            'tanggal' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required|max:20',
            'deskripsi' => 'required',
            'nilai_kasbank' => 'required',
            'akun_kasbank' => 'required',
            'status' => 'required|array',
            'no_spb' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-BK".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $ins = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$request->no_dokumen,'-',$request->akun_kasbank,$request->tanggal,$request->deskripsi,$kode_pp,'KBSPB','BK',$periode,'IDR',1,floatval($request->nilai_kasbank),$nik,$nik,getdate(),$nik,'F','-','-','-','-'));	

				$ins2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ()", array($no_bukti,$request->no_dokumen,$request->tanggal,999,$request->akun_kasbank,$request->deskripsi,'C',"+nilaiToFloat(this.e_nilaikb.getText())+",$kode_pp,'-','-','-',$kode_lokasi,'KBSPB','KB',$periode,'IDR',1,$nik,getdate(),'-',"+nilaiToFloat(this.e_nilaikb.getText())+"));
										
				for($i=0;$i<count($request->no_spb);$i++){
                    if ($request->status[$i] == "BAYAR") {
                        $upd1[] = DB::connection($this->db)->table("pbh_pb_m")
                        ->where('no_spb',$request->no_spb) 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update([
                            'progress'=>'3', 
                            'no_kas'=>$no_bukti 
                        ]);

                        $upd2[] = DB::connection($this->db)->table("spb_m")
                        ->where('no_spb',$kode_lokasi) 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update([
                            'progress'=>'1', 
                            'no_kas'=>$no_bukti 
                        ]);						
                        
                        $ins3[] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr)
                            select ?,no_dokumen,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,'-','-',?,'KBSPB',jenis,?,'IDR',1,?,getdate(),'-',nilai 
                            from spb_j 
                            where no_spb=? and kode_lokasi=? ",array($no_bukti,$request->tanggal,$kode_lokasi,$periode,$nik,$request->no_spb[$i],$kode_lokasi));							

                        //------update status modul ---------
                        //PANJAR
                        $upd3[] = DB::connection($this->db)->update("update a set a.progress='2', a.no_kas=? 
                            from panjar_m a 
                            inner join pbh_pb_m b on a.no_pj=b.no_pb and a.kode_lokasi=b.kode_lokasi 
                            where b.no_spb=? and b.kode_lokasi=? ",array($no_bukti,$request->no_spb[$i],$kode_lokasi));									
                        //gak usah muter dulu ke spb_j
                        // "inner join spb_j c on b.no_pb=c.no_dokumen and b.kode_lokasi=c.kode_lokasi "+
                        // "where c.no_spb='"+line.no_spb+"' and c.kode_lokasi=$kode_lokasi ");
                            
                        //DROPING		
                        $upd4[] = DB::connection($this->db)->update("update a set a.progress='2', a.no_kas=? 
                                from ys_minta_m a 
                                inner join pbh_pb_m b on a.no_minta=b.no_pb and a.kode_lokasi=b.kode_loktuj 
                                where b.no_spb=? and b.kode_lokasi=? ",array($no_bukti,$request->no_spb[$i],$kode_lokasi));	

                        $ins4[] = DB::connection($this->db)->insert("insert into yk_kasdrop_d(no_spb,nu,no_kas,no_dokumen,kode_lokasi,periode,kode_loktuj,kode_rek,keterangan,nilai,progress,akun_tak,no_kasterima) 
                            select b.no_spb,0,?,a.no_pb,?,?,a.kode_loktuj,'-',a.keterangan,a.nilai,'0',a.akun_hutang,'-' 
                            from pbh_pb_m a 
                            inner join spb_m b on a.no_spb=b.no_spb and a.kode_lokasi=b.kode_lokasi and a.modul='PBMINTA' 
                            where a.no_spb = ? ", array($no_bukti,$kode_lokasi,$periode,$request->no_spb[$i]));
                    }
                }

                if (count($request->kode_akun) > 0){
                    for ($i=0; $i < count($request->kode_akun);$i++){
                        $k = 1000+$i;
                        $ins5[] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(),?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$k,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],'-','-','-',$kode_lokasi,'KBSPB','NONKB',$periode,'IDR',1,$nik,'-',floatval($request->nilai[$i])));			
                    }
                }						
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pembayaran SPB berhasil disimpan";

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran SPB gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				   
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required|max:20',
            'deskripsi' => 'required',
            'nilai_kasbank' => 'required',
            'akun_kasbank' => 'required',
            'status' => 'required|array',
            'no_spb' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $request->no_bukti;

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $del = DB::connection($this->db)->table("kas_m")
                ->where('no_kas',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $del2 = DB::connection($this->db)->table("kas_j")
                ->where('no_kas',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $upd = DB::connection($this->db)->table("pbh_pb_m")
                ->where('no_kas',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)										
                ->update(['progress'=>'2', 'no_kas'=>'-']);

                $upd2 = DB::connection($this->db)->table("spb_m")
                ->where('no_kas',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)										
                ->update(['progress'=>'0', 'no_kas'=>'-']);

                $del3 = DB::connection($this->db)->table("yk_kasdrop_d")
                ->where('no_kas',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();
                
                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $ins = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$request->no_dokumen,'-',$request->akun_kasbank,$request->tanggal,$request->deskripsi,$kode_pp,'KBSPB','BK',$periode,'IDR',1,floatval($request->nilai_kasbank),$nik,$nik,getdate(),$nik,'F','-','-','-','-'));	

				$ins2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ()", array($no_bukti,$request->no_dokumen,$request->tanggal,999,$request->akun_kasbank,$request->deskripsi,'C',"+nilaiToFloat(this.e_nilaikb.getText())+",$kode_pp,'-','-','-',$kode_lokasi,'KBSPB','KB',$periode,'IDR',1,$nik,getdate(),'-',"+nilaiToFloat(this.e_nilaikb.getText())+"));
										
				for($i=0;$i<count($request->no_spb);$i++){
                    if ($request->status[$i] == "BAYAR") {
                        $upd1[] = DB::connection($this->db)->table("pbh_pb_m")
                        ->where('no_spb',$request->no_spb) 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update([
                            'progress'=>'3', 
                            'no_kas'=>$no_bukti 
                        ]);

                        $upd2[] = DB::connection($this->db)->table("spb_m")
                        ->where('no_spb',$kode_lokasi) 
                        ->where('kode_lokasi',$kode_lokasi)
                        ->update([
                            'progress'=>'1', 
                            'no_kas'=>$no_bukti 
                        ]);						
                        
                        $ins3[] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr)
                            select ?,no_dokumen,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,'-','-',?,'KBSPB',jenis,?,'IDR',1,?,getdate(),'-',nilai 
                            from spb_j 
                            where no_spb=? and kode_lokasi=? ",array($no_bukti,$request->tanggal,$kode_lokasi,$periode,$nik,$request->no_spb[$i],$kode_lokasi));							

                        //------update status modul ---------
                        //PANJAR
                        $upd3[] = DB::connection($this->db)->update("update a set a.progress='2', a.no_kas=? 
                            from panjar_m a 
                            inner join pbh_pb_m b on a.no_pj=b.no_pb and a.kode_lokasi=b.kode_lokasi 
                            where b.no_spb=? and b.kode_lokasi=? ",array($no_bukti,$request->no_spb[$i],$kode_lokasi));									
                        //gak usah muter dulu ke spb_j
                        // "inner join spb_j c on b.no_pb=c.no_dokumen and b.kode_lokasi=c.kode_lokasi "+
                        // "where c.no_spb='"+line.no_spb+"' and c.kode_lokasi=$kode_lokasi ");
                            
                        //DROPING		
                        $upd4[] = DB::connection($this->db)->update("update a set a.progress='2', a.no_kas=? 
                                from ys_minta_m a 
                                inner join pbh_pb_m b on a.no_minta=b.no_pb and a.kode_lokasi=b.kode_loktuj 
                                where b.no_spb=? and b.kode_lokasi=? ",array($no_bukti,$request->no_spb[$i],$kode_lokasi));	

                        $ins4[] = DB::connection($this->db)->insert("insert into yk_kasdrop_d(no_spb,nu,no_kas,no_dokumen,kode_lokasi,periode,kode_loktuj,kode_rek,keterangan,nilai,progress,akun_tak,no_kasterima) 
                            select b.no_spb,0,?,a.no_pb,?,?,a.kode_loktuj,'-',a.keterangan,a.nilai,'0',a.akun_hutang,'-' 
                            from pbh_pb_m a 
                            inner join spb_m b on a.no_spb=b.no_spb and a.kode_lokasi=b.kode_lokasi and a.modul='PBMINTA' 
                            where a.no_spb = ? ", array($no_bukti,$kode_lokasi,$periode,$request->no_spb[$i]));
                    }
                }

                if (count($request->kode_akun) > 0){
                    for ($i=0; $i < count($request->kode_akun);$i++){
                        $k = 1000+$i;
                        $ins5[] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(),?, ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,$k,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],'-','-','-',$kode_lokasi,'KBSPB','NONKB',$periode,'IDR',1,$nik,'-',floatval($request->nilai[$i])));			
                    }
                }						
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pembayaran SPB berhasil diubah";

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran SPB gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_kas' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_kas;			

            $del = DB::connection($this->db)->table("kas_m")
            ->where('no_kas',$no_bukti)
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $del2 = DB::connection($this->db)->table("kas_j")
            ->where('no_kas',$no_bukti)
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $upd = DB::connection($this->db)->table("pbh_pb_m")
            ->where('no_kas',$no_bukti)
            ->where('kode_lokasi',$kode_lokasi)										
            ->update(['progress'=>'2', 'no_kas'=>'-']);

            $upd2 = DB::connection($this->db)->table("spb_m")
            ->where('no_kas',$no_bukti)
            ->where('kode_lokasi',$kode_lokasi)										
            ->update(['progress'=>'0', 'no_kas'=>'-']);

            $del3 = DB::connection($this->db)->table("yk_kasdrop_d")
            ->where('no_kas',$no_bukti)
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran SPB berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran SPB gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_aju' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // data main
            if(isset($request->no_kas) && $request->no_kas != "-"){
                $sql = "select a.due_date,a.no_pb as no_bukti,case a.progress when '1' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_kas as no_kas1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                      inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                where a.progress in ('1','V') and a.no_pb='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD', 'PBBA', 'PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') ";
            }else{

                $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,a.nik_user as pembuat,a.no_kas as no_kas1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 				 
                where a.progress='P' and a.no_pb='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD','PBBA','PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') order by no_pb";
            }
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $modul = $res[0]['modul'];
                $status = $res[0]['status'];
                $no_kaslama = $res[0]['no_kas1'];
                
                // data jurnal
                if ($modul == "PBBAU" || $modul == "PBBMHD" || $modul == "PBADK" || $modul == "PBBA") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                    from pbh_pb_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.no_pb = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "IFREIM" || $modul == "IFCLOSE") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk
                        from hutang_j a 
                            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                            where a.jenis in ('BEBAN','PAJAK') and a.no_hutang = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJAJU") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                    from panjar_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                    where a.jenis in ('BEBAN','PAJAK') and a.no_pj = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJPTG") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                    from ptg_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                        where a.jenis in ('BEBAN','PAJAK') and a.no_ptg = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
                $rs3 = DB::connection($this->db)->select($strSQL3);
                $res3 = json_decode(json_encode($rs3),true);

                $strSQL5 = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,b.keterangan 
                from pbh_rek a
                inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti ='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";
                $rs5 = DB::connection($this->db)->select($strSQL5);
                $res5 = json_decode(json_encode($rs5),true);
    
                $strSQL6 = "select distinct convert(varchar,tgl_input,103) as tgl
                from kas_m 
                where no_bukti='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."' 
                order by convert(varchar,tgl_input,103) desc";
                $rs6 = DB::connection($this->db)->select($strSQL6);
                $res6 = json_decode(json_encode($rs6),true);
    
                $strSQL7="select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a 
                inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_ref = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
                $rs7 = DB::connection($this->db)->select($strSQL7);
                $res7 = json_decode(json_encode($rs7),true);
                $memo = "-";
                if($status != "INPROG") {
                
                    $sql = "select catatan from kas_m where no_kas ='".$no_kaslama."' and kode_lokasi='".$kode_lokasi."'";
                    $rs8 = DB::connection($this->db)->select($sql);
                    if (count($rs8) > 0){
                        $line = $rs8[0];	
                        $memo = $line->catatan;	
                    }			
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res3;
                $success['detail_rek'] = $res5;
                $success['detail_dok'] = $res7;
                if(count($res6) > 0){
                    $i=0;
                    foreach($res6 as $row){
                        $sql = "select catatan,no_kas, convert(varchar,tgl_input,103) as tgl,convert(varchar,tgl_input,108) as jam,nik_user 
                        from kas_m 
                        where no_bukti='".$request->no_aju."' and convert(varchar,tgl_input,103)='".$row['tgl']."' and kode_lokasi='".$kode_lokasi."' 
                        order by convert(varchar,tgl_input,103) desc,convert(varchar,tgl_input,108) desc ";
                        $rs6 = DB::connection($this->db)->select($sql);
                        $res6[$i]['detail'] = json_decode(json_encode($rs6),true);
                        $i++;
                    }
                }
                $success['detail_catatan'] = $res6;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['detail_rek'] = [];
                $success['detail_dok'] = [];
                $success['detail_catatan'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_jurnal'] = [];
            $success['detail_rek'] = [];
            $success['detail_dok'] = [];
            $success['detail_catatan'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
    
    public function getAkun(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun,a.nama from masakun a 
            inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '035' 
            left  join flag_relasi c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi and c.kode_flag in ('001','009') 
            where c.kode_akun is null and a.block= '0' and a.kode_lokasi ='".$kode_lokasi."' ";

            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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

    public function getPP(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select kode_pp, nama  from pp where kode_lokasi = '".$kode_lokasi."' and tipe='posting' and flag_aktif ='1'";
            
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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

    public function getSPBList(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select 'INPROG' as status,no_spb,convert(varchar,tanggal,103) as tgl,keterangan,nilai 
            from spb_m 
            where progress='0' and no_kas='-' and kode_lokasi='".$kode_lokasi."' and modul='SPB'";
            
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
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

    public function getRekTransfer(Request $request)
    {
        $this->validate($request,[
            'no_spb' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $this_in = "";
            $tmp = explode(",",$request->input('no_spb'));
            for($x=0;$x<count($tmp);$x++){
                if($x == 0){
                    $this_in .= "'".$tmp[$x]."'";
                }else{
                    
                    $this_in .= ","."'".$tmp[$x]."'";
                }
            }

            $where = "where b.kode_lokasi='$kode_lokasi' and b.no_spb in ($this_in) ";

            $sql = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,a.nilai 
            from pbh_rek a inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi and a.modul<>'PINBUK-C' 
            $where ";

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


}
