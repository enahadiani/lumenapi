<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JenisDokController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->db)->select("select kode_jenis from dok_jenis where kode_jenis ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_jenis)){
                if($request->kode_jenis == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_jenis='$request->kode_jenis' ";
                }
                $sql= "select a.kode_jenis,a.nama,a.kode_lokasi,a.idx
                from dok_jenis a 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select kode_jenis,nama,kode_lokasi,idx from dok_jenis where kode_lokasi= '".$kode_lokasi."'";
            }

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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
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
            'kode_jenis' => 'required|max:10',
            'nama' => 'required|max:200',
            'idx' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_jenis,$kode_lokasi)){

                $ins = DB::connection($this->db)->insert("insert into dok_jenis(kode_jenis,kode_lokasi,nama,idx) values (?, ?, ?, ?)",array($request->kode_jenis,$kode_lokasi,$request->nama,$request->idx));
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['kode'] = $request->kode_jenis;
                $success['message'] = "Data Jenis Dokumen berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. Kode Jenis Dokumen sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Dokumen gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
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
            'kode_jenis' => 'required|max:10',
            'nama' => 'required|max:200',
            'idx' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('dok_jenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_jenis', $request->kode_jenis)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into dok_jenis(kode_jenis,kode_lokasi,nama,idx) values (?, ?, ?, ?)",array($request->kode_jenis,$kode_lokasi,$request->nama,$request->idx));
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->kode_jenis;
            $success['message'] = "Data Jenis Dokumen berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['kode'] = "-";
            $success['message'] = "Data Jenis Dokumen gagal diubah ".$e;
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
            'kode_jenis' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('dok_jenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_jenis', $request->kode_jenis)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis Dokumen berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis Dokumen gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
