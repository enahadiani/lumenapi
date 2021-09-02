<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class VerDokController extends Controller
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
            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-VDK".substr($periode,2,4).".", "0001");

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

            if(isset($request->no_pb) && $request->no_pb != ""){
                $sql = "select a.due_date,a.no_pb as no_bukti,case a.progress when 'D' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_verdok as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                      inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                where a.progress in ('D','R') and a.no_pb='".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU', 'PBBMHD', 'PBBA', 'PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG')  ";
            }else{

                $sql="select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,a.nik_user as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                        inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 				 
                where a.progress='S' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD','PBBA','PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') order by no_pb";
            }

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

    public function getPB(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_pb, keterangan, case progress when 'D' then 'APPROVE' else 'RETURN' end as status from pbh_pb_m where progress in ('D','R') and kode_lokasi='".$kode_lokasi."'";

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
            'no_pb' => 'required|max:20',
            'memo' => 'required|max:200',
            'modul' => 'required',
            'status' => 'required|in:APPROVE,RETURN',
            'status_dok' => 'required|array',
            'kode_dok' => 'required|array',
            'catatan_dok' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-VDK".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }

                if ($request->status == "APPROVE") {
                    $vStatus = "D";
                }else{
                    $vStatus = "R";						
                }
                
                $upd = DB::connection($this->db)->table("pbh_ver_m")
                ->where('no_bukti',$request->no_pb)
                ->where('no_flag','-')
                ->where('form','VERDOK')
                ->where('modul',$request->modul) 
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_flag'=>$no_bukti]);
                
                $ins = DB::connection($this->db)->insert("insert into pbh_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat) values (?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$request->tanggal,$periode,$nik,$vStatus,$request->modul,'VERDOK',$request->no_pb,$request->memo,'-','X','X'));
                    													
				$upd2 = DB::connection($this->db)->table('pbh_pb_m')
                ->where('no_pb',$request->no_pb)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_verdok'=>$no_bukti,'progress'=>$vStatus]);
                
                //dokumen			
                $check = false;			
                if (count($request->kode_dok) > 0){
                    for ($i=0; $i < count($request->kode_dok);$i++){
                        if($request->status_dok[$i] == "CHECK"){
                            $check = true;	
                        }
                        $ins = DB::connection($this->db)->insert("insert into pbh_verdok_d (no_ver,no_bukti,kode_lokasi,kode_dok,status,catatan) values (?, ?, ?, ?, ?, ?) ",array($no_bukti,$request->no_pb,$kode_lokasi,$request->kode_dok[$i],$request->status_dok[$i],$request->catatan_dok[$i]));
                    }
                }

                if(!$check){
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = '-';
                    $success['message'] = "Data Verifikasi Dokumen gagal disimpan. Transaksi tidak valid. Tidak ada status CHECK dokumen.";
                }else{

                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['no_bukti'] = $no_bukti;
                    $success['message'] = "Data Verifikasi Dokumen berhasil disimpan";
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
            $success['message'] = "Data Verifikasi Dokumen gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_ver' => 'required',
            'no_pb' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_ver;			

            $del = DB::connection($this->db)->table('pbh_ver_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ver', $no_bukti)
            ->delete();
            $del2 = DB::connection($this->db)->table('pbh_verdok_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_ver', $no_bukti)
            ->delete();
            
            $upd = DB::connection($this->db)->table('pbh_pb_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $request->no_pb)
            ->update(['no_verdok'=>'-','progress'=>'S']);

            $upd2 = DB::connection($this->db)->table('panjar_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pj', $request->no_pb)
            ->updatea(['progress','S']);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akru Simpanan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akru Simpanan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


    public function show(Request $request)
    {
        $this->validate($request,[
            'no_pb' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // data main
            if(isset($request->no_ver) && $request->no_ver != "-"){
                $sql = "select a.due_date,a.no_pb as no_bukti,case a.progress when 'D' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_verdok as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                      inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                where a.progress in ('D','R') and a.no_pb='".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU', 'PBBMHD', 'PBBA', 'PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG')  ";
            }else{

                $sql="select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,a.nik_user as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                        inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 				 
                where a.progress='S' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD','PBBA','PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') 
                and a.no_pb='".$request->no_pb."'
                order by no_pb";
            }
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $modul = $res[0]['modul'];
                $status = $res[0]['status'];
                $no_verlama = $res[0]['no_ver1'];
                
                // data jurnal
                if ($modul == "PBBAU" || $modul == "PBBMHD" || $modul == "PBADK" || $modul == "PBBA") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                        from pbh_pb_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                        where a.no_pb = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "IFREIM" || $modul == "IFCLOSE") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                        from hutang_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                        where a.jenis = 'BEBAN' and a.no_hutang = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJAJU") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                    from panjar_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi  
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                        where a.jenis = 'BEBAN' and a.no_pj = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJPTG") {			  
                    $strSQL2 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                    from ptg_j a 
                        inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 
                        where a.jenis = 'BEBAN' and a.no_ptg = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
                $rs2 = DB::connection($this->db)->select($strSQL2);
                $res2 = json_decode(json_encode($rs2),true);
    
                $strSQL3 = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,b.keterangan 
                from pbh_rek a
                inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti ='".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."'";
                $rs3 = DB::connection($this->db)->select($strSQL3);
                $res3 = json_decode(json_encode($rs3),true);
    
                $strSQL4 = "select *,saldo-nilai as sakhir from angg_r where no_bukti ='".$request->no_pb."' and kode_lokasi='".$kode_lokasi."'";
                $rs4 = DB::connection($this->db)->select($strSQL4);
                $res4 = json_decode(json_encode($rs4),true);
    
                $strSQL5 = "select distinct convert(varchar,tanggal,103) as tgl,tanggal 
                from pbh_ver_m 
                where no_bukti='".$request->no_pb."' and kode_lokasi='".$kode_lokasi."' 
                order by convert(varchar,tanggal,103) desc";
                $rs5 = DB::connection($this->db)->select($strSQL5);
                $res5 = json_decode(json_encode($rs5),true);
    
                $strSQL7="select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a 
                inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_ref = '".$request->no_pb."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
                $rs7 = DB::connection($this->db)->select($strSQL7);
                $res7 = json_decode(json_encode($rs7),true);
                $memo = "-";
                if($status != "INPROG") {
                
                    $sql = "select catatan from pbh_ver_m where no_ver ='".$no_verlama."' and kode_lokasi='".$kode_lokasi."'";
                    $rs8 = DB::connection($this->db)->select($sql);
                    if (count($rs8) > 0){
                        $line = $rs8[0];	
                        $memo = $line->catatan;	
                    }
                    $strSQL9 = "select isnull(b.status,'UNCHECK') as status,isnull(b.catatan,'-') as catatan,a.kode_dok,a.nama 
                    from pbh_dok_ver a left join pbh_verdok_d b on a.kode_dok=b.kode_dok and a.kode_lokasi=b.kode_lokasi and b.no_ver='".$no_verlama."' 
                    where b.kode_lokasi='".$kode_lokasi."' order by a.idx";				
                }else{
                    $strSQL9 = "select 'UNCHECK' as status,kode_dok,nama from pbh_dok_ver where kode_lokasi='".$kode_lokasi."' order by idx";	
                }	

                $rs9 = DB::connection($this->db)->select($strSQL9);
                $res9 = json_decode(json_encode($rs9),true);
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res2;
                $success['detail_rek'] = $res3;
                $success['detail_gar'] = $res4;
                $success['detail_dok'] = $res7;
                $success['detail_dok_check'] = $res9;
                if(count($res5) > 0){
                    $i=0;
                    foreach($res5 as $row){
                        $sql = "select catatan,no_ver, convert(varchar,tanggal,103) as tgl,tanggal, convert(varchar,tgl_input,108) as jam,nik_user 
                        from pbh_ver_m 
                        where no_bukti='".$request->no_pb."' and tanggal='".$row['tanggal']."' and kode_lokasi='".$kode_lokasi."' 
                        order by tanggal desc,convert(varchar,tgl_input,108) desc ";
                        $rs6 = DB::connection($this->db)->select($sql);
                        $res5[$i]['detail'] = json_decode(json_encode($rs6),true);
                        $i++;
                    }
                }
                $success['detail_catatan'] = $res5;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['detail_rek'] = [];
                $success['detail_gar'] = [];
                $success['detail_dok'] = [];
                $success['detail_dok_check'] = [];
                $success['detail_catatan'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_jurnal'] = [];
            $success['detail_rek'] = [];
            $success['detail_gar'] = [];
            $success['detail_dok'] = [];
            $success['detail_dok_check'] = [];
            $success['detail_catatan'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


}
