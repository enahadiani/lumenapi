<?php

namespace App\Http\Controllers\Gl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $rs = DB::connection('sqlsrv2')->select("select kode_pp,nama,flag_aktif from pp	where kode_lokasi='$kode_lokasi'");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $rs;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
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
            'kode_pp' => 'required',
            'nama' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $ins = DB::connection('sqlsrv2')->insert('insert into pp (kode_pp,kode_lokasi,nama,flag_aktif) values (?, ?, ?, ?)', [$request->input('kode_pp'),$kode_lokasi,$request->input('nama'),$request->input('flag_aktif')]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data PP berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data PP gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($kode_pp)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }

            $rs = DB::connection('sqlsrv2')->select("select kode_pp,nama,flag_aktif from pp where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp'				 
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $rs;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
    public function update(Request $request, $kode_pp)
    {
        $this->validate($request, [
            'nama' => 'required',
            'flag_aktif' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $del = DB::connection('sqlsrv2')->table('pp')->where('kode_lokasi', $kode_lokasi)->where('kode_pp', $kode_pp)->delete();

            $ins = DB::connection('sqlsrv2')->insert('insert into pp (kode_pp,kode_lokasi,nama,flag_aktif) values (?, ?, ?, ?)', [$kode_pp,$kode_lokasi,$request->input('nama'),$request->input('flag_aktif')]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data PP berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data PP gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_pp)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '34';
            }
            
            $del = DB::connection('sqlsrv2')->table('pp')->where('kode_lokasi', $kode_lokasi)->where('kode_pp', $kode_pp)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data PP berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data PP gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
