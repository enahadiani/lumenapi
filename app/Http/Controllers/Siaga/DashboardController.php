<?php

namespace App\Http\Controllers\Siaga;

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
    public $db = 'dbsiaga';
    public $guard = 'siaga';
   
    function execute($sql){
        $query = DB::connection($this->db)->select($sql);
        return $query;
    }

    public function getSummary(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            if($periode == ""){
                $periode = date('Ym');
            }

            $color = array("#D32f2f","#2196F3","#FFC107","#388E3C","#00796B","#7B1FA2","#673AB7","#FFA000","#689F38");
            if (trim($request->dept) == "All") {
                $dept = "";
            }else{
                $dept = $request->dept;
            }

            $tahun = substr($periode, 0, 4);
            $tahunLalu = floatval($tahun) - 1;
            $periodeLalu = $tahunLalu . substr($periode, 4, 2);

            $res = $this->execute("select klp, sum(nilai) as n1 from exs_real 
            where tahun = '$tahun' and periode <= '$periode' and dept like '%$dept%'
            group by klp");

            $success = array("summary" => array(),"categories" => array(), "trend" => array(),"series" => array(), "series2" => array(), "series3" => array() );

            foreach ($res as $row){
                $success[$row->klp] = $row->n1;
            }
            $sql = "select a.klp, case a.klp when 'REVENUE' then 1 
            when 'COGS'  then 2
            when 'GP' then 3
            when 'OPEX' then 4
            when 'OTHERS' then 5
            else 99 end as nu,
            a.n1, b.fullyear as fullyear_rkap, b.ytd as ytd_rkap, c.fullyear , c.ytd , a.n1 / b.fullyear * 100 as ach2, a.n1 / b.ytd * 100 as ach, (a.n1 / c.ytd - 1) * 100 as grow
            from (
                select klp, sum(nilai) as n1 from exs_real where tahun = '$tahun' and periode <= '$periode'
                and dept like '%$dept%'
                group by klp ) a 
            left outer join (
                select klp, sum(nilai) as fullyear, sum(case when periode <= '$periode' then nilai else 0 end) as ytd from exs_rkap where tahun = '$tahun' 
                and dept like '%$dept%'
                group by klp ) b on b.klp = a.klp
            left outer join (
                select klp, sum(nilai) as fullyear, sum(case when periode <= '$periodeLalu' then nilai else 0 end) as ytd  from exs_real where tahun = '$tahunLalu' 
                and dept like '%$dept%'
                group by klp ) c on c.klp = a.klp
                order by nu";
                        
            $res = $this->execute($sql);
            $grouping = array();
             
            $success["categories"] = array("Real $tahunLalu", "RKAP $tahun","Real $tahun");
            foreach ($res as $row){
                $success["summary"][] = (array)$row;
                // $tmp = ;
                if (!isset($grouping[$row->klp])){
                    $tmp = array("name" => $row->klp, "colorByPoint" => true,  "data" => array(floatval($row->ytd),floatval($row->fullyear_rkap),floatval($row->n1)  ) );
                }
                $grouping[$row->klp] = $tmp;	
                
            }
            $success["series"] = $grouping;
            
            $sql = "select klp, portofolio, sum(nilai) as n1 from exs_real where tahun = '$tahun' and periode <= '$periode'
            and dept like '%$dept%'
            group by klp, portofolio
			order by klp ";
            
            $res = $this->execute($sql);
            $grouping = array();
            foreach ($res as $row){
                if (!isset($grouping[$row->klp])){
                    $tmp = array("name" => $row->klp, "colorByPoint" => true,  "data" => array() );
                }
                $tmp["data"][] = array($row->portofolio, floatval($row->n1));
                $grouping[$row->klp] = $tmp;
            }
                        
			$success["grouping"] = $grouping;
            $success['status'] = true;
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPeriode(Request $request) {
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select distinct periode 
            from exs_real 
            order by periode");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getDept(Request $request) {
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select distinct dept 
            from exs_real 
            order by dept");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getDataOfModul(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            if($periode == ""){
                $periode = date('Ym');
            }
            $tahun = substr($periode, 0, 4);
            $color = array("#D32f2f","#2196F3","#FFC107","#388E3C","#00796B","#7B1FA2","#673AB7","#FFA000","#689F38");
            $field = $request->field;
            $modul = $request->modul;
            $dept = $request->dept;
            if($dept == "All"){
                $dept = "";
            }
            $sql = "select a.".$field.",a.n1, b.n2, c.n2  as n3 from (
                select $field, sum(nilai) as n1 from exs_real where tahun = '$tahun' and periode <= '$periode'
                and klp = '$modul' and dept like '%$dept%'
                group by $field ) a 
                left outer join (
                    select $field, sum(nilai) as n2  from exs_rkap where tahun = '$tahun' 
                    and klp = '$modul' and dept like '%$dept%'
                    group by $field ) b on b.".$field." = a.".$field."
                left outer join (
                        select $field, sum(nilai) as n2  from exs_rkap where tahun = '$tahun' and periode <= '$periode'
                        and klp = '$modul' and dept like '%$dept%'
                        group by $field ) c on c.".$field." = a.".$field." ";
            $res = $this->execute($sql);
			$success = array("summary" => array(),"categories" => array(), "trend" => array(),"series" => array(), "series2" => array(), "series3" => array() );
            
            $success["series"][] = array("name" => "Actual", "color" => $color[0], "data" => array() );
            $success["series"][] = array("name" => "RKAP", "color" => $color[1], "data" => array() );
            
            $success["series2"][] = array("name" => "Actual", "color" => $color[0], "data" => array() );
            $success["series2"][] = array("name" => "RKAP", "color" => $color[1], "data" => array() );
            
            $success["series4"] = array("name" => "Actual", "color" => $color[0], "data" => array() );
            $success["series5"] = array("name" => "RKAP", "color" => $color[1], "data" => array() );
            
            foreach ($res as $row){
                $tmp = (array)$row;
                $success["summary"][] = $tmp;
                $success["categories"][] = $tmp[$field];
                $success["series"][0]["data"][] = floatval($row->n1);
                $success["series"][1]["data"][] = floatval($row->n2);
                
                $success["series2"][0]["data"][] = floatval($row->n1);
                $success["series2"][1]["data"][] = floatval($row->n3);
                
                $success["series4"]["data"][] = array( $tmp[$field], floatval($row->n1) );
                $success["series5"]["data"][] = array( $tmp[$field], floatval($row->n3) );
            }
            $sql = "select $field
            , sum(nilai) as total
            , sum(case when bulan = 'Jan' then nilai else 0 end )as n1
            , sum(case when bulan = 'Feb' then nilai else 0 end) as n2
            , sum(case when bulan = 'Mar' then nilai else 0 end) as n3
            , sum(case when bulan = 'Apr' then nilai else 0 end) as n4
            , sum(case when bulan = 'May' then nilai else 0 end) as n5
            , sum(case when bulan = 'Jun' then nilai else 0 end) as n6
            , sum(case when bulan = 'Jul' then nilai else 0 end) as n7
            , sum(case when bulan = 'Aug' then nilai else 0 end) as n8
            , sum(case when bulan = 'Sep' then nilai else 0 end) as n9
            , sum(case when bulan = 'Okt' then nilai else 0 end) as n10
            , sum(case when bulan = 'Nop' then nilai else 0 end) as n11
            , sum(case when bulan = 'Des' then nilai else 0 end) as n12
            from exs_real 
            where tahun = '$tahun' and klp = '$modul' and dept like '%$dept%'
            group by $field";
			$res = $this->execute($sql);
            $idx = 0;
            foreach ($res as $row){
                if($idx >= count($color)){
                    $idx = 0;
                }
                $tmp = (array)$row;
                $success["trend"][] = $tmp;
                $success["series3"][] = array("name" => $tmp[$field], "color" => $color[$idx], "data" => array(
                    round(floatval($row->n1) )
                    , round(floatval($row->n2)), round(floatval($row->n3)), round(floatval($row->n4))
                    , round(floatval($row->n5)), round(floatval($row->n6)), round(floatval($row->n7)), round(floatval($row->n8))
                    , round(floatval($row->n9)), round(floatval($row->n10)), round(floatval($row->n11)), round(floatval($row->n12))
                ));
                
                $idx++;
            }
            $success['status'] = true;
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDataOther(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode;
            if($periode == ""){
                $periode = date('Ym');
            }
            $tahun = substr($periode, 0, 4);
            $klp = $request->klp;
            if($klp == "NRC"){
                $color = array("#D32f2f");
            }else{
                $color = array("green");
            }
            $sql = "select tipe,sum(nilai) as n1
            from exs_nrc
            where tahun='$tahun' and klp='$klp'
            group by tipe";
            $res = $this->execute($sql);
            $success = array("summary" => array(),"categories" => array(),"series" => array());
            
            $success["series"][] = array("name" => "Tipe", "color" => $color[0], "data" => array() );
            
            foreach ($res as $row){
                $tmp = (array)$row;
                $success["summary"][] = $tmp;
                $success["categories"][] = $tmp['tipe'];
                $success["series"][0]["data"][] = floatval($row->n1);
            }
            $success['status'] = true;
            
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
   

}
