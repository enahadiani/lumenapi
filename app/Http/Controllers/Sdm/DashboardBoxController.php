<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardBoxController extends Controller
{
    public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';

    public function getJumlahJenisKelamin(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '" . $kode_lokasi . "'
            AND jk = 'L'";

            $rs1 = DB::connection($this->db)->select($sql);
            $rs1 = json_decode(json_encode($rs1), true);

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '" . $kode_lokasi . "'
            AND jk = 'P'";

            $rs2 = DB::connection($this->db)->select($sql);
            $rs2 = json_decode(json_encode($rs2), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'pria' => intval($rs1[0]['jumlah']),
                'perempuan' => intval($rs2[0]['jumlah']),
            ];

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getJumlahLoker(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "select count(nik) as jumlah 
			from hr_sdm_pribadi where kota is not null
            and kode_lokasi = '".$kode_lokasi."'";

            $rs1 = DB::connection($this->db)->select($sql);
            $rs1 = json_decode(json_encode($rs1), true);

            $total = 0;
            foreach($rs1 as $dt) {
                $total = $total + intval($dt['jumlah']);
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $total;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getClient(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT b.kelompok as nama, count(*) AS jumlah,
           CASE
                WHEN b.kelompok = 'YPT' THEN '#255F85'
                WHEN b.kelompok = 'GSD' THEN '#941B0C'
                WHEN b.kelompok = 'TELKOM' THEN '#E26D5C'
                WHEN b.kelompok = 'EKSTERNAL' THEN '#FAA613'
                ELSE '#F1F1'
            END as color,
            CASE
                WHEN b.kelompok = 'YPT' THEN 'bg-dark-blue'
                WHEN b.kelompok = 'GSD' THEN 'bg-dark-red'
                WHEN b.kelompok = 'TELKOM' THEN 'bg-pink'
                WHEN b.kelompok = 'EKSTERNAL' THEN 'bg-orange'
                ELSE '#F1F1'
            END as bg
            FROM hr_sdm_client a
            INNER JOIN hr_client b ON a.nama_client=b.kode_client
            WHERE a.kode_lokasi = '".$kode_lokasi."'
            GROUP BY b.kelompok";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            $total = 0;
            $series = [];
            $desk = [];
            $chart = [];

            if (count($rs) > 0) {
                foreach ($rs as $dt) {
                    $total = $total + $dt['jumlah'];
                }

                foreach ($rs as $dt) {
                    $persen = ($dt['jumlah'] / $total) * 100;
                    $_persen = floatval(number_format((float)$persen, 1, '.', ''));
                    $data = [
                        'name' => $dt['nama'],
                        'y' => $dt['jumlah'],
                        'color' => $dt['color'],
                        'decimal' => $_persen,
                        'bg' => $dt['bg']
                    ];

                    $_chart = [
                        'name' => $dt['nama'],
                        'y' => intval($dt['jumlah']),
                    ];



                    array_unshift($chart, $_chart);
                    array_unshift($series, $data);
                }
            }


            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $series;
            $success['chart'] = $chart;


            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTotalClient(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT COUNT(kode_client)
            FROM hr_client";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $rs;

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBPJSKerja(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '" . $kode_lokasi . "'
            AND (no_bpjs_kerja IS NOT NULL AND no_bpjs_kerja <> '-' AND no_bpjs_kerja <> '')";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBPJSSehat(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_karyawan WHERE kode_lokasi = '" . $kode_lokasi . "'
            AND (no_bpjs IS NOT NULL AND no_bpjs <> '-' AND no_bpjs <> '')";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPegawai(Request $r)
    {
        try {
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql = "SELECT count(nik) AS jumlah FROM hr_sdm_pribadi WHERE kode_lokasi = '" . $kode_lokasi . "'";

            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = intval($rs[0]['jumlah']);

            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMarketClient(Request $r)
    {
        try {
            $input = $r->input('tahun');
            if ($data =  Auth::guard($this->guard)->user()) {
                $nik = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $sql  = "SELECT kelompok as name FROM hr_client GROUP BY kelompok";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs), true);

            for ($y = 0; $y < count($rs); $y++) {
                for ($i = 0; $i < count($input); $i++) {
                    $sql1  = "SELECT YEAR(a.tgl_kontrak_awal) as tahun,b.kelompok as nama,  count(*) AS jumlah
                    FROM hr_sdm_client a
                    LEFT JOIN hr_client b ON a.nama_client=b.kode_client
                    WHERE a.kode_lokasi = '" . $kode_lokasi . "' and b.kelompok = '" . $rs[$y]['name'] . "' and year(a.tgl_kontrak_awal) = '" . $input[$i] . "'
                    GROUP BY b.kelompok,YEAR(a.tgl_kontrak_awal)";
                    $rs1 = DB::connection($this->db)->select($sql1);
                    $rs1 = json_decode(json_encode($rs1), true);
                }

                switch ($rs[$y]['name']) {
                    case 'YPT':
                        $color = '#255F85';
                        break;
                    case 'GSD':
                        $color = '#FAA613';
                        break;
                    case 'TELKOM':
                        $color = '#E26D5C';
                        break;
                    case 'EKSTERNAL':
                        $color = '#941B0C';
                        break;
                }

                $result[] = [
                    'name' => $rs[$y]['name'],
                    'color' => $color,
                    'data'  => $rs1
                ];
            }
            // for ($i = 0; $i < count($input); $i++) {
            //     $sql  = "SELECT YEAR(a.tgl_kontrak_awal) as tahun,b.kelompok as nama,  count(*) AS jumlah
            //        FROM hr_sdm_client a
            //        LEFT JOIN hr_client b ON a.nama_client=b.kode_client
            //        WHERE a.kode_lokasi = '" . $kode_lokasi . "' and year(a.tgl_kontrak_awal) = '" . $input[$i] . "'
            //        GROUP BY b.kelompok,YEAR(a.tgl_kontrak_awal)";
            //     $rs = DB::connection($this->db)->select($sql);
            //     $rs = json_decode(json_encode($rs), true);

            //     if (count($rs) > 0) {
            //         $detail = $rs;
            //         // for ($y = 0; $y < $detail; $y++) {
            //         //     $item_detail = [
            //         //         'tahun' => $detail[$i]['tahun']
            //         //     ];
            //         // }
            //     } else {
            //         $detail = [
            //             'tahun' => $input[$i]
            //         ];
            //     }

            //     $result[] = [
            //         'tahun' => $input[$i],
            //         'detail' => $detail
            //     ];
            // }



            //     $sql = "SELECT b.kelompok as nama, count(*) AS jumlah,
            //    CASE
            //         WHEN b.kelompok = 'YPT' THEN '#255F85'
            //         WHEN b.kelompok = 'GSD' THEN '#941B0C'
            //         WHEN b.kelompok = 'TELKOM' THEN '#E26D5C'
            //         WHEN b.kelompok = 'EKSTERNAL' THEN '#FAA613'
            //         ELSE '#F1F1'
            //     END as color,
            //     CASE
            //         WHEN b.kelompok = 'YPT' THEN 'bg-dark-blue'
            //         WHEN b.kelompok = 'GSD' THEN 'bg-dark-red'
            //         WHEN b.kelompok = 'TELKOM' THEN 'bg-pink'
            //         WHEN b.kelompok = 'EKSTERNAL' THEN 'bg-orange'
            //         ELSE '#F1F1'
            //     END as bg
            //     FROM hr_sdm_client a
            //     JOIN hr_client b ON a.nama_client=b.kode_client
            //     WHERE a.kode_lokasi = '" . $kode_lokasi . "'
            //     GROUP BY b.kelompok";

            //     $rs = DB::connection($this->db)->select($sql);
            //     $rs = json_decode(json_encode($rs), true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $result;


            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            $success['data'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }
}
