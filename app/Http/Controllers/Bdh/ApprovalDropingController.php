<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApprovalDropingController extends Controller
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
            $no_bukti = $this->generateKode("ys_app_m", "no_app", $kode_lokasi."-APC".substr($periode,2,4).".", "0001");

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

            if(isset($request->no_aju) && $request->no_aju != ""){
                $sql = "select a.tanggal as due_date,a.no_minta as no_bukti,case a.progress when '1' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,'DROPING' as modul,b.kode_pp+' - '+b.nama as pp,a.no_dokumen,a.keterangan,isnull(d.nilai,0) as nilai,c.nik+' - '+c.nama as pembuat,a.no_app,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from ys_minta_m a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                left join (select a.no_minta,a.kode_lokasi,sum(a.nilai_usul) as nilai 
                    from ys_minta_d a 
                    where a.kode_lokasi='".$kode_lokasi."' 
                    group by a.no_minta,a.kode_lokasi  ) d 
                    on a.no_minta=d.no_minta and a.kode_lokasi=d.kode_lokasi 
                where a.no_minta='".$request->no_pb."'";
            }else{

                $sql="select a.tanggal as due_date,a.no_minta as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,'DROPING' as modul, 
			    b.kode_pp+' - '+b.nama as pp,a.no_dokumen,a.keterangan,isnull(d.nilai,0) as nilai,c.nik+' - '+c.nama as pembuat,a.no_app,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp,a.kode_lokasi 
			    from ys_minta_m a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                left join ( 
                    select a.no_minta,a.kode_lokasi,sum(a.nilai_usul) as nilai 
                    from ys_minta_d a 
                    group by a.no_minta,a.kode_lokasi 
                    ) d on a.no_minta=d.no_minta and a.kode_lokasi=d.kode_lokasi 
                where a.kode_lokasi='$kode_lokasi' and a.progress='0'";
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

    public function getAju(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select no_minta, keterangan from ys_minta_m where kode_lokasi='".$kode_lokasi."' and progress in ('1','C') ";

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
            'status' => 'required|in:APPROVE,RETURN',
            'catatan' => 'required|max:200',
            'no_aju' => 'required|max:20',
            'kode_pp_bukti' => 'required',
            'lokasi_asal' => 'required',
            'modul' => 'required',
            'total_approve' => 'required',
            'akun_mutasi' => 'required',
            'bank' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'nilai_app'=> 'required|array',
            'id'=> 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("ys_app_m", "no_app", $kode_lokasi."-APC".substr($periode,2,4).".", "0001");


            if(floatval($request->total_approve) <= 0){
                $msg = "Transaksi tidak valid. Total Approval tidak boleh nol atau kurang";
                $sts = false;
            }else{
                $getPP = DB::connection($this->db)->select("select kode_pp from karyawan where nik='$nik' and kode_lokasi='$kode_lokasi' ");
                if(count($getPP) > 0){
                    $kode_pp = $getPP[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }
    
                if ($request->status == "APPROVE") {
                    $vStatus = "1";
                }else{
                    $vStatus = "C";						
                }
                
                $upd = DB::connection($this->db)->table("ys_app_m")
                ->where('no_bukti',$request->no_aju)
                ->where('no_flag','-')
                ->where('form','APPCAB')
                ->where('modul',$request->modul) 
                ->where('kode_lokasi',$kode_lokasi)
                ->update(['no_flag'=>$no_bukti]);
                
                $ins = DB::connection($this->db)->insert("insert into ys_app_m (no_app,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat) values (?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$request->tanggal,$periode,$nik,$vStatus,$request->modul,'APPCAB',$request->no_aju,$request->catatan,'-','X','X'));
    
                //---------------- flag bukti					
                if ($request->modul == "DROPING") {
                    $ins = DB::connection($this->db)->table('ys_minta_m')
                    ->where('no_minta',$request->no_aju)
                    ->update(['no_app'=>$no_bukti,'progress'=>$vStatus]);
                    
                    for ($i=0;$i < count($request->nilai_app);$i++){
                        $updy = DB::connection($this->db)->table('ys_minta_d')
                        ->where('no_minta',$request->no_aju)
                        ->where('nu', $request->id[$i])
                        ->update([
                            'nilai_app'=>floatval($request->nilai_app[$i])
                        ]); 
                    }
                }
    
                //melengkapi norekening, hps data sebelumnya, pakai nobukti krn no approval bisa duplikasi (historikal)
                $del1 = DB::connection($this->db)->table('pbh_pb_m')
                ->where('no_pb',$request->no_aju) ->where('kode_lokasi',$kode_lokasi)
                ->delete();
    
                $del2 = DB::connection($this->db)->table('pbh_pb_j') 
                ->where('no_pb',$request->no_aju) ->where('kode_lokasi',$kode_lokasi)
                ->delete();
    
                $del3 = DB::connection($this->db)->table('pbh_rek')
                ->where('no_pb',$request->no_aju) ->where('kode_lokasi',$kode_lokasi)
                ->delete();
                
                $insm = DB::connection($this->db)->insert("insert into pbh_pb_m (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver) values (?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($request->no_aju,$no_bukti,$kode_lokasi,$periode,$nik,$request->tanggal,$request->tanggal,$request->catatan,floatval($request->total_approve),'PBMINTA','1',$kode_pp,$nik,$nik,'-','-','-','-',$request->kode_pp_bukti,$request->lokasi_asal,floatval($request->total_approve),'X','-','-','-','-','-',$request->akun_minta,$nik));
    
                $insj = DB::connection($this->db)->insert("insert into pbh_pb_j(no_pb,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_lokasi,modul,jenis,periode,nik_user,tgl_input,kode_curr,kurs) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?)",array($request->no_aju,$no_bukti,$request->tanggal,0,$request->akun_mutasi,$request->catatan,'D',floatval($request->total_approve),$kode_pp,'-',$kode_lokasi,'PBMINTA','TAK',$periode,$nik,'IDR',1));
    
                $insr = DB::connection($this->db)->insert("insert into pbh_rek(no_bukti,kode_lokasi,modul,nama_rek,no_rek,bank,nama,bruto,pajak,nilai) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($request->no_aju,$kode_lokasi,'PBMINTA',$request->nama_rek,$request->no_rek,$request->bank,$request->nama_rek,floatval($request->total_approve),0,floatval($request->total_approve)));

                $sts = true;
                $msg =  "Data Approval Droping berhasil disimpan";
                DB::connection($this->db)->commit();
            }

            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Approval Droping gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_app' => 'required',
            'no_aju' => 'required',
            'modul' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_app;			

            $del = DB::connection($this->db)->table('ys_app_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_app', $no_bukti)
            ->delete();

            if ($request->modul == "DROPING") {
                $del = DB::connection($this->db)->table('ys_minta_m') 
                ->where('no_minta',$request->no_aju)
                ->update(['no_app'=>'-','progress'=>'0']); 
            }

            //backup hapus
            $insb = DB::connection($this->db)->insert("insert into pbh_pb_his (no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver,nik_del,tgl_del) 
            select no_pb,no_dokumen,kode_lokasi,periode,nik_user,tgl_input,tanggal,due_date,keterangan,nilai,modul,progress,kode_pp,nik_app,nik_tahu,no_hutang,no_app,no_spb,no_ver,kode_bidang,kode_loktuj,nilai_final,posted,kode_proyek,no_app2,no_app3,no_fiat,no_kas,akun_hutang,nik_ver,?,getdate() 
            from pbh_pb_m where no_pb=? and kode_lokasi=?",array($nik,$request->no_aju,$kode_lokasi));

            $del1 = DB::connection($this->db)->table('pbh_pb_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $request->no_aju)
            ->delete();

            $del2 = DB::connection($this->db)->table('pbh_pb_j')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_pb', $request->no_aju)
            ->delete();

            $del3 = DB::connection($this->db)->table('pbh_pb_rek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_aju)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Approval Droping berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Approval Droping gagal dihapus ".$e;
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
            if(isset($request->no_app) && $request->no_app != ""){
                $sql = "select a.tanggal as due_date,a.no_minta as no_bukti,case a.progress when '1' then 'APPROVE' else 'RETURN' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,'DROPING' as modul,b.kode_pp+' - '+b.nama as pp,a.no_dokumen,a.keterangan,isnull(d.nilai,0) as nilai,c.nik+' - '+c.nama as pembuat,a.no_app,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                from ys_minta_m a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                left join (select a.no_minta,a.kode_lokasi,sum(a.nilai_usul) as nilai 
                    from ys_minta_d a 
                    where a.kode_lokasi='".$kode_lokasi."' 
                    group by a.no_minta,a.kode_lokasi  ) d 
                    on a.no_minta=d.no_minta and a.kode_lokasi=d.kode_lokasi 
                where a.no_app='".$request->no_app."' and a.no_minta='$request->no_aju' ";
            }else{

                $sql="select a.tanggal as due_date,a.no_minta as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.tanggal,103) as tgl2,'DROPING' as modul, 
			    b.kode_pp+' - '+b.nama as pp,a.no_dokumen,a.keterangan,isnull(d.nilai,0) as nilai,c.nik+' - '+c.nama as pembuat,a.no_app,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp,a.kode_lokasi 
			    from ys_minta_m a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                left join ( 
                    select a.no_minta,a.kode_lokasi,sum(a.nilai_usul) as nilai 
                    from ys_minta_d a 
                    group by a.no_minta,a.kode_lokasi 
                    ) d on a.no_minta=d.no_minta and a.kode_lokasi=d.kode_lokasi 
                where a.kode_lokasi='$kode_lokasi' and a.no_minta='$request->no_aju' and a.progress='0'";
            }
            $rs = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $lokasi_asal = $res[0]['kode_lokasi'];

                $sqldet = "select a.kode_akun,b.nama,a.keterangan,a.nilai_usul,a.nilai_app,a.nu 
                from ys_minta_d a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                where a.no_minta = '".$request->no_aju."'  order by a.nu";

                $rsdet = DB::connection($this->db)->select($sqldet);
                $resdet = json_decode(json_encode($rsdet),true);

                $sqldok = "select b.kode_jenis,b.nama,a.no_gambar 
                from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                where a.no_bukti = '".$request->no_aju."' and a.kode_lokasi='".$lokasi_asal."' order by a.nu";

                $rsdok = DB::connection($this->db)->select($sqldok);
                $resdok = json_decode(json_encode($rsdok),true);

                $sqlrek = "select *
                from pbh_rek a 
                where a.no_bukti = '".$request->no_aju."' and a.kode_lokasi='".$kode_lokasi."' ";

                $rsrek = DB::connection($this->db)->select($sqlrek);
                $resrek = json_decode(json_encode($rsrek),true);

                $sqlakun = "select kode_akun from pbh_pb_j where jenis ='TAK' and no_pb = '".$request->no_aju."' and kode_lokasi='".$kode_lokasi."'";
                $get = DB::connection($this->db)->select($sqlakun);
                if(count($get) > 0){
                    $success['kode_akun'] = $get[0]->kode_akun;
                }else{
                    $success['kode_akun'] = "-";
                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $resdet;
                $success['data_dok'] = $resdok;
                $success['data_rek'] = $resrek;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['data_dok'] = [];
                $success['data_rek'] = [];
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
            $success['memo'] = '';
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkunMutasi(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag='016' where a.block='0' and a.kode_lokasi = '".$kode_lokasi."' ";

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
