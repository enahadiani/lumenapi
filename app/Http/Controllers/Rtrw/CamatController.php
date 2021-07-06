<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class CamatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    public function isUnik($isi){
        
        $auth = DB::connection($this->db)->select("select kode_camat from rt_camat where kode_camat ='".$isi."' ");
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
            if(isset($request->kode_camat)){
                if($request->kode_camat == "all"){
                    $filter = "";
                }else{
                    $filter = "where kode_camat='$request->kode_camat' ";
                }
                $sql= "select a.kode_camat,a.nama,a.kode_kota,b.nama as nama_kota 
                from rt_camat a
                inner join rt_kota b on a.kode_kota=b.kode_kota   
                $filter";
            }else{
                $sql = "select a.kode_camat,a.nama,a.kode_kota,b.nama as nama_kota 
                from rt_camat a
                inner join rt_kota b on a.kode_kota=b.kode_kota  ";
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
            'kode_camat' => 'required',
            'nama' => 'required',
            'kode_kota' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_camat)){
                $ins = DB::connection($this->db)->insert("insert into rt_camat(kode_camat,nama,kode_kota) values ('".$request->kode_camat."','".$request->nama."','".$request->kode_kota."') ");
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Camat berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Camat sudah ada di database!";
            }
            $success['kode'] = $request->kode_camat;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal disimpan ".$e;
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
            'kode_camat' => 'required',
            'nama' => 'required',
            'kode_kota' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_camat')
            ->where('kode_camat', $request->kode_camat)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into rt_camat(kode_camat,nama,kode_kota) values ('".$request->kode_camat."','".$request->nama."','".$request->kode_kota."') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Camat berhasil diubah";
            $success['kode'] = $request->kode_camat;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal diubah ".$e;
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
            'kode_camat' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('rt_camat')
            ->where('kode_camat', $request->kode_camat)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Camat berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Camat gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
