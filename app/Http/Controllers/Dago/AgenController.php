<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AgenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select no_agen from dgw_agent where no_agen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->no_agen)){
                if($request->no_agen == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_agen='$request->no_agen' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select( "select 
            no_agen,nama_agen,alamat,flag_aktif,tempat_lahir,tgl_lahir,no_hp,email,bank,cabang,norek,namarek,kode_marketing from dgw_agent where kode_lokasi='".$kode_lokasi."' $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
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
            'no_agen' => 'required',
            'nama_agen' => 'required',
            'alamat' => 'required',
            'flag_aktif' => 'required|in:1,0',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required|date_format:Y-m-d',
            'no_hp' => 'required',
            'email' => 'required|email',
            'bank' => 'required',
            'cabang' => 'required',
            'norek' => 'required',
            'namarek' => 'required',
            'kode_marketing' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_agen,$kode_lokasi)){

                $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_agent(
                no_agen,nama_agen,alamat,flag_aktif,tempat_lahir,tgl_lahir,no_hp,email,bank,cabang,norek,namarek,kode_marketing,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->no_agen,$request->nama_agen,$request->alamat,$request->flag_aktif, $request->tempat_lahir, $request->tgl_lahir,$request->no_hp,$request->email,$request->bank,$request->cabang,$request->norek,$request->namarek,$request->kode_marketing,$kode_lokasi));
                
                DB::connection('sqlsrvdago')->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Agen berhasil disimpan";
            }else{
                $success['status'] = "FAILED";
                $success['message'] = "Error : Duplicate entry. No Agent sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Agen gagal disimpan ".$e;
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
            'no_agen' => 'required',
            'nama_agen' => 'required',
            'alamat' => 'required',
            'flag_aktif' => 'required|in:1,0',
            'tempat_lahir' => 'required',
            'tgl_lahir' => 'required|date_format:Y-m-d',
            'no_hp' => 'required',
            'email' => 'required|email',
            'bank' => 'required',
            'cabang' => 'required',
            'norek' => 'required',
            'namarek' => 'required',
            'kode_marketing' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_agent')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_agen', $request->no_agen)
            ->delete();

            $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_agent(
                no_agen,nama_agen,alamat,flag_aktif,tempat_lahir,tgl_lahir,no_hp,email,bank,cabang,norek,namarek,kode_marketing,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->no_agen,$request->nama_agen,$request->alamat,$request->flag_aktif, $request->tempat_lahir, $request->tgl_lahir,$request->no_hp,$request->email,$request->bank,$request->cabang,$request->norek,$request->namarek,$request->kode_marketing,$kode_lokasi));
            
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Agen berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Agen gagal diubah ".$e;
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
            'no_agen' => 'required'
        ]);
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_agent')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_agen', $request->no_agen)
            ->delete();

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Agen berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Agen gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
