<?php

namespace App\Http\Controllers\Sju;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public $successStatus = 200;
    // public function __construct()
    // {
    //     $this->middleware();
    // }

    public function pengajuan(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select case when no_atasan ='-' then 'INPROG' else 'APPROVE' end as status, no_pb, convert(varchar,tanggal,103) as tgl,kode_pp,keterangan, nilai,kode_curr,kurs,no_atasan,nilai_curr,due_date, progress 
            from sju_pb_m 
            where periode <='$request->periode' and kode_lokasi='$kode_lokasi' and progress='0' and no_atasan='-' 
            and modul='PBPROSES' and no_kas='-' and nik_atasan='$nik' ";

            $aju = DB::connection('sqlsrvsju')->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($aju);$i++){
                    $aju[$i]["nilai"] = number_format($aju[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['sql']= $sql;
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuanfinal(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select case when no_app1 ='-' then 'INPROG' else 'APPROVE' end as status, no_pb, convert(varchar,tanggal,103) as tgl,kode_pp,keterangan, nilai,kode_curr,kurs,no_app1 as no_app,nilai_curr,due_date, 'APP-VP' as progress 
            from sju_pb_m 
            where periode <='$request->periode' and kode_lokasi='".$kode_lokasi."' and progress='2' and no_app1='-' and modul='PBPROSES' and no_kas='-' and nik_app1='".$nik."' ";

            $aju = DB::connection('sqlsrvsju')->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($aju);$i++){
                    $aju[$i]["nilai"] = number_format($aju[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['sql']= $sql;
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuankug(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection('sqlsrvsju')->select("select case when no_app2 ='-' then 'INPROG' else 'APPROVE' end as status, no_pb, convert(varchar,tanggal,103) as tgl,kode_pp,keterangan, nilai,kode_curr,kurs,no_app2 as no_app,nilai_curr,due_date, 'APP-DIRKUG' as progress 
            from sju_pb_m 
            where periode <='$request->periode' and kode_lokasi='".$kode_lokasi."' and progress='3' and no_app2='-' and modul='PBPROSES' and no_kas='-' and nik_app2='".$nik."'			 
            ");
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($aju);$i++){
                    $aju[$i]["nilai"] = number_format($aju[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuandir(Request $request){

        // $kode_lokasi= $request->input('kode_lokasi');
        $this->validate($request,[
            'periode' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection('sqlsrvsju')->select("select case when no_app3 ='-' then 'INPROG' else 'APPROVE' end as status, no_pb, convert(varchar,tanggal,103) as tgl,kode_pp,keterangan, nilai,kode_curr,kurs,no_app3 as no_app,nilai_curr,due_date, 'APP-DIRUT' as progress 
            from sju_pb_m 
            where periode <='$request->periode' and kode_lokasi='".$kode_lokasi."' and progress='4' and no_app3='-' and modul='PBPROSES' and no_kas='-' and nik_app3='".$nik."'			 
            ");
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($aju);$i++){
                    $aju[$i]["nilai"] = number_format($aju[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuanHistory(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection('sqlsrvsju')->select("
            select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_atasan,a.no_app1,a.no_app2,a.no_app3,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from sju_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='2' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBPROSES') 	
            order by tgl  					 
            ");
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($aju);$i++){
                    $aju[$i]["nilai"] = number_format($aju[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ajuHistory($jenis){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if ($jenis=="all") {
                $tmp="";
            }
            if ($jenis=="reject") {
                $tmp=" and d.status in ('A','P','K','U') ";
            }
            if ($jenis=="approve") {
                $tmp=" and d.status not in ('1','3','4','5') ";
            }
            $sql="select a.due_date,a.no_pb as no_bukti,case when d.status in ('A','P','K','U') then 'REJECT' when d.status not in ('1','3','4','5') then 'APPROVE' else 'INPROG' end as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_atasan,a.no_app1,a.no_app2,a.no_app3,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from sju_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            inner join sju_ver_m d on a.no_pb=d.no_dokumen and a.kode_lokasi=d.kode_lokasi
            where d.nik_user='$nik' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBPROSES') $tmp
            order by a.tanggal	";
            $aju = DB::connection('sqlsrvsju')->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
              
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detail($no_aju){

        try {
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_akun, b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
            from sju_pb_j a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 					  
            where a.no_pb ='$no_aju' and a.kode_lokasi='$kode_lokasi'";

            $det = DB::connection('sqlsrvsju')->select($sql);
            $det = json_decode(json_encode($det),true);
            
            if(count($det) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($det);$i++){
                    $det[$i]["nilai"] = number_format($det[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $det;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ajuDetailHistory($no_aju){

        try {
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            switch(substr($no_aju,3,2)){
                case 'PB':	
                    $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_atasan,a.no_app1,a.no_app2,a.no_app3,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from sju_pb_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.no_pb='$no_aju' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBPROSES') and c.nik_user='$nik'	
                    order by tgl";
                break;
                case 'PP' : 
                    $sql ="select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from panjar2_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') and a.no_panjar='$no_aju' ";
                break;
            }

            $det = DB::connection('sqlsrvsju')->select($sql);
            $det = json_decode(json_encode($det),true);
            
            if(count($det) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($det);$i++){
                    $det[$i]["nilai"] = number_format($det[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $det;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ajuDetailDok($no_aju){

        try {
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select b.kode_jenis,b.nama,a.no_gambar 
            from pbh_dok a 
            inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
            where a.no_bukti = '$no_aju' and a.kode_lokasi='$kode_lokasi' 
            order by a.nu ";
          
            $det = DB::connection('sqlsrvsju')->select($sql);
            $det = json_decode(json_encode($det),true);
            
            if(count($det) > 0){ //mengecek apakah data kosong atau tidak
               
                $success['status'] = true;
                $success['data'] = $det;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ajuDetailApproval($no_aju){

        try {
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_akun, b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
            from sju_pb_j a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 					  
            where a.no_pb ='$no_aju' and a.kode_lokasi='$kode_lokasi'";

           
            $det = DB::connection('sqlsrvsju')->select($sql);
            $det = json_decode(json_encode($det),true);
            
            if(count($det) > 0){ //mengecek apakah data kosong atau tidak
               
                $success['status'] = true;
                $success['data'] = $det;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rekening($no_aju){

        try {
            
            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="";
            $rek = DB::connection('sqlsrvsju')->select("select a.bank,a.cabang,a.no_rek,a.nama_rek,a.bruto,a.pajak
            from spm_rek a
            where a.no_bukti ='$no_aju' and a.kode_lokasi='$kode_lokasi'					 
            ");
            $rek = json_decode(json_encode($rek),true);
            
            if(count($rek) > 0){ //mengecek apakah data kosong atau tidak
                for($i=0;$i<count($rek);$i++){
                    $rek[$i]["bruto"] = number_format($rek[$i]["bruto"],0,",","."); 
                    $rek[$i]["pajak"] = number_format($rek[$i]["pajak"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $rek;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function jurnal($no_aju){

        try {

            if($data =  Auth::guard('sju')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_akun, b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
            from sju_pb_j a 
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 					  
            where a.no_pb ='$no_aju' and a.kode_lokasi='$kode_lokasi'";
           

            $jur = DB::connection('sqlsrvsju')->select($sql);
            $jur = json_decode(json_encode($jur),true);
            
            if(count($jur) > 0){ //mengecek apakah data kosong atau tidak
                
                for($i=0;$i<count($jur);$i++){
                    $jur[$i]["nilai"] = number_format($jur[$i]["nilai"],0,",","."); 
                }
                $success['status'] = true;
                $success['data'] = $jur;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function approvalSM($request)
    {
        
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "A";
        } else if ($request->input('status') == "APPROVE") {
            $vStatus = "1";	
        }                    
        
        $str_format="0000";
        
        $tanggal=$request->tanggal;
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $per=substr($periode,2,2);
        $prefix=$kode_lokasi."-AAT".$per.".";		
        
        $query = DB::connection('sqlsrvsju')->select("select right(isnull(max(no_ver),'".$prefix."0000'),".strlen($str_format).")+1 as id from sju_ver_m where no_ver like '$prefix%'");        
        $query = json_decode(json_encode($query),true);
        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
												

        DB::connection('sqlsrvsju')->beginTransaction();
        
        try {

            if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {
               
                $ins = DB::connection('sqlsrvsju')->insert('insert into sju_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,no_dokumen,no_verseb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,'ATASAN',$request->no_aju,'-']);

                $insdet = DB::connection('sqlsrvsju')->insert('insert into sju_ver_d (no_ver,status,modul,no_bukti,kode_lokasi,catatan) values (?, ?, ?, ?, ?, ?)', [$no_bukti,$vStatus,'PBPROSES',$request->no_aju,$kode_lokasi,$request->keterangan]);
    
                //---------------- flag bukti		
                $upd = DB::connection('sqlsrvsju')->table('sju_pb_m')
                ->where('no_pb', $request->no_aju)       
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['progress' => $vStatus,'no_atasan' =>$no_bukti]);
                
                DB::connection('sqlsrvsju')->commit();
                $success['status'] = true;
                $success['id'] = $no_bukti;
                $success['message'] = "Data approval berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Data status tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvsju')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

    public function approvalFinal($request)
    {
        
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "P";
        } else {
            $vStatus = "3";	
        }	

        $progress = "APP-VP";
        
        $str_format="0000";
        
        $tanggal=$request->tanggal;
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $per=substr($periode,2,2);
        $prefix=$kode_lokasi."-VDD".$per.".";		
        
        $query = DB::connection('sqlsrvsju')->select("select right(isnull(max(no_ver),'".$prefix."0000'),".strlen($str_format).")+1 as id from sju_ver_m where no_ver like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrvsju')->beginTransaction();
        
        try {
           
            if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {
                    
                $ins = DB::connection('sqlsrvsju')->insert('insert into sju_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,no_dokumen,no_verseb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$progress,$request->no_aju,'-']);
                    
                $insdet = DB::connection('sqlsrvsju')->insert('insert into sju_ver_d (no_ver,status,modul,no_bukti,kode_lokasi,catatan) values (?, ?, ?, ?, ?, ?)', [$no_bukti,$vStatus,'PBPROSES',$request->no_aju,$kode_lokasi,$request->keterangan]);
                    
                //---------------- flag bukti		
                $upd = DB::connection('sqlsrvsju')->table('sju_pb_m')
                    ->where('no_pb', $request->no_aju)       
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $vStatus,'no_app1' =>$no_bukti]);

                DB::connection('sqlsrvsju')->commit();
                $success['status'] = true;
                $success['id'] = $no_bukti;
                $success['message'] = "Data approval berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Data status tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvsju')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

    public function approvalKug($request)
    {
        
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "K";
        } else {
            $vStatus = "4";	
        }	

        $progress = "APP-DIRKUG";
        
        $str_format="0000";
        
        $tanggal=$request->tanggal;
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $per=substr($periode,2,2);
        $prefix=$kode_lokasi."-VDD".$per.".";		
        
        $query = DB::connection('sqlsrvsju')->select("select right(isnull(max(no_ver),'".$prefix."0000'),".strlen($str_format).")+1 as id from sju_ver_m where no_ver like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrvsju')->beginTransaction();
        
        try {
           
            if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {
                    
                $ins = DB::connection('sqlsrvsju')->insert('insert into sju_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,no_dokumen,no_verseb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$progress,$request->no_aju,'-']);
                    
                $insdet = DB::connection('sqlsrvsju')->insert('insert into sju_ver_d (no_ver,status,modul,no_bukti,kode_lokasi,catatan) values (?, ?, ?, ?, ?, ?)', [$no_bukti,$vStatus,'PBPROSES',$request->no_aju,$kode_lokasi,$request->keterangan]);
                    
                //---------------- flag bukti		
                $upd = DB::connection('sqlsrvsju')->table('sju_pb_m')
                    ->where('no_pb', $request->no_aju)       
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $vStatus,'no_app2' =>$no_bukti]);
                    
                DB::connection('sqlsrvsju')->commit();
                $success['status'] = true;
                $success['id'] = $no_bukti;
                $success['message'] = "Data approval berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Data status tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvsju')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }															
    }

    public function approvalDir($request)
    {
        
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "U";
        } else {
            $vStatus = "5";	
        }	

        $progress = "APP-DIRUT";
        
        $str_format="0000";
       
        $tanggal=$request->tanggal;
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $per=substr($periode,2,2);
        $prefix=$kode_lokasi."-VDD".$per.".";		
        
        $query = DB::connection('sqlsrvsju')->select("select right(isnull(max(no_ver),'".$prefix."0000'),".strlen($str_format).")+1 as id from sju_ver_m where no_ver like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrvsju')->beginTransaction();
        
        try {
           
            if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {
                    
                $ins = DB::connection('sqlsrvsju')->insert('insert into sju_ver_m (no_ver,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,no_dokumen,no_verseb) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$progress,$request->no_aju,'-']);
                    
                $insdet = DB::connection('sqlsrvsju')->insert('insert into sju_ver_d (no_ver,status,modul,no_bukti,kode_lokasi,catatan) values (?, ?, ?, ?, ?, ?)', [$no_bukti,$vStatus,'PBPROSES',$request->no_aju,$kode_lokasi,$request->keterangan]);
                    
                //---------------- flag bukti		
                $upd = DB::connection('sqlsrvsju')->table('sju_pb_m')
                    ->where('no_pb', $request->no_aju)       
                    ->where('kode_lokasi', $kode_lokasi)
                    ->update(['progress' => $vStatus,'no_app3' =>$no_bukti]);
                    
                DB::connection('sqlsrvsju')->commit();
                $success['status'] = true;
                $success['id'] = $no_bukti;
                $success['message'] = "Data approval berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Data status tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrvsju')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }															
    }

}
