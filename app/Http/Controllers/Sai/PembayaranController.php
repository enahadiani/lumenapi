<?php

namespace App\Http\Controllers\Sai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class PembayaranController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrv2';
    public $guard = 'admin';

    public function reverseDate($ymd_or_dmy_date, $org_sep='-', $new_sep='-'){
        $arr = explode($org_sep, $ymd_or_dmy_date);
        return $arr[2].$new_sep.$arr[1].$new_sep.$arr[0];
    }

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.no_bayar,a.tanggal,a.keterangan,a.kode_cust
            from sai_bayar_m a
            where a.kode_lokasi='".$kode_lokasi."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
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
            'tanggal' => 'required',
            'keterangan' => 'required',
            'kode_cust' => 'required',
            'no_bill' => 'required|array',
            'nilai' => 'required|array',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = date('Ym');
            $per = substr($periode,2,4);
            $no_bukti = $this->generateKode("sai_bayar_m", "no_bayar", $kode_lokasi."-PYR".$per.".", "0001");

            $ins = DB::connection($this->sql)->insert("insert into sai_bayar_m (no_bayar,kode_lokasi,tanggal,keterangan,nik_user,tgl_input,kode_cust) values ('$no_bukti','$kode_lokasi','$request->tanggal','$request->keterangan','$nik_user',getdate(),'$request->kode_cust') ");

            if(count($request->no_bill) > 0){
                for($i=0;$i<count($request->no_bill);$i++){
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bayar_d (no_bayar,kode_lokasi,no_bill,nilai) values ('$no_bukti','$kode_lokasi','".$request->no_bill[$i]."',".$request->nilai[$i].") ");

                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil disimpan. No Bukti:".$no_bukti;
            $success['no_bukti'] = $no_bukti;

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bayar,a.tanggal,a.keterangan,a.kode_cust from sai_bayar_m a where a.kode_lokasi='".$kode_lokasi."' and a.no_bayar='$request->no_bukti' ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
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
            'tanggal' => 'required',
            'no_bukti' => 'required',
            'keterangan' => 'required',
            'kode_cust' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_bayar_m')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('sai_bayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bukti)->delete();

            $periode = date('Ym');
            $per = substr($periode,2,4);
            $no_bukti = $request->no_bukti;

            $ins = DB::connection($this->sql)->insert("insert into sai_bayar_m (no_bayar,kode_lokasi,tanggal,keterangan,nik_user,tgl_input,kode_cust) values ('$no_bukti','$kode_lokasi','$request->tanggal','$request->keterangan','$nik_user',getdate(),'$request->kode_cust') ");

            if(count($request->no_bill) > 0){
                for($i=0;$i<count($request->no_bill);$i++){
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into sai_bayar_d (no_bayar,kode_lokasi,no_bill,nilai) values ('$no_bukti','$kode_lokasi','".$request->no_bill[$i]."',".$request->nilai[$i].") ");
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil diubah. No Pembayaran:".$no_bukti;
            $success['no_bukti'] = $no_bukti;
          
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal diubah ".$e;
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
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('sai_bayar_m')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bukti)->delete();
            $del2 = DB::connection($this->sql)->table('sai_bayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bukti)->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPreview(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql=" ";
            
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
