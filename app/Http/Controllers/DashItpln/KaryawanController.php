<?php

namespace App\Http\Controllers\DashItpln;

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
    public $guard = 'itpln';
    public $sql = 'dbpln';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from karyawan where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->sql)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }


    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select nik,nama,alamat,jabatan,no_telp,email,kode_pp from karyawan where kode_lokasi='".$kode_lokasi."' 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
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
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'status' => 'required',
            'flag_aktif' => 'required',
            'email' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('yakes/'.$foto)){
                    Storage::disk('s3')->delete('yakes/'.$foto);
                }
                Storage::disk('s3')->put('yakes/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            
            $ins = DB::connection($this->sql)->insert("insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp, status, no_hp,flag_aktif,foto) values ('".$request->nik."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->jabatan."','".$request->no_telp."','".$request->email."','".$request->kode_pp."','-','".$request->no_hp."','".$request->flag_aktif."','".$foto."') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Karyawan berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request,[
            'nik' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/yakes-auth/storage');

            $sql = "select nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp, status, no_hp,flag_aktif,case when foto != '-' then '".$url."/'+foto else '-' end as foto from karyawan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['sql'] = $sql;
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
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
    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'status' => 'required',
            'flag_aktif' => 'required',
            'email' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select foto as file_gambar from karyawan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' 
                ";
                $res = DB::connection($this->sql)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('yakes/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('yakes/'.$foto)){
                    Storage::disk('s3')->delete('yakes/'.$foto);
                }
                Storage::disk('s3')->put('yakes/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            $del = DB::connection($this->sql)->table('karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $request->nik)->delete();

            $ins = DB::connection($this->sql)->insert("insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp, status, no_hp,flag_aktif,foto) values ('".$request->nik."','".$kode_lokasi."','".$request->nama."','".$request->alamat."','".$request->jabatan."','".$request->no_telp."','".$request->email."','".$request->kode_pp."','-','".$request->no_hp."','".$request->flag_aktif."','".$foto."') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
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
        $this->validate($request,[
            'nik' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $request->nik)->delete();

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
