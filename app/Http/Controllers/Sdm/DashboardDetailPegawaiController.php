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

    public function getDataPegawaiDetail(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $where = "where a.kode_lokasi = '".$kode_lokasi."' AND a.nik = '".$r->query('nik')."'";

            $select = "SELECT a.nik, ISNULL(a.no_ktp, '-') AS no_ktp, ISNULL(a.nama, '-') AS nama, 
            ISNULL(a.no_telp, '') AS no_telp, ISNULL(a.no_hp, '-') AS no_hp, ISNULL(a.tempat, '-') AS tempat, 
            CONVERT(varchar(10), a.tgl_lahir, 101) as tgl_lahir, ISNULL(a.alamat, '-') AS alamat, 
            ISNULL(a.provinsi, '-') AS provinsi, ISNULL(a.kota, '-') AS kota, ISNULL(a.kecamatan, '-') AS kecamatan, 
            ISNULL(a.kelurahan, '-') AS kelurahan,  ISNULL(a.kode_pos, '-') AS kode_pos, ISNULL(a.t_badan, 0) AS t_badan, 
            ISNULL(a.b_badan, 0) AS b_badan, ISNULL(a.gol_darah, '-') AS gol_darah, ISNULL(a.no_kk, '-') AS no_kk, 
            CASE 
            WHEN a.status_nikah = '0' THEN 'Belum Menikah'
            WHEN a.status_nikah = '1' THEN 'Sudah Menikah'
            ELSE 'Cerai'
            END AS status_nikah,
            CASE 
            WHEN a.jk = 'P' THEN 'Perempuan'
            ELSE 'Laki-laki'
            END AS jk,
            CONVERT(varchar(10), a.tgl_nikah, 101) as tgl_nikah, CONVERT(varchar(10), a.tgl_masuk, 101) AS tgl_masuk, 
            ISNULL(a.npwp, '-') AS npwp, ISNULL(a.no_bpjs, '-') AS no_bpjs, ISNULL(a.no_bpjs_kerja, '-') AS no_bpjs_kerja, 
            ISNULL(a.bank, '-') AS bank, ISNULL(a.cabang, '-') AS cabang, ISNULL(a.no_rek, '-') AS no_rek, 
            ISNULL(a.nama_rek, '-') AS nama_rek, ISNULL(a.client, '-') AS client, ISNULL(a.fungsi, '-') AS fungsi, 
            ISNULL(a.skill, '-') AS skill, ISNULL(a.no_kontrak, '-') AS no_kontrak, 
            CONVERT(varchar(10), a.tgl_kontrak, 101) as tgl_kontrak, CONVERT(varchar(10), a.tgl_kontrak_akhir, 101) AS tgl_kontrak_akhir, 
            ISNULL(a.area, '-') AS area, ISNULL(a.kota_area, '-') AS kota_area, ISNULL(a.fm, '-') AS fm, 
            ISNULL(a.bm, '-') AS bm, ISNULL(a.atasan_langsung, '-') AS atasan_langsung, 
            ISNULL(a.atasan_t_langsung, '-') AS atasan_t_langsung, ISNULL(c.nama, '-') AS nama_sdm, 
            ISNULL(d.nama, '-') AS nama_gol, ISNULL(e.nama, '-') AS nama_loker, ISNULL(f.nama, '-') AS nama_unit, 
            ISNULL(g.nama, '-') AS nama_profesi, ISNULL(h.nama, '-') AS nama_agama, ISNULL(a.email, '-') AS email
            FROM hr_karyawan a
            LEFT JOIN pp b ON a.kode_pp=b.kode_pp AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_sdm c ON a.kode_sdm=c.kode_sdm AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN hr_gol d ON a.kode_gol=d.kode_gol AND a.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_loker e ON a.kode_loker=e.kode_loker AND a.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_unit f ON a.kode_unit=f.kode_unit AND a.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_profesi g ON a.kode_profesi=g.kode_profesi AND a.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_agama h ON a.kode_agama=h.kode_agama AND a.kode_lokasi=h.kode_lokasi
            $where";

            $res = DB::connection($this->db)->select($select);
            $res = json_decode(json_encode($res),true);

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