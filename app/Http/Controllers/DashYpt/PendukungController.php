<?php

namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PendukungController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select kode_dash from dash_ypt_neraca where kode_dash = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['kode_dash'] = $auth[0]['kode_dash'];
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

            $res = DB::connection($this->db)->select("select kode_dash,nama,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input from dash_ypt_neraca where kode_lokasi='$kode_lokasi'	 
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
            'kode_dash' => 'required',
            'nama' => 'required',
            'kode_fs' => 'required',
            'kode_neraca' => 'required|array',
            'keterangan' => 'required|array' 
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            DB::connection($this->db)->beginTransaction();

            $res = $this->isUnik($request->kode_dash);
            if($res['status']){
                
                $sql = DB::connection($this->db)->insert("insert into dash_ypt_neraca (kode_dash,nama,kode_lokasi,kode_fs,tgl_input) values (?,?,?,?,getdate())",array($request->kode_dash,$request->nama,$kode_lokasi,$request->kode_fs));
            
                if (count($request->kode_neraca) > 0){
                    for ($i=0;$i < count($request->kode_neraca);$i++){
                        $ins = DB::connection($this->db)->insert("insert into dash_ypt_neraca_d (kode_dash,kode_lokasi,kode_neraca,kode_fs,nama) values (?,?,?,?,?)",array($request->kode_dash,$kode_lokasi,$request->kode_neraca[$i],$request->kode_fs,$request->keterangan[$i]));
                    }
                }	

                $tmp="sukses";
                $sts=true;
                
            }else{
                $tmp = "Transaksi tidak valid. Kode Dash Neraca '".$data[$i]['kode_dash']."' sudah ada di database.";
                $sts = false;
            }

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['kode_dash'] = $request->kode_dash;
                $success['message'] = "Data Setting Dash Neraca berhasil disimpan ";
                return response()->json($success, $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['kode_dash'] = "-";
                $success['message'] = $tmp;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Setting Dash Neraca gagal disimpan ".$e;
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
            'kode_dash' => 'required',
            'nama' => 'required',
            'kode_fs' => 'required',
            'kode_neraca' => 'required|array',
            'keterangan' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }


            $del1 = DB::connection($this->db)->table('dash_ypt_neraca')->where('kode_lokasi', $kode_lokasi)->where('kode_dash', $request->kode_dash)->delete();

            $del2 = DB::connection($this->db)->table('dash_ypt_neraca_d')->where('kode_lokasi', $kode_lokasi)->where('kode_dash', $request->kode_dash)->delete();

            $sql = DB::connection($this->db)->insert("insert into dash_ypt_neraca (kode_dash,nama,kode_lokasi,kode_fs,tgl_input) values (?,?,?,?,getdate())",array($request->kode_dash,$request->nama,$kode_lokasi,$request->kode_fs));
            
            if (count($request->kode_neraca) > 0){
                for ($i=0;$i < count($request->kode_neraca);$i++){
                    $ins = DB::connection($this->db)->insert("insert into dash_ypt_neraca_d (kode_dash,kode_lokasi,kode_neraca,kode_fs,nama) values (?,?,?,?,?)",array($request->kode_dash,$kode_lokasi,$request->kode_neraca[$i],$request->kode_fs,$request->keterangan[$i]));
                }
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode_dash'] = $request->kode_dash;
            $success['message'] = "Data Setting Dash Neraca berhasil diubah ";
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Setting Dash Neraca gagal diubah ".$e;
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
            
            $del1 = DB::connection($this->db)->table('dash_ypt_neraca')->where('kode_lokasi', $kode_lokasi)->where('kode_dash', $request->kode_dash)->delete();

            $del2 = DB::connection($this->db)->table('dash_ypt_neraca_d')->where('kode_lokasi', $kode_lokasi)->where('kode_dash', $request->kode_dash)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Dash Neraca berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dash Neraca gagal dihapus ".$e;
            
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

            $kode_dash= $request->kode_dash;
            $res = DB::connection($this->db)->select("select a.kode_dash,a.nama,a.kode_fs, a.tgl_input,c.nama as nama_fs 
            from dash_ypt_neraca a 
            left join fs c on a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi
            where a.kode_dash = '".$kode_dash."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_neraca,c.nama as nama_neraca,a.nama as keterangan
                    from dash_ypt_neraca_d a
                    inner join neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs
                    where a.kode_dash = '".$kode_dash."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
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
            $res = DB::connection($this->db)->select("select a.kode_neraca,a.nama from neraca a where a.kode_lokasi='$kode_lokasi' $filter ");						
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

