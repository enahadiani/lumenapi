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
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select( "select a.no_paket,a.nama,a.kode_curr,b.nama as nama_produk 
            from dgw_paket a
            inner join dgw_jenis_produk b on a.kode_produk=b.kode_produk and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' ");
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

    public function show(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_paket = $request->no_paket;

            if(isset($request->no_jadwal)){
                if($request->no_jadwal == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_jadwal='$request->no_jadwal' ";
                }
            }else{
                $filter = "";
            }

            $sql = "select no_jadwal,convert (varchar, tgl_berangkat,103) as tgl_berangkat from dgw_jadwal where no_closing = '-' and kode_lokasi='".$kode_lokasi."' and no_paket='".$no_paket."' $filter";
            $res = DB::connection($this->sql)->select($sql);
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
            $success['sql'] = $sql;  
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = "FAILED";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function showJadwal(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_paket = $request->no_paket;

            if(isset($request->no_jadwal)){
                if($request->no_jadwal == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_jadwal='$request->no_jadwal' ";
                }
            }else{
                $filter = "";
            }

            $sql = "select no_jadwal,convert (varchar, tgl_datang,103) as tgl_berangkat from dgw_jadwal where no_closing = '-' and kode_lokasi='".$kode_lokasi."' and no_paket='".$no_paket."' $filter";
            $res = DB::connection($this->sql)->select($sql);
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
            $success['sql'] = $sql;  
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
