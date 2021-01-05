<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Exports\HrKaryawanExport;
use App\Imports\HrKaryawanImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\HrKaryawanTmp;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class HrKaryawanController extends Controller
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

    public function indexm(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select no_bukti,keterangan,periode,total_upload,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,tgl_input from hr_karyawan_m where kode_lokasi='".$kode_lokasi."' ";

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

    public function cariNik(Request $request) {
        $this->validate($request, [    
            'nik' => 'required',
            'periode' => 'required'
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select nik, nama from hr_karyawan where nik='".$request->nik."' and periode='".$request->periode."' ");
            $res = json_decode(json_encode($res),true);
            
            $success['status'] = true;
            $success['data'] = $res;
            $success['message'] = "Success!";
            return response()->json(['success'=>$success], $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function isUnik($isi,$periode)
    {        
        $auth = DB::connection($this->sql)->select("select nik from hr_karyawan where nik ='".$isi."' and periode='".$periode."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik)){
                if($request->nik == "all"){
                    $filter = "";
                }else{
                    $filter = " where nik='".$request->nik."' ";
                }
                $sql= "select nik, nama from hr_karyawan ".$filter;
            } 
            else {
                $sql = "select nik, nama from hr_karyawan ";
            }

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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function validateData($nik,$gender,$sts_organik,$sts_medis,$sts_edu,$sts_aktif,$kode_pp,$periode){
        $keterangan = "";
        $auth = DB::connection($this->sql)->select("select nik from hr_karyawan where nik='$nik' and periode='$periode'
        ");
        if(count($auth) > 0){
            $keterangan .= "NIK $nik sudah ada di datatabase. ";
        }else{
            $keterangan .= "";
        }

        if($gender == "L" || $gender == "P"){
            $keterangan .="";
        }else{
            $keterangan .="Gender $gender tidak valid. ";
        }

        $auth2 = DB::connection($this->sql)->select("select sts_organik from hr_stsorganik where sts_organik='$sts_organik'
        ");
        if(count($auth2) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Sts Organik $sts_organik tidak valid. ";
        }

        $auth3 = DB::connection($this->sql)->select("select sts_medis from hr_stsmedis where sts_medis='$sts_medis'
        ");
        if(count($auth3) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Sts Medis $sts_medis tidak valid. ";
        }

        $auth4 = DB::connection($this->sql)->select("select sts_edu from hr_stsedu where sts_edu='$sts_edu'
        ");
        if(count($auth4) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Sts Edu $sts_edu tidak valid. ";
        }

        if($sts_aktif == "1" || $sts_aktif == "0"){
            $keterangan .="";
        }else{
            $keterangan .="Status Aktif $sts_aktif tidak valid. ";
        }
        return $keterangan;

    }

    public function store(Request $request)
    {
        $this->validate($request, [
            // 'nik' => 'required|max:10',
            // 'nama' => 'required|max:50',    
            // 'tgl_lahir' => 'required',
            // 'gender' => 'required|max:1',   
            // 'sts_organik' => 'required|max:10',          
            // 'sts_medis' => 'required|max:10',          
            // 'sts_edu' => 'required|max:10',                      
            // 'sts_aktif' => 'required|max:1',           
            // 'kode_pp' => 'required|max:10',
            // 'periode' => 'required|max:6',
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
            if($this->isUnik($request->nik, $request->periode)){

                // $ins = DB::connection($this->sql)->insert("insert into hr_karyawan(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user,periode) values 
                //                                          ('".$request->nik."','".$request->nama."','".$request->tgl_lahir."','".$request->gender."','".$request->sts_organik."','".$request->sts_medis."','".$request->sts_edu."','".$request->sts_aktif."','".$request->kode_pp."',getdate(),'".$nik."','".$request->periode."')");
                $per = date('ym');
                $no_bukti = $this->generateKode("hr_karyawan_m", "no_bukti", $kode_lokasi."-UKR".$per.".", "0001");

                $del1 = DB::connection($this->sql)->table('hr_karyawan')->where('periode', $request->periode)->delete();

                $insm = DB::connection($this->sql)->insert("insert into hr_karyawan_m(no_bukti,kode_lokasi,periode,keterangan,total_upload,tgl_input,nik_user) select  '$no_bukti','$kode_lokasi','$request->periode','$request->keterangan', count(nik),getdate(),'$nik' from hr_karyawan_tmp where nik_user='$request->nik_user' and periode ='$request->periode' ");

                $ins = DB::connection($this->sql)->insert("insert into hr_karyawan(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user,periode) 
                select nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,'$nik' as nik_user,periode from hr_karyawan_tmp where nik_user='$request->nik_user' and periode ='$request->periode'  ");

                $insd = DB::connection($this->sql)->insert("insert into hr_karyawan_d(no_bukti,nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user,periode) 
                select '$no_bukti',nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,'$nik' as nik_user,periode from hr_karyawan_tmp where nik_user='$request->nik_user' and periode ='$request->periode'  ");

                $exec =  DB::connection($this->sql)->update("exec gen_dash_sdm '$request->periode','$request->nik' ");

                $del2 = DB::connection($this->sql)->table('hr_karyawan_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Karyawan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required|max:10',
            'nama' => 'required|max:50',    
            'tgl_lahir' => 'required',
            'gender' => 'required|max:1',   
            'sts_organik' => 'required|max:10',          
            'sts_medis' => 'required|max:10',          
            'sts_edu' => 'required|max:10',                      
            'sts_aktif' => 'required|max:1',           
            'kode_pp' => 'required|max:10',
            'periode' => 'required|max:6'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_karyawan')
            ->where('nik', $request->nik)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_karyawan(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user,periode) values 
                                                      ('".$request->nik."','".$request->nama."','".$request->tgl_lahir."','".$request->gender."','".$request->sts_organik."','".$request->sts_medis."','".$request->sts_edu."','".$request->sts_aktif."','".$request->kode_pp."',getdate(),'".$nik."','".$request->periode."')");
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required|max:10',
            'periode' => 'required|max:6'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_karyawan')            
            ->where('nik', $request->nik)
            ->where('periode', $request->periode)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
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
            
            $del1 = DB::connection($this->sql)->table('hr_karyawan_tmp')->where('periode', $request->periode)->where('nik_user', $request->nik_user)->delete();
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new HrKaryawanImport(),$nama_file);
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
                    $ket = $this->validateData($row[0],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$request->periode);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $nama = str_replace("'","",$row[1]);
                    $tgl = (is_int($row[2]) ? Date::excelToDateTimeObject($row[2])->format('Y-m-d') : $row[2]);
                    $query .= "insert into hr_karyawan_tmp(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user,periode,sts_upload,ket_upload,nu) values ('".$row[0]."','".$nama."','".$tgl."','".$row[3]."','".$row[4]."','".$row[5]."','".$row[6]."','".$row[7]."','".$row[8]."',getdate(),'".$request->nik_user."','".$request->periode."','".$sts."','".$ket."',".$no.");";
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
            return Excel::download(new HrKaryawanExport($request->nik_user,$request->periode,$request->type), 'Karyawan_'.$request->nik_user.'.xlsx');
        }else{
            return Excel::download(new HrKaryawanExport($request->nik_user,$request->periode,$request->type), 'Karyawan_'.$request->nik_user.'.xlsx');
        }
    }

    public function getKaryawanTmp(Request $request)
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
            nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,periode
            from hr_karyawan_tmp 
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
