<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class GaleriController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $sql = 'dbsaife';
    public $guard = 'admginas';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->id)){
                if($request->id == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.id='$request->id' ";
                }
                $sql= "select a.id,a.kode_lokasi,a.nama,a.keterangan,a.file_gambar,a.flag_aktif,a.jenis,a.nik_user,a.tgl_input,a.file_type,a.kode_ktg,b.nama as nama_ktg from lab_konten_galeri a
                left join lab_konten_ktg b on a.kode_ktg=b.kode_ktg and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "SELECT a.id,a.kode_lokasi,a.nama,a.keterangan,a.file_gambar,a.flag_aktif,a.jenis,a.nik_user,a.file_type,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input,a.kode_ktg,b.nama as nama_ktg FROM lab_konten_galeri a
                left join lab_konten_ktg b on a.kode_ktg=b.kode_ktg and a.kode_lokasi=b.kode_lokasi
                where a.kode_lokasi='".$kode_lokasi."'
                ";
            }

            $success['req'] = $request->all();

            $res = DB::connection($this->sql)->select($sql);
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
            'nama' => 'required',
            'keterangan' => 'required',
            'jenis' => 'required',
            'kode_ktg' => 'required',
            'file_gambar' => 'required|file|mimes:jpeg,png,jpg,mp4,avi'
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
                $file_type = $file->getmimeType();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                $foto = "/assets/uploads/".$foto;
            }else{

                $foto="-";
                $file_type = "-";
            }

            $ins = DB::connection($this->sql)->insert("insert into lab_konten_galeri(kode_lokasi,nama,keterangan,file_gambar,flag_aktif,jenis,nik_user,tgl_input,file_type,kode_ktg) values ('".$kode_lokasi."','".$request->nama."','".$request->keterangan."','".$foto."','1','".$request->jenis."','".$nik."',getdate(),'".$file_type."','".$request->kode_ktg."') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = '';
            $success['message'] = "Data Galeri berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Galeri gagal disimpan ".$e;
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
            'id' => 'required',
            'nama' => 'required',
            'keterangan' => 'required',
            'jenis' => 'required',
            'kode_ktg' => 'required',
            'file_gambar' => 'required|file|mimes:jpeg,png,jpg,mp4,avi'
        ]);


        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select file_gambar from lab_konten_galeri where kode_lokasi='".$kode_lokasi."' and id='$request->id' 
                ";
                $res = DB::connection($this->sql)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    if($res[0]['file_gambar'] != "-"){

                        $tmp = explode("/",$res[0]['file_gambar']);
                        $foto = $tmp[3];
                        if($foto != ""){
                            Storage::disk('s3')->delete('webginas/'.$foto);
                        }
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('webginas/'.$foto)){
                    Storage::disk('s3')->delete('webginas/'.$foto);
                }
                Storage::disk('s3')->put('webginas/'.$foto,file_get_contents($file));
                
                $file_type = $file->getmimeType();
                $foto = "/assets/uploads/".$foto;
            }else{

                $foto="-";
                $file_type = "-";
            }
            
            $update = DB::connection($this->sql)->update("update lab_konten_galeri set nama='$request->nama',keterangan='$request->keterangan',file_gambar='$foto',jenis='$request->jenis',file_type='$file_type',kode_ktg='$request->kode_ktg' where kode_lokasi='$kode_lokasi' and id='$request->id' ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->id;
            $success['message'] = "Data Galeri berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Galeri gagal diubah ".$e;
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
            'id' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('lab_konten_galeri')->where('kode_lokasi', $kode_lokasi)->where('id', $request->id)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Galeri berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Galeri gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
