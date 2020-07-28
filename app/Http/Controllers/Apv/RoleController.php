<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'silo';
    public $db = 'dbsilo';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select kode_role from apv_role where kode_role = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
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
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_role,a.nama,a.kode_pp,a.bawah,a.atas,case a.modul when 'JK' then 'Justifikasi Kebutuhan' when 'JP' then 'Justifikasi Pengadaan' when 'JV' then 'Verifikasi' else '-' end as modul
            from apv_role a
            where a.kode_lokasi='".$kode_lokasi."'
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
            'kode_role' => 'required',
            'nama' => 'required',
            'kode_pp' => 'required',
            'bawah' => 'required',
            'atas' => 'required',
            'modul' => 'required',
            'detail.*.kode_jab'=> 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(!$this->isUnik($request->kode_role)){
                $tmp=" error:Duplicate Entry. Kode Role sudah ada didatabase !";
                $sts=false;
            }else{
                $sts= true;
            }
            
            if($sts){

                $ins = DB::connection($this->db)->insert('insert into apv_role (kode_role,kode_pp,nama,bawah,atas,kode_lokasi,modul) values (?, ?, ?, ?, ?, ?, ?)', [$request->input('kode_role'),$request->input('kode_pp'),$request->input('nama'),$request->input('bawah'),$request->input('atas'),$kode_lokasi,$request->input('modul')]);

                $detail = $request->input('detail');

                if(count($detail) > 0){
                    for($i=0; $i<count($detail);$i++){
                        $ins2 = DB::connection($this->db)->insert("insert into apv_role_jab (kode_lokasi,kode_role,kode_jab,no_urut) values (?, ?, ?, ?) ", [$kode_lokasi,$request->input('kode_role'),$detail[$i]['kode_jab'],$i]); 
                    }
                }
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Role berhasil disimpan";
            }else{
                $success['status'] = $sts;
                $success['message'] = $tmp;
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Role gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show($kode_role)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select kode_role,kode_pp,nama,bawah,atas,modul from apv_role where kode_lokasi='".$kode_lokasi."' and kode_role='$kode_role'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select kode_role,kode_jab,no_urut from apv_role_jab where kode_lokasi='".$kode_lokasi."' and kode_role='$kode_role'  order by no_urut
            ";
            $res2 = DB::connection($this->db)->select($sql2);
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
    public function update(Request $request, $kode_role)
    {
        $this->validate($request, [
            'nama' => 'required',
            'kode_pp' => 'required',
            'bawah' => 'required',
            'atas' => 'required',
            'modul' => 'required',
            'detail.*.kode_jab'=> 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('apv_role')->where('kode_lokasi', $kode_lokasi)->where('kode_role', $kode_role)->delete();

            $del2 = DB::connection($this->db)->table('apv_role_jab')->where('kode_lokasi', $kode_lokasi)->where('kode_role', $kode_role)->delete();

            $ins = DB::connection($this->db)->insert('insert into apv_role (kode_role,kode_pp,nama,bawah,atas,kode_lokasi,modul) values (?, ?, ?, ?, ?, ?, ?)', [$kode_role,$request->input('kode_pp'),$request->input('nama'),$request->input('bawah'),$request->input('atas'),$kode_lokasi,$request->input('modul')]);

            $detail = $request->input('detail');

            if(count($detail) > 0){
                for($i=0; $i<count($detail);$i++){
                    $ins2 = DB::connection($this->db)->insert("insert into apv_role_jab (kode_lokasi,kode_role,kode_jab,no_urut) values (?, ?, ?, ?) ", [$kode_lokasi,$kode_role,$detail[$i]['kode_jab'],$i]); 
                }
            }

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Role berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Role gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy($kode_role)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('apv_role')->where('kode_lokasi', $kode_lokasi)->where('kode_role', $kode_role)->delete();

            $del2 = DB::connection($this->db)->table('apv_role_jab')->where('kode_lokasi', $kode_lokasi)->where('kode_role', $kode_role)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Role berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Role gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

}
