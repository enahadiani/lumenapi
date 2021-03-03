<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\SawalImport;
use App\Exports\SawalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Log; 

class SawalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "toko";
    public $db = "tokoaws";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }    

    public function getPeriode(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select periode from periode where kode_lokasi='".$kode_lokasi."' ";

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
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }        
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
            'periode' => 'required', 
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->update("delete from glma where periode='".$request->periode."' and kode_lokasi='$kode_lokasi' ");

            $ins2 = DB::connection($this->db)->insert("insert into glma(kode_akun, kode_lokasi, periode, so_akhir,tgl_input)  
                select kode_akun,kode_lokasi,periode,case when debet <> 0 then debet else -kredit end as so_akhir,getdate() as tgl_input
                from xsawal2 
                where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user' and periode ='$request->periode'           
            ");

            $del2 = DB::connection($this->db)->update("delete from xsawal2 where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Saldo awal berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Saldo awal gagal disimpan. Internal Server Error.";
            Log::error($e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
       
    }

    public function validateData($kode_akun,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi' 
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Akun $kode_akun tidak valid. ";
        }

        return $keterangan;

    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required',
            'periode' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('xsawal2')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            $periode =$request->periode;
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new SawalImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    
                    $ket = $this->validateData($row[0],$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }

                    $x[] = DB::connection($this->db)->insert("insert into xsawal2(kode_akun,debet,kredit,nik_user,tgl_input,sts_upload,ket_upload,nu,kode_lokasi,periode)  values ('".$row[0]."','".$row[1]."','".$row[2]."','".$request->nik_user."',getdate(),'".$sts."','".$ket."',".$no.",'$kode_lokasi','$request->periode') ");
                    $no++;
                }
            }
            
            DB::connection($this->db)->commit();
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
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $this->validate($request, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'nik' => 'required',
            'type' => 'required'
        ]);

        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $request->nik_user;
        $nik = $request->nik;
        $kode_lokasi = $request->kode_lokasi;
        if(isset($request->type) && $request->type == "template"){
            return Excel::download(new SawalExport($nik_user,$kode_lokasi,$request->type), 'Sawal_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new SawalExport($nik_user,$kode_lokasi,$request->type,$request->periode), 'Sawal_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function getSawalTmp(Request $request)
    {
        
        $this->validate($request, [
            'nik_user' => 'required'
        ]);

        $nik_user = $request->nik_user;
        $kode_pp = $request->kode_pp;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select a.kode_akun,a.debet,a.kredit,a.periode 
            from xsawal2 a
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' and periode='".$request->periode."' order by a.nu";
            $res = DB::connection($this->db)->select($sql);
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
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error";
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }

}
