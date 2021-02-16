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
    public $dark_color = array('#2200FF','#28DA66','#FFCD2F','#ED4346','#E225FF','#27D1E6','#FE732F','#C7C7C7');

    public function getPeriode(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct a.periode
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

    public function pencapaianYoY($periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n2,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n3,
            case when a.n1<>0 then (a.n5/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D01'
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
    
    public function rkaVSReal(Request $request,$periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select a.kode_neraca,a.nama,case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n2,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n3,
            case when a.n2<>0 then (a.n4/a.n2)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D02'
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

    public function growthRKA(Request $request,$periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $bulan = substr($periode,4,2);
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

    public function growthReal(Request $request,$periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $bulan = substr($periode,4,2);
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
    public function komposisiPdpt(Request $request, $periode){
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
            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D04' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
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

    public function rkaVSRealPdpt(Request $request, $periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D04' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['capai']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['category'] = $category;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['categori'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealPdptRp(Request $request, $periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D04' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['n4']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['category'] = $category;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['categori'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
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

    public function komposisiBeban(Request $request, $periode){
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

            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
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

    public function rkaVSRealBeban(Request $request, $periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['capai']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['category'] = $category;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['category'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function rkaVSRealBebanRp(Request $request, $periode){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and a.periode='$periode' and b.kode_grafik='D06' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
            order by b.nu
            ";
            $row = DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak
                $daftar = array();
                $category = array();
                for($i=0;$i<count($row);$i++){
                    $daftar[] = array("y"=>floatval($row[$i]['n4']),"name"=>$row[$i]['nama'],"key"=>$row[$i]['kode_neraca']); 
                    $category[] = $row[$i]['nama'];
                
                }
                $success['status'] = true;
                $success['data'] = $daftar;
                $success['category'] = $category;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['category'] = [];
                $success['status'] = true;
                
                return response()->json(['success'=>$success], $this->successStatus);
            }
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

    public function pdptFakultas(Request $request, $periode,$kode_neraca){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($periode,4,2);

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
            
			$sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca='$kode_neraca'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.thn1,0)<>0
            order by a.kode_bidang";
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);

            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
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

    public function detailPdpt(Request $request, $periode,$kode_neraca){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun= substr($periode,0,4);
            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca='$kode_neraca' and a.periode like '$tahun%'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
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

    public function pdptJurusan(Request $request, $periode,$kode_neraca,$kode_bidang){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $bulan = substr($periode,4,2);
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
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang'
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

    public function detailPdptJurusan($periode,$kode_neraca,$kode_bidang,$tahun){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
			if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

			$sql="select a.kode_pp,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,0)/isnull(b.n2,0))*100 else 0 end as capai
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D04' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang' and a.periode like '$tahun%'
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
    public function bebanFakultas(Request $request,$periode,$kode_neraca){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $bulan = substr($periode,4,2);
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
            $sql="select a.kode_bidang,a.nama $kolom
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi $sumcase
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D06' and b.kode_neraca='$kode_neraca'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and isnull(b.thn1,0)<>0
            order by a.kode_bidang";
            $row =  DB::connection($this->db)->select($sql);
            $row = json_decode(json_encode($row),true);
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
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

    public function detailBeban($periode,$kode_neraca){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $tahun= substr($periode,0,4);
            $sql=" select a.kode_bidang,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,4)/isnull(b.n2,0))*100 else 0 end as capai
            from bidang a 
            left join (select c.kode_bidang,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D06' and b.kode_neraca='$kode_neraca' and a.periode like '$tahun%'
                        group by c.kode_bidang,a.kode_lokasi
                        )b on a.kode_bidang=b.kode_bidang and a.kode_lokasi=b.kode_lokasi
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

    public function bebanJurusan(Request $request, $periode,$kode_neraca,$kode_bidang){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $bulan = substr($periode,4,2);
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
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D06' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang'
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

    public function detailBebanJurusan($periode,$kode_neraca,$kode_bidang,$tahun){
        // $kode_lokasi= $request->input('kode_lokasi');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $th = substr($periode,0,2);
			$sql="select a.kode_pp,a.nama,
            isnull(b.n2,0) as n2,isnull(b.n4,0) as n4,isnull(b.n5,0) as n5,
            case when isnull(b.n2,0)<>0 then (isnull(b.n4,0)/isnull(b.n2,0))*100 else 0 end as capai
            from pp a 
            left join (select c.kode_pp,a.kode_lokasi,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as n2,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as n4,
                            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5
                        from exs_neraca_pp a
                        inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' and b.kode_grafik='D06' and b.kode_neraca='$kode_neraca' and c.kode_bidang='$kode_bidang' and a.periode like '$tahun%'
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
            $tahun = intval($request->tahun)-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,a.nama, isnull(b.n1,0) as n1,isnull(c.n1,0) as n2,isnull(d.n1,0) as n3,isnull(e.n1,0) as n4,isnull(f.n1,0) as n5,isnull(g.n1,0) as n6
            from dash_grafik_m a
                left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR01','GR02','GR03','GR08') ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        if($row[$i]['kode_grafik'] == "GR01" || $row[$i]['kode_grafik'] == "GR03"){
                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])*-1,"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]),"kode_grafik"=>$row[$i]["kode_grafik"],"tahun"=>$ctg[$c]);
                        }
                        $c++;          
                    }
                }

                $color = array('#005FB8','#FDC500','#FB8500','#FDC500');
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
            $tahun = intval($request->tahun)-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,a.nama,
            case when isnull(b.n1,0) <> 0 then (isnull(c.n1,0)/isnull(b.n1,0))*100 else 0 end as n1,
            case when isnull(c.n1,0) <> 0 then (isnull(d.n1,0)/isnull(c.n1,0))*100 else 0 end as n2,
            case when isnull(d.n1,0) <> 0 then (isnull(e.n1,0)/isnull(d.n1,0))*100 else 0 end as n3,
            case when isnull(e.n1,0) <> 0 then (isnull(f.n1,0)/isnull(e.n1,0))*100 else 0 end as n4,
            case when isnull(f.n1,0) <> 0 then (isnull(g.n1,0)/isnull(f.n1,0))*100 else 0 end as n5,
            case when isnull(g.n1,0) <> 0 then (isnull(h.n1,0)/isnull(g.n1,0))*100 else 0 end as n6
                      from dash_grafik_m a
                      left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg2[0]."12'
                      left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg2[1]."12'
                      left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg2[2]."12'
                      left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg2[3]."12'
                      left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg2[4]."12'
                      left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg2[5]."12'
                      left join dash_grafik_lap h on a.kode_grafik=h.kode_grafik and a.kode_lokasi=h.kode_lokasi and h.periode='".$ctg2[6]."12'
                      where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR01','GR02','GR03','GR08')");

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
            $tahun = intval($request->tahun)-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,a.nama,
            case when isnull(b.n1,0) <> 0 then ((isnull(c.n1,0)-isnull(b.n1,0))/isnull(b.n1,0))*100 else 0 end as n1,
            case when isnull(c.n1,0) <> 0 then ((isnull(d.n1,0)-isnull(c.n1,0))/isnull(c.n1,0))*100 else 0 end as n2,
            case when isnull(d.n1,0) <> 0 then ((isnull(e.n1,0)-isnull(d.n1,0))/isnull(d.n1,0))*100 else 0 end as n3,
            case when isnull(e.n1,0) <> 0 then ((isnull(f.n1,0)-isnull(e.n1,0))/isnull(e.n1,0))*100 else 0 end as n4,
            case when isnull(f.n1,0) <> 0 then ((isnull(g.n1,0)-isnull(f.n1,0))/isnull(f.n1,0))*100 else 0 end as n5,
            case when isnull(g.n1,0) <> 0 then ((isnull(h.n1,0)-isnull(g.n1,0))/isnull(g.n1,0))*100 else 0 end as n6
                      from dash_grafik_m a
                      left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg2[0]."12'
                      left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg2[1]."12'
                      left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg2[2]."12'
                      left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg2[3]."12'
                      left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg2[4]."12'
                      left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg2[5]."12'
                      left join dash_grafik_lap h on a.kode_grafik=h.kode_grafik and a.kode_lokasi=h.kode_lokasi and h.periode='".$ctg2[6]."12'
                      where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR01','GR02','GR03','GR08')");

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
            $tahun = intval($request->tahun)-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,a.nama, isnull(b.n1,0) as n1,isnull(c.n1,0) as n2,isnull(d.n1,0) as n3,isnull(e.n1,0) as n4,isnull(f.n1,0) as n5,isnull(g.n1,0) as n6
            from dash_grafik_m a
                left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18') ");
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
            $tahun = intval($request->tahun)-5;
            for($x=0;$x <= 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
            
            $row =  DB::connection($this->db)->select("select a.kode_grafik,a.nama, isnull(b.n1,0) as n1,isnull(c.n1,0) as n2,isnull(d.n1,0) as n3,isnull(e.n1,0) as n4,isnull(f.n1,0) as n5,isnull(g.n1,0) as n6
            from dash_grafik_m a
                left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
                left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
                left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
                left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
                left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
                left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
                where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18') ");
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
            $tahun = intval($request->tahun)-6;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun.'-'.($tahun+1));
                array_push($ctg2,$tahun);
                $tahun++;
            }
            
            array_push($ctg2,$tahun);
            $success['ctg'] = $ctg;
            $success['ctg2'] = $ctg2;
            
            $row =  DB::connection($this->db)->select(" select a.kode_grafik,a.nama,
            case when isnull(b.n1,0) <> 0 then ((isnull(c.n1,0)-isnull(b.n1,0))/isnull(b.n1,0))*100 else 0 end as n1,
            case when isnull(c.n1,0) <> 0 then ((isnull(d.n1,0)-isnull(c.n1,0))/isnull(c.n1,0))*100 else 0 end as n2,
            case when isnull(d.n1,0) <> 0 then ((isnull(e.n1,0)-isnull(d.n1,0))/isnull(d.n1,0))*100 else 0 end as n3,
            case when isnull(e.n1,0) <> 0 then ((isnull(f.n1,0)-isnull(e.n1,0))/isnull(e.n1,0))*100 else 0 end as n4,
            case when isnull(f.n1,0) <> 0 then ((isnull(g.n1,0)-isnull(f.n1,0))/isnull(f.n1,0))*100 else 0 end as n5,
            case when isnull(g.n1,0) <> 0 then ((isnull(h.n1,0)-isnull(g.n1,0))/isnull(g.n1,0))*100 else 0 end as n6
                      from dash_grafik_m a
                      left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg2[0]."12'
                      left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg2[1]."12'
                      left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg2[2]."12'
                      left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg2[3]."12'
                      left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg2[4]."12'
                      left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg2[5]."12'
                      left join dash_grafik_lap h on a.kode_grafik=h.kode_grafik and a.kode_lokasi=h.kode_lokasi and h.periode='".$ctg2[6]."12'
                      where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR17','GR18')");

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
            
            $komponen = DB::connection($this->db)->select("select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as capai
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS3' and a.periode='$request->periode' and b.kode_grafik='GR29' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
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
            $tahun = intval(substr($request->periode,0,4))-5;
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

            $rs = DB::connection($this->db)->select("
            select a.kode_neraca,a.nama,
            case when a.jenis_akun='Pendapatan' then -a.n1 else a.n1 end as n1,
            case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end as n4,
            case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end as n5,
            case when a.n1<>0 then (a.n4/a.n1)*100 else 0 end as on_progress
            from exs_neraca a
            inner join dash_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS3' and a.periode='$request->periode' and b.kode_grafik='GR29' and (a.n1<>0 or a.n4<>0 or a.n5<>0)
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

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n4,b.n2 as nilai,case n2 when 0 then 0 else (n4/n2)*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_klp='K01'
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

            $periode = $request->periode;
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunLalu = intval($tahun)-1;
            $periodeLalu = $tahunLalu.$bulan;
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n2 as rka,b.n4 as nilai,isnull(c.n4,0) as nilai_lalu, case isnull(c.n4,0) when 0 then 0 else ((b.n4 - isnull(c.n4,0))/isnull(c.n4,0)*100) end as persen
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

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n4,n2 as nilai,case n2 when 0 then 0 else (n4/n2)*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_klp='K03'
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

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n4,n2 as nilai,case n2 when 0 then 0 else (n4/n2)*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_klp='K06'
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

    public function kelolaKeuangan(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n4,n2 as nilai,case n2 when 0 then 0 else (n4/n2)*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_klp='K04'
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

    public function penjualanPin(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $rs = DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n4,n2 as nilai,case n2 when 0 then 0 else (n4/n2)*100 end as persen  
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_klp='K05'
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
     // MS Pengembangan
    public function msPengembanganRKA(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            
            $sql="select a.kode_fakultas,a.nama,isnull(b.nilai,0) as n1,isnull(b.gar,0) as n2
            from aka_fakultas a
            left join (select d.kode_fakultas,a.kode_lokasi,sum(b.n4) as nilai,sum(b.n8) as gar
            from dash_grafik_d a
            inner join exs_glma_gar_pp b on a.kode_neraca=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join dash_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            inner join pp_fakultas d on b.kode_pp=d.kode_pp and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_grafik='GR07'  
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
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['n1']),"color"=> $color[$i]);
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'Pengembangan',"colorByPoint" => false,"data"=>$dt
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
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $color = array('#611dad','#4c4c4c','#ad1d3e','#ad571d','#30ad1d','#a31dad','#1dada8','#1d78ad','#ad9b1d','#1dad6e');

            if($request->mode == "dark"){
                $color = $this->dark_color;
            }
            
            $sql="select a.kode_lokasi,a.kode_grafik,c.nama,sum(b.n4) as nilai,sum(b.n8) as gar
            from dash_grafik_d a
            inner join exs_glma_gar b on a.kode_neraca=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            inner join dash_grafik_m c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and c.jenis='Posting' and a.kode_grafik in ('GR25','GR26','GR27','GR28')
            group by a.kode_lokasi,a.kode_grafik,c.nama
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            $success['colors'] = $color;
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['total'] = 0;
                $dt = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['nilai']),"color"=> $color[$i]);
                    $success['total'] += floatval($rs[$i]['nilai']);    
                }
                $success["series"][0]= array(
                    "name"=> 'Komposisi',"data"=>$dt
                );
                $dt[0] = array('','');
                $success['data'] = $dt;
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

    public function getLabaRugi5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $ctg = array();
            $ctg2 = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                array_push($ctg2,'RKA '.$tahun);
                array_push($ctg2,'Real '.$tahun);
                $tahun++;
            }
            $success['ctg2'] = $ctg;
            $success['ctg'] = $ctg2;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n1,b.n2,c.n1 as n3,c.n2 as n4,d.n1 as n5,d.n2 as n6,e.n1 as n7,e.n2 as n8,
            f.n1 as n9,f.n2 as n10,g.n1 as n11,g.n2 as n12
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_klp='K07'
            
            ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg2);$x++){
                        if($row[$i]['nama'] == "Beban"){
                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x])/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
                        }else if($row[$i]['nama'] == "OR"){

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1),"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
                        }else{

                            $dt[$i][]=array("y"=>floatval($row[$i]["n".$x]*-1)/1000000000,"name"=>$row[$i]["nama"],"tahun"=>$ctg2[$c]);
                        }
                        $c++;          
                    }
                }

                $color = array('#00509D','#005FB8','#FB8500','#FB8500');
                // 00296B,003F88,00509D,005FB8,208EAC,CED4DA,FDC500,FB8500
                if($request->mode == "dark"){
                    $color = array($this->dark_color[0],$this->dark_color[1],$this->dark_color[2],$this->dark_color[6]);
                }
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    if($i == 2){
                        $success["series"][$i]= array(
                            "name"=> $row[$i]['nama'], "yAxis"=>0,"color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "marker"=>array("enabled"=>true)
                            
                        );
                    }
                    else if($i == 3){
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
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR16'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR16'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
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

    public function getPend5TahunTF(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
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

    public function getPend5TahunNTF(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
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

    public function getPend5TahunKomposisi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik, a.nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR18'
            union all
            select a.kode_grafik, a.nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR17'
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
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_klp='K08'
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


    public function getBeban5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR19'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR19'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
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

    public function getBeban5TahunSDM(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
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

    public function getBeban5TahunKomposisi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik, a.nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR20'
            union all
            select a.kode_grafik, a.nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik='GR21'
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
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR16','GR19','GR20','GR22')
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

    public function getSHU5Tahun(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $ctg = array();
            $tahun = intval(substr($request->periode,0,4))-5;
            for($x=0;$x < 6;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
            $success['ctg'] = $ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,b.n1,c.n1 as n2,d.n1 as n3,e.n1 as n4,f.n1 as n5,g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_klp='K10'
            union all
            select 'Actual' as nama,b.n2 as n1,c.n2 as n2,d.n2 as n3,e.n2 as n4,f.n2 as n5,g.n2 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_klp='K10'
            union all
            select 'Capaian' as nama,b.n2/b.n1 as n1,c.n2/c.n1 as n2,d.n2/d.n1 as n3,e.n2/e.n1 as n4,f.n2/f.n1 as n5,g.n2/g.n1 as n6
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi and b.periode='".$ctg[0]."12'
            left join dash_grafik_lap c on a.kode_grafik=c.kode_grafik and a.kode_lokasi=c.kode_lokasi and c.periode='".$ctg[1]."12'
            left join dash_grafik_lap d on a.kode_grafik=d.kode_grafik and a.kode_lokasi=d.kode_lokasi and d.periode='".$ctg[2]."12'
            left join dash_grafik_lap e on a.kode_grafik=e.kode_grafik and a.kode_lokasi=e.kode_lokasi and e.periode='".$ctg[3]."12'
            left join dash_grafik_lap f on a.kode_grafik=f.kode_grafik and a.kode_lokasi=f.kode_lokasi and f.periode='".$ctg[4]."12'
            left join dash_grafik_lap g on a.kode_grafik=g.kode_grafik and a.kode_lokasi=g.kode_lokasi and g.periode='".$ctg[5]."12'
            where a.kode_lokasi='$kode_lokasi'  and a.kode_klp='K10'
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

    public function getPendCapai(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $tahun = substr($request->periode,0,4);
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n2*-1 as rka, b.n1*-1 as real,case when (b.n2*-1)-isnull(b.n1*-1,0) < 0 then abs((b.n2*-1)-isnull(b.n1*-1,0)) else 0 end as melampaui,  case when (b.n2*-1)-isnull((b.n1*-1),0) < 0 then 0 else abs((b.n2*-1)-isnull(b.n1*-1,0)) end as tidak_tercapai
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR17','GR18') and b.periode='$request->periode'
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
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000);
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
            $tahun = substr($request->periode,0,4);
            $row =  DB::connection($this->db)->select("select a.kode_neraca,b.nama,b.n4*-1 as real, b.n8*-1 as rka,case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then abs((b.n8*-1)-isnull(b.n4*-1,0)) else 0 end as melampaui,  case when (b.n8*-1)-isnull((b.n4*-1),0) < 0 then 0 else abs((b.n8*-1)-isnull(b.n4*-1,0)) end as tidak_tercapai
                from dash_grafik_d a
                inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_grafik ='GR18' and a.kode_fs='FS1'
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
            $tahun = substr($request->periode,0,4);
            $row =  DB::connection($this->db)->select("
            select a.kode_grafik,a.nama,b.n2 as rka, b.n1 as real,case when (b.n2)-isnull(b.n1,0) < 0 then abs((b.n2)-isnull(b.n1,0)) else 0 end as melampaui,  case when (b.n2)-isnull((b.n1),0) < 0 then 0 else abs((b.n2)-isnull(b.n1,0)) end as tidak_tercapai
            from dash_grafik_m a
            left join dash_grafik_lap b on a.kode_grafik=b.kode_grafik and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'  and a.kode_grafik in ('GR08','GR09') and b.periode='$request->periode'
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
                    $rka[] = array("y"=>floatval($row[$i]['rka'])/1000000000,"nlabel"=>floatval($row[$i]['rka'])/1000000000);
                    $real[] = array("y"=>$r,"nlabel"=>$row[$i]['real']/1000000000);
                    $melampaui[] = array("y"=>floatval($row[$i]['melampaui'])/1000000000,"nlabel"=>floatval($row[$i]['melampaui'])/1000000000);
                    $tdkcapai[] = array("y"=>floatval($row[$i]['tidak_tercapai'])/1000000000,"nlabel"=>floatval($row[$i]['tidak_tercapai'])/1000000000);
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
            $tahun = substr($request->periode,0,4);
            $row =  DB::connection($this->db)->select("
                select a.kode_neraca,b.nama,b.n8 as rka, b.n4 as real,case when (b.n8)-isnull((b.n4),0) < 0 then abs((b.n8)-isnull(b.n4,0)) else 0 end as melampaui,  case when (b.n8)-isnull((b.n4),0) < 0 then 0 else abs((b.n8)-isnull(b.n4,0)) end as tidak_tercapai
                from dash_grafik_d a
                inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                where a.kode_lokasi='$kode_lokasi' and b.periode='$request->periode' and a.kode_grafik='GR24' and a.kode_fs='FS1'
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

    
}
