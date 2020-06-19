<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use App\AdminSatpam;
use SimpleSoftwareIO\QrCode\Facade as QrCode;

class SatpamController extends Controller
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

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select id_satpam from rt_satpam where id_satpam ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/portal/storage');
            if(isset($request->id_satpam)){
                if($request->id_satpam == "all"){
                    $filter = "";
                }else{
                    $filter = " and id_satpam='$request->id_satpam' ";
                }
                $sql= "select id_satpam,kode_lokasi,nama,alamat,status,no_hp,flag_aktif,case when foto != '-' then '".$url."/'+foto else '-' end as foto,case when qrcode != '-' then '".$url."/'+qrcode else '-' end as qrcode from rt_satpam
                where kode_lokasi='".$kode_lokasi."' $filter";
            }else{
                $sql = "select id_satpam,kode_lokasi,nama,alamat,status,no_hp,flag_aktif,case when foto != '-' then '".$url."/'+foto else '-' end as foto,case when qrcode != '-' then '".$url."/'+qrcode else '-' end as qrcode from rt_satpam where kode_lokasi= '".$kode_lokasi."'";
            }

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
    public function generateQrCode()
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = AdminSatpam::all();
            foreach($get as $row){
                if($row->qrcode == "" || $row->qrcode == "-"){

                    $image = QrCode::size(300)->generate($row->id_satpam);
                    $output_file = 'qrcode-' .uniqid(). '.png';
                    Storage::disk('s3')->put('rtrw/'.$output_file, $image);
                    $update = AdminSatpam::where('id_satpam',$row->id_satpam)
                    ->where('kode_lokasi',$kode_lokasi)
                    ->update([
                        'qrcode' => $output_file
                    ]);

                }
            }
            $success['status'] = true;
            $success['message'] = "Generate Qrcode berhasil ";
            return response()->json($success, $this->successStatus);
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
        //select id_satpam,kode_lokasi,nama,alamat,status,no_hp,flag_aktif,foto,qrcode   
        $this->validate($request, [
            'id_satpam' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'status' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'password' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->id_satpam,$kode_lokasi)){

                if($request->hasfile('foto')){
                    $file = $request->file('foto');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }
                    Storage::disk('s3')->put('rtrw/'.$foto,file_get_contents($file));
                }else{
    
                    $foto="-";
                }

                $image = QrCode::size(300)->generate($request->id_satpam);
                $output_file = 'qrcode-' .uniqid(). '.png';
                Storage::disk('s3')->put('rtrw/'.$output_file, $image);
                $req = array(
                    'id_satpam' => $request->id_satpam,
                    'nama' => $request->nama,
                    'kode_lokasi' => $kode_lokasi,
                    'alamat' => $request->alamat,
                    'status' => $request->status,
                    'no_hp' => $request->no_hp,
                    'flag_aktif' => $request->flag_aktif,
                    'password' => app('hash')->make($request->password),
                    'pass' => $request->password,
                    'foto' => $foto,
                    'qrcode' => $output_file
                );

                if(AdminSatpam::create($req)){
                    $success['status'] = true;
                    $success['message'] = "Data Satpam berhasil disimpan";
                }else{
                    if(Storage::disk('s3')->exists('rtrw/'.$foto)){
                        Storage::disk('s3')->delete('rtrw/'.$foto);
                    }

                    if(Storage::disk('s3')->exists('rtrw/'.$output_file)){
                        Storage::disk('s3')->delete('rtrw/'.$output_file);
                    }
                    $success['status'] = false;
                    $success['message'] = "Data Satpam gagal disimpan";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Id Satpam sudah ada di database!";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Satpam gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //select id_satpam,kode_lokasi,nama,alamat,status,no_hp,flag_aktif,foto,qrcode   
        $this->validate($request, [
            'id_satpam' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'status' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'password' => 'required',
            'foto' => 'file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else if($data =  Auth::guard($this->guard2)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
           
            $res = AdminSatpam::where('id_satpam',$request->id_satpam)
            ->where('kode_lokasi',$kode_lokasi)
            ->get();
            $foto = $res[0]->foto;
            
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

            $update = AdminSatpam::where('id_satpam',$request->id_satpam)
            ->where('kode_lokasi',$kode_lokasi)
            ->update([
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'status' => $request->status,
                'no_hp' => $request->no_hp,
                'flag_aktif' => $request->flag_aktif,
                'foto' => $foto,
                'password' =>app('hash')->make($request->password),
                'pass' => $request->password
            ]);
            
            if($update){
                $success['status'] = true;
                $success['message'] = "Data Satpam berhasil diubah";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Satpam gagal diubah";
            }
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Data Satpam gagal diubah ".$e;
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
            'id_satpam' => 'required'
        ]);

        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }else if($data =  Auth::guard($this->guard2)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        $get = AdminSatpam::where('id_satpam',$request->id_satpam)
        ->where('kode_lokasi',$kode_lokasi)
        ->get();
        $foto = $get[0]->foto;
        $qrcode = $get[0]->qrcode;

        if(Storage::disk('s3')->exists('rtrw/'.$foto)){
            Storage::disk('s3')->delete('rtrw/'.$foto);
        }

        if(Storage::disk('s3')->exists('rtrw/'.$qrcode)){
            Storage::disk('s3')->delete('rtrw/'.$qrcode);
        }

        $res = AdminSatpam::where('id_satpam',$request->id_satpam)
        ->where('kode_lokasi',$kode_lokasi);
        if($res->delete()){ //mengecek apakah data kosong atau tidak
            $success['status'] = true;
            $success['message'] = "Data Satpam berhasil dihapus";
            return response()->json($success, $this->successStatus);     
        }
        else{
            $success['status'] = false;
            $success['message'] = "Data Satpam gagal dihapus";
            return response()->json($success, $this->successStatus);     
        }
        
        
    }
}
