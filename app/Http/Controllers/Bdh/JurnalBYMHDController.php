<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class JurnalBYMHDController extends Controller
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
            $no_bukti = $this->generateKode("bmhd_m", "no_bmhd", $kode_lokasi."-BY".substr($periode,2,4).".", "0001");

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

            $sql="select a.no_bmhd,convert(varchar,a.tanggal,103) as tgl,'BMHD' as jenis,a.no_dokumen,a.keterangan,a.nilai,a.kode_vendor,a.kode_akun as akun_bmhd,c.nama as nama_vendor 
            from bmhd_m a 				
            inner join vendor c on a.kode_vendor=c.kode_vendor and a.kode_lokasi=c.kode_lokasi 
            left join (select distinct no_bmhd,kode_lokasi from bmhd_bayar where kode_lokasi='".$kode_lokasi."') b on a.no_bmhd=b.no_bmhd and a.kode_lokasi=b.kode_lokasi 
            where b.no_bmhd is null and a.kode_lokasi='".$kode_lokasi."' and a.posted ='F'";

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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'kode_unit' => 'required',
            'akun_bmhd' => 'required',
            'kode_vendor' => 'required',
            'nik_tahu' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'nilai_bmhd' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'dc' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'kode_akun_agg' => 'required|array',
            'kode_pp_agg' => 'required|array',
            'kode_drk_agg' => 'required|array',
            'saldo_awal_agg' => 'required|array',
            'nilai_agg' => 'required|array',
            'saldo_akhir_agg' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("bmhd_m", "no_bmhd", $kode_lokasi."-BY".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $flagGarFree = "0";
			    $get= DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('GARFREE') and kode_lokasi = '".$kode_lokasi."'");			
                if (count($get) > 0){
                    $line = $get[0];
                    if ($line->kode_spro == "GARFREE") $flagGarFree = $line->flag;								
                }

                $j = 0;
                $total = 0; $total_d = 0; $total_k =0;
                if(count($request->kode_akun) > 0){
                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into bmhd_j(no_bmhd,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],'-',$kode_lokasi,'BMHD','UMUM',$periode,'IDR',1,$nik));
                        if($request->dc[$i] == "D"){
                            $total_d+= +floatval($request->nilai[$i]);
                        }else{
                            $total_k+= +floatval($request->nilai[$i]);
                        }
                    }
                }

                if(($total_k + floatval($request->nilai_bmhd)) != $total_d){
                    $msg = "Transaksi tidak valid. Nilai BMHD,Total Debet dan Kredit tidak balance.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    // if($request->nilai_bmhd > 0){
                        
                        $insm = DB::connection($this->db)->insert("insert into bmhd_m(no_bmhd,kode_lokasi,no_dokumen,kode_akun,tanggal,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1, dc,kode_vendor) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,$akun_bmhd,$request->tanggal,$request->deskripsi,$kode_pp,'BMHD',$periode,'IDR',1,floatval($total_d),$nik,$request->nik_tahu,$nik,'F','-','-','-','D',$request->kode_vendor));

                        $insjj = DB::connection($this->db)->insert("insert into bmhd_j(no_bmhd, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,999,$request->akun_bmhd,$request->deskripsi,C,floatval($request->nilai_bmhd),$request->kode_unit,$request->kode_drk,'-',$kode_lokasi,'BMHD','BMHD',$periode,'IDR',1,$nik));
                        
                        $total_agg = 0; $sts_agg = true; $msg_agg ="";
                        if (count($request->kode_akun_agg) > 0){
                            for ($i=0; $i < count($request->kode_akun_agg);$i++){
                                if (floatval($request->nilai_agg[$i]) > 0) {
                                    $DC = "D"; 
                                    $nilai = floatval($request->nilai_agg[$i]);
                                } else {
                                    $DC = "C";
                                    $nilai = floatval($request->nilai_agg[$i]) * -1;
                                }

                                if($flagGarFree == "0"){

                                    if(floatval($request->nilai_agg[$i]) > 0 && (floatval($request->nilai_agg[$i]) > floatval($request->saldo_awal_agg[$i]))){
                                        $sts_agg = false;
                                        $msg_agg .= "Transaksi tidak valid. Saldo Anggaran Akun ".$request->kode_akun_agg[$i]." tidak mencukupi. [Baris : ".($i+1)."] , silahkan melakukan RRA dari menu anggaran";
                                        break;
                                    }
                                }

                                DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,'BMHD',$kode_lokasi,$request->kode_akun_agg[$i],$request->kode_pp_agg[$i],$request->kode_drk_agg[$i],$periode,$periode,$DC,floatval($request->saldo_akhir_agg[$i]),$nilai));

                                if($DC == "D"){
                                    $total_agg+= floatval($request->nilai[$i]);
                                }
                            }
                        }

                        if(!$sts_agg){
                            DB::connection($this->db)->rollback();
                            $success['status'] = true;
                            $success['no_bukti'] = "-";
                            $success['message'] = $msg_agg;
                        }else{
                            DB::connection($this->db)->commit();
                            $success['status'] = true;
                            $success['no_bukti'] = $no_bukti;
                            $success['message'] = "Data Jurnal BYMHD berhasil disimpan";
                        }

                    // }else{

                    //     DB::connection($this->db)->rollback();
                    //     $success['status'] = false;
                    //     $success['no_bukti'] = "-";
                    //     $success['message'] = "Transaksi tidak valid. Total Jurnal BYMHD tidak boleh kurang dari atau sama dengan nol";
                    // }
                }
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
            $success['message'] = "Data Jurnal BYMHD gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
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
            'kode_unit' => 'required',
            'akun_bmhd' => 'required',
            'kode_vendor' => 'required',
            'nik_tahu' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'nilai_bmhd' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'dc' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'kode_akun_agg' => 'required|array',
            'kode_pp_agg' => 'required|array',
            'kode_drk_agg' => 'required|array',
            'saldo_awal_agg' => 'required|array',
            'nilai_agg' => 'required|array',
            'saldo_akhir_agg' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $del4 = DB::connection($this->db)->table('bmhd_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bmhd', $no_bukti)
                ->delete();

                $del5 = DB::connection($this->db)->table('bmhd_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bmhd', $no_bukti)
                ->delete();

                $del6 = DB::connection($this->db)->table('angg_r')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $flagGarFree = "0";
			    $get= DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('GARFREE') and kode_lokasi = '".$kode_lokasi."'");			
                if (count($get) > 0){
                    $line = $get[0];
                    if ($line->kode_spro == "GARFREE") $flagGarFree = $line->flag;								
                }
                
                $j = 0;
                $total = 0; $total_d = 0; $total_k =0;
                if(count($request->kode_akun) > 0){
                    for ($i=0; $i<count($request->kode_akun); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into bmhd_j(no_bmhd,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($no_bukti,$request->no_dokumen,$request->tanggal,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],'-',$kode_lokasi,'BMHD','UMUM',$periode,'IDR',1,$nik));
                        if($request->dc[$i] == "D"){
                            $total_d+= +floatval($request->nilai[$i]);
                        }else{
                            $total_k+= +floatval($request->nilai[$i]);
                        }
                    }
                }

                if(($total_k + floatval($request->nilai_bmhd)) != $total_d){
                    $msg = "Transaksi tidak valid. Nilai BMHD,Total Debet dan Kredit tidak balance.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    // if($request->nilai_bmhd > 0){
                        
                        $insm = DB::connection($this->db)->insert("insert into bmhd_m(no_bmhd,kode_lokasi,no_dokumen,kode_akun,tanggal,keterangan,kode_pp,modul,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1, dc,kode_vendor) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,$akun_bmhd,$request->tanggal,$request->deskripsi,$kode_pp,'BMHD',$periode,'IDR',1,floatval($total_d),$nik,$request->nik_tahu,$nik,'F','-','-','-','D',$request->kode_vendor));

                        $insjj = DB::connection($this->db)->insert("insert into bmhd_j(no_bmhd, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?)",array($no_bukti,$request->no_dokumen,$request->tanggal,999,$request->akun_bmhd,$request->deskripsi,C,floatval($request->nilai_bmhd),$request->kode_unit,$request->kode_drk,'-',$kode_lokasi,'BMHD','BMHD',$periode,'IDR',1,$nik));
                        
                        $total_agg = 0; $sts_agg = true; $msg_agg ="";
                        if (count($request->kode_akun_agg) > 0){
                            for ($i=0; $i < count($request->kode_akun_agg);$i++){
                                if (floatval($request->nilai_agg[$i]) > 0) {
                                    $DC = "D"; 
                                    $nilai = floatval($request->nilai_agg[$i]);
                                } else {
                                    $DC = "C";
                                    $nilai = floatval($request->nilai_agg[$i]) * -1;
                                }

                                if($flagGarFree == "0"){
                                    if(floatval($request->nilai_agg[$i]) > 0 && (floatval($request->nilai_agg[$i]) > floatval($request->saldo_awal_agg[$i]))){
                                        $sts_agg = false;
                                        $msg_agg .= "Transaksi tidak valid. Saldo Anggaran Akun ".$request->kode_akun_agg[$i]." tidak mencukupi. [Baris : ".($i+1)."] , silahkan melakukan RRA dari menu anggaran";
                                        break;
                                    }
                                }

                                DB::connection($this->db)->insert("insert into angg_r(no_bukti,modul,kode_lokasi,kode_akun,kode_pp,kode_drk,periode1,periode2,dc,saldo,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,'BMHD',$kode_lokasi,$request->kode_akun_agg[$i],$request->kode_pp_agg[$i],$request->kode_drk_agg[$i],$periode,$periode,$DC,floatval($request->saldo_akhir_agg[$i]),$nilai));

                                if($DC == "D"){
                                    $total_agg+= floatval($request->nilai[$i]);
                                }
                            }
                        }

                        if(!$sts_agg){
                            DB::connection($this->db)->rollback();
                            $success['status'] = true;
                            $success['no_bukti'] = "-";
                            $success['message'] = $msg_agg;
                        }else{
                            DB::connection($this->db)->commit();
                            $success['status'] = true;
                            $success['no_bukti'] = $no_bukti;
                            $success['message'] = "Data Jurnal BYMHD berhasil diubah";
                        }

                    // }else{

                    //     DB::connection($this->db)->rollback();
                    //     $success['status'] = false;
                    //     $success['no_bukti'] = "-";
                    //     $success['message'] = "Transaksi tidak valid. Total Jurnal BYMHD tidak boleh kurang dari atau sama dengan nol";
                    // }
                }
            
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
            $success['no_bukti'] = "-";
            $success['message'] = "Data Jurnal BYMHD gagal diubah ".$e;
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
            'no_bukti' => 'required',
            'no_panjar' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
            if(isset($request->periode) && $request->periode != ""){
                $periode = $request->periode;
            }else{
                $periode = date('Ym');
            }
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $del4 = DB::connection($this->db)->table('bmhd_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bmhd', $no_bukti)
                ->delete();

                $del5 = DB::connection($this->db)->table('bmhd_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bmhd', $no_bukti)
                ->delete();

                $del6 = DB::connection($this->db)->table('angg_r')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Jurnal BYMHD berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal BYMHD gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function cekBudget(Request $request)
    {
        $this->validate($request,[
            'kode_akun_agg' => 'required|array',
            'kode_pp_agg' => 'required|array',
            'kode_drk_agg' => 'required|array',
            'nilai_agg' => 'required|array',
            'periode' => 'required',
            'sts_simpan' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            if(isset($request->no_bukti)){
                $no_bukti = $request->no_bukti;
            }else{
                $no_bukti = '-';
            }
            $nilai = 0; $total = 0;
            $sls = 0;
            $result = array();
			for ($i=0;$i < count($request->kode_akun_agg);$i++){
                if($request->sts_simpan == 1){
                    $strSQL = "select dbo.fn_cekagg2('".$request->kode_pp_agg[$i]."','".$kode_lokasi."','".$request->kode_akun_agg[$i]."','".$request->kode_drk_agg[$i]."','".$periode."') as gar ";			
                }else{

                    $strSQL = "select dbo.fn_cekagg3('".$request->kode_pp_agg[$i]."','".$kode_lokasi."','".$request->kode_akun_agg[$i]."','".$request->kode_drk_agg[$i]."','".$periode."','".$no_bukti."') as gar ";			
                }
                $res = DB::connection($this->db)->select($strSQL);
				if (count($res) > 0){
					$line = $res[0];
                    if($line->gar != ""){
                        $data = explode(";",$line->gar);					
                        $so_awal = floatval($data[0]) - floatval($data[1]);
                        $so_akhir = $so_awal - floatval($request->nilai_agg[$i]);

                    }else{
                        $so_awal = 0;
                        $so_akhir = $so_awal - floatval($request->nilai_agg[$i]);
                    }
				}else{
                    $so_awal = 0;
					$so_akhir = $so_awal - floatval($request->nilai_agg[$i]);
                }

                $hasil = array(
                    'kode_akun_agg' => $request->kode_akun_agg[$i],
                    'kode_pp_agg' => $request->kode_pp_agg[$i],
                    'kode_drk_agg' => $request->kode_drk_agg[$i],
                    'so_awal_agg' => $so_awal,
                    'nilai_agg' => $request->nilai_agg[$i],
                    'so_akhir_agg' => $so_akhir,
                );
                $result[] = $hasil;
			}
            
            if(count($result) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $result;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
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

            $strSQL = "select a.*,b.nama as nama_pp,c.nama as nama_vendor,d.nama as nama_tahu 
            from bmhd_m a 		
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi	
            left join vendor c on a.kode_vendor=c.kode_vendor and a.kode_lokasi=c.kode_lokasi	
            left join karyawan d on a.nik_app=d.nik and a.kode_lokasi=d.kode_lokasi	
            where a.no_bmhd = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."'";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $strj = "select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp, a.kode_drk, isnull(d.nama,'-') as nama_drk 
                from bmhd_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                              inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 																				
                			   left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4) = d.tahun 
                where a.jenis = 'UMUM' and a.no_bmhd = '".$request->no_bmhd."' and a.kode_lokasi='".$kode_lokasi."' order by a.no_urut";
                $rsj = DB::connection($this->db)->select($strj);
                $resj = json_decode(json_encode($rsj),true);

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $resj;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_jurnal'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkunBMHD(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            where b.kode_flag in ('004') and a.kode_lokasi = '".$kode_lokasi."'";

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

    public function getVendor(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select kode_vendor,nama from vendor where  kode_lokasi = '".$kode_lokasi."'";

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


    public function getAkun(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '034' where a.block= '0' and a.kode_lokasi = '".$kode_lokasi."'";

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

            $strSQL = "select a.kode_pp, a.nama  
            from pp a 
            inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            where b.nik='".$nik."' and a.kode_lokasi = '".$kode_lokasi."' and a.tipe='posting' and a.flag_aktif ='1'";
            
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

    public function getUnit(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select kode_pp,nama from pp where flag_aktif='1' and kode_lokasi='$kode_lokasi' ";
            
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

    public function getDRK(Request $request)
    {
        $this->validate($request,[
            'kode_akun' => 'required',
            'periode' => 'required',
            'kode_pp' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $vUnion = "";
            $data = DB::connection($this->db)->select("select status_gar from masakun where kode_akun='".$request->kode_akun."' and kode_lokasi='".$kode_lokasi."'");
            if(count($data) > 0){
                $line = $data[0];							
                if ($line->status_gar != "1") $vUnion = " union select '-','-' "; 
                
            }
            $strSQL="select distinct a.kode_drk, a.nama from drk a inner join anggaran_d b on a.kode_drk=b.kode_drk where a.tahun=substring(b.periode,1,4) and b.periode like '".$request->periode."%' and b.kode_akun='".$request->kode_akun."' and b.kode_pp = '".$request->kode_pp."' and a.kode_lokasi='".$kode_lokasi."' ".$vUnion;

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
    
    public function getNIKTahu(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select nik,nama from karyawan where flag_aktif='1' and kode_lokasi = '".$kode_lokasi."'";
          
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

}
