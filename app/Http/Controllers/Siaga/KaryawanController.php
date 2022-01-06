<?php

namespace App\Http\Controllers\Siaga;

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
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.nik,a.nama,a.grade as band,a.jabatan,a.status,a.no_telp,a.no_hp,a.email,a.alamat,a.kode_pp+' - '+b.nama as pp 
            from karyawan a inner join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi  
            where a.flag_aktif='1' and a.status<>'CLIENT' and a.kode_lokasi='".$kode_lokasi."' order by a.nik
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
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'kode_pp' => 'required',
            'npwp' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'status' => 'required',
            'grade' => 'required',
            'kota' => 'required',
            'kode_pos' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'kode_jab' => 'required',
            'tgl_lahir' => 'required',
            'sts_sdm' => 'required',
            'nik2' => 'required',
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
                if(Storage::disk('s3')->exists('siaga/'.$foto)){
                    Storage::disk('s3')->delete('siaga/'.$foto);
                }
                Storage::disk('s3')->put('siaga/'.$foto,file_get_contents($file));
            }else{

                $foto="-";
            }           

            $ins = DB::connection($this->db)->insert('insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp,npwp,bank,cabang,no_rek,nama_rek,status,grade,kota,kode_pos,no_hp,flag_aktif,foto,sts_pj,kode_jab,tgl_lahir,sts_sdm,nik2) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$kode_lokasi,$request->input('nama'),$request->input('alamat'),$request->input('jabatan'),$request->input('no_telp'),$request->input('email'),$request->input('kode_pp'),$request->input('npwp'),$request->input('bank'),$request->input('cabang'),$request->input('no_rek'),$request->input('nama_rek'),$request->input('status'),$request->input('band'),$request->input('kota'),$request->input('kode_pos'),$request->input('no_hp'),$request->input('flag_aktif'),$foto,1,$request->input('kode_jab'),$request->input('tgl_lahir'),$request->input('sts_sdm'),$request->input('nik2')]);

            $ins = DB::connection($this->db)->insert("insert into karyawan_pp(nik,kode_lokasi,kode_pp) values (?, ?, ?)",array($request->input('nik'),$kode_lokasi,$request->input('kode_pp')));
            
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

            $url = url('api/siaga-auth/storage');

            $sql = "select * 
            from gr_karyawan 
            where nik ='".$nik."'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql = "select * 
            from karyawan 
            where nik ='".$nik."'
            ";
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['data2'] = [];
                $success['sql'] = $sql;
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['data2'] = [];
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
            'nik' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'jabatan' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'kode_pp' => 'required',
            'npwp' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'status' => 'required',
            'grade' => 'required',
            'kota' => 'required',
            'kode_pos' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'kode_jab' => 'required',
            'tgl_lahir' => 'required',
            'sts_sdm' => 'required',
            'nik2' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:3072'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($request->hasfile('foto')){

                $sql = "select foto as file_gambar from karyawan where kode_lokasi='".$kode_lokasi."' and nik='$nik' 
                ";
                $res = DB::connection($this->db)->select($sql);
                $res = json_decode(json_encode($res),true);

                if(count($res) > 0){
                    $foto = $res[0]['file_gambar'];
                    if($foto != ""){
                        Storage::disk('s3')->delete('siaga/'.$foto);
                    }
                }else{
                    $foto = "-";
                }
                
                $file = $request->file('foto');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('siaga/'.$foto)){
                    Storage::disk('s3')->delete('siaga/'.$foto);
                }
                Storage::disk('s3')->put('siaga/'.$foto,file_get_contents($file));
                
            }else{

                $foto="-";
            }
            
            $del = DB::connection($this->db)->table('karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();
            $del2 = DB::connection($this->db)->table('karyawan_pp')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            $ins = DB::connection($this->db)->insert('insert into karyawan(nik,kode_lokasi,nama,alamat,jabatan,no_telp,email,kode_pp,npwp,bank,cabang,no_rek,nama_rek,status,grade,kota,kode_pos,no_hp,flag_aktif,foto,sts_pj,kode_jab,tgl_lahir,sts_sdm,nik2) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$kode_lokasi,$request->input('nama'),$request->input('alamat'),$request->input('jabatan'),$request->input('no_telp'),$request->input('email'),$request->input('kode_pp'),$request->input('npwp'),$request->input('bank'),$request->input('cabang'),$request->input('no_rek'),$request->input('nama_rek'),$request->input('status'),$request->input('band'),$request->input('kota'),$request->input('kode_pos'),$request->input('no_hp'),$request->input('flag_aktif'),$foto,1,$request->input('kode_jab'),$request->input('tgl_lahir'),$request->input('sts_sdm'),$request->input('nik2')]);


            $ins2 = DB::connection($this->db)->insert("insert into karyawan_pp(nik,kode_lokasi,kode_pp) values (?, ?, ?)",array($request->input('nik'),$kode_lokasi,$request->input('kode_pp')));

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
            
            $del = DB::connection($this->db)->table('karyawan')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();
            $del2 = DB::connection($this->db)->table('karyawan_pp')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

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

    public function getPP()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_pp,nama from pp where flag_aktif='1' and kode_lokasi='".$kode_lokasi."'
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

    public function getStsSDM()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select sts_sdm, nama from gr_status_sdm where kode_lokasi='".$kode_lokasi."' union select '-','-' 
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

    public function getGrKaryawan()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik,nama from gr_karyawan where kode_lokasi='".$kode_lokasi."'
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

}
