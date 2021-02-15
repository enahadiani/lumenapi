<?php

namespace App\Http\Controllers\Yakes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 

class KelompokAkunController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsapkug';
    public $guard = 'yakes';

    function isUnik($isi){

        $strSQL = "select kode_klpakun from dash_klp_akun where kode_klpakun = '".$isi."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['kode_klpakun'] = $auth[0]['kode_klpakun'];
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

            $res = DB::connection($this->db)->select("select kode_klpakun,nama,jenis,idx,warna,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input from dash_klp_akun
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
            'kode_klpakun' => 'required',
            'nama' => 'required',
            'jenis' => 'required',
            'idx' => 'required',
            'warna' => 'required',
            'kode_akun' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            DB::connection($this->db)->beginTransaction();

            $res = $this->isUnik($request->kode_klpakun);
            if($res['status']){
                
                $sql = DB::connection($this->db)->insert("insert into dash_klp_akun (kode_klpakun,nama,jenis,idx,warna,tgl_input) values ('$request->kode_klpakun','$request->nama','$request->jenis','$request->idx','$request->warna',getdate())");
            
                if (count($request->kode_akun) > 0){
                    for ($i=0;$i < count($request->kode_akun);$i++){
                        $ins = DB::connection($this->db)->insert("insert into dash_klp_akun_d (kode_klpakun,kode_akun) values ('$request->kode_klpakun','".$request->kode_akun[$i]."')");
                    }
                }	

                $tmp="sukses";
                $sts=true;
                
            }else{
                $tmp = "Transaksi tidak valid. Kode Kelompok Akun '".$data[$i]['kode_klpakun']."' sudah ada di database.";
                $sts = false;
            }

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['kode_klpakun'] = $request->kode_klpakun;
                $success['message'] = "Data Kelompok Akun berhasil disimpan ";
                return response()->json($success, $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['kode_klpakun'] = "-";
                $success['message'] = $tmp;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Akun gagal disimpan ".$e;
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
            'kode_klpakun' => 'required',
            'nama' => 'required',
            'jenis' => 'required',
            'idx' => 'required',
            'warna' => 'required',
            'kode_akun' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }


            $del1 = DB::connection($this->db)->table('dash_klp_akun')->where('kode_klpakun', $request->kode_klpakun)->delete();

            $del2 = DB::connection($this->db)->table('dash_klp_akun_d')->where('kode_klpakun', $request->kode_klpakun)->delete();

            $sql = DB::connection($this->db)->insert("insert into dash_klp_akun (kode_klpakun,nama,jenis,idx,warna,tgl_input) values ('$request->kode_klpakun','$request->nama','$request->jenis','$request->idx','$request->warna',getdate())");
            
            if (count($request->kode_akun) > 0){
                for ($i=0;$i < count($request->kode_akun);$i++){
                    $ins = DB::connection($this->db)->insert("insert into dash_klp_akun_d (kode_klpakun,kode_akun) values ('$request->kode_klpakun','".$request->kode_akun[$i]."')");
                }
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_klpakun'] = $request->kode_klpakun;
            $success['message'] = "Data Kelompok Akun berhasil diubah ";
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Akun gagal diubah ".$e;
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
            
            $del1 = DB::connection($this->db)->table('dash_klp_akun')->where('kode_klpakun', $request->kode_klpakun)->delete();

            $del2 = DB::connection($this->db)->table('dash_klp_akun_d')->where('kode_klpakun', $request->kode_klpakun)->delete();

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

            $kode_klpakun= $request->kode_klpakun;
            $res = DB::connection($this->db)->select("select a.kode_klpakun,a.nama,a.jenis,a.idx,a.warna,a.tgl_input
            from dash_klp_akun a 
            where a.kode_klpakun = '".$kode_klpakun."' ");						
            $res= json_decode(json_encode($res),true);
            
            $sql="select a.kode_akun,b.nama as nama_akun
            from dash_klp_akun_d a
            inner join masakun b on a.kode_akun=b.kode_akun and b.kode_lokasi='$kode_lokasi'
            where a.kode_klpakun = '$kode_klpakun' ";
            $res2 = DB::connection($this->db)->select($sql);
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

    public function getAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $filter = "";
            if(isset($request->kode_akun) && $request->kode_akun != ""){
                $filter = " and a.kode_akun='$request->kode_akun' ";
            }else{
                $filter = "";
            }
            $sql="select a.kode_akun,a.nama 
            from masakun a 
            where a.kode_lokasi='$kode_lokasi' $filter ";
            $res = DB::connection($this->db)->select($sql);						
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

    
}

