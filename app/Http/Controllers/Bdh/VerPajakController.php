<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class VerPajakController extends Controller
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
            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-PJK".substr($periode,2,4).".", "0001");

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

            if(isset($request->no_aju) && $request->no_aju !=""){
                $sql = "select a.due_date,a.no_pb as no_bukti,case a.progress when 'P' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                      inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                where a.progress in ('P','K') and a.no_pb='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD', 'PBBA', 'PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') ";
            }else{

                $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,a.nik_user as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                where a.progress='D' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD','PBBA','PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') order by no_pb ";
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

            $sql="select no_pb, keterangan, case progress when 'P' then 'APPROVE' else 'RETURN' end as status from pbh_pb_m where progress in ('P','K') and kode_lokasi='".$kode_lokasi."'";

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
            'no_aju' => 'required|max:20',
            'kode_pp_aju' => 'required|max:10',
            'tgl_aju' => 'required|date_format:Y-m-d',
            'memo' => 'required|max:200',
            'total' => 'required',
            'no_dokumen' => 'required',
            'modul' => 'required',
            'status' => 'required|in:APPROVE,RETURN',
            'nik_buat' => 'required|max:20',
            'atensi' => 'required|array',
            'bank' => 'required|array',
            'nama_rek' => 'required|array',
            'no_rek' => 'required|array',
            'bruto' => 'required|array',
            'potongan' => 'required|array',
            'netto' => 'required|array',
            'kode_akun' => 'required|array',
            'dc' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'kode_akun_pajak' => 'required|array',
            'dc_pajak' => 'required|array',
            'keterangan_pajak' => 'required|array',
            'nilai_pajak' => 'required|array',
            'kode_pp_pajak' => 'required|array',
            'kode_drk_pajak' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-PJK".substr($periode,2,4).".", "0001");

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
                    $vStatus = "P";
                }else{
                    $vStatus = "K";						
                }
                
                $upd = DB::connection($this->db)->table('pbh_ver_m')
                ->where('no_bukti',$request->no_aju)
                ->where('no_flag','-')
                ->where('form','VERPJK')
                ->where('modul',$request->modul)
                ->where('kode_lokasi',$kode_lokasi)		
                ->update(['no_flag'=>$no_bukti]);
                
                $ins = DB::connection($this->db)->insert("insert into pbh_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat) values (?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($no_bukti,$kode_lokasi,$request->tanggal,$periode,$nik,$vStatus,$request->modul,'VERPJK',$request->no_aju,$request->memo,'-','X','X'));
                    													
                $upd = DB::connection($this->db)->table('pbh_pb_m')
                ->where('no_pb',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_pajak'=>$no_bukti,'progress'=>$vStatus,'nilai'=>floatval($request->total),'nilai_final'=>floatval($request->total)]);

                $del = DB::connection($this->db)->table('pbh_pb_j') 
                ->where('no_pb',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $del2 = DB::connection($this->db)->table('angg_r') 
                ->where('no_bukti',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $del3 = DB::connection($this->db)->table('pbh_rek') 
                ->where('no_bukti',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();
                
                $del4 = DB::connection($this->db)->table('pbh_dok') 
                ->where('no_bukti',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();
				//------------------------------------------------------------------------------------------------------------------------------------------
                if ($request->modul == "PBBAU" || $request->modul == "PBBMHD" || $request->modul == "PBADK" || $request->modul == "PBBA") {
                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){
                            $ins2[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)", array($request->no_aju,$request->no_dokumen,$request->tgl_aju,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,$request->modul,'BEBAN',$periode,$nik,'IDR',1));		
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)", array($request->no_aju,$request->no_dokumen,$request->tgl_aju,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,$request->modul,'PAJAK',$periode,$nik,'IDR',1));		
                        }
                    }
                }
                
                //update transaksi
                $periodeAju = substr($request->tgl_aju,0,4).substr($request->tgl_aju,5,2);
                if ($periodeAju != $periode) {
                    $periodeInput = $periode;
                    $tglInput = $request->tanggal;
                }
                else {
                    $periodeInput = $periodeAju;
                    $tglInput = $request->tgl_aju;
                }

                //modul IFREIM					
                if ($request->modul == "IFREIM") {
                    $upd2 = DB::connection($this->db)->table('hutang_m')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['tanggal'=>$tglInput,'posted'=>'F','periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('hutang_j') 
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                   if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],'IDR',1,floatval($request->nilai[$i]),floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'IFREIM','BEBAN',$periodeInput,$nik));
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){	
                            $ins4[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],'IDR',1,floatval($request->nilai_pajak[$i]),floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'IFREIM','PAJAK',$periodeInput,$nik));	
                        }
                    }

                    $upd3 = DB::connection($this->db)->table('hutang_j')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->where('jenis','HUT')
                    ->update(['tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai_curr'=>floatval($request->total),'nilai'=>floatval($request->total)]);

                    $ins5 = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) 
                        select no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,'D',nilai,kode_pp,kode_drk,kode_lokasi,'IFREIM','HUTIF',periode,nik_user,tgl_input,kode_curr,kurs
                        from hutang_j 
                        where no_hutang='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."' and jenis='HUT'");					
                }
                
                if ($request->modul == "IFCLOSE") {
                    $upd2 = DB::connection($this->db)->table('hutang_m')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('hutang_j') 
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())", array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],'IDR',1,floatval($request->nilai[$i]),floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'IFREIM','BEBAN',$periodeInput,$nik));					
                        }
                    }	

                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],'IDR',1,floatval($request->nilai_pajak[$i]),floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'IFREIM','PAJAK',$periodeInput,$nik));	
                        }
                    }					
                }

                //pjaju
                if ($request->modul == "PJAJU") {
                    $upd2 = DB::connection($this->db)->table('panjar_m')
                    ->where('no_pj',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress'=>$vStatus,'tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('panjar_j') 
                    ->where('no_pj',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into panjar_j(no_pj, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate()) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PJAJU','BEBAN',$periodeInput,'IDR',1,$nik));
                        }
                    }

                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into panjar_j(no_pj, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate()) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'PJAJU','PAJAK',$periodeInput,'IDR',1,$nik));	
                        }
                    }

                    $ins4[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) 
                        select no_pj,no_dokumen,'".$tglInput."',0,akun_pj,keterangan,'D',nilai,kode_pp,'-',kode_lokasi,'PJAJU','PJ','".$periodeInput."','".$nik."',getdate(),'IDR',1 
                        from panjar_m where no_pj='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."'");					
                }

                //ptg panjar
                if ($request->modul == "PJPTG") {

                    $upd2 = DB::connection($this->db)->table('ptg_m')
                    ->where('no_ptg',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress'=>$vStatus,'tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('ptg_j') 
                    ->where('no_ptg',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into ptg_j(no_ptg, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PTGPJ','BEBAN',$periodeInput,'IDR',1,$nik,'-'));
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into ptg_j(no_ptg, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'PTGPJ','PAJAK',$periodeInput,'IDR',1,$nik,'-'));			
                        }
                    }
                }


                //------------------------------------------------------------------------------------------------------------------------------------------
                //rekening dan budget
                if (count($request->atensi) > 0){
                    $netto = 0;
                    for ($i=0;$i < count($request->atensi);$i++){
                        $netto = floatval($request->bruto[$i]) - floatval($request->potongan[$i]);
                        $ins6 = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array($request->no_aju,$kode_lokasi,$request->modul,$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],$request->atensi[$i],floatval($request->bruto[$i]),floatval($request->potongan[$i]),$netto));
                    }
                }
                
                //dokumen						
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
                        
                        $deldok = DB::connection($this->db)->table('pbh_dok') 
                        ->where('no_bukti',$request->no_aju)
                        ->where('kode_lokasi',$kode_lokasi)	
                        ->delete();
                    }
                }
                
                if(count($arr_dok) > 0){

                    for ($i=0; $i < count($arr_dok);$i++){						
                        $insdok[] = DB::connection($this->db)->insert("insert into pbh_dok(no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?)",array($request->no_aju,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'VERPB',$request->no_aju));
                    }	
                }

                $insn = DB::connection($this->db)->insert("insert into api_notif (nik,tgl_notif,title,pesan,kode_lokasi,modul,status,kode_pp,no_bukti) values (?, getdate(), ?, ?, ?, ?, ?, ?, ?)",array($request->nik_buat,'VERIFIKASI PB',$request->memo,$kode_lokasi,'VERPB',$vStatus,$request->kode_pp_aju,$request->no_aju));	
            
				//modul PB-cashbasis
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Verifikasi Pajak berhasil disimpan";

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
            $success['message'] = "Data Verifikasi Pajak gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'no_aju' => 'required|max:20',
            'kode_pp_aju' => 'required|max:10',
            'tgl_aju' => 'required|date_format:Y-m-d',
            'memo' => 'required|max:200',
            'total' => 'required',
            'no_dokumen' => 'required',
            'modul' => 'required',
            'status' => 'required|in:APPROVE,RETURN',
            'nik_buat' => 'required|max:20',
            'atensi' => 'required|array',
            'bank' => 'required|array',
            'nama_rek' => 'required|array',
            'no_rek' => 'required|array',
            'bruto' => 'required|array',
            'potongan' => 'required|array',
            'netto' => 'required|array',
            'kode_akun' => 'required|array',
            'dc' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'kode_akun_pajak' => 'required|array',
            'dc_pajak' => 'required|array',
            'keterangan_pajak' => 'required|array',
            'nilai_pajak' => 'required|array',
            'kode_pp_pajak' => 'required|array',
            'kode_drk_pajak' => 'required|array',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $no_bukti = $this->generateKode("pbh_ver_m", "no_ver", $kode_lokasi."-PJK".substr($periode,2,4).".", "0001");

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
                    $vStatus = "P";
                }else{
                    $vStatus = "K";						
                }
                
                $upd = DB::connection($this->db)->table('pbh_ver_m')
                ->where('no_bukti',$request->no_aju)
                ->where('no_flag','-')
                ->where('form','VERPJK')
                ->where('modul',$request->modul)
                ->where('kode_lokasi',$kode_lokasi)		
                ->update(['no_flag'=>$no_bukti]);
                
                $ins = DB::connection($this->db)->insert("insert into pbh_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat) values (?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($no_bukti,$kode_lokasi,$request->tanggal,$periode,$nik,$vStatus,$request->modul,'VERPJK',$request->no_aju,$request->memo,'-','X','X'));
                    													
                $upd = DB::connection($this->db)->table('pbh_pb_m')
                ->where('no_pb',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_pajak'=>$no_bukti,'progress'=>$vStatus,'nilai'=>floatval($request->total),'nilai_final'=>floatval($request->total)]);

                $del = DB::connection($this->db)->table('pbh_pb_j') 
                ->where('no_pb',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $del2 = DB::connection($this->db)->table('angg_r') 
                ->where('no_bukti',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();

                $del3 = DB::connection($this->db)->table('pbh_rek') 
                ->where('no_bukti',$request->no_aju)
                ->where('kode_lokasi',$kode_lokasi)
                ->delete();
				//------------------------------------------------------------------------------------------------------------------------------------------
                if ($request->modul == "PBBAU" || $request->modul == "PBBMHD" || $request->modul == "PBADK" || $request->modul == "PBBA") {
                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){
                            $ins2[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)", array($request->no_aju,$request->no_dokumen,$request->tgl_aju,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,$request->modul,'BEBAN',$periode,$nik,'IDR',1));		
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)", array($request->no_aju,$request->no_dokumen,$request->tgl_aju,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,$request->modul,'PAJAK',$periode,$nik,'IDR',1));		
                        }
                    }
                }
                
                //update transaksi
                $periodeAju = substr($request->tgl_aju,0,4).substr($request->tgl_aju,5,2);
                if ($periodeAju != $periode) {
                    $periodeInput = $periode;
                    $tglInput = $request->tanggal;
                }
                else {
                    $periodeInput = $periodeAju;
                    $tglInput = $request->tgl_aju;
                }

                //modul IFREIM					
                if ($request->modul == "IFREIM") {
                    $upd2 = DB::connection($this->db)->table('hutang_m')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['tanggal'=>$tglInput,'posted'=>'F','periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('hutang_j') 
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                   if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],'IDR',1,floatval($request->nilai[$i]),floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'IFREIM','BEBAN',$periodeInput,$nik));
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){	
                            $ins4[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],'IDR',1,floatval($request->nilai_pajak[$i]),floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'IFREIM','PAJAK',$periodeInput,$nik));	
                        }
                    }

                    $upd3 = DB::connection($this->db)->table('hutang_j')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->where('jenis','HUT')
                    ->update(['tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai_curr'=>floatval($request->total),'nilai'=>floatval($request->total)]);

                    $ins5 = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) 
                        select no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,'D',nilai,kode_pp,kode_drk,kode_lokasi,'IFREIM','HUTIF',periode,nik_user,tgl_input,kode_curr,kurs
                        from hutang_j 
                        where no_hutang='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."' and jenis='HUT'");					
                }
                
                if ($request->modul == "IFCLOSE") {
                    $upd2 = DB::connection($this->db)->table('hutang_m')
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('hutang_j') 
                    ->where('no_hutang',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())", array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],'IDR',1,floatval($request->nilai[$i]),floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'IFREIM','BEBAN',$periodeInput,$nik));					
                        }
                    }	

                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into hutang_j(no_hutang,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,kode_curr,kurs,nilai_curr,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],'IDR',1,floatval($request->nilai_pajak[$i]),floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'IFREIM','PAJAK',$periodeInput,$nik));	
                        }
                    }					
                }

                //pjaju
                if ($request->modul == "PJAJU") {
                    $upd2 = DB::connection($this->db)->table('panjar_m')
                    ->where('no_pj',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress' => $vStatus,'tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('panjar_j') 
                    ->where('no_pj',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into panjar_j(no_pj, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate()) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PJAJU','BEBAN',$periodeInput,'IDR',1,$nik));
                        }
                    }

                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into panjar_j(no_pj, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate()) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'PJAJU','PAJAK',$periodeInput,'IDR',1,$nik));	
                        }
                    }

                    $ins4[$i] = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) 
                        select no_pj,no_dokumen,'".$tglInput."',0,akun_pj,keterangan,'D',nilai,kode_pp,'-',kode_lokasi,'PJAJU','PJ','".$periodeInput."','".$nik."',getdate(),'IDR',1 
                        from panjar_m where no_pj='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."'");					
                }

                //ptg panjar
                if ($request->modul == "PJPTG") {

                    $upd2 = DB::connection($this->db)->table('ptg_m')
                    ->where('no_ptg',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update(['progress' => $vStatus,'tanggal'=>$tglInput,'periode'=>$periodeInput,'nilai'=>floatval($request->total)]);

                    $del5 = DB::connection($this->db)->table('ptg_j') 
                    ->where('no_ptg',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->whereIn('jenis',array('BEBAN','PAJAK'))
                    ->delete();
                    
                    $del6 = DB::connection($this->db)->table('angg_r') 
                    ->where('no_bukti',$request->no_aju)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->delete();

                    if (count($request->kode_akun) > 0){
                        for ($i=0; $i < count($request->kode_akun);$i++){								
                            $ins3[$i] = DB::connection($this->db)->insert("insert into ptg_j(no_ptg, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun[$i],$request->keterangan[$i],$request->dc[$i],floatval($request->nilai[$i]),$request->kode_pp[$i],$request->kode_drk[$i],$kode_lokasi,'PTGPJ','BEBAN',$periodeInput,'IDR',1,$nik,'-'));
                        }
                    }
                    if (count($request->kode_akun_pajak) > 0){
                        for ($i=0; $i < count($request->kode_akun_pajak);$i++){								
                            $ins4[$i] = DB::connection($this->db)->insert("insert into ptg_j(no_ptg, no_dokumen, tanggal, no_urut, kode_akun, keterangan, dc, nilai, kode_pp, kode_drk, kode_lokasi, modul, jenis, periode, kode_curr, kurs, nik_user, tgl_input, no_link) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($request->no_aju,'-',$tglInput,$i,$request->kode_akun_pajak[$i],$request->keterangan_pajak[$i],$request->dc_pajak[$i],'IDR',1,floatval($request->nilai_pajak[$i]),$request->kode_pp_pajak[$i],$request->kode_drk_pajak[$i],$kode_lokasi,'PTGPJ','PAJAK',$periodeInput,'IDR',1,$nik,'-'));			
                        }
                    }
                }


                //------------------------------------------------------------------------------------------------------------------------------------------
                //rekening dan budget
                if (count($request->atensi) > 0){
                    $netto = 0;
                    for ($i=0;$i < count($request->atensi);$i++){
                        $netto = floatval($request->bruto[$i]) - floatval($request->potongan[$i]);
                        $ins6 = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array($request->no_aju,$kode_lokasi,$request->modul,$request->nama_rek[$i],$request->no_rek[$i],$request->bank[$i],$request->atensi[$i],floatval($request->bruto[$i]),floatval($request->potongan[$i]),$netto));
                    }
                }
                
                //dokumen						
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
                        
                        $deldok = DB::connection($this->db)->table('pbh_dok') 
                        ->where('no_bukti',$request->no_aju)
                        ->where('kode_lokasi',$kode_lokasi)	
                        ->delete();
                    }
                }
                
                if(count($arr_dok) > 0){

                    for ($i=0; $i < count($arr_dok);$i++){						
                        $insdok[] = DB::connection($this->db)->insert("insert into pbh_dok(no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?)",array($request->no_aju,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'VERPB',$request->no_aju));
                    }	
                }

                $insn = DB::connection($this->db)->insert("insert into api_notif (nik,tgl_notif,title,pesan,kode_lokasi,modul,status,kode_pp,no_bukti) values (?, getdate(), ?, ?, ?, ?, ?, ?, ?)",array($request->nik_buat,'VERIFIKASI PB',$request->memo,$kode_lokasi,'VERPB',$vStatus,$request->kode_pp_aju,$request->no_aju));	
            
				//modul PB-cashbasis
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Verifikasi Pajak berhasil diubah";

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
            $success['message'] = "Data Verifikasi Pajak gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_ver' => 'required',
            'no_aju' => 'required'
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
            
            $upd = DB::connection($this->db)->table('pbh_pb_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $request->no_aju)
            ->update(['no_pajak'=>'-','progress'=>'D']);

            $upd2 = DB::connection($this->db)->table('panjar_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pj', $request->no_aju)
            ->update(['progress' => 'D']);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Verifikasi Pajak berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Verifikasi Pajak gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


    public function show(Request $request)
    {
        $this->validate($request,[
            'no_aju' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // data main
            if(isset($request->no_ver) && $request->no_ver != "-"){
                $sql = "select a.due_date,a.no_pb as no_bukti,case a.progress when 'P' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                		inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                      inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                where a.progress in ('P','K') and a.no_pb='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD', 'PBBA', 'PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') and a.no_ver='$request->no_ver' ";
            }else{

                $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,a.nik_user as pembuat,a.no_ver as no_ver1,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from pbh_pb_m a 
                inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                where a.progress='D' and a.no_pb='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' and a.modul in ('PBBAU','PBBMHD','PBBA','PBADK','IFREIM','IFCLOSE','PJAJU','PJPTG') order by no_pb ";
            }
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $modul = $res[0]['modul'];
                $status = $res[0]['status'];
                $no_verlama = $res[0]['no_ver1'];
                
                // data jurnal
                if ($modul == "PBBAU" || $modul == "PBBMHD" || $modul == "PBADK" || $modul == "PBBA") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from pbh_pb_j a 
                                	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                     left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 											  
                                where a.jenis in ('BEBAN','BMHD','HUTANG') and a.no_pb = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                    $strSQL4 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from pbh_pb_j a 
                                	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                     left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 											  
                                where a.jenis = 'PAJAK' and a.no_pb = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "IFREIM" || $modul == "IFCLOSE") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                               from hutang_j a 
                               	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 																															  
                               where a.jenis = 'BEBAN' and a.no_hutang = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                    $strSQL4 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from hutang_j a 
                                	inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 											
                                where a.jenis = 'PAJAK' and a.no_hutang = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJAJU") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from panjar_j a 
                                	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                     left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 																															  
                                where a.jenis = 'BEBAN' and a.no_pj = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                    $strSQL4 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                               from panjar_j a 
                               	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 																															  
                               where a.jenis = 'PAJAK' and a.no_pj = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
        
                if ($modul == "PJPTG") {			  
                    $strSQL3 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from ptg_j a 
                                	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                     left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 																															  
                                where a.jenis = 'BEBAN' and a.no_ptg = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                    $strSQL4 = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk 
                                from ptg_j a 
                                	  inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                                     inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 	
                                     left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and substring(a.periode,1,4)=d.tahun 											  
                                where a.jenis = 'PAJAK' and a.no_ptg = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";		
                }
                $rs3 = DB::connection($this->db)->select($strSQL3);
                $res3 = json_decode(json_encode($rs3),true);

                $rs4 = DB::connection($this->db)->select($strSQL4);
                $res4 = json_decode(json_encode($rs4),true);
    
                $strSQL5 = "select a.bank,a.nama,a.no_rek,a.nama_rek,a.bruto,a.pajak,b.keterangan 
                from pbh_rek a
                inner join pbh_pb_m b on a.no_bukti=b.no_pb and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti ='".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."'";
                $rs5 = DB::connection($this->db)->select($strSQL5);
                $res5 = json_decode(json_encode($rs5),true);
    
                $strSQL6 = "select distinct convert(varchar,tgl_input,103) as tgl
                from pbh_ver_m 
                where no_bukti='".$request->no_aju."' and kode_lokasi='".$kode_lokasi."' 
                order by convert(varchar,tgl_input,103) desc";
                $rs6 = DB::connection($this->db)->select($strSQL6);
                $res6 = json_decode(json_encode($rs6),true);
    
                $strSQL7="select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a 
                inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_ref = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
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
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_jurnal'] = $res3;
                $success['detail_pajak'] = $res4;
                $success['detail_rek'] = $res5;
                $success['detail_dok'] = $res7;
                if(count($res6) > 0){
                    $i=0;
                    foreach($res6 as $row){
                        $sql = "select catatan,no_ver, convert(varchar,tgl_input,103) as tgl,convert(varchar,tgl_input,108) as jam,nik_user 
                        from pbh_ver_m 
                        where no_bukti='".$request->no_aju."' and convert(varchar,tgl_input,103)='".$row['tgl']."' and kode_lokasi='".$kode_lokasi."' 
                        order by convert(varchar,tgl_input,103) desc,convert(varchar,tgl_input,108) desc ";
                        $rs6 = DB::connection($this->db)->select($sql);
                        $res6[$i]['detail'] = json_decode(json_encode($rs6),true);
                        $i++;
                    }
                }
                $success['detail_catatan'] = $res6;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_jurnal'] = [];
                $success['detail_pajak'] = [];
                $success['detail_rek'] = [];
                $success['detail_dok'] = [];
                $success['detail_catatan'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_jurnal'] = [];
            $success['detail_pajak'] = [];
            $success['detail_rek'] = [];
            $success['detail_dok'] = [];
            $success['detail_catatan'] = [];
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

            $strSQL = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '063' where a.block= '0' and a.kode_lokasi = '".$kode_lokasi."' ";

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

    public function getAkunGar(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun,a.nama from masakun a where a.status_gar= '1' and a.kode_lokasi = '".$kode_lokasi."' ";

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

            $strSQL = "select a.kode_pp,a.nama from pp a where a.flag_aktif= '1' and a.kode_lokasi = '".$kode_lokasi."'";
            
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

    public function getJenisDokumen(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

           
            $strSQL = "select kode_jenis, nama  from dok_jenis where kode_lokasi='$kode_lokasi' ";
          
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
