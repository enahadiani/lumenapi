<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class SPBController extends Controller
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
            $no_bukti = $this->generateKode("spb_m", "no_spb", $kode_lokasi."-SPB".substr($periode,2,4).".", "0001");

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

            $sql = "select a.no_spb,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.nilai 
            from spb_m a 					 					 
            where a.kode_lokasi='".$kode_lokasi."' and a.progress ='0'";

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

    public function getPBList(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_pb) && $request->no_pb != ""){
                $filter = " and no_pb='$request->no_pb' ";
                $status = "SPB";
            }else{
                $filter = "";
                $status = "INPROG";
            }

            $sql = "select $status as status,no_pb,convert(varchar,tanggal,103) as tgl,keterangan,nilai 
            from pbh_pb_m 
            where progress='1' and no_spb='-' and kode_lokasi='".$kode_lokasi."' and modul not in ('IFCLOSE','PJPTG') $filter ";

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

    public function getPBTambah(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_pb,keterangan 
            from pbh_pb_m 
            where progress='1' and no_spb='-' and kode_lokasi='".$kode_lokasi."'";

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

    public function getRekTransfer(Request $request)
    {
        $this->validate($request,[
            'no_pb' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $this_in = "";
            $tmp = explode(",",$request->input('no_pb'));
            for($x=0;$x<count($tmp);$x++){
                if($x == 0){
                    $this_in .= "'".$tmp[$x]."'";
                }else{
                    
                    $this_in .= ","."'".$tmp[$x]."'";
                }
            }

            $where = "where a.kode_lokasi='$kode_lokasi' and b.no_pb in ($this_in) ";

            $sql = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,a.nilai, case when a.modul='PINBUK-C' then 'SUMBER' else 'TUJUAN' end jenis 
            from pbh_rek a inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi 
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
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'nik_fiat' => 'required',
            'nik_bdh' => 'required',
            'total' => 'required',
            'status' => 'required|array',
            'no_pb' => 'required|array',
            'nilai_pb' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("spb_m", "no_spb", $kode_lokasi."-SPB".substr($periode,2,4).".", "0001");

            $ins = DB::connection($this->db)->insert("insert into spb_m (no_spb,no_dokumen,no_ver,no_bukti,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nik_buat,nik_sah,nik_fiat,nik_bdh,lok_bayar,no_kas,nilai,modul,no_fiat,progress,kode_ppasal) values (?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,'-','-',$kode_lokasi,$periode,$nik,$request->tanggal,$request->tanggal,$request->deskripsi,$nik,'-',$request->nik_fiat,$request->nik_bdh,$kode_lokasi,'-',floatval($request->total),'SPB','-','0','-'));

            $nilai_pb=0;
            for ($i=0; $i < count($request->no_pb);$i++){
                if ($request->status[$i] == "SPB"){							
                    $upd = DB::connection($this->db)->table("pbh_pb_m")
                    ->where('no_pb',$request->no_pb[$i])
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress'=>'2', 'no_spb'=>$no_bukti]); 

                    $upd = DB::connection($this->db)->insert("insert into spb_j (no_spb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs)  
                    select ?,no_pb,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,?,?,getdate(),'IDR',1 
                    from pbh_pb_j where no_pb=? and jenis in ('BEBAN','HUTIF','PJ','TAK','TUJUAN') and kode_lokasi=? ",array($no_bukti,$request->tanggal,$periode,$nik,$request->no_pb[$i],$kode_lokasi));		
                    $nilai_pb+=floatval($request->nilai_pb[$i]);					
                }
            }

            if(floatval($request->total) <= 0){
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = '-';
                $success['message'] = "Data SPB tidak valid. Total SPB tidak boleh 0 atau kurang";
            }else if(floatval($request->total) != $nilai_pb){
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = '-';
                $success['message'] = "Data SPB tidak valid. Total SPB tidak sama dengan Total detail SPB";
            }else{
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data SPB berhasil disimpan";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SPB gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'nik_fiat' => 'required',
            'nik_bdh' => 'required',
            'total' => 'required',
            'status' => 'required|array',
            'no_pb' => 'required|array',
            'nilai_pb' => 'required|array'
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

            $del = DB::connection($this->db)->table("spb_m")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $del = DB::connection($this->db)->table("spb_j")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $del = DB::connection($this->db)->table("pbh_pb_m")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->update(['progress'=>'1', 'no_spb'=>'-']);

            $ins = DB::connection($this->db)->insert("insert into spb_m (no_spb,no_dokumen,no_ver,no_bukti,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nik_buat,nik_sah,nik_fiat,nik_bdh,lok_bayar,no_kas,nilai,modul,no_fiat,progress,kode_ppasal) values (?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$request->no_dokumen,'-','-',$kode_lokasi,$periode,$nik,$request->tanggal,$request->tanggal,$request->deskripsi,$nik,'-',$request->nik_fiat,$request->nik_bdh,$kode_lokasi,'-',floatval($request->total),'SPB','-','0','-'));

            $nilai_pb=0;
            for ($i=0;$i < count($request->no_pb);$i++){
                if ($request->status[$i] == "SPB"){							
                    $upd = DB::connection($this->db)->table("pbh_pb_m")
                    ->where('no_pb',$request->no_pb[$i])
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress'=>'2', 'no_spb'=>$no_bukti]); 

                    $upd = DB::connection($this->db)->insert("insert into spb_j (no_spb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs)  
                    select ?,no_pb,?,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,?,?,getdate(),'IDR',1 
                    from pbh_pb_j where no_pb=? and jenis in ('BEBAN','HUTIF','PJ','TAK','TUJUAN') and kode_lokasi=? ",array($no_bukti,$request->tanggal,$periode,$nik,$request->no_pb[$i],$kode_lokasi));		
                    $nilai_pb+=floatval($request->nilai_pb[$i]);					
                }
            }

            if(floatval($request->total) <= 0){
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = '-';
                $success['message'] = "Data SPB tidak valid. Total SPB tidak boleh 0 atau kurang";
            }else if(floatval($request->total) != $nilai_pb){
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = '-';
                $success['message'] = "Data SPB tidak valid. Total SPB tidak sama dengan Total detail SPB";
            }else{
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data SPB berhasil diubah";
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SPB gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

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
            
            $del = DB::connection($this->db)->table("spb_m")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $del = DB::connection($this->db)->table("spb_j")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->delete();

            $del = DB::connection($this->db)->table("pbh_pb_m")
            ->where('no_spb',$no_bukti) 
            ->where('kode_lokasi',$kode_lokasi)
            ->update(['progress'=>'1', 'no_spb'=>'-']);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data SPB berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data SPB gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


    public function show(Request $request)
    {
        $this->validate($request,[
            'no_spb' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // data main
           
           $sql = "select * from spb_m where no_spb = '".$request->no_spb."' and kode_lokasi='".$kode_lokasi."'";
           
           $rs = DB::connection($this->db)->select($sql);
           $res = json_decode(json_encode($rs),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $strSQL2 = "
                select 'SPB' as status,b.no_pb,convert(varchar,b.tanggal,103) as tgl,b.keterangan,b.nilai 
					from pbh_pb_m b 
					where b.progress='2' and b.no_spb='".$request->no_spb."' and b.kode_lokasi='".$kode_lokasi."' and modul not in ('IFCLOSE','PJPTG')
                ";
                $rs2 = DB::connection($this->db)->select($strSQL2);
                $res2 = json_decode(json_encode($rs2),true);
    
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

    public function getAkun(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun,a.nama from masakun a where a.block= '0' and a.kode_lokasi = '".$kode_lokasi."' ";

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

    public function getNIKBdh(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $nik_default = "-";
            $data = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('NIKBDH') and kode_lokasi = '".$kode_lokasi."'");
            if(count($data) > 0){
                $line = $data[0];							
                if ($line->flag != "") $nik_default = $line->flag; 
            }
            $success['nik_default'] = $nik_default;
            $strSQL="select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='".$kode_lokasi."' ";

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

    public function getNIKFiat(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $nik_default = "-";
            $data = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('NIKFIA') and kode_lokasi = '".$kode_lokasi."'");
            if(count($data) > 0){
                $line = $data[0];							
                if ($line->flag != "") $nik_default = $line->flag; 
            }
            $success['nik_default'] = $nik_default;
            $strSQL="select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='".$kode_lokasi."' ";

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
