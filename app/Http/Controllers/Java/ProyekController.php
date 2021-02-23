<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProyekController extends Controller {
    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnikProyek($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_proyek from java_proyek where no_proyek ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function isUnikKontrak($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_kontrak from java_proyek where no_kontrak ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function checkProyek(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_proyek from java_proyek where no_proyek ='".$request->query('kode')."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $success['status'] = false;
        }else{
            $success['status'] = true;
        }

        return response()->json($success, $this->successStatus);
    }

    public function checkKontrak(Request $request){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_kontrak from java_proyek where no_kontrak ='".$request->query('kode')."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $success['status'] = false;
        }else{
            $success['status'] = true;
        }

        return response()->json($success, $this->successStatus);
    }

    public function getCustomer() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_vendor)){
                if($request->kode_vendor != "" ){

                    $filter = " and a.kode_vendor='$request->kode_vendor' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select kode_vendor, nama 
            from java_vendor where kode_lokasi='".$kode_lokasi."' $filter";

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

    public function index(Request $request) {
        try {

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->no_proyek)){
                if($request->no_proyek == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_proyek='$request->no_proyek' ";
                }
                $sql= "select a.no_proyek, a.keterangan, a.kode_cust, a.no_kontrak, a.tgl_mulai, a.tgl_selesai, a.nilai, a.ppn
                b.nama as nama 
                from java_proyek a inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select no_proyek, no_kontrak, tgl_selesai, nilai,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_proyek
                where kode_lokasi= '$kode_lokasi'";
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

    public function store(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $this->validate($request, [
                'no_proyek' => 'required',
                'no_kontrak' => 'required',
                'keterangan' => 'required',
                'kode_cust' => 'required',
                'tgl_mulai' => 'required',
                'tgl_selesai' => 'required',
                'nilai' => 'required',
                'ppn' => 'required',
                'periode' => 'required'
            ]);

            if($this->isUnikKontrak($request->no_kontrak, $kode_lokasi) && $this->isUnikProyek($request->no_proyek, $kode_lokasi)) {
                $insert = "insert into java_proyek(no_proyek, kode_lokasi, keterangan, kode_cust, no_kontrak, tgl_selesai, tgl_mulai, nilai, ppn, periode, tgl_input)
                values ('$request->no_proyek', '$kode_lokasi', '$request->keterangan', '$request->kode_cust', '$request->no_kontrak',
                '$request->tgl_selesai', '$request->tgl_mulai','$request->nilai', '$request->ppn', '$request->periode', getdate()";

                DB::connection($this->sql)->insert($insert);

                $success['status'] = true;
                $success['kode'] = $request->no_proyek;
                $success['message'] = "Data Proyek berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Proyek atau No Kontrak sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $this->validate($request, [
                'no_proyek' => 'required',
                'no_kontrak' => 'required',
                'keterangan' => 'required',
                'kode_cust' => 'required',
                'tgl_mulai' => 'required',
                'tgl_selesai' => 'required',
                'nilai' => 'required',
                'ppn' => 'required',
                'periode' => 'required'
            ]);

            DB::connection($this->sql)->beginTransaction();
            
            DB::connection($this->sql)->table('java_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            $insert = "insert into java_proyek(no_proyek, kode_lokasi, keterangan, kode_cust, no_kontrak, tgl_selesai, tgl_mulai, nilai, ppn, periode, tgl_input)
            values ('$request->no_proyek', '$kode_lokasi', '$request->keterangan', '$request->kode_cust', '$request->no_kontrak',
            '$request->tgl_selesai', '$request->tgl_mulai', '$request->nilai', '$request->ppn', '$request->periode', getdate()";

            DB::connection($this->sql)->insert($insert);
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = $request->no_proyek;
            $success['message'] = "Data Proyek berhasil disimpan";
            
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function destroy(Request $request) {
        try {
            $this->validate($request, [
                'kode_vendor' => 'required'
            ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->sql)->table('java_proyek')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_proyek', $request->no_proyek)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data Proyek berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}

?>