<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CustomerOLController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select kode_cust from cust where kode_cust ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            $filter = "";
            if(isset($request->kode_cust)){
                if($request->kode_cust == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_cust='$request->kode_cust' ";
                }
            }else{
                $filter .= "";
            }
            
            $sql= "select kode_cust,nama,alamat,no_tel,email,pic,id_lain,kota,provinsi from ol_cust a 
            where kode_lokasi='".$kode_lokasi."' $filter ";
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
            'kode_cust' => 'required|max:30',
            'nama' => 'required|max:50',
            'alamat' => 'required|max:200',
            'no_tel' => 'required|max:50',
            'email' => 'required|email|max:50',
            'pic' => 'required|max:50',
            'id_lain' => 'max:30',
            'kota' => 'required|max:150',
            'provinsi' => 'required|max:150'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_cust,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into ol_cust(kode_cust,nama,alamat,no_tel,email,pic,id_lain,kode_lokasi,kota,provinsi) values ('$request->kode_cust','$request->nama','$request->alamat','$request->no_tel','$request->email','$request->pic','$request->id_lain','$kode_lokasi','$request->kota','$request->provinsi') ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Customer berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Customer sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal disimpan ".$e;
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
            'kode_cust' => 'required|max:30',
            'nama' => 'required|max:50',
            'alamat' => 'required|max:200',
            'no_tel' => 'required|max:50',
            'email' => 'required|email|max:50',
            'pic' => 'required|max:50',
            'id_lain' => 'max:30'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ol_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into ol_cust(kode_cust,nama,alamat,no_tel,email,pic,id_lain,kode_lokasi,kota,provinsi) values ('$request->kode_cust','$request->nama','$request->alamat','$request->no_tel','$request->email','$request->pic','$request->id_lain','$kode_lokasi','$request->kota','$request->provinsi') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal diubah ".$e;
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
            'kode_cust' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ol_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
