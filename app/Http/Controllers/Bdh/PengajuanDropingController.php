<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PengajuanDropingController extends Controller
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
            $no_bukti = $this->generateKode("ys_minta_m", "no_minta", $kode_lokasi."-PMT".substr($periode,2,4).".", "0001");

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

            $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $sql="select a.no_minta,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai 
            from ys_minta_m a 
            where a.kode_pp ='".$kode_pp."' and a.kode_lokasi='".$kode_lokasi."' and a.progress in ('0','C')";

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
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'kode_pp' => 'required|max:10',
            'total' => 'required',
            'kode_akun' => 'required|array',
            'kegiatan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("ys_minta_m", "no_minta", $kode_lokasi."-PMT".substr($periode,2,4).".", "0001");
            
            $j = 0;
            $total = 0;
            if(count($request->kode_akun) > 0){
                for ($i=0; $i<count($request->kode_akun); $i++){	
                    $insj[$i] = DB::connection($this->db)->insert("insert into ys_minta_d(no_minta,kode_lokasi,nu,kode_akun,keterangan,nilai_usul,nilai_app) values (?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$i,$request->kode_akun[$i],$request->kegiatan[$i],floatval($request->nilai[$i]),floatval($request->nilai[$i])));
                    $total+= +floatval($request->nilai[$i]);
                }
            }

            if($total != floatval($request->total)){
                $msg = "Transaksi tidak valid. Total Droping ($request->total) dan Total Detail Permintaan ($total) tidak sama.";
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $msg;
            }else{
                if($total > 0){

                    $ins1 = DB::connection($this->db)->insert("insert into ys_minta_m (no_minta,kode_lokasi,no_dokumen,tanggal,keterangan,kode_pp,modul,jenis,periode,nilai,nik_buat,nik_app,tgl_input,nik_user,progress,no_app,no_kas,akun_terima) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,$request->tanggal,$request->deskripsi,$request->kode_pp,'MINTA','MINTA',$periode,floatval($request->total),$nik,$nik,$nik,'0','-','-','-'));
                    
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
                            
                            $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        }
        
                        if(count($arr_no_urut) > 0){
                            for($i=0; $i<count($arr_no_urut);$i++){
                                $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'MINTA',$no_bukti)); 
                            }
                        }
                    }

                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['no_bukti'] = $no_bukti;
                    $success['message'] = "Data Pengajuan Droping berhasil disimpan";
                }else{

                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = "Transaksi tidak valid. Total Droping tidak boleh kurang dari atau sama dengan nol";
                }
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan Droping gagal disimpan ".$e;
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
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'kode_pp' => 'required|max:10',
            'total' => 'required',
            'kode_akun' => 'required|array',
            'kegiatan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('ys_minta_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_minta', $no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('ys_minta_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_minta', $no_bukti)
            ->delete();

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $j = 0;
            $total = 0;
            if(count($request->kode_akun) > 0){
                for ($i=0; $i<count($request->kode_akun); $i++){	
                    $insj[$i] = DB::connection($this->db)->insert("insert into ys_minta_d(no_minta,kode_lokasi,nu,kode_akun,keterangan,nilai_usul,nilai_app) values (?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$i,$request->kode_akun[$i],$request->kegiatan[$i],floatval($request->nilai[$i]),floatval($request->nilai[$i])));
                    $total+= +floatval($request->nilai[$i]);
                }
            }

            if($total != floatval($request->total)){
                $msg = "Transaksi tidak valid. Total Droping ($request->total) dan Total Detail Permintaan ($total) tidak sama.";
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $msg;
            }else{
                if($total > 0){

                    $ins1 = DB::connection($this->db)->insert("insert into ys_minta_m (no_minta,kode_lokasi,no_dokumen,tanggal,keterangan,kode_pp,modul,jenis,periode,nilai,nik_buat,nik_app,tgl_input,nik_user,progress,no_app,no_kas,akun_terima) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,$request->tanggal,$request->deskripsi,$request->kode_pp,'MINTA','MINTA',$periode,floatval($request->total),$nik,$nik,$nik,'0','-','-','-'));
                    
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
                            
                            $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        }
        
                        if(count($arr_no_urut) > 0){
                            for($i=0; $i<count($arr_no_urut);$i++){
                                $insdok[$i] = DB::connection($this->db)->insert("insert into pbh_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'MINTA',$no_bukti)); 
                            }
                        }
                    }

                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['no_bukti'] = $no_bukti;
                    $success['message'] = "Data Pengajuan Droping berhasil diubah";
                }else{

                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = "Transaksi tidak valid. Total Droping tidak boleh kurang dari atau sama dengan nol";
                }
            }
                            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Pengajuan Droping gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
           
            $del = DB::connection($this->db)->table('ys_minta_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_minta', $no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('ys_minta_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_minta', $no_bukti)
            ->delete();

            $res = DB::connection($this->db)->select("select * from pbh_dok where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);
            for($i=0;$i<count($res);$i++){
                if(Storage::disk('s3')->exists('bdh/'.$res[$i]['no_gambar'])){
                    Storage::disk('s3')->delete('bdh/'.$res[$i]['no_gambar']);
                }
            }

            $deldok = DB::connection($this->db)->table('pbh_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pengajuan Droping berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan Droping gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function cekBudget(Request $request)
    {
        $this->validate($request,[
            'kode_akun_agg' => 'required|array',
            'nilai_agg' => 'required|array',
            'periode' => 'required',
            'kode_pp' => 'required',
            'no_bukti' => 'required',
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $nilai = 0; $total = 0;
            $sls = 0;
            $result = array();
			for ($i=0;$i < count($request->kode_akun_agg);$i++){
                $garPeriode = 0;
                $mintaPeriode = 0;
				$strSQL = "select sum(case dc when 'D' then nilai else -nilai end) as gar from anggaran_d where kode_lokasi='".$kode_lokasi."' and kode_akun='".$request->kode_akun_agg[$i]."' and kode_pp='".$request->kode_pp."' and periode='".$request->periode."' ";	
                $res = DB::connection($this->db)->select($strSQL);
				if (count($res) > 0){
					$line = $res[0];
                    if($line->gar != ""){
                        $garPeriode = floatval($line->gar);
                    }
                }

                $strSQL = "select isnull(sum(a.nilai_app),0) as minta 
                from ys_minta_d a inner join ys_minta_m b on a.no_minta=b.no_minta and a.kode_lokasi=b.kode_lokasi
                where a.no_minta <> '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_akun='".$request->kode_akun_agg[$i]."' and b.kode_pp='".$request->kode_pp."' and b.periode='".$request->periode."' ";	
                $res2 = DB::connection($this->db)->select($strSQL);
				if (count($res2) > 0){
					$line2 = $res2[0];
                    if($line2->minta != ""){
                        $mintaPeriode = floatval($line2->minta);
                    }
                }
			    $sls = $garPeriode - $mintaPeriode;			
                $so_awal = $sls;
                $so_akhir = $so_awal - floatval($request->nilai_agg[$i]);

                $hasil = array(
                    'kode_akun_agg' => $request->kode_akun_agg[$i],
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

            $strSQL = "select *
            from ys_minta_m a 
            where a.no_minta = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' ";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            $strSQL2 = "select a.kode_akun,b.nama,a.keterangan,a.nilai_usul 
            from ys_minta_d a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            where a.no_minta = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
            $rs2 = DB::connection($this->db)->select($strSQL2);
            $res2 = json_decode(json_encode($rs2),true);

            $strSQL3 = "select b.kode_jenis,b.nama,a.no_gambar 
            from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
            where a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
            $rs3 = DB::connection($this->db)->select($strSQL3);
            $res3 = json_decode(json_encode($rs3),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_minta'] = $res2;
                $success['detail_dok'] = $res3;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_minta'] = [];
                $success['detail_dok'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_minta'] = [];
            $success['detail_dok'] = [];
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

            $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $strSQL = "select a.kode_akun,a.nama 
            from masakun a 
            inner join (select a.kode_akun,a.kode_lokasi 
            			from anggaran_d a 
            			where a.kode_lokasi='".$kode_lokasi."' and a.kode_pp='".$kode_pp."' 
            			group by a.kode_akun,a.kode_lokasi 
            			)b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 	
            where a.kode_lokasi='".$kode_lokasi."'";

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

            // if ($status_admin == "A") {
                $strSQL = "select a.kode_pp,a.nama from pp a where a.flag_aktif= '1' and a.kode_lokasi = '".$kode_lokasi."'";
            // }
            // else {
            //     $strSQL = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi where b.nik='".$nik."' and a.flag_aktif= '1' and a.kode_lokasi = '".$kode_lokasi."'";
            // }

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
