<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KaryawanController extends Controller
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
            }

            $res = DB::connection('sqlsrv2')->select("select nik,nama,kode_pp,kode_jab,email,no_telp from apv_karyawan where kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
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
            'nik' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'kode_jab' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->foto)){
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('local')->exists($foto)){
                    Storage::disk('local')->delete($foto);
                }
                Storage::disk('local')->put($foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            
            $ins = DB::connection('sqlsrv2')->insert('insert into apv_karyawan (nik,nama,kode_lokasi,kode_pp,kode_jab,foto,email,no_telp) values (?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_pp'),$request->input('kode_jab'),$foto,$request->input('email'),$request->input('no_telp')]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($nik)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/apv/storage');

            $sql = "select nik,nama,kode_pp,kode_jab,case when foto != '-' then '".$url."/'+foto else '-' end as file_gambar,email,no_telp from apv_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
            ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['sql'] = $sql;
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
    public function update(Request $request, $nik)
    {
        $this->validate($request, [
            'nama' => 'required',
            'kode_pp' => 'required',
            'kode_jab' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->foto)){

                if($request->foto == ""){
                    $sql = "select foto as file_gambar where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                    ";
                    $res = DB::connection('sqlsrv2')->select($sql);
                    $res = json_decode(json_encode($res),true);
                    $foto = $res[0]['file_gambar'];
                }else{

                    $file = $request->file('foto');
            
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('local')->exists($foto)){
                        Storage::disk('local')->delete($foto);
                    }
                    Storage::disk('local')->put($foto,file_get_contents($file));
                }
            }else{

                $foto="-";
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            $ins = DB::connection('sqlsrv2')->insert('insert into apv_karyawan (nik,nama,kode_lokasi,kode_pp,kode_jab,foto,email,no_telp) values (?, ?, ?, ?, ?, ?, ?, ?)', [$nik,$request->input('nama'),$kode_lokasi,$request->input('kode_pp'),$request->input('kode_jab'),$foto,$request->input('email'),$request->input('no_telp')]);

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($nik)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrv2')->table('apv_karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
