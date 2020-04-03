<?php

namespace App\Http\Controllers\Approval;

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

    public function pengajuan(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $aju = DB::connection('sqlsrv')->select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from yk_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBBAU','PBPR','PBINV') 					 
            union 			
            select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from panjar2_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') 
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

    public function pengajuanfinal(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $aju = DB::connection('sqlsrv')->select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from yk_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='2' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBBAU','PBPR','PBINV') 					 
            union 			
            select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from panjar2_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='2' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') 
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

    public function pengajuandir(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $aju = DB::connection('sqlsrv')->select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from yk_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='3' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBBAU','PBPR','PBINV') 					 
            union 			
            select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from panjar2_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='3' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') 
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

    public function pengajuanhistory(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $aju = DB::connection('sqlsrv')->select("select a.no_pb,a.kode_lokasi,convert(varchar,a.tanggal,103) as tgl,a.keterangan,a.posted,a.nilai,
            case a.progress 
            when '0' then 'Pengajuan'
            when '1' then 'App GM/SM'
            when 'S' then 'Return GM/SM'
            when '2' then 'App Ver/SPB'
            when 'V' then 'Return Ver/SPB'
            when '3' then 'App GM Fin'
            when 'K' then 'App GM Fin'
            when '4' then 'App BOD'
            when 'D' then 'Return BOD'
            when '5' then 'App Fiat'
            when 'F' then 'Return Fiat'
            when '6' then 'Bayar'
            end as progress
            ,a.kode_pp,b.nama as nama_pp,
                   a.no_app,convert(varchar,c.tanggal,103) as tgl_app,
                   a.no_app4,convert(varchar,j.tanggal,103) as tgl_app4,
                   a.no_app2,convert(varchar,h.tanggal,103) as tgl_app2,
                   a.no_ver,convert(varchar,d.tanggal,103) as tgl_ver,
                   a.no_app3,convert(varchar,e.tanggal,103) as tgl_app3,
                   a.no_fiat,convert(varchar,f.tanggal,103) as tgl_fiat,
                   a.no_kas,convert(varchar,g.tanggal,103) as tgl_kas,
                   l.nama as vendor
                   
            from yk_pb_m a
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join spm_app_m c on a.no_app=c.no_app and a.kode_lokasi=c.kode_lokasi
            left join spm_app_m j on a.no_app4=j.no_app and a.kode_lokasi=j.kode_lokasi
            left join spm_app_m d on a.no_ver=d.no_app and a.kode_lokasi=d.kode_lokasi
            left join spm_app_m e on a.no_app3=e.no_app and a.kode_lokasi=e.kode_lokasi
            left join spm_app_m f on a.no_fiat=f.no_app and a.kode_lokasi=f.kode_lokasi
            left join spm_app_m h on a.no_app2=h.no_app and a.kode_lokasi=f.kode_lokasi
            left join trans_m g on a.no_kas=g.no_bukti and a.kode_lokasi=g.kode_lokasi
            inner join (select distinct kode_vendor,kode_lokasi,no_pb from yk_pb_d) k on a.no_pb=k.no_pb and a.kode_lokasi=k.kode_lokasi
            inner join vendor l on l.kode_vendor=k.kode_vendor and l.kode_lokasi=k.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            order by a.no_pb 					 
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

    public function detail($no_aju){

        try {
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            switch(substr($no_aju,3,2)){
                case 'PB':		
                    $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from yk_pb_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PBBAU','PBPR','PBINV') and a.no_pb='$no_aju' ";
                break;
                case 'PP' : 
                    $sql ="select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from panjar2_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.progress='1' and a.kode_lokasi='$kode_lokasi' and a.modul in ('PJAJU','PJPR') and a.no_panjar='$no_aju' ";
                break;
            }

            $det = DB::connection('sqlsrv')->select($sql);
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

    public function rekening($no_aju){

        try {
            
            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $rek = DB::connection('sqlsrv')->select("select a.bank,a.cabang,a.no_rek,a.nama_rek,a.bruto,a.pajak
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

            if($data =  Auth::guard('user')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            switch(substr($no_aju,3,2)){
                case 'PB':		
                    $sql = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,d.kode_proyek,
                    isnull(e.nama,'-') as nama_proyek 
                    from yk_pb_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 							
                    inner join yk_pb_m d on a.no_pb=d.no_pb and a.kode_lokasi=d.kode_lokasi 	
                    left join spm_proyek e on d.kode_proyek=e.kode_proyek and d.kode_lokasi=e.kode_lokasi 
                    where a.no_pb = '$no_aju' and a.kode_lokasi='$kode_lokasi'
                    ";
                break;
                case 'PP' : 
                    $sql ="select b.kode_akun,b.nama as nama_akun,'D' as dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,'-' as kode_proyek,'-' as nama_proyek 
                    from panjar2_m a 
                    inner join masakun b on a.akun_panjar=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 								
                    where a.no_panjar = '$no_aju' and a.kode_lokasi='$kode_lokasi' ";
                break;
                default :
                    $sql="select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,'-' as kode_proyek,'-' as nama_proyek 
                    from panjarptg2_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 								
                    where a.no_ptg = '$no_aju' and a.kode_lokasi='$kode_lokasi' ";
                break;
            }

            $jur = DB::connection('sqlsrv')->select($sql);
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

    public function approvalSM(Request $request)
    {
        
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }else{
            $nik= '';
            $kode_lokasi= '34';
        }

        $this->validate($request, [
            'modul' => 'required',
            'status' => 'required',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "S";
        } else if ($request->input('status') == "APPROVE") {
            $vStatus = "1";	
        }
        
        $str_format="0000";
        $periode=date('Y').date('m');
        $tanggal=date('Y-m-d');

        $nik="tes";
        $per=date('y').date('m');
        $prefix=$kode_lokasi."-AGM".$per.".";		
        
        $query = DB::connection('sqlsrv')->select("select right(isnull(max(no_app),'".$prefix."0000'),".strlen($str_format).")+1 as id from spm_app_m where no_app like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrv')->beginTransaction();
        
        try {
            if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR" ||$request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" || $request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG" ) {

                if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {

                    DB::connection('sqlsrv')->table('spm_app_m')
                        ->where('no_bukti', $request->input('no_aju'))
                        ->where('no_flag', '-')
                        ->where('form', 'APPSM')
                        ->where('modul', $request->input('modul'))            
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_flag' => $no_bukti]);
        
                    $ins = DB::connection('sqlsrv')->insert('insert into spm_app_m (no_app,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat)  values (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$request->input('modul'),'APPSM',$request->input('no_aju'),$request->input('keterangan'),'-','X','X']);
        
                    //---------------- flag bukti									
                    if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR") {
                        DB::connection('sqlsrv')->table('yk_pb_m')
                        ->where('no_pb', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                                                                                    
                    if ($request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" ) {
                        DB::connection('sqlsrv')->table('panjar2_m')
                        ->where('no_panjar', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                                                
                    if ($request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG"){
                        DB::connection('sqlsrv')->table('panjarptg2_m')
                        ->where('no_ptg', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
                    } 
                
                    DB::connection('sqlsrv')->commit();
                    $success['status'] = true;
                    $success['id'] = $no_bukti;
                    $success['message'] = "Data approval berhasil disimpan";
                }else{
                    $success['status'] = false;
                    $success['message'] = "Data status tidak valid";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Data modul tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

    public function approvalFinal(Request $request)
    {
        
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }else{
            $nik= '';
            $kode_lokasi= '34';
        }

        $this->validate($request, [
            'modul' => 'required',
            'status' => 'required',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "K";
        } else {
            $vStatus = "3";	
        }	
        
        $str_format="0000";
        $periode=date('Y').date('m');
        $tanggal=date('Y-m-d');

        $nik="tes";
        $per=date('y').date('m');
        $prefix=$kode_lokasi."-AFI".$per.".";		
        
        $query = DB::connection('sqlsrv')->select("select right(isnull(max(no_app),'".$prefix."0000'),".strlen($str_format).")+1 as id from spm_app_m where no_app like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrv')->beginTransaction();
        
        try {
            if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR" ||$request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" || $request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG" ) {

                if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {

                    DB::connection('sqlsrv')->table('spm_app_m')
                        ->where('no_bukti', $request->input('no_aju'))
                        ->where('no_flag', '-')
                        ->where('form', 'APPFIN')
                        ->where('modul', $request->input('modul'))            
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_flag' => $no_bukti]);
        
                    $ins = DB::connection('sqlsrv')->insert('insert into spm_app_m (no_app,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat)  values (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$request->input('modul'),'APPFIN',$request->input('no_aju'),$request->input('keterangan'),'-','X','X']);
        
                    //---------------- flag bukti									
                    if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR") {
                        DB::connection('sqlsrv')->table('yk_pb_m')
                        ->where('no_pb', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app3' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                                                                                    
                    if ($request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" ) {
                        DB::connection('sqlsrv')->table('panjar2_m')
                        ->where('no_panjar', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app3' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                                                
                    if ($request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG"){
                        DB::connection('sqlsrv')->table('panjarptg2_m')
                        ->where('no_ptg', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app3' => $no_bukti,'progress'=>$vStatus]);
                    } 
                
                    DB::connection('sqlsrv')->commit();
                    $success['status'] = true;
                    $success['id'] = $no_bukti;
                    $success['message'] = "Data approval berhasil disimpan";
                }else{
                    $success['status'] = false;
                    $success['message'] = "Data status tidak valid";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Data modul tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

    public function approvalDir(Request $request)
    {
        
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }else{
            $nik= '';
            $kode_lokasi= '34';
        }

        $this->validate($request, [
            'modul' => 'required',
            'status' => 'required',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "D";
        } else {
            $vStatus = "5";	
        }	
        
        $str_format="0000";
        $periode=date('Y').date('m');
        $tanggal=date('Y-m-d');

        $nik="tes";
        $per=date('y').date('m');
        $prefix=$kode_lokasi."-ADI".$per.".";		
        
        $query = DB::connection('sqlsrv')->select("select right(isnull(max(no_app),'".$prefix."0000'),".strlen($str_format).")+1 as id from spm_app_m where no_app like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::connection('sqlsrv')->beginTransaction();
        
        try {
            if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR" ||$request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" || $request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG" ) {
                if ($request->input('status') == "APPROVE" || $request->input('status') == "RETURN" ) {

                    DB::connection('sqlsrv')->table('spm_app_m')
                        ->where('no_bukti', $request->input('no_aju'))
                        ->where('no_flag', '-')
                        ->where('form', 'APPDIR')
                        ->where('modul', $request->input('modul'))            
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_flag' => $no_bukti]);
        
                    $ins = DB::connection('sqlsrv')->insert('insert into spm_app_m (no_app,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat)  values (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$request->input('modul'),'APPDIR',$request->input('no_aju'),$request->input('keterangan'),'-','X','X']);
        
                    //---------------- flag bukti									
                    if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR") {
                        DB::connection('sqlsrv')->table('yk_pb_m')
                        ->where('no_pb', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app4' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                                                                                    
                    if ($request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" ) {
                        DB::connection('sqlsrv')->table('panjar2_m')
                        ->where('no_panjar', $request->input('no_aju'))        
                        ->where('kode_lokasi', $kode_lokasi)
                        ->update(['no_app4' => $no_bukti,'progress'=>$vStatus]);
                    }
                                                            
                
                    DB::connection('sqlsrv')->commit();
                    $success['status'] = true;
                    $success['id'] = $no_bukti;
                    $success['message'] = "Data approval berhasil disimpan";
                }else{
                    $success['status'] = false;
                    $success['message'] = "Data status tidak valid";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Data modul tidak valid";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

}
