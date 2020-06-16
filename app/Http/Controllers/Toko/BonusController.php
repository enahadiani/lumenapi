<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BonusController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select kode_bonus from brg_bonus where kode_bonus ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_barang)){
                if($request->kode_barang == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_barang='$request->kode_barang' ";
                }
                $sql= "select kode_barang,keterangan,ref_qty,bonus_qty,tgl_mulai,tgl_selesai from brg_bonus 
                where kode_lokasi='".$kode_lokasi."'
                $filter ";
            }else{
                $sql = "select kode_barang,keterangan,ref_qty,bonus_qty,tgl_mulai,tgl_selesai from brg_bonus where kode_lokasi= '".$kode_lokasi."'";
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
            'kode_barang' => 'required',
            'keterangan' => 'required',
            'ref_qty' => 'required',
            'bonus_qty' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            // if($this->isUnik($request->kode_bonus,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert('insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values (?, ?, ?, ?, ?, ?, ?)', array($request->kode_barang,$request->keterangan,$kode_lokasi,$request->ref_qty,$request->bonus_qty,$request->tgl_mulai,$request->tgl_selesai));
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Bonus berhasil disimpan";
            // }else{
            //     $success['status'] = false;
            //     $success['message'] = "Error : Duplicate entry. No Bonus sudah ada di database!";
            // }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bonus gagal disimpan ".$e;
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
            'kode_barang' => 'required',
            'keterangan' => 'required',
            'ref_qty' => 'required',
            'bonus_qty' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('brg_bonus')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            $ins = DB::connection($this->sql)->insert('insert into brg_bonus(kode_barang,keterangan,kode_lokasi,ref_qty,bonus_qty,tgl_mulai,tgl_selesai) values (?, ?, ?, ?, ?, ?, ?)', array($request->kode_barang,$request->keterangan,$kode_lokasi,$request->ref_qty, $request->bonus_qty,$request->tgl_mulai,$request->tgl_selesai));
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bonus berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bonus gagal diubah ".$e;
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
            'kode_barang' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $del = DB::connection($this->sql)->table('brg_bonus')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barang', $request->kode_barang)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Bonus berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bonus gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
