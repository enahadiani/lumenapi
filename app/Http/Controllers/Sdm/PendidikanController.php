<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class PendidikanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'dbtoko';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "select a.nama, a.tahun, a.kode_jurusan,a.kode_strata, b.nama as nama_jur,c.nama as nama_str, isnull(a.setifikat,'-') as serti 
            from hr_pendidikan a 
            inner join hr_jur b on a.kode_jurusan =b.kode_jur and a.kode_lokasi=b.kode_lokasi 
            inner join hr_strata c on a.kode_strata =c.kode_strata and a.kode_lokasi=c.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' and a.nik='$nik' ";
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama' => 'required',
            'tahun' => 'required',
            'kode_jur' => 'required',
            'kode_strata' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->db)->beginTransaction();
        
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
            
            $sqlnu= "select max(nu) as nu from hr_pendidikan where nik='$nik' and kode_lokasi='$kode_lokasi'  ";
            $rsnu=DB::connection($this->db)->select($sqlnu);

            if(count($rsnu) > 0){
                $nu = $rsnu[0]->nu + 1;
            }else{
                $nu = 0;
            }

            $sql = "insert into hr_pendidikan(nik,kode_lokasi,nu,nama,tahun,setifikat,kode_jurusan,kode_strata) values ('".$nik."','".$kode_lokasi."',".$nu.",'".$request->nama."','".$request->tahun."','".$foto."','".$request->kode_jur."','".$request->kode_strata."')";

            $ins = DB::connection($this->db)->insert($sql);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Pendidikan berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pendidikan gagal disimpan ".$e;
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
            
            $sql= "select nik,nama,nu,penyelenggara,convert(varchar,tgl_mulai,23) as tglm,convert(varchar,tgl_selesai,23) as tgls,case when foto != '-' then '".$url."/'+foto else '-' end as foto from hr_pendidikan 
            where kode_lokasi='$kode_lokasi' and nik='$nik' and nu='$request->nu' ";
            $res = DB::connection($this->db)->select($sql);
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
            'nu' => 'required',
            'nama' => 'required',
            'tahun' => 'required',
            'kode_jur' => 'required',
            'kode_strata' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select foto as file_gambar from hr_pendidikan where kode_lokasi='".$kode_lokasi."' and nik='$request->nik' and nu='$request->nu'
                ";
                $res = DB::connection($this->db)->select($sql);
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
            
            $del = DB::connection($this->db)->table('hr_pendidikan')->where('kode_lokasi', $kode_lokasi)->where('nik', $request->nik)->where('nu', $request->nu)->delete();

            $sql = "insert into hr_pendidikan(nik,kode_lokasi,nu,nama,tahun,setifikat,kode_jurusan,kode_strata) values ('".$nik."','".$kode_lokasi."',".$nu.",'".$request->nama."','".$request->tahun."','".$foto."','".$request->kode_jur."','".$request->kode_strata."')";

            $ins = DB::connection($this->db)->insert($sql);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Pendidikan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pendidikan gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('hr_pendidikan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->where('nu', $request->nu)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pendidikan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pendidikan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
