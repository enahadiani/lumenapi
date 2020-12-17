<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Exports\KunjunganExport;
use App\Imports\KunjunganImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use PhpOffice\PhpSpreadsheet\Shared\Date;

class KunjunganController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    public function validateData($jenis,$kode_pp,$kode_biaya){
        $keterangan = "";
        $auth = DB::connection($this->sql)->select("select kode_pp from pp where kode_pp='$kode_pp' 
        ");
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Regional $kode_pp tidak valid";
        }

        $auth = DB::connection($this->sql)->select("select kode_biaya from yk_bpjs_biaya where kode_biaya='$kode_biaya' 
        ");
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Layanan $kode_biaya tidak valid";
        }

        if($jenis == "PEGAWAI" || $jenis == "PENSIUN"){
            $keterangan .="";
        }else{
            $keterangan .="Jenis $jenis tidak valid. Jenis yang diperbolehkan : PEGAWAI atau PENSIUN ";
        }
        return $keterangan;

    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required|max:6',
            'nik_user'=> 'required' 
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del1 = DB::connection($this->sql)->table('dash_kunj')->where('periode', $request->periode)->delete();

            $ins = DB::connection($this->sql)->insert("insert into dash_kunj(
                periode,kode_lokasi,jenis,kode_biaya,rka_kunj,jumlah,tgl_input,nik_user) 
                select periode,kode_lokasi,jenis,kode_biaya,rka_kunj,jumlah,tgl_input,'$nik' as nik_user from dash_kunj_tmp where nik_user='$request->nik_user' and periode ='$request->periode'  ");
                
                $del2 = DB::connection($this->sql)->table('dash_kunj_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Kunjungan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kunjungan gagal disimpan ".$e;
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
            
            $del1 = DB::connection($this->sql)->table('dash_kunj_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new KunjunganImport(),$nama_file);
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
                    $ket = $this->validateData($row[0],$row[1],$row[2]);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    // $nama = str_replace("'","",$row[1]);
                    $query .= "insert into dash_kunj_tmp(periode,kode_lokasi,jenis,kode_biaya,rka_kunj,jumlah,tgl_input,nik_user,sts_upload,ket_upload,nu) values ('".$request->periode."','".$row[1]."','".$row[0]."','".$row[2]."',".floatval($row[3]).",".floatval($row[4]).",getdate(),'".$request->nik_user."','".$sts."','".$ket."',".$no.");";
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
            return Excel::download(new KunjunganExport($request->nik_user,$request->periode,$request->type), 'Kunjungan_'.$request->nik_user.'.xlsx');
        }else{
            return Excel::download(new KunjunganExport($request->nik_user,$request->periode,$request->type), 'Kunjungan_'.$request->nik_user.'.xlsx');
        }
    }

    public function getKunjunganTmp(Request $request)
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
            periode,kode_lokasi as kode_pp,jenis,kode_biaya,rka_kunj,jumlah,tgl_input,nik_user
            from dash_kunj_tmp 
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
