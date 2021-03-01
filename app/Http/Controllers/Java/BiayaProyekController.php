<?php

namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BiayaProyekController extends Controller {
    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function isUnikDokumen($isi,$kode_lokasi){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $auth = DB::connection($this->sql)->select("select no_dokumen from java_beban where no_dokumen ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function getCust() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_cust)){
                if($request->kode_cust != "" ){

                    $filter = " and kode_cust='$request->kode_cust' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select select kode_cust, nama from java_cust 
            where kode_lokasi='".$kode_lokasi."' $filter";

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

    public function getVendor() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->kode_vendor)){
                if($request->kode_vendor != "" ){

                    $filter = " and kode_vendor='$request->kode_vendor' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select kode_vendor, nama from java_vendor 
            where kode_lokasi='".$kode_lokasi."' $filter";

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

    public function getProyek(Request $request) {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->no_proyek)){
                if($request->no_proyek != "" ){

                    $filter = " and a.no_proyek='$request->no_proyek' ";
                }else{
                    $filter = "";
                }
            }else{
                $filter = "";
            }

            $sql= "select a.no_proyek, a.no_rab, nilai_anggaran from java_rab_m a
            inner join java_proyek b on a.no_proyek=b.no_proyek and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='".$kode_lokasi."' and b.kode_cust = '$request->kode_cust' $filter";

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

            if(isset($request->no_bukti)){
                if($request->no_bukti == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.no_bukti='$request->no_bukti' ";
                }
                $sql= "select a.no_bukti, a.tanggal, a.keterangan, a.no_dokumen, a.kode_vendor, a.kode_cust, 
                convert(varchar(10), a.tanggal, 120) as tanggal, a.nilai, a.status,
                b.nama as nama_vendor, c.nama as nama_customer 
                from java_beban a inner join java_vendor b on a.kode_vendor=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
                inner join java_cust c on a.kode_cust=c.kode_cust and a.kode_lokasi=c.kode_lokasi 
                where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }else{
                $sql = "select no_bukti, no_proyek, keterangan, nilai,
                case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status from java_beban
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
        $this->validate($request, [
            'tanggal' => 'required',
            'kode_vendor' => 'required',
            'kode_cust' => 'required',
            'nilai' => 'required',
            'status' => 'required',
            'no_proyek' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tanggal = $request->tanggal;
            $periode = substr($tanggal,0,4).substr($tanggal,5,2);
            $per = substr($periode, 2, 4);
            $no_bukti = $this->generateKode('java_beban', 'no_bukti', $kode_lokasi."-BYP$per".".", '00001');

            if($this->isUnikDokumen($request->no_dokumen, $kode_lokasi)) {
                $insert = "insert into java_beban(no_bukti, kode_lokasi, tanggal, keterangan, no_dokumen, kode_vendor, nilai, status, no_proyek, kode_cust, tgl_input)
                values ('$no_bukti', '$kode_lokasi', '$request->tanggal', '$request->keterangan', '$request->no_dokumen',
                '$request->kode_vendor', '$request->nilai','$request->status', '$request->no_proyek', '$request->kode_cust', getdate())";

                DB::connection($this->sql)->insert($insert);

                $success['status'] = true;
                $success['kode'] = $no_bukti;
                $success['message'] = "Data Biaya Proyek berhasil disimpan";
            } else {
                $success['status'] = false;
                $success['kode'] = "-";
                $success['jenis'] = "duplicate";
                $success['message'] = "Error : Duplicate entry. No Dokumen sudah ada di database!";
            }

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function update(Request $request) {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'kode_vendor' => 'required',
            'kode_cust' => 'required',
            'nilai' => 'required',
            'status' => 'required',
            'no_proyek' => 'required',
            'no_dokumen' => 'required',
            'keterangan' => 'required',
        ]);
        DB::connection($this->sql)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            DB::connection($this->sql)->table('java_beban')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $insert = "insert into java_beban(no_bukti, kode_lokasi, tanggal, keterangan, no_dokumen, kode_vendor, nilai, status, no_proyek, kode_cust, tgl_input)
            values ('$no_bukti', '$kode_lokasi', '$request->tanggal', '$request->keterangan', '$request->no_dokumen',
            '$request->kode_vendor', '$request->nilai','$request->status', '$request->no_proyek', '$request->kode_cust', getdate())";

            DB::connection($this->sql)->insert($insert);

            $success['status'] = true;
            $success['kode'] = $no_bukti;
            $success['message'] = "Data Biaya Proyek berhasil disimpan";
            
            DB::connection($this->sql)->commit();
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
                'no_bukti' => 'required'
            ]);

            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            DB::connection($this->sql)->table('java_beban')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $success['status'] = true;
            $success['message'] = "Data Biaya Proyek berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}

?>