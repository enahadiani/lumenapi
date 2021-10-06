<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PenutupanIFController extends Controller
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
            'tanggal' => 'required',
            'jenis' => 'required'      
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-".$request->jenis.substr($periode,2,4).".", "0001");

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

            $sql="select distinct a.no_kas,convert(varchar,a.tanggal,103) as tgl,a.no_dokumen,a.keterangan,a.nilai,a.tanggal 
            from kas_m a 			 					 
            where a.kode_lokasi='".$kode_lokasi."' and a.modul='KBIFCLOSE' and a.posted ='F' 
            order by a.tanggal";

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
            'jenis' => 'required',
            'deskripsi' => 'required|max:150',
            'no_hutang' => 'required',
            'nik_if'=>'required',
            'akun_kas' => 'required',
            'nilai_kas' => 'required',
            'total_reim' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            if(floatval($request->nilai_kas) == 0){
                $jenis = 'CL';
            }else{
                $jenis = 'BM';
            }

            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-".$jenis.substr($periode,2,4).".", "0001");

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
                
                $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
                if(count($get) > 0){
                    $kode_pp = $get[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $get2 = DB::connection($this->db)->select("select kode_pp from karyawan where nik='".$request->nik_if."' and kode_lokasi='".$kode_lokasi."'");
                if(count($get2) > 0){
                    $this_pp = $get2[0]->kode_pp;
                }else{
                    $this_pp = "-";
                }

                $upd = DB::connection($this->db)->table('hutang_m')
                ->where('modul','IFREIM')
                ->where('no_hutang',$request->no_hutang)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_ref'=>$no_bukti]); 

                $ins = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)",
                array($no_bukti,$kode_lokasi,$request->no_hutang,'-',$request->akun_kas,$request->tanggal,$request->deskripsi,$this_pp,'KBIFCLOSE',$jenis,$periode,'IDR',1,floatval($request->nilai_kas),$nik,$nik,$nik,'F','-','-','-','-'));

                if (floatval($request->nilai_kas) != 0) {
                    $insj = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?)",
                    array($no_bukti,'-',$request->tanggal,777,$request->akun_kas,$request->deskripsi,'D',floatval($request->nilai_kas),$kode_pp,'-','-','-',$kode_lokasi,'KBIFCLOSE','KB',$periode,'IDR',1,$nik,'-'));		
                }

                $insj2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank)  
                    select ?,no_hutang,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,'-','-',kode_lokasi,modul,jenis,?,kode_curr,kurs,?,getdate(),'-' 
                    from hutang_j where no_hutang=? and kode_lokasi=? ",[$no_bukti,$request->tanggal,$periode,$nik,$request->no_hutang,$kode_lokasi]);
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Penutupan Reimburse berhasil disimpan";

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
            $success['message'] = "Data Penutupan Reimburse gagal disimpan ".$e;
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
            'jenis' => 'required',
            'deskripsi' => 'required|max:150',
            'no_hutang' => 'required',
            'nik_if'=>'required',
            'akun_kas' => 'required',
            'nilai_kas' => 'required',
            'total_reim' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            
            $del = DB::connection($this->db)->table('kas_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_kas', $no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('kas_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_kas', $no_bukti)
            ->delete();

            $upd = DB::connection($this->db)->table('hutang_m')
                ->where('modul','IFREIM')
                ->where('no_ref',$no_bukti)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_ref'=>'-']); 

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
                $j = 0;
                $total = 0;
                $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
                if(count($get) > 0){
                    $kode_pp = $get[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                $get2 = DB::connection($this->db)->select("select kode_pp from karyawan where nik='".$request->nik_if."' and kode_lokasi='".$kode_lokasi."'");
                if(count($get2) > 0){
                    $this_pp = $get2[0]->kode_pp;
                }else{
                    $this_pp = "-";
                }

                $upd = DB::connection($this->db)->table('hutang_m')
                ->where('modul','IFREIM')
                ->where('no_hutang',$request->no_hutang)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_ref'=>$no_bukti]); 

                $ins = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)",
                array($no_bukti,$kode_lokasi,$request->no_hutang,'-',$request->akun_kas,$request->tanggal,$request->deskripsi,$this_pp,'KBIFCLOSE',$jenis,$periode,'IDR',1,floatval($request->nilai_kas),$nik,$nik,$nik,'F','-','-','-','-'));

                if (floatval($request->nilai_kas) != 0) {
                    $insj = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?)",
                    array($no_bukti,'-',$request->tanggal,777,$request->akun_kas,$request->deskripsi,'D',floatval($request->nilai_kas),$kode_pp,'-','-','-',$kode_lokasi,'KBIFCLOSE','KB',$periode,'IDR',1,$nik,'-'));		
                }

                $insj2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank)  
                    select ?,no_hutang,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,'-','-',kode_lokasi,modul,jenis,?,kode_curr,kurs,?,getdate(),'-' 
                    from hutang_j where no_hutang=? and kode_lokasi=? ",[$no_bukti,$request->tanggal,$periode,$nik,$request->no_hutang,$kode_lokasi]);
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Penutupan Reimburse berhasil diubah";

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
            $success['message'] = "Data Penutupan Reimburse gagal diubah ".$e;
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
            if(isset($request->periode) && $request->periode != ""){
                $periode = $request->periode;
            }else{
                $periode = date('Ym');
            }
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
            
                $del = DB::connection($this->db)->table('kas_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();
    
                $del2 = DB::connection($this->db)->table('kas_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();
    
                $upd = DB::connection($this->db)->table('hutang_m')
                    ->where('modul','IFREIM')
                    ->where('no_ref',$no_bukti)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['no_ref'=>'-']);     

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Penutupan Reimburse berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penutupan Reimburse gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function loadData(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required',
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select b.nik,b.akun_if,b.nilai,a.akun_hutang 
            from hutang_m a 
            inner join if_nik b on a.no_dokumen=b.no_kas and a.kode_lokasi=b.kode_lokasi 
            where a.no_hutang='".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."'";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            $sql = "select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
				from hutang_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 									
				where a.jenis='BEBAN' and a.no_hutang = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.no_urut";

            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail'] = [];
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

            $strSQL = "select a.tanggal,a.jenis,a.keterangan,a.akun_kb,b.no_hutang 
            from kas_m a 
            inner join hutang_m b on a.no_kas=b.no_ref and a.kode_lokasi=b.kode_lokasi 
            where a.no_kas = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' ";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $no_hutang = $res[0]['no_hutang'];
                
                $strSQL2 = "select no_hutang, keterangan from hutang_m where no_hutang='".$no_hutang."' and kode_lokasi='".$kode_lokasi."' and no_ref='".$request->no_bukti."'";
                $rs2 = DB::connection($this->db)->select($strSQL2);
                $res2 = json_decode(json_encode($rs2),true);

                $success['status'] = true;
                $success['data'] = $res;
                $success['data_reimburse'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_reimburse'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data_reimburse'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkunKas(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $rs = DB::connection($this->db)->select("select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag in ('001','009') 
            where a.kode_lokasi='$kode_lokasi' ");
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

    public function getReimburse(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $rs = DB::connection($this->db)->select("select no_hutang, keterangan 
            from hutang_m 
            where kode_project='CLOSE' and modul='IFREIM' and kode_lokasi='".$kode_lokasi."' and no_ref='-' ");
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
