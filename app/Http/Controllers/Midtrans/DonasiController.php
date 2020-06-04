<?php

namespace App\Http\Controllers\Midtrans;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DonasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select no_bukti,nama,email,type_donasi,nilai,keterangan,status,snap_token from mid_donasi where kode_lokasi='$kode_lokasi' and nik='$nik'		 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
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
    public function getKode()
    {
        try{
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $this->generateKode("mid_donasi", "no_bukti", $kode_lokasi."-MID.", "0001");
            $success['no_bukti'] = $no_bukti;
            $success['status'] = true;
            $success['message'] = "Success";
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
        $this->validate($request, [
            'no_bukti' => 'required',
            'nama' => 'required',
            'email' => 'required',
            'type_donasi' => 'required',
            'nilai' => 'required',
            'keterangan' => 'required',
            'status' => 'required',
            'snap_token' => 'required'
        ]);

        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $ins = DB::connection('sqlsrv2')->insert('insert into mid_donasi (no_bukti,nama,email,type_donasi,nilai,keterangan,status,snap_token,kode_lokasi,nik) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$no_bukti,$request->nama,$request->email,$request->type_donasi,$request->nilai,$request->keterangan,$request->status,$request->snap_token,$kode_lokasi,$nik]);
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Donasi berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Donasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function show($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select no_bukti,nama,email,type_donasi,nilai,keterangan,status,snap_token from mid_donasi where kode_lokasi='$kode_lokasi' and nik='$nik' and no_bukti='$no_bukti'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ubahStatus($no_bukti,$sts_bayar)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $upd = DB::connection('sqlsrv2')->table('mid_donasi')
            ->where('no_bukti', $no_bukti)          
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['status' => $sts_bayar]);
    
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Donasi berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Donasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }


}
