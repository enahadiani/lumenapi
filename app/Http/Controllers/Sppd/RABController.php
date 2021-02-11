<?php

namespace App\Http\Controllers\Sppd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Log;

class RABController extends Controller
{
    public $successStatus = 200;
    public $guard = 'ypt';
    public $db = 'sqlsrvypt';
    
    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query =DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function getPP(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

			if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filterkode_pp = " and a.kode_pp='$kode_pp' ";
            
            }else{
                $filterkode_pp = "";
            }
			

            $res = DB::connection($this->db)->select("select a.kode_pp,a.nama
            from pp a
            where a.kode_lokasi='$kode_lokasi' $filterkode_pp and a.flag_aktif='1' order by a.kode_pp	 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getCust(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

			if ($request->input('kode_cust') != "") {
                $kode_cust = $request->input('kode_cust');                
                $filterkode_cust = " and kode_cust='$kode_cust' ";
            
            }else{
                $filterkode_cust = "";
            }
			

            $res = DB::connection($this->db)->select("select kode_cust,nama from prb_cust where kode_lokasi='$kode_lokasi' $filterkode_cust order by kode_cust 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRAB(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

			if ($request->input('no_rab') != "") {
                $no_rab = $request->input('no_rab');                
                $filter = " and no_rab='$no_rab' ";
            
            }else{
                $filter = "";
            }
			

            $res = DB::connection($this->db)->select("select a.no_rab,a.keterangan,a.kode_pp,a.kode_cust,a.tgl_mulai,a.tgl_selesai,a.nilai,a.p_or as persen_or,a.nilai_or,a.nik_app,a.progress,
            a.pp_kelola,a.periode,a.no_dok as no_kontrak,a.nik_buat,a.tanggal,a.cat_app_proyek,a.ppn,a.pph42,a.no_memo,a.sts_va,a.bank,a.nama_rek,a.no_rek,a.tgl_admin,isnull(a.no_app_proyek,'-') as no_app_proyek 
            from prb_rab_m a
            where a.kode_lokasi='$kode_lokasi' $filter
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getProyek(Request $request){
        $this->validate($request,[
            'kode_pp' => 'required',
            'periode' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
			if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filter .= " and kode_pp='$kode_pp' ";
            
            }else{
                $filter .= "";
            }
			
            if ($request->input('kode_proyek') != "") {
                $kode_proyek = $request->input('kode_proyek');                
                $filter .= " and kode_proyek='$kode_proyek' ";
            
            }else{
                $filter .= "";
            }
			

            $res = DB::connection($this->db)->select("select a.kode_proyek,a.nama,a.flag_aktif,a.no_pks,a.kode_pp,a.kode_cust,a.tgl_mulai,a.tgl_selesai,a.nilai,a.nilai_or,a.p_or,a.kode_jenis,a.nilai_ppn,a.pph42,a.jumlah, a.nik_app,a.progress,a.no_app,a.tgl_app,a.modul,a.nik_buat, a.bank,a.nama_rek,a.no_rek,a.tgl_admin,a.pp_rab 
            from prb_proyek a            
            where a.kode_lokasi='$kode_lokasi' and 
            substring(convert(varchar,a.tgl_mulai,112),1,6) <= '".$request->periode."' and a.progress in ('1','2') and a.modul='PROYEK' and a.pp_rab='".$request->kode_pp."' and a.flag_aktif='1'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSaldoProyek(Request $request){
        $this->validate($request,[
            'kode_proyek' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
			if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filter .= " and a.kode_pp='$kode_pp' ";
            
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.kode_pp,a.kode_proyek,a.nama,(a.nilai_or - a.pph42) - isnull(c.beban,0) as saldo_or
            from prb_proyek a  
            inner join prb_proyek_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
            left join ( select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
                        from prb_prbeban_d where kode_lokasi='$kode_lokasi' 
                        group by kode_proyek,kode_lokasi 
                      ) c on a.kode_proyek=c.kode_proyek and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_proyek = '$request->kode_proyek' $filter
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['daftar'] = [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDetailProyek($kode_proyek,$no_bukti){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_pp,a.tgl_admin,a.nama,b.kode_drkb,b.akun_beban,b.akun_bdd,(a.nilai_or - a.pph42) - isnull(c.beban,0) as saldo_or, b.akun_bmhd,isnull(d.bmhd,0) as saldo_bmhd 
            from prb_proyek a 			             
               inner join prb_proyek_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
               left join ( 			             
            		select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
            		from prb_prbeban_d where kode_lokasi='$kode_lokasi' and 
            		no_bukti <> '$no_bukti' 
            		group by kode_proyek,kode_lokasi 
               ) c on a.kode_proyek=c.kode_proyek and a.kode_lokasi=c.kode_lokasi 

               left join ( 
                  select kode_proyek, kode_lokasi, sum(case dc when 'D' then nilai else -nilai end) as bmhd  
                  from prb_bmhd_d a 
                  group by kode_proyek,kode_lokasi                 
            ) d on a.kode_proyek=d.kode_proyek and a.kode_lokasi=d.kode_lokasi 
                      
            where a.kode_proyek = '$kode_proyek' and a.kode_lokasi='$kode_lokasi' 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return $success;    
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
                
                return $success;
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return $success;
        }
    }

    public function getSaldoSCH($periode,$kode_proyek,$no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $res = DB::connection($this->db)->select("
        select b.nilai_beban-isnull(c.totbeban_sch,0) as saldo_beban_sch
        from prb_proyek_d b 
        left join ( select kode_proyek,kode_lokasi,periode_sch,sum(case dc when 'D' then nilai else -nilai end) as totbeban_sch 
                    from prb_prbeban_d 
                    where modul = 'AJUBEBAN' and kode_lokasi='".$kode_lokasi."' and kode_proyek='".$kode_proyek."' and no_bukti<>'".$no_bukti."' 
                    group by kode_proyek,kode_lokasi,periode_sch
                ) c on b.kode_proyek=c.kode_proyek and b.periode=c.periode_sch and b.kode_lokasi=c.kode_lokasi 
        where b.kode_proyek='".$kode_proyek."' and b.periode='".$periode."' and b.kode_lokasi ='".$kode_lokasi."'
        ");

        if(count($res) > 0){
            return floatval($res[0]->saldo_beban_sch);
        }else{
            return 0;
        }
        
    }

    public function getPeriodeAktif($kode_lokasi){

        $res = DB::connection($this->db)->select("
        select max(periode) as periode
        from periode
        where kode_lokasi='$kode_lokasi'
        ");

        if(count($res) > 0){
            return floatval($res[0]->periode);
        }else{
            return '-';
        }
        
    }

    public function pengajuanBeban(Request $request){
        $this->validate($request,[
            'AJU' => 'required|array',
            'AJU.*.tanggal' => 'required',
            'AJU.*.kode_pp' => 'required',
            'AJU.*.kode_proyek'=> 'required',
            'AJU.*.keterangan'=> 'required',
            'AJU.*.status_pajak' => 'required',
            'AJU.*.user_input' => 'required',
            'REK' => 'required|array',
            'REK.*.nama_rek' => 'required',
            'REK.*.no_rek' => 'required',
            'REK.*.bank' => 'required',
            'REK.*.nilai_bruto' => 'required',
            'REK.*.nilai_pajak' => 'required'
        ]);
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        
        $datam= $request->input("AJU")[0];
        Log::info($request->all());
        DB::connection($this->db)->beginTransaction();
        try {
            date_default_timezone_set("Asia/Jakarta");
            $periode = substr($datam['tanggal'],0,4).substr($datam['tanggal'],5,2);
            $no_bukti = $this->generateKode("it_aju_m", "no_aju", $kode_lokasi."-".substr($periode,2,2).".", "00001");

            $detail_proyek = $this->getDetailProyek($datam['kode_proyek'],$no_bukti);
            // $success['detail_proyek'] = $detail_proyek;
            // PROTEKSI

            $app_periode = $this->getPeriodeAktif($kode_lokasi);
            if (intval($app_periode) > intval($periode)){
                $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[".$app_periode."]";
                $sts = false;
            } 
            else if (intval($app_periode) < intval($periode)){
                $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh melebihi periode aktif sistem.[".$app_periode."]";
                $sts = false;
            }
            else{

                if($detail_proyek['status']){
                    $line = $detail_proyek['data'][0];
                    $ppKelola = $line['kode_pp'];
                    $saldo_or = $line['saldo_or'];
                    $saldo_bmhd = $line['saldo_bmhd'];
                    $akun_beban = $line['akun_beban'];
                    $akunBDD = $line['akun_bdd'];
                    $kode_drk = $line['kode_drkb'];
                    $tgladm = $line['tgl_admin'];
    
                    $date1=date_create($tgladm);
                    $date2=date_create($datam['tanggal']);
                    $diff=date_diff($date1,$date2);
                    $jumlah = intval($diff->format("%R%a"));
                    if ($jumlah > 0) {
                        $msg = "Transaksi tidak valid. Tanggal Transaksi melebihi Tgl Maksimal Administrasi";
                        $sts = false;
                    }else{
    
                        if (floatval($saldo_or) == 0 && floatval($saldo_bmhd) != 0) {
                            $modeBMHD = "BMHD";				
                            $akun_beban = $line['akun_bmhd'];			
                            $kode_drk = "-";
                        }
                        else $modeBMHD = "NON";
        
                        $saldo_sch = $this->getSaldoSCH($periode,$datam['kode_proyek'],$no_bukti);
        
                        //nyimpen kode_pp transaksi
                        $total_bruto =0; $total_netto =0;$total_pajak=0;
                        $datarek = $request->input('REK');
                        if (count($datarek) > 0){
                            for ($i=0;$i < count($datarek); $i++){			$total_bruto += $datarek[$i]['nilai_bruto'];
                                $total_pajak += $datarek[$i]['nilai_pajak'];
                                $nbersih = 	$datarek[$i]['nilai_bruto'] - $datarek[$i]['nilai_pajak'];
                                $total_netto += $nbersih;
                                $ins9 = DB::connection($this->db)->insert("insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_bukti."','".$kode_lokasi."','".$datarek[$i]['bank']."','".$datarek[$i]['no_rek']."','".$datarek[$i]['nama_rek']."','-',".$nbersih.",'".$datam['keterangan']."',".$datarek[$i]['nilai_pajak'].",'-')");	
                            }
                        }
    
                        if (floatval($total_bruto) <= 0) {
                            $msg = "Nilai transaksi tidak valid.Nilai tidak boleh nol atau kurang.";
                            $sts = false;
                            DB::connection($this->db)->rollback();
                        }else{
    
                            if (floatval($total_pajak) != 0 && $datam['status_pajak'] == "NON") {
                                $msg = "Transaksi tidak valid. Nilai Pajak tidak sesuai dengan status pajak.";
                                $sts = false;
                                DB::connection($this->db)->rollback();
                            }else{
    
                                if ($modeBMHD == "NON" && (floatval($total_bruto) > floatval($saldo_or))) {
                                    $msg = "Nilai transaksi tidak valid.Nilai tidak boleh melebihi Saldo OR.";
                                    $sts = false;
                                    DB::connection($this->db)->rollback();
                                }else if ($modeBMHD == "BMHD" && (floatval($total_bruto) > floatval($saldo_bmhd))) {
                                    $msg = "Nilai transaksi tidak valid. Nilai tidak boleh melebihi Saldo BMHD.";
                                    $sts = false;
                                    DB::connection($this->db)->rollback();
                                }else{
                                    $ins = DB::connection($this->db)->insert("insert into it_aju_m(no_aju,kode_lokasi,periode,tanggal,modul,kode_akun,kode_pp,kode_drk,keterangan,nilai,tgl_input,nik_user,no_ver,no_fiat,no_kas,progress,nik_panjar,no_ptg,user_input,form,sts_pajak,npajak,no_ref1,dasar) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$datam['tanggal']."','UMUM','".$akun_beban."','".$ppKelola."','".$kode_drk."','".$datam['kode_proyek']." | ".$datam['keterangan']."',".floatval($total_netto).",getdate(),'".$nik."','-','-','-','A','-','-','".$datam['user_input']."','PRBEBAN','".$datam['status_pajak']."',".floatval($total_pajak).",'".$modeBMHD."','".$datam['kode_pp']."')");				
                                    //jurnal bisa lebih dari satu akun (it_aju_d), akun_beban proyek dan akun_bdd 
                                    //(jika nilai pengajuan melebihi saldo schedule,kelebihan nilai pengajuan (nilai aju-saldo sch) di BDD-kan)		
                        
                                    if ($modeBMHD == "NON") {
                                        $nilaiBeban = 0; $nilaiBDD = 0;
                                        if (floatval($total_bruto) > floatval($saldo_sch))	{
                                            $nilaiBeban = floatval($saldo_sch);
                                            $nilaiBDD = floatval($total_bruto) - floatval($saldo_sch);						
                                            $ins2 = DB::connection($this->db)->insert("insert into prb_prbeban_d(no_bukti,kode_lokasi,periode,periode_sch,tanggal,kode_akun,kode_pp,kode_drk,keterangan,dc,nilai,tgl_input,kode_proyek,modul,no_ref1,jenis) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$periode."','".$datam['tanggal']."','".$akunBDD."','".$ppKelola."','-','".$datam['keterangan']."','D',".$nilaiBDD.",getdate(),'".$datam['kode_proyek']."','BDD','-','ITAJU')");									
                                            $ins3 = DB::connection($this->db)->insert("insert into it_aju_d (no_aju,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,akun_seb) values ('".$no_bukti."','-','".$datam['tanggal']."',0,'".$akunBDD."','".$datam['kode_proyek']." | ".$datam['keterangan']."','D','IDR',1,".$nilaiBDD.",".$nilaiBDD.",'".$ppKelola."','-','".$kode_lokasi."','PRBEBAN','BDD','".$periode."','".$nik."',getdate(),'-')");		
                                        }
                                        else {
                                            $nilaiBeban = floatval($total_bruto);						
                                        }
                                        
                                        if ($nilaiBeban != 0) {
                                            $ins4 = DB::connection($this->db)->insert("insert into prb_prbeban_d(no_bukti,kode_lokasi,periode,periode_sch,tanggal,kode_akun,kode_pp,kode_drk,keterangan,dc,nilai,tgl_input,kode_proyek,modul,no_ref1,jenis) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$periode."','".$datam['tanggal']."','".$akun_beban."','".$ppKelola."','".$kode_drk."','".$datam['kode_proyek']." | ".$datam['keterangan']."','D',".$nilaiBeban.",getdate(),'".$datam['kode_proyek']."','AJUBEBAN','-','ITAJU')");												
                                            $ins5 = DB::connection($this->db)->insert("insert into it_aju_d (no_aju,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,akun_seb) values ('".$no_bukti."','-','".$datam['tanggal']."',0,'".$akun_beban."','".$datam['kode_proyek']." | ".$datam['keterangan']."','D','IDR',1,".$nilaiBeban.",".$nilaiBeban.",'".$ppKelola."','".$kode_drk."','".$kode_lokasi."','PRBEBAN','BEBAN','".$periode."','".$nik."',getdate(),'-')");
                        
                                            $ins6 = DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values ('".$no_bukti."','PRBEBAN','".$kode_lokasi."','".$akun_beban."','".$ppKelola."','".$kode_drk."','".$periode."','".$periode."','D',0,".$nilaiBeban.")");
                                        }
                                        
                                    }					
                                    else {
                                        //BMHD
                                        $ins7 = DB::connection($this->db)->insert("insert into it_aju_d (no_aju,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,akun_seb) values ('".$no_bukti."','-','".$datam['tanggal']."',0,'".$akun_beban."','".$datam['keterangan']."','D','IDR',1,".floatval($total_bruto).",".floatval($total_bruto).",'".$ppKelola."','-','".$kode_lokasi."','PRBEBAN','BMHD','".$periode."','".$nik."',getdate(),'-')");
                        
                                        $ins8 = DB::connection($this->db)->insert("insert into prb_bmhd_d(no_bukti,kode_lokasi,periode,tanggal,kode_akun,kode_pp,keterangan,dc,nilai,tgl_input,kode_proyek,modul,no_ref1) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$datam['tanggal']."','".$akun_beban."','".$ppKelola."','".$datam['keterangan']."','C',".floatval($total_bruto).",getdate(),'".$datam['kode_proyek']."','AJUBMHD','-')");	
                                    }
                    
                                    DB::connection($this->db)->commit();
                                    $sts = true;
                                    $msg = "Data pengajuan beban berhasil disimpan";
                                    $success['no_bukti'] = $no_bukti;
                                }
                            }
                        }
        
                    }
                }else{
                    $sts = true;
                    $msg = "Data pengajuan beban gagal disimpan. Kode Proyek tidak valid.";
    
                }
            }

            
            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
          
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

}
