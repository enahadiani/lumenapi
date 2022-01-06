<?php

namespace App\Http\Controllers\Siaga;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HakaksesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select nik from hakakses where nik = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
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

            $res = DB::connection($this->db)->select("select nik,nama,kode_menu_saku,kode_lokasi,status_admin from hakakses where kode_lokasi='".$kode_lokasi."'
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
            'nik' => 'required|max:10',
            'nama' => 'required|max:100',
            'kode_klp_menu' => 'required|max:10',
            'path_view' => 'required|max:10',
            'pass' => 'required|max:10',
            'status_admin' => 'required|max:1',
            'klp_akses' => 'required|max:20',
            'kode_menu_saku'=> 'required|max:20',
            'menu_mobile' => 'required|max:10'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(!$this->isUnik($request->nik)){
                $tmp=" error:Duplicate Entry. NIK sudah terdaftar di database !";
                $sts=false;
            }else{
                $sts= true;
            }
            
            if($sts){
                $kode_menu_lab = (isset($request->kode_menu_lab) && $request->kode_menu_lab != '' ? $request->kode_menu_Lab : '-');
                $ins = DB::connection($this->db)->insert('insert into hakakses(nik,nama,kode_lokasi,kode_klp_menu,pass,status_admin,klp_akses,path_view,menu_mobile,kode_menu_lab,kode_menu_saku,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_klp_menu'),$request->input('pass'),$request->input('status_admin'),$request->input('klp_akses'),$request->input('path_view'),$request->input('menu_mobile'),$kode_menu_lab,$request->input('kode_menu_saku'),app('hash')->make($request->pass)]);
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Hakakses berhasil disimpan";
            }else{
                $success['status'] = $sts;
                $success['message'] = $tmp;
            }
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal disimpan ".$e;
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

            $sql = "select a.nik,a.nama,a.kode_klp_menu,a.kode_menu_lab, a.kode_menu_saku,a.pass,a.status_admin,a.klp_akses,a.menu_mobile,a.path_view,a.kode_menu_saku,b.nama as nama_klp,c.nama as nama_klp2,d.nama as nama_klp3,e.nama_form as nama_form,f.nama_form as nama_form2 
            from hakakses a 
            left join menu_klp b on a.kode_klp_menu=b.kode_klp
            left join menu_klp c on a.kode_menu_lab=c.kode_klp
            left join menu_klp d on a.kode_menu_saku=d.kode_klp
            left join m_form e on a.path_view=e.kode_form
            left join m_form f on a.menu_mobile=f.kode_form
            where a.kode_lokasi='".$kode_lokasi."' and a.nik='$nik'
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
            'nama' => 'required|max:100',
            'kode_klp_menu' => 'required|max:10',
            'path_view' => 'required|max:10',
            'pass' => 'required|max:10',
            'status_admin' => 'required|max:1',
            'klp_akses' => 'required|max:20',
            'kode_menu_saku'=> 'required|max:20',
            'menu_mobile' => 'required|max:10'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('hakakses')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            $kode_menu_lab = (isset($request->kode_menu_lab) && $request->kode_menu_lab != '' ? $request->kode_menu_Lab : '-');
            $ins = DB::connection($this->db)->insert('insert into hakakses(nik,nama,kode_lokasi,kode_klp_menu,pass,status_admin,klp_akses,path_view,menu_mobile,kode_menu_lab,kode_menu_saku,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->input('nik'),$request->input('nama'),$kode_lokasi,$request->input('kode_klp_menu'),$request->input('pass'),$request->input('status_admin'),$request->input('klp_akses'),$request->input('path_view'),$request->input('menu_mobile'),$kode_menu_lab,$request->input('kode_menu_saku'),app('hash')->make($request->pass)]);

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil diubah";
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal diubah ".$e;
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
            
            $del = DB::connection($this->db)->table('hakakses')->where('kode_lokasi', $kode_lokasi)->where('nik', $nik)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Hakakses berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Hakakses gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function getMenu()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_klp,nama from menu_klp
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

    public function getForm()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_form,nama_form from m_form 
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

    public function getSideMenu($kode_klp)
    {
        try {

            $menu = DB::connection($this->db)->select("select a.*,b.form from menu a left join m_form b on a.kode_form=b.kode_form where a.kode_klp = '$kode_klp' and (isnull(a.jenis_menu,'-') = '-' OR a.jenis_menu = '') order by kode_klp, rowindex ");
            $menu = json_decode(json_encode($menu),true);
            
            if(count($menu) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $menu;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
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
