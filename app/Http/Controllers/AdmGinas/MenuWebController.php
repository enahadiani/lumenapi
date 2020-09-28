<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class MenuWebController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    
    public $db = 'dbsaife';
    public $guard = 'admginas';

    public function index(Request $request)
    {
        
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $menu = DB::connection($this->db)->select("select *,(nu*100)+nu as nu2 from lab_konten_menu where kode_lokasi='$kode_lokasi' order by nu");
            $menu = json_decode(json_encode($menu),true);
            
            if(count($menu) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $menu;
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

    public function getForm(Request $request)
    {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            SELECT DISTINCT kode_form as id, nama FROM lab_form where id_form like 'trengginas%' 
            UNION
            SELECT CONVERT(VARCHAR, id) as id, judul as nama FROM lab_konten where kode_lokasi='$kode_lokasi' and kode_klp = 'KLP02'");
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_menu' =>'required',
            'nama' =>'required',
            'link' =>'required',
            'jenis' =>'required',
            'level_menu' =>'required',
            'nu' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('lab_konten_menu_tmp')
            ->where('kode_lokasi', $kode_lokasi)
            ->delete(); 

            //insert menu tmp
            $menu_tmp = DB::connection($this->db)->update("insert into lab_konten_menu_tmp
            select kode_lokasi,kode_menu,nama,link,jenis,level_menu, (nu*100)+nu  as nu
            from lab_konten_menu 
            where kode_lokasi='$kode_lokasi'
            order by nu");

            //insert 1 row to menu_tmp
            $sql = DB::connection($this->db)->insert("insert into lab_konten_menu_tmp (kode_lokasi,kode_menu,nama,link,jenis,level_menu,nu) values ('".$kode_lokasi."','".$request->kode_menu."','".$request->nama."','".$request->link."','".$request->jenis."','".$request->level_menu."','".$request->nu."')");

            // del menu
            $del = DB::connection($this->db)->table('lab_konten_menu')
            ->where('kode_lokasi', $kode_lokasi)
            ->delete(); 
 
            //get menu dari tmp
            $getmenu = DB::connection($this->db)->select("select kode_lokasi,kode_menu,nama,link,jenis,level_menu, (nu*100)+nu  as nu
            from lab_konten_menu_tmp 
            where kode_lokasi='$kode_lokasi'
            order by nu"); 
            //insert menu
            $i=1;
            foreach($getmenu as $row){
                $ins = DB::connection($this->db)->insert("insert into lab_konten_menu (kode_lokasi,kode_menu,nama,link,jenis,level_menu,nu) values ('".$kode_lokasi."','".$row->kode_menu."','".$row->nama."','".$row->link."','".$row->jenis."','".$row->level_menu."','".$i."')");
                $i++;
            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Menu berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Menu gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
                
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_menu' =>'required',
            'nama' =>'required',
            'link' =>'required',
            'jenis' =>'required',
            'level_menu' =>'required',
            'nu' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->db)->table('lab_konten_menu')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_menu', $kode_menu)
            ->delete(); 

            $ins = DB::connection($this->db)->insert("insert into lab_konten_menu (kode_lokasi,kode_menu,nama,link,jenis,level_menu,nu) values ('".$kode_lokasi."','".$row->kode_menu."','".$row->nama."','".$row->link."','".$row->jenis."','".$row->level_menu."','".$request->nu."')");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Menu berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Menu gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
                
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_menu' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select kode_menu,nu from lab_konten_menu where kode_menu = '".$request->kode_menu."' and kode_lokasi='$kode_lokasi' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $del = DB::connection($this->db)->table('lab_konten_menu')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('kode_menu', $request->kode_menu)
                ->delete();
               
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Menu berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Menu gagal dihapus";
            }
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Menu gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function simpanMove(Request $request)
    {
        $this->validate($request, [
            'kode_menu' => 'required|array',
            'level_menu' => 'required|array',
            'link' => 'required|array',
            'nama' => 'required|array',
            'jenis' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $req = $request->all();

            if(count($req['kode_menu']) > 0){

                $del = DB::connection($this->db)->table('lab_konten_menu')
                ->where('kode_lokasi', $kode_lokasi)
                ->delete(); 

                $nu=1;
                for($i=0;$i<count($req['kode_menu']);$i++){

                    $ins = DB::connection($this->db)->insert("insert into lab_konten_menu (kode_lokasi,kode_menu,nama,link,jenis,level_menu,nu) values ('".$kode_lokasi."','".$req['kode_menu'][$i]."','".$req['nama'][$i]."','".$req['link'][$i]."','".$req['jenis'][$i]."',".$req['level_menu'][$i].",$nu)");
                    $nu++;
                }

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Menu berhasil disimpan";
                
            }else{
                $success['status'] = true;
                $success['message'] = "Error data kosong!";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Menu gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
                
    }

}
