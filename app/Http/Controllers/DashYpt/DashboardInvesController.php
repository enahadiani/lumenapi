<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardInvesController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    private function filterReq($request,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($request->input($col_array[$i])[0])){
                if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                    $where .= " AND (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                }elseif($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                }elseif($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                    $tmp = explode(",",$request->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }ELSE{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " AND ".$db_col_name[$i]." in ($this_in) ";
                }elseif($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                }elseif($request->input($col_array[$i])[0] == "<>" AND ISSET($request->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <> '".$request->input($col_array[$i])[1]."' ";
                }
            }
        }
        return $where;
    }

    public function getDataBox(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $bulan=substr($periode,4,2);
            $periode_awal=$tahun."01";
            $bulanSeb = intval($bulan)-1;
            if(strlen($bulanSeb) == 1){
                $bulanSeb = "0".$bulanSeb;
            }else{
                $bulanSeb = $bulanSeb;
            }
            $periode_rev=$tahun.$bulanSeb;

            $sql = "select 0 as persen_ytd, 0 as rka, 0 as real, 0 sa persen_tahun, 0 as rka_tahun, 0 as real_tahun, 0 as persen_ach, 0 as ach_now, 0 as ach_lalu 
            ";
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSerapAgg(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode=$r->periode[1];
            $where = "where x.kode_lokasi='12' ";

            $sql = "select 'A' as kode_aset,  'Aset A' as nama_aset, 0 as rka, 0 as real, 0 as ach ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAggPerLembagaChart(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "SELECT a.kode_lokasi, a.nama, a.skode, 1000000000 as nilai
            FROM dash_ypt_lokasi a
            WHERE a.kode_lokasi IN ('03','11','12','13','14','15') $filter_lokasi";

            $select = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($select),true);

            $chart = [];
            $idx = 0;
            foreach($res as $item) { 
                $name = $item['skode'];
                $nilai = floatval($item['n4']);
                if($idx == 0) {
                    if($nilai < 0){
                        $value = [
                            'name' => $name,
                            'y' => abs($nilai),
                            'sliced' =>  true,
                            'selected' => true,
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',                            
                            'color' => 'url(#custom-pattern)'
                        ];
                    }else{
                        $value = [
                            'name' => $name,
                            'y' => $nilai,
                            'sliced' =>  true,
                            'selected' => true,
                            'negative' => false
                        ];
                    }
                } else {
                    if($nilai < 0){
                        $value = [
                            'name' => $name,
                            'y' => abs($nilai),
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',                            
                            'color' => 'url(#custom-pattern)'
                        ];
                    }else{
                        $value = [
                            'name' => $name,
                            'y' => $nilai,
                            'negative' => false
                        ];
                    }
                }
                array_push($chart, $value);
                $idx++;
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $chart;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getNilaiAsetChart(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $ctg = array();
            $tahun = intval($tahun)-4;
            $thn = "";
            for($x=0;$x < 5;$x++){
                array_push($ctg,$tahun);
                if($x == 0){
                    $thn .= "'".$tahun.$bulan."'";
                }else{
                    
                    $thn .= ","."'".$tahun.$bulan."'";
                }
                $tahun++;
            }
           
            $sql="SELECT '20' as kode_lokasi, 'RKA' as nama,
                0 AS n1,
                0 AS n2,
                0 AS n3,
                0 AS n4,
                0 AS n5,
                0 AS n6,
                0 AS n7,
                0 AS n8,
                0 AS n9,
                0 AS n10,
                0 AS n11,
                0 AS n12
                union all
                SELECT '20' as kode_lokasi, 'Real' as nama,
                0 AS n1,
                0 AS n2,
                0 AS n3,
                0 AS n4,
                0 AS n5,
                0 AS n6,
                0 AS n7,
                0 AS n8,
                0 AS n9,
                0 AS n10,
                0 AS n11,
                0 AS n12
                ";
          
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $series = array();
            $i=0;
            foreach($res as $dt) {
                if(!isset($series[$i])){
                    $series[$i] = array('name' => $dt['nama'], 'data' => array());
                }
                $data = array(
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']), 
                floatval($dt['n5']), 
                floatval($dt['n6']), 
                floatval($dt['n7']), 
                floatval($dt['n8']), 
                floatval($dt['n9']), 
                floatval($dt['n10']), 
                floatval($dt['n11']), 
                floatval($dt['n12']));
                $series[$i]['data'] = $data;
                $i++;
            }
            $success['nama'] = $nama;
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'kategori' => $ctg,
                'series' => $series
            );

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
}
?>