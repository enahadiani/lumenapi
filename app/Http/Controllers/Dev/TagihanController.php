<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TagihanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrv2';
    public $guard = 'admin';

    
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

            $res = DB::connection($this->db)->select("select no_tagihan,convert(varchar,tanggal,103) as tanggal, nim, keterangan,periode,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input  from dev_tagihan_m where kode_lokasi='$kode_lokasi'	 
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
                $success['data']= [];
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required',
            'nim' => 'required',
            'keterangan' => 'required',
            'periode' => 'required',
            'kode_jenis' => 'required|array',
            'nilai' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

           
            DB::connection($this->db)->beginTransaction();

            $no_bukti = $this->generateKode("dev_tagihan_m", "no_tagihan", $kode_lokasi."-TGH".substr($request->periode,2,4).".", "0001");
            
            $ins = DB::connection($this->db)->insert("insert into dev_tagihan_m (no_tagihan,kode_lokasi,tgl_input,tanggal,periode,keterangan,nim) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$request->tanggal."','".$request->periode."','".$request->keterangan."','".$request->nim."')");

            if (count($request->kode_jenis) > 0){
                for ($j=0;$j < count($request->kode_jenis);$j++){
                    $ins2[$j] = DB::connection($this->db)->insert("insert into dev_tagihan_d (no_tagihan,kode_lokasi,kode_jenis,nilai) values ('".$no_bukti."','".$kode_lokasi."','".$request->kode_jenis[$j]."','".$request->nilai[$j]."')");
                }
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_tagihan'] = $no_bukti;
            $success['message'] = "Data Tagihan berhasil disimpan ";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal disimpan ".$e;
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
            'no_tagihan' => 'required',
            'tanggal' => 'required',
            'nim' => 'required',
            'keterangan' => 'required',
            'periode' => 'required',
            'kode_jenis' => 'required|array',
            'nilai' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            DB::connection($this->db)->beginTransaction();

            $del1 = DB::connection($this->db)->table('dev_tagihan_m')->where('kode_lokasi', $kode_lokasi)->where('no_tagihan', $request->no_tagihan)->delete();

            $del2 = DB::connection($this->db)->table('dev_tagihan_d')->where('kode_lokasi', $kode_lokasi)->where('no_tagihan', $request->no_tagihan)->delete();

            $ins = DB::connection($this->db)->insert("insert into dev_tagihan_m (no_tagihan,kode_lokasi,tgl_input,tanggal,periode,keterangan,nim) values ('".$request->no_tagihan."','".$kode_lokasi."',getdate(),'".$request->tanggal."','".$request->periode."','".$request->keterangan."','".$request->nim."')");

            if (count($request->kode_jenis) > 0){
                for ($j=0;$j < count($request->kode_jenis);$j++){
                    $ins2[$j] = DB::connection($this->db)->insert("insert into dev_tagihan_d (no_tagihan,kode_lokasi,kode_jenis,nilai) values ('".$request->no_tagihan."','".$kode_lokasi."','".$request->kode_jenis[$j]."','".$request->nilai[$j]."')");
                }
            }	
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_tagihan'] = $request->no_tagihan;
            $success['message'] = "Data Tagihan berhasil diubah ";
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request,[
            'no_tagihan' => 'required'
        ]);
        
        $no_tagihan = $request->tagihan;
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('dev_tagihan_m')->where('kode_lokasi', $kode_lokasi)->where('no_tagihan', $no_tagihan)->delete();

            $del2 = DB::connection($this->db)->table('dev_tagihan_d')->where('kode_lokasi', $kode_lokasi)->where('no_tagihan', $no_tagihan)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Tagihan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Tagihan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_tagihan' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_tagihan = $request->no_tagihan;

            $res = DB::connection($this->db)->select("select no_tagihan,tanggal,periode,keterangan,periode from dev_tagihan_m where no_tagihan = '".$no_tagihan."' and kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_jenis,b.nama as nama_jenis,a.nilai
                    from dev_tagihan_d a 
                    inner join dev_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi 
                    where a.no_tagihan = '".$no_tagihan."' and a.kode_lokasi='".$kode_lokasi."'
                    ");
            $res2= json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
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

