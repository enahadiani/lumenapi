<?php

namespace App\Http\Controllers\Rtrw;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class RwController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'sqlsrvrtrw';
    public $guard = 'rtrw';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from lokasi where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->db)->select($strSQL);
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

            $res = DB::connection($this->db)->select("select a.kode_lokasi,a.nama,a.rw as kode_rw,a.logo,a.kode_desa,b.nama as nama_desa,b.kode_camat,c.kode_kota,d.kode_prop,c.nama as nama_camat,d.nama as nama_kota,e.nama as nama_prop 
            from lokasi a 
            left join rt_desa b on a.kode_desa=b.kode_desa 
            left join rt_camat c on b.kode_camat=c.kode_camat
            left join rt_kota d on c.kode_kota=d.kode_kota
            left join rt_prop e on d.kode_prop=e.kode_prop
            
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'kode_rw' => 'required',
            'kode_desa' => 'required',
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
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            
            $ins = DB::connection($this->db)->insert("insert into lokasi(kode_lokasi,nama,rw,kode_desa,logo) values ('".$request->kode_lokasi."','".$request->nama."','".$request->kode_rw."','".$request->kode_desa."','".$foto."') ");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data RW berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data RW gagal disimpan ".$e;
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
            'kode_lokasi' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/rtrw/storage');

            $sql = "
            select a.kode_lokasi,a.nama,a.rw as kode_rw,case when logo != '-' then '".$url."/'+logo else '-' end as logo,a.kode_desa,b.nama as nama_desa,b.kode_camat,c.kode_kota,d.kode_prop,c.nama as nama_camat,d.nama as nama_kota,e.nama as nama_prop
            from lokasi a 
            left join rt_desa b on a.kode_desa=b.kode_desa 
            left join rt_camat c on b.kode_camat=c.kode_camat
            left join rt_kota d on c.kode_kota=d.kode_kota
            left join rt_prop e on d.kode_prop=e.kode_prop
            where kode_lokasi='".$request->kode_lokasi."' 
            ";
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'kode_rw' => 'required',
            'kode_desa' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('file_gambar')){

                $sql = "select logo as file_gambar from lokasi where kode_lokasi='".$request->kode_lokasi."' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            // $del = DB::connection($this->db)->table('lokasi')->where('kode_lokasi', $request->kode_lokasi)->delete();

            $ins = DB::connection($this->db)->update("update lokasi set nama='".$request->nama."',rw='".$request->kode_rw."',kode_desa ='".$request->kode_desa."',logo='".$foto."' where kode_lokasi='".$request->kode_lokasi."' ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data RW berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data RW gagal diubah ".$e;
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
            'kode_lokasi' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('lokasi')->where('kode_lokasi', $request->kode_lokasi)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data RW berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data RW gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
