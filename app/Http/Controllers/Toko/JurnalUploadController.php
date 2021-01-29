<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Imports\JurnalUploadImport;
use App\Exports\JurnalUploadExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Log; 
use PhpOffice\PhpSpreadsheet\Shared\Date;

class JurnalUploadController extends Controller
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

            $del = DB::connection($this->db)->update("delete from gldt where periode='".$request->periode."' and kode_lokasi='$kode_lokasi' ");

            $ins2 = DB::connection($this->db)->insert("insert into gldt(kode_lokasi, periode, no_bukti, tanggal, kode_akun, dc, keterangan, nilai, kode_pp, nu,modul,jenis)  
                select kode_lokasi, periode, no_bukti, tanggal, akun_debet, 'D', keterangan, nilai, 'KUG' as kode_pp, nu,'MI' as modul,'MI' as jenis
                from xjan 
                where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user' and periode ='$request->periode'   
                union all
                select kode_lokasi, periode, no_bukti, tanggal, akun_kredit, 'C', keterangan, nilai, 'KUG' as kode_pp, nu,'MI' as modul,'MI' as jenis
                from xjan 
                where kode_lokasi='$kode_lokasi' and nik_user='$request->nik_user' and periode ='$request->periode'           
            ");

            $del2 = DB::connection($this->db)->update("delete from xjan where nik_user='$request->nik_user' and kode_lokasi='$kode_lokasi' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal disimpan. Internal Server Error.".$e;
            Log::error($e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
       
    }

    public function validateData($kode_akun,$kode_akun2,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi' 
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Akun Debet $kode_akun tidak valid. ";
        }

        $auth2 = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun2' and kode_lokasi='$kode_lokasi' 
        ");
        $auth2 = json_decode(json_encode($auth2),true);
        if(count($auth2) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Akun Kredit $kode_akun2 tidak valid. ";
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
            
            $del1 = DB::connection($this->db)->table('xjan')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            $periode =$request->periode;
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new JurnalUploadImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            $ket= "";
            foreach($excel as $row){
                if($row[0] != ""){
                    
                    $ket = $this->validateData($row[3],$row[4],$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $tgl = (is_int($row[0]) ? Date::excelToDateTimeObject($row[0])->format('Y-m-d') : $row[2]);
                    $x[] = DB::connection($this->db)->insert("insert into xjan(tanggal,no_bukti,keterangan,akun_debet,akun_kredit,nilai,sts_upload,ket_upload,nu,kode_lokasi,periode,tgl_input,nik_user)  values ('".$tgl."','".$row[1]."','".$row[2]."','".$row[3]."','".$row[4]."',".floatval($row[5]).",'".$sts."','".$ket."',".$no.",'$kode_lokasi','$request->periode',getdate(),'$request->nik_user') ");
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
            return Excel::download(new JurnalUploadExport($nik_user,$kode_lokasi,$request->type), 'JurnalUpload_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new JurnalUploadExport($nik_user,$kode_lokasi,$request->type,$request->periode), 'JurnalUpload_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }

    public function getJurnalUploadTmp(Request $request)
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
            $sql = "select tanggal,no_bukti,keterangan,akun_debet,akun_kredit,nilai
            from xjan 
            where nik_user = '".$nik_user."' and kode_lokasi='".$kode_lokasi."' and periode='".$request->periode."' order by nu";
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
