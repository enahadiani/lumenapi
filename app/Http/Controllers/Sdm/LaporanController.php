<?php

namespace App\Http\Controllers\Sdm;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{

    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getKaryawan(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('kode_sdm', 'kode_area', 'kode_fm', 'kode_bm', 'kode_loker', 'nik');
            $db_col_name = array('b.kode_sdm', 'b.kode_area', 'b.kode_fm', 'b.kode_bm', 'b.kode_loker', 'a.nik');
            $where = "where a.kode_lokasi='" . $kode_lokasi . "'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "SELECT a.nik,a.kode_lokasi,a.nama,a.no_telp,a.no_hp,a.alamat,convert(varchar,b.tgl_masuk,103) AS tgl_masuk,

            b.kode_sdm, d.nama as nama_sdm,
            b.kode_loker, e.nama as nama_loker,
            b.kode_fm, g.nama as nama_fm,
            b.kode_bm, h.nama as nama_bm,
            b.kode_area, f.nama as nama_area
            FROM hr_sdm_pribadi a
            LEFT JOIN hr_sdm_kepegawaian b ON a.nik=b.nik AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_sdm d ON b.kode_sdm=d.kode_sdm AND b.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_loker e ON b.kode_loker=e.kode_loker AND b.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_area f ON b.kode_area=f.kode_area AND b.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_fm g ON b.kode_fm=g.kode_fm AND b.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_bm h ON b.kode_bm=h.kode_bm AND b.kode_lokasi=h.kode_lokasi
            $where
            order by b.tgl_masuk";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res), true);

            if (count($res) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;
                $success['query'] = $sql;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_rab'] = [];
                $success['data_beban'] = [];
                $success['sql'] = $sql;
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getCVKaryawan(Request $request)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $col_array = array('kode_sdm', 'kode_area', 'kode_fm', 'kode_bm', 'kode_loker', 'nik');
            $db_col_name = array('c.kode_sdm', 'c.kode_area', 'c.kode_fm', 'c.kode_bm', 'c.kode_loker', 'a.nik');
            $where = "where a.kode_lokasi='" . $kode_lokasi . "'";
            $this_in = "";
            for ($i = 0; $i < count($col_array); $i++) {
                if (isset($request->input($col_array[$i])[0])) {
                    if ($request->input($col_array[$i])[0] == "range" and isset($request->input($col_array[$i])[1]) and isset($request->input($col_array[$i])[2])) {
                        $where .= " and (" . $db_col_name[$i] . " between '" . $request->input($col_array[$i])[1] . "' AND '" . $request->input($col_array[$i])[2] . "') ";
                    } else if ($request->input($col_array[$i])[0] == "=" and isset($request->input($col_array[$i])[1])) {
                        $where .= " and " . $db_col_name[$i] . " = '" . $request->input($col_array[$i])[1] . "' ";
                    } else if ($request->input($col_array[$i])[0] == "in" and isset($request->input($col_array[$i])[1])) {
                        $tmp = explode(",", $request->input($col_array[$i])[1]);
                        for ($x = 0; $x < count($tmp); $x++) {
                            if ($x == 0) {
                                $this_in .= "'" . $tmp[$x] . "'";
                            } else {

                                $this_in .= "," . "'" . $tmp[$x] . "'";
                            }
                        }
                        $where .= " and " . $db_col_name[$i] . " in ($this_in) ";
                    }
                }
            }

            $sql = "SELECT a.nik, a.kode_lokasi, a.nama,a.alamat,a.no_telp,a.no_hp,
            CASE WHEN a.status_nikah='0' THEN 'Tidak' ELSE 'Ya' END AS status_nikah,
            CASE WHEN a.jenis_kelamin='L' THEN 'Laki-Laki' ELSE 'Perempuan' END AS jenis_kelamin,
            CASE WHEN a.status_nikah='0' THEN '-' ELSE convert(varchar,a.tgl_nikah,103)  END AS tgl_nikah,
            a.golongan_darah,a.nomor_kk,d.nama as nama_area,e.nama as nama_fm,f.nama as nama_bm,g.nama as nama_sdm,
            h.nama as nama_profesi,a.tempat_lahir,convert(varchar,a.tgl_lahir,103) AS tgl_lahir,c.no_npwp,a.kode_pos,
            i.kode_bank,j.nama as nama_bank,i.cabang,i.nama_rek,i.no_rek,a.kota,a.kelurahan,a.kecamatan,
            convert(varchar,k.tgl_kontrak_awal,103) AS tgl_kontrak, k.no_kontrak,b.nama as nama_agama,l.nama as nama_loker
            FROM hr_sdm_pribadi a
            LEFT JOIN hr_agama b ON a.kode_agama=b.kode_agama AND a.kode_lokasi=b.kode_lokasi
            LEFT JOIN hr_sdm_kepegawaian c ON a.nik=c.nik AND a.kode_lokasi=c.kode_lokasi
            LEFT JOIN hr_area d ON c.kode_area=d.kode_area AND c.kode_lokasi=d.kode_lokasi
            LEFT JOIN hr_fm e ON c.kode_fm=e.kode_fm AND c.kode_lokasi=e.kode_lokasi
            LEFT JOIN hr_bm f ON c.kode_bm=f.kode_bm AND c.kode_lokasi=f.kode_lokasi
            LEFT JOIN hr_sdm g ON c.kode_sdm=g.kode_sdm AND c.kode_lokasi=g.kode_lokasi
            LEFT JOIN hr_profesi h ON c.kode_profesi=h.kode_profesi AND c.kode_lokasi=h.kode_lokasi
            LEFT JOIN hr_sdm_bank i ON a.nik=i.nik AND a.kode_lokasi=i.kode_lokasi
            LEFT JOIN hr_bank j ON i.kode_bank=j.kode_bank AND i.kode_lokasi=j.kode_lokasi
            LEFT JOIN hr_sdm_client k ON a.nik=k.nik AND a.kode_lokasi=k.kode_lokasi
            LEFT JOIN hr_loker l ON c.kode_loker=l.kode_loker AND l.kode_lokasi=c.kode_lokasi
            $where
            ORDER BY a.nik";

            $cv = DB::connection($this->sql)->select($sql);
            $cv = json_decode(json_encode($cv), true);

            if (count($cv) > 0) {
                $data_keluarga = array();
                $data_dinas = array();
                $data_pendidikan = array();
                $data_pelatihan = array();
                $data_penghargaan = array();
                $data_sanksi = array();

                for ($i = 0; $i < count($cv); $i++) {
                    $keluarga = "SELECT nik, kode_lokasi, nu, nama, tempat,
                    convert(varchar,tgl_lahir,103) as tgl_lahir,
                    CASE WHEN status_kes = 'Y' THEN 'Ditanggung' ELSE 'Tidak Ditanggung' END AS status_kes,
                    CASE WHEN jk = 'L' THEN 'Laki-laki' ELSE 'Perempuan' END AS jk,
                    CASE
                        WHEN jenis ='S' THEN 'Suami'
                        WHEN jenis ='I' THEN 'Istri'
                        ELSE 'Anak' END AS jenis
                    from hr_keluarga
                    where nik = '" . $cv[$i]['nik'] . "' AND kode_lokasi='$kode_lokasi'";

                    $resKeluarga = DB::connection($this->sql)->select($keluarga);
                    $resKeluarga = json_decode(json_encode($resKeluarga), true);

                    if (count($resKeluarga) > 0) {
                        array_push($data_keluarga, $resKeluarga);
                    } else {
                        array_push($data_keluarga, array());
                    }

                    $dinas = "SELECT no_sk,nama, convert(varchar,tgl_sk,103) AS tgl_sk
                    FROM hr_sk
			        WHERE nik = '" . $cv[$i]['nik'] . "' AND kode_lokasi='" . $kode_lokasi . "' ORDER BY tgl_sk DESC";

                    $resDinas = DB::connection($this->sql)->select($dinas);
                    $resDinas = json_decode(json_encode($resDinas), true);

                    if (count($resDinas) > 0) {
                        array_push($data_dinas, $resDinas);
                    } else {
                        array_push($data_dinas, array());
                    }

                    $pendidikan = "SELECT a.nama, a.tahun, a.kode_jurusan, a.kode_strata,b.nama AS nama_jur,
                    c.nama AS nama_strata
                    FROM hr_pendidikan a
                    LEFT JOIN hr_jur b ON a.kode_jurusan=b.kode_jur AND a.kode_lokasi=b.kode_lokasi
                    LEFT JOIN hr_strata c ON a.kode_strata=c.kode_strata AND a.kode_lokasi=c.kode_lokasi
                    where a.nik = '" . $cv[$i]['nik'] . "' AND a.kode_lokasi='" . $kode_lokasi . "' ORDER BY tahun DESC";

                    $resPendidikan = DB::connection($this->sql)->select($pendidikan);
                    $resPendidikan = json_decode(json_encode($resPendidikan), true);

                    if (count($resPendidikan) > 0) {
                        array_push($data_pendidikan, $resPendidikan);
                    } else {
                        array_push($data_pendidikan, array());
                    }

                    $pelatihan = "SELECT nama, panitia, convert(varchar,tgl_mulai,103) AS tgl_mulai,
                    convert(varchar,tgl_selesai,103) AS tgl_selesai
                    FROM hr_pelatihan
                    WHERE nik = '" . $cv[$i]['nik'] . "' AND kode_lokasi='" . $kode_lokasi . "' ORDER BY tgl_mulai DESC";

                    $resPelatihan = DB::connection($this->sql)->select($pelatihan);
                    $resPelatihan = json_decode(json_encode($resPelatihan), true);

                    if (count($resPelatihan) > 0) {
                        array_push($data_pelatihan, $resPelatihan);
                    } else {
                        array_push($data_pelatihan, array());
                    }

                    $penghargaan = "SELECT nama, convert(varchar,tanggal,103) AS tanggal
                    FROM hr_penghargaan
                    WHERE nik = '" . $cv[$i]['nik'] . "' AND kode_lokasi='" . $kode_lokasi . "' ORDER BY tanggal DESC";

                    $resPenghargaan = DB::connection($this->sql)->select($penghargaan);
                    $resPenghargaan = json_decode(json_encode($resPenghargaan), true);

                    if (count($resPenghargaan) > 0) {
                        array_push($data_penghargaan, $resPenghargaan);
                    } else {
                        array_push($data_penghargaan, array());
                    }

                    $sanksi = "SELECT nama, jenis, convert(varchar,tanggal,103) AS tanggal
                    FROM hr_sanksi
                    WHERE nik = '" . $cv[$i]['nik'] . "' AND kode_lokasi='" . $kode_lokasi . "' ORDER BY tanggal DESC";

                    $resSanksi = DB::connection($this->sql)->select($sanksi);
                    $resSanksi = json_decode(json_encode($resSanksi), true);

                    if (count($resSanksi) > 0) {
                        array_push($data_sanksi, $resSanksi);
                    } else {
                        array_push($data_sanksi, array());
                    }
                }
            }

            if (count($cv) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $cv;
                $success['data_keluarga'] = $data_keluarga;
                $success['data_dinas'] = $data_dinas;
                $success['data_pendidikan'] = $data_pendidikan;
                $success['data_pelatihan'] = $data_pelatihan;
                $success['data_penghargaan'] = $data_penghargaan;
                $success['data_sanksi'] = $data_sanksi;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;

                return response()->json($success, $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_keluarga'] = [];
                $success['data_dinas'] = [];
                $success['data_pendidikan'] = [];
                $success['data_pelatihan'] = [];
                $success['data_penghargaan'] = [];
                $success['data_sanksi'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }
}
