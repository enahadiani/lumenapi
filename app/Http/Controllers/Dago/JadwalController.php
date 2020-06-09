<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select( "select no_jadwal,convert (varchar, tgl_berangkat,103) as tgl_berangkat from dgw_jadwal where no_closing = '-' and kode_lokasi='".$kode_lokasi."' ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = "SUCCESS";
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
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required',
            'data_jadwal' => 'required|array',
            'data_jadwal.*.no_jadwal' => 'required',
            'data_jadwal.*.tgl_berangkat' => 'required|date_format:Y-m-d',
            'data_jadwal.*.tgl_baru' => 'required|date_format:Y-m-d'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $detJadwal = $request->data_jadwal;
            if (count($detJadwal) > 0){
                for ($i=0;$i < count($detJadwal);$i++){
                    
                    $upd[$i] = DB::connection($this->sql)->table('dgw_jadwal')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_paket', $request->no_paket)
                    ->where('no_jadwal', $detJadwal[$i]['no_jadwal'])
                    ->update(['tgl_berangkat' => $detJadwal[$i]['tgl_baru']]);
                }						
            }

            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Jadwal berhasil diubah";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Jadwal gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

}
