<?php

namespace App\Http\Controllers\Sukka;

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
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik,nama,kode_pp,kode_jab,email,no_telp from apv_karyawan where kode_lokasi='".$kode_lokasi."'
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
            'nik' => 'required|max:20',
            'nama' => 'required|max:150',
            'kode_pp' => 'required|max:10',
            'kode_jab' => 'required|max:10',
            'email' => 'required|max:50',
            'no_telp' => 'required|max:20',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('foto')){
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                // $picName = uniqid() . '_' . $picName;
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sukka/'.$foto)){
                    Storage::disk('s3')->delete('sukka/'.$foto);
                }
                Storage::disk('s3')->put('sukka/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }


            $ins = DB::connection($this->db)->insert('insert into apv_karyawan (nik,nama,kode_lokasi,kode_pp,kode_jab,foto,email,no_telp) values (?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_pp'),$request->input('kode_jab'),$foto,$request->input('email'),$request->input('no_telp')]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
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
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/sukka-auth/storage');

            $sql = "select nik,nama,kode_pp,kode_jab,case when foto != '-' then '".$url."/'+foto else '-' end as file_gambar,email,no_telp from apv_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
            ";
            $res = DB::connection($this->db)->select($sql);
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
            'nama' => 'required|max:150',
            'kode_pp' => 'required|max:10',
            'kode_jab' => 'required|max:10',
            'email' => 'required|max:50',
            'no_telp' => 'required|max:20',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('foto')){

                $sql = "select foto as file_gambar from apv_karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('sukka/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('sukka/'.$foto)){
                    Storage::disk('s3')->delete('sukka/'.$foto);
                }
                Storage::disk('s3')->put('sukka/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            $del = DB::connection($this->db)->table('apv_karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            
            if(isset($request->kode_kota)){
                $kode_kota = $request->kode_kota;
            }else{
                $kode_kota = "-";
            }

            if(isset($request->nama_kota)){
                $nama_kota = $request->nama_kota;
            }else{
                $nama_kota = "-";
            }

            if(isset($request->kode_divisi)){
                $kode_divisi = $request->kode_divisi;
            }else{
                $kode_divisi = "-";
            }

            $ins = DB::connection($this->db)->insert('insert into apv_karyawan (nik,nama,kode_lokasi,kode_pp,kode_jab,foto,email,no_telp) values (?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_pp'),$request->input('kode_jab'),$foto,$request->input('email'),$request->input('no_telp')]);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('apv_karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
