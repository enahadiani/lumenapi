<?php

namespace App\Http\Controllers\Ypt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Menu;

class DashboardController extends Controller
{
	public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';
    public $dark_color = array('#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7','#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7');

    public function getPeriode(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct a.periode,dbo.fnNamaBulan(a.periode) as nama
            from periode a
            where a.kode_lokasi='$kode_lokasi'
            order by a.periode desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
			
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTahun(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct substring(a.periode,1,4) as periode
            from periode a
            where a.kode_lokasi='$kode_lokasi'
            order by substring(a.periode,1,4) desc";
			$res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
			
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                //$success['sql'] = $sql;
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pencapaianYoY(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
			$sql="select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n2,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n3,
            sum(case when a.n1<>0 then (a.n5/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='D01'
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu			 
            ";
            $capai = DB::connection($this->db)->select($sql);
            $capai = json_decode(json_encode($capai),true);
            
            if(count($capai) > 0){ //mengecek apakah data kosong atau tidak
                // for($i=0;$i<count($capai);$i++){
                //     $capai[$i]["n1"] = number_format($capai[$i]["n1"],0,",","."); 
                //     $capai[$i]["n2"] = number_format($capai[$i]["n2"],0,",","."); 
                //     $capai[$i]["n3"] = number_format($capai[$i]["n3"],0,",","."); 
                //     $capai[$i]["capai"] = number_format($capai[$i]["capai"],0,",","."); 
                // }
				$success['sql'] = $sql;
                $success['status'] = true;
                $success['data'] = $capai;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
    public function rkaVSReal(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
			$sql="select a.kode_neraca,a.nama,b.nu,sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n2,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n3,
            sum(case when a.n2<>0 then (a.n4/a.n2)*100 else 0 end) as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='D02'
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu";
            $capai = DB::connection($this->db)->select($sql);
            $capai = json_decode(json_encode($capai),true);
            
            if(count($capai) > 0){ //mengecek apakah data kosong atau tidak
                
                $dt[0] = array();
                $dt[1] = array();
                $ctg= array();
                for($i=0;$i<count($capai);$i++){
                    array_push($dt[0],floatval($capai[$i]['n1']));
                    array_push($dt[1],floatval($capai[$i]['n2']));  
                    array_push($ctg,$capai[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'RKA', "type"=>'column',"color"=>($request->mode == 'dark' ? '#4c4c4c' : '#ad1d3e'), "data"=>$dt[0],
                    "pointPadding" => 0.3,
                    "pointPlacement" => 0.2
                );
                
                $success["series"][1] = array(
                    "name"=> 'Realisasi', "type"=>'column',"color"=>($request->mode == 'dark' ? $this->dark_color[1] : '#4c4c4c'),"data"=>$dt[1],
                    "pointPadding" => 0.4,
                    "pointPlacement" => 0.2
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function growthRKA(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $bulan = substr($request->periode[1],4,2);
			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4'
                ) a
                ORDER BY tahun DESC
            ) SQ
            ORDER BY tahun ASC ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            $ctg2 = array();
            if(count($rs)> 0){
                $i=1;
                
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(c.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    if($x != 0){
                        array_push($ctg2,$rs[$x-1]['tahun']."-".$rs[$x]['tahun']);
                    }
                    $i++;
                }
            }
            // $success['ctg']=$ctg;
            $success['ctg']=$ctg2;
            
			$sql="select a.kode_neraca,b.nama $kolom
            from db_grafik_d a
            inner join neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            left join (select a.kode_neraca,a.kode_lokasi,a.kode_fs $sumcase                        
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4'  and b.kode_grafik='D02'
            group by a.kode_neraca,a.kode_lokasi,a.kode_fs
            )c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs
            where a.kode_grafik='D02' and a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4'";
            $rs2 = DB::connection($this->db)->select($sql) ;

            $row = json_decode(json_encode($rs2),true);

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($rs2);$i++){
                    $dt[$i] = array();
                    for($x=1;$x<=count($ctg);$x++){

                        array_push($dt[$i],floatval($row[$i]["thn$x"]));             
                    }
                }

                $dtp = array();
                for($i=0;$i< count($dt);$i++){
                    $x = array();
                    for($j=0;$j < count($dt[$i]);$j++){
                        if($j != 0){
                            $x[] = round((($dt[$i][$j]-$dt[$i][$j-1])/ $dt[$i][$j-1])*100);
                        }
                    }
                    $dtp[] = $x;
                }

                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_neraca'] == '47'){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"spline", "marker"=>array("enabled"=>false)
                            
                        );
                    }else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                
                }
                
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['series'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function growthReal(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $bulan = substr($request->periode[1],4,2);
			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4'
                ) a
                ORDER BY tahun DESC
            ) SQ
            ORDER BY tahun ASC ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            $ctg2 = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(c.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    if($x != 0){
                        array_push($ctg2,$rs[$x-1]['tahun']."-".$rs[$x]['tahun']);
                    }
                    $i++;
                }
            }
            $success['ctg']=$ctg2;
            $sql="select a.kode_neraca,b.nama $kolom
            from db_grafik_d a
            inner join neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            left join (select a.kode_neraca,a.kode_lokasi,a.kode_fs $sumcase                        
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4'  and b.kode_grafik='D02'
            group by a.kode_neraca,a.kode_lokasi,a.kode_fs
            )c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs
            where a.kode_grafik='D02' and a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4'";
            $rs2 = DB::connection($this->db)->select($sql) ;

            $row = json_decode(json_encode($rs2),true);

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($rs2);$i++){
                    $dt[$i] = array();
                    for($x=1;$x<=count($ctg);$x++){

                        array_push($dt[$i],floatval($row[$i]["thn$x"]));             
                    }
                }

                $dtp = array();
                for($i=0;$i< count($dt);$i++){
                    $x = array();
                    for($j=0;$j < count($dt[$i]);$j++){
                        if($j != 0){
                            $x[] = round((($dt[$i][$j]-$dt[$i][$j-1])/ $dt[$i][$j-1])*100);
                        }
                    }
                    $dtp[] = $x;
                }

                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_neraca'] == '47'){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"spline", "marker"=>array("enabled"=>false)
                            
                        );
                    }else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"column"
                            
                        );
                    }
                
                }
                
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    //PENDAPATAN
    public function komposisiPdpt(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sql="select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='D04' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu";
            $komposisi = DB::connection($this->db)->select($sql);
            $komposisi = json_decode(json_encode($komposisi),true);
            
            if(count($komposisi) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                for($i=0;$i<count($komposisi);$i++){
                    $daftar[] = array("y"=>floatval($komposisi[$i]['n1']),"name"=>$komposisi[$i]['nama'],"key"=>$komposisi[$i]['kode_neraca']); 
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealPdpt(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            // $sql="select a.kode_neraca,a.nama,b.nu,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            // sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            // from exs_neraca a
            // inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            // $where and a.kode_fs='FS4' and b.kode_grafik='D04' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            // group by a.kode_neraca,a.nama,b.nu
            // order by b.nu
            // ";
            $sql = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR17' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $sql2 = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR18' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row2 = DB::connection($this->db)->select($sql2);
            $row2 = json_decode(json_encode($row2),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['capai']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['data_tf'] = $daftar;
                $success['categori_tf'] = $category;
            }
            else{
                $success['data_tf'] = [];
                $success['categori_tf'] = [];
                
            }

            if(count($row2) > 0){ //mengecek apakah data kosong atau tidak
                $daftar2 = array();
                $category2 = array();
                for($i=0;$i<count($row2);$i++){
                    $daftar2[] = array("y"=>floatval($row2[$i]['capai']),"name"=>$row2[$i]['nama'],"key"=>$row2[$i]['kode_neraca']); 
                    $category2[] = $row2[$i]['nama'];
                
                }
                $success['data_ntf'] = $daftar2;
                $success['categori_ntf'] = $category2;
            }
            else{
                $success['data_ntf'] = [];
                $success['categori_ntf'] = [];
                
            }
            $success['message'] = "Success!";
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealPdptRp(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sql = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR17' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $sql2 = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR18' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row2 = DB::connection($this->db)->select($sql2);
            $row2 = json_decode(json_encode($row2),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['n4']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['data_tf'] = $daftar;
                $success['categori_tf'] = $category;
            }
            else{
                $success['data_tf'] = [];
                $success['categori_tf'] = [];
                
            }

            if(count($row2) > 0){ //mengecek apakah data kosong atau tidak
                $daftar2 = array();
                $category2 = array();
                for($i=0;$i<count($row2);$i++){
                    $daftar2[] = array("y"=>floatval($row2[$i]['n4']),"name"=>$row2[$i]['nama'],"key"=>$row2[$i]['kode_neraca']); 
                    $category2[] = $row2[$i]['nama'];
                
                }
                $success['data_ntf'] = $daftar2;
                $success['categori_ntf'] = $category2;
            }
            else{
                $success['data_ntf'] = [];
                $success['categori_ntf'] = [];
                
            }
            $success['message'] = "Success!";
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function totalPdpt($periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select a.kode_neraca,a.n5,a.n1,a.n4,case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D03'
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['opr'] = $row[0]["capai"];
                $success['nonopr'] = $row[1]["capai"]; 
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['opr'] = 0;
                $success['nonopr'] = 0;
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    //BEBAN

    public function komposisiBeban(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sql="select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu	 
            ";
            $komposisi = DB::connection($this->db)->select($sql);
            $komposisi = json_decode(json_encode($komposisi),true);
            
            if(count($komposisi) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                for($i=0;$i<count($komposisi);$i++){
                    $daftar[] = array("y"=>floatval($komposisi[$i]['n1']),"name"=>$komposisi[$i]['nama'],"key"=>$komposisi[$i]['kode_neraca']); 
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealBeban(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            // $sql="select a.kode_neraca,a.nama,b.nu,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            // sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            // from exs_neraca a
            // inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            // $where and a.kode_fs='FS4' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            // group by a.kode_neraca,a.nama,b.nu
            // order by b.nu
            // ";
            $sql = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR23' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $sql2 = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR24' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row2 = DB::connection($this->db)->select($sql2);
            $row2 = json_decode(json_encode($row2),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['capai']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['data_sdm'] = $daftar;
                $success['categori_sdm'] = $category;
            }
            else{
                $success['data_sdm'] = [];
                $success['categori_sdm'] = [];
                
            }

            if(count($row2) > 0){ //mengecek apakah data kosong atau tidak
                $daftar2 = array();
                $category2 = array();
                for($i=0;$i<count($row2);$i++){
                    $daftar2[] = array("y"=>floatval($row2[$i]['capai']),"name"=>$row2[$i]['nama'],"key"=>$row2[$i]['kode_neraca']); 
                    $category2[] = $row2[$i]['nama'];
                
                }
                $success['data_non'] = $daftar2;
                $success['categori_non'] = $category2;
            }
            else{
                $success['data_non'] = [];
                $success['categori_non'] = [];
                
            }
            $success['message'] = "Success!";
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealBebanRp(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            // $sql="select a.kode_neraca,a.nama,b.nu,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            // sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            // sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            // from exs_neraca a
            // inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            // $where and a.kode_fs='FS4' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            // group by a.kode_neraca,a.nama,b.nu
            // order by b.nu
            // ";
            $sql = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR23' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $sql2 = "select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS4' and b.kode_grafik='GR24' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu ";
            $row2 = DB::connection($this->db)->select($sql2);
            $row2 = json_decode(json_encode($row2),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['n4']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['data_sdm'] = $daftar;
                $success['categori_sdm'] = $category;
            }
            else{
                $success['data_sdm'] = [];
                $success['categori_sdm'] = [];
                
            }

            if(count($row2) > 0){ //mengecek apakah data kosong atau tidak
                $daftar2 = array();
                $category2 = array();
                for($i=0;$i<count($row2);$i++){
                    $daftar2[] = array("y"=>floatval($row2[$i]['n4']),"name"=>$row2[$i]['nama'],"key"=>$row2[$i]['kode_neraca']); 
                    $category2[] = $row2[$i]['nama'];
                
                }
                $success['data_non'] = $daftar2;
                $success['categori_non'] = $category2;
            }
            else{
                $success['data_non'] = [];
                $success['categori_non'] = [];
                
            }
            $success['message'] = "Success!";
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function totalBeban($periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select a.kode_neraca,a.n5,a.n1,a.n4,case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D05'
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['opr'] = $row[0]["capai"];
                $success['nonopr'] = $row[1]["capai"]; 
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['opr'] = 0;
                $success['nonopr'] = 0;
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    //DETAIL PENDAPATAN

    public function pdptFakultas(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSPendapatan"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    $filter_nol = " and (isnull(b.thn1,0)<>0) ";
                }else{
                    $filter_tahun = "";
                    $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
                }
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }

            $bulan = substr($request->periode[1],4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";           
			$rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca)
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' 
            and a.nama like 'Fakultas%'
            --and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) 
            order by a.kode_bidang";
            $success['sql'] = $sql;
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }
                $success['row'] = $row;
                $success['dt'] = $dt;
                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color"=> $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pdptFakultasNon(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($request->periode[1],4,2);
            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSPendapatan"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    $filter_nol = " and (isnull(b.thn1,0)<>0) ";
                }else{
                    $filter_tahun = "";
                    $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
                }
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }

            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }

            $sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";           
			$rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca)
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and (isnull(b.thn1,0)<>0) and a.kode_bidang not like '5%'
            order by a.kode_bidang";
            $success['sql'] = $sql;
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }
                $success['row'] = $row;
                $success['dt'] = $dt;
                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color"=> $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailPdpt(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca) and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.nama like 'Fakultas%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailPdptNon(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca) and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.kode_bidang not like '5%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function pdptJurusan(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($request->periode[1],4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_neraca' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $kode_bidang = $request->kode_bidang;

            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSPendapatan"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    
                }else{
                    $filter_tahun = "";
                    
                }
            }else{
                $filter_tahun = "";
                
            }

			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC  ";
            $success['sqlthn'] = $sql;
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            $sql="select a.kode_bidang,a.nama $kolom
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca in ($kode_neraca) and c.kode_bidang='$kode_bidang'
                        group by c.kode_pp,a.kode_lokasi
                        )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.thn1,0)<>0
            order by a.kode_pp";
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            $get = DB::connection($this->db)->select("select nama, case when nama like 'Fakultas%' then 'Jurusan' else 'PP' end as nama2 from bidang where kode_bidang='$kode_bidang' ");
            if(count($get) >0){
                $success['nama_bidang'] = $get[0]->nama;
                $success['nama_pp'] = $get[0]->nama2;
            }else{
                $success['nama_bidang'] = "-";
                $success['nama_pp'] = "-";
            }

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){ 
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]));
                        $c++;     
                    }
                }

                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailPdptJurusan(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
			if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $tahun = $request->tahun;
            $periode = $request->periode[1];
            $bulan = substr($periode,4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_neraca' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $kode_bidang = $request->kode_bidang;
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_pp,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,0)/isnull(b.n2,0))*100 else 0 end as capai
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca in ($kode_neraca) and c.kode_bidang='$kode_bidang' and a.periode = '".$tahun.$bulan."'
                        group by c.kode_pp,a.kode_lokasi
                    )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.n2,0)<>0
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    //DETAIL BEBAN
    public function bebanFakultas(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSBeban"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    $filter_nol = " and (isnull(b.thn1,0)<>0) ";
                }else{
                    $filter_tahun = "";
                    $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
                }
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }
            
            $bulan = substr($request->periode[1],4,2);
            $kode_neraca = $request->kode_neraca;
			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca='$kode_neraca'
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");
            $sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.nama like 'Fakultas%'
            order by a.kode_bidang";
            $success['sql'] = $sql;
            
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function bebanFakultasNon(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $bulan = substr($request->periode[1],4,2);
            $kode_neraca = $request->kode_neraca;
            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSBeban"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    $filter_nol = " and (isnull(b.thn1,0)<>0) ";
                }else{
                    $filter_tahun = "";
                    $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
                }
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }
			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca='$kode_neraca'
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");
            $sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and a.kode_bidang not like '5%'
            order by a.kode_bidang";
            $success['sql'] = $sql;
            
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailBeban(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_neraca = $request->kode_neraca;
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca' and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.nama like 'Fakultas%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailBebanNon(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_neraca = $request->kode_neraca;
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca' and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.kode_bidang not like '5%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function bebanJurusan(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $bulan = substr($request->periode[1],4,2);
            $kode_neraca = $request->kode_neraca;
            $kode_bidang = $request->kode_bidang;

            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");
            
            if(isset($request->form) && $request->form != ""){
                if($request->form == "fDashMSBeban"){

                    $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                    
                }else{
                    $filter_tahun = "";
                    
                }
            }else{
                $filter_tahun = "";
                
            }

			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca='$kode_neraca'
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC  ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            $sql="select a.kode_bidang,a.nama $kolom
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang' 
                        group by c.kode_pp,a.kode_lokasi
                        )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.thn1,0)<>0 --and a.kode_bidang like '5%'
            order by a.kode_pp";
            $success['sql'] = $sql;
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            $get = DB::connection($this->db)->select("select nama, case when nama like 'Fakultas%' then 'Jurusan' else 'PP' end as nama2 from bidang where kode_bidang='$kode_bidang' ");
            if(count($get) >0){
                $success['nama_bidang'] = $get[0]->nama;
                $success['nama_pp'] = $get[0]->nama2;
            }else{
                $success['nama_bidang'] = "-";
                $success['nama_pp'] = "-";
            }
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){ 
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]));
                        $c++;     
                    }
                }

                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailBebanJurusan(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $request->tahun;
            $periode = $request->periode[1];
            $bulan = substr($periode,4,2);
            $kode_neraca = $request->kode_neraca;
            $kode_bidang = $request->kode_bidang;
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");
			$sql="select a.kode_pp,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,0)/isnull(b.n2,0))*100 else 0 end as capai
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang' and a.periode = '".$tahun.$bulan."' 
                        group by c.kode_pp,a.kode_lokasi
                    )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.n4,0)<>0 --and a.kode_bidang like '5%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMenu($kode_klp){
        try {
            
            
			if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
			$sql="select a.*,b.form from menu a 
			left join m_form b on a.kode_form=b.kode_form 
			where a.kode_klp = '$kode_klp' and (isnull(a.jenis_menu,'-') = '-' OR a.jenis_menu = '') 
			order by kode_klp, rowindex";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMenu2($kode_klp){
        try {
            
            
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $menus = Menu::where('kode_induk',0)->where('kode_klp',$kode_klp)->get();

            $allMenus = Menu::with('children')->where('kode_klp',$kode_klp)->where('level_menu',1)->get();
    
            if(count($allMenus) > 0){ //mengecek apakah data kosong atau tidak
                // $success['menu'] = $menus;
                $success['allmenu'] = $allMenus;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                // $success['menu'] = [];
                $success['allmenu'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBCRKA(Request $request){
        try {
            
            
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,a.kode_neraca,x.nama, 
            case when b.jenis_akun <> 'Pendapatan' then isnull(b.n4,0) else -isnull(b.n4,0) end as n1,
            case when c.jenis_akun <> 'Pendapatan' then isnull(c.n4,0) else -isnull(c.n4,0) end as n2,
            case when d.jenis_akun <> 'Pendapatan' then isnull(d.n4,0) else -isnull(d.n4,0) end as n3,
            case when e.jenis_akun <> 'Pendapatan' then isnull(e.n4,0) else -isnull(e.n4,0) end as n4,
            case when f.jenis_akun <> 'Pendapatan' then isnull(f.n4,0) else -isnull(f.n4,0) end as n5,
            case when g.jenis_akun <> 'Pendapatan' then isnull(g.n4,0) else -isnull(g.n4,0) end as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs and b.periode='".$ctg[0]."12'
                            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs and c.periode='".$ctg[1]."12'
                            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and a.kode_fs=d.kode_fs and d.periode='".$ctg[2]."12'
                            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and a.kode_fs=e.kode_fs and e.periode='".$ctg[3]."12'
                            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and a.kode_fs=f.kode_fs and f.periode='".$ctg[4]."12'
                            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and a.kode_fs=g.kode_fs and g.periode='".$ctg[5]."12'
                            where a.kode_lokasi='$kode_lokasi' and x.kode_grafik in ('GR01','GR02','GR03','GR20')
            order by x.kode_grafik ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#005FB8','#28da66','#FDC500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], 'color'=>$color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBCRKAPersen(Request $request){
        try {
            
            
			if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg2 = array();
            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,a.kode_neraca,x.nama,
            case when isnull(b.n4,0) <> 0 then (isnull(c.n4,0)/isnull(b.n4,0))*100 else 0 end as n1,
            case when isnull(c.n4,0) <> 0 then (isnull(d.n4,0)/isnull(c.n4,0))*100 else 0 end as n2,
            case when isnull(d.n4,0) <> 0 then (isnull(e.n4,0)/isnull(d.n4,0))*100 else 0 end as n3,
            case when isnull(e.n4,0) <> 0 then (isnull(f.n4,0)/isnull(e.n4,0))*100 else 0 end as n4,
            case when isnull(f.n4,0) <> 0 then (isnull(g.n4,0)/isnull(f.n4,0))*100 else 0 end as n5,
            case when isnull(g.n4,0) <> 0 then (isnull(h.n4,0)/isnull(g.n4,0))*100 else 0 end as n6
                      from dash_grafik_d a
					  inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                      left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs and b.periode='".$ctg2[0]."12'
                      left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and a.kode_fs=c.kode_fs and c.periode='".$ctg2[1]."12'
                      left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and a.kode_fs=d.kode_fs and d.periode='".$ctg2[2]."12'
                      left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and a.kode_fs=e.kode_fs and e.periode='".$ctg2[3]."12'
                      left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and a.kode_fs=f.kode_fs and f.periode='".$ctg2[4]."12'
                      left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and a.kode_fs=g.kode_fs and g.periode='".$ctg2[5]."12'
                      left join exs_neraca h on a.kode_neraca=h.kode_neraca and a.kode_lokasi=h.kode_lokasi and a.kode_fs=h.kode_fs and h.periode='".$ctg2[6]."12'
                      where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR01','GR02','GR03','GR20') 
                      order by a.kode_grafik ");

            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x < 7;$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg2[$x]);
                        $c++;          
                    }
                }

                $color = array('#005FB8','#28da66','#FDC500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                for($i=0;$i<count($row);$i++){
                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBCGrowthRKA(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg2 = array();
            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,a.kode_neraca,x.nama,
            case when isnull(b.n4,0) <> 0 then ((isnull(c.n4,0)-isnull(b.n4,0))/isnull(b.n4,0))*100 else 0 end as n1,
            case when isnull(c.n4,0) <> 0 then ((isnull(d.n4,0)-isnull(c.n4,0))/isnull(c.n4,0))*100 else 0 end as n2,
            case when isnull(d.n4,0) <> 0 then ((isnull(e.n4,0)-isnull(d.n4,0))/isnull(d.n4,0))*100 else 0 end as n3,
            case when isnull(e.n4,0) <> 0 then ((isnull(f.n4,0)-isnull(e.n4,0))/isnull(e.n4,0))*100 else 0 end as n4,
            case when isnull(f.n4,0) <> 0 then ((isnull(g.n4,0)-isnull(f.n4,0))/isnull(f.n4,0))*100 else 0 end as n5,
            case when isnull(g.n4,0) <> 0 then ((isnull(h.n4,0)-isnull(g.n4,0))/isnull(g.n4,0))*100 else 0 end as n6
                      from dash_grafik_d a
                      inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                      left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg2[0]."12'
                      left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg2[1]."12'
                      left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg2[2]."12'
                      left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg2[3]."12'
                      left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg2[4]."12'
                      left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg2[5]."12'
                      left join exs_neraca h on a.kode_neraca=h.kode_neraca and a.kode_fs=h.kode_fs and a.kode_lokasi=h.kode_lokasi and h.periode='".$ctg2[6]."12'
                      where a.kode_lokasi='$kode_lokasi' and a.kode_grafik in ('GR01','GR02','GR03','GR20')");

            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x < 7;$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg2[$x]);
                        $c++;          
                    }
                }

                $color = array('#005FB8','#28da66','#FDC500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                for($i=0;$i<count($row);$i++){
                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBCTuiTion(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,x.nama, isnull(sum(b.n4),0) as n1,isnull(sum(c.n4),0) as n2,isnull(sum(d.n4),0) as n3,isnull(sum(e.n4),0) as n4,isnull(sum(f.n4),0) as n5,isnull(sum(g.n4),0) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18') 
				group by a.kode_grafik,x.nama
                order by a.kode_grafik ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*-1,"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#005FB8','#FDC500','#FB8500','#FDC500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_grafik'] == 'GR16'){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBCTuiTionPersen(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,x.nama, isnull(sum(b.n4),0) as n1,isnull(sum(c.n4),0) as n2,isnull(sum(d.n4),0) as n3,isnull(sum(e.n4),0) as n4,isnull(sum(f.n4),0) as n5,isnull(sum(g.n4),0) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18') 
				group by a.kode_grafik,x.nama
                order by a.kode_grafik ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*-1,"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $dtp[0] = array();
                $dtp[1] = array();
                $dtp[2] = array();
                for($i=0;$i< count($dt[0]);$i++){
                    $pend = 100;
                    $tuition =($dt[0][$i]["y"] != 0 ? round($dt[1][$i]["y"]/$dt[0][$i]["y"]*100) : 0);
                    $nontuition = ($dt[0][$i]["y"] != 0 ? round($dt[2][$i]["y"]/$dt[0][$i]["y"]*100) : 0);
                    array_push($dtp[0], $pend);
                    array_push($dtp[1], $tuition);
                    array_push($dtp[2], $nontuition);
                }

                $color = array('#005FB8','#FDC500','#FB8500','#FDC500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_grafik'] == 'GR16'){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "color"=>$color[$i],"data"=>$dtp[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getBCGrowthTuition(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg2 = array();
            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,x.nama,
            case when isnull(sum(b.n1),0) <> 0 then ((isnull(sum(c.n1),0)-isnull(sum(b.n1),0))/isnull(sum(b.n1),0))*100 else 0 end as n1,
            case when isnull(sum(c.n1),0) <> 0 then ((isnull(sum(d.n1),0)-isnull(sum(c.n1),0))/isnull(sum(c.n1),0))*100 else 0 end as n2,
            case when isnull(sum(d.n1),0) <> 0 then ((isnull(sum(e.n1),0)-isnull(sum(d.n1),0))/isnull(sum(d.n1),0))*100 else 0 end as n3,
            case when isnull(sum(e.n1),0) <> 0 then ((isnull(sum(f.n1),0)-isnull(sum(e.n1),0))/isnull(sum(e.n1),0))*100 else 0 end as n4,
            case when isnull(sum(f.n1),0) <> 0 then ((isnull(sum(g.n1),0)-isnull(sum(f.n1),0))/isnull(sum(f.n1),0))*100 else 0 end as n5,
            case when isnull(sum(g.n1),0) <> 0 then ((isnull(sum(h.n1),0)-isnull(sum(g.n1),0))/isnull(sum(g.n1),0))*100 else 0 end as n6
                from dash_grafik_d a
                inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg2[0]."12'
                left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg2[1]."12'
                left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg2[2]."12'
                left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg2[3]."12'
                left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg2[4]."12'
                left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg2[5]."12'
                left join exs_neraca h on a.kode_neraca=h.kode_neraca and a.kode_fs=h.kode_fs and a.kode_lokasi=h.kode_lokasi and h.periode='".$ctg2[6]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18') 
				group by a.kode_grafik,x.nama
                order by a.kode_grafik ");

            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x < 7;$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg2[$x]);
                        $c++;          
                    }
                }

                $color = array('#005FB8','#28da66','#FDC500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                for($i=0;$i<count($row);$i++){
                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    //INVESTASI
    public function komponenInvestasi(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
            $komponen = DB::connection($this->db)->select("select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS3' and b.kode_grafik='GR29' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu
            ");
            $komponen = json_decode(json_encode($komponen),true);
            
            if(count($komponen) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                for($i=0;$i<count($komponen);$i++){
                    $daftar[] = array("y"=>floatval($komponen[$i]['n1']),"name"=>$komponen[$i]['nama'],"key"=>$komponen[$i]['kode_neraca']); 
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealInvestasi(Request $request){
        try {
            
            
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $sql = "
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR30'
            union all
            select 'Actual' as nama,b.n4 as n1,c.n4 as n2,d.n4 as n3,e.n4 as n4,f.n4 as n5,g.n4 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR30'
            union all
            select 'On Progress' as nama,case when b.n1 <> 0 then b.n4/b.n1 else 0 end as n1,case when c.n1 <> 0 then c.n4/c.n1 else 0 end as n2,case when d.n1 <> 0 then d.n4/d.n1 else 0 end as n3,case when e.n1 <> 0 then e.n4/e.n1 else 0 end as n4,case when f.n1 <> 0 then f.n4/f.n1 else 0 end as n5,case when g.n1 <> 0 then g.n4/g.n1 else 0 end as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR30'
            ";
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            $success['row'] = $sql;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "On Progress"){
                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function penyerapanInvestasi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            

            $rs = DB::connection($this->db)->select("
            select a.kode_neraca,a.nama,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end) as n1,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            sum(case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end) as on_progress
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_fs='FS3' and b.kode_grafik='GR29' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            group by a.kode_neraca,a.nama,b.nu
            order by b.nu
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $ctg = array();
                $dt[0] = array();
                $dt[1] = array();
                $dt[2] = array();
                if(count($rs) > 0){
                    $i=1;
                    for($x=0;$x<count($rs);$x++){
                        array_push($ctg,$rs[$x]['nama']);
                        array_push($dt[0],round(floatval($rs[$x]['n1']),2));
                        array_push($dt[1],round(floatval($rs[$x]['n4']),2));
                        array_push($dt[2],round(floatval($rs[$x]['on_progress']),2));
                        $i++;
                    }
                }
                $success['ctg']=$ctg;

                $color = array('#0004FF','#FF8F01','#A5A5A5');
                $name = array('RKA','Real','On Progress');
                $type = array('area','column','column');
                for($i=0;$i<count($name);$i++){
                    $success["series"][$i]= array(
                        "name"=> $name[$i], "color"=>$color[$i],"data"=>$dt[$i],"type"=>$type[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    // Management System
    public function profitLoss(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,sum(b.n1) as real,sum(b.n2) as rka,case sum(n2) when 0 then 0 else (sum(n1)/sum(n2))*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_klp='K01'
            group by a.kode_grafik,a.nama
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function fxPosition(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = $request->periode[1];
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunLalu = intval($tahun)-1;
            $periodeLalu = $tahunLalu.$bulan;
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,
                case when a.dc='D' then b.n1 else b.n1*-1 end as real,
                case when a.dc='D' then b.n2 else b.n2*-1 end as rka,
                case when a.dc='D' then c.n1 else c.n1*-1 end as real_lalu, 
                case isnull(c.n1,0) when 0 then 0 else ((b.n1 - isnull(c.n1,0))/isnull(c.n1,0)*100) end as persen
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='$periode'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='$periodeLalu'
            where a.kode_lokasi='$kode_lokasi' and a.kode_klp='K02'
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function penyerapanBeban(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,sum(b.n1) as real,sum(n2) as rka,case sum(n2) when 0 then 0 else (sum(n1)/sum(n2))*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_klp='K03'
            group by a.kode_grafik,a.nama
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function debt(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $col_array = array('periode');
            // $db_col_name = array('b.periode');
            // $where = "where a.kode_lokasi='$kode_lokasi'";
            // $this_in = "";
            // for($i = 0; $i<count($col_array); $i++){
            //     if(ISSET($request->input($col_array[$i])[0])){
            //         if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
            //             $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
            //         }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
            //             $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
            //         }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
            //             $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
            //         }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
            //             $tmp = explode(",",$request->input($col_array[$i])[1]);
            //             for($x=0;$x<count($tmp);$x++){
            //                 if($x == 0){
            //                     $this_in .= "'".$tmp[$x]."'";
            //                 }else{
            
            //                     $this_in .= ","."'".$tmp[$x]."'";
            //                 }
            //             }
            //             $where .= " and ".$db_col_name[$i]." in ($this_in) ";
            //         }
            //     }
            // }
            
            // $rs = DB::connection($this->db)->select("
            // select a.kode_grafik,a.nama,sum(b.n1) as real,sum(n2) as rka,case sum(n2) when 0 then 0 else (sum(n1)/sum(n2))*100 end as persen  
            // from dash_grafik_m a
            // left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            // $where and a.kode_klp='K06'
            // group by a.kode_grafik,a.nama
            // ");
            $periode = $request->periode[1];
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunLalu = intval($tahun)-1;
            $periodeLalu = $tahunLalu.$bulan;
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,
                case when a.dc='D' then b.n1 else b.n1*-1 end as real,
                case when a.dc='D' then b.n2 else b.n2*-1 end as rka,
                case when a.dc='D' then c.n1 else c.n1*-1 end as real_lalu, 
                case isnull(c.n1,0) when 0 then 0 else ((b.n1 - isnull(c.n1,0))/isnull(c.n1,0)*100) end as yoy
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='$periode'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='$periodeLalu'
            where a.kode_lokasi='$kode_lokasi' and a.kode_klp='K06'
            ");
            $total = 0;
            foreach($rs as $row){
                $total+= floatval($row->real);
            }
            $rs = json_decode(json_encode($rs),true);
            $success['total'] = $total;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function kelolaKeuangan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $col_array = array('periode');
            // $db_col_name = array('b.periode');
            // $where = "where a.kode_lokasi='$kode_lokasi'";
            // $this_in = "";
            // for($i = 0; $i<count($col_array); $i++){
            //     if(ISSET($request->input($col_array[$i])[0])){
            //         if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
            //             $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
            //         }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
            //             $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
            //         }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
            //             $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
            //         }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
            //             $tmp = explode(",",$request->input($col_array[$i])[1]);
            //             for($x=0;$x<count($tmp);$x++){
            //                 if($x == 0){
            //                     $this_in .= "'".$tmp[$x]."'";
            //                 }else{
            
            //                     $this_in .= ","."'".$tmp[$x]."'";
            //                 }
            //             }
            //             $where .= " and ".$db_col_name[$i]." in ($this_in) ";
            //         }
            //     }
            // }
            
            // $rs = DB::connection($this->db)->select("
            // select a.kode_grafik,a.nama,sum(b.n1) as real,sum(n2) as rka,case sum(n2) when 0 then 0 else (sum(n1)/sum(n2))*100 end as persen  
            // from dash_grafik_m a
            // left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            // $where and a.kode_klp='K04'
            // group by a.kode_grafik,a.nama
            // ");
            $periode = $request->periode[1];
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunLalu = intval($tahun)-1;
            $periodeLalu = $tahunLalu.$bulan;
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,
                case when a.dc='D' then b.n1 else b.n1*-1 end as real,
                case when a.dc='D' then b.n2 else b.n2*-1 end as rka,
                case when a.dc='D' then c.n1 else c.n1*-1 end as real_lalu, 
                case isnull(c.n1,0) when 0 then 0 else ((b.n1 - isnull(c.n1,0))/isnull(c.n1,0)*100) end as yoy
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='$periode'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='$periodeLalu'
            where a.kode_lokasi='$kode_lokasi' and a.kode_klp='K04'
            ");
            $total = 0;
            foreach($rs as $row){
                $total+= floatval($row->real);
            }
            $success['total'] = $total;
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function penjualanPin(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,sum(b.n1) as real,sum(n2) as rka,case sum(n2) when 0 then 0 else (sum(n1)/sum(n2))*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_klp='K12'
            group by a.kode_grafik,a.nama
            ");
            $total =0; $total_rka=0;
            foreach($rs as $row){
                $total+=floatval($row->real);
                $total_rka+=floatval($row->rka);
            }
            $success['total_real'] = $total;
            $success['total_rka'] = $total_rka;
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
     // MS Pengembangan
    public function msPengembanganRKA(Request $request){
        try {
            
            $kode_grafik = $request->kode_grafik;
            $nama = $request->nama;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
            // $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            // if($request->mode == "dark"){
            //     $color = $this->dark_color;
            // }
            // Warna per fakultas
            $color = array('#2200FF','#FFCD2F','#38995a','#27D1E6','#E225FF','#FE732F','#28DA66');
            
            $sql="select a.kode_fakultas,a.nama,isnull(b.nilai,0) as n1,isnull(b.gar,0) as n2
            from aka_fakultas a
            left join (select d.kode_fakultas,a.kode_lokasi,sum(b.n4) as nilai,sum(b.n8) as gar
            from dash_grafik_d a
            inner join exs_glma_gar_pp b on a.kode_neraca=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join dash_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            inner join pp_fakultas d on b.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
            $where and a.kode_grafik='$kode_grafik'  
            group by d.kode_fakultas,a.kode_lokasi          
                    )b on a.kode_fakultas=b.kode_fakultas and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and (isnull(b.nilai,0)<>0 or isnull(b.gar,0)<>0)
            order by a.kode_fakultas
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                // $dt = array();
                // for($i=0;$i<count($rs);$i++){
                //     $dt[$i]= array($rs[$i]['nama'],floatval($rs[$i]['nilai']),$color[$i]);
                // }
                // $dt[0] = array('','',array('role'=>'style'));
                $dt = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['n2']),"color"=> $color[$i]);
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> $nama,"colorByPoint" => false,"data"=>$dt
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function msPengembanganRKADir(Request $request){
        $periode= $request->input('periode');
        try {
            
            $kode_grafik = $request->kode_grafik;
            $nama = $request->nama;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_rektor,a.nama,isnull(b.nilai,0) as n1,isnull(b.gar,0) as n2
            from rektor a
            left join (select e.kode_rektor,a.kode_lokasi,sum(b.n4) as nilai,sum(b.n8) as gar
            from dash_grafik_d a
            inner join exs_glma_gar_pp b on a.kode_neraca=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join dash_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            inner join pp d on b.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
			inner join exs_bidang e on d.kode_bidang=e.kode_bidang and d.kode_lokasi=e.kode_lokasi
            $where and a.kode_grafik='$kode_grafik'  
            group by e.kode_rektor,a.kode_lokasi          
                    )b on a.kode_rektor=b.kode_rektor and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and (isnull(b.nilai,0)<>0 or isnull(b.gar,0)<>0) and a.kode_rektor <> 5
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                // $dt = array();
                // for($i=0;$i<count($rs);$i++){
                //     $dt[$i]= array($rs[$i]['nama'],floatval($rs[$i]['nilai']),$color[$i]);
                // }
                // $dt[0] = array('','',array('role'=>'style'));
                $dt = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['n2']),"color"=> $color[$i]);
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> $nama,"colorByPoint" => false,"data"=>$dt
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function msPengembanganKomposisi(Request $request){
        try {
            
            $kode_grafik = $request->kode_grafik;
            $nama = $request->nama;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $color = array('#611dad','#4c4c4c','#ad1d3e','#ad571d','#30ad1d','#a31dad','#1dada8','#1d78ad','#ad9b1d','#1dad6e');

            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
            if($kode_grafik == "GR07"){
                $kd_grafik = "('GR25','GR26','GR27','GR28')";
            }else{
                $kd_grafik = "()";
            }

            $sql="select a.kode_lokasi,a.kode_grafik,c.nama,sum(b.n4) as nilai,sum(b.n8) as gar
            from dash_grafik_d a
            inner join exs_glma_gar b on a.kode_neraca=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join dash_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            $where and c.jenis='Posting' and a.kode_grafik in $kd_grafik
            group by a.kode_lokasi,a.kode_grafik,c.nama
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['total'] = 0;
                $success['total_real'] = 0;
                $dt = array();
                $dt2= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['gar']),"color"=> $color[$i]);
                    $dt2[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['nilai']),"color"=> $color[$i]);
                    $success['total'] += floatval($rs[$i]['gar']);    
                    $success['total_real'] += floatval($rs[$i]['nilai']);    
                }
                $success["series"][0]= array(
                    "name"=> 'Komposisi RKA',"data"=>$dt
                );
                $success["series_real"][0]= array(
                    "name"=> 'Komposisi Realisasi',"data"=>$dt2
                );
                $dt[0] = array('','');
                $success['data'] = $dt;
                $dt2[0] = array('','');
                $success['data2'] = $dt2;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['total'] = 0;
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    // public function getLabaRugi5TahunBackup(Request $request){
    //     try {
            
            
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //         }

            
    //         $ctg = array();
    //         $ctg2 = array();
    //         $tahun = intval(substr($request->periode[1],0,4))-5;
    //         for($x=0;$x < 6;$x++){
    //             array_push($ctg,$tahun);
    //             array_push($ctg2,'RKA '.$tahun);
    //             array_push($ctg2,'Real '.$tahun);
    //             $tahun++;
    //         }
    //         $success['ctg2'] = $ctg;
    //         $success['ctg'] = $ctg2;
                        
    //         // $row =  DB::connection($this->db)->select("
    //         // select a.kode_grafik,x.nama,b.n2 as n1,b.n4 as n2,c.n2 as n3,c.n4 as n4,d.n2 as n5,d.n4 as n6,e.n2 as n7,e.n4 as n8,
    //         // f.n2 as n9,f.n4 as n10,g.n2 as n11,g.n4 as n12
    //         // from dash_grafik_d a
    //         // inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
    //         // left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
    //         // left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
    //         // left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
    //         // left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
    //         // left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
    //         // left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
    //         // where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K07'
    //         // ");
    //         $row = DB::connection($this->db)->select("select a.kode_grafik,x.nama,
    //         sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka1, 
    //         sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real1,
    //         sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end) as rka2, 
    //         sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end) as real2,
    //         sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end) as rka3, 
    //         sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end) as real3,
    //         sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end) as rka4, 
    //         sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end) as real4,
    //         sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end) as rka5, 
    //         sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end) as real5,
    //         sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end) as rka6, 
    //         sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end) as real6
    //         from dash_grafik_d a
    //                 inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
    //                 left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
    //                 left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
    //                 left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
    //                 left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
    //                 left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
    //                 left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
    //                 where a.kode_lokasi='11'  and x.kode_klp='K07'
    //                 group by a.kode_grafik,x.nama");
    //         $row = json_decode(json_encode($row),true);
    //         if(count($row) > 0){ //mengecek apakah data kosong atau tidak

    //             for($i=0;$i<count($row);$i++){
    //                 $dt[$i] = array();
    //                 $c=0;
    //                 for($x=1;$x<=count($ctg2);$x++){
    //                     if($row[$i]['nama'] == "Beban"){
    //                         $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
    //                     }else if($row[$i]['nama'] == "OR"){

    //                         $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1),"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
    //                     }else{

    //                         $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
    //                     }
    //                     $c++;          
    //                 }
    //             }

    //             $color = array('#00509D','#005FB8','#FB8500','#FB8500');
    //             // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
    //             if($request->mode == "dark"){
    //                 $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[6]);
    //             }
    //             $success['colors'] = $color;
    //             for($i=0;$i<count($row);$i++){

    //                 if($i == 2){
    //                     $success["series"][$i]= array(
    //                         "name"=> $row[$i]['nama'], "yAxis"=>0,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
    //                     );
    //                 }
    //                 else if($i == 3){
    //                     $success["series"][$i]= array(
    //                         "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
    //                     );
    //                 }
    //                 else{
                        
    //                     $success["series"][$i]= array(
    //                         "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
    //                     );
    //                 }
    //             }


    //             $success['status'] = true;
    //             $success['message'] = "Success!";
                
    //             return response()->json(['success'=>$success], $this->successStatus);     
    //         }
    //         else{
    //             $success['message'] = "Data Kosong!";
    //             $success['series'] = [];
    //             $success['status'] = true;
                
    //             return response()->json(['success'=>$success], $this->successStatus);
    //         }
    //     } catch (\Throwable $e) {
    //         $success['status'] = false;
    //         $success['message'] = "Error ".$e;
    //         return response()->json($success, $this->successStatus);
    //     }
    // }

    public function getLabaRugi5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $ctg = array();
            $ctg2 = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                array_push($ctg2,'Pendapatan '.$tahun);
                array_push($ctg2,'Beban '.$tahun);
                array_push($ctg2,'SHU '.$tahun);
                // array_push($ctg2,'OR '.$tahun);
                $tahun++;
            }
            $success['ctg2'] = $ctg;
            $success['categories'] = $ctg2;
                  
            $row = DB::connection($this->db)->select("select a.kode_grafik,x.nama,
            sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka1, 
            sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real1,
            sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end) as rka2, 
            sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end) as real2,
            sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end) as rka3, 
            sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end) as real3,
            sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end) as rka4, 
            sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end) as real4,
            sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end) as rka5, 
            sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end) as real5,
            sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end) as rka6, 
            sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end) as real6
            from dash_grafik_d a
                    inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
                    left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                    left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                    left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                    left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                    left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                    left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                    where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K07' and x.nama not in ('OR')
                    group by a.kode_grafik,x.nama");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                        // array_push($ctg,$row[$i]['nama']);
                    }

                }
                $success['rka'] = $rka;
                // $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR16'
            union all
            select 'Actual' as nama,b.n4 as n1,c.n4 as n2,d.n4 as n3,e.n4 as n4,f.n4 as n5,g.n4 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR16'
            union all
            select 'Capaian' as nama,b.n4/b.n2 as n1,c.n4/c.n2 as n2,d.n4/d.n2 as n3,e.n4/e.n2 as n4,f.n4/f.n2 as n5,g.n4/g.n2 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR16'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_grafik='GR16' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunTFBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            union all
            select 'Actual' as nama,b.n4 as n1,c.n4 as n2,d.n4 as n3,e.n4 as n4,f.n4 as n5,g.n4 as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            union all
            select 'Capaian' as nama, 
            case when b.n2 <> 0 then b.n4/b.n2 else 0 end as n1,
            case when c.n2 <> 0 then c.n4/c.n2 else 0 end as n2,
            case when d.n2 <> 0 then d.n4/d.n2 else 0 end as n3,
            case when e.n2 <> 0 then e.n4/e.n2 else 0 end as n4,
            case when f.n2 <> 0 then f.n4/f.n2 else 0 end as n5,
            case when g.n2 <> 0 then g.n4/g.n2 else 0 end as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunTF(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_grafik='GR17' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunNTFBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,sum(b.n2) as n1,sum(c.n2) as n2,sum(d.n2) as n3,sum(e.n2) as n4,sum(f.n2) as n5,sum(g.n2) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            union all
            select 'Actual' as nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            union all
            select 'Capaian' as nama,
            case when sum(b.n2) <> 0 then sum(b.n4)/sum(b.n2) else 0 end as n1,
            case when sum(c.n2) <> 0 then sum(c.n4)/sum(c.n2) else 0 end as n2,
            case when sum(d.n2) <> 0 then sum(d.n4)/sum(d.n2) else 0 end as n3,
            case when sum(e.n2) <> 0 then sum(e.n4)/sum(e.n2) else 0 end as n4,
            case when sum(f.n2) <> 0 then sum(f.n4)/sum(f.n2) else 0 end as n5,
            case when sum(g.n2) <> 0 then sum(g.n4)/sum(g.n2) else 0 end as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                $success['count'] = count($row);
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunNTF(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_grafik='GR18' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunKomposisi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik, x.nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            group by a.kode_grafik,x.nama
            union all
            select a.kode_grafik, x.nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*-1,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                        
                    );
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPend5TahunGrowth(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,sum(b.n4)/sum(b.n2) as n1,sum(c.n4)/sum(c.n2) as n2,sum(d.n4)/sum(d.n2) as n3,sum(e.n4)/sum(e.n2) as n4,sum(f.n4)/sum(f.n2) as n5,sum(g.n4)/sum(g.n2) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K08'
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#005FB8','#FDC500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "yAxis"=>1, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "dataLabels"=>array("enabled"=>true)
                        
                    );
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getBeban5TahunBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,sum(b.n2) as n1,sum(c.n2) as n2,sum(d.n2) as n3,sum(e.n2) as n4,sum(f.n2) as n5,sum(g.n2) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR19'
            union all
            select 'Actual' as nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR19'
            union all
            select 'Capaian' as nama,
            case when sum(b.n2) <> 0 then sum(b.n4)/sum(b.n2) else 0 end as n1,
            case when sum(c.n2) <> 0 then sum(c.n4)/sum(c.n2) else 0 end as n2,
            case when sum(d.n2) <> 0 then sum(d.n4)/sum(d.n2) else 0 end as n3,
            case when sum(e.n2) <> 0 then sum(e.n4)/sum(e.n2) else 0 end as n4,
            case when sum(f.n2) <> 0 then sum(f.n4)/sum(f.n2) else 0 end as n5,
            case when sum(g.n2) <> 0 then sum(g.n4)/sum(g.n2) else 0 end as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR19'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBeban5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_grafik='GR19' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBeban5TahunSDMBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,sum(b.n2) as n1,sum(c.n2) as n2,sum(d.n2) as n3,sum(e.n2) as n4,sum(f.n2) as n5,sum(g.n2) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            union all
            select 'Actual' as nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            union all
            select 'Capaian' as nama,
            case when sum(b.n2) <> 0 then sum(b.n4)/sum(b.n2) else 0 end as n1,
            case when sum(c.n2) <> 0 then sum(c.n4)/sum(c.n2) else 0 end as n2,
            case when sum(d.n2) <> 0 then sum(d.n4)/sum(d.n2) else 0 end as n3,
            case when sum(e.n2) <> 0 then sum(e.n4)/sum(e.n2) else 0 end as n4,
            case when sum(f.n2) <> 0 then sum(f.n4)/sum(f.n2) else 0 end as n5,
            case when sum(g.n2) <> 0 then sum(g.n4)/sum(g.n2) else 0 end as n6
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBeban5TahunSDM(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_grafik='GR20' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBeban5TahunKomposisi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik, x.nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            group by a.kode_grafik,x.nama
            union all
            select a.kode_grafik, x.nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR21'
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                        
                    );
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBeban5TahunGrowth(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,sum(b.n4)/sum(b.n2) as n1,sum(c.n4)/sum(c.n2) as n2,sum(d.n4)/sum(d.n2) as n3,sum(e.n4)/sum(e.n2) as n4,sum(f.n4)/sum(f.n2) as n5,sum(g.n4)/sum(g.n2) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_grafik in ('GR16','GR19','GR20','GR22')
            group by a.kode_grafik,x.nama
            order by a.kode_grafik
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#005FB8','#FDC500','#FB8500','#FDC500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[3]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "yAxis"=>1, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "dataLabels"=>array("enabled"=>true)
                        
                    );
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSHU5TahunBackup(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,sum(b.n2) as n1,sum(c.n2) as n2,sum(d.n2) as n3,sum(e.n2) as n4,sum(f.n2) as n5,sum(g.n2) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K10'
            union all
            select 'Actual' as nama,sum(b.n4) as n1,sum(c.n4) as n2,sum(d.n4) as n3,sum(e.n4) as n4,sum(f.n4) as n5,sum(g.n4) as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K10'
            union all
            select 'Capaian' as nama,
            case when sum(b.n2) <> 0 then sum(b.n4)/sum(b.n2) else 0 end as n1,
            case when sum(c.n2) <> 0 then sum(c.n4)/sum(c.n2) else 0 end as n2,
            case when sum(d.n2) <> 0 then sum(d.n4)/sum(d.n2) else 0 end as n3,
            case when sum(e.n2) <> 0 then sum(e.n4)/sum(e.n2) else 0 end as n4,
            case when sum(f.n2) <> 0 then sum(f.n4)/sum(f.n2) else 0 end as n5,
            case when sum(g.n2) <> 0 then sum(g.n4)/sum(g.n2) else 0 end as n6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12' and a.kode_fs=b.kode_fs
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12' and a.kode_fs=c.kode_fs
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12' and a.kode_fs=d.kode_fs
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12' and a.kode_fs=e.kode_fs
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12' and a.kode_fs=f.kode_fs
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12' and a.kode_fs=g.kode_fs
            where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K10'
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['nama'] == "Capaian"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*100,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                // $color = array('#4c4c4c','#900604','#16ff14');
                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[6]);
                }
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>1,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else{
                        
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"column", "dataLabels"=>array("enabled"=>true)
                            
                        );
                    }
                }


                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSHU5Tahun(Request $request){
        try{
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $ctg = array();
            $tahun = intval(substr($request->periode[1],0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,x.nama,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end),0) as rka1, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end),0) as real1,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n1 else c.n1 end),0) as rka2, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -c.n4 else c.n4 end),0) as real2,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n1 else d.n1 end),0) as rka3, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -d.n4 else d.n4 end),0) as real3,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n1 else e.n1 end),0) as rka4, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -e.n4 else e.n4 end),0) as real4,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n1 else f.n1 end),0) as rka5, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -f.n4 else f.n4 end),0) as real5,
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n1 else g.n1 end),0) as rka6, 
            isnull(sum(case when b.jenis_akun='Pendapatan' then -g.n4 else g.n4 end),0) as real6
            from dash_grafik_d a
            inner join dash_grafik_m x on a.kode_grafik=x.kode_grafik and a.kode_lokasi=x.kode_lokasi
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join exs_neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join exs_neraca d on a.kode_neraca=d.kode_neraca and a.kode_fs=d.kode_fs and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join exs_neraca e on a.kode_neraca=e.kode_neraca and a.kode_fs=e.kode_fs and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join exs_neraca f on a.kode_neraca=f.kode_neraca and a.kode_fs=f.kode_fs and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join exs_neraca g on a.kode_neraca=g.kode_neraca and a.kode_fs=g.kode_fs and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and x.kode_klp='K10' 
            group by a.kode_grafik,x.nama
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){

                    for($j=1; $j <= 6 ;$j++){

                        $selisih = floatval($row[$i]['real'.$j]) - floatval($row[$i]['rka'.$j]);
                        if($selisih > 0){
                            $lebih = $selisih; 
                            $kurang = 0;
                            $r = floatval($row[$i]['rka'.$j])/1000000000;
                        }else if($selisih == 0){
                            $lebih = 0;
                            $kurang = 0;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }else{
                            
                            $lebih = 0;
                            $kurang = $selisih * -1;
                            $r = $row[$i]['real'.$j]/1000000000;
                        }
    
                        $rka[] = array("y"=>floatval($row[$i]['rka'.$j])/1000000000,"nlabel"=>floatval($row[$i]['rka'.$j])/1000000000);
                        $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real'.$j]/1000000000);
                        $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                        $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    }

                }
                $success['rka'] = $rka;
                $success['categories'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
            
    }

    public function getPendCapai(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,sum(b.n2*-1) as rka, sum(b.n1*-1) as real,sum(case when (b.n2*-1)-isnull(b.n1*-1,0) < 0 then abs((b.n2*-1)-isnull(b.n1*-1,0)) else 0 end) as melampaui,  sum(case when (b.n2*-1)-isnull((b.n1*-1),0) < 0 then 0 else abs((b.n2*-1)-isnull(b.n1*-1,0)) end) as tidak_tercapai
            from dash_grafik_m a
            inner join dash_grafik_d c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_grafik in ('GR17','GR18') 
            group by a.kode_grafik,a.nama
            order by a.kode_grafik
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                }
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPendCapaiKlp(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $row =  DB::connection($this->db)->select("select a.kode_neraca,b.nama,sum(b.n4*-1) as real, sum(b.n8*-1) as rka,sum(case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then abs((b.n8*-1)-isnull(b.n4*-1,0)) else 0 end) as melampaui,  sum(case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then 0 else abs((b.n8*-1)-isnull(b.n4*-1,0)) end) as tidak_tercapai
                from dash_grafik_d a
                inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                $where and a.kode_grafik ='GR18' 
            group by a.kode_neraca,b.nama
            order by a.kode_neraca
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000);
                    array_push($ctg,$row[$i]['nama']);
                }
                $success['rka'] = $rka;
                $success['ctg'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['ctg'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBebanCapai(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,c.kode_neraca,sum(b.n2) as rka, sum(b.n1) as real,sum(case when (b.n2)-isnull(b.n1,0) < 0 then abs((b.n2)-isnull(b.n1,0)) else 0 end) as melampaui,  sum(case when (b.n2)-isnull((b.n1),0) < 0 then 0 else abs((b.n2)-isnull(b.n1,0)) end) as tidak_tercapai
            from dash_grafik_m a
			inner join dash_grafik_d c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_grafik in ('GR08','GR09')
            group by a.kode_grafik,a.nama,c.kode_neraca
            order by a.kode_grafik
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000,"key"=>$row[$i]['kode_neraca'],"key2"=>$row[$i]['kode_grafik']);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000,"key"=>$row[$i]['kode_neraca'],"key2"=>$row[$i]['kode_grafik']);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000,"key"=>$row[$i]['kode_neraca'],"key2"=>$row[$i]['kode_grafik']);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"key"=>$row[$i]['kode_neraca'],"key2"=>$row[$i]['kode_grafik']);
                }
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBebanCapaiKlp(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $row =  DB::connection($this->db)->select("
                select a.kode_neraca,b.nama,sum(b.n8) as rka, sum(b.n4) as real,sum(case when (b.n8)-isnull((b.n4),0) < 0 then abs((b.n8)-isnull(b.n4,0)) else 0 end) as melampaui,  sum(case when (b.n8)-isnull((b.n4),0) < 0 then 0 else abs((b.n8)-isnull(b.n4,0)) end) as tidak_tercapai
                from dash_grafik_d a
                inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                $where and a.kode_grafik='GR24'
                group by a.kode_neraca,b.nama 
                order by a.kode_neraca
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000);
                    array_push($ctg,$row[$i]['nama']);
                }
                $success['rka'] = $rka;
                $success['ctg'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['ctg'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getDaftarBank(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $sqlex="exec sp_glma_trail_tmp 'FS4','112','$kode_lokasi','$kode_lokasi','$kode_lokasi','".$request->periode[1]."','$request->nik_user'";
            $res = DB::connection($this->db)->update($sqlex);

            $rs = DB::connection($this->db)->select("
            select a.kode_akun,c.nama,a.kode_lokasi,a.so_akhir
            from glma_tmp a
            inner join relakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi
            $where and b.kode_fs='FS4' and a.nik_user='$request->nik_user' and b.kode_neraca='112' and (a.so_awal<>0 or a.debet<>0 or a.kredit<>0 or a.so_akhir<>0) 
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getSHUDetail(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            
            $rs = DB::connection($this->db)->select("
            select a.kode_neraca,b.nama, sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka,
            sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='GR43' and a.kode_fs='FS4'
            group by a.kode_neraca,b.nama
            ");
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $rs;
                $success['status'] = true;
                $success['message'] = "Success!";    
            }
            else{
                $success['data'] = [];
                $success['status'] = true;
                $success['message'] = "Data Kosong!";
            
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSAsetBackup(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_neraca,b.nama, sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka,
            sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='GR44' 
            group by a.kode_neraca,b.nama
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $dt = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['real']),"color"=> $color[$i]);
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'Aset',"colorByPoint" => false,"data"=>$dt
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSAset(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_neraca,b.nama, sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka,
            sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='GR44' 
            group by a.kode_neraca,b.nama
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            $success['colors'] = $color;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){

                    $selisih = floatval($row[$i]['real']) - floatval($row[$i]['rka']);
                    if($selisih > 0){
                        $lebih = $selisih; 
                        $kurang = 0;
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if($selisih == 0){
                        $lebih = 0;
                        $kurang = 0;
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        
                        $lebih = 0;
                        $kurang = $selisih * -1;
                        $r = $row[$i]['real']/1000000000;
                    }
                    
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                    $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    array_push($ctg,$row[$i]['nama']);

                }
                $success['categories'] = $ctg;
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSHutangBackup(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_neraca,b.nama, sum(case when b.modul='P' then -b.n1 else b.n1 end) as rka,
            sum(case when b.modul='P' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='GR45' and a.kode_fs='FS4' 
            group by a.kode_neraca,b.nama
            having sum(b.n4) <> 0
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                $dt = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['real']),"color"=> $color[$i]);
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'Hutang',"colorByPoint" => false,"data"=>$dt
                );
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSHutang(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_neraca,b.nama, sum(case when b.modul='P' then -b.n1 else b.n1 end) as rka,
            sum(case when b.modul='P' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='GR45' and a.kode_fs='FS4' 
            group by a.kode_neraca,b.nama
            having sum(b.n4) <> 0
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            $success['colors'] = $color;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){

                    $selisih = floatval($row[$i]['real']) - floatval($row[$i]['rka']);
                    if($selisih > 0){
                        $lebih = $selisih; 
                        $kurang = 0;
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if($selisih == 0){
                        $lebih = 0;
                        $kurang = 0;
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        
                        $lebih = 0;
                        $kurang = $selisih * -1;
                        $r = $row[$i]['real']/1000000000;
                    }
                    
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                    $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    array_push($ctg,$row[$i]['nama']);

                }
                $success['categories'] = $ctg;
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getListVideo(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            
			$sql="select no_bukti,convert(varchar,tanggal,103) as tgl,keterangan,link,flag_aktif,nik_user from dash_video where flag_aktif='1' and kode_lokasi='$kode_lokasi'
            ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    // DRILLDOWN MANAGEMENT SYSTEM 

    public function getMSCapai(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,sum(b.n2*-1) as rka, sum(b.n1*-1) as real,sum(case when (b.n2*-1)-isnull(b.n1*-1,0) < 0 then abs((b.n2*-1)-isnull(b.n1*-1,0)) else 0 end) as melampaui,  sum(case when (b.n2*-1)-isnull((b.n1*-1),0) < 0 then 0 else abs((b.n2*-1)-isnull(b.n1*-1,0)) end) as tidak_tercapai
            from dash_grafik_m a
            inner join dash_grafik_d c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            $where and a.kode_grafik in ($kode_grafik)
            group by a.kode_grafik,a.nama
            order by a.kode_grafik
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"key"=>$row[$i]['kode_grafik'],"key2"=>$row[$i]['kode_grafik']);
                }
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSCapaiKlp(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }

            $row =  DB::connection($this->db)->select("select a.kode_neraca,b.nama,sum(b.n4*-1) as real, sum(b.n8*-1) as rka,sum(case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then abs((b.n8*-1)-isnull(b.n4*-1,0)) else 0 end) as melampaui,  sum(case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then 0 else abs((b.n8*-1)-isnull(b.n4*-1,0)) end) as tidak_tercapai
                from dash_grafik_d a
                inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                $where and a.kode_grafik ='$kode_grafik' 
            group by a.kode_neraca,b.nama
            order by a.kode_neraca
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){
                    if(floatval($row[$i]['melampaui']) > 0){
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if(floatval($row[$i]['tidak_tercapai']) > 0){
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        $r = $row[$i]['real']/1000000000;
                    }
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000);
                    array_push($ctg,$row[$i]['nama']);
                }
                $success['rka'] = $rka;
                $success['ctg'] = $ctg;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['ctg'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function dataDrillFakultas(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->form) && $request->form != ""){
                $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                $filter_nol = " and (isnull(b.thn1,0)<>0) ";
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }

            $bulan = substr($request->periode[1],4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";           
			$rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca)
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' 
            and a.nama like 'Fakultas%'
            --and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) 
            order by a.kode_bidang";
            $success['sql'] = $sql;
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }
                $success['row'] = $row;
                $success['dt'] = $dt;
                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color"=> $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function dataDrillDirektorat(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($request->periode[1],4,2);
            if(isset($request->form) && $request->form != ""){
                $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
                $filter_nol = " and (isnull(b.thn1,0)<>0) ";
                
            }else{
                $filter_tahun = "";
                $filter_nol = " and (isnull(b.thn1,0)<>0 or isnull(b.thn2,0)<>0 or isnull(b.thn3,0)<>0 or isnull(b.thn4,0)<>0 or isnull(b.thn5,0)<>0 or isnull(b.thn6,0)<>0) ";
            }

            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }

            $sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC ";           
			$rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,substr($rs[$x]['tahun'],2,2));
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca)
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and (isnull(b.thn1,0)<>0) and a.kode_bidang not like '5%'
            order by a.kode_bidang";
            $success['sql'] = $sql;
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d','#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]),"kode_bidang"=>$row[$i]["kode_bidang"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }
                $success['row'] = $row;
                $success['dt'] = $dt;
                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color"=> $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailDrillFakultas(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca) and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.nama like 'Fakultas%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailDrillDirektorat(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_grafik' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $tahun= substr($request->periode[1],0,4);
            $periode = $request->periode[1];
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='$kode_grafik' and b.kode_neraca in ($kode_neraca) and a.periode = '$periode'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' --and isnull(b.n2,0)<>0 
            and a.kode_bidang not like '5%'
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function dataDrillPP(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($request->periode[1],4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_neraca' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $kode_bidang = $request->kode_bidang;

            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D04");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

            if(isset($request->form) && $request->form != ""){
                $filter_tahun = " where tahun = '".substr($request->periode[1],0,4)."' ";
            }else{
                $filter_tahun = "";
                
            }

			$sql="SELECT
            tahun
            FROM
            (
                SELECT TOP 6 * from (
                select distinct substring(periode,1,4) as tahun
                FROM exs_neraca_pp 
                WHERE kode_lokasi='$kode_lokasi' and kode_fs='FS4' and kode_neraca in ($kode_neraca)
                ) a
                ORDER BY tahun DESC
            ) SQ
            $filter_tahun
            ORDER BY tahun ASC  ";
            $success['sqlthn'] = $sql;
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            $sumcase = "";
            $kolom ="";
            $ctg = array();
            if(count($rs)> 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    $sumcase .= " , sum(case when a.periode='".$rs[$x]['tahun']."".$bulan."' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as thn$i ";
                    $kolom .=",isnull(b.thn$i,0) as thn$i";

                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            $sql="select a.kode_bidang,a.nama $kolom
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca in ($kode_neraca) and c.kode_bidang='$kode_bidang'
                        group by c.kode_pp,a.kode_lokasi
                        )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.thn1,0)<>0
            order by a.kode_pp";
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            $success['colors'] = $color;

            $get = DB::connection($this->db)->select("select nama, case when nama like 'Fakultas%' then 'Jurusan' else 'PP' end as nama2 from bidang where kode_bidang='$kode_bidang' ");
            if(count($get) >0){
                $success['nama_bidang'] = $get[0]->nama;
                $success['nama_pp'] = $get[0]->nama2;
            }else{
                $success['nama_bidang'] = "-";
                $success['nama_pp'] = "-";
            }

            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){ 
                        $dt[$i][]=array("y"=>floatval($row[$i]["thn$x"]));
                        $c++;     
                    }
                }

                // $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i], "color" => $color[$i]
                    );
                }

                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function detailDrillPP(Request $request){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
			if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $tahun = $request->tahun;
            $periode = $request->periode[1];
            $bulan = substr($periode,4,2);
            if(substr($request->kode_neraca,0,2) == "GR"){
                $kode_neraca = "select kode_neraca from dash_grafik_d where kode_grafik='$request->kode_neraca' and kode_lokasi='$kode_lokasi' ";
            }else{
                $kode_neraca = "'".$request->kode_neraca."'";
            }
            $kode_bidang = $request->kode_bidang;
            $kode_grafik = ($request->kode_grafik != "" ? $request->kode_grafik : "D06");
            $tbl = ($request->kode_grafik != "" ? "dash_grafik_d" : "db_grafik_d");

			$sql="select a.kode_pp,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,0)/isnull(b.n2,0))*100 else 0 end as capai
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join $tbl b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca in ($kode_neraca) and c.kode_bidang='$kode_bidang' and a.periode = '".$tahun.$bulan."'
                        group by c.kode_pp,a.kode_lokasi
                    )b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.n2,0)<>0
            order by a.kode_bidang
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $success['data'] = $row;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getMSPiutang(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('b.periode');
            $where = "where a.kode_lokasi='$kode_lokasi'";
            $this_in = "";
            for($i = 0; $i<count($col_array); $i++){
                if(ISSET($request->input($col_array[$i])[0])){
                    if($request->input($col_array[$i])[0] == "range" AND ISSET($request->input($col_array[$i])[1]) AND ISSET($request->input($col_array[$i])[2])){
                        $where .= " and (".$db_col_name[$i]." between '".$request->input($col_array[$i])[1]."' AND '".$request->input($col_array[$i])[2]."') ";
                    }else if($request->input($col_array[$i])[0] == "=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." = '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "<=" AND ISSET($request->input($col_array[$i])[1])){
                        $where .= " and ".$db_col_name[$i]." <= '".$request->input($col_array[$i])[1]."' ";
                    }else if($request->input($col_array[$i])[0] == "in" AND ISSET($request->input($col_array[$i])[1])){
                        $tmp = explode(",",$request->input($col_array[$i])[1]);
                        for($x=0;$x<count($tmp);$x++){
                            if($x == 0){
                                $this_in .= "'".$tmp[$x]."'";
                            }else{
            
                                $this_in .= ","."'".$tmp[$x]."'";
                            }
                        }
                        $where .= " and ".$db_col_name[$i]." in ($this_in) ";
                    }
                }
            }
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }

            $sql="select a.kode_neraca,b.nama, sum(case when b.jenis_akun='Pendapatan' then -b.n1 else b.n1 end) as rka,
            sum(case when b.jenis_akun='Pendapatan' then -b.n4 else b.n4 end) as real,case sum(n1) when 0 then 0 else (sum(n4)/sum(n1))*100 end as persen  
            from dash_grafik_d a
            left join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            $where and a.kode_grafik='$request->kode_grafik' 
            group by a.kode_neraca,b.nama
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            $success['colors'] = $color;
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $rka = array();
                $real = array();
                $melampaui = array();
                $tdkcapai = array();
                $ctg = array();
                for($i=0;$i<count($row);$i++){

                    $selisih = floatval($row[$i]['real']) - floatval($row[$i]['rka']);
                    if($selisih > 0){
                        $lebih = $selisih; 
                        $kurang = 0;
                        $r = floatval($row[$i]['rka'])/1000000000;
                    }else if($selisih == 0){
                        $lebih = 0;
                        $kurang = 0;
                        $r = $row[$i]['real']/1000000000;
                    }else{
                        
                        $lebih = 0;
                        $kurang = $selisih * -1;
                        $r = $row[$i]['real']/1000000000;
                    }
                    
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($lebih)/1000000000,"nlabel"=>floatval($lebih)/1000000000);
                    $tdkcapai[] = array("y"=>floatval($kurang)/1000000000,"nlabel"=>floatval($kurang)/1000000000);
                    array_push($ctg,$row[$i]['nama']);

                }
                $success['categories'] = $ctg;
                $success['rka'] = $rka;
                $success['actual'] = $real;
                $success['melampaui'] = $melampaui;
                $success['tdkcapai'] = $tdkcapai;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['categories'] = [];
                $success['rka'] = [];
                $success['real'] = [];
                $success['melampaui'] = [];
                $success['tdkcapai'] = [];
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    

    
}
