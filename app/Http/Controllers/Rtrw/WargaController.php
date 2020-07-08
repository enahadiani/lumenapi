<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use App\Imports\WargaImport;
use Maatwebsite\Excel\Facades\Excel;

class WargaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';
    public $guard3 = 'warga';
    
    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter .= "";
                }else{
                    $filter .= " and no_rumah='$request->no_rumah' ";
                }
            }else{
                $filter .= "";
            }

            $sql= "select distinct no_bukti,tgl_masuk,sts_masuk,kode_blok,no_rumah from rt_warga_d where kode_lokasi='$kode_lokasi' $filter ";
            
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
            'rt' => 'required',
            'blok' => 'required',
            'no_rumah' => 'required',
            'tgl_masuk' => 'required',
            'sts_masuk' => 'required',
            'nama' => 'required|array',
            'nik' => 'required|array',
            'no_hp' => 'required|array',
            'foto.*' => 'file|image|mimes:jpeg,png,jpg|max:2048|required',
            'jenis_kelamin' => 'required|array',
            'agama' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix="WR".$per;
            $sql="select right(isnull(max(no_bukti),'00000'),".strlen($str_format).")+1 as id from rt_warga_d where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $no_bukti = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $no_bukti = "-";
            }

            $arr_foto = array();
            $arr_nama = array();
            if($request->hasfile('foto'))
            {
                $i=0;
                foreach($request->file('foto') as $file)
                {                
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }
                    Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama')[$i];
                    $i++;
                }
            }

            if(count($request->nama) > 0){
                $del3 = DB::connection($this->sql)->table('rt_warga_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $res = DB::connection($this->sql)->select("select max(no_urut) as nu from rt_warga_d where no_rumah ='$request->no_rumah' and kode_lokasi='$kode_lokasi' and kode_blok ='$request->kode_blok' ");
                $no_urut = intval($res[0]->nu)+1;
                for($i=0; $i<count($request->nama);$i++){
                    if(isset($request->alias[$i])){
                        $alias = $request->alias[$i];
                    }else{
                        $alias = "-";
                    }
                    
                    $pass = substr($request->no_hp[$i],6);
                    $password = app('hash')->make($pass);
                    $ins = DB::connection($this->sql)->insert('insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,nik,no_hp,foto,kode_lokasi,no_bukti,kode_jk,kode_agama,kode_pp,tgl_masuk,sts_masuk,alias,pass,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->blok,$request->no_rumah,$no_urut,$request->nama[$i],$request->nik[$i],$request->no_hp[$i],$arr_foto[$i],$kode_lokasi,$no_bukti,$request->jenis_kelamin[$i],$request->agama[$i],$request->rt,$request->tgl_masuk,$request->sts_masuk,$alias,$pass,$password));
                    $no_urut++;
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            $url = url('api/portal/storage');
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter .= "";
                }else{
                    $filter .= " and a.no_rumah='$request->no_rumah' ";
                }
            }else{
                $filter .= "";
            }

            if(isset($request->nik)){
                if($request->nik != "" || $request->nik != "all"){
                    $filter .= " and a.nik='$request->nik' ";
                }else{
                    $filter .= "";
                }
            }else{
                $filter .= "";
            }

            $sql= "select a.kode_pp,a.kode_blok,a.no_rumah,a.no_urut,a.nama,a.alias,a.nik,a.no_hp,case when foto != '-' then '".$url."/'+foto else '-' end as foto,a.kode_jk,a.kode_agama,a.id_device from rt_warga_d a where a.kode_lokasi='".$kode_lokasi."' $filter ";
            
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'rt' => 'required',
            'blok' => 'required',
            'no_rumah' => 'required',
            'tgl_masuk' => 'required',
            'sts_masuk' => 'required',
            'nama' => 'required|array',
            'nik' => 'required|array',
            'no_hp' => 'required|array',
            'foto.*' => 'file|image|mimes:jpeg,png,jpg|max:2048|required',
            'jenis_kelamin' => 'required|array',
            'agama' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;
            $arr_foto = array();
            $arr_nama = array();
            if($request->hasfile('foto'))
            {
                $res = DB::connection($this->sql)->select("select * from rt_warga_d where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
                $res = json_decode(json_encode($res),true);
                for($i=0; $i<count($res);$i++){
                    if($res[$i]['foto'] != "" || $res[$i]['foto'] != "-"){
                        if(Storage::disk('s3')->exists('rtrw/'.$res[$i]['foto'])){
                            Storage::disk('s3')->delete('rtrw/'.$res[$i]['foto']);
                        }
                    }
                }

                $i=0;
                foreach($request->file('foto') as $file)
                {                
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }
                    Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                    $arr_foto[] = $foto;
                    $arr_nama[] = $request->input('nama')[$i];
                    $i++;
                }

            }

            if(count($request->nama) > 0){
                $del3 = DB::connection($this->sql)->table('rt_warga_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $no_urut = 1;
                for($i=0; $i<count($request->nama);$i++){
                    if(isset($request->alias[$i])){
                        $alias = $request->alias[$i];
                    }else{
                        $alias = "-";
                    }
                    $pass = substr($request->no_hp[$i],6);
                    $password = app('hash')->make($pass);
                    $ins = DB::connection($this->sql)->insert('insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,nik,no_hp,foto,kode_lokasi,no_bukti,kode_jk,kode_agama,kode_pp,tgl_masuk,sts_masuk,alias,pass,password) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->blok,$request->no_rumah,$no_urut,$request->nama[$i],$request->nik[$i],$request->no_hp[$i],$arr_foto[$i],$kode_lokasi,$no_bukti,$request->jenis_kelamin[$i],$request->agama[$i],$request->rt,$request->tgl_masuk,$request->sts_masuk,$alias,$pass,$password));
                    $no_urut++;
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }		
    }

    public function updatePerUser(Request $request)
    {  
        $this->validate($request, [
            'nama' => 'required',
            'alias' => 'required',
            'no_hp' => 'required',
            'password' => 'max:6',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $rt = $request->rt;
                $blok = $request->blok;
                $no_rumah = $request->no_rumah;
                $no_urut = $request->no_urut;
                $no_bukti = $request->no_bukti;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
                $rt = $request->rt;
                $blok = $request->blok;
                $no_rumah = $request->no_rumah;
            }else if($data =  Auth::guard($this->guard3)->user()){
                $nik= $data->no_hp;
                $kode_lokasi= $data->kode_lokasi;
                $rt = $data->kode_pp;
                $blok = $data->kode_blok;
                $no_rumah = $data->no_rumah;
                $no_urut = $data->no_urut;
                $no_bukti = $data->no_bukti;
            }
           
            $res = DB::connection($this->sql)->select("select foto,pass,tgl_lahir from rt_warga_d where no_urut='$no_urut' and no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' and no_rumah='$no_rumah' and kode_pp='$rt' ");
            $foto = $res[0]->foto;
            $pass = $res[0]->pass;
            $tgl_lahir = $res[0]->tgl_lahir;
            
            if($request->hasfile('foto')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                
                $file = $request->file('foto');
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                    Storage::disk('s3')->delete('rtrw/'.$foto);
                }
                Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                
            }

            if(isset($request->alias)){
                $alias = $request->alias;
            }else{
                $alias = "-";
            }

            if(isset($request->tgl_lahir)){
                $tgl_lahir = $request->tgl_lahir;
            }else{
                $tgl_lahir = $tgl_lahir;
            }

            if(isset($request->password)){
                $pass = $request->password;
            }else{
                $pass = $pass;
            }

            $update = DB::connection($this->sql)->table('rt_warga_d')
            ->where('no_bukti',$no_bukti)
            ->where('no_urut',$no_urut)
            ->where('no_rumah',$no_rumah)
            ->where('kode_pp',$rt)
            ->where('kode_lokasi',$kode_lokasi)
            ->update([
                'nama' => $request->nama,
                'alias' => $alias,
                'no_hp' => $request->no_hp,
                'tgl_lahir' => $tgl_lahir,
                'foto' => $foto,
                'pass' => $pass,
                'password' => app('hash')->make($pass)
            ]);
            
            if($update){
                $success['status'] = true;
                $success['message'] = "Data Warga berhasil diubah";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Warga gagal diubah";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Warga gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $res = DB::connection($this->sql)->select("select * from rt_warga_d where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);
            for($i=0; $i<count($res);$i++){
                if($res[$i]['foto'] != "" || $res[$i]['foto'] != "-"){
                    if(Storage::disk('s3')->exists('rtrw/'.$res[$i]['foto'])){
                        Storage::disk('s3')->delete('rtrw/'.$res[$i]['foto']);
                    }
                }
            }

            $del3 = DB::connection($this->sql)->table('rt_warga_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Warga berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Warga gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getDetailWarga(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            
            $url = url('api/portal/storage');
            if(isset($request->no_rumah)){
                if($request->no_rumah == "all" || $request->no_rumah == ""){
                    $filter .= "";
                }else{
                    $filter .= " and no_rumah='$request->no_rumah' ";
                }
            }else{
                $filter .= "";
            }

            if(isset($request->no_bukti)){
                if($request->no_bukti == "all" || $request->no_bukti == ""){
                    $filter .= "";
                }else{
                    $filter .= " and no_bukti='$request->no_bukti' ";
                }
            }else{
                $filter .= "";
            }

            $sql= "select distinct kode_pp,no_bukti,tgl_masuk,sts_masuk,kode_blok,no_rumah from rt_warga_d where kode_lokasi='$kode_lokasi' $filter ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2= "select a.no_urut,a.nama,a.alias,a.nik,a.no_hp,case when foto != '-' then '".$url."/'+foto else '-' end as foto,a.kode_jk as jk,a.kode_agama as agama from rt_warga_d a where a.kode_lokasi='".$kode_lokasi."' $filter  ";
            
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data_detail'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function hashPassPerWarga(Request $request){
        $this->validate($request,[
            'no_bukti' => 'required',
            'no_urut' => 'required',
            'kode_pp' => 'required',
            'kode_lokasi' => 'required',
            'no_rumah' => 'required',
            'password' => 'required'
        ]);
        DB::connection('sqlsrvrtrw')->beginTransaction();
        
        try {

            DB::connection('sqlsrvrtrw')->table('rt_warga_d')
                        ->where('no_bukti', $request->no_bukti)
                        ->where('no_urut', $request->no_urut)
                        ->where('kode_pp', $request->kode_pp)
                        ->where('kode_lokasi', $request->kode_lokasi)
                        ->where('no_rumah', $request->no_rumah)
                        ->where('pass','<>',' ')
                        ->update(['password' => app('hash')->make($request->password)]);

            DB::connection('sqlsrvrtrw')->commit();
            $success['status'] = true;
            $success['message'] = "Hash Password berhasil disimpan ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollback();
            $success['status'] = false;
            $success['message'] = "Hash Password gagal disimpan ".$e;
            return response()->json($success, 200);
        }	

    }

    public function uploadWarga(Request $request)
    {
        $this->validate($request, [
            'nama' => 'required|array',
            'alias' => 'required|array',
            'no_rumah' => 'required|array',
            'no_hp' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(count($request->no_hp) > 0){
                for($i=0; $i<count($request->no_hp);$i++){

                    $cek= DB::connection($this->sql)->select("select no_bukti from rt_warga_d where kode_lokasi='$kode_lokasi' and no_rumah='".$request->no_rumah[$i]."' ");
                    $cek = json_decode(json_encode($cek),true);
                    if(count($cek) > 0){
                        $no_bukti = $cek[0]['no_bukti'];
                    }else{

                        $str_format="0000";
                        $periode=date('Y').date('m');
                        $per=date('y').date('m');
                        $prefix="WR".$per;
                        $sql="select right(isnull(max(no_bukti),'00000'),".strlen($str_format).")+1 as id from rt_warga_d where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
                        $get = DB::connection($this->sql)->select($sql);
                        $get = json_decode(json_encode($get),true);
                        $no_bukti = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
                    }
                    $rs= DB::connection($this->sql)->select("select blok,rt from rt_rumah where kode_lokasi='$kode_lokasi' and kode_rumah='".$request->no_rumah[$i]."' ");
                    $rs = json_decode(json_encode($rs),true);
                    if(count($rs) > 0){
                        $rt = $rs[0]['rt'];
                        $blok = $rs[0]['blok'];
                    }else{
                        $rt = "-";
                        $blok = "-";
                    }

                    $res = DB::connection($this->sql)->select("select max(no_urut) as nu from rt_warga_d where no_rumah ='".$request->no_rumah[$i]."' and kode_lokasi='$kode_lokasi' ");
                    $no_urut = intval($res[0]->nu)+1;
                    $alias = $request->alias[$i];
                    
                    $pass = substr($request->no_hp[$i],6);
                    $password = app('hash')->make($pass);

                    $ins = DB::connection($this->sql)->insert("insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,nik,no_hp,foto,kode_lokasi,no_bukti,kode_jk,kode_agama,kode_pp,tgl_masuk,sts_masuk,alias,pass,password) values ('".$blok."','".$request->no_rumah[$i]."','".$no_urut."','".$request->nama[$i]."','-','".$request->no_hp[$i]."','-','$kode_lokasi','$no_bukti','-','-','$rt',NULL,NULL,'$alias','$pass','$password')");

                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Upload Data Warga berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Upload Data Warga gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->id_satpam;
                $kode_lokasi= $data->kode_lokasi;
            }
 
            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
    
            // import data
            Excel::import(new WargaImport, $nama_file);
            Storage::disk('local')->delete($nama_file);

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
