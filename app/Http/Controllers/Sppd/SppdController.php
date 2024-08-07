<?php

namespace App\Http\Controllers\Sppd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Log;

class SppdController extends Controller
{
    public $successStatus = 200;
    
    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query =DB::connection('sqlsrvypt')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function getPeriodeAktif(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvypt')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'				 
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

    public function getAkun(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');
                $filterkode_akun = " and a.kode_akun='$kode_akun' ";
            
            }else{
                $filterkode_akun = "";
            }

			if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filterkode_pp = " and a.kode_pp='$kode_pp' ";
            
            }else{
                $filterkode_pp = "";
            }

            $res = DB::connection('sqlsrvypt')->select("select a.kode_akun,a.nama
            from masakun a
            inner join (select a.kode_akun,a.kode_lokasi
                        from akun_sppd a
                        where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1' $filterkode_pp
                        group by a.kode_akun,a.kode_lokasi
                        ) b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filterkode_akun order by a.kode_akun	 
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

    public function getBank(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->input('kode_bank') != "") {
                $kode_bank = $request->input('kode_bank');
                $filterkode_bank = " and kode_bank='$kode_bank' ";
            
            }else{
                $filterkode_bank = "";
            }			

            $res = DB::connection('sqlsrvypt')->select("select * from bank 
            where kode_lokasi='$kode_lokasi' $filterkode_bank order by kode_bank	 
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

    public function getPP(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

			if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filterkode_pp = " and a.kode_pp='$kode_pp' ";
            
            }else{
                $filterkode_pp = "";
            }
			

            $res = DB::connection('sqlsrvypt')->select("select a.kode_pp,a.nama,c.bank,c.cabang,c.no_rek,c.nama_rek
            from pp a
            inner join pp_rek c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            inner join (select a.kode_pp,a.kode_lokasi
                        from akun_sppd a
                        where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1' 
                        group by a.kode_pp,a.kode_lokasi 
                        ) b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filterkode_pp order by a.kode_pp	 
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

    public function getDrk(Request $request){
        try {

            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun=$request->input('tahun');
            $filter="";
            if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filter .= " and a.kode_pp='$kode_pp' ";
                
            }else{
                $filter .= "";
            }
            
            if ($request->input('kode_akun') != "") {
                $kode_akun = $request->input('kode_akun');                
                $filter .= " and a.kode_akun='$kode_akun' ";
                
            }else{
                $filter .= "";
            }

            if ($request->input('kode_drk') != "") {
                $kode_drk = $request->input('kode_drk');                
                $filter .= " and a.kode_drk='$kode_drk' ";
                
            }else{
                $filter .= "";
            }
            
            $res = DB::connection('sqlsrvypt')->select("select a.kode_drk,a.nama
            from drk a
            inner join (select a.kode_drk,a.kode_lokasi
            from akun_sppd a
            where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun' and a.flag_aktif='1' $filter
            group by a.kode_drk,a.kode_lokasi
            ) b on a.kode_drk=b.kode_drk and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun'  order by a.kode_drk,a.nama  
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

    public function cekBudget(Request $request){
        
        try {
            
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_pp=$request->kode_pp;
            $kode_akun=$request->kode_akun;
            $kode_drk=$request->kode_drk;
            $periode=$request->periode;
            // $success['req'] = $request->all();

            if(isset($request->no_agenda) && $request->no_agenda != ""){
                $sql="select dbo.fn_cekagg3('$kode_pp','$kode_lokasi','$kode_akun','$kode_drk','$periode','$no_agenda') as gar ";
            }else{
                $sql="select dbo.fn_cekagg2('$kode_pp','$kode_lokasi','$kode_akun','$kode_drk','$periode') as gar ";
            }

            $res = DB::connection('sqlsrvypt')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                if($res[0]['gar'] != ""){

                    $bug = explode(";",$res[0]['gar']);
                    $success['saldo_budget'] = $bug[0]-$bug[1];
                }else{
                    $success['saldo_budget'] = 0;
                }
                // $success['sql'] = $sql;
                // $success['gar'] = $res;
                $success['message'] = "Success!";
                $success['rows']=count($res);
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['saldo_budget'] = 0;
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

    public function keepBudget(Request $request){
        $this->validate($request,[
            'PBYR' =>'required|array',
            'PBYR.*.keterangan' =>'max:200',
            'PBYR.*.tanggal' =>'date_format:Y-m-d H:i:s',
            'PBYR.*.jenis' =>'max:100',
            'PBYR.*.lama' =>'max:100',
            'PBYR.*.kota' =>'max:100',
            'PBYR.*.sarana' =>'max:100',
            'PBYR.*.catatan' =>'max:200',
            'PBYR.*.nik_buat' =>'max:10',
            'PBYR.*.nik_app' =>'max:10',
            'PBYR.*.jenis_trans' =>'max:10',
            'PBYR.*.kode_pp' =>'max:10',
            'PBYR.*.kode_akun' =>'max:20',
            'PBYR.*.kode_drk' =>'max:30',
            'PBYR.*.periode' =>'max:6',
            'PBYR.*.no_ref1' =>'required|max:20',
            'AJU' =>'required|array',
            'AJU.*.nip' =>'max:20',
            'AJU.*.nama_perjalanan' =>'max:200',
            'AJU.*.pp_code' =>'max:10',
            'REK' => 'required|array',
            'REK.*.nama' => 'required|max:50',
            'REK.*.no_rekening' => 'required|max:50',
            'REK.*.bank' =>'required|max:100'
        ]);
        if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        
        $datam= $request->input("PBYR");
        Log::info($request->all());
        DB::connection('sqlsrvypt')->beginTransaction();
        try {
            $tglperiode = substr($datam[0]['tanggal'],0,4).''.substr($datam[0]['tanggal'],5,2);
            $periodecek = $datam[0]['periode'];
            // cek tgl & periode
            if($tglperiode != $periodecek){

                $msg = "Periode tidak valid. Periode $periodecek tidak sama dengan periode di tanggal (".$datam[0]['tanggal']."). ";
                $sts = false;	

            }else{
    
                $no_agenda = $this->generateKode("it_aju_m", "no_aju", $kode_lokasi."-".substr($datam[0]['periode'],2,2).".", "00001");
                $no_bukti = $this->generateKode("tu_pdapp_m", "no_app", $kode_lokasi."-PDA".substr($datam[0]['periode'],2,4).".", "0001");

                // SAVE LOG TO DB
                $log = print_r($request->input(), true); 
                $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
                values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,$log,'KEEP',$datam[0]['no_ref1']));
                // END SAVE
    
                $res = DB::connection('sqlsrvypt')->select("select status_gar from masakun where kode_akun='".$datam[0]['kode_akun']."' and kode_lokasi='$kode_lokasi' ");
                $res = json_decode(json_encode($res),true);
                if(count($res) > 0){
                    $stsGar = $res[0]['status_gar'];
                    if ($datam[0]['kode_drk'] == "-") {
                        $msg = "Transaksi tidak valid. Akun Anggaran Harus diisi DRK.";
                        $sts = false;						
                    }
                    else {
    
                        $request->request->add([
                            'kode_pp' => $datam[0]['kode_pp'],
                            'kode_akun' =>$datam[0]['kode_akun'],
                            'periode' => $datam[0]['periode'],
                            'kode_drk' => $datam[0]['kode_drk']
                        ]);
    
                        $cekB = json_decode(json_encode($this->cekBudget($request)),true);
                        $dtbugdet = $cekB['original'];
    
                        if (floatval($datam[0]['total']) > floatval($dtbugdet['saldo_budget'])) {   
                            $msg = "Transaksi tidak valid. Nilai transaksi melebihi saldo.";
                            $sts =false;						
                        }elseif(floatval($datam[0]['total']) <= 0) {
                            $msg = "Transaksi tidak valid. Total tidak boleh nol atau kurang.";
                            $sts =false;
                        }else{
                            $periode = DB::connection('sqlsrvypt')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                            ");
                            $periode = json_decode(json_encode($periode),true);
    
                            if ($datam[0]['periode'] < $periode[0]['periode']  ){
                                $msg ="Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[".$periode[0]['periode']."]";
                                $sts= false;
                            } else {
                                $msg="Transaksi Berhasil";
                                $sts=true;
                                $exec = array();
                                //if ($stsGar == "1") {
                                    $nilaiGar = $datam[0]['total'];
                                    $sql1 = DB::connection('sqlsrvypt')->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values 
                                    ('".$no_agenda."','ITKBAJUDRK','".$kode_lokasi."','".$datam[0]['kode_akun']."','".$datam[0]['kode_pp']."','".$datam[0]['kode_drk']."','".$datam[0]['periode']."','".$datam[0]['periode']."','D',".$datam[0]['saldo_budget'].",".$nilaiGar.")");
                                //}	
                                $sql="insert into tu_pdapp_m(no_app,kode_lokasi,nik_user,tgl_input,periode,tanggal,keterangan,jenis,lama,kota,sarana,catatan,nik_buat,nik_app,no_aju) values ('".$no_bukti."','".$kode_lokasi."','".$nik."',getdate(),'".$datam[0]['periode']."','".$datam[0]['tanggal']."','".$datam[0]['keterangan']."','".$datam[0]['jenis']."','".$datam[0]['lama']."','".$datam[0]['kota']."','".$datam[0]['sarana']."','".$datam[0]['catatan']."','".$datam[0]['nik_buat']."','".$datam[0]['nik_app']."','".$no_agenda."')";
                                $sql2 = DB::connection('sqlsrvypt')->insert($sql);
                                //$success['tmp1']=$sql;
                                $datad=$request->input("AJU");
                                $datarek=$request->input("REK");
                                $nu=1;
                                $total_d=0;
                                for ($i=0;$i < count($datad);$i++){
                                    
                                    //$no_spj = $this->generateKode("tu_pdaju_m", "no_spj", $kode_lokasi."-PJ".substr($datam[0]['periode'],2,4).".", "0001");
                                    $no_spj=$no_agenda."-".strval($nu);
                                    // $upd = "update tu_pdaju_m set progress='1',no_app='".$no_bukti."' where kode_lokasi='".$kode_lokasi."' and no_spj='".$datad[$i]['no_spj']."'";																								
                                    // array_push($exec,$upd);
                                    $sql="insert into tu_pdaju_m (no_spj,tanggal,kode_lokasi,kode_pp,kode_akun,kode_drk,keterangan,nik_buat,nik_spj,periode,tgl_input,progress,no_app,nilai,jenis_pd,sts_bmhd,kode_proyek) values ('$no_spj',getdate(),'".$kode_lokasi."','".$datad[$i]['pp_code']."','".$datam[0]['kode_akun']."','".$datam[0]['kode_drk']."','".$datad[$i]['nama_perjalanan']."','".$datam[0]['nik_buat']."','".$datad[$i]['nip']."','".$datam[0]['periode']."',getdate(),'1','".$no_bukti."',".$datad[$i]['total_biaya'].",'-','-','-') ";
                                    $insAju = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp2']=$sql;
    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','91',1,".$datad[$i]['transport'].",".$datad[$i]['transport'].") ";
                                    $insAjud1 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp3']=$sql;
                                    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','92',1,".$datad[$i]['harian'].",".$datad[$i]['harian'].") ";
                                    $insAjud2 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp4']=$sql;
    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','93',1,".$datad[$i]['lain_lain'].",".$datad[$i]['lain_lain'].") ";
                                    $insAjud3 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp5']=$sql;
    
                                    $total_d= $total_d + floatval($datad[$i]['total_biaya']);
                                    $nu++;
                                }	

                                if(count($datarek) > 0){
                                    if(count($datarek) == 1){
                                        $nu=1; 
                                        $nip = (isset($datad[0]['nip']) ? $datad[0]['nip'] : '-');
                                        $total_biaya = $total_d;

                                        $sql="insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_agenda."','".$kode_lokasi."','".$datarek[0]['bank']."','".$datarek[0]['no_rekening']."','".$datarek[0]['nama']."','-',".$total_biaya.",'".$nip."',0,'".$no_agenda."-".$nu."')";

                                        $sql3 = DB::connection('sqlsrvypt')->insert($sql);
                                    }else{
                                        $nu = 1;
                                        for ($i=0;$i < count($datarek);$i++){
        
                                            $nip = (isset($datad[$i]['nip']) ? $datad[$i]['nip'] : $datad[0]['nip']);
                                            $total_biaya = (isset($datad[$i]['total_biaya']) ? $datad[$i]['total_biaya'] : $datad[0]['total_biaya'] );
                                            $sql="insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_agenda."','".$kode_lokasi."','".$datarek[$i]['bank']."','".$datarek[$i]['no_rekening']."','".$datarek[$i]['nama']."','-',".$total_biaya.",'".$nip."',0,'".$no_agenda."-".$nu."')";
                                            $sql3 = DB::connection('sqlsrvypt')->insert($sql);
                                            //$success['tmp6']=$sql;
                                            $nu++;
                                        }
                                    }
                                }
                                
                                // $sql4 = "update a set a.bank=b.bank,a.no_rek=b.no_rek,a.nama_rek=b.nama_rek,a.bank_trans=b.cabang 
                                // from it_aju_rek a inner join karyawan b on a.keterangan=b.nik and a.kode_lokasi=b.kode_lokasi 
                                // where a.no_aju='".$no_agenda."' and a.kode_lokasi='".$kode_lokasi."'";
                                
                                // array_push($exec,$sql4);
                                // $no_ref1 = (isset($datam[0]['no_ref1']) ? $datam[0]['no_ref1'] : '-');
                                $no_ref1 = $datam[0]['no_ref1'];
                                $ck_ref1 = DB::connection('sqlsrvypt')->select("select no_aju from it_aju_m where no_ref1=? ",[strval($no_ref1)]);
                                if(count($ck_ref1) > 0){
                                    $sts = false;
                                    $msg = "Transaksi tidak valid. Terdapat data duplikat. Kode SPPD ".$no_ref1." sudah terdaftar dengan No Agenda = ".$ck_ref1[0]->no_aju.".";
                                    DB::connection('sqlsrvypt')->rollback();
                                }else{
                                    $sql="insert into it_aju_m(no_aju,kode_lokasi,periode,tanggal,modul,kode_akun,kode_pp,kode_drk,keterangan,nilai,tgl_input,nik_user,no_kpa,no_app,no_ver,no_fiat,no_kas,progress,nik_panjar,no_ptg,user_input,form,sts_pajak,npajak,nik_app,no_ref1) values ('".$no_agenda."','".$kode_lokasi."','".$datam[0]['periode']."','".$datam[0]['tanggal']."','".$datam[0]['jenis_trans']."','".$datam[0]['kode_akun']."','".$datam[0]['kode_pp']."','".$datam[0]['kode_drk']."','".$datam[0]['keterangan']."',".$datam[0]['total'].",getdate(),'".$nik."','-','-','-','-','-','A','-','-','".$datam[0]['nik_buat']."','SPPD','NON',0,'".$datam[0]['nik_app']."','".$no_ref1."')";
                                    //$success['tmp7']=$sql;
                                    $sql5 = DB::connection('sqlsrvypt')->insert($sql);	
        
                                    //insert it_aju_dok
                                    $dataDok = $request->input("URL_DOK");
                                    $nu=1;
                                    if(count($dataDok) > 0 ){
                                        for ($i=0;$i < count($dataDok);$i++){
                                          
                                            $sql ="insert into it_aju_dok(no_bukti,modul,no_gambar,kode_lokasi,jenis) values ('".$no_agenda."','SPPD','".$dataDok[$i]."','$kode_lokasi',1)";
                                            $upload = DB::connection('sqlsrvypt')->insert($sql);
                                            $nu++;
                                        }	
                                    }
        
                                    DB::connection('sqlsrvypt')->commit();
                                    $success['no_agenda']=$no_agenda;
                                    $success['no_bukti']=$no_bukti;
                                }

                            } 
                        }
                    }
    
                }else{
                    $msg = "Kode akun tidak valid. Status Anggaran kode akun ".$datam[0]['kode_akun']." tidak valid.";
                    $sts = false;	
                }
            }


            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
          
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function keepBudget21(Request $request){
        $this->validate($request,[
            'PBYR' =>'required|array',
            'PBYR.*.keterangan' =>'max:200',
            'PBYR.*.tanggal' =>'date_format:Y-m-d H:i:s',
            'PBYR.*.jenis' =>'max:100',
            'PBYR.*.lama' =>'max:100',
            'PBYR.*.kota' =>'max:100',
            'PBYR.*.sarana' =>'max:100',
            'PBYR.*.catatan' =>'max:200',
            'PBYR.*.nik_buat' =>'max:10',
            'PBYR.*.nik_app' =>'max:10',
            'PBYR.*.jenis_trans' =>'max:10',
            'PBYR.*.kode_pp' =>'max:10',
            'PBYR.*.kode_akun' =>'max:20',
            'PBYR.*.kode_drk' =>'max:30',
            'PBYR.*.periode' =>'max:6',
            'PBYR.*.no_ref1' =>'required|max:20',
            'AJU' =>'required|array',
            'AJU.*.no_spj_igracias' => 'required', 
            'AJU.*.nip' =>'max:20',
            'AJU.*.nama_perjalanan' =>'max:200',
            'AJU.*.pp_code' =>'max:10',
            'REK' => 'required|array',
            'REK.*.nama' => 'required|max:50',
            'REK.*.no_rekening' => 'required|max:50',
            'REK.*.bank' =>'required|max:100'
        ]);
        if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        
        $datam= $request->input("PBYR");
        Log::info($request->all());
        DB::connection('sqlsrvypt')->beginTransaction();
        try {
            $tglperiode = substr($datam[0]['tanggal'],0,4).''.substr($datam[0]['tanggal'],5,2);
            $periodecek = $datam[0]['periode'];
            // cek tgl & periode
            if($tglperiode != $periodecek){

                $msg = "Periode tidak valid. Periode $periodecek tidak sama dengan periode di tanggal (".$datam[0]['tanggal']."). ";
                $sts = false;	

            }else{
    
                $no_agenda = $this->generateKode("it_aju_m", "no_aju", $kode_lokasi."-".substr($datam[0]['periode'],2,2).".", "00001");
                $no_bukti = $this->generateKode("tu_pdapp_m", "no_app", $kode_lokasi."-PDA".substr($datam[0]['periode'],2,4).".", "0001");

                // SAVE LOG TO DB
                $log = print_r($request->input(), true); 
                $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
                values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,$log,'KEEP21',$datam[0]['no_ref1']));
                // END SAVE
    
                $res = DB::connection('sqlsrvypt')->select("select status_gar from masakun where kode_akun='".$datam[0]['kode_akun']."' and kode_lokasi='$kode_lokasi' ");
                $res = json_decode(json_encode($res),true);
                if(count($res) > 0){
                    $stsGar = $res[0]['status_gar'];
                    if ($datam[0]['kode_drk'] == "-") {
                        $msg = "Transaksi tidak valid. Akun Anggaran Harus diisi DRK.";
                        $sts = false;						
                    }
                    else {
    
                        $request->request->add([
                            'kode_pp' => $datam[0]['kode_pp'],
                            'kode_akun' =>$datam[0]['kode_akun'],
                            'periode' => $datam[0]['periode'],
                            'kode_drk' => $datam[0]['kode_drk']
                        ]);
    
                        $cekB = json_decode(json_encode($this->cekBudget($request)),true);
                        $dtbugdet = $cekB['original'];
    
                        if (floatval($datam[0]['total']) > floatval($dtbugdet['saldo_budget'])) {   
                            $msg = "Transaksi tidak valid. Nilai transaksi melebihi saldo.";
                            $sts =false;						
                        }elseif(floatval($datam[0]['total']) <= 0) {
                            $msg = "Transaksi tidak valid. Total tidak boleh nol atau kurang.";
                            $sts =false;
                        }else{
                            $periode = DB::connection('sqlsrvypt')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
                            ");
                            $periode = json_decode(json_encode($periode),true);
    
                            if ($datam[0]['periode'] < $periode[0]['periode']  ){
                                $msg ="Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[".$periode[0]['periode']."]";
                                $sts= false;
                            } else {
                                $sts=true;
                                $msg="Transaksi Berhasil";
                                $exec = array();
                                //if ($stsGar == "1") {
                                    $nilaiGar = $datam[0]['total'];
                                    $sql1 = DB::connection('sqlsrvypt')->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values 
                                    ('".$no_agenda."','ITKBAJUDRK','".$kode_lokasi."','".$datam[0]['kode_akun']."','".$datam[0]['kode_pp']."','".$datam[0]['kode_drk']."','".$datam[0]['periode']."','".$datam[0]['periode']."','D',".$datam[0]['saldo_budget'].",".$nilaiGar.")");
                                //}	
                                $sql="insert into tu_pdapp_m(no_app,kode_lokasi,nik_user,tgl_input,periode,tanggal,keterangan,jenis,lama,kota,sarana,catatan,nik_buat,nik_app,no_aju) values ('".$no_bukti."','".$kode_lokasi."','".$nik."',getdate(),'".$datam[0]['periode']."','".$datam[0]['tanggal']."','".$datam[0]['keterangan']."','".$datam[0]['jenis']."','".$datam[0]['lama']."','".$datam[0]['kota']."','".$datam[0]['sarana']."','".$datam[0]['catatan']."','".$datam[0]['nik_buat']."','".$datam[0]['nik_app']."','".$no_agenda."')";
                                $sql2 = DB::connection('sqlsrvypt')->insert($sql);
                                //$success['tmp1']=$sql;
                                $datad=$request->input("AJU");
                                $datarek=$request->input("REK");
                                $nu=1;
                                $total_d=0;
                                $sts_no_igracias = true; $no_duplikat = [];
                                for ($i=0;$i < count($datad);$i++){
                                    
                                    $gt = DB::connection('sqlsrvypt')->select("select count(no_spj_igracias) as jum 
                                    from tu_pdaju_m 
                                    where kode_lokasi='$kode_lokasi' and no_spj_igracias='".$datad[$i]['no_spj_igracias']."'
                                    ");
                                    if(count($gt) > 0){
                                        if($gt[0]->jum > 0){
                                            $sts_no_igracias = false;
                                            $msg = 'Transaksi tidak valid. Terdapat data duplikat, no spj ('.$datad[$i]['no_spj_igracias'].') sudah ada di database.';
                                            break;
                                        }
                                    }
                                    //$no_spj = $this->generateKode("tu_pdaju_m", "no_spj", $kode_lokasi."-PJ".substr($datam[0]['periode'],2,4).".", "0001");
                                    $no_spj=$no_agenda."-".strval($nu);
                                    // $upd = "update tu_pdaju_m set progress='1',no_app='".$no_bukti."' where kode_lokasi='".$kode_lokasi."' and no_spj='".$datad[$i]['no_spj']."'";																								
                                    // array_push($exec,$upd);
                                    $sql="insert into tu_pdaju_m (no_spj,tanggal,kode_lokasi,kode_pp,kode_akun,kode_drk,keterangan,nik_buat,nik_spj,periode,tgl_input,progress,no_app,nilai,jenis_pd,sts_bmhd,kode_proyek,no_spj_igracias) values ('$no_spj',getdate(),'".$kode_lokasi."','".$datad[$i]['pp_code']."','".$datam[0]['kode_akun']."','".$datam[0]['kode_drk']."','".$datad[$i]['nama_perjalanan']."','".$datam[0]['nik_buat']."','".$datad[$i]['nip']."','".$datam[0]['periode']."',getdate(),'1','".$no_bukti."',".$datad[$i]['total_biaya'].",'-','-','-','".$datad[$i]['no_spj_igracias']."') ";
                                    $insAju = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp2']=$sql;
    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','91',1,".$datad[$i]['transport'].",".$datad[$i]['transport'].") ";
                                    $insAjud1 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp3']=$sql;
                                    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','92',1,".$datad[$i]['harian'].",".$datad[$i]['harian'].") ";
                                    $insAjud2 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp4']=$sql;
    
                                    $sql="insert into tu_pdaju_d (no_spj,kode_lokasi,kode_param,jumlah,nilai,total) values ('$no_spj','$kode_lokasi','93',1,".$datad[$i]['lain_lain'].",".$datad[$i]['lain_lain'].") ";
                                    $insAjud3 = DB::connection('sqlsrvypt')->insert($sql);
                                    //$success['tmp5']=$sql;
    
                                    $total_d= $total_d + floatval($datad[$i]['total_biaya']);
                                    $nu++;
                                }
                                
                                if($sts_no_igracias){

                                    if(count($datarek) > 0){
                                        if(count($datarek) == 1){
                                            $nu=1; 
                                            $nip = (isset($datad[0]['nip']) ? $datad[0]['nip'] : '-');
                                            $total_biaya = $total_d;

                                            $sql="insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_agenda."','".$kode_lokasi."','".$datarek[0]['bank']."','".$datarek[0]['no_rekening']."','".$datarek[0]['nama']."','-',".$total_biaya.",'".$nip."',0,'".$no_agenda."-".$nu."')";

                                            $sql3 = DB::connection('sqlsrvypt')->insert($sql);
                                        }else{
                                            $nu = 1;
                                            for ($i=0;$i < count($datarek);$i++){
            
                                                $nip = (isset($datad[$i]['nip']) ? $datad[$i]['nip'] : $datad[0]['nip']);
                                                $total_biaya = (isset($datad[$i]['total_biaya']) ? $datad[$i]['total_biaya'] : $datad[0]['total_biaya'] );
                                                $sql="insert into it_aju_rek(no_aju,kode_lokasi,bank,no_rek,nama_rek,bank_trans,nilai,keterangan,pajak,berita) values ('".$no_agenda."','".$kode_lokasi."','".$datarek[$i]['bank']."','".$datarek[$i]['no_rekening']."','".$datarek[$i]['nama']."','-',".$total_biaya.",'".$nip."',0,'".$no_agenda."-".$nu."')";
                                                $sql3 = DB::connection('sqlsrvypt')->insert($sql);
                                                //$success['tmp6']=$sql;
                                                $nu++;
                                            }
                                        }
                                    }
                                    
                                    // $sql4 = "update a set a.bank=b.bank,a.no_rek=b.no_rek,a.nama_rek=b.nama_rek,a.bank_trans=b.cabang 
                                    // from it_aju_rek a inner join karyawan b on a.keterangan=b.nik and a.kode_lokasi=b.kode_lokasi 
                                    // where a.no_aju='".$no_agenda."' and a.kode_lokasi='".$kode_lokasi."'";
                                    
                                    // array_push($exec,$sql4);
                                    // $no_ref1 = isset($data[0]['no_ref1']) ? $data[0]['no_ref1'] : '-';
                                    $no_ref1 = $datam[0]['no_ref1'];
                                    $ck_ref1 = DB::connection('sqlsrvypt')->select("select no_aju from it_aju_m where no_ref1=? ",[strval($no_ref1)]);
                                    if(count($ck_ref1) > 0){
                                        $sts = false;
                                        $msg = "Transaksi tidak valid. Terdapat data duplikat. Kode SPPD ".$no_ref1." sudah terdaftar dengan No Agenda = ".$ck_ref1[0]->no_aju.".";
                                        DB::connection('sqlsrvypt')->rollback();
                                    }else{
                                        $sql="insert into it_aju_m(no_aju,kode_lokasi,periode,tanggal,modul,kode_akun,kode_pp,kode_drk,keterangan,nilai,tgl_input,nik_user,no_kpa,no_app,no_ver,no_fiat,no_kas,progress,nik_panjar,no_ptg,user_input,form,sts_pajak,npajak,nik_app,no_ref1) values ('".$no_agenda."','".$kode_lokasi."','".$datam[0]['periode']."','".$datam[0]['tanggal']."','".$datam[0]['jenis_trans']."','".$datam[0]['kode_akun']."','".$datam[0]['kode_pp']."','".$datam[0]['kode_drk']."','".$datam[0]['keterangan']."',".$datam[0]['total'].",getdate(),'".$nik."','-','-','-','-','-','A','-','-','".$datam[0]['nik_buat']."','SPPD','NON',0,'".$datam[0]['nik_app']."','".$no_ref1."')";
                                        //$success['tmp7']=$sql;
                                        $sql5 = DB::connection('sqlsrvypt')->insert($sql);	
            
                                        //insert it_aju_dok
                                        $dataDok = $request->input("URL_DOK");
                                        $nu=1;
                                        if(count($dataDok) > 0 ){
                                            for ($i=0;$i < count($dataDok);$i++){
                                            
                                                $sql ="insert into it_aju_dok(no_bukti,modul,no_gambar,kode_lokasi,jenis) values ('".$no_agenda."','SPPD','".$dataDok[$i]."','$kode_lokasi',1)";
                                                $upload = DB::connection('sqlsrvypt')->insert($sql);
                                                $nu++;
                                            }	
                                        }
            
                                        DB::connection('sqlsrvypt')->commit();
                                        $success['no_agenda']=$no_agenda;
                                        $success['no_bukti']=$no_bukti;
                                    }
                                }else{
                                    DB::connection('sqlsrvypt')->rollback();
                                    $sts = false;
                                }

                            } 
                        }
                    }
    
                }else{
                    $msg = "Kode akun tidak valid. Status Anggaran kode akun ".$datam[0]['kode_akun']." tidak valid.";
                    $sts = false;	
                }
            }

            $success['status'] = $sts;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);     
          
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error.".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function releaseBudget($no_agenda){
        if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
       
        DB::connection('sqlsrvypt')->beginTransaction();
        try {

            //$exec = array();

            $ckr = DB::connection('sqlsrvypt')->select("select no_ref1 from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_agenda'");
            $no_ref1 = count($ckr) > 0 ? $ckr[0]->no_ref1 : '-';
          
            $cek = DB::connection('sqlsrvypt')->select("select no_aju from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_agenda' and progress not in ('A','R','D') ");
            if(count($cek) > 0){
                
                // SAVE LOG TO DB
                $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
                    values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,'Release Budget Gagal. No Agenda tidak dapat dihapus karena sudah diproses di Keuangan.','RELEASE-GAGAL',$no_ref1));
                // END SAVE
                DB::connection('sqlsrvypt')->commit();

                $success['status'] = false;
                $success['message'] = "Release Budget Gagal. No Agenda tidak dapat dihapus karena sudah diproses di Keuangan.";
            }else{

                $cek2 = DB::connection('sqlsrvypt')->select("select no_aju from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_agenda' and progress in ('A','R','D')  ");
                if(count($cek2) > 0){

                    // SAVE LOG TO DB
                    $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
                    values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,'','RELEASE',$no_ref1));
                    // END SAVE

                    $del = DB::connection('sqlsrvypt')->table('it_aju_m')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_agenda)->delete();
                    $del2 = DB::connection('sqlsrvypt')->table('it_aju_d')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_agenda)->delete();
                    $del3 = DB::connection('sqlsrvypt')->table('it_aju_rek')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_agenda)->delete();
                    $del4 = DB::connection('sqlsrvypt')->table('angg_r')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_agenda)->delete();
                    $del5 = DB::connection('sqlsrvypt')->table('tu_pdaju_m')->where('kode_lokasi', $kode_lokasi)->where('no_spj','like' ,$no_agenda.'-%')->delete();
                    $del6 = DB::connection('sqlsrvypt')->table('tu_pdaju_d')->where('kode_lokasi', $kode_lokasi)->where('no_spj','like' ,$no_agenda.'-%')->delete();
                    $del7 = DB::connection('sqlsrvypt')->table('tu_pdapp_m')->where('kode_lokasi', $kode_lokasi)->where('no_aju', $no_agenda)->delete();
                    $del8 = DB::connection('sqlsrvypt')->table('it_aju_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_agenda)->delete();
                    DB::connection('sqlsrvypt')->commit();

                    $success['no_agenda']=$no_agenda;
                    $success['status'] = true;
                    $success['message'] = "Release Budget Sukses";

                }else{
                    $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
                    values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,'Release Budget Gagal. No Agenda tidak valid','RELEASE-GAGAL',$no_ref1));
                    // END SAVE
                    DB::connection('sqlsrvypt')->commit();

                    $success['status'] = false;
                    $success['message'] = "Release Budget Gagal. No Agenda tidak valid.";
                }

            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();

            DB::connection('sqlsrvypt')->beginTransaction();

            $ckr = DB::connection('sqlsrvypt')->select("select no_ref1 from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_agenda'");
            $no_ref1 = count($ckr) > 0 ? $ckr[0]->no_ref1 : '-';

            $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis,no_ref1)
            values (?, ?, getdate(), ?, ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,print_r($e->getMessage(), true),'RELEASE-GAGAL',$no_ref1));
            // END SAVE
            DB::connection('sqlsrvypt')->commit();

            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);


            return response()->json($success, $this->successStatus);
        }
    }

    public function getProgress($no_agenda){
        if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
       
        DB::connection('sqlsrvypt')->beginTransaction();
        try {
          
            $res = DB::connection('sqlsrvypt')->select("select progress from it_aju_m where kode_lokasi='$kode_lokasi' and no_aju='$no_agenda' ");
            
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['progress'] = $res[0]['progress'];
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Agenda Tidak ditemukan!";
                $success['progress'] = "-";
                $success['status'] = false;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }
   
    public function kirimNoAgenda(Request $request){
        if($data =  Auth::guard('ypt')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        DB::connection('sqlsrvypt')->beginTransaction();
        try {
        
            $cek = DB::connection('sqlsrvypt')->select("select a.nilai,a.kode_pp,a.keterangan,a.tanggal,a.kode_pp+' - '+b.nama as pp,a.kode_akun+' - '+c.nama as akun,a.kode_drk+' - '+isnull(d.nama,'-') as drk 
            from it_aju_m a 
                           inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                           inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                           left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi 
            where a.no_aju = '".$request->input("no_agenda")."' and a.nilai=".$request->input('nilai')." and a.kode_lokasi='".$kode_lokasi."' and a.progress='A' ");
            $periode = DB::connection('sqlsrvypt')->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi'
            ");
            $periode = json_decode(json_encode($periode),true);

            if(count($cek) > 0){

                // SAVE LOG TO DB
                $log = print_r($request->input(), true); 
                $save_log = DB::connection('sqlsrvypt')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog,jenis)
                values (?, ?, getdate(), ?, ?, ?) ",array($no_agenda,$kode_lokasi,$nik,$log,'KIRIM'));
                // END SAVE

                $no_app = generateKode("it_ajuapp_m", "no_app", $kode_lokasi."-APP".substr($periode[0]['periode'],2,2).".", "00001");

                $sql = DB::connection('sqlsrvypt')->insert("insert into it_ajuapp_m(no_app,no_aju,kode_lokasi,periode,tgl_input,user_input,tgl_aju,nik_terima) values 
                        ('".$no_app."','".$request->input("no_agenda")."','".$kode_lokasi."','".$periode[0]['periode']."',getdate(),'".$request->input("user_input")."',getdate(),'".$request->input("nik_terima")."')");	
                $sql2 = DB::connection('sqlsrvypt')->table('it_aju_m')
                        ->where('no_aju', $request->input('no_agenda'))          
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['progress' => '0','tanggal'=>date('Y-m-d'),'no_app'=>$no_app]);
                
                if($sql && $sql2){
                    DB::connection('sqlsrvypt')->commit();
                    $sts = true;
                    $msg = "No agenda sukses terkirim";
                }else{
                    $sts = false;
                    $msg = "No agenda gagal terkirim";
                }
            }else{
                $sts = false;
                $msg = "Error : No agenda tidak ditemukan";
            }

            $success['status'] = $sts;
            $success['message'] = $msg;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvypt')->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAgendaDok($no_agenda){
        try {

            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection('sqlsrvypt')->select("select a.no_aju,a.no_app,convert(varchar,b.tgl_input,103) as tgl_dok
            from it_aju_m a
            inner join it_ajuapp_m b on a.no_aju=b.no_aju and a.kode_lokasi=b.kode_lokasi and a.no_app=b.no_app
            where a.kode_lokasi='$kode_lokasi' and a.no_aju='".$no_agenda."'  
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

    public function getAgendaBayar($no_agenda){

        try {

            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection('sqlsrvypt')->select("select a.no_aju,a.no_spb,a.no_kas,convert(varchar,a.tanggal,103) as tgl_bayar
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.no_aju='".$no_agenda."' 
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


    public function cek(Request $request){
        $cekB = json_decode(json_encode($this->cekBudget($request)),true);
        $dtbugdet = $cekB['original'];
        dump($dtbugdet["saldo_budget"]);
    }

    public function tes_store(Request $request){

		DB::connection('sqlsrvyptkug')->beginTransaction();
        try{
            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_agenda = $this->generateKode("sppd_log", "no_bukti", $kode_lokasi."-".substr("202104",2,2).".", "00001");
            $log = print_r($request->all(), true); 
            DB::connection('sqlsrvyptkug')->insert("insert into sppd_log (no_bukti,kode_lokasi,tgl_input,nik_user,datalog)
            values ('$no_agenda','$kode_lokasi',getdate(),$nik,'".$log."') ");
            DB::connection('sqlsrvyptkug')->commit();

            $success['message'] = "OK";
            $success['status'] = true;
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvyptkug')->rollback();
            $success['message'] = $e;
            $success['status'] = false;
            return response()->json($success, 500);
        }
    }

    // DATA ASSET
    public function getAset(Request $r){

        $this->validate($r,[
            'no_hutang' => 'required|max:20'
        ]);

        try {

            if($data =  Auth::guard('ypt')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = DB::connection('sqlsrvypt')->select("
            select no_fa, tgl_perolehan, kode_klpfa, kode_pp, nama as uraian, nilai  
            from fa_asset
            where kode_lokasi=? and no_fa=? 
            ",[$kode_lokasi, $r->input('no_hutang')]);
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

}
