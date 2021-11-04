<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
	public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getListBPJSKetenagaanNonTerdaftar(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($request->query('bpjs') != null && $request->query('bpjs') != '') {
                $where = " AND no_bpjs_kerja LIKE '%".$request->query('bpjs')."%'";
            }

            $sql = "SELECT nik, nama, ISNULL(no_bpjs_kerja, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '-' OR no_bpjs_kerja = '') 
            $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getListBPJSKetenagaanTerdaftar(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($request->query('bpjs') != null && $request->query('bpjs') != '') {
                $where = " AND no_bpjs_kerja LIKE '%".$request->query('bpjs')."%'";
            } else {
                $where = " AND no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '' AND no_bpjs_kerja <> '-'";
            }

            $sql = "SELECT nik, nama, no_bpjs_kerja AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getListBPJSKesehatanNonTerdaftar(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($request->query('bpjs') != null && $request->query('bpjs') != '') {
                $where = " AND no_bpjs LIKE '%".$request->query('bpjs')."%'";
            }

            $sql = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs IS NULL OR no_bpjs = '-' OR no_bpjs = '') $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getListBPJSKesehatanTerdaftar(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $where = "";
            if($request->query('bpjs') != null && $request->query('bpjs') != '') {
                $where = " AND no_bpjs LIKE '%".$request->query('bpjs')."%'";
            } else {
                $where = " AND no_bpjs IS NOT NULL AND no_bpjs <> '' AND no_bpjs <> '-'";
            }

            $sql = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' $where";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataKaryawanDetail(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $where = "where a.kode_lokasi = '".$kode_lokasi."' AND a.nik = '".$request->query('nik')."'";

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

            $selectData = DB::connection($this->db)->select($select);
            $resData = json_decode(json_encode($selectData),true);

            if(count($resData) > 0){ 
                $success['data'] = $resData;
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

    public function getDataKomposisiClient(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $data_client = "SELECT client, fungsi, ISNULL(alamat, '-') AS alamat, count(*) AS jumlah
			FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client, fungsi, alamat";

            $selectData = DB::connection($this->db)->select($data_client);
            $resData = json_decode(json_encode($selectData),true);
            
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $resData;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataUmur() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "exec [dbo].[sp_hr_dash] '202108','$kode_lokasi';";
            DB::connection($this->db)->update($sql1);

            $select = "SELECT a.kode_klp, b.nama_klp, a.nilai FROM hr_dashklp_periode a
            INNER JOIN hr_dashklp b on a.kode_klp=b.kode_klp and b.kode_lokasi = '".$kode_lokasi."' 
            WHERE a.jenis_klp = 'UMUR' AND b.kode_lokasi = '".$kode_lokasi."' AND a.periode = '202108'";
            $select = DB::connection($this->db)->select($select);
            $res = json_decode(json_encode($select),true);

            if(count($res) > 0){ 
                $ctg = array();
                $value = array();
                for($i=0;$i<count($res);$i++) {
                    array_push($ctg, $res[$i]['nama_klp']);
                    array_push($value, floatval($res[$i]['nilai']));
                }

                $success['categories'] = $ctg;
                $success['value'] = $value;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success['categories'] = [];
                $success['value'] = [];
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

    public function getDataGaji() {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql1 = "exec [dbo].[sp_hr_dash] '202108','$kode_lokasi';";
            DB::connection($this->db)->update($sql1);

            $select = "SELECT a.kode_klp, b.nama_klp, a.nilai FROM hr_dashklp_periode a
            INNER JOIN hr_dashklp b on a.kode_klp=b.kode_klp and b.kode_lokasi = '".$kode_lokasi."' 
            WHERE a.jenis_klp = 'GAJI' AND b.kode_lokasi = '".$kode_lokasi."' AND a.periode = '202108'";
            $select = DB::connection($this->db)->select($select);
            $res = json_decode(json_encode($select),true);



            if(count($res) > 0){ 
                $ctg = array();
                $value = array();
                for($i=0;$i<count($res);$i++) {
                    array_push($ctg, $res[$i]['nama_klp']);
                    array_push($value, floatval($res[$i]['nilai']));
                }

                $success['categories'] = $ctg;
                $success['value'] = $value;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success['categories'] = [];
                $success['value'] = [];
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

    public function getDataBPJSTenagaKerja(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $jumlah_karyawan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $jumlah_ketenagaan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '-' AND no_bpjs_kerja <> '')";

            $jumlah_non_ketenagaan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '-' OR no_bpjs_kerja = '')";

            $data_karyawan_tedaftar = "SELECT nik, nama, no_bpjs_kerja AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> ''
            AND no_bpjs_kerja <> '-'";

            $data_karyawan_non_terdaftar = "SELECT nik, nama, isnull(no_bpjs_kerja, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs_kerja IS NULL OR no_bpjs_kerja = '' OR no_bpjs_kerja = '-')";

            $selectJK = DB::connection($this->db)->select($jumlah_karyawan);
            $resJK = json_decode(json_encode($selectJK),true);

            $selectKerja = DB::connection($this->db)->select($jumlah_ketenagaan);
            $resKerja = json_decode(json_encode($selectKerja),true);

            $selectNonKerja = DB::connection($this->db)->select($jumlah_non_ketenagaan);
            $resNonKerja = json_decode(json_encode($selectNonKerja),true);

            $selectKaryawanTerdaftar = DB::connection($this->db)->select($data_karyawan_tedaftar);
            $resKaryawanTerdaftar = json_decode(json_encode($selectKaryawanTerdaftar),true);

            $selectKaryawanNonTerdaftar = DB::connection($this->db)->select($data_karyawan_non_terdaftar);
            $resKaryawanNonTerdaftar = json_decode(json_encode($selectKaryawanNonTerdaftar),true);

            $jumlah_karyawan = floatval($resJK[0]['jumlah']);
            $jumlah_bpjs = floatval($resKerja[0]['jumlah']);
            $jumlah_non_bpjs = floatval($resNonKerja[0]['jumlah']);

            $percentage_terdaftar = ($jumlah_bpjs / $jumlah_karyawan) * 100;
            $percentage_non_terdaftar = ($jumlah_non_bpjs / $jumlah_karyawan) * 100;

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data_terdaftar'] = $resKaryawanTerdaftar;
            $success['data_non_terdaftar'] = $resKaryawanNonTerdaftar;
            $success['jumlah_karyawan'] = $jumlah_karyawan;
            $success['jumlah_terdaftar'] = $jumlah_bpjs;
            $success['jumlah_non_terdaftar'] = $jumlah_non_bpjs;
            $success['percentage_terdaftar'] = round($percentage_terdaftar);
            $success['percentage_non_terdaftar'] = round($percentage_non_terdaftar);

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataBPJSKesehatan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $jumlah_karyawan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $jumlah_kesehatan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NOT NULL AND no_bpjs <> '-' AND no_bpjs <> '')";

            $jumlah_non_kesehatan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NULL OR no_bpjs = '-' OR no_bpjs = '')";

            $data_karyawan_terdaftar = "SELECT nik, nama, no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND no_bpjs IS NOT NULL AND no_bpjs <> '' AND no_bpjs <> '-'";

            $data_karyawan_non_terdaftar = "SELECT nik, nama, isnull(no_bpjs, '-') AS no_bpjs
			FROM hr_karyawan
			WHERE kode_lokasi = '".$kode_lokasi."' AND (no_bpjs IS NULL OR no_bpjs = '' OR no_bpjs = '-')";

            $selectJK = DB::connection($this->db)->select($jumlah_karyawan);
            $resJK = json_decode(json_encode($selectJK),true);

            $selectKes = DB::connection($this->db)->select($jumlah_kesehatan);
            $resKes = json_decode(json_encode($selectKes),true);

            $selectNonKes = DB::connection($this->db)->select($jumlah_non_kesehatan);
            $resNonKes = json_decode(json_encode($selectNonKes),true);

            $selectKaryawanTerdaftar = DB::connection($this->db)->select($data_karyawan_terdaftar);
            $resKaryawanTerdaftar = json_decode(json_encode($selectKaryawanTerdaftar),true);

            $selectKaryawanNonTerdaftar = DB::connection($this->db)->select($data_karyawan_non_terdaftar);
            $resKaryawanNonTerdaftar = json_decode(json_encode($selectKaryawanNonTerdaftar),true);

            $jumlah_karyawan = floatval($resJK[0]['jumlah']);
            $jumlah_bpjs = floatval($resKes[0]['jumlah']);
            $jumlah_non_bpjs = floatval($resNonKes[0]['jumlah']);

            $percentage_terdaftar = ($jumlah_bpjs / $jumlah_karyawan) * 100;
            $percentage_non_terdaftar = ($jumlah_non_bpjs / $jumlah_karyawan) * 100;

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data_terdaftar'] = $resKaryawanTerdaftar;
            $success['data_non_terdaftar'] = $resKaryawanNonTerdaftar;
            $success['jumlah_karyawan'] = $jumlah_karyawan;
            $success['jumlah_terdaftar'] = $jumlah_bpjs;
            $success['jumlah_non_terdaftar'] = $jumlah_non_bpjs;
            $success['percentage_terdaftar'] = round($percentage_terdaftar);
            $success['percentage_non_terdaftar'] = round($percentage_non_terdaftar);

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKomposisiClient(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $jumlah_client = "SELECT client,count(*) AS jumlah 
            FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client";

            $selectClient = DB::connection($this->db)->select($jumlah_client);
            $resClient = json_decode(json_encode($selectClient),true);

            $total = 0;
            for($i=0;$i<count($resClient);$i++) {
                $total += floatval($resClient[$i]['jumlah']);
            }

            $ctg = array();
            $komposisi = array();
            for($i=0;$i<count($resClient);$i++) {
                array_push($ctg, $resClient[$i]['client']);
                $percentage = (floatval($resClient[$i]['jumlah']) / $total) * 100;
                array_push($komposisi, round($percentage, 2));
            }
            
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['categories'] = $ctg;
            $success['komposisi'] = $komposisi;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDataKaryawan(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $where = "where a.kode_lokasi = '".$kode_lokasi."'";

            if($request->query('pendidikan') === null) {
                $filter_array = array('jk','kode_loker','kode_jab');
                $col_array = array('a.jk', 'a.kode_loker', 'a.jabatan');

                for($i=0;$i<count($col_array);$i++) {
                    if($request->query($filter_array[$i]) !== null) {
                        $where .= " AND ".$col_array[$i]." = '".$request->query($filter_array[$i])."'";
                    }
                }

                $select = "SELECT a.nik, a.nama AS nama_pegawai, b.nama AS nama_jabatan, c.nama AS nama_loker, a.client,
                ISNULL(a.no_bpjs_kerja, '-') AS no_bpjs_kerja
                FROM hr_karyawan a
                INNER JOIN hr_jab b ON a.jabatan=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
                INNER JOIN hr_loker c ON a.kode_loker=c.kode_loker AND a.kode_lokasi=c.kode_lokasi
                $where";

                $res = DB::connection($this->db)->select($select);
                $res = json_decode(json_encode($res),true);
            } else {
                $select = "SELECT a.nik, a.nama AS nama_pegawai, b.nama AS nama_jabatan, c.nama AS nama_loker, a.client,
                ISNULL(a.no_bpjs_kerja, '-') AS no_bpjs_kerja
                FROM hr_karyawan a
                INNER JOIN hr_jab b ON a.jabatan=b.kode_jab AND a.kode_lokasi=b.kode_lokasi
                INNER JOIN hr_loker c ON a.kode_loker=c.kode_loker AND a.kode_lokasi=c.kode_lokasi
                $where AND a.kode_strata = '".$request->query('pendidikan')."'";

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

    public function getDataDashboard(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $jumlah_karyawan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'";

            $jumlah_kesehatan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs IS NOT NULL AND no_bpjs <> '-' AND no_bpjs <> '')";

            $jumlah_ketenagakerjaan = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."' 
            AND (no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '-' AND no_bpjs_kerja <> '')";

            $jumlah_pria = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'L'";

            $jumlah_wanita = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '".$kode_lokasi."'
            AND jk = 'P'";

            $jumlah_client = "SELECT client,count(*) AS jumlah 
            FROM hr_karyawan 
            WHERE kode_lokasi = '".$kode_lokasi."'
            GROUP BY client";

            $tingkat_pendidikan = "SELECT a.kode_strata, a.nama AS nama_strata, isnull(b.jumlah, 0) AS jumlah
            FROM hr_strata a
            LEFT JOIN (SELECT kode_strata, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_strata, kode_lokasi
            ) b ON a.kode_strata=b.kode_strata AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."' ORDER BY a.nu";

            $lokasi_kerja = "SELECT TOP 5 a.kode_loker, a.nama AS nama_loker, isnull(b.jumlah, 0) AS jumlah
            FROM hr_loker a
            LEFT JOIN (SELECT kode_loker, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY kode_loker, kode_lokasi
            ) b ON a.kode_loker=b.kode_loker AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";

            // $jabatan = "SELECT a.kode_jab, a.nama AS nama_jabatan, isnull(b.jumlah, 0) AS jumlah
            // FROM hr_jab a
            // LEFT JOIN (SELECT jabatan, kode_lokasi, count(nik) AS jumlah
            // FROM hr_karyawan
            // GROUP BY kode_jab, kode_lokasi
            // ) b ON a.kode_jab=b.jabatan AND a.kode_lokasi=b.kode_lokasi
            // WHERE a.kode_lokasi = '".$kode_lokasi."'";
            $jabatan = "SELECT a.kode_jab, a.nama AS nama_jabatan, isnull(b.jumlah, 0) AS jumlah
            FROM hr_jab a
            LEFT JOIN (SELECT jabatan, kode_lokasi, count(nik) AS jumlah
            FROM hr_karyawan
            GROUP BY jabatan, kode_lokasi
            ) b ON a.kode_jab=b.jabatan AND a.kode_lokasi=b.kode_lokasi
            WHERE a.kode_lokasi = '".$kode_lokasi."'";

            $selectJK = DB::connection($this->db)->select($jumlah_karyawan);
            $resJK = json_decode(json_encode($selectJK),true);

            $selectKes = DB::connection($this->db)->select($jumlah_kesehatan);
            $resKes = json_decode(json_encode($selectKes),true);

            $selectKer = DB::connection($this->db)->select($jumlah_ketenagakerjaan);
            $resKer = json_decode(json_encode($selectKer),true);

            $selectPria = DB::connection($this->db)->select($jumlah_pria);
            $resPria = json_decode(json_encode($selectPria),true);

            $selectWanita = DB::connection($this->db)->select($jumlah_wanita);
            $resWanita = json_decode(json_encode($selectWanita),true);

            $selectClient = DB::connection($this->db)->select($jumlah_client);
            $resClient = json_decode(json_encode($selectClient),true);
            
            $selectPend = DB::connection($this->db)->select($tingkat_pendidikan);
            $resPend = json_decode(json_encode($selectPend),true);

            $selectLok = DB::connection($this->db)->select($lokasi_kerja);
            $resLok = json_decode(json_encode($selectLok),true);

            $selectJab = DB::connection($this->db)->select($jabatan);
            $resJab = json_decode(json_encode($selectJab),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['jumlah_karyawan'] = $resJK;
            $success['jumlah_kesehatan'] = $resKes;
            $success['jumlah_kerja'] = $resKer;
            $success['jumlah_pria'] = $resPria;
            $success['jumlah_wanita'] = $resWanita;
            $success['tingkat_pendidikan'] = $resPend;
            $success['lokasi_kerja'] = $resLok;
            $success['jumlah_client'] = count($resClient);
            $success['jabatan'] = $resJab;

            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }   
}
