<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

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
                $res = DB::connection($this->sql)->select("select max(no_urut) as nu from rt_warga_d where no_rumah ='$request->no_rumah' and kode_lokasi='$request->kode_lokasi' and kode_blok ='$request->kode_blok' ");
                $no_urut = intval($res[0]->nu)+1;
                for($i=0; $i<count($request->nama);$i++){
                    $ins = DB::connection($this->sql)->insert('insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,nik,no_hp,foto,kode_lokasi,no_bukti,kode_jk,kode_agama,kode_pp,tgl_masuk,sts_masuk) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->blok,$request->no_rumah,$no_urut,$request->nama[$i],$request->nik[$i],$request->no_hp[$i],$arr_foto[$i],$kode_lokasi,$no_bukti,$request->jenis_kelamin[$i],$request->agama[$i],$request->rt,$request->tgl_masuk,$request->sts_masuk));
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

            $sql= "select a.kode_pp,a.kode_blok,a.no_rumah,a.no_urut,a.nama,a.nik,a.no_hp,case when foto != '-' then '".$url."/'+foto else '-' end as foto,a.kode_jk,a.kode_agama from rt_warga_d a where a.kode_lokasi='".$kode_lokasi."' $filter ";
            
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
                    $ins = DB::connection($this->sql)->insert('insert into rt_warga_d(kode_blok,no_rumah,no_urut,nama,nik,no_hp,foto,kode_lokasi,no_bukti,kode_jk,kode_agama,kode_pp,tgl_masuk,sts_masuk) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($request->blok,$request->no_rumah,$no_urut,$request->nama[$i],$request->nik[$i],$request->no_hp[$i],$arr_foto[$i],$kode_lokasi,$no_bukti,$request->jenis_kelamin[$i],$request->agama[$i],$request->rt,$request->tgl_masuk,$request->sts_masuk));
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
}
