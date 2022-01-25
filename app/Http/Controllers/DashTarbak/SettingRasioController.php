<?php

namespace App\Http\Controllers\DashTarbak;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SettingRasioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'tarbak';
    public $db = 'sqlsrvtarbak';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select kode_rasio from dash_ypt_rasio_m where kode_rasio = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['kode_rasio'] = $auth[0]['kode_rasio'];
        }else{
            $res['status'] = true;
        }
        return $res;
    }
    
    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_rasio,nama,klp_rasio,keterangan,rumus,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input from dash_ypt_rasio_m where kode_lokasi='$kode_lokasi'	 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data']= [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
            'kode_rasio' => 'required',
            'nama' => 'required',
            'kode_klp' => 'required',
            'keterangan' => 'required',
            'kode_fs' => 'required',
            'kode_neraca' => 'required|array',
            'nama2' => 'required|array',
            'dc' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            DB::connection($this->db)->beginTransaction();

            $res = $this->isUnik($request->kode_rasio);
            if($res['status']){
                
                $sql = DB::connection($this->db)->insert("insert into dash_ypt_rasio_m (kode_rasio,nama,klp_rasio,kode_lokasi,rumus,keterangan,kode_fs,tgl_input) values ('$request->kode_rasio','$request->nama','$request->kode_klp','$kode_lokasi','$request->rumus','$request->keterangan','$request->kode_fs',getdate())");
            
                if (count($request->kode_neraca) > 0){
                    for ($i=0;$i < count($request->kode_neraca);$i++){
                        $ins = DB::connection($this->db)->insert("insert into dash_ypt_rasio_d (kode_rasio,kode_lokasi,kode_neraca,kode_fs,no,nama,dc) values ('$request->kode_rasio','$kode_lokasi','".$request->kode_neraca[$i]."','".$request->kode_fs."',$i,'".$request->nama2[$i]."','".$request->dc[$i]."')");
                    }
                }	

                $tmp="sukses";
                $sts=true;
                
            }else{
                $tmp = "Transaksi tidak valid. Kode Rasio '".$data[$i]['kode_rasio']."' sudah ada di database.";
                $sts = false;
            }

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['kode_rasio'] = $request->kode_rasio;
                $success['message'] = "Data Setting Rasio berhasil disimpan ";
                return response()->json($success, $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['kode_rasio'] = "-";
                $success['message'] = $tmp;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Setting Rasio gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_rasio' => 'required',
            'nama' => 'required',
            'kode_klp' => 'required',
            'keterangan' => 'required',
            'kode_fs' => 'required',
            'kode_neraca' => 'required|array',
            'nama2'=>'required|array',
            'dc'=>'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }


            $del1 = DB::connection($this->db)->table('dash_ypt_rasio_m')->where('kode_lokasi', $kode_lokasi)->where('kode_rasio', $request->kode_rasio)->delete();

            $del2 = DB::connection($this->db)->table('dash_ypt_rasio_d')->where('kode_lokasi', $kode_lokasi)->where('kode_rasio', $request->kode_rasio)->delete();

            $sql = DB::connection($this->db)->insert("insert into dash_ypt_rasio_m (kode_rasio,nama,klp_rasio,kode_lokasi,rumus,keterangan,kode_fs,tgl_input) values ('$request->kode_rasio','$request->nama','$request->kode_klp','$kode_lokasi','$request->rumus','$request->keterangan','$request->kode_fs',getdate())");
            
            if (count($request->kode_neraca) > 0){
                for ($i=0;$i < count($request->kode_neraca);$i++){
                    $ins = DB::connection($this->db)->insert("insert into dash_ypt_rasio_d (kode_rasio,kode_lokasi,kode_neraca,kode_fs,no,nama,dc) values ('$request->kode_rasio','$kode_lokasi','".$request->kode_neraca[$i]."','".$request->kode_fs."',$i,'".$request->nama2[$i]."','".$request->dc[$i]."')");
                }
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_rasio'] = $request->kode_rasio;
            $success['message'] = "Data Setting Rasio berhasil diubah ";
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Setting Rasio gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Rasio  $Rasio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('dash_ypt_rasio_m')->where('kode_lokasi', $kode_lokasi)->where('kode_rasio', $request->kode_rasio)->delete();

            $del2 = DB::connection($this->db)->table('dash_ypt_rasio_d')->where('kode_lokasi', $kode_lokasi)->where('kode_rasio', $request->kode_rasio)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Rasio berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Rasio gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $kode_rasio= $request->kode_rasio;
            $res = DB::connection($this->db)->select("select a.kode_rasio,a.nama,a.klp_rasio,a.keterangan,a.rumus,a.kode_fs, a.tgl_input,b.nama as nama_klp,c.nama as nama_fs 
            from dash_ypt_rasio_m a 
            left join dash_ypt_rasio_klp b on a.klp_rasio=b.kode_klp and a.kode_lokasi=b.kode_lokasi
            left join fs c on a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi
            where a.kode_rasio = '".$kode_rasio."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_neraca,c.nama as nama_neraca,a.nama,a.dc
                    from dash_ypt_rasio_d a
                    inner join dash_ypt_rasio_m b on a.kode_rasio=b.kode_rasio and a.kode_lokasi=b.kode_lokasi 
                    inner join neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs
                    where a.kode_rasio = '".$kode_rasio."' and a.kode_lokasi='".$kode_lokasi."' order by a.no");
            $res2= json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getNeraca(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_fs) && $request->kode_fs != ""){
                $filter .= " and a.kode_fs = '$request->kode_fs' ";
            }
            if(isset($request->kode_neraca) && $request->kode_neraca != ""){
                $filter .= " and a.kode_neraca = '$request->kode_neraca' ";
            }
            $res = DB::connection($this->db)->select(" select a.kode_neraca,a.nama from neraca a where a.kode_lokasi='$kode_lokasi' $filter
            union all
            select a.kode_dash as kode_neraca,a.nama from dash_ypt_neraca a where a.kode_lokasi='$kode_lokasi' $filter
             ");						
            $res= json_decode(json_encode($res),true);
            
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKlp(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $filter = "";
            if(isset($request->kode_klp) && $request->kode_klp != ""){
                $filter .= " and a.kode_klp = '$request->kode_klp' ";
            }
            $res = DB::connection($this->db)->select("select a.kode_klp,a.nama from dash_ypt_rasio_klp a where a.kode_lokasi='$kode_lokasi' $filter ");						
            $res= json_decode(json_encode($res),true);
            
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    // public function validateRasio($kode_neraca,$kode_pp,$dc,$ket,$nilai,$kode_lokasi){
    //     $keterangan = "";
    //     $auth = DB::connection($this->db)->select("select kode_neraca from masakun where kode_neraca='$kode_neraca' and kode_lokasi='$kode_lokasi'
    //     ");
    //     $auth = json_decode(json_encode($auth),true);
    //     if(count($auth) > 0){
    //         $keterangan .= "";
    //     }else{
    //         $keterangan .= "Kode Akun $kode_neraca tidak valid. ";
    //     }

    //     $auth2 = DB::connection($this->db)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi'
    //     ");
    //     $auth2 = json_decode(json_encode($auth2),true);
    //     if(count($auth2) > 0){
    //         $keterangan .= "";
    //     }else{
    //         $keterangan .= "Kode PP $kode_pp tidak valid. ";
    //     }

    //     if(floatval($nilai) > 0){
    //         $keterangan .= "";
    //     }else{
    //         $keterangan .= "Nilai tidak valid. ";
    //     }

    //     if($ket != ""){
    //         $keterangan .= "";
    //     }else{
    //         $keterangan .= "Keterangan tidak valid. ";
    //     }

    //     if($dc == "D" || $dc == "C"){
    //         $keterangan .= "";
    //     }else{
    //         $keterangan .= "DC $dc tidak valid. ";
    //     }

    //     return $keterangan;
    //     // return $keterangan;

    // }


    // public function importExcel(Request $request)
    // {
    //     $this->validate($request, [
    //         'file' => 'required|mimes:csv,xls,xlsx',
    //         'nik_user' => 'required'
    //     ]);

    //     DB::connection($this->db)->beginTransaction();
    //     try {
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $del1 = DB::connection($this->db)->table('Rasio_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

    //         // menangkap file excel
    //         $file = $request->file('file');
    
    //         // membuat nama file unik
    //         $nama_file = rand().$file->getClientOriginalName();

    //         Storage::disk('local')->put($nama_file,file_get_contents($file));
    //         // $excel = Excel::import(new RasioImport($request->nik_user), $nama_file);
    //         $dt = Excel::toArray(new RasioImport($request->nik_user),$nama_file);
    //         $excel = $dt[0];
    //         $x = array();
    //         $status_validate = true;
    //         $no=1;
    //         foreach($excel as $row){
    //             if($row[0] != ""){
    //                 $ket = $this->validateRasio(strval($row[0]),strval($row[4]),strval($row[1]),strval($row[2]),floatval($row[3]),$kode_lokasi);
    //                 if($ket != ""){
    //                     $sts = 0;
    //                     $status_validate = false;
    //                 }else{
    //                     $sts = 1;
    //                 }
    //                 $x[] = RasioTmp::create([
    //                     'kode_neraca' => strval($row[0]),
    //                     'dc' => strval($row[1]),
    //                     'keterangan' => strval($row[2]),
    //                     'nilai' => floatval($row[3]),
    //                     'kode_pp' => strval($row[4]),
    //                     'kode_lokasi' => $kode_lokasi,
    //                     'nik_user' => $request->nik_user,
    //                     'tgl_input' => date('Y-m-d H:i:s'),
    //                     'status' => $sts,
    //                     'ket_status' => $ket,
    //                     'nu' => $no
    //                 ]);
    //                 $no++;
    //             }
    //         }
            
    //         DB::connection($this->db)->commit();
    //         Storage::disk('local')->delete($nama_file);
    //         if($status_validate){
    //             $msg = "File berhasil diupload!";
    //         }else{
    //             $msg = "Ada error!";
    //         }
            
    //         $success['status'] = true;
    //         $success['validate'] = $status_validate;
    //         $success['message'] = $msg;
    //         return response()->json($success, $this->successStatus);
    //     } catch (\Throwable $e) {
    //         DB::connection($this->db)->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
        
    // }

    // public function export(Request $request) 
    // {
    //     $nik_user = $request->nik_user;
    //     $kode_lokasi = $request->kode_lokasi;
    //     $nik = $request->nik;
    //     date_default_timezone_set("Asia/Bangkok");
    //     return Excel::download(new RasioExport($nik_user,$kode_lokasi), 'Rasio_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
    // }

    // public function getRasioTmp(Request $request)
    // {
        
    //     $nik_user = $request->nik_user;

    //     try {
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

    //         $res = DB::connection($this->db)->select("select a.kode_neraca,a.dc,a.keterangan,a.nilai,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp 
    //         from Rasio_tmp a
    //         inner join masakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
    //         inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
    //         where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
    //         $res= json_decode(json_encode($res),true);

    //         if(count($res) > 0){ //mengecek apakah data kosong atau tidak
    //             $success['status'] = true;
    //             $success['detail'] = $res;
    //             $success['message'] = "Success!";
    //             return response()->json($success, $this->successStatus);     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!"; 
    //             $success['detail'] = [];
    //             $success['status'] = false;
    //             return response()->json($success, $this->successStatus);
    //         }
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
        
    // }
}

