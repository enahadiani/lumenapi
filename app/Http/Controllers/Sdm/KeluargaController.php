<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KeluargaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $sql = 'sqlsrvtarbak';
    public $guard = 'tarbak';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select nama,case when jk = 'P' then 'Perempuan' else 'Laki-laki' end as jk,case when status_kes = 'Y' then 'Ya' else 'Tidak' end as status_kes,case when jenis = 'I' then 'Istri' when jenis= 'A' then 'Anak' else 'Suami' end as jenis,tempat,convert(varchar,tgl_lahir,103) as tgl from hr_keluarga where kode_lokasi='".$kode_lokasi."' and nik='$nik'
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
            'jenis' => 'required',
            'nama' => 'required',
            'jk' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'status_kes' => 'required',
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
                if(Storage::disk('s3')->exists('sdm/'.$foto)){
                    Storage::disk('s3')->delete('sdm/'.$foto);
                }
                Storage::disk('s3')->put('sdm/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }
            
            $sqlnu= "select max(nu) as nu from hr_keluarga where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
            $rsnu=DB::connection($this->sql)->select($sqlnu);

            if(count($rsnu) > 0){
                $nu = $rsnu[0]->nu + 1;
            }else{
                $nu = 0;
            }

            $ins = DB::connection($this->sql)->insert("insert into hr_keluarga(nik,kode_lokasi,nu,jenis,nama,jk,tempat,tgl_lahir,status_kes,foto) values ('".$request->nik."','".$kode_lokasi."',".$nu.",'".$request->jenis."','".$request->nama."','".$request->jk."','".$request->tempat."','".$request->tgl_lahir."','".$request->status_kes."','".$foto."') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Keluarga berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Keluarga gagal disimpan ".$e;
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
            'nu' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/sdm/storage');

            $sql = "select nik,kode_lokasi,nu,jenis,nama,jk,tempat,tgl_lahir,status_kes,case when foto != '-' then '".$url."/'+foto else '-' end as foto from hr_keluarga where kode_lokasi='".$kode_lokasi."' and nik='$nik' and nu='$request->nu'
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
            'nu' => 'required',
            'jenis' => 'required',
            'nama' => 'required',
            'jk' => 'required',
            'tempat' => 'required',
            'tgl_lahir' => 'required',
            'status_kes' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select foto as file_gambar from hr_keluarga where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' and nu='$request->nu'
                ";
                $res = DB::connection($this->sql)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('sdm/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sdm/'.$foto)){
                    Storage::disk('s3')->delete('sdm/'.$foto);
                }
                Storage::disk('s3')->put('sdm/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            $del = DB::connection($this->sql)->table('hr_keluarga')->where('kode_lokasi', $kode_lokasi)->where('nik', $request->nik)->where('nu', $request->nu)->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_keluarga(nik,kode_lokasi,nu,jenis,nama,jk,tempat,tgl_lahir,status_kes,foto) values ('".$request->nik."','".$kode_lokasi."',".$nu.",'".$request->jenis."','".$request->nama."','".$request->jk."','".$request->tempat."','".$request->tgl_lahir."','".$request->status_kes."','".$foto."') ");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Keluarga berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Keluarga gagal diubah ".$e;
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
            'nu' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_keluarga')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->where('nu', $request->nu)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Keluarga berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Keluarga gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
