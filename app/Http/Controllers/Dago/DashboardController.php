<?php

namespace App\Http\Controllers\Dago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public $successStatus = 200;
    public $db = "sqlsrvdago";
    public $guard = "dago";

    function dbResultArray($sql){
    
        $res = DB::connection($this->db)->select($sql);
        $res = json_decode(json_encode($res),true);
        return $res;
    }

    function execute($sql){
    
        $res = DB::connection($this->db)->select($sql);
        return $res;
    }

    public function getDataBox(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->execute("select count(a.no_peserta) as jumlah from dgw_peserta a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['jamaah'] = (count($res) ? $res[0]->jumlah : 0);

            $res = $this->execute("select count(a.no_reg) as jumlah from dgw_reg a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['reg'] = (count($res) ? $res[0]->jumlah : 0);

            $res = $this->execute("select count(a.no_kwitansi) as jumlah from dgw_pembayaran a where a.kode_lokasi='$kode_lokasi'
            ");
            $success['pbyr'] = (count($res) ? $res[0]->jumlah : 0);

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTopAgen(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->dbResultArray("select top 7 a.no_agen,b.nama_agen,count(*) as jum
            from dgw_reg a
            inner join dgw_agent b on a.no_agen=b.no_agen and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            group by a.no_agen,b.nama_agen
            order by count(*) desc            
            ");
            $success['daftar'] = $res;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRegHarian(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->execute("select * from (select top 12 a.tgl_input,count(*) as jumlah
            from dgw_reg a
            where a.kode_lokasi='$kode_lokasi'
            group by a.tgl_input ) a
            order by a.tgl_input asc
            ");
            $result = array();
            if(count($res) > 0){
                foreach ($res as $row){
                    $result['reg'][] = floatval($row->jumlah);
                    $ctg[] = $row->tgl_input;
                }
            }
            
            $series[] = array("name"=>"Total Registrasi","data"=>$result['reg']);
            $success['series']=$series;
            $success['ctg']=$ctg;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKuotaPaket(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $res = $this->dbResultArray(" select a.no_paket,a.no_jadwal,a.tgl_berangkat,b.nama, a.quota+a.quota_se+a.quota_e as quota,isnull(c.jum,0) as jum,  (a.quota+a.quota_se+a.quota_e) - isnull(c.jum,0) as sisa 
            from dgw_jadwal a
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi=b.kode_lokasi
            left join (
                    select a.no_paket,a.no_jadwal,a.kode_lokasi,count(*) as jum
                    from dgw_reg a
                    group by a.no_paket,a.no_jadwal,a.kode_lokasi
            ) c on a.no_paket=c.no_paket and a.no_jadwal=c.no_jadwal and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            order by a.tgl_berangkat desc,a.no_paket
            ");
            $success['daftar']=$res;

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    

    
}
