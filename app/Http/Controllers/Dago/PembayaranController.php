<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PembayaranController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_peserta from dgw_paket where id_peserta ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t, (a.nilai_p * a.kurs) + a.nilai_t as total_idr 
            from dgw_pembayaran a inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and b.posted='F' and b.form='KBREG' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKurs(Request $request)
    {
        $this->validate($request,[
            'kode_curr' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select top 1 kurs from dgw_kurs where kd_curr = '".$request->kode_curr."' and kode_lokasi='".$kode_lokasi."' order by id DESC ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['kurs'] = $res[0]['kurs'];
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['kurs'] = 1;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRegistrasi()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_reg,a.no_peserta,b.nama,a.tgl_input,f.nama as nama_paket,e.tgl_berangkat,case when ( ((a.harga+harga_room) > isnull(c.bayar_paket,0)+a.diskon) or (isnull(d.tot_tambahan,0) > isnull(c.bayar_tambahan,0) ) ) then '-' else 'Lunas' end as status
            from dgw_reg a 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            
            inner join dgw_paket f on a.no_paket=f.no_paket and a.kode_lokasi=f.kode_lokasi 
            left join (select no_reg,sum(nilai) as tot_tambahan from dgw_reg_biaya 
                        where nilai <> 0 and kode_lokasi='$kode_lokasi' group by no_reg
                        ) d on a.no_reg=d.no_reg 
            left join (select no_reg,sum(nilai_p) as bayar_paket,sum(nilai_t+nilai_m) as bayar_tambahan
                        from dgw_pembayaran 
                        where kode_lokasi='$kode_lokasi' group by no_reg 
                        ) c on a.no_reg=c.no_reg 
            left join dgw_jadwal e on a.no_paket=e.no_paket and a.no_jadwal=e.no_jadwal and a.kode_lokasi=e.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format:Y-m-d',
            'no_reg' => 'required',
            'nama' => 'required',
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'akun_titip' => 'required',
            'kurs_closing' => 'required',
            'akun_tambah' => 'required',
            'akun_dokumen' => 'required', 
            'paket' => 'required',
            'tgl_berangkat' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'biaya' => 'required|array',
            'biaya.*.kode_biaya' => 'required',
            'biaya.*.jenis_biaya' => 'required',
            'biaya.*.kode_akun' => 'required',
            'biaya.*.bayar' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $d = DB::connection($this->sql)->select("select kode_spro,flag from spro where kode_spro in ('LKURS','RKURS','AKUNT','AKUND','AKUNOI','AKUNOE') and kode_lokasi = '".$kode_lokasi."'");
            $d = json_decode(json_encode($d),true);	
            if (count($d) > 0){
				for ($i=0;$i<count($d);$i++){
					$line = $d[$i];	
					if ($line['kode_spro'] == "AKUNOI") $akunOI = $line['flag'];
                    if ($line['kode_spro'] == "AKUNOE") $akunOE = $line['flag'];
                    if ($line['kode_spro'] == "LKURS") $lKurs = $line['flag'];
                    if ($line['kode_spro'] == "RKURS") $rKurs = $line['flag'];
				}
            }	

            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-TT'.substr($periode,2,4).".", "0001");
            $no_kb = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-BM'.substr($periode,2,4).".", "0001");
            $bayarPaketIDR = floatval($request->bayar_paket)*floatval($request->kurs);
            
            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBREG','F','-','-',$request->kode_pp,$request->tanggal,$request->no_reg,$request->deskripsi,$request->kode_curr,$request->kurs,$request->total_bayar,0,0,'-','-','-',$request->no_reg,'-','-',$request->kode_akun,'-','BM'));

            $ins2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,0,$request->kode_akun,'D',$request->total_bayar,$request->total_bayar,$request->deskripsi,'KB','KB','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));

            //PENAMBAHAN KURS
            $totalOri = (floatval($request->kurs) *  floatval($request->bayar_paket)) + floatval($request->bayar_tambahan) + floatval($request->bayar_dok);							
            $sls = floatval($request->total_bayar) - $totalOri;		
            if ($sls != 0) {								
				if ($sls < 0) {							
					$sls = $sls * -1;
                    $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOE."','D',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                }
				else {							
					$insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOI."','C',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");											
				}
			}	
            //
                    
            $ins3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,1,$request->akun_titip,'C',$bayarPaketIDR,$request->bayar_paket,$request->deskripsi,'KB','TTPPAKET',$request->kode_curr,$request->kurs,$request->kode_pp,'-','-','-','-','-','-','-','-'));										

            if (intval($request->bayar_tambahan) != 0 || intval($request->bayar_dok) != 0 || intval($request->bayar_paket) != 0) {
               
                $nilai_t=0;$nilai_d=0;$total_t=0;$total_d=0;$ser_t=array();$ser2_t=array();$ser_d=array();$ser2_d=array();$tes=array();
                $biaya = $request->biaya;
                for($i=0; $i<count($biaya);$i++){
                
                    if(intval($biaya[$i]['bayar']) != 0){

                        if($biaya[$i]['jenis_biaya'] == 'TAMBAHAN'){
                            $nilai_t = intval($biaya[$i]['bayar']);
                            array_push($tes,$nilai_t);
                            $isAda_t = false;
                            $idx_t = 0;
                            
                            $akun_t = $biaya[$i]['kode_akun'];						
                            for ($c=0;$c <= $i;$c++){
                                if(isset($biaya[$c-1]['kode_akun'])){

                                    if ($akun_t == $biaya[$c-1]['kode_akun']) {
                                        $isAda_t = true;
                                        $idx_t = $c;
                                        break;
                                    }
                                }
                            }
                            if (!$isAda_t) {							
                                array_push($ser_t,$biaya[$i]['kode_akun']);
                                
                                $ser2_t[$biaya[$i]['kode_akun']]=$nilai_t;
                            } 
                            else { 
                                $total_t = $ser2_t[$biaya[$i]['kode_akun']];
                                $total_t = $total_t + $nilai_t;
                                $ser2_t[$biaya[$i]['kode_akun']]=$total_t;
                            }		
                        }else if($biaya[$i]['jenis_biaya'] == 'DOKUMEN'){
                            $nilai_d = intval($biaya[$i]['bayar']);
                            $isAda_d = false;
                            $idx_d = 0;
                            
                            $akun_d = $biaya[$i]['kode_akun'];						
                            for ($c=0;$c <= $i;$c++){
                                if(isset($biaya[$c-1]['kode_akun'])){
                                    if ($akun_d == $biaya[$c-1]['kode_akun']) {
                                        $isAda_d = true;
                                        $idx_d = $c;
                                        break;
                                    }
                                }
                            }
                            if (!$isAda_d) {							
                                array_push($ser_d,$biaya[$i]['kode_akun']);
                                
                                $ser2_d[$biaya[$i]['kode_akun']]=$nilai_d;
                            } 
                            else { 
                                $total_d = $ser2_d[$biaya[$i]['kode_akun']];
                                $total_d = $total_d + $nilai_d;
                                $ser2_d[$biaya[$i]['kode_akun']]=$total_d;
                            }
                        }
    
                        $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai) values(?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,$request->no_reg,$biaya[$i]['kode_biaya'],$biaya[$i]['jenis_biaya'],$biaya[$i]['bayar']));
                    } 
                }	
                $nu =2;
                for($x=0; $x<count($ser_t);$x++){
                        
                    if($request->akun_tambah == "" || $request->akun_tambah == "-"){

                        $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_t[$x],'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }else{
                        $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_tambah,'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }
                    $nu++;
                        
                }

                $nu =3;
                for($x=0; $x<count($ser_d);$x++){
                    if($request->akun_dokumen == "" || $request->akun_dokumen == "-"){   
                        $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_d[$x],'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }else{
                        $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_dokumen,'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }
                    $nu++;
                        
                }
            }		
            
            $insp = DB::connection($this->sql)->update("insert into dgw_pembayaran (no_kwitansi,no_reg,jadwal,tgl_bayar,paket,sistem_bayar,kode_lokasi,periode,nilai_t,nilai_p,kode_curr,kurs,nilai_m,flag_ver,no_kb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_reg,$request->tgl_berangkat,$request->tanggal,$request->paket,$request->status_bayar,$kode_lokasi,$periode,$request->bayar_tambahan,$request->bayar_paket,$request->kode_curr,$request->kurs,$request->bayar_dok,'0',$no_kb));

            //hitung selisih kurs pembyaran dan closing jadwal (untuk yg di piutang-kan - saat berangkat blm lunas)
            //jika pembayran dilakukan setelah berangkat
			if (floatval($request->kurs_closing) != 0 && floatval($request->kurs_closing) != floatval($request->kurs))  {
				$sls = (floatval($request->kurs) - floatval($request->kurs_closing)) * floatval($request->bayar_paket);
				if ($sls !=0 ) {
                    if ($sls > 0){ 
                        $akunKurs = $lKurs;
                        $dc = "C";
                        $dcPiutang = "D";
                    }
                    else {
                        $akunKurs = $rKurs;
                        $dc = "D";
                        $dcPiutang = "C";
                    }
                    $sls = abs($sls);
                    
                    $insk2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',777,'".$akunKurs."','".$dc."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SKURS','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                    
                    $insk3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',778,'".$request->akun_titip."','".$dcPiutang."',".$sls.",".$sls.",'Selisih Kurs a.n ".$request->nama."','KB','SLSPIU','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");		
				}
            }
                    
            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_kwitansi'] = $no_bukti;
            $success['message'] = "Data Pembayaran berhasil disimpan. No Bukti:".$no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }		
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id = $request->no_reg;
            if(isset($request->no_bukti)){
                $no_bukti = $request->no_bukti;
                $sql = "select no_bukti,keterangan,param1 as kode_akun 
                from trans_m 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and no_ref1='".$id."'";
                $res4 = DB::connection($this->sql)->select($sql);
                $res4 = json_decode(json_encode($res4),true);
                
            }else{
                $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-TT'.date('ym'), "0001");
                $res4 = array();
            }

            $sql = "select b.no_reg,a.nama,d.tgl_berangkat,e.nama as paket,e.kode_curr,b.harga + b.harga_room as harga_tot,  case when d.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun,d.kurs_closing, d.no_closing, f.akun_piutang, b.diskon 
            from dgw_peserta a 
                inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
                left join dgw_jadwal d on b.no_paket=d.no_paket and b.no_jadwal=d.no_jadwal and b.kode_lokasi=d.kode_lokasi 
                inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 
                inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi
            where b.no_reg='".$id."' and b.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
            from dgw_pembayaran 
            where no_reg='".$id."' and kode_lokasi='".$kode_lokasi."' and no_kwitansi ='".$no_bukti."'";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.nilai-isnull(c.byr,0)-isnull(d.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			 left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) d on a.kode_biaya=d.kode_biaya and a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.nilai <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga_room-isnull(c.byr,0)-isnull(d.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.harga_room <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'PAKET' as kode_biaya, a.harga-a.diskon as tarif, a.harga-a.diskon as nilai,isnull(c.byr,0) as byr_e,isnull(d.byr,0) as byr,a.harga-isnull(c.byr,0)-isnull(d.byr,0)-a.diskon as saldo, 1 as jml, 
                    'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi = '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
			left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <> '$no_bukti'
                        group by a.no_reg,a.kode_lokasi ) d on a.kode_lokasi=d.kode_lokasi 
                        and a.no_reg=d.no_reg 
            where a.harga <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            
            order by curr desc");
            $res3 = json_decode(json_encode($res3),true);

            $sql5 = " select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t,nilai_m, (a.nilai_p * a.kurs) + a.nilai_t+a.nilai_m as total_idr 
            from dgw_pembayaran a 
            inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and a.no_reg='$id' and b.posted='F' and b.form='KBREG' and a.no_kwitansi <> '$no_bukti' ";
            $res5 = DB::connection($this->sql)->select( $sql5);
            $res5 = json_decode(json_encode($res5),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $totTambah = $totDok = 0;
                if (count($res3) > 0){
                    for($i=0;$i<count($res3);$i++){
                        if ($res3[$i]['jenis'] == "TAMBAHAN") $totTambah += floatval($res3[$i]['nilai']);
                        if ($res3[$i]['jenis'] == "DOKUMEN") $totDok += floatval($res3[$i]['nilai']);	
                    }
                } 
                
                $success['status'] = "SUCCESS";
                $success['data_jamaah'] = $res;
                $success['detail_bayar'] = $res2;
                $success['detail_biaya'] = $res3;
                $success['data_bayar'] = $res4;
                $success['histori_bayar'] = $res5;
                $success['totTambah']=$totTambah;
                $success['totDok']=$totDok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['biaya_tambahan'] = [];
                $success['biaya_dokumen'] = [];
                $success['data_bayar'] = [];
                $success['histori_bayar'] = [];
                $success['totTambah']=0;
                $success['totDok']=0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

     /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request, [
            'no_reg' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $id = $request->no_reg;
            if(isset($request->no_bukti)){
                $no_bukti = $request->no_bukti;
                $sql = "select no_bukti,keterangan,param1 as kode_akun 
                from trans_m 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and no_ref1='".$id."'";
                $res4 = DB::connection($this->sql)->select( $sql);
                $res4 = json_decode(json_encode($res4),true);
                
            }else{
                $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi.'-BM'.date('ym'), "0001");
                $res4 = array();
            }

            $sql = "select b.no_reg,a.nama,d.tgl_berangkat,e.nama as paket,e.kode_curr,b.harga + b.harga_room as harga_tot,  case when d.no_closing ='-' then f.kode_akun else f.akun_piutang end as kode_akun,d.kurs_closing, d.no_closing, f.akun_piutang, b.diskon 
            from dgw_peserta a 
                inner join dgw_reg b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
                left join dgw_jadwal d on b.no_paket=d.no_paket and b.no_jadwal=d.no_jadwal and b.kode_lokasi=d.kode_lokasi 
                inner join dgw_paket e on b.no_paket=e.no_paket and b.kode_lokasi=e.kode_lokasi 
                inner join dgw_jenis_produk f on e.kode_produk=f.kode_produk and e.kode_lokasi=f.kode_lokasi
            where b.no_reg='".$id."' and b.kode_lokasi='".$kode_lokasi."'";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select isnull(sum(nilai_p),0) as paket, isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
            from dgw_pembayaran 
            where no_reg='".$id."' and kode_lokasi='".$kode_lokasi."' and no_kwitansi <> '".$no_bukti."'";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select a.kode_biaya, a.tarif, a.nilai, isnull(c.byr,0) as byr,a.nilai-isnull(c.byr,0) as saldo,a.jml, b.nama, 'IDR' as curr, b.jenis,b.akun_pdpt 
            from dgw_reg_biaya a 
            inner join dgw_biaya b on a.kode_biaya=b.kode_biaya and a.kode_lokasi=b.kode_lokasi 
            left join ( select a.no_reg,a.kode_biaya,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_biaya,a.kode_lokasi ) c on a.kode_biaya=c.kode_biaya and a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.nilai <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'ROOM' as kode_biaya, a.harga_room as tarif, a.harga_room as nilai,isnull(c.byr,0) as byr,a.harga_room-isnull(c.byr,0) as saldo, 
                    1 as jml, 'ROOM' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya ='ROOM' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga_room <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
            union all 
            select 'PAKET' as kode_biaya, a.harga-isnull(a.diskon,0) as tarif, a.harga-isnull(a.diskon,0) as nilai,isnull(c.byr,0) as byr,a.harga-isnull(a.diskon,0)-isnull(c.byr,0) as saldo, 1 as jml, 'PAKET' as nama, 'USD' as curr, '-' as jenis,'-' as akun_pdpt 
            from dgw_reg a 
            left join ( select a.no_reg,a.kode_lokasi,sum(nilai) as byr 
                        from dgw_pembayaran_d a 
                        where a.kode_biaya = 'PAKET' and a.no_kwitansi <>'".$no_bukti."'
                        group by a.no_reg,a.kode_lokasi ) c on a.kode_lokasi=c.kode_lokasi 
                        and a.no_reg=c.no_reg 
            where a.harga <> 0 and a.no_reg='$id' and a.kode_lokasi='$kode_lokasi' 
           
            order by curr desc");
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = " select a.no_kwitansi, a.tgl_bayar, a.no_reg, a.paket, a.jadwal, round(a.nilai_p,4) as nilai_p, a.nilai_t,nilai_m, (a.nilai_p * a.kurs) + a.nilai_t+a.nilai_m as total_idr 
            from dgw_pembayaran a 
            inner join trans_m b on a.no_kwitansi=b.no_bukti and a.kode_lokasi=b.kode_lokasi
            where b.kode_lokasi='".$kode_lokasi."' and a.no_reg='$id' and b.posted='F' and b.form='KBREG' ";
            $res4 = DB::connection($this->sql)->select( $sql4);
            $res4 = json_decode(json_encode($res4),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $totTambah = $totDok = 0;
                if (count($res3) > 0){
                    for($i=0;$i<count($res3);$i++){
                        if ($res3[$i]['jenis'] == "TAMBAHAN") $totTambah += floatval($res3[$i]['nilai']);
                        if ($res3[$i]['jenis'] == "DOKUMEN") $totDok += floatval($res3[$i]['nilai']);	
                    }
                } 
                
                $success['status'] = "SUCCESS";
                $success['data_jamaah'] = $res;
                $success['detail_bayar'] = $res2;
                $success['detail_biaya'] = $res3;
                $success['histori_bayar'] = $res4;
                $success['totTambah']=$totTambah;
                $success['totDok']=$totDok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['biaya_tambahan'] = [];
                $success['biaya_dokumen'] = [];
                $success['histori_bayar'] = [];
                $success['totTambah']=0;
                $success['totDok']=0;
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_reg' => 'required',
            'nama' => 'required',
            'deskripsi' => 'required',
            'kode_pp' => 'required',
            'kode_akun' => 'required',
            'kode_curr' => 'required',
            'kurs' => 'required',
            'akun_titip' => 'required',
            'kurs_closing' => 'required',
            'akun_tambah' => 'required',
            'akun_dokumen' => 'required', 
            'paket' => 'required',
            'tgl_berangkat' => 'required|date_format:Y-m-d',
            'status_bayar' => 'required|in:TUNAI,TRANSFER',
            'total_bayar' => 'required',
            'bayar_paket' => 'required',
            'bayar_tambahan' => 'required',
            'bayar_dok' => 'required',
            'biaya' => 'required|array',
            'biaya.*.kode_biaya' => 'required',
            'biaya.*.jenis_biaya' => 'required',
            'biaya.*.kode_akun' => 'required',
            'biaya.*.bayar' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $d = DB::connection($this->sql)->select("select kode_spro,flag from spro where kode_spro in ('LKURS','RKURS','AKUNT','AKUND','AKUNOI','AKUNOE') and kode_lokasi = '".$kode_lokasi."'");
            $d = json_decode(json_encode($d),true);	
            if (count($d) > 0){
				for ($i=0;$i<count($d);$i++){
					$line = $d[$i];	
					if ($line['kode_spro'] == "AKUNOI") $akunOI = $line['flag'];
					if ($line['kode_spro'] == "AKUNOE") $akunOE = $line['flag'];
				}
            }	

            $no_bukti = $request->no_bukti;

            $nk = DB::connection($this->sql)->select("select no_kb from dgw_pembayaran where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
            $kas = json_decode(json_encode($nk),true);	
            if (count($kas) > 0){
                $no_kb = $kas[0]->no_kb;
            }else{
                $no_kb = "-";
            }

            
            $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_pembayaran')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_pembayaran_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();
                
            $del5 = DB::connection($this->sql)->table('dgw_ver_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_kb)
                ->delete();	
            
            $del6 = DB::connection($this->sql)->table('dgw_ver_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_kb)
                ->delete();
            
            $no_ver = $this->generateKode("dgw_ver_m", "no_ver", $kode_lokasi.'-VER'.date('ym'), "0001");
                
            $bayarPaketIDR = floatval($request->bayar_paket)*floatval($request->kurs);    
            $ins = DB::connection($this->sql)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,'KB','KBREG','F','-','-',$request->kode_pp,$request->tanggal,$request->no_reg,$request->deskripsi,$request->kode_curr,$request->kurs,$request->total_bayar,0,0,'-','-','-',$request->no_reg,'-','-',$request->kode_akun,'-','BM'));

            $ins2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,0,$request->kode_akun,'D',$request->total_bayar,$request->total_bayar,$request->deskripsi,'KB','KB','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
            
            //PENAMBAHAN KURS
            $totalOri = (floatval($request->kurs) *  floatval($request->bayar_paket)) + floatval($request->bayar_tambahan) + floatval($request->bayar_dok);							
            $sls = floatval($request->total_bayar) - $totalOri;		
            if ($sls != 0) {								
                if ($sls < 0) {							
                    $sls = $sls * -1;
                    $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOE."','D',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                }
                else {							
                    $insk1 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','-','".$request->tanggal."',998,'".$akunOI."','C',".$sls.",".$sls.",'".$request->deskripsi."','KB','SLSKOMA','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");											
                }
            }	
            //
                
            $ins3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,1,$request->akun_titip,'C',$bayarPaketIDR,$request->bayar_paket,$request->deskripsi,'KB','TTPPAKET',$request->kode_curr,$request->kurs,$request->kode_pp,'-','-','-','-','-','-','-','-'));										
                
            if (intval($request->bayar_tambahan) != 0 || intval($request->bayar_dok) != 0 || intval($request->bayar_paket) != 0) 
            { 
                $nilai_t=0;$nilai_d=0;$total_t=0;$total_d=0;$ser_t=array();$ser2_t=array();$ser_d=array();$ser2_d=array();$tes=array();
                $biaya = $request->biaya;
                for($i=0; $i<count($biaya);$i++){
                    if(intval($biaya[$i]['bayar']) != 0){
                        if($biaya[$i]['jenis_biaya'] == 'TAMBAHAN'){
                            $nilai_t = intval($biaya[$i]['bayar']);
                            array_push($tes,$nilai_t);
                            $isAda_t = false;
                            $idx_t = 0;
                            
                            $akun_t = $biaya[$i]['kode_akun'];						
                            for ($c=0;$c <= $i;$c++)
                            {
                                if(isset($biaya[$c-1]['kode_akun']))
                                {
                                    
                                    if ($akun_t == $biaya[$c-1]['kode_akun']) 
                                    {
                                        $isAda_t = true;
                                        $idx_t = $c;
                                        break;
                                    }
                                }
                            }

                            if (!$isAda_t) {							
                                array_push($ser_t,$biaya[$i]['kode_akun']);
                                
                                $ser2_t[$biaya[$i]['kode_akun']]=$nilai_t;
                            } 
                            else { 
                                $total_t = $ser2_t[$biaya[$i]['kode_akun']];
                                $total_t = $total_t + $nilai_t;
                                $ser2_t[$biaya[$i]['kode_akun']]=$total_t;
                            }		
                        }else if($biaya[$i]['jenis_biaya'] == 'DOKUMEN'){
                            $nilai_d = intval($biaya[$i]['bayar']);
                            $isAda_d = false;
                            $idx_d = 0;
                            
                            $akun_d = $biaya[$i]['kode_akun'];						
                            for ($c=0;$c <= $i;$c++){
                                if(isset($biaya[$c-1]['kode_akun'])){
                                    if ($akun_d == $biaya[$c-1]['kode_akun']) {
                                        $isAda_d = true;
                                        $idx_d = $c;
                                    break;
                                    }
                                }
                            }
                            if (!$isAda_d) {							
                                array_push($ser_d,$biaya[$i]['kode_akun']);
                                
                                $ser2_d[$biaya[$i]['kode_akun']]=$nilai_d;
                            } 
                            else { 
                                $total_d = $ser2_d[$biaya[$i]['kode_akun']];
                                $total_d = $total_d + $nilai_d;
                                $ser2_d[$biaya[$i]['kode_akun']]=$total_d;
                            }
                        }
                    
                        $insdet[$i] =  DB::connection($this->sql)->insert("insert into dgw_pembayaran_d (no_kwitansi,kode_lokasi,no_reg,kode_biaya,jenis,nilai) values(?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,$request->no_reg,$biaya[$i]['kode_biaya'],$biaya[$i]['jenis_biaya'],$biaya[$i]['bayar']));
                    } 
                }	
                $nu =2;
                for($x=0; $x<count($ser_t);$x++){
                    
                    if($request->akun_tambah == "" || $request->akun_tambah == "-"){
                        
                        $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_t[$x],'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }else{
                        $ins4[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_tambah,'C',$ser2_t[$ser_t[$x]],$ser2_t[$ser_t[$x]],$request->deskripsi,'KB','PDTAMBAH','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }
                    $nu++;
                    
                }
                
                $nu =3;
                for($x=0; $x<count($ser_d);$x++){
                    if($request->akun_dokumen == "" || $request->akun_dokumen == "-"){   
                        $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$ser_d[$x],'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }else{
                        $ins5[$i] =  DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", array($no_bukti,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$periode,$request->no_reg,$request->tanggal,$nu,$request->akun_dokumen,'C',$ser2_d[$ser_d[$x]],$ser2_d[$ser_d[$x]],$request->deskripsi,'KB','PDDOKUMEN','IDR',1,$request->kode_pp,'-','-','-','-','-','-','-','-'));
                    }
                    $nu++;
                    
                }
            }		
        
            $insp = DB::connection($this->sql)->update("insert into dgw_pembayaran (no_kwitansi,no_reg,jadwal,tgl_bayar,paket,sistem_bayar,kode_lokasi,periode,nilai_t,nilai_p,kode_curr,kurs,nilai_m,flag_ver,no_kb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_reg,$request->tgl_berangkat,$request->tanggal,$request->paket,$request->status_bayar,$kode_lokasi,$periode,$request->bayar_tambahan,$request->bayar_paket,$request->kode_curr,$request->kurs,$request->bayar_dok,'1',$no_kb));
            
            //hitung selisih kurs pembyaran dan closing jadwal (untuk yg di piutang-kan - saat berangkat blm lunas)
            //jika pembayran dilakukan setelah berangkat
            if (floatval($request->kurs_closing) != 0 && floatval($request->kurs_closing) != floatval($request->kurs))  {
                $sls = (floatval($request->kurs) - floatval($request->kurs_closing)) * floatval($request->bayar_paket);
                if ($sls !=0 ) {
                    if ($sls > 0){ 
                        $akunKurs = $lKurs;
                        $dc = "C";
                        $dcPiutang = "D";
                    }
                    else {
                        $akunKurs = $rKurs;
                        $dc = "D";
                        $dcPiutang = "C";
                    }
                    $sls = abs($sls);
                    
                    $insk2 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',777,'".$akunKurs."','".$dc."',".$sls.",".$sls.",'Selisih Kurs Piutang Closing','KB','SKURS','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");	
                    
                    $insk3 = DB::connection($this->sql)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_reg."','".$request->tanggal."',778,'".$request->akun_titip."','".$dcPiutang."',".$sls.",".$sls.",'Selisih Kurs a.n ".$request->nama."','KB','SLSPIU','IDR',1,'".$request->kode_pp."','-','-','-','-','-','-','-','-')");		
                }
            }

            $insVerm = DB::connection($this->sql)->insert("insert into dgw_ver_m (no_ver,kode_lokasi,tanggal,keterangan,nik_ver,nik_user,tgl_input,no_kwitansi) values ('$no_ver','$kode_lokasi',getdate(),'$request->deskripsi','$nik','$nik',getdate(),'$no_kb') ");

            $insVerd = DB::connection($this->sql)->insert("insert into dgw_ver_d (no_ver,kode_lokasi,no_kwitansi) values ('$no_ver','$kode_lokasi','$no_kb')");

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['no_terima'] = $no_bukti;
            $success['no_kwitansi'] = $no_kb;
            $success['message'] = "Data Pembayaran berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }		
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $nk = DB::connection($this->sql)->select("select no_kb from dgw_pembayaran where no_kwitansi='$no_bukti' and kode_lokasi='$kode_lokasi' ");
            $kas = json_decode(json_encode($nk),true);	
            if (count($kas) > 0){
                $no_kb = $kas[0]->no_kb;
            }else{
                $no_kb = "-";
            }

            $del = DB::connection($this->sql)->table('trans_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();

            $del2 = DB::connection($this->sql)->table('trans_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $request->no_bukti)
                ->delete();
            
            $del3 = DB::connection($this->sql)->table('dgw_pembayaran')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	
            
            $del4 = DB::connection($this->sql)->table('dgw_pembayaran_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $request->no_bukti)
                ->delete();	

            $del5 = DB::connection($this->sql)->table('dgw_ver_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $no_kb)
                ->delete();	

            $del6 = DB::connection($this->sql)->table('dgw_ver_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kwitansi', $no_kb)
                ->delete();	

            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil dihapus ";
            DB::connection($this->sql)->commit();
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Pembayaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getRekBank(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_akun)){
                if($request->kode_akun == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_akun='$request->kode_akun' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection($this->sql)->select("select a.kode_akun, a.nama
            from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.kode_flag in ('057') $filter");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPreview(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_kwitansi, a.kurs,a.paket,b.no_type,c.nama as room,b.harga+b.harga_room as harga_paket,a.jadwal,h.nama_marketing,e.nama_agen,isnull(b.referal,'-') as referal,a.no_reg,i.biaya_tambah,j.paket+j.tambahan+j.dokumen as bayar_lain,n.cicil_ke as cicil_ke, (a.kurs*(b.harga+b.harga_room)) as biaya_paket,(b.harga+b.harga_room)-(j.paket)+a.nilai_p as saldo, a.nilai_p as bayar,(b.harga+b.harga_room)-(j.paket) as sisa,CONVERT(varchar, a.tgl_bayar, 105) as tgl_bayar,k.nama as peserta,l.kode_curr,m.nik_user,o.kode_akun,p.nama as nama_akun,a.sistem_bayar,k.telp
            from dgw_pembayaran a
            inner join dgw_reg b on a.no_reg=b.no_reg and a.kode_lokasi=b.kode_lokasi
            inner join dgw_typeroom c on b.no_type=c.no_type and b.kode_lokasi=c.kode_lokasi
            inner join dgw_agent e on b.no_agen=e.no_agen and b.kode_lokasi=e.kode_lokasi 
            inner join dgw_marketing h on b.no_marketing=h.no_marketing and b.kode_lokasi=h.kode_lokasi
            inner join dgw_peserta k on b.no_peserta=k.no_peserta and b.kode_lokasi=k.kode_lokasi 
            inner join dgw_paket l on b.no_paket=l.no_paket and b.kode_lokasi=l.kode_lokasi 
            inner join trans_m m on a.no_kwitansi=m.no_bukti and a.kode_lokasi=m.kode_lokasi
            inner join trans_j o on m.no_bukti=o.no_bukti and m.kode_lokasi=o.kode_lokasi and o.dc='D'
			inner join masakun p on o.kode_akun=p.kode_akun and o.kode_lokasi=p.kode_lokasi
            left join ( select no_reg,kode_lokasi,sum(jml*nilai) as biaya_tambah 
                        from dgw_reg_biaya 
                        group by no_reg,kode_lokasi ) i on b.no_reg=i.no_reg and b.kode_lokasi=i.kode_lokasi
            left join (select no_reg,kode_lokasi,isnull(sum(nilai_p),0) as paket, 
                        isnull(sum(nilai_t),0) as tambahan, isnull(sum(nilai_m),0) as dokumen
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) j on b.no_reg=j.no_reg and b.kode_lokasi=j.kode_lokasi
			left join (select no_reg,kode_lokasi,count(no_kwitansi) as cicil_ke
                        from dgw_pembayaran 
                        group by no_reg,kode_lokasi ) n on b.no_reg=n.no_reg and b.kode_lokasi=n.kode_lokasi
			where a.no_kwitansi='$request->no_bukti' and a.kode_lokasi='$kode_lokasi'
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['data']= $res;
                $success['status'] = "SUCCESS";
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }   
    }
}
