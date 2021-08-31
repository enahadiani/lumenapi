<?php

namespace App\Http\Controllers\Simlog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    public function index(Request $request)
    {
        $this->validate($request,[
            'kode_klp' => 'required'
        ]);
        try {

            $menu = DB::connection($this->db)->select("select *,(rowindex*100)+rowindex as nu from menu where kode_klp='$request->kode_klp' order by rowindex");
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

    public function getKlp(Request $request)
    {
        try {

            $res = DB::connection($this->db)->select("select kode_klp from menu_klp");
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
            'kode_klp' => 'required',
            'kode_menu' => 'required',
            'level_menu' => 'required',
            'link' => 'required',
            'nama' => 'required',
            'nu' => 'required',
            'jenis_menu' => 'required',
            'icon' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_klp = $request->kode_klp;
            $del = DB::connection($this->db)->table('menu_tmp')
            ->where('kode_klp', $kode_klp)
            ->delete(); 

            //insert menu tmp
            $menu_tmp = DB::connection($this->db)->update("insert into menu_tmp
            select kode_menu, kode_form, kode_klp, nama, level_menu, (rowindex*100)+rowindex  as rowindex, jenis_menu, icon
            from menu 
            where kode_klp='$kode_klp'
            order by rowindex");

            //insert 1 row to menu_tmp
            $sql = DB::connection($this->db)->insert("insert into menu_tmp (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu, icon) values ('".$request->kode_menu."','".$request->link."','".$request->kode_klp."','".$request->nama."','".$request->level_menu."','".$request->nu."','".$request->jenis_menu."','".$request->icon."')");

            // del menu
            $del = DB::connection($this->db)->table('menu')
            ->where('kode_klp', $kode_klp)
            ->delete(); 
 
            //get menu dari tmp
            $getmenu = DB::connection($this->db)->select("select kode_menu, kode_form, kode_klp, nama, level_menu, (rowindex*100)+rowindex  as rowindex, jenis_menu, icon
            from menu_tmp 
            where kode_klp='$kode_klp'
            order by rowindex"); 
            //insert menu
            $i=1;
            foreach($getmenu as $row){
                $ins = DB::connection($this->db)->insert("insert into menu (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu, icon) values ('".$row->kode_menu."','".$row->kode_form."','".$row->kode_klp."','".$row->nama."','".$row->level_menu."','".$i."','".$row->jenis_menu."','".$row->icon."')");
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
     * Display the specified resource.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function show($kode_klp)
    {
        try {

            $menu = DB::connection($this->db)->select("select a.*,b.form from menu a left join m_form b on a.kode_form=b.kode_form where a.kode_klp = '$kode_klp' and (isnull(a.jenis_menu,'-') = '-' OR a.jenis_menu = '') order by kode_klp, rowindex ");
            $menu = json_decode(json_encode($menu),true);
            
            if(count($menu) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $menu;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\DevSiswa  $DevSiswa
     * @return \Illuminate\Http\Response
     */
    public function edit(DevSiswa $DevSiswa)
    {
        //
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
            'kode_klp' => 'required',
            'kode_menu' => 'required',
            'link' => 'required',
            'nama' => 'required',
            'nu' => 'required',
            'jenis_menu' => 'required',
            'level_menu' => 'required',
            'rowindex' => 'required',
            'icon' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_klp = $request->kode_klp;
            $del = DB::connection($this->db)->table('menu')
            ->where('kode_klp', $kode_klp)
            ->where('kode_menu', $request->kode_menu)
            ->delete(); 
           
            $ins = DB::connection($this->db)->insert("insert into menu (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu,icon) values ('".$request->kode_menu."','".$request->link."','".$request->kode_klp."','".$request->nama."','".$request->level_menu."','".$request->rowindex."','".$request->jenis_menu."','".$request->icon."')");
            
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
            'kode_menu' => 'required',
            'kode_klp' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select kode_menu,rowindex from menu where kode_menu = '".$request->kode_menu."' and kode_klp='$request->kode_klp' 
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $del = DB::connection($this->db)->table('menu')
                ->where('kode_klp', $request->kode_klp)
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
            'kode_klp' => 'required',
            'kode_menu' => 'required|array',
            'level_menu' => 'required|array',
            'kode_form' => 'required|array',
            'nama_menu' => 'required|array',
            'jenis_menu' => 'required|array',
            'icon' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_klp = $request->kode_klp;
            $req = $request->all();

            if(count($req['kode_menu']) > 0){

                $del = DB::connection($this->db)->table('menu')
                ->where('kode_klp', $kode_klp)
                ->delete(); 

                $nu=1;
                for($i=0;$i<count($req['kode_menu']);$i++){

                    $ins = DB::connection($this->db)->insert("insert into menu (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu, icon) values ('".$req['kode_menu'][$i]."','".$req['kode_form'][$i]."','".$kode_klp."','".$req['nama_menu'][$i]."','".$req['level_menu'][$i]."','".$nu."','".$req['jenis_menu'][$i]."','".$req['icon'][$i]."')");
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
