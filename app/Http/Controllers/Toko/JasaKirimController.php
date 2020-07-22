<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JasaKirimController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_kirim from ol_kirim where kode_kirim ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            }

            if(isset($request->kode_kirim)){
                if($request->kode_kirim == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_kirim='$request->kode_kirim' ";
                }
            }else{
                $filter = "";
            }

            $sql= "select a.kode_kirim,a.nama,a.alamat,a.no_telp,a.email,a.pic,a.bank,a.cabang,a.no_rek,a.nama_rek,a.no_pic
            kode_lokasi from ol_kirim a where a.kode_lokasi='".$kode_lokasi."' $filter ";

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
            'kode_kirim' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'pic' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'no_pic'=>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_kirim,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into ol_kirim(kode_kirim,nama,alamat,no_telp,email,pic,bank,cabang,no_rek,nama_rek,no_pic,kode_lokasi) values ('".$request->kode_kirim."','".$request->nama."','".$request->alamat."','".$request->no_telp."','".$request->email."','".$request->pic."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->no_pic."','$kode_lokasi') ");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Jasa Kirim berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. No Jasa Kirim sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jasa Kirim gagal disimpan ".$e;
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
    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_kirim' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'no_telp' => 'required',
            'email' => 'required',
            'pic' => 'required',
            'bank' => 'required',
            'cabang' => 'required',
            'no_rek' => 'required',
            'nama_rek' => 'required',
            'no_pic'=>'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ol_kirim')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_kirim', $request->kode_kirim)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into ol_kirim(kode_kirim,nama,alamat,no_telp,email,pic,bank,cabang,no_rek,nama_rek,no_pic,kode_lokasi) values ('".$request->kode_kirim."','".$request->nama."','".$request->alamat."','".$request->no_telp."','".$request->email."','".$request->pic."','".$request->bank."','".$request->cabang."','".$request->no_rek."','".$request->nama_rek."','".$request->no_pic."','$kode_lokasi') ");
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jasa Kirim berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jasa Kirim gagal diubah ".$e;
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
            'kode_kirim' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('ol_kirim')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_kirim', $request->kode_kirim)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jasa Kirim berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jasa Kirim gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
