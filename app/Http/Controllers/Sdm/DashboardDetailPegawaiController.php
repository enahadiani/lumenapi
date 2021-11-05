<?php
namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardDetailPegawaiController extends Controller { 
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getDataPegawai(Request $r) { 
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $where = "WHERE a.kode_lokasi = '".$kode_lokasi."'";

            if($r->query('pendidikan') === null) {
                $filter_array = array('jk','kode_loker','kode_jab');
                $col_array = array('a.jk', 'a.kode_loker', 'a.jabatan');

                for($i=0;$i<count($col_array);$i++) {
                    if($r->query($filter_array[$i]) !== null) {
                        $where .= " AND ".$col_array[$i]." = '".$r->query($filter_array[$i])."'";
                    }
                }

                $select = "SELECT a.nik, a.nama AS nama_pegawai, '-' AS nama_jabatan, c.nama AS nama_loker, ISNULL(a.client, '-') AS client,
                ISNULL(a.no_bpjs_kerja, '-') AS no_bpjs_kerja
                FROM hr_karyawan a
                LEFT JOIN hr_loker c ON a.kode_loker=c.kode_loker AND a.kode_lokasi=c.kode_lokasi
                $where";

                $res = DB::connection($this->db)->select($select);
                $res = json_decode(json_encode($res),true);
            } else {
                $select = "SELECT a.nik, a.nama AS nama_pegawai, b.nama AS nama_jabatan, c.nama AS nama_loker, a.client,
                ISNULL(a.no_bpjs_kerja, '-') AS no_bpjs_kerja
                FROM hr_karyawan a
                INNER JOIN hr_jab b ON a.jabatan=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
                INNER JOIN hr_loker c ON a.kode_loker=c.kode_loker AND a.kode_lokasi=c.kode_lokasi
                $where AND a.kode_strata = '".$r->query('pendidikan')."'";

                $res = DB::connection($this->db)->select($select);
                $res = json_decode(json_encode($res),true);
            }

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
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