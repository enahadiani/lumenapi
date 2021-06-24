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
    public $db = 'dbtoko';

    public function getDataBox(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            if($status_admin == "A"){
                $filter = "";
            } else {
                $filter = " and nik='$nik' ";
            }
            $sql0 = "select count(*) as jum from hr_karyawan where kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql0);
            $success['jum_profile'] = (count($res) > 0 ? $res[0]->jum : 0);

            $sql1 = "select count(*) as jum from hr_absen where kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql1);
            $success['jum_absen'] = (count($res) > 0 ? $res[0]->jum : 0);

            $sql2 = "select count(*) as jum from hr_pelatihan where kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql2);
            $success['jum_pelatihan'] = (count($res) > 0 ? $res[0]->jum : 0);

            $sql3 = "select count(*) as jum from hr_penghargaan where kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql3);
            $success['jum_penghargaan'] = (count($res) > 0 ? $res[0]->jum : 0);

            $sql4 = "select count(*) as jum from hr_sanksi where kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql4);
            $success['jum_sanksi'] = (count($res) > 0 ? $res[0]->jum : 0);

            $success['status'] = true;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getChart(Request $request){
        $this->validate($request,[
            'periode' => 'required'
        ]);

        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode;

			$sql="select a.kode_sts,a.nama,isnull(b.jumlah,0) as jum
            from hr_stsabsen a
            left join ( select status,kode_lokasi, count(*) as jumlah
            from hr_absen
            where kode_lokasi='$kode_lokasi' and nik='$nik' and substring(convert(varchar,tanggal,112),1,6) = '$periode'
            group by status,kode_lokasi ) b on a.kode_sts=b.status and a.kode_lokasi=b.kode_lokasi ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $ctg= array();
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dt = array();
                for($i=0;$i<count($res);$i++){
                    $dt[] = array('y'=>floatval($res[$i]['jum']),'name'=>$res[$i]['nama'],'key'=>$res[$i]['kode_sts']);  
                    array_push($ctg,$res[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'Jumlah',"colorByPoint"=>true,"data"=>$dt
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['ctg'] = $ctg;
                $success['series'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

        
}
