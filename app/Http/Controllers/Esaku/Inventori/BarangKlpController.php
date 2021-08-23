<?php

namespace App\Http\Controllers\Esaku\Inventori;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BarangKlpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_klp from brg_barangklp where kode_klp ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_klp)){
                if($request->kode_klp == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_klp='$request->kode_klp' ";
                }
                $sql= "select a.kode_klp,a.nama,a.akun_pers,a.akun_pdpt,a.akun_hpp, b.nama as nama_pers,c.nama as nama_pdpt,d.nama as nama_hpp from brg_barangklp a
                inner join masakun b on a.akun_pers =b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                inner join masakun c on a.akun_pdpt =c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                inner join masakun d on a.akun_hpp =d.kode_akun and a.kode_lokasi=d.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."'  
                $filter ";
            }else{
                $sql = "select kode_klp,nama,akun_pers,akun_pdpt,akun_hpp,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,tgl_input from brg_barangklp where kode_lokasi= '".$kode_lokasi."'";
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

    /**
     * Show the from for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'kode_klp' => 'required',
            'nama' => 'required',
            'akun_pers' => 'required',
            'akun_pdpt' => 'required',
            'akun_hpp' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_klp,$kode_lokasi)){

                $insert = "insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp,tgl_input) 
                values (?,?,?,?,?,?,getdate())";
                DB::connection($this->sql)->insert($insert, [
                    $request->kode_klp,
                    $kode_lokasi,
                    $request->nama,
                    $request->akun_pers,
                    $request->akun_pdpt,
                    $request->akun_hpp
                ]);
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_klp;
                $success['message'] = "Data Kelompok Barang berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. Kode Kelompok Barang sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
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
            'kode_klp' => 'required',
            'nama' => 'required',
            'akun_pers' => 'required',
            'akun_pdpt' => 'required',
            'akun_hpp' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('brg_barangklp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klp', $request->kode_klp)
            ->delete();

            $insert = "insert into brg_barangklp(kode_klp,kode_lokasi,nama,akun_pers,akun_pdpt,akun_hpp,tgl_input) 
            values (?,?,?,?,?,?,getdate())";
            DB::connection($this->sql)->insert($insert, [
                $request->kode_klp,
                $kode_lokasi,
                $request->nama,
                $request->akun_pers,
                $request->akun_pdpt,
                $request->akun_hpp
            ]);
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_klp;
            $success['message'] = "Data Kelompok Barang berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_klp' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $del = DB::connection($this->sql)->table('brg_barangklp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klp', $request->kode_klp)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kelompok Barang berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPers(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != ""){
                    $filter .= " and a.kode_akun = '$request->kode_akun' ";
                }else{
                    $filter .= "";
                }
            }

            $sql = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '005' where  a.kode_lokasi='$kode_lokasi' $filter ";

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

    public function getPdpt(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != ""){
                    $filter .= " and a.kode_akun = '$request->kode_akun' ";
                }else{
                    $filter .= "";
                }
            }

            $sql = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '022' where  a.kode_lokasi='$kode_lokasi' $filter ";

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

    public function getHpp(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != ""){
                    $filter .= " and a.kode_akun = '$request->kode_akun' ";
                }else{
                    $filter .= "";
                }
            }

            $sql = "select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '027' where  a.kode_lokasi='$kode_lokasi' $filter";

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

}
