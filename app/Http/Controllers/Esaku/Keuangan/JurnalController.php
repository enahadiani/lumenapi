<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Imports\JurnalImport;
use App\Exports\JurnalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\JurnalTmp;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class JurnalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';
    public $api_url = "https://eu179.chat-api.com/instance222737/";
    public $token = "p0wo8m8y3twd36o5";
    
    
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

    function isUnik($isi,$no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select no_bukti from trans_m where no_dokumen = '".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '".$no_bukti."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['no_bukti'] = $auth[0]['no_bukti'];
        }else{
            $res['status'] = true;
        }
        return $res;
    }
    
    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            $res = DB::connection($this->db)->select("select no_bukti,tanggal,no_dokumen,keterangan,nilai1,case posted when 'T' then 'Close' else 'Open' end as posted,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input  from trans_m where modul='MI' and kode_lokasi='$kode_lokasi' and periode = '$periode_aktif'	 
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'kode_form' => 'required',
            'deskripsi' => 'required',
            'total_debet' => 'required',
            'total_kredit' => 'required',
            'nik_periksa' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'dc' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('jurnal');
            DB::connection($this->db)->beginTransaction();
            
            
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-JU".substr($periode,2,4).".", "0001");
            
            $cek = $this->doCekPeriode2('MI',$status_admin,$periode);
            
            if($cek['status']){
                $res = $this->isUnik($request->no_dokumen,$no_bukti);
                if($res['status']){
                    $nilai = 0;
                    if (count($request->kode_akun) > 0){
                        for ($j=0;$j < count($request->kode_akun);$j++){
                            if($request->kode_akun[$j] != ""){
                                if($request->dc[$j] == "D"){
                                    $nilai += floatval($request->nilai[$j]);
                                }
                                $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".$j.",'".$request->kode_akun[$j]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','MI','MI','IDR',1,'".$request->kode_pp[$j]."','-','-','-','-','-','-','-','-')");
                                
                            }
                        }
                    }	
                    
                    $sql = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','".$request->kode_form."','F','-','-','".$kode_pp."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",0,0,'".$nik."','".$request->nik_periksa."','-','-','-','-','-','-','MI')");
                    
                    $arr_foto = array();
                    $arr_jenis = array();
                    $arr_no_urut = array();
                    $arr_nama_dok = array();
                    $i=0;
                    $cek = $request->file;
                    if(!empty($cek)){
                        if(count($request->nama_file_seb) > 0){
                            //looping berdasarkan nama dok
                            for($i=0;$i<count($request->nama_file_seb);$i++){
                                //cek row i ada file atau tidak
                                if(isset($request->file('file')[$i])){
                                    $file = $request->file('file')[$i];
                                    //kalo ada cek nama sebelumnya ada atau -
                                    if($request->nama_file_seb[$i] != "-"){
                                        //kalo ada hapus yang lama
                                        Storage::disk('s3')->delete('toko/'.$request->nama_file_seb[$i]);
                                    }
                                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                    $foto = $nama_foto;
                                    if(Storage::disk('s3')->exists('toko/'.$foto)){
                                        Storage::disk('s3')->delete('toko/'.$foto);
                                    }
                                    Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                                    $arr_foto[] = $foto;
                                    $arr_jenis[] = $request->jenis[$i];
                                    $arr_no_urut[] = $request->no_urut[$i];
                                    $arr_nama_dok[] = $request->nama_dok[$i];
                                }else if($request->nama_file_seb[$i] != "-"){
                                    $arr_foto[] = $request->nama_file_seb[$i];
                                    $arr_jenis[] = $request->jenis[$i];
                                    $arr_no_urut[] = $request->no_urut[$i];
                                    $arr_nama_dok[] = $request->nama_dok[$i];
                                }     
                            }
                            
                            $deldok = DB::connection($this->db)->table('trans_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        }
        
                        if(count($arr_no_urut) > 0){
                            for($i=0; $i<count($arr_no_urut);$i++){
                                $insdok[$i] = DB::connection($this->db)->insert("insert into trans_dok (no_bukti,kode_lokasi,file_dok,no_urut,nama,jenis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$arr_no_urut[$i]."','".$arr_nama_dok[$i]."','".$arr_jenis[$i]."') "); 
                            }
                        }
                    }

                    // EMAIL WA STORE POOLING
                    $pesan_body = "Data Jurnal berhasil disimpan dengan no transaksi: ".$no_bukti;
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    $inspool1= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('$request->no_telp_app','$request->email_app','$pesan_body','0',getdate(),NULL,'WA','$no_pool') ");
        
                    $inspool2= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('$request->no_telp_app','$request->email_app','$pesan_body','0',getdate(),NULL,'EMAIL','$no_pool') ");
        
                    $success['no_pooling'] = $no_pool;

                    $tmp="sukses";
                    $sts=true;
                    
                }else{
                    $tmp = "Transaksi tidak valid. No Dokumen '".$request->no_dokumen."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                    $sts = false;
                }
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }         

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Jurnal berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
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
            'no_dokumen' => 'required',
            'tanggal' => 'required',
            'kode_form' => 'required',
            'deskripsi' => 'required',
            'total_debet' => 'required',
            'total_kredit' => 'required',
            'nik_periksa' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'dc' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            DB::connection($this->db)->beginTransaction();
            
            $periode=substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $request->no_bukti;
            
            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
            
            $cek = $this->doCekPeriode2('MI',$status_admin,$periode);
            
            if($cek['status']){
                $res = $this->isUnik($request->no_dokumen,$no_bukti);
                if($res['status']){
                    
                    $nilai = 0;
                    if (count($request->kode_akun) > 0){
                        for ($j=0;$j < count($request->kode_akun);$j++){
                            if($request->kode_akun[$j] != ""){
                                if($request->dc[$j] == "D"){
                                    $nilai += floatval($request->nilai[$j]);
                                }
                                $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".$j.",'".$request->kode_akun[$j]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','MI','MI','IDR',1,'".$request->kode_pp[$j]."','-','-','-','-','-','-','-','-')");
                            }
                        }
                    }	
                    
                    $sql = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','".$request->kode_form."','F','-','-','".$kode_pp."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",0,0,'".$nik."','".$request->nik_periksa."','-','-','-','-','-','-','MI')");
                    
                    $arr_foto = array();
                    $arr_jenis = array();
                    $arr_no_urut = array();
                    $arr_nama_dok = array();
                    $i=0;
                    $cek = $request->file;
                    if(!empty($cek)){
                        if(count($request->nama_file_seb) > 0){
                            //looping berdasarkan nama dok
                            for($i=0;$i<count($request->nama_file_seb);$i++){
                                //cek row i ada file atau tidak
                                if(isset($request->file('file')[$i])){
                                    $file = $request->file('file')[$i];
                                    //kalo ada cek nama sebelumnya ada atau -
                                    if($request->nama_file_seb[$i] != "-"){
                                        //kalo ada hapus yang lama
                                        Storage::disk('s3')->delete('toko/'.$request->nama_file_seb[$i]);
                                    }
                                    $nama_foto = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                    $foto = $nama_foto;
                                    if(Storage::disk('s3')->exists('toko/'.$foto)){
                                        Storage::disk('s3')->delete('toko/'.$foto);
                                    }
                                    Storage::disk('s3')->put('toko/'.$foto,file_get_contents($file));
                                    $arr_foto[] = $foto;
                                    $arr_jenis[] = $request->jenis[$i];
                                    $arr_no_urut[] = $request->no_urut[$i];
                                    $arr_nama_dok[] = $request->nama_dok[$i];
                                }else if($request->nama_file_seb[$i] != "-"){
                                    $arr_foto[] = $request->nama_file_seb[$i];
                                    $arr_jenis[] = $request->jenis[$i];
                                    $arr_no_urut[] = $request->no_urut[$i];
                                    $arr_nama_dok[] = $request->nama_dok[$i];
                                }     
                            }
                            
                            $deldok = DB::connection($this->db)->table('trans_dok')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                        }
        
                        if(count($arr_no_urut) > 0){
                            for($i=0; $i<count($arr_no_urut);$i++){
                                $insdok[$i] = DB::connection($this->db)->insert("insert into trans_dok (no_bukti,kode_lokasi,file_dok,no_urut,nama,jenis) values ('$no_bukti','$kode_lokasi','".$arr_foto[$i]."','".$arr_no_urut[$i]."','".$arr_nama_dok[$i]."','".$arr_jenis[$i]."') "); 
                            }
                        }
                    }

                    $tmp="sukses";
                    $sts=true;
                    
                }else{
                    $tmp = "Transaksi tidak valid. No Dokumen '".$request->no_dokumen."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                    $sts = false;
                }
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }         

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Jurnal berhasil diubah ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            
            $ins = DB::connection($this->db)->insert("insert into trans_h 
            select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync,'$nik',getdate()
            from trans_m 
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'  
            ");

            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.tanggal,a.no_bukti,a.periode,keterangan as deskripsi,a.nilai1,a.no_dokumen,a.modul as jenis,a.nik2 as nik_periksa,b.nama as nama_periksa 
            from trans_m a
            left join karyawan b on a.nik2=b.nik and a.kode_lokasi=b.kode_lokasi
            where a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
                    from trans_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                    where a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res2= json_decode(json_encode($res2),true);

            $sql3="select a.no_bukti,a.kode_lokasi,a.jenis,a.file_dok as fileaddres,a.no_urut,a.nama from trans_dok a
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' order by a.no_urut ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['detail'] = $res2;
                $success['dokumen'] = $res3;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal'] = [];
                $success['detail'] = [];
                $success['dokumen'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkun()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '034' where a.block= '0' and a.kode_lokasi='$kode_lokasi' ");
            $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a where a.block= '0' and a.kode_lokasi='$kode_lokasi' ");						
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

    public function getNIKPeriksa()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik, nama from karyawan where kode_lokasi='".$kode_lokasi."' and flag_aktif='1'
            union all
            select '-' as nik,'-' as nama ");						
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

    public function getNIKPeriksaByNIK($nik)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select(" select * from ( select nik, nama from karyawan where kode_lokasi='".$kode_lokasi."' and flag_aktif='1'
            union all
            select '-' as nik,'-' as nama ) a
            where a.nik='$nik' ");						
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

    public function getPP()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            
            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);
            $kode_pp = $res[0]['kode_pp'];

            if ($status_admin == "U"){

				$sql = "select a.kode_pp,a.nama from pp a where a.kode_pp='".$kode_pp."'  and a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
            }else{

                $sql = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='".$nik."' 
                where a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
            }

            $res2 = DB::connection($this->db)->select($sql);						
            $res2= json_decode(json_encode($res2),true);
            
           
            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res2;
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

    public function getPeriodeJurnal()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $sql = "select distinct a.periode from trans_m a  where a.kode_lokasi = '".$kode_lokasi."' and a.modul='MI'";

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

    
    public function validateJurnal($kode_akun,$kode_pp,$dc,$ket,$nilai,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Akun $kode_akun tidak valid. ";
        }

        $auth2 = DB::connection($this->db)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi'
        ");
        $auth2 = json_decode(json_encode($auth2),true);
        if(count($auth2) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode PP $kode_pp tidak valid. ";
        }

        if(floatval($nilai) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Nilai tidak valid. ";
        }

        if($ket != ""){
            $keterangan .= "";
        }else{
            $keterangan .= "Keterangan tidak valid. ";
        }

        if($dc == "D" || $dc == "C"){
            $keterangan .= "";
        }else{
            $keterangan .= "DC $dc tidak valid. ";
        }

        return $keterangan;
        // return $keterangan;

    }


    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('jurnal_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            // $excel = Excel::import(new JurnalImport($request->nik_user), $nama_file);
            $dt = Excel::toArray(new JurnalImport($request->nik_user),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateJurnal(strval($row[0]),strval($row[4]),strval($row[1]),strval($row[2]),floatval($row[3]),$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $x[] = JurnalTmp::create([
                        'kode_akun' => strval($row[0]),
                        'dc' => strval($row[1]),
                        'keterangan' => strval($row[2]),
                        'nilai' => floatval($row[3]),
                        'kode_pp' => strval($row[4]),
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $request->nik_user,
                        'tgl_input' => date('Y-m-d H:i:s'),
                        'status' => $sts,
                        'ket_status' => $ket,
                        'nu' => $no
                    ]);
                    $no++;
                }
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $nik_user = $request->nik_user;
        $kode_lokasi = $request->kode_lokasi;
        $nik = $request->nik;
        date_default_timezone_set("Asia/Bangkok");
        return Excel::download(new JurnalExport($nik_user,$kode_lokasi), 'Jurnal_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
    }

    public function getJurnalTmp(Request $request)
    {
        
        $nik_user = $request->nik_user;

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp 
            from jurnal_tmp a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['detail'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function sendNotifikasi(Request $request)
	{
		$this->validate($request,[
			"no_pooling" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();

            $res = DB::connection($this->db)->select("select no_hp,pesan,jenis,email from pooling where flag_kirim=0 and no_pool ='$request->no_pooling'  ");
            if(count($res) > 0){
                $msg = "";
                $sts = false;
                foreach($res as $row){
                    if($row->jenis == "WA"){

                        $response = $client->request('GET',  $this->api_url."sendMessage?token=".$this->token,[
                            'form_params' => [
                                'body' => $row->pesan,
                                'phone' => $row->no_hp
                            ]
                        ]);

                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);

                            if($data['sent']){
                                $success['data'] = $data;
                                DB::connection($this->db)->update("update pooling set tgl_kirim=getdate(),flag_kirim=1 where flag_kirim=0 and no_pool ='$request->no_pooling' and jenis='WA'
                                ");
                                DB::connection($this->db)->commit();
                                $sts = true;
                                $msg .=$data['message'];
                            }
                        }


                    }else if($row->jenis == "EMAIL") {
                        $credentials = base64_encode('api:'.config('services.mailgun.secret'));
                        $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
                        $response = $client->request('POST',  $domain,[
                            'headers' => [
                                'Authorization' => 'Basic '.$credentials
                            ],
                            'form_params' => [
                                'from' => 'devsaku5@gmail.com',
                                'to' => $row->email,
                                'subject' => 'Jurnal (ESAKU)',
                                'html' => $row->pesan
                            ]
                        ]);
                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);
                            if(isset($data["id"])){
                                $success['data2'] = $data;
                                DB::connection($this->db)->update("update pooling set tgl_kirim=getdate(),flag_kirim=1 where flag_kirim=0 and no_pool ='$request->no_pooling' and jenis='EMAIL'
                                ");
                                DB::connection($this->db)->commit();
                                $sts = true;
                                $msg .= $data['message'];
                            }
                        }
                    }
                    
                }

                $success['message'] = $msg;
                $success['status'] = $sts;
            }else{
                $success['message'] = "Data pooling tidak valid";
                $success['status'] = false;
            }
            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            
			DB::connection($this->db)->rollback();
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json($data, 500);
        }
    }
}

