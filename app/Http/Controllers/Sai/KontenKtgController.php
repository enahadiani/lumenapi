<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KontenKtgController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_ktg)){
                if($request->kode_ktg == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_ktg='$request->kode_ktg' ";
                }  
            }else{
                $filter .="";
            }

            $sql = "SELECT kode_ktg, nama FROM sai_konten_ktg as status where kode_lokasi= '".$kode_lokasi."' $filter";

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
            'nama' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $req = $request->all();

            $str_format="000";
            $prefix="KTG";
            $sql2=  "select right(isnull(max(kode_ktg),'".$prefix."000'),".strlen($str_format).")+1 as id from sai_konten_ktg where kode_ktg like '$prefix%' and kode_lokasi='".$kode_lokasi."'";
            $query = DB::connection($this->sql)->select($sql2);
    
            $id = $prefix.str_pad($query[0]->id, strlen($str_format), $str_format, STR_PAD_LEFT);

            $ins = DB::connection($this->sql)->insert("insert into sai_konten_ktg (kode_lokasi,kode_ktg,jenis,nama) values ('".$kode_lokasi."','".$id."','Gambar','".$req['nama']."') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten Kategori berhasil disimpan";

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten Kategori gagal disimpan ".$e;
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
            'kode_ktg' => 'required',
            'nama' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $req = $request->all();
            $ins = DB::connection($this->sql)->update("update sai_konten_ktg set nama='".$req['nama']."' where kode_ktg = '".$req['kode_ktg']."' and kode_lokasi='".$kode_lokasi."'");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten Kategori berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten Kategori gagal diubah ".$e;
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
            'kode_ktg' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_konten_ktg')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_ktg', $request->kode_ktg)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Konten Kategori berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Konten Kategori gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
