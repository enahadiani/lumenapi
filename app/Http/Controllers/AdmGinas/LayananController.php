<?php

namespace App\Http\Controllers\AdmGinas;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LayananController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $sql = 'dbsaife';
    public $guard = 'admginas';

    public function showLayananDetail(Request $request) {
        try {
            $kode_lokasi= '17';

            $sql = "select a.nama_layanan, b.nama_sublayanan, b.deskripsi, b.file_gambar 
            from lab_layanan a
            inner join lab_detail_layanan c on a.kode_lokasi=c.kode_lokasi and a.id_layanan=c.id_layanan
            inner join lab_sublayanan b on c.kode_lokasi=b.kode_lokasi and c.id_sublayanan=b.id_sublayanan
            where b.kode_lokasi = '$kode_lokasi' and b.id_sublayanan = '$request->id_sublayanan' and a.id_layanan = '$request->id_layanan'";
                
            $res = DB::connection($this->sql)->select($sql);
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

    public function showDaftarLayananDetail(Request $request) {
        try {
            $kode_lokasi= '17';

            $sql1 = "select nama_layanan
            from lab_layanan
            where kode_lokasi = '$kode_lokasi' and id_layanan = '$request->id_layanan'";

            $sql2 = "select a.id_sublayanan, a.nama_sublayanan, a.deskripsi_singkat, b.id_layanan
            from lab_sublayanan a
            inner join lab_detail_layanan b on a.kode_lokasi=b.kode_lokasi and a.id_sublayanan=b.id_sublayanan
            where a.kode_lokasi = '$kode_lokasi' and b.id_layanan = '$request->id_layanan'";
                
            $res1 = DB::connection($this->sql)->select($sql1);
            $res1 = json_decode(json_encode($res1),true);
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res1) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res1;
                $success['data_detail'] = $res2;
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

    public function showDaftarLayanan() {
        try {
            $kode_lokasi= '17';

            $sql= "select a.id_layanan, a.nama_layanan, b.id_sublayanan, b.nama_sublayanan
            from lab_layanan a
            inner join lab_detail_layanan c on a.kode_lokasi=c.kode_lokasi and a.id_layanan=c.id_layanan
            inner join lab_sublayanan b on c.kode_lokasi=b.kode_lokasi and c.id_sublayanan=b.id_sublayanan
            where c.kode_lokasi = '$kode_lokasi'";

            $jumlah = "select max(jumlah) as jumlah 
            from (
                select kode_lokasi, count(id_layanan) as jumlah
                from lab_detail_layanan
                group by id_layanan, kode_lokasi
            ) b where kode_lokasi = '$kode_lokasi'";
                
            $res = DB::connection($this->sql)->select($sql);
            $jum = DB::connection($this->sql)->select($jumlah);
            $res = json_decode(json_encode($res),true);
            $jum = json_decode(json_encode($jum),true);
            $result = array();
            foreach($res as $key) {
                $result[$key['id_layanan']][] = $key;
            }

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $result;
                $success['jumlah'] = $jum;
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

    public function isUnik($isi,$kode_lokasi){
        $auth = DB::connection($this->sql)->select("select id_layanan from lab_layanan where id_layanan ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->id)){
                if($request->id == "all"){
                    $filter = "";
                }else{
                    $filter = " and id_layanan='".$request->id."' ";
                }
                $sql= "select id_layanan,nama_layanan from lab_layanan where kode_lokasi='".$kode_lokasi."' $filter ";
            }
            else {
                $sql = "select id_layanan,nama_layanan from lab_layanan where kode_lokasi= '".$kode_lokasi."'";
            }

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

    public function show(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select id_layanan,nama_layanan from lab_layanan where kode_lokasi= '".$kode_lokasi."' and id_layanan = '".$r->id_layanan."'";

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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'id_layanan' => 'required|max:10',
            'nama_layanan' => 'required|max:100'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->id_layanan,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into lab_layanan(id_layanan,nama_layanan,kode_lokasi) values ('".$request->id_layanan."','".$request->nama_layanan."','".$kode_lokasi."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Layanan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Layanan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Bidang gagal disimpan ".$e;
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
            'id_layanan' => 'required|max:10',
            'nama_layanan' => 'required|max:100'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('lab_layanan')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('id_layanan', $request->id_layanan)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into lab_layanan(id_layanan,nama_layanan,kode_lokasi) values ('".$request->id_layanan."','".$request->nama_layanan."','".$kode_lokasi."')");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Layanan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Layanan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    // public function destroy(Request $request)
    // {
    //     $this->validate($request, [
    //         'kode_bidang' => 'required'
    //     ]);
    //     DB::connection($this->sql)->beginTransaction();
        
    //     try {
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }
            
    //         $del = DB::connection($this->sql)->table('par_bidang')
    //         ->where('kode_lokasi', $kode_lokasi)
    //         ->where('kode_bidang', $request->kode_bidang)
    //         ->delete();

    //         DB::connection($this->sql)->commit();
    //         $success['status'] = true;
    //         $success['message'] = "Data Bidang berhasil dihapus";
            
    //         return response()->json($success, $this->successStatus); 
    //     } catch (\Throwable $e) {
    //         DB::connection($this->sql)->rollback();
    //         $success['status'] = false;
    //         $success['message'] = "Data Bidang gagal dihapus ".$e;
            
    //         return response()->json($success, $this->successStatus); 
    //     }	
    // }

    
}
