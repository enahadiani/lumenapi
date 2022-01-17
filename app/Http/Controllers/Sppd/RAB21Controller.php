<?php

namespace App\Http\Controllers\Sppd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Log;

class RAB21Controller extends Controller
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
			

            $res = DB::connection($this->db)->select("select a.kode_pp, a.nama from pp a inner join karyawan_pp d on a.kode_pp = d.kode_pp and a.kode_lokasi=d.kode_lokasi and d.nik='".$nik."' where a.flag_aktif ='1' and a.kode_lokasi='$kode_lokasi' $filterkode_pp order by a.kode_pp	 
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
			
            if ($request->input('kode_proyek') != "") {
                $kode_proyek = $request->input('kode_proyek');                
                $filter .= " and kode_proyek='$kode_proyek' ";
            
            }else{
                $filter .= "";
            }
			

            $res = DB::connection($this->db)->select("select kode_proyek, nama from prb_proyek where versi = 'NTF21' and substring(convert(varchar,tgl_mulai,112),1,6) <= '".$request->periode."' and progress in ('1','2') and modul='PROYEK' and pp_rab='".$request->kode_pp."' and flag_aktif='1' and kode_lokasi='$kode_lokasi' $filter
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

            $res = DB::connection($this->db)->select("select a.kode_proyek,a.nama,a.nilai_or - isnull(c.beban,0) as saldo_or 
            from prb_proyek a 			             
               inner join prb_proyek_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 			            						 
               left join ( 			             
            			select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
            			from prb_prbeban_d where kode_lokasi='".$kode_lokasi."' 
                        --and no_bukti <> '".$request->no_bukti."' 
            			group by kode_proyek,kode_lokasi 			             
               ) c on a.kode_proyek=c.kode_proyek and a.kode_lokasi=c.kode_lokasi 		
            where a.kode_proyek = '".$request->kode_proyek."' and a.kode_lokasi='$kode_lokasi' $filter
            ");
            $res = json_decode(json_encode($res),true);
            
            $success['rows']=count($res);
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                
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

            $sql = "select a.kode_pp,a.tgl_admin,a.nama,b.akun_bdd,a.nilai_or - isnull(c.beban,0) as saldo_or 
            from prb_proyek a 			             
               inner join prb_proyek_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 			            						 
               left join ( 			             
            			select kode_proyek,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as beban 
            			from prb_prbeban_d where kode_lokasi='".$kode_lokasi."' and 
            			no_bukti <> '".$no_bukti."' 
            			group by kode_proyek,kode_lokasi 			             
               ) c on a.kode_proyek=c.kode_proyek and a.kode_lokasi=c.kode_lokasi 		
            where a.kode_proyek = '".$kode_proyek."' and a.kode_lokasi='".$kode_lokasi."' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return $success;    
            }
            else{
                $success['message'] = "Data Kosong!".$sql;
                $success['data'] = [];
                $success['status'] = false;
                $success['rows']=count($res);
                
                return $success;
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return $success;
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
            'PBYR' => 'required|array',
            'PBYR.*.keterangan'=> 'required',
            'PBYR.*.tanggal' => 'required',
            'PBYR.*.jenis' => 'required',
            'PBYR.*.lama' => 'required',
            'PBYR.*.kota' => 'required',
            'PBYR.*.sarana' => 'required',
            'PBYR.*.catatan' => 'required',
            'PBYR.*.nik_buat' => 'required',
            'PBYR.*.nik_app' => 'required',
            'PBYR.*.kode_pp' => 'required',
            'PBYR.*.kode_proyek'=> 'required',
            'PBYR.*.status_pajak' => 'required',
            'PBYR.*.user_input' => 'required',
            'PBYR.*.tipe_transfer' => 'required',
            'AJU.*.name' => 'required',
            'AJU.*.nip' => 'required',
            'AJU.*.transport' => 'required',
            'AJU.*.lain_lain' => 'required',
            'AJU.*.harian' => 'required',
            'AJU.*.total_biaya' => 'required',
            'AJU.*.nama_perjalanan' => 'required',
            'AJU.*.pp_code' => 'required',
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
        
        $datam= $request->input("PBYR")[0];
        Log::info($request->all());
        DB::connection($this->db)->beginTransaction();
        try {
            date_default_timezone_set("Asia/Jakarta");
            $periode = substr($datam['tanggal'],0,4).substr($datam['tanggal'],5,2);
            $no_bukti = $this->generateKode("it_aju_m", "no_aju", $kode_lokasi."-".substr($periode,2,2).".", "00001");
            $no_app = $this->generateKode("tu_pdapp_m", "no_app", $kode_lokasi."-PDA".substr($periode,2,4).".", "0001");

            // SAVE LOG TO DB
            $log = print_r($request->input(), true); 
            $save_log = DB::connection($this->db)->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis)
            values (?, ?, getdate(), ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,$log,'AJUBEBAN21'));
            // END SAVE

            $detail_proyek = $this->getDetailProyek($datam['kode_proyek'],$no_bukti);
            
            // GET SPRO
            $akunPPH21 = "-";
            $akunPPH23 = "-";
            $get = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('PPH21','PPH23') and kode_lokasi = '".$kode_lokasi."'");	
            $get = json_decode(json_encode($get),true);		
			if (count($get) > 0){
				for ($i=0; $i < count($get); $i++){
					$line = $get[$i];																	
					if ($line['kode_spro'] == "PPH21") {
                        $akunPPH21 = $line['flag'];
                    }								
					if ($line['kode_spro'] == "PPH23") {
                        $akunPPH23 = $line['flag'];	
                    }							
				}				
			}

            // PROTEKSI

            $app_periode = $this->getPeriodeAktif($kode_lokasi);
            if (intval($app_periode) > intval($periode)){
                $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[".$app_periode."]";
                $sts = false;
            } 
            else{

                if($detail_proyek['status']){
                    $line = $detail_proyek['data'][0];
                    $ppKelola = $line['kode_pp'];
                    $saldo_or = $line['saldo_or'];
                    $akunBDD = $line['akun_bdd'];
                    $akun_beban = $akunBDD;
                    $tgladm = $line['tgl_admin'];
    
                    $date1=date_create($tgladm);
                    $date2=date_create($datam['tanggal']);
                    $diff=date_diff($date1,$date2);
                    $jumlah = intval($diff->format("%R%a"));
                    /*
                    if ($jumlah > 0) {
                        $msg = "Transaksi tidak valid. Tanggal Transaksi melebihi Tgl Maksimal Administrasi";
                        $sts = false;
                    }else{
                    */

                        $total_bruto =0; $total_netto =0;$total_pajak=0;
                        $datarek = $request->input('REK');
                        if (count($datarek) > 0){
                            for ($i=0;$i < count($datarek); $i++){			
                                $total_bruto += $datarek[$i]['nilai_bruto'];
                                $total_pajak += $datarek[$i]['nilai_pajak'];
                                $nbersih = 	$datarek[$i]['nilai_bruto'] - $datarek[$i]['nilai_pajak'];
                                $total_netto += $nbersih;
                                $ins9 = DB::connection($this->db)->insert("insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_bukti."','".$kode_lokasi."','".$datarek[$i]['bank']."','".$datarek[$i]['no_rek']."','".$datarek[$i]['nama_rek']."','-',".$nbersih.",'".$datam['keterangan']."',".$datarek[$i]['nilai_pajak'].",'-')");	
                            }
                        }

                        
                        $data_aju = $request->input('AJU');
                        $total_biaya =0;
                        if (count($data_aju) > 0){
                            $nu = 1;
                            for ($i=0;$i < count($data_aju); $i++){	
                                $no_spj = $no_bukti."-".strval($nu);                               
                                $total_biaya += $data_aju[$i]['total_biaya'];

                                $ins10 = DB::connection($this->db)->insert("insert into tu_pdaju_m (no_spj,tanggal,kode_lokasi,kode_pp,kode_akun,kode_drk,keterangan,nik_buat,nik_spj,periode,tgl_input,progress,no_app,nilai,jenis_pd,sts_bmhd,kode_proyek) values ('$no_spj',getdate(),'".$kode_lokasi."','".$data_aju[$i]['pp_code']."','".$akun_beban."','-','".$data_aju[$i]['nama_perjalanan']."','".$datam['nik_buat']."','".$data_aju[$i]['nip']."','".$periode."',getdate(),'1','".$no_bukti."',".$data_aju[$i]['total_biaya'].",'-','-','-') ");

                                $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','91',1,".$data_aju[$i]['transport'].",".$data_aju[$i]['transport'].") ";

                                $insAjud1 = DB::connection($this->db)->insert($sql);
                                
                                $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','92',1,".$data_aju[$i]['harian'].",".$data_aju[$i]['harian'].") ";

                                $insAjud2 = DB::connection($this->db)->insert($sql);

                                $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','93',1,".$data_aju[$i]['lain_lain'].",".$data_aju[$i]['lain_lain'].") ";

                                $insAjud3 = DB::connection($this->db)->insert($sql);

                                $nu++;
                            }
                        }
    
                        
                        if (floatval($total_bruto) <= 0) {
                            $msg = "Nilai transaksi tidak valid.Nilai tidak boleh nol atau kurang.";
                            $sts = false;
                            DB::connection($this->db)->rollback();
                        }else{

                            if(floatval($total_bruto) != floatval($total_biaya)){
                                $msg = "Total nilai rekening tidak sama dengan total dipengajuan.";
                                $sts = false;
                                DB::connection($this->db)->rollback();
                            }else{

                                if (floatval($total_pajak) != 0 && $datam['status_pajak'] == "NON") {
                                    $msg = "Transaksi tidak valid. Nilai Pajak tidak sesuai dengan status pajak.";
                                    $sts = false;
                                    DB::connection($this->db)->rollback();
                                }else{
        
                                    if (floatval($total_bruto) > floatval($saldo_or)) {
                                        $msg = "Nilai transaksi tidak valid.Nilai tidak boleh melebihi Saldo OR.";
                                        $sts = false;
                                        DB::connection($this->db)->rollback();
                                    }else{
                                        $ins = DB::connection($this->db)->insert("insert into it_aju_m(no_aju,kode_lokasi,periode,tanggal,modul,kode_akun,kode_pp,kode_drk,keterangan,nilai,tgl_input,nik_user,no_ver,no_fiat,no_kas,progress,nik_panjar,no_ptg,user_input,form,sts_pajak,npajak,no_ref1,dasar) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$datam['tanggal']."','UMUM','".$akun_beban."','".$ppKelola."','-','".$datam['kode_proyek']." | ".$datam['keterangan']."',".floatval($total_netto).",getdate(),'".$nik."','-','-','-','A','-','-','".$datam['user_input']."','NTF19','".$datam['status_pajak']."',".floatval($total_pajak).",'-','".$datam['kode_pp']."')");				
                                        
                                        $nilaiBeban = floatval($total_bruto);						
                                        $ins2 = DB::connection($this->db)->insert("insert into it_aju_d (no_aju,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,akun_seb) values ('".$no_bukti."','-','".$datam['tanggal']."',0,'".$akun_beban."','".$datam['kode_proyek']." | ".$datam['keterangan']."','D','IDR',1,".$nilaiBeban.",".$nilaiBeban.",'".$ppKelola."','-','".$kode_lokasi."','NTF19','BEBAN','".$periode."','".$nik."',getdate(),'-')");		

                                        if($datam['status_pajak'] != "NON"){
                                            if($datam['status_pajak'] == "P21"){
                                                $akunPajak = $akunPPH21;
                                            }
                                            else{
                                                $akunPajak = $akunPPH23;
                                            }

                                            $ins3 = DB::connection($this->db)->insert("insert into it_aju_d (no_aju,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,akun_seb) values ('".$no_bukti."','".$datam['kode_proyek']."','".$datam['tanggal']."',0,'".$akunPajak."','".$datam['keterangan']."','C','IDR',1,".$total_pajak.",".$total_pajak.",'".$ppKelola."','-','".$kode_lokasi."','NTF19','".$datam['status_pajak']."','".$periode."','".$nik."',getdate(),'-')");		
                                        }
                                        
                                        $ins4 = DB::connection($this->db)->insert("insert into prb_prbdd_d(no_bukti,kode_lokasi,periode,tanggal,kode_akun,kode_pp,keterangan,dc,nilai,tgl_input,nik_user,kode_proyek,form) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$datam['tanggal']."','".$akun_beban."','".$ppKelola."','".$datam['keterangan']."','D',".$nilaiBeban.",getdate(),'$nik','".$datam['kode_proyek']."','NTF19')");	

                                        $ins5 = DB::connection($this->db)->insert("insert into prb_prbeban_d(no_bukti,kode_lokasi,periode,periode_sch,tanggal,kode_akun,kode_pp,kode_drk,keterangan,dc,nilai,tgl_input,kode_proyek,modul,no_ref1,jenis) values ('".$no_bukti."','".$kode_lokasi."','".$periode."','".$periode."','".$datam['tanggal']."','".$akun_beban."','".$ppKelola."','-','".$datam['kode_proyek']." | ".$datam['keterangan']."','D',".$nilaiBeban.",getdate(),'".$datam['kode_proyek']."','AJUBEBAN','-','ITAJU')");	
                                        
                                        $ins6 = DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values ('".$no_bukti."','NTF19','".$kode_lokasi."','".$akun_beban."','".$ppKelola."','-','".$periode."','".$periode."','D',0,".$nilaiBeban.")");

                                        // insert ke tu_pdapp_m
                                        
                                        $ins9 = DB::connection($this->db)->insert("insert into tu_pdapp_m(no_app,kode_lokasi,nik_user,tgl_input,periode,tanggal,keterangan,jenis,lama,kota,sarana,catatan,nik_buat,nik_app,no_aju) values ('$no_app','$kode_lokasi','$nik',getdate(),'$periode','".$datam['tanggal']."','".$datam['keterangan']."','".$datam['jenis']."','".$datam['lama']."','".$datam['kota']."','".$datam['sarana']."','".$datam['catatan']."','".$datam['nik_buat']."','".$datam['nik_app']."','".$no_bukti."') ");
                        
                                        //insert it_aju_dok
                                        $dataDok = $request->input("URL_DOK");
                                        $nu=1;
                                        if(count($dataDok) > 0 ){
                                            for ($i=0;$i < count($dataDok);$i++){
                                                
                                                $sql ="insert into it_aju_dok(no_bukti,modul,no_gambar,kode_lokasi,jenis) values ('".$no_bukti."','SPPD','".$dataDok[$i]."','$kode_lokasi',1)";
                                                $upload = DB::connection($this->db)->insert($sql);
                                                $nu++;
                                            }	
                                        }
                                        
                                        DB::connection($this->db)->commit();
                                        $sts = true;
                                        $msg = "Data pengajuan beban berhasil disimpan";
                                        $success['no_bukti'] = $no_bukti;
                                    }
                                }
                            }

 
    
                        }
        
                    //}
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

    public function hapusPengajuan($no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
       
        DB::connection($this->db)->beginTransaction();
        try {

            $cek = DB::connection($this->db)->select("select no_aju from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_bukti' and progress <> 'A' ");
            if(count($cek) > 0){
                $success['status'] = false;
                $success['message'] = "Pengajuan gagal dihapus. No Pengajuan tidak dapat dihapus karena sudah diproses di Keuangan.";
            }else{

                $cek2 = DB::connection($this->db)->select("select no_aju from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_bukti' and progress = 'A'  ");
                if(count($cek2) > 0){
                    
                    $del = DB::connection($this->db)->table('it_aju_m')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_bukti)->delete();

                    $del2 = DB::connection($this->db)->table('it_aju_d')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_bukti)->delete();

                    $del3 = DB::connection($this->db)->table('it_aju_rek')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_bukti)->delete();

                    $del4 = DB::connection($this->db)->table('angg_r')->where('kode_lokasi', $kode_lokasi)
                    ->where('modul', 'NTF19')
                    ->where('no_bukti', $no_bukti)->delete();

                    $del5 = DB::connection($this->db)->table('prb_prbeban_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti' ,$no_bukti)->delete();

                    $del10 = DB::connection($this->db)->table('prb_prbdd_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti' ,$no_bukti)->delete();

                    $del6 = DB::connection($this->db)->table('tu_pdapp_m')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_bukti)->delete();
                    $del7 = DB::connection($this->db)->table('tu_pdaju_m')->where('kode_lokasi', $kode_lokasi)->where('no_spj','like', $no_bukti.'-%')->delete();
                    $del8 = DB::connection($this->db)->table('tu_pdaju_d')->where('kode_lokasi', $kode_lokasi)->where('no_spj','like', $no_bukti.'-%')->delete();

                    $del9 = DB::connection($this->db)->table('it_aju_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                
                    DB::connection($this->db)->commit();
                    $success['no_bukti']=$no_bukti;
                    $success['status'] = true;
                    $success['message'] = "Pengajuan berhasil dihapus";

                }else{
                    $success['status'] = false;
                    $success['message'] = "Pengajuan gagal dihapus. No Pengajuan tidak valid.";
                }

            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    

}
