<?php

namespace App\Http\Controllers\Bangtel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
	public $successStatus = 200;
    public $guard = 'bangtel';
    public $db = 'dbbangtelindo';
    public $dark_color = array('#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7');

    public function getPeriode(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct a.periode,dbo.fnNamaBulan(a.periode) as nama
            from periode a
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,5,2) not in ('13','14','15','16','17')
            order by a.periode desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['periode_max'] = $res[0]['periode'];
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['periode_max'] = date('Ym');
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPP(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select  a.kode_pp,a.nama
            from pp a
            where a.kode_lokasi='$kode_lokasi'
            order by a.kode_pp desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['kode_pp_max'] = $res[0]['kode_pp'];
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['periode_max'] = date('Ym');
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBoxProject(Request $request){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp) && $request->kode_pp != ""){
                $filter .= " and a.kode_pp ='$request->kode_pp' ";    
            }
            
			$sql="select COUNT(a.kode_proyek) as total,COUNT(CASE WHEN substring(i.status,1,1) = '6' THEN 1 ELSE NULL END) as selesai,COUNT(CASE WHEN substring(i.status,1,1) <> '6' THEN 1 ELSE NULL END) as berjalan
            from spm_proyek a
            left join (	select kode_proyek,
                        progress,
                        status,
                        kode_lokasi
                        from (select kode_proyek,
                            progress,
                            status,
                            kode_lokasi,
                            row_number() over(partition by kode_proyek order by no_bukti desc) as rn
                        from spm_proyek_prog) as T
                        where rn = 1  
                        ) i on a.kode_proyek=i.kode_proyek and a.kode_lokasi=i.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' $filter ";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json($success, $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['data'] = [];
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
        
}
