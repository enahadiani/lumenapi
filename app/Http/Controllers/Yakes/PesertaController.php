<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Exports\PesertaExport;
use App\Imports\PesertaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PesertaController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }    

    public function validateData($jenis,$kode_pp){
        $keterangan = "";
        $auth = DB::connection($this->sql)->select("select kode_pp from pp where kode_pp='$kode_pp' 
        ");
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Regional $kode_pp tidak valid";
        }

        if($jenis == "PEGAWAI" || $jenis == "PENSIUN"){
            $keterangan .="";
        }else{
            $keterangan .="Jenis $jenis tidak valid. Jenis yang diperbolehkan : PEGAWAI atau PENSIUN ";
        }
        return $keterangan;

    }

    public function index(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select no_bukti,keterangan,periode,total_upload,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,tgl_input from dash_peserta_m where kode_lokasi='".$kode_lokasi."' ";

            $res = DB::connection($this->sql)->select($sql);
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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required|max:6',
            'keterangan' => 'required|max:200',
            'nik_user'=> 'required' 
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del1 = DB::connection($this->sql)->table('dash_peserta')->where('periode', $request->periode)->delete();

            $per = date('ym');
            $no_bukti = $this->generateKode("hr_karyawan_m", "no_bukti", $kode_lokasi."-UPS".$per.".", "0001");

            $insm = DB::connection($this->sql)->insert("insert into dash_peserta_m(no_bukti,kode_lokasi,periode,keterangan,total_upload,tgl_input,nik_user) select  '$no_bukti','$kode_lokasi','$request->periode','$request->keterangan', count(jenis),getdate(),'$nik' from dash_peserta_tmp where nik_user='$request->nik_user' and periode ='$request->periode' ");

            $ins = DB::connection($this->sql)->insert("insert into dash_peserta(
                periode,kode_lokasi,jenis,kk,pas,anak,jd,rka_claim,tgl_input,nik_user) 
                select periode,kode_lokasi,jenis,kk,pas,anak,jd,rka_claim,tgl_input,'$nik' as nik_user from dash_peserta_tmp where nik_user='$request->nik_user' and periode ='$request->periode'  ");
            
            $insd = DB::connection($this->sql)->insert("insert into dash_peserta_d(no_bukti,periode,kode_lokasi,jenis,kk,pas,anak,jd,rka_claim,tgl_input,nik_user) 
            select '$no_bukti',periode,kode_lokasi,jenis,kk,pas,anak,jd,rka_claim,tgl_input,'$nik' as nik_user from dash_peserta_tmp where nik_user='$request->nik_user' and periode ='$request->periode'  ");
            
            $del2 = DB::connection($this->sql)->table('dash_peserta_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Data Peserta berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Peserta gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required',
            'periode' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->sql)->table('dash_peserta_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new PesertaImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $query = "";
            $status_validate = true;
            $no=1;
            $ket = "";
            date_default_timezone_set('Asia/Jakarta');
            $tgl_input = date('Y-m-d H:i:s');
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateData($row[0],$row[1]);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    // $nama = str_replace("'","",$row[1]);
                    $query .= "insert into dash_peserta_tmp(periode,kode_lokasi,jenis,kk,pas,anak,jd,rka_claim,tgl_input,nik_user,sts_upload,ket_upload,nu) values ('".$request->periode."','".$row[1]."','".$row[0]."',".intval($row[2]).",".intval($row[3]).",".intval($row[4]).",".intval($row[5]).",".floatval($row[6]).",getdate(),'".$request->nik_user."','".$sts."','".$ket."',".$no.");";
                    $no++;
                }
            }

            $insert = DB::connection($this->sql)->insert($begin.$query.$commit);
            
            DB::connection($this->sql)->commit();
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
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $this->validate($request, [
            'nik_user' => 'required',
            'periode' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new PesertaExport($request->nik_user,$request->periode,$request->type), 'Peserta_'.$request->nik_user.'.xlsx');
        }else{
            return Excel::download(new PesertaExport($request->nik_user,$request->periode,$request->type), 'Peserta_'.$request->nik_user.'.xlsx');
        }
    }

    public function getPesertaTmp(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required',
            'periode' => 'required'
        ]);

        $nik_user = $request->nik_user;
        $periode = $request->periode;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select 
            periode,kode_lokasi as kode_pp,jenis,kk,pas,anak,jd,rka_claim,tgl_input,nik_user
            from dash_peserta_tmp 
            where nik_user = '".$nik_user."' and periode='".$periode."' 
            order by nu";
            $res = DB::connection($this->sql)->select($sql);
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


}
