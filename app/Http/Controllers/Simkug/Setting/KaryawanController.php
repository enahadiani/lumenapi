<?php

namespace App\Http\Controllers\Simkug\Setting;

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
    public $db = 'dbsimkug';
    public $guard = 'simkug';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from karyawan where nik = '".$isi."' ";
    
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

            $res = DB::connection($this->db)->select("select nik,nama,kode_lokasi,jabatan,no_telp,email,kode_pp from karyawan 
            where flag_aktif=1
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
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'status' => 'required',
            'flag_aktif' => 'required',
            'email' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'ttd' => 'file|image|mimes:jpeg,png,jpg|max:2048'
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
                if(Storage::disk('s3')->exists('pajak/'.$foto)){
                    Storage::disk('s3')->delete('pajak/'.$foto);
                }
                Storage::disk('s3')->put('pajak/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }

            if($request->hasfile('ttd')){
                $file = $request->file('ttd');
                
                $nama_ttd = "ttd_".$request->input('nik').".".$file->getClientOriginalExtension();
                // $picName = uniqid() . '_' . $picName;
                $ttd = $nama_ttd;
                if(Storage::disk('s3')->exists('pajak/'.$ttd)){
                    Storage::disk('s3')->delete('pajak/'.$ttd);
                }
                Storage::disk('s3')->put('pajak/'.$ttd,file_get_contents($file));
            }else{

                $ttd="-";
            }

            if(isset($request->kode_jab)){
                $kode_jab = $request->input('kode_jab');
            }else{
                $kode_jab = "-";
            }
   
            $ins = DB::connection($this->db)->insert("insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp, status, no_hp,flag_aktif,foto,ttd,kode_jab) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->input('nik'),$request->input('kode_lokasi'),$request->input('nama'),$request->input('alamat'),$request->input('jabatan'),$request->input('no_telp'),$request->input('email'),$request->input('kode_pp'),'-',$request->input('no_hp'),$request->input('flag_aktif'),$foto,$ttd,$kode_jab));
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Karyawan berhasil disimpan";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
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

            $url = url('api/bdh-auth/storage-pajak');

            $sql = "select a.nik,a.kode_lokasi,a.nama,a.alamat,a.jabatan,a.no_telp,a.email,a.kode_pp,a. status,a. no_hp,a.flag_aktif,isnull(a.kode_jab,'-') as kode_jab,case when a.foto != '-' then '".$url."/'+a.foto else '-' end as foto,case when a.ttd != '-' then '".$url."/'+a.ttd else '-' end as ttd,b.nama as nama_pp,c.nama as nama_jab,d.nama as nama_lokasi 
            from karyawan a 
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            left join apv_jab c on a.kode_jab=c.kode_jab and a.kode_lokasi=c.kode_lokasi
            left join lokasi d on a.kode_lokasi=d.kode_lokasi
            where a.nik='$request->nik' 
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
            'nik' => 'required',
            'kode_lokasi' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'no_hp' => 'required',
            'status' => 'required',
            'flag_aktif' => 'required',
            'email' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'ttd' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);


        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select foto as file_gambar from karyawan where nik='$request->nik' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if($request->hasfile('file_gambar')){


                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('pajak/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('pajak/'.$foto)){
                    Storage::disk('s3')->delete('pajak/'.$foto);
                }
                Storage::disk('s3')->put('pajak/'.$foto,file_get_contents($file));
                
            }else{
                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                }else{
                    $foto = "-";
                }
            }

            $sql = "select ttd from karyawan where nik='$request->nik' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            if($request->hasfile('ttd')){
                if(count($res) > 0){
                    $ttd = $res[0]['ttd'];
                    if($ttd != ""){
                        Storage::disk('s3')->delete('pajak/'.$ttd);
                    }
                }else{
                    $ttd = "-";
                }
                
                $file = $request->file('ttd');
                
                $nama_ttd = "ttd_".$request->input('nik').".".$file->getClientOriginalExtension();
                $ttd = $nama_ttd;
                if(Storage::disk('s3')->exists('pajak/'.$ttd)){
                    Storage::disk('s3')->delete('pajak/'.$ttd);
                }
                Storage::disk('s3')->put('pajak/'.$ttd,file_get_contents($file));
                
            }else{
                if(count($res) > 0){
                    $ttd = $res[0]['ttd'];
                }else{
                    $ttd = "-";
                }
            }

            if(isset($request->kode_jab)){
                $kode_jab = $request->input('kode_jab');
            }else{
                $kode_jab = "-";
            }

            
            $del = DB::connection($this->db)->table('karyawan')->where('nik', $request->nik)->delete();

            $ins = DB::connection($this->db)->insert("insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp, status, no_hp,flag_aktif,foto,ttd,kode_jab) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->input('nik'),$request->input('kode_lokasi'),$request->input('nama'),$request->input('alamat'),$request->input('jabatan'),$request->input('no_telp'),$request->input('email'),$request->input('kode_pp'),'-',$request->input('no_hp'),$request->input('flag_aktif'),$foto,$ttd,$kode_jab));

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['kode'] = $request->nik;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('karyawan')->where('nik', $request->nik)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    function getLokasi(request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            if(isset($request->kode_lokasi)){
                $filter = " where a.kode_lokasi='$request->kode_lokasi' ";
            }else{
                $filter = "";
            }
            $sql="select a.kode_lokasi,a.nama from lokasi a $filter ";
            $res = DB::connection($this->db)->select($sql);
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

}
