<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    
    public $db = 'tokoaws';
    public $guard = 'toko';

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

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_klp = $request->kode_klp;
            $del = DB::connection($this->sql)->table('menu_tmp')
            ->where('kode_klp', $kode_klp)
            ->delete(); 

            //insert menu tmp
            $menu_tmp = DB::connection($this->sql)->update("insert into menu_tmp
            select kode_menu, kode_form, kode_klp, nama, level_menu, (rowindex*100)+rowindex  as rowindex, jenis_menu, icon
            from menu 
            where kode_klp='$kode_klp'
            order by rowindex");

            //insert 1 row to menu_tmp
            $sql = DB::connection($this->sql)->insert("insert into menu_tmp (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu, icon) values ('".$request->kode_menu."','".$request->link."','".$request->kode_klp."','".$request->nama."','".$request->level_menu."','".$request->nu."','".$request->jenis_menu."','".$request->icon."')");

            // del menu
            $del = DB::connection($this->sql)->table('menu')
            ->where('kode_klp', $kode_klp)
            ->delete(); 
 
            //get menu dari tmp
            $getmenu = DB::connection($this->sql)->select("select kode_menu, kode_form, kode_klp, nama, level_menu, (rowindex*100)+rowindex  as rowindex, jenis_menu, icon
            from menu_tmp 
            where kode_klp='$kode_klp'
            order by rowindex"); 
            //insert menu
            $i=1;
            foreach($getmenu as $menu){
                $ins = DB::connection($this->sql)->insert("insert into menu (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu, icon) values ('".$row->kode_menu."','".$row->kode_form."','".$row->kode_klp."','".$row->nama."','".$row->level_menu."','".$i."','".$row->jenis_menu."','".$row->icon."')");
                $i++;
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Menu berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
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

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_klp = $request->kode_klp;
            $del = DB::connection($this->sql)->table('menu')
            ->where('kode_klp', $kode_klp)
            ->where('kode_menu', $request->kode_menu)
            ->delete(); 
           
            $ins = DB::connection($this->sql)->insert("insert into menu (kode_menu,kode_form,kode_klp,nama,level_menu,rowindex,jenis_menu,icon) values ('".$request->kode_menu."','".$request->link."','".$request->kode_klp."','".$request->nama."','".$request->level_menu."','".$request->index."','".$request->jenis_menu."','".$request->icon."')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Menu berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
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
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select kode_menu,rowindex from menu where kode_menu = '".$request->kode_menu."' and kode_klp='$request->kode_klp' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $del = DB::connection($this->sql)->table('menu')
                ->where('kode_klp', $request->kode_klp)
                ->where('kode_menu', $request->kode_menu)
                ->delete();
               
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Menu berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Menu gagal dihapus";
            }
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Menu gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
