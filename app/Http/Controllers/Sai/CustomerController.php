<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    function isUnik($isi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
    
        $strSQL = "select kode_cust from sai_cust where kode_cust = '".$isi."' and kode_lokasi='".$kode_lokasi."' ";
    
        $auth = DB::connection($this->sql)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
    
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
        return $res;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $url = url('api/sai-auth/storage');
            $filter = "";
            if(isset($request->kode_cust)){
                if($request->kode_cust == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_cust='$request->kode_cust' ";
                }   
                $sql="select kode_cust,nama,alamat,pic,case when gambar != '-' then '".$url."/'+gambar else '-' end as file_gambar,email,no_telp,bank,cabang,no_rek,nama_rek from sai_cust where kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                
                $sql = "select kode_cust,nama,alamat,pic,email,no_telp,bank,cabang,no_rek,nama_rek from sai_cust where kode_lokasi='".$kode_lokasi."' ";
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
     * Show the from for creating a new resource.
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
            'kode_cust' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'pic' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if($this->isUnik($request->kode_cust)){
                if($request->hasfile('file_gambar')){
                    $file = $request->file('file_gambar');
                    
                    $nama_foto = uniqid()."_".$file->getClientOriginalName();
                    $filetype =  $file->getmimeType();
                    // $picName = uniqid() . '_' . $picName;
                    $foto = $nama_foto;
                    if(Storage::disk('s3')->exists('sai/'.$foto)){
                        Storage::disk('s3')->delete('sai/'.$foto);
                    }
                    Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                }else{
                    
                    $foto="-";
                    $filetype = "-";
                }

                $ins = DB::connection($this->sql)->insert("insert into sai_cust(kode_cust,nama,alamat,pic,kode_lokasi,gambar,email,no_telp,bank,cabang,no_rek,nama_rek) values ('".$request->kode_cust."','".$request->nama."','".$request->alamat."','".$request->pic."','".$kode_lokasi."','".$foto."','".$request->email."','".$request->no_telp."','$request->bank','$request->cabang','$request->no_rek','$request->nama_rek')");

                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Customer berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Customer sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
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
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_cust' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'pic' => 'required',
            'email' => 'required',
            'no_telp' => 'required',
            'file_gambar' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select gambar as file_gambar from sai_cust where kode_lokasi='".$kode_lokasi."' and kode_cust='$request->kode_cust' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
            }else{
                $foto = "-";
            }
            
            if($request->hasfile('file_gambar')){
                if($foto != "" || $foto != "-"){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
                
                $file = $request->file('file_gambar');
                
                $nama_foto = uniqid()."_".$file->getClientOriginalName();
                $foto = $nama_foto;
                $filetype = $file->getmimeType();
                if(Storage::disk('s3')->exists('sai/'.$foto)){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
                Storage::disk('s3')->put('sai/'.$foto,file_get_contents($file));
                
            }
            
            $del = DB::connection($this->sql)->table('sai_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into sai_cust(kode_cust,nama,alamat,pic,kode_lokasi,gambar,email,no_telp,bank,cabang,no_rek,nama_rek) values ('".$request->kode_cust."','".$request->nama."','".$request->alamat."','".$request->pic."','".$kode_lokasi."','".$foto."','".$request->email."','".$request->no_telp."','$request->bank','$request->cabang','$request->no_rek','$request->nama_rek')");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_cust' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql = "select gambar as file_gambar from sai_cust where kode_lokasi='".$kode_lokasi."' and kode_cust='$request->kode_cust' 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){
                $foto = $res[0]['file_gambar'];
                if($foto != ""){
                    Storage::disk('s3')->delete('sai/'.$foto);
                }
            }

            $del = DB::connection($this->sql)->table('sai_cust')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_cust', $request->kode_cust)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Customer berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Customer gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
