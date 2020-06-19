<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RumahController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_rumah from rt_rumah where kode_rumah ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_rumah)){
                if($request->kode_rumah == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_rumah='$request->kode_rumah' ";
                }
                $sql= "select a.kode_rumah,a.kode_lokasi,a.rt,a.rw,a.blok,a.status_huni from rt_rumah a where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select a.kode_rumah,a.kode_lokasi,a.rt,a.rw,a.blok,a.status_huni from rt_rumah a where a.kode_lokasi= '".$kode_lokasi."'";
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
            'kode_rumah' => 'required',
            'rt' => 'required',
            'rw' => 'required',
            'blok' => 'required',
            'status_huni' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_rumah,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert('insert into rt_rumah(kode_rumah,kode_lokasi,rt,rw,blok,status_huni) values (?, ?, ?, ?, ?, ?)', array($request->kode_rumah,$kode_lokasi,$request->rt,$request->rw,$request->blok,$request->status_huni));
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Rumah berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Rumah sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Rumah gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_rumah' => 'required',
            'rt' => 'required',
            'rw' => 'required',
            'blok' => 'required',
            'status_huni' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('rt_rumah')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_rumah', $request->kode_rumah)
            ->delete();

            $ins = DB::connection($this->sql)->insert('insert into rt_rumah(kode_rumah,kode_lokasi,rt,rw,blok,status_huni) values (?, ?, ?, ?, ?, ?)', array($request->kode_rumah,$kode_lokasi,$request->rt,$request->rw,$request->blok,$request->status_huni));
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Rumah berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Rumah gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_rumah' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
            $del = DB::connection($this->sql)->table('rt_rumah')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_rumah', $request->kode_rumah)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Rumah berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Rumah gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
