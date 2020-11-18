<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BayarController extends Controller
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

            $res = DB::connection($this->db)->select("select no_bayar,convert(varchar,tanggal,103) as tanggal, nim, keterangan,periode,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input  from dev_bayar_m where kode_lokasi='$kode_lokasi'	 
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
            'no_tagihan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

           
            DB::connection($this->db)->beginTransaction();

            $no_bukti = $this->generateKode("dev_bayar_m", "no_tagihan", $kode_lokasi."-PBR".substr($periode,2,4).".", "0001");
            
            $ins = DB::connection($this->db)->insert("insert into dev_bayar_m (no_tagihan,kode_lokasi,tgl_input,tanggal,periode,keterangan,nim) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$request->tanggal."','".$request->periode."','".$request->keterangan."','".$request->nim."')");

            if (count($request->no_tagihan) > 0){
                for ($j=0;$j < count($request->no_tagihan);$j++){
                    $ins2[$j] = DB::connection($this->db)->insert("insert into dev_bayar_d (no_bayar,kode_lokasi,no_tagihan,nilai) values ('".$no_bukti."','".$kode_lokasi."','".$request->no_tagihan[$j]."','".$request->nilai[$j]."')");
                }
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['no_bayar'] = $no_bukti;
            $success['message'] = "Data Pembayaran berhasil disimpan ";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
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
            'no_bayar' => 'required',
            'tanggal' => 'required',
            'nim' => 'required',
            'keterangan' => 'required',
            'periode' => 'required',
            'no_tagihan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            DB::connection($this->db)->beginTransaction();

            $del1 = DB::connection($this->db)->table('dev_bayar_m')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bayar)->delete();

            $del2 = DB::connection($this->db)->table('dev_bayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $request->no_bayar)->delete();

            $ins = DB::connection($this->db)->insert("insert into dev_bayar_m (no_bayar,kode_lokasi,tgl_input,tanggal,periode,keterangan,nim) values ('".$request->no_bayar."','".$kode_lokasi."',getdate(),'".$request->tanggal."','".$request->periode."','".$request->keterangan."','".$request->nim."')");

            if (count($request->no_tagihan) > 0){
                for ($j=0;$j < count($request->no_tagihan);$j++){
                    $ins2[$j] = DB::connection($this->db)->insert("insert into dev_bayar_d (no_bayar,kode_lokasi,no_tagihan,nilai) values ('".$request->no_bayar."','".$kode_lokasi."','".$request->no_tagihan[$j]."','".$request->nilai[$j]."')");
                }
            }	
            
            DB::connection($this->db)->commit();
            $success['status'] = $sts;
            $success['no_bayar'] = $request->no_bayar;
            $success['message'] = "Data Pembayaran berhasil diubah ";
            return response()->json($success, $this->successStatus); 

        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy($no_bayar)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('dev_bayar_m')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $no_bayar)->delete();

            $del2 = DB::connection($this->db)->table('dev_bayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bayar', $no_bayar)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show($no_bayar)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bayar,tanggal,periode,keterangan,periode from dev_bayar_m where no_bayar = '".$no_bayar."' and kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("
            select a.nim,a.no_tagihan,a.keterangan,isnull(b.tagihan,0) as tagihan,isnull(c.bayar,0) as bayar,isnull(b.tagihan,0)-isnull(c.bayar,0) as sisa_tagihan 
            from dev_tagihan_m a 
            left join (select no_tagihan,kode_lokasi,sum(nilai) as tagihan 
                        from  dev_tagihan_d 
                        group by no_tagihan,kode_lokasi 
                    ) b on a.no_tagihan=b.no_tagihan and a.kode_lokasi=b.kode_lokasi 
            left join (select no_tagihan,kode_lokasi,sum(nilai) as bayar 
                    from  dev_bayar_d 
                    group by no_tagihan,kode_lokasi
                    ) c on a.no_tagihan=c.no_tagihan and a.kode_lokasi=c.kode_lokasi ");
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

