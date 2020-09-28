<?php

namespace App\Http\Controllers\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KunjController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';
    public $nobukti = '';

    public function getMitra() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_mitra,nama,alamat from par_mitra where kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function getMitraSub($kode_mitra) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_subjenis,a.nama from par_subjenis a inner join par_mitra_subjenis b on a.kode_subjenis=b.kode_subjenis and a.kode_lokasi=b.kode_lokasi where b.kode_mitra='".$kode_mitra."' and b.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function getTahunList() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select year(getdate())  as tahun union select year(getdate()) -1 as tahun union select year(getdate()) -2 as tahun ");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function getTglServer() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select getdate() as tglnow ");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function getJumTgl($tahun,$bulan) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select substring(convert(varchar,  dateadd(s,-1,dateadd(mm, datediff(m,0,'".$tahun."-".$bulan."-01')+1,0)) ,112),7,2) as jum_tgl ");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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

    public function index(Request $request)
    {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_bukti)){
                if($request->no_bukti == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_bukti='".$request->no_bukti."' ";
                }
                $sql= "select a.no_bukti,a.tahun,a.bulan,b.nama as nama_mitra, b.alamat, c.nama as nama_subjenis 
                      from par_kunj_m a 
                      inner join par_mitra b on a.kode_mitra=b.kode_mitra and a.kode_lokasi=b.kode_lokasi 
                      inner join par_subjenis c on a.kode_subjenis=c.kode_subjenis and a.kode_lokasi=c.kode_lokasi 
                      where a.kode_lokasi='".$kode_lokasi."' ".$filter;
            }
            else {
                $sql= "select a.no_bukti,a.tahun,a.bulan,b.nama as nama_mitra, b.alamat,  c.nama as nama_subjenis 
                      from par_kunj_m a 
                      inner join par_mitra b on a.kode_mitra=b.kode_mitra and a.kode_lokasi=b.kode_lokasi 
                      inner join par_subjenis c on a.kode_subjenis=c.kode_subjenis and a.kode_lokasi=c.kode_lokasi 
                      where a.kode_lokasi='".$kode_lokasi."' ";
            }

            $res = DB::connection($this->db)->select($sql);
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

    public function edit(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select * from par_kunj_m where no_bukti='".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."'");
            $res = json_decode(json_encode($res),true);

            $res2 = DB::connection($this->db)->select( "select tanggal,jumlah from par_kunj_d where no_bukti='".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."' ");
            $res2 = json_decode(json_encode($res2),true);


            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = "SUCCESS";
                $success['data'] = $res;
                $success['arrbid'] = $res2;                                
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['arrbid'] = [];                
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

    public function getBukti($kode_mitra,$kode_subjenis,$tahun,$bulan,$kode_lokasi){            
        $auth = DB::connection($this->db)->select("select no_bukti from par_kunj_m where kode_mitra ='".$kode_mitra."' and kode_subjenis ='".$kode_subjenis."' and tahun ='".$tahun."' and bulan ='".$bulan."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
            $this->nobukti = $auth[0]["no_bukti"];
        }else{
            return true;
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function store(Request $request)
    {
        $this->validate($request, [            
            'tanggal' => 'required',
            'kode_mitra' => 'required|max:10',
            'kode_subjenis' => 'required|max:10',            
            'tahun' => 'required|max:4',            
            'bulan' => 'required|max:2',
            'arrtgl'=>'required|array',
            'arrtgl.*.tanggal' => 'required',                                    
            'arrtgl.*.jumlah' => 'required|integer'                                    
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                       
            if($this->getBukti($request->kode_mitra,$request->kode_subjenis,$request->tahun,$request->bulan,$kode_lokasi)){

                $periode = substr($request->tanggal,2,2).substr($request->tanggal,5,2);
                $no_bukti = $this->generateKode("par_kunj_m", "no_bukti", $kode_lokasi."-KJ".$periode.".", "0001");

                $ins = DB::connection($this->db)->insert("insert into par_kunj_m(no_bukti,tanggal,kode_lokasi,tgl_input,nik_user,kode_mitra,kode_subjenis,tahun,bulan) values 
                                                        ('".$no_bukti."','".$request->tanggal."','".$kode_lokasi."',getdate(),'".$nik."','".$request->kode_mitra."','".$request->kode_subjenis."','".$request->tahun."','".$request->bulan."')");

                $arrtgl = $request->arrtgl;
                if (count($arrtgl) > 0){
                    for ($i=0;$i <count($arrtgl);$i++){                
                        $ins2[$i] = DB::connection($this->db)->insert("insert into par_kunj_d(no_bukti,kode_mitra,kode_subjenis,tanggal,jumlah,kode_lokasi) values  
                                                                      ('".$no_bukti."','".$request->kode_mitra."','".$request->kode_subjenis."','".$arrtgl[$i]['tanggal']."','".floatval($arrtgl[$i]['jumlah'])."','".$kode_lokasi."')");                    
                    }						
                }	
                                                                      
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Mitra berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Mitra sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Mitra gagal disimpan ".$e;
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
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'kode_mitra' => 'required|max:10',
            'kode_subjenis' => 'required|max:10',            
            'tahun' => 'required|max:4',            
            'bulan' => 'required|max:2',
            'arrtgl'=>'required|array',
            'arrtgl.*.tanggal' => 'required',                                    
            'arrtgl.*.jumlah' => 'required|integer'                          
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('par_kunj_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('par_kunj_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into par_kunj_m(no_bukti,tanggal,kode_lokasi,tgl_input,nik_user,kode_mitra,kode_subjenis,tahun,bulan) values 
                                                    ('".$request->no_bukti."','".$request->tanggal."','".$kode_lokasi."',getdate(),'".$nik."','".$request->kode_mitra."','".$request->kode_subjenis."','".$request->tahun."','".$request->bulan."')");

            $arrtgl = $request->arrtgl;
            if (count($arrtgl) > 0){
                for ($i=0;$i <count($arrtgl);$i++){                
                    $ins2[$i] = DB::connection($this->db)->insert("insert into par_kunj_d(no_bukti,kode_mitra,kode_subjenis,tanggal,jumlah,kode_lokasi) values  
                                                                  ('".$request->no_bukti."','".$request->kode_mitra."','".$request->kode_subjenis."','".$arrtgl[$i]['tanggal']."','".floatval($arrtgl[$i]['jumlah'])."','".$kode_lokasi."')");                    
                }						
            }	

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kunjungan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kunjungan gagal diubah ".$e;
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
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('par_kunj_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('par_kunj_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kunjungan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kunjungan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
