<?php

namespace App\Http\Controllers\Sai;

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
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from sai_karyawan where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->sql)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/sai-auth/storage');
            $filter = "";
            if(isset($request->nik)){
                if($request->nik == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and nik='$request->nik' ";
                }   
                $sql = "select nik,nama,case when foto != '-' then '".$url."/'+foto else '-' end as file_gambar,email,no_telp from sai_karyawan where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                
                $sql = "select nik,nama,email,no_telp from sai_karyawan where kode_lokasi='".$kode_lokasi."' ";
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
            'nik' => 'required',
            'nama' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->nik)){

                if($request->hasfile('file_gambar')){
                    $file = $request->file('file_gambar');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $filetype =  $file->getmimeType();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('sai/'.$foto)){
                        Storage::disk('s3')->delete('sai/'.$foto);
                    }
                    Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                }else{
                    
                    $foto="-";
                    $filetype = "-";
                }

                $ins = DB::connection($this->sql)->insert("insert into sai_karyawan(nik,nama,kode_lokasi,foto,email,no_telp) values 
                ('$request->nik','$request->nama','$kode_lokasi',
                '$foto','$request->email','".$request->no_telp."')");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. NIK sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal disimpan ".$e;
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
            'nik' => 'required',
            'nama' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select foto as file_gambar from sai_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }
            
            if($request->hasfile('file_gambar')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                $filetype = $file->getmimeType();
                if(Storage::disk('s3')->exists('sai/'.$foto)){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
                Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                
            }
            
            $del = DB::connection($this->sql)->table('sai_karyawan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik', $request->nik)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into sai_karyawan(nik,nama,kode_lokasi,foto,email,no_telp) values 
            ('$request->nik','$request->nama','$kode_lokasi',
            '$foto','$request->email','".$request->no_telp."')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal diubah ".$e;
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
            'nik' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select foto as file_gambar from sai_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
            }

            $del = DB::connection($this->sql)->table('sai_karyawan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('nik', $request->nik)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
