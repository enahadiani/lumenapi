<?php

namespace App\Http\Controllers\Apv;

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

    public function getDataApproval()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $queryJuskeb = "select count(*) as jum from apv_juskeb_m where kode_lokasi='$kode_lokasi'";
            $queryVer = "select count(distinct no_juskeb) as jum from apv_ver_m  where kode_lokasi='$kode_lokasi'";
            $queryAppJuskeb = "select count(*) as jum from apv_juskeb_m where kode_lokasi='$kode_lokasi' and progress in ('S')";
            $queryJusPeng = "select count(*) as jum from apv_juspo_m where kode_lokasi='$kode_lokasi'";
            $queryApv1 = "select count(distinct a.no_bukti) as jum from apv_juspo_m a inner join apv_flow b on a.kode_lokasi=b.kode_lokasi and a.no_bukti=b.no_bukti where a.kode_lokasi = '$kode_lokasi' and b.status = '0'";
            $queryApv2 = "select count(distinct a.no_bukti) as jum from apv_juspo_m a inner join apv_flow b on a.kode_lokasi=b.kode_lokasi and a.no_bukti=b.no_bukti where a.kode_lokasi = '$kode_lokasi' and b.status = '1'";
            $queryApv3 = "select count(distinct a.no_bukti) as jum from apv_juspo_m a inner join apv_flow b on a.kode_lokasi=b.kode_lokasi and a.no_bukti=b.no_bukti where a.kode_lokasi = '$kode_lokasi' and b.status = '2'";
            $queryApv4 = "select count(distinct a.no_bukti) as jum from apv_juspo_m a inner join apv_flow b on a.kode_lokasi=b.kode_lokasi and a.no_bukti=b.no_bukti where a.kode_lokasi = '$kode_lokasi' and b.status = '3'";

            $juskeb = DB::connection($this->db)->select($queryJuskeb);
            $juskeb = json_decode(json_encode($juskeb),true);
            $juskeb = $juskeb[0]["jum"];

            $ver =DB::connection($this->db)->select($queryVer);
            $ver = json_decode(json_encode($ver),true);
            $ver = $ver[0]["jum"];

            $appjuskeb =DB::connection($this->db)->select($queryAppJuskeb);
            $appjuskeb = json_decode(json_encode($appjuskeb),true);
            $appjuskeb = $appjuskeb[0]["jum"];

            
            $juspeng =DB::connection($this->db)->select($queryJusPeng);
            $juspeng = json_decode(json_encode($juspeng),true);
            $juspeng = $juspeng[0]["jum"];

            $apv1 =DB::connection($this->db)->select($queryApv1);
            $apv1 = json_decode(json_encode($apv1),true);
            $apv1 = $apv1[0]["jum"];

            $apv2 =DB::connection($this->db)->select($queryApv2);
            $apv2 = json_decode(json_encode($apv2),true);
            $apv2 = $apv2[0]["jum"];

            $apv3 =DB::connection($this->db)->select($queryApv3);
            $apv3 = json_decode(json_encode($apv3),true);
            $apv3 = $apv3[0]["jum"];

            $apv4 =DB::connection($this->db)->select($queryApv4);
            $apv4 = json_decode(json_encode($apv4),true);
            $apv4 = $apv4[0]["jum"];

            $success = array(
                "status" => true,
                "message" => "Success!",
                "juskeb" => $juskeb,
                "ver" => $ver,
                "appjuskeb" => $appjuskeb,
                "juspeng" => $juspeng,
                "apv1" => $apv1,
                "apv2" => $apv2,
                "apv3" => $apv3,
                "apv4" => $apv4
            );
            return response()->json(['success'=>$success], $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
