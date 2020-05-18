<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BiayaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection('sqlsrvdago')->select("select kode_biaya from dgw_biaya where kode_biaya ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_biaya)){
                if($request->kode_biaya == "all"){
                    $filter = "";
                }else{

                    $filter = " and kode_biaya='$request->kode_biaya' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection('sqlsrvdago')->select( "select kode_biaya,nama,nilai,akun_pdpt,jenis from dgw_biaya where kode_lokasi='".$kode_lokasi."' $filter ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
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
            'kode_biaya' => 'required',
            'nama' => 'required',
            'nilai' => 'required',
            'akun_pdpt' => 'required',
            'jenis' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_biaya,$kode_lokasi)){

                $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_biaya(kode_biaya,nama,nilai,akun_pdpt,jenis,kode_lokasi) values values (?, ?, ?, ?, ?, ?)', array($request->kode_biaya,$request->nama,$request->nilai,$request->akun_pdpt,$request->jenis,$kode_lokasi));
                
                DB::connection('sqlsrvdago')->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Biaya berhasil disimpan";
            }else{
                $success['status'] = "FAILED";
                $success['message'] = "Error : Duplicate entry. Kode Biaya sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Biaya gagal disimpan ".$e;
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
            'kode_biaya' => 'required',
            'nama' => 'required',
            'nilai' => 'required',
            'akun_pdpt' => 'required',
            'jenis' => 'required'
        ]);

        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_biaya')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_biaya', $request->kode_biaya)
            ->delete();

            $ins = DB::connection('sqlsrvdago')->insert('insert into dgw_biaya(kode_biaya,nama,nilai,akun_pdpt,jenis,kode_lokasi) values values (?, ?, ?, ?, ?, ?)', array($request->kode_biaya,$request->nama,$request->nilai,$request->akun_pdpt,$request->jenis,$kode_lokasi));
            
            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Biaya berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Biaya gagal diubah ".$e;
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
            'kode_biaya' => 'required'
        ]);
        DB::connection('sqlsrvdago')->beginTransaction();
        
        try {
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection('sqlsrvdago')->table('dgw_biaya')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_biaya', $request->kode_biaya)
            ->delete();

            DB::connection('sqlsrvdago')->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Biaya berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrvdago')->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Biaya gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getAkunPDPT()
    {
        try {
            
            if($data =  Auth::guard('dago')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrvdago')->select( "select a.kode_akun, a.nama from masakun a 
            inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag in ('022') 
            where a.block='0' and a.kode_lokasi = '$kode_lokasi' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "FAILED";
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
