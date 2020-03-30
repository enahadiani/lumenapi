<?php

namespace App\Http\Controllers\apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApvController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public $successStatus = 200;
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function pengajuan(){

        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            $data =  Auth::user();
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $aju = DB::select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
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
            
            $data =  Auth::user();
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

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

            $det = DB::select($sql);
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

    public function rekening($no_bukti){

        try {
            
            $data =  Auth::user();
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

            $rek = DB::select("select a.bank,a.cabang,a.no_rek,a.nama_rek,a.bruto,a.pajak
            from spm_rek a
            where a.no_bukti ='$no_bukti' and a.kode_lokasi='$kode_lokasi'					 
            ");
            $rek = json_decode(json_encode($rek),true);
            
            if(count($rek) > 0){ //mengecek apakah data kosong atau tidak
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

            $data =  Auth::user();
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;

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

            $jur = DB::select($sql);
            $jur = json_decode(json_encode($jur),true);
            
            if(count($jur) > 0){ //mengecek apakah data kosong atau tidak
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

    public function appSM(Request $request)
    {
        
        $data =  Auth::user();
        $nik= $data->nik;
        $kode_lokasi= $data->kode_lokasi;

        $this->validate($request, [
            'modul' => 'required',
            'status' => 'required',
            'no_aju' => 'required',
            'keterangan' => 'required',
        ]);

        if ($request->input('status') == "RETURN") {
            $vStatus = "S";
        } else {
            $vStatus = "1";	
        }	
        
        $str_format="0000";
        $periode=date('Y').date('m');
        $tanggal=date('Y-m-d');

        $nik="tes";
        $per=date('y').date('m');
        $prefix=$kode_lokasi."-AGM".$per.".";		
        
        $query = DB::select("select right(isnull(max(no_app),'".$prefix."0000'),".strlen($str_format).")+1 as id from spm_app_m where no_app like '$prefix%'");
        
        $query = json_decode(json_encode($query),true);

        $no_bukti = $prefix.str_pad($query[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);

        DB::beginTransaction();
        
        try {
            DB::table('spm_app_m')
                ->where('no_bukti', $request->input('no_aju'))
                ->where('no_flag', '-')
                ->where('form', 'APPSM')
                ->where('modul', $request->input('modul'))            
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['no_flag' => $no_bukti]);

            $ins = DB::insert('insert into spm_app_m (no_app,kode_lokasi,tanggal,periode,tgl_input,nik_user,status,modul,form,no_bukti,catatan,no_flag,nik_bdh,nik_fiat)  values (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$kode_lokasi,$tanggal,$periode,$tanggal,$nik,$vStatus,$request->input('modul'),'APPSM',$request->input('no_aju'),$request->input('keterangan'),'-','X','X']);

            //---------------- flag bukti									
            if ($request->input('modul') == "PBBAU" || $request->input('modul') == "PBPR") {
                DB::table('yk_pb_m')
                ->where('no_pb', $request->input('no_aju'))        
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
            }
            																								
            if ($request->input('modul') == "PJAJU" || $request->input('modul') == "PJPR" ) {
                DB::table('panjar2_m')
                ->where('no_panjar', $request->input('no_aju'))        
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
            }
           																
            if ($request->input('modul') == "PJPTG" || $request->input('modul') == "PRPTG"){
                DB::table('panjarptg2_m')
                ->where('no_ptg', $request->input('no_aju'))        
                ->where('kode_lokasi', $kode_lokasi)
                ->update(['no_app2' => $no_bukti,'progress'=>$vStatus]);
            } 
          
            DB::commit();
            $success['status'] = true;
            $success['id'] = $no_bukti;
            $success['message'] = "Data approval berhasil disimpan";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::rollback();
            $success['status'] = false;
            $success['message'] = "Data approval gagal disimpan ".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

}
