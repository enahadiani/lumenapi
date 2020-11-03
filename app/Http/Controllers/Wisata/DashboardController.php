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

    public function getTopDaerah() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $select = "select top 5 sum(a.jumlah) as jumlah, b.nama as mitra, c.nama as camat, g.nama as bidang
                from par_kunj_d a
                inner join par_mitra b on a.kode_lokasi=b.kode_lokasi and a.kode_mitra=b.kode_mitra
                inner join par_camat c on b.kode_lokasi=c.kode_lokasi and b.kecamatan=c.kode_camat
                inner join par_mitra_subjenis d on b.kode_lokasi=d.kode_lokasi and b.kode_mitra=d.kode_mitra
                inner join par_subjenis e on d.kode_lokasi=e.kode_lokasi and d.kode_subjenis=e.kode_subjenis
                inner join par_jenis f on e.kode_lokasi=f.kode_lokasi and e.kode_jenis=f.kode_jenis
                inner join par_bidang g on f.kode_lokasi=g.kode_lokasi and f.kode_bidang=g.kode_bidang
                where year(a.tanggal) = '$yearNow' and a.kode_lokasi = '$kode_lokasi'
                group by b.nama, c.nama, g.nama
                order by jumlah desc";
            
            $res = DB::connection($this->sql)->select($select);						
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0) {
                $success['status'] = true;
                $success['data'] = $res;
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

    public function getTopMitra() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $select = "select distinct top 5 sum(a.jumlah) as jumlah, b.kode_mitra, b.nama as mitra
                from par_kunj_d a
                inner join par_mitra b on a.kode_lokasi=b.kode_lokasi and a.kode_mitra=b.kode_mitra
                where year(a.tanggal) = '$yearNow' and a.kode_lokasi = '$kode_lokasi'
                group by b.nama, b.kode_mitra
                order by jumlah desc";

            $res = DB::connection($this->sql)->select($select);						
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0) {
                $success['status'] = true;
                $success['data'] = $res;
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

    public function getKunjunganBulanan() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $yearNow = date('Y');
            $select = "select e.nama,
                sum(case when month(a.tanggal)='01' then a.jumlah else 0 end) n1,
                sum(case when month(a.tanggal)='02' then a.jumlah else 0 end) n2,
                sum(case when month(a.tanggal)='03' then a.jumlah else 0 end) n3,
                sum(case when month(a.tanggal)='04' then a.jumlah else 0 end) n4,
                sum(case when month(a.tanggal)='05' then a.jumlah else 0 end) n5,
                sum(case when month(a.tanggal)='06' then a.jumlah else 0 end) n6,
                sum(case when month(a.tanggal)='07' then a.jumlah else 0 end) n7,
                sum(case when month(a.tanggal)='08' then a.jumlah else 0 end) n8,
                sum(case when month(a.tanggal)='09' then a.jumlah else 0 end) n9,
                sum(case when month(a.tanggal)='10' then a.jumlah else 0 end) n10,
                sum(case when month(a.tanggal)='11' then a.jumlah else 0 end) n11,
                sum(case when month(a.tanggal)='12' then a.jumlah else 0 end) n12
                from par_kunj_d a
                inner join par_subjenis c on a.kode_subjenis=c.kode_subjenis and a.kode_lokasi=c.kode_lokasi
                inner join par_jenis d on c.kode_jenis=d.kode_jenis and c.kode_lokasi=d.kode_lokasi
                inner join par_bidang e on d.kode_bidang=e.kode_bidang and d.kode_lokasi=e.kode_lokasi
                where year(a.tanggal) = '$yearNow' and a.kode_lokasi = '$kode_lokasi'
                group by e.nama";
            
            $res = DB::connection($this->sql)->select($select);						
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0) {
                
                for($i=0;$i<count($res);$i++){
                    $daftar[] = array('name'=>$res[$i]['nama'],
                    'data'=> array($res[$i]['n1'],$res[$i]['n2'],$res[$i]['n3'],$res[$i]['n4'],$res[$i]['n5'],$res[$i]['n6'],$res[$i]['n7'],$res[$i]['n8'],$res[$i]['n9'],$res[$i]['n10'],$res[$i]['n11'],$res[$i]['n12'])
                );
                }

                $success['status'] = true;
                $success['data'] = $daftar;
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