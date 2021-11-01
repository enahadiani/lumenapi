<?php

namespace App\Http\Controllers\Silo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'silo';
    public $db = 'dbsilo';

    public function getDataBox()
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik_user = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }

            $aju = DB::connection($this->db)->select("select count(*) as jum from apv_juskeb_m where kode_lokasi='$kode_lokasi'");
            $juskeb = json_decode(json_encode($aju), true);
            $juskeb = $juskeb[0]["jum"];

            $ver = DB::connection($this->db)->select("select count(distinct no_juskeb) as jum from apv_ver_m  where kode_lokasi='$kode_lokasi'");
            $ver = json_decode(json_encode($ver), true);
            $ver = $ver[0]["jum"];

            $appkeb = DB::connection($this->db)->select("select count(*) as jum from apv_juskeb_m where kode_lokasi='$kode_lokasi' and progress in ('S')");
            $appjuskeb = json_decode(json_encode($appkeb), true);
            $appjuskeb = $appjuskeb[0]["jum"];


            $ajup = DB::connection($this->db)->select("select count(*) as jum from apv_juspo_m where kode_lokasi='$kode_lokasi'");
            $juspeng = json_decode(json_encode($ajup), true);
            $juspeng = $juspeng[0]["jum"];

            $appp = DB::connection($this->db)->select("select count(*) as jum from apv_juspo_m where kode_lokasi='$kode_lokasi' and progress in ('S')");
            $appjuspeng = json_decode(json_encode($appp), true);
            $appjuspeng = $appjuspeng[0]["jum"];

            $success = array(
                "status" => true,
                "message" => "Success!",
                "juskeb" => $juskeb,
                "ver" => $ver,
                "appjuskeb" => $appjuskeb,
                "juspeng" => $juspeng,
                "appjuspeng" => $appjuspeng
            );
            return response()->json(['success' => $success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPosisi(Request $request)
    {
        try {

            if ($data =  Auth::guard($this->guard)->user()) {
                $nik_user = $data->nik;
                $kode_lokasi = $data->kode_lokasi;
            }
            if (isset($request->jenis)) {

                $jenis = $request->jenis;
                if ($jenis == "") {
                    $filter = "";
                } else {

                    switch ($jenis) {
                        case 'JK':
                            $sql = "select no_bukti from apv_juskeb_m where kode_lokasi='$kode_lokasi' ";
                            break;
                        case 'VR':

                            $sql = "select distinct no_juskeb as no_bukti from apv_ver_m where kode_lokasi='$kode_lokasi' ";
                            break;
                        case 'AJK':
                            $sql = "select no_bukti from apv_juskeb_m where kode_lokasi='$kode_lokasi' and progress in ('S') ";
                            break;
                        case 'JP':
                            $sql = "select no_juskeb as no_bukti from apv_juspo_m where kode_lokasi='$kode_lokasi' ";
                            break;
                        case 'AJP':
                            $sql = "select no_juskeb as no_bukti from apv_juspo_m where kode_lokasi='$kode_lokasi'  and progress in ('S') ";
                            break;
                    }

                    $res = DB::connection($this->db)->select($sql);
                    $res = json_decode(json_encode($res), true);
                    $array_no = "";
                    for ($i = 0; $i < count($res); $i++) {
                        $no_bukti = $res[$i]['no_bukti'];
                        if ($i == 0) {
                            $array_no .= "'$no_bukti'";
                        } else {

                            $array_no .= "," . "'$no_bukti'";
                        }
                    }
                    $filter = " and a.no_bukti in ($array_no) ";
                }
            } else {
                $filter = "";
            }

            $aju = DB::connection($this->db)->select(" select a.no_bukti,a.no_dokumen,a.kode_pp,convert(varchar,a.waktu,103) as waktu,a.kegiatan,case a.progress when 'S' then 'FINISH' when 'F' then 'Return Verifikasi' when 'R' then 'Return Approval' else isnull(b.nama_jab,'-') end as posisi,a.nilai,a.progress,c.progress as progress2,case c.progress when 'S' then 'FINISH' when 'F' then 'Return Verifikasi' when 'R' then 'Return Approval' else isnull(d.nama_jab,'-') end as posisi2
            from apv_juskeb_m a
            left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1'
                    )b on a.no_bukti=b.no_bukti
			left join apv_juspo_m c on a.no_bukti=c.no_juskeb and a.kode_lokasi=c.kode_lokasi
			left join (select a.no_bukti,b.nama as nama_jab
                    from apv_flow a
                    inner join apv_jab b on a.kode_jab=b.kode_jab and a.kode_lokasi=b.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi' and a.status='1'
                    )d on c.no_bukti=d.no_bukti
            where a.kode_lokasi='" . $kode_lokasi . "'  and a.nik_buat='" . $nik_user . "' $filter ");
            $aju = json_decode(json_encode($aju), true);

            if (count($aju) > 0) { //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                return response()->json(['success' => $success], $this->successStatus);
            } else {
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success' => $success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error " . $e;
            return response()->json($success, $this->successStatus);
        }
    }

    function cek(Request $request)
    {
        $result = $this->getPosisi($request);
        $tmp = json_decode(json_encode($result), true);
        $data = $tmp["original"]["success"];
        dd($data);
    }
}
