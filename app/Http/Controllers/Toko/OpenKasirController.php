<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OpenKasirController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select kode_klp form brg_barangklp where kode_klp ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            
            if(isset($request->no_open)){
                if($request->no_open == "all"){
                    $filter = "";
                }else{
                    $filter = " and no_open='$request->no_open' ";
                }
            }else{
                $filter = "";
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $sql = "select no_open,nik,tgl_input,saldo_awal,no_close from kasir_open where kode_lokasi='".$kode_lokasi."' and nik='".$nik."' and no_close = '-' $filter ";

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
            'saldo_awal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $str_format="0000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-OPN".$per.".";
            $sql="select right(isnull(max(no_open),'0000'),".strlen($str_format).")+1 as id from kasir_open where no_open like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $sql="select*from kasir_open where nik='$nik' and no_close ='-' and kode_lokasi='$kode_lokasi' ";
            $get2 = DB::connection($this->sql)->select($sql);
            $get2 = json_decode(json_encode($get2),true);
            if(count($get2) > 0){
                $msg = "Gagal disimpan. Masih ada data open kasir yg belum closing";
                $sts = false;
            }else{
                $sql1= DB::connection($this->sql)->insert("insert into kasir_open (no_open,kode_lokasi,tgl_input,nik_user,nik,saldo_awal,no_close) values (?, ?, ?, ?, ?, ?, ?) ", array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$nik,$request->saldo_awal,'-'));
                $msg = "Data Open Kasir berhasil disimpan";
                $sts = true;
                DB::connection($this->sql)->commit();
            }
            $success['status'] = $sts;
            $success['message'] = $msg;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Open Kasir gagal disimpan ".$e;
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
            'no_open' => 'required',
            'saldo_awal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }

            $id = $request->no_open;
            $del = DB::connection($this->sql)->table('kasir_open')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_open', $id)
            ->delete();

            $sql="select*from kasir_open where nik='$nik' and no_close ='-' and kode_lokasi='$kode_lokasi' ";
            $get2 = DB::connection($this->sql)->select($sql);
            $get2 = json_decode(json_encode($get2),true);
            if(count($get2) > 0){
                $msg = "Gagal disimpan. Masih ada data open kasir yg belum closing";
                $sts = false;
            }else{

                $del = DB::connection($this->sql)->table('kasir_open')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_open', $request->no_open)
                ->delete();

                $sql1= DB::connection($this->sql)->insert("insert into kasir_open (no_open,kode_lokasi,tgl_input,nik_user,nik,saldo_awal,no_close) values (?, ?, ?, ?, ?, ?, ?) ", array($id,$kode_lokasi,date('Y-m-d H:i:s'),$nik,$nik,$request->saldo_awal,'-'));
                $msg = "Data Open Kasir berhasil diubah";
                $sts = true;
                DB::connection($this->sql)->commit();
            }
            $success['status'] = $sts;
            $success['message'] = $msg;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Open Kasir gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }
}
