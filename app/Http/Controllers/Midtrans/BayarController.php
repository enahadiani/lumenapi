<?php

namespace App\Http\Controllers\Midtrans;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BayarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = "sqlsrvyptkug";
    public $guard = "ts";
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,tgl_input from sis_mid_bayar where kode_lokasi='$kode_lokasi' and nis='$nis' and kode_pp='$kode_pp'	 
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
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }
            $no_bukti = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-MID.", "0001");
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
            'nis' => 'required',
            'no_bill' => 'required',
            'nilai' => 'required',
            'keterangan' => 'required',
            'status' => 'required',
            'snap_token' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_bukti = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-MID.", "0001");

            $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input) values ('$no_bukti','$request->nis','$request->no_bill','$request->nilai','$request->keterangan','$request->status','$request->snap_token','$kode_lokasi','$nik',getdate())");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Donasi berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Donasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function show($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bukti,nis,no_bill,nilai,keterangan,status,snap_token from sis_mid_bayar where kode_lokasi='$kode_lokasi' and nik_user='$nik' and no_bukti='$no_bukti'
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            
            $upd = DB::connection($this->db)->table('sis_mid_bayar')
            ->where('no_bukti', $no_bukti)      
            ->update(['status' => $sts_bayar]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Donasi berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Donasi gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }


}
