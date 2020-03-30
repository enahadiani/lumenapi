<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
            $aju = DB::select("select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from yk_pb_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='1' and a.kode_lokasi='34' and a.modul in ('PBBAU','PBPR','PBINV') 					 
            union 			
            select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
            from panjar2_m a 
            inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
            where a.progress='1' and a.kode_lokasi='34' and a.modul in ('PJAJU','PJPR') 
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

    public function detail($modul,$no_aju){

        try {

            switch($modul){
                case 'PBBAU':
                case 'PBPR' :
                case 'PBINV': 		
                    $sql = "select a.due_date,a.no_pb as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from yk_pb_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_user=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.progress='1' and a.kode_lokasi='34' and a.modul in ('PBBAU','PBPR','PBINV') and a.no_pb='$no_aju' ";
                break;
                case 'PJAJU' :
                case 'PJPR' : 
                    $sql ="select a.due_date,a.no_panjar as no_bukti,'INPROG' as status,convert(varchar,a.tanggal,103) as tgl,convert(varchar,a.due_date,103) as tgl2,a.modul,b.kode_pp+' - '+b.nama as pp,'-' as no_dokumen,a.keterangan,a.nilai,c.nik+' - '+c.nama as pembuat,a.no_app2,a.kode_lokasi,convert(varchar,a.tgl_input,120) as tglinput,b.kode_pp 
                    from panjar2_m a 
                    inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                    inner join karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi 
                    where a.progress='1' and a.kode_lokasi='34' and a.modul in ('PJAJU','PJPR') and a.no_panjar='$no_aju' ";
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
            $rek = DB::select("select a.bank,a.cabang,a.no_rek,a.nama_rek,a.bruto,a.pajak
            from spm_rek a
            where a.no_bukti ='$no_bukti' and a.kode_lokasi='34'					 
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

    public function jurnal($modul,$no_aju){

        try {

            switch($modul){
                case 'PBBAU':
                case 'PBPR' :
                case 'PBINV': 		
                    $sql = "select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,d.kode_proyek,
                    isnull(e.nama,'-') as nama_proyek 
                    from yk_pb_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 							
                    inner join yk_pb_m d on a.no_pb=d.no_pb and a.kode_lokasi=d.kode_lokasi 	
                    left join spm_proyek e on d.kode_proyek=e.kode_proyek and d.kode_lokasi=e.kode_lokasi 
                    where a.no_pb = '$no_aju' and a.kode_lokasi='34'
                    ";
                break;
                case 'PJAJU' :
                case 'PJPR' : 
                    $sql ="select b.kode_akun,b.nama as nama_akun,'D' as dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,'-' as kode_proyek,'-' as nama_proyek 
                    from panjar2_m a 
                    inner join masakun b on a.akun_panjar=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 								
                    where a.no_panjar = '$no_aju' and a.kode_lokasi='34' ";
                break;
                case 'PJPTG':
                case 'PRPTG':
                    $sql="select b.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp,'-' as kode_proyek,'-' as nama_proyek 
                    from panjarptg2_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 								
                    where a.no_ptg = '$no_aju' and a.kode_lokasi='34' ";
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


}
