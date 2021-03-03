<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\AkunImport;
use App\Exports\AkunExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Log; 

class AkunController extends Controller
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [ 
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ins2 = DB::connection($this->db)->insert("insert into masakun(kode_akun, nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal,tgl_input)  
                select kode_akun, nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal,getdate() as tgl_input
                from xmasakun 
                where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user'
            ");

            $del2 = DB::connection($this->db)->update("delete from xmasakun where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akun berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun gagal disimpan. Internal Server Error.";
            Log::error($e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
       
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('xmasakun')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new AkunImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    
                    $ket = "";
                    $sts = 1;

                    $x[] = DB::connection($this->db)->insert("insert into xmasakun(kode_akun, nama,modul,jenis,kode_curr,block,status_gar,normal,nik_user,tgl_input,sts_upload,ket_upload,nu,kode_lokasi)  values ('".$row[0]."','".$row[1]."','".$row[2]."','".$row[3]."','".$row[4]."','".$row[5]."','".$row[6]."','".$row[7]."','".$request->nik_user."',getdate(),'".$sts."','".$ket."',".$no.",'$kode_lokasi') ");
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
            return Excel::download(new AkunExport($nik_user,$kode_lokasi,$request->type), 'Akun_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new AkunExport($nik_user,$kode_lokasi,$request->type), 'Akun_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function getAkunTmp(Request $request)
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
            $sql = "select kode_akun, nama,modul,jenis,kode_curr,block,status_gar,normal
            from xmasakun 
            where nik_user = '".$nik_user."' and kode_lokasi='".$kode_lokasi."' order by nu";
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
