<?php

namespace App\Http\Controllers\Dago;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PaketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'sqlsrvdago';
    public $guard = 'dago';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select no_paket from dgw_paket where no_paket ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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
            if(isset($request->no_paket)){
                if($request->no_paket == "all"){
                    $filter = "";
                }else{

                    $filter = " and no_paket='$request->no_paket' ";
                }
            }else{
                $filter = "";
            }

            $res = DB::connection($this->sql)->select( "select 
            no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen from dgw_paket where kode_lokasi='".$kode_lokasi."' $filter ");
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
    public function store(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required',
            'nama' => 'required',
            'kode_curr' => 'required|in:IDR,USD',
            'jenis' => 'required|in:REGULER,PLUS',
            'kode_produk' => 'required',
            'tarif_agen' => 'required|integer',
            'data_harga' => 'required|array',
            'data_harga.*.kode_harga' => 'required',
            'data_harga.*.harga' => 'required|integer',
            'data_harga.*.harga_se' => 'required|integer',
            'data_harga.*.harga_e' => 'required|integer',
            'data_harga.*.fee' => 'required|integer',
            'data_harga.*.curr_fee' => 'required|in:IDR,USD',
            'data_jadwal' => 'required|array',
            'data_jadwal.*.tgl_berangkat' => 'required|date_format:Y-m-d',
            'data_jadwal.*.lama_hari' => 'required|integer',
            'data_jadwal.*.quota' => 'required|integer',
            'data_jadwal.*.quota_se' => 'required|integer',
            'data_jadwal.*.quota_e' => 'required|integer',
            'data_jadwal.*.tgl_datang' => 'required|date_format:Y-m-d',
            'data_jadwal.*.id_jadwal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->no_paket,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert('insert into dgw_paket(
                no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?)', array($request->no_paket,$request->nama,$request->kode_curr,$request->jenis, $request->kode_produk, $request->tarif_agen,$kode_lokasi));

                $detHarga = $request->data_harga;

                if (count($detHarga) > 0){
                    for ($i=0;$i < count($detHarga);$i++){
                        $ins2[$i] = DB::connection($this->sql)->insert("insert into dgw_harga(no_paket,kode_harga,harga,harga_se,harga_e,fee,kode_lokasi,curr_fee) values (?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detHarga[$i]['kode_harga'],$detHarga[$i]['harga'],$detHarga[$i]['harga_se'],$detHarga[$i]['harga_e'],$detHarga[$i]['fee'],$kode_lokasi,$detHarga[$i]['curr_fee']));
                    }						
                }

                // $strSQL = "select isnull(max(no_jadwal),0) + 1 as id_jadwal from dgw_jadwal where no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";	
                
                // $res = DB::connection($this->sql)->select($strSQL); 
                // $res = json_decode(json_encode($res),true);
                // if (count($res) > 0){
                //     $line = $res[0];							
                //     $idJadwal = intval($line['id_jadwal']);
                // } 

                $detJadwal = $request->data_jadwal;
                if (count($detJadwal) > 0){
                    for ($i=0;$i < count($detJadwal);$i++){
                       
                        $ins3[$i] = DB::connection($this->sql)->insert("insert into dgw_jadwal(no_paket,no_jadwal,tgl_berangkat,lama_hari,quota,quota_se,quota_e,kode_lokasi, no_closing,kurs_closing,id_pbb,tgl_datang) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detJadwal[$i]['id_jadwal'],$detJadwal[$i]['tgl_berangkat'],$detJadwal[$i]['lama_hari'],$detJadwal[$i]['quota'],$detJadwal[$i]['quota_se'],$detJadwal[$i]['quota_e'],$kode_lokasi,'-',0,'-',$detJadwal[$i]['tgl_datang']));   
                        // $idJadwal = $idJadwal + 1;	
                    }						
                }
                
                DB::connection($this->sql)->commit();
                $success['status'] = "SUCCESS";
                $success['message'] = "Data Paket berhasil disimpan";
            }else{
                $success['status'] = "FAILED";
                $success['message'] = "Error : Duplicate entry. No Paket sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Paket gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select( "select 
            no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen from dgw_paket where kode_lokasi='".$kode_lokasi."' and no_paket='$request->no_paket' ");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->sql)->select("
            select a.kode_harga,b.nama,a.no_paket,a.harga,a.harga_se,a.harga_e,a.fee,a.curr_fee 
            from dgw_harga a 
            inner join dgw_jenis_harga b on a.kode_harga=b.kode_harga and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='".$kode_lokasi."' and a.no_paket='$request->no_paket' 
            ");
            $res2 = json_decode(json_encode($res2),true);

            $res3 = DB::connection($this->sql)->select("select no_jadwal,tgl_berangkat,no_paket,lama_hari,quota,quota_se,quota_e,no_closing,kurs_closing,id_pbb,tgl_datang,tgl_cetak from dgw_jadwal where kode_lokasi='".$kode_lokasi."' and no_paket='$request->no_paket' ");
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['data_harga'] = $res2;
                $success['data_jadwal'] = $res3;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_harga'] = [];
                $success['data_jadwal'] = [];
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_paket' => 'required',
            'nama' => 'required',
            'kode_curr' => 'required|in:IDR,USD',
            'jenis' => 'required|in:REGULER,PLUS',
            'kode_produk' => 'required',
            'tarif_agen' => 'required|integer',
            'data_harga' => 'required|array',
            'data_harga.*.kode_harga' => 'required',
            'data_harga.*.harga' => 'required|integer',
            'data_harga.*.harga_se' => 'required|integer',
            'data_harga.*.harga_e' => 'required|integer',
            'data_harga.*.fee' => 'required|integer',
            'data_harga.*.curr_fee' => 'required|in:IDR,USD',
            'data_jadwal' => 'required|array',
            'data_jadwal.*.tgl_berangkat' => 'required|date_format:Y-m-d',
            'data_jadwal.*.lama_hari' => 'required|integer',
            'data_jadwal.*.quota' => 'required|integer',
            'data_jadwal.*.quota_se' => 'required|integer',
            'data_jadwal.*.quota_e' => 'required|integer',
            'data_jadwal.*.tgl_datang' => 'required|date_format:Y-m-d',
            'data_jadwal.*.id_jadwal' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            //update idJadwal
            $detJadwal = $request->data_jadwal;
            if (count($detJadwal) > 0){
                for ($i=0;$i < count($detJadwal);$i++){
                   
                    $strSQL = "select no_jadwal from dgw_jadwal where tgl_berangkat='".$detJadwal[$i]['tgl_berangkat']."' and no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";					
                    $res = DB::connection($this->sql)->select($strSQL); 
                    $res = json_decode(json_encode($res),true);
                    if (count($res) > 0){
                        $line = $res[0];
                        $detJadwal[$i]['no_jadwal'] = $line['no_jadwal'];
                    }								
                    else $detJadwal[$i]['no_jadwal'] = "ID";
                }
            }


            $del = DB::connection($this->sql)->table('dgw_paket')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_paket', $request->no_paket)
            ->delete();		

            $ins = DB::connection($this->sql)->insert('insert into dgw_paket(
            no_paket,nama,kode_curr,jenis,kode_produk, tarif_agen,kode_lokasi) values (?, ?, ?, ?, ?, ?, ?)', array($request->no_paket,$request->nama,$request->kode_curr,$request->jenis, $request->kode_produk, $request->tarif_agen,$kode_lokasi));
            
            $del2 = DB::connection($this->sql)->table('dgw_harga')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_paket', $request->no_paket)
            ->delete();		
            $detHarga = $request->data_harga;
            if (count($detHarga) > 0){
                for ($i=0;$i < count($detHarga);$i++){
                    $ins2[$i] = DB::connection($this->sql)->insert("insert into dgw_harga(no_paket,kode_harga,harga,harga_se,harga_e,fee,kode_lokasi,curr_fee) values (?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detHarga[$i]['kode_harga'],$detHarga[$i]['harga'],$detHarga[$i]['harga_se'],$detHarga[$i]['harga_e'],$detHarga[$i]['fee'],$kode_lokasi,$detHarga[$i]['curr_fee']));
                }						
            }
            
            // $strSQL = "select isnull(max(no_jadwal),0) + 1 as id_jadwal from dgw_jadwal where no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";	
            
            // $res = DB::connection($this->sql)->select($strSQL); 
            // $res = json_decode(json_encode($res),true);
            // if (count($res) > 0){
            //     $line = $res[0];							
            //     $idJadwal = intval($line['id_jadwal']);
            // } 
            
            $del3 = DB::connection($this->sql)->table('dgw_jadwal')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_paket', $request->no_paket)
            ->delete();		
            $detJadwal = $request->data_jadwal;
            if (count($detJadwal) > 0){
                for ($i=0;$i < count($detJadwal);$i++){
                    
                    $ins3[$i] = DB::connection($this->sql)->insert("insert into dgw_jadwal(no_paket,no_jadwal,tgl_berangkat,lama_hari,quota,quota_se,quota_e,kode_lokasi, no_closing,kurs_closing,id_pbb,tgl_datang) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($request->no_paket,$detJadwal[$i]['id_jadwal'],$detJadwal[$i]['tgl_berangkat'],$detJadwal[$i]['lama_hari'],$detJadwal[$i]['quota'],$detJadwal[$i]['quota_se'],$detJadwal[$i]['quota_e'],$kode_lokasi,'-',0,'-',$detJadwal[$i]['tgl_datang']));   
                    // $idJadwal = $idJadwal + 1;	
                }						
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = "SUCCESS";
            $success['message'] = "Data Paket berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Paket gagal diubah ".$e;
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
            'no_paket' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sts = false;
            $msg = "something wrong";
            $strSQL = "select count(*) as jml from dgw_jadwal where no_closing <> '-' and  no_paket='".$request->no_paket."' and kode_lokasi='".$kode_lokasi."'";					
            $res = DB::connection($this->sql)->select($strSQL); 
            $res = json_decode(json_encode($res),true);
            if (count($res) > 0){
                $line = $res[0];							
                if ($line['jml'] != 0) {
                    $msg = "Paket tidak dapat dihapus. Terdapat jadwal yang sudah di closing.";
                    $sts = "FAILED";		
                }else{
                    $del = DB::connection($this->sql)->table('dgw_paket')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_paket', $request->no_paket)
                    ->delete();
                    
                    $del2 = DB::connection($this->sql)->table('dgw_harga')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_paket', $request->no_paket)
                    ->delete();
                    
                    $del3 = DB::connection($this->sql)->table('dgw_jadwal')
                    ->where('kode_lokasi', $kode_lokasi)
                    ->where('no_paket', $request->no_paket)
                    ->delete();
                    
                    DB::connection($this->sql)->commit();
                    $msg = "Data Paket berhasil dihapus";
                    $sts = "SUCCESS";
                } 
            }
            

            $success['status'] = $sts;
            $success['message'] = $msg;
            // $success['sql'] = $strSQL;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = "FAILED";
            $success['message'] = "Data Paket gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }
}
