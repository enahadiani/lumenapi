<?php 

namespace App\Http\Controllers\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller 
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $errorStatus = 500;
    public $guard = 'toko';
    public $sql = 'tokoaws';

    public function getDataKunjungan() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $convertYearNow = strval($yearNow);
            $yearYesterday = $convertYearNow - 1;
            $convertYearYesterday = strval($yearYesterday);

            $selectYoY = "select isnull(sum(a.jumlah),0) as jumlah from par_kunj_d a where year(a.tanggal)='$yearNow' and a.kode_lokasi='$kode_lokasi'";
            $selectYearYesterday = "select isnull(sum(a.jumlah),0) as jumlah from par_kunj_d a where year(a.tanggal)='$convertYearYesterday' and a.kode_lokasi='$kode_lokasi'";
            
            $resYoY = DB::connection($this->sql)->select($selectYoY);						
            $resYoY = json_decode(json_encode($resYoY),true);
            $resYearYesterday = DB::connection($this->sql)->select($selectYearYesterday);						
            $resYearYesterday = json_decode(json_encode($resYearYesterday),true);

            if($resYearYesterday[0]['jumlah'] > 0) {
                $persentase = $resYoY[0]['jumlah']/$resYearYesterday[0]['jumlah'];
            } else {
                $persentase = 100;
            }

            if($resYoY[0]['jumlah'] > $resYearYesterday[0]['jumlah']) {
                $pembanding = "besar";
            } else {
                $pembanding = "kecil";
            }

            $success['status'] = true;
            $success['YoYnow'] = $resYoY[0]['jumlah'];
            $success['YoYyesterday'] = $resYearYesterday[0]['jumlah'];
            $success['persentase'] = $persentase;
            $success['banding'] = $pembanding;
            return response()->json(['data'=>$success], $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->errorStatus);
        }
    }

    public function getDataBidang() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $select = "select top 1 isnull(sum(a.jumlah),0) as jumlah, d.nama 
                from par_kunj_d a
                inner join par_subjenis b on a.kode_subjenis=b.kode_subjenis and a.kode_lokasi=b.kode_lokasi
                inner join par_jenis c on b.kode_jenis=c.kode_jenis and b.kode_lokasi=c.kode_lokasi
                inner join par_bidang d on c.kode_lokasi=d.kode_lokasi and c.kode_bidang=d.kode_bidang
                where a.kode_lokasi = '$kode_lokasi' and year(a.tanggal)='$yearNow'
                group by d.nama, d.kode_bidang
                order by jumlah desc";

            $res = DB::connection($this->sql)->select($select);						
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0) {
                $success['status'] = true;
                $success['data'] = $res[0];
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }

            return response()->json(['data'=>$success], $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->errorStatus);
        }
    }

    public function getDataMitra() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $select = "select top 1 isnull(sum(a.jumlah),0) as jumlah, b.nama
                from par_kunj_d a
                inner join par_mitra b on a.kode_lokasi=b.kode_lokasi and a.kode_mitra=b.kode_mitra
                where a.kode_lokasi = '$kode_lokasi' and year(a.tanggal)='$yearNow'
                group by b.nama, a.kode_mitra
                order by jumlah desc";
            
            $res = DB::connection($this->sql)->select($select);						
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0) {
                $success['status'] = true;
                $success['data'] = $res[0];
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }

            return response()->json(['data'=>$success], $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->errorStatus);
        }
    }

}
?>