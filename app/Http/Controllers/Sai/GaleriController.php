<?php

namespace App\Http\Controllers\Sai;

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
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/sai-auth/storage');
            $filter = "";
            if(isset($request->id)){
                if($request->id == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and id='$request->id' ";
                }   
                $sql = "select id, nama, keterangan as isi,kode_ktg,case when file_gambar != '-' then '".$url."/'+file_gambar else '-' end as file_gambar,file_type,jenis from sai_konten_galeri where kode_lokasi='$kode_lokasi' $filter ";
            }else{
                
                $sql = "select id, nama, case when file_gambar != '-' then '".$url."/'+file_gambar else '-' end as file_gambar from sai_konten_galeri where kode_lokasi= '".$kode_lokasi."' ";
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
            'nama' => 'required',
            'jenis' => 'required|in:Video,Konten,Galeri,Slider,Artikel',
            'isi' => 'required',
            'kode_ktg' => 'required',
            'file_gambar' => 'required|file|image|mimes:jpeg,png,jpg|max:2048'
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

            $sql="select isnull(max(id),0)+1 as id from sai_konten_galeri where kode_lokasi='".$data['kode_lokasi']."' ";
            $rs1=DB::connection($this->sql)->select($sql);
    
            $id=$rs1[0]->id;

            $ins = DB::connection($this->sql)->insert("insert into sai_konten_galeri 
            (id,kode_lokasi,nik_user,tgl_input,flag_aktif,nama,jenis,file_gambar,file_type,keterangan,kode_ktg) values 
            ('$id','$kode_lokasi','$nik',getdate(),'1','".$request->nama."','".$request->jenis."','".$foto."','".$filetype."','".$request->isi."','".$request->kode_ktg."')");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
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
            'id' => 'required',            
            'nama' => 'required',
            'jenis' => 'required',
            'isi' => 'required',
            'kode_ktg' => 'required',
            'file_gambar' => 'required|file|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select file_gambar,file_type from sai_konten_galeri where kode_lokasi='".$kode_lokasi."' and id='$request->id' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                $filetype = $res[0]['file_type'];
            }else{
                $foto = "-";
                $filetype = "-";
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
            
            $del = DB::connection($this->sql)->table('sai_konten_galeri')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id', $request->id)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into sai_konten_galeri 
            (id,kode_lokasi,nik_user,tgl_input,flag_aktif,nama,jenis,file_gambar,file_type,keterangan,kode_ktg) values 
            ('$request->id','$kode_lokasi','$nik',getdate(),'1','".$request->nama."','".$request->jenis."','".$foto."','".$filetype."','".$request->isi."','".$request->kode_ktg."')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
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
        $this->validate($request, [
            'id' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select file_gambar from sai_konten_galeri where kode_lokasi='".$kode_lokasi."' and id='$request->id' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
            }

            $del = DB::connection($this->sql)->table('sai_konten_galeri')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id', $request->id)
            ->delete();

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
