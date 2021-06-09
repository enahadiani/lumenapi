<?php
namespace App\Http\Controllers\Java;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth; 

class DashboardController extends Controller {
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getPeriode()
    {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql= "select distinct concat(substring(periode, 1, 4), '-', substring(periode, 5, 2)) as periode 
            from java_proyek 
            where kode_lokasi='".$kode_lokasi."'";

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

    public function getDetailPembayaranCustomer(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select convert(varchar,a.tanggal,103) as tanggal, b.no_dokumen, a.keterangan
            from java_bayar a inner join java_bayar_detail b on a.no_bayar=b.no_bayar and a.kode_lokasi=b.kode_lokasi
            where a.kode_cust = '".$request->query('customer')."' and a.kode_lokasi = '$kode_lokasi'";

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

    public function getDetailPembayaranSupplier(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select convert(varchar,tanggal,103) as tanggal, no_dokumen, keterangan, b.nama, a.status from java_beban a
            inner join java_vendor b on a.kode_vendor=b.kode_vendor and a.kode_lokasi=b.kode_lokasi
            where a.no_proyek = '".$request->query('proyek')."' and a.kode_lokasi = '$kode_lokasi'";

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

    public function getProfitDashboard(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            // format(a.tanggal, 'MM') = '".$request->query('bulan')."' and year(a.tanggal) = '".$request->query('tahun')."
            $sql = "select isnull(sum(a.nilai), 0) as nilai_proyek, isnull(sum(b.nilai), 0) as nilai_beban from java_proyek a
            left join (select a.no_proyek,a.kode_lokasi, sum(a.nilai) as nilai
            from java_beban a
            where a.kode_lokasi='11'
            group by a.no_proyek,a.kode_lokasi
            ) b on a.no_proyek=b.no_proyek and a.kode_lokasi=b.kode_lokasi";

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

    public function getProjectAktif(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select a.no_proyek, convert(varchar,tgl_selesai,103) as tgl_selesai,b.nama as nama_cust,
            isnull(c.nilai,0) as rab,isnull(d.nilai,0) as beban, isnull(e.nilai,0) as tagihan,isnull(f.nilai,0) as bayar, 
            a.nilai as nilai_proyek, b.kode_cust
            from java_proyek a
            inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            left join (select b.no_proyek,b.kode_lokasi,sum(a.jumlah*a.harga) as nilai
            from java_rab_d a
            inner join java_rab_m b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='11'
            group by b.no_proyek,b.kode_lokasi
            )c on a.no_proyek=c.no_proyek and a.kode_lokasi=b.kode_lokasi
            left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
            from java_beban  a
            where a.kode_lokasi='11'
            group by a.no_proyek,a.kode_lokasi
            )d on a.no_proyek=d.no_proyek and a.kode_lokasi=d.kode_lokasi
            left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
            from java_tagihan  a
            where a.kode_lokasi='11'
            group by a.no_proyek,a.kode_lokasi
            )e on a.no_proyek=e.no_proyek and a.kode_lokasi=e.kode_lokasi
            left join (select b.no_proyek,a.kode_lokasi,sum(a.nilai_bayar) as nilai
            from java_bayar_detail a
            inner join java_tagihan b on a.no_tagihan=b.no_tagihan and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='11'
            group by b.no_proyek,a.kode_lokasi
            )f on a.no_proyek=f.no_proyek and a.kode_lokasi=f.kode_lokasi
            where a.flag_aktif = '0'";
            //  and a.nilai<>0 and isnull(c.nilai,0)<>0 and isnull(d.nilai,0)<>0
            // and format(a.tgl_mulai, 'MM') = '".$request->query('bulan')."' and year(a.tgl_mulai) = '".$request->query('tahun')."'
            // $sql = "select a.no_kontrak, convert(varchar,tgl_selesai,103) as tgl_selesai,b.nama as nama_cust,
            // isnull(c.nilai,0) as rab,isnull(d.nilai,0) as beban, isnull(e.nilai,0) as tagihan,isnull(f.nilai,0) as bayar, 
            // a.nilai as nilai_proyek
            // from java_proyek a
            // inner join java_cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi
            // left join (select b.no_proyek,b.kode_lokasi,sum(a.jumlah*a.harga) as nilai
            // from java_rab_d a
            // inner join java_rab_m b on a.no_rab=b.no_rab and a.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='04'
            // group by b.no_proyek,b.kode_lokasi
            // )c on a.no_proyek=c.no_proyek and a.kode_lokasi=b.kode_lokasi
            // left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
            // from java_beban  a
            // where a.kode_lokasi='04'
            // group by a.no_proyek,a.kode_lokasi
            // )d on a.no_proyek=d.no_proyek and a.kode_lokasi=d.kode_lokasi
            // left join (select a.no_proyek,a.kode_lokasi,sum(a.nilai) as nilai
            // from java_tagihan  a
            // where a.kode_lokasi='04'
            // group by a.no_proyek,a.kode_lokasi
            // )e on a.no_proyek=e.no_proyek and a.kode_lokasi=e.kode_lokasi
            // left join (select b.no_proyek,a.kode_lokasi,sum(a.nilai_bayar) as nilai
            // from java_bayar_detail a
            // inner join java_tagihan b on a.no_tagihan=b.no_tagihan and a.kode_lokasi=b.kode_lokasi
            // where a.kode_lokasi='04'
            // group by b.no_proyek,a.kode_lokasi
            // )f on a.no_proyek=f.no_proyek and a.kode_lokasi=f.kode_lokasi
            // where a.flag_aktif = '1' and format(a.tgl_mulai, 'MM') = '".$request->query('bulan')."' 
            // and year(a.tgl_mulai) = '".$request->query('tahun')."'";

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

    public function getProjectDashboard(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            // and format(tgl_mulai, 'MM') = '".$request->query('bulan')."' and year(tgl_mulai) = '".$request->query('tahun')."'
            $sql = "select count(no_proyek) as jumlah_proyek, 
            (select count(no_proyek) from java_proyek where flag_aktif = '1') as proyek_selesai, 
            (select count(no_proyek) from java_proyek where flag_aktif = '0') as proyek_berjalan 
            from java_proyek";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $success['sql'] = $sql;
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
}

?>