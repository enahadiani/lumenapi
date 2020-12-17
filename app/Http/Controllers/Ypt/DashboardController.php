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

    public function getPeriode(){
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
			$sql="select distinct a.periode
            from exs_neraca a
            where a.kode_lokasi='$kode_lokasi' and a.kode_fs='FS4' 
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
    
    public function rkaVSReal($periode){
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
                    "name"=> 'RKA', "type"=>'column',"color"=>'#ad1d3e',"data"=>$dt[0]
                );
                
                $success["series"][1] = array(
                    "name"=> 'Realisasi', "type"=>'column',"color"=>'#4c4c4c',"data"=>$dt[1]
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

    public function growthRKA($periode){
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

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
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

    public function growthReal($periode){
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

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
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
    public function komposisiPdpt($periode){
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

    public function rkaVSRealPdpt($periode){
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

    public function komposisiBeban($periode){
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

    public function rkaVSRealBeban($periode){
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

    public function pdptFakultas($periode,$kode_neraca){
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
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i]
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

    public function detailPdpt($periode,$kode_neraca){
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

    public function pdptJurusan($periode,$kode_neraca,$kode_bidang){
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
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i]
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

            $th = substr($periode,0,2);
            $tahun = $th.$tahun;
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
    public function bebanFakultas($periode,$kode_neraca){
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
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i]
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

    public function bebanJurusan($periode,$kode_neraca,$kode_bidang){
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
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i]
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
            $tahun = $th.$tahun;
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

            $rs = DB::connection($this->db)->select("
            select '2014' as tahun 
            union all
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun 
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun 
            union all
            select '2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $row =  DB::connection($this->db)->select("select 'Pendapatan' as nama,'411' as kode_neraca,248.04	as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,	543.28 as n2020
            union all
            select 'Beban' as nama,'511' as kode_neraca,220.04,238.40,290.79,320.30,377.72,417.38,435.63
            union all
            select 'SDM' as nama,'412' as kode_neraca,107.65,126.76,150.95,168.89,203.44,222.52,240.68
            union all
            select 'SHU' as nama,'611' as kode_neraca,28.01,53.73,64.36,95.22,96.18,104.98,107.65 ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dt[$i]
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

            $rs = DB::connection($this->db)->select("
            select '2014' as tahun 
            union all
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun 
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun 
            union all
            select '2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $row =  DB::connection($this->db)->select("select 'Pendapatan' as nama,'411' as kode_neraca,248.04	as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,	543.28 as n2020
            union all
            select 'Beban' as nama,'511' as kode_neraca,220.04,238.40,290.79,320.30,377.72,417.38,435.63
            union all
            select 'SDM' as nama,'412' as kode_neraca,107.65,126.76,150.95,168.89,203.44,222.52,240.68
            union all
            select 'SHU' as nama,'611' as kode_neraca,28.01,53.73,64.36,95.22,96.18,104.98,107.65 ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                
                $dtp[0] = array();
                $dtp[1] = array();
                $dtp[2] = array();
                $dtp[3] = array();
                for($i=0;$i< count($dt[0]);$i++){
                    $pend = round($dt[0][$i]["y"]/$dt[1][$i]["y"]*100);
                    $beb = round($dt[1][$i]["y"]/$dt[0][$i]["y"]*100);
                    $sdm = round($dt[2][$i]["y"]/$dt[1][$i]["y"]*100);
                    $shu = round($dt[3][$i]["y"]/$dt[0][$i]["y"]*100);
                    array_push($dtp[0], $pend);
                    array_push($dtp[1], $beb);
                    array_push($dtp[2], $sdm);
                    array_push($dtp[3], $shu);
                }

                for($i=0;$i<count($row);$i++){
                    
                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dtp[$i]
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

            $rs = DB::connection($this->db)->select("
            select '2014-2015' as tahun 
            union all
            select '2015-2016' as tahun 
            union all
            select '2016-2017' as tahun 
            union all
            select '2017-2018' as tahun 
            union all
            select '2018-2019' as tahun 
            union all
            select '2019-2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;

            $ctg2 = array('2014','2015','2016','2017','2018','2019','2020');
            
            $row =  DB::connection($this->db)->select("select 'Pendapatan' as nama,'411' as kode_neraca,248.04	as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,	543.28 as n2020
            union all
            select 'Beban' as nama,'511' as kode_neraca,220.04,238.40,290.79,320.30,377.72,417.38,435.63
            union all
            select 'SDM' as nama,'412' as kode_neraca,107.65,126.76,150.95,168.89,203.44,222.52,240.68
            union all
            select 'SHU' as nama,'611' as kode_neraca,28.01,53.73,64.36,95.22,96.18,104.98,107.65 ");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg2);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg2[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg2[$c]);
                        $c++;          
                    }
                }

                $dtp = array();
                for($i=0;$i< count($dt);$i++){
                    $x = array();
                    for($j=0;$j < count($dt[$i]);$j++){
                        if($j != 0){
                            $x[] = round((($dt[$i][$j]["y"]-$dt[$i][$j-1]["y"])/ $dt[$i][$j-1]["y"])*100);
                        }
                    }
                    $dtp[] = $x;
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dtp[$i]
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

            $rs = DB::connection($this->db)->select("
            select '2014' as tahun 
            union all
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun 
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun 
            union all
            select '2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $row =  DB::connection($this->db)->select("select 'Total Pendapatan' as nama,'411' as kode_neraca,248.04 as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,543.28 as n2020
            union all
            select 'Tuition Fee' as nama,'511' as kode_neraca,218.19,260.18,307.08,365.32,413.84,453.11,451.08
            union all
            select 'Non Tuition Fee' as nama,'412' as kode_neraca,29.85,30.37,46.02,46.93,57.95,66.84,91.77");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_neraca'] == '411'){
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

            $rs = DB::connection($this->db)->select("
            select '2014' as tahun 
            union all
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun 
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun 
            union all
            select '2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $row =  DB::connection($this->db)->select("select 'Total Pendapatan' as nama,'411' as kode_neraca,248.04 as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,543.28 as n2020
            union all
            select 'Tuition Fee' as nama,'511' as kode_neraca,218.19,260.18,307.08,365.32,413.84,453.11,451.08
            union all
            select 'Non Tuition Fee' as nama,'412' as kode_neraca,29.85,30.37,46.02,46.93,57.95,66.84,91.77");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $dtp[0] = array();
                $dtp[1] = array();
                $dtp[2] = array();
                for($i=0;$i< count($dt[0]);$i++){
                    $pend = 100;
                    $tuition = round($dt[1][$i]["y"]/$dt[0][$i]["y"]*100);
                    $nontuition = round($dt[2][$i]["y"]/$dt[0][$i]["y"]*100);
                    array_push($dtp[0], $pend);
                    array_push($dtp[1], $tuition);
                    array_push($dtp[2], $nontuition);
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_neraca'] == '411'){
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

            $rs = DB::connection($this->db)->select("
            select '2014-2015' as tahun 
            union all
            select '2015-2016' as tahun 
            union all
            select '2016-2017' as tahun 
            union all
            select '2017-2018' as tahun 
            union all
            select '2018-2019' as tahun 
            union all
            select '2019-2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $ctg2 = array('2014','2015','2016','2017','2018','2019','2020');
            
            $row =  DB::connection($this->db)->select("select 'Total Pendapatan' as nama,'411' as kode_neraca,248.04 as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,543.28 as n2020
            union all
            select 'Tuition Fee' as nama,'511' as kode_neraca,218.19,260.18,307.08,365.32,413.84,453.11,451.08
            union all
            select 'Non Tuition Fee' as nama,'412' as kode_neraca,29.85,30.37,46.02,46.93,57.95,66.84,91.77");
            $row = json_decode(json_encode($row),true);
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg2);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg2[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg2[$c]);
                        $c++;          
                    }
                }

                $dtp = array();
                for($i=0;$i< count($dt);$i++){
                    $x = array();
                    for($j=0;$j < count($dt[$i]);$j++){
                        if($j != 0){
                            $x[] = round((($dt[$i][$j]["y"]-$dt[$i][$j-1]["y"])/ $dt[$i][$j-1]["y"])*100);
                        }
                    }
                    $dtp[] = $x;
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "data"=>$dtp[$i]
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
            
            $komponen = DB::connection($this->db)->select("select kode_neraca,nama,n1,n4,0 as on_progress 
            from exs_neraca 
            where kode_Lokasi='$kode_lokasi' and kode_fs='FS3' and periode='$request->periode' and tipe='Posting'
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun 
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun 
            union all
            select '2020' as tahun 
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
            
            $row =  DB::connection($this->db)->select("select 'RKA' as nama,'411' as kode_neraca,248.04 as n2014,292.13 as n2015,355.15 as n2016,415.52 as n2017,473.90 as n2018,522.37 as n2019,543.28 as n2020
            union all
            select 'Real' as nama,'511' as kode_neraca,218.19,260.18,307.08,365.32,413.84,453.11,451.08
            union all
            select 'On Progress' as nama,'412' as kode_neraca,29.85,30.37,46.02,46.93,57.95,66.84,91.77");
            $row = json_decode(json_encode($row),true);
            if(count($row) > 0){ //mengecek apakah data kosong atau tidak

                for($i=0;$i<count($row);$i++){
                    $dt[$i] = array();
                    $c=0;
                    for($x=1;$x<=count($ctg);$x++){
                        $dt[$i][]=array("y"=>floatval($row[$i]["n".$ctg[$c]]),"kode_neraca"=>$row[$i]["kode_neraca"],"tahun"=>$ctg[$c]);
                        $c++;          
                    }
                }

                $color = array('#E5FE42','#007AFF','#4CD964','#FF9500');
                for($i=0;$i<count($row);$i++){

                    if($row[$i]['kode_neraca'] == '412'){
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

    public function penyerapanInvestasi(Request $request){
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $rs = DB::connection($this->db)->select("
            select sum(n1) as n1,sum(n4) as n4, 1 as on_progress
            from exs_neraca 
            where kode_Lokasi='$kode_lokasi' and kode_fs='FS3' and periode='$request->periode' and tipe='Posting'
            ");
            $jumn1 = floatval($rs[0]->n1);
            $jumn4 = floatval($rs[0]->n4);
            $jumnprog = floatval($rs[0]->on_progress);

            $rs = DB::connection($this->db)->select("
            select kode_neraca,nama,(n1/$jumn1)*100 as n1,(n4/$jumn4)*100 as n4,0 as on_progress
            from exs_neraca 
            where kode_Lokasi='$kode_lokasi' and kode_fs='FS3' and periode='$request->periode' and tipe='Posting'
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
            select 'Pendapatan' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'Beban' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'SHU' as nama, 1000000000 as nilai,120 as persen
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

            
            $rs = DB::connection($this->db)->select("
            select 'Asset' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'Liability' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'Net Asset Position' as nama, 1000000000 as nilai,120 as persen
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
            select 'Pengembangan' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'Operasional' as nama, 1000000000 as nilai,120 as persen
            union all
            select 'Non Operasional' as nama, 1000000000 as nilai,120 as persen
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
            select 'Mahasiswa' as nama, 1000000000 as nilai
            union all
            select 'NTF' as nama, 1000000000 as nilai
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
            select 'Kas di Bank' as nama, 1000000000 as nilai
            union all
            select 'Kas Unit' as nama, 1000000000 as nilai
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
            select 'Pendapatan Pin' as nama, 1000000000 as nilai,120 as persen
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

    // MS Pendapatan
    public function msPendapatan(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql=" select 'Tuition Fee' as nama, 73 as rka, 90 as act 
            union all
            select 'Non Tuition Fee' as nama, 73 as rka, 90 as act 
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $dt[0] = array();
                $dt[1] = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    array_push($dt[0],floatval($rs[$i]['rka']));
                    array_push($dt[1],floatval($rs[$i]['act']));  
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'RKA', "type"=>'column',"color"=>'#4c4c4c',"data"=>$dt[0], "pointPadding"=> 0.3
                );
                
                $success["series"][1] = array(
                    "name"=> 'Actual', "type"=>'column',"color"=>'#900604',"data"=>$dt[1], "pointPadding"=> 0.4
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

    // MS Pendapatan
    public function msPendapatanKlp(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql=" select 'Pendaftaran' as nama, 73 as rka, 90 as act 
            union all
            select 'Pelatihan' as nama, 73 as rka, 90 as act 
            union all
            select 'Proyek Kerjasama' as nama, 73 as rka, 90 as act 
            union all
            select 'Pengabdian Masyarakat' as nama, 73 as rka, 90 as act 
            union all
            select 'OP Lainnya' as nama, 73 as rka, 90 as act
            union all
            select 'Pengelolaan' as nama, 73 as rka, 90 as act  
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $dt[0] = array();
                $dt[1] = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    array_push($dt[0],floatval($rs[$i]['rka']));
                    array_push($dt[1],floatval($rs[$i]['act']));  
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'RKA', "type"=>'column',"color"=>'#4c4c4c',"data"=>$dt[0], "pointPadding"=> 0.3
                );
                
                $success["series"][1] = array(
                    "name"=> 'Actual', "type"=>'column',"color"=>'#900604',"data"=>$dt[1], "pointPadding"=> 0.4
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

    // MS Beban
    public function msBeban(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql=" select 'Tuition Fee' as nama, 73 as rka, 90 as act 
            union all
            select 'Non Tuition Fee' as nama, 73 as rka, 90 as act 
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $dt[0] = array();
                $dt[1] = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    array_push($dt[0],floatval($rs[$i]['rka']));
                    array_push($dt[1],floatval($rs[$i]['act']));  
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'RKA', "type"=>'column',"color"=>'#4c4c4c',"data"=>$dt[0], "pointPadding"=> 0.3
                );
                
                $success["series"][1] = array(
                    "name"=> 'Actual', "type"=>'column',"color"=>'#900604',"data"=>$dt[1], "pointPadding"=> 0.4
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

    // MS Beban
    public function msBebanKlp(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql=" select 'Pendaftaran' as nama, 73 as rka, 90 as act 
            union all
            select 'Pelatihan' as nama, 73 as rka, 90 as act 
            union all
            select 'Proyek Kerjasama' as nama, 73 as rka, 90 as act 
            union all
            select 'Pengabdian Masyarakat' as nama, 73 as rka, 90 as act 
            union all
            select 'OP Lainnya' as nama, 73 as rka, 90 as act
            union all
            select 'Pengelolaan' as nama, 73 as rka, 90 as act  
            ";
            $rs = DB::connection($this->db)->select($sql);
            $rs = json_decode(json_encode($rs),true);
            
            if(count($rs) > 0){ //mengecek apakah data kosong atau tidak
                
                $dt[0] = array();
                $dt[1] = array();
                $ctg= array();
                for($i=0;$i<count($rs);$i++){
                    array_push($dt[0],floatval($rs[$i]['rka']));
                    array_push($dt[1],floatval($rs[$i]['act']));  
                    array_push($ctg,$rs[$i]['nama']);    
                }
                $success['ctg'] = $ctg;
                $success["series"][0]= array(
                    "name"=> 'RKA', "type"=>'column',"color"=>'#4c4c4c',"data"=>$dt[0], "pointPadding"=> 0.3
                );
                
                $success["series"][1] = array(
                    "name"=> 'Actual', "type"=>'column',"color"=>'#900604',"data"=>$dt[1], "pointPadding"=> 0.4
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

     // MS Pengembangan
    public function msPengembanganRKA(Request $request){
        $periode= $request->input('periode');
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $color = array('#ad1d3e','#511dad','#30ad1d','#a31dad','#1dada8','#611dad','#1d78ad','#ad9b1d','#1dad6e','#ad571d');
            
            $sql=" 
            select 'Fak1' as nama, 2.45 as nilai
            union all
            select 'Fak2' as nama, 2.45 as nilai
            union all
            select 'Fak3' as nama, 2.45 as nilai
            union all
            select 'Fak4' as nama, 2.45 as nilai
            union all
            select 'Fak5' as nama, 2.45 as nilai
            union all
            select 'Fak6' as nama, 2.45 as nilai
            union all
            select 'Fak7' as nama, 2.45 as nilai
            union all
            select 'Fak8' as nama, 2.45 as nilai
            union all
            select 'Fak9' as nama, 2.45 as nilai
            union all
            select 'Fak10' as nama, 2.45 as nilai
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
                    $dt[] = array("name"=>$rs[$i]['nama'], "y" => floatval($rs[$i]['nilai']),"color"=> $color[$i]);
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
            
            $sql="
            select 'Beban Bang Lembaga' as nama, 8284465321 as nilai
            union all
            select 'Beban Bang SDM' as nama, 3552831259 as nilai
            union all
            select 'Beban Pengembangan Akademik' as nama, 2752365994 as nilai
            union all
            select 'Beban Pengembangan Sistem' as nama, 158299985 as nilai
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

            $rs = DB::connection($this->db)->select("
            select '2015 RKA' as tahun 
            union all
            select '2015 Real' as tahun
            union all 
            select '2016 RKA' as tahun 
            union all
            select '2016 Real' as tahun 
            union all
            select '2017 RKA' as tahun 
            union all
            select '2017 Real' as tahun
            union all 
            select '2018 RKA' as tahun 
            union all
            select '2018 Real' as tahun 
            union all
            select '2019 RKA' as tahun 
            union all
            select '2019 Real' as tahun
            union all 
            select '2020 RKA' as tahun 
            union all
            select '2020 Real' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'Pendapatan' as nama,273 as n1,292.1 as n2,340.6 as n3,355.2 as n4,378.2 as n5,415.5 as n6,448.5 as n7,475.4 as n8,500.9 as n9,525.5 as n10,543.3 as n11,503.0 as n12
            union all
            select 'Beban' as nama,248.6 as n1,239.8 as n2,304.4 as n3,290.8 as n4,307.4 as n5,329.5 as n6,352.5 as n7,379.2 as n8,391.9 as n9,420.6 as n10,435.6 as n11,417.2 as n12
            union all
            select 'SHU' as nama,24.4 as n1,52.3 as n2,36.2 as n3,64.4 as n4,70.8 as n5,86.0 as n6,96.0 as n7,96.3 as n8,108.9 as n9,104.9 as n10,101.9 as n11,85.8 as n12
            union all
            select 'OR' as nama,90.5 as n1,84.6 as n2,88.7 as n3,81.8 as n4,80.8 as n5,78.1 as n6,72.9 as n7,78.5 as n8,76.8 as n9,78.8 as n10,80.2 as n11,82.9 as n12");
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

                $color = array('#4c4c4c','#900604','#ffc114','#16ff14');
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun
            union all
            select '2020' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,273.0 as n1,340.6 as n2,378.2 as n3,448.5 as n4,500.9 as n5,543.3 as n6
            union all
            select 'Actual' as nama,292.1 as n1,355.2 as n2,415.5 as n3,475.4 as n4,525.5 as n5,503.0 as n6
            union all
            select 'Capaian' as nama,107.0 as n1,104.3 as n2,109.9 as n3,106.0 as n4,104.9 as n5,92.6 as n6
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

                $color = array('#4c4c4c','#900604','#16ff14');
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun
            union all
            select '2020' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,250.1 as n1,302.2 as n2,331.6 as n3,381.9 as n4,436.9 as n5,451.1 as n6
            union all
            select 'Actual' as nama,260.2 as n1,307.1 as n2,365.3 as n3,413.8 as n4,453.1 as n5,445.7 as n6
            union all
            select 'Capaian' as nama,104.0 as n1,101.6 as n2,110.2 as n3,108.4 as n4,103.7 as n5,98.8 as n6
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

                $color = array('#4c4c4c','#900604','#16ff14');
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun
            union all
            select '2020' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'RKA' as nama,22.9 as n1,38.4 as n2,46.6 as n3,66.6 as n4,63.9 as n5,92.2 as n6
            union all
            select 'Actual' as nama,31.9 as n1,48.1 as n2,50.2 as n3,61.6 as n4,72.4 as n5,57.3 as n6
            union all
            select 'Capaian' as nama,139.7 as n1,125.2 as n2,107.8 as n3,92.5 as n4,113.3 as n5,62.2 as n6
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

                $color = array('#4c4c4c','#900604','#16ff14');
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun
            union all
            select '2020' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'NTF' as nama,31.9 as n1,48.1 as n2,50.2 as n3,61.6 as n4,72.4 as n5,57.3 as n6
            union all
            select 'TF' as nama,260 as n1,307 as n2,365 as n3,414 as n4,453 as n5,446 as n6
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

                $color = array('#4c4c4c','#900604','#16ff14');
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

            $rs = DB::connection($this->db)->select("
            select '2015' as tahun 
            union all
            select '2016' as tahun 
            union all
            select '2017' as tahun
            union all
            select '2018' as tahun 
            union all
            select '2019' as tahun
            union all
            select '2020' as tahun 
            
            ");
            $rs = json_decode(json_encode($rs),true);
            $ctg = array();
            if(count($rs) > 0){
                $i=1;
                for($x=0;$x<count($rs);$x++){
                    array_push($ctg,$rs[$x]['tahun']);
                    $i++;
                }
            }
            $success['ctg']=$ctg;
                        
            $row =  DB::connection($this->db)->select("
            select 'Total Pendapatan' as nama,107.0 as n1,104.3 as n2,109.9 as n3,106.0 as n4,104.9 as n5,92.6 as n6
            union all
            select 'TF' as nama,104.0 as n1,101.6 as n2,110.2 as n3,108.4 as n4,103.7 as n5,98.8 as n6
            union all
            select 'NTF' as nama,139.7 as n1,125.2 as n2,107.8 as n3,92.5 as n4,113.3 as n5,62.2 as n6
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

                $color = array('#4c4c4c','#900604','#16ff14');
                $success['colors'] = $color;
                for($i=0;$i<count($row);$i++){

                    $success["series"][$i]= array(
                        "name"=> $row[$i]['nama'], "yAxis"=>0, "color"=>$color[$i],"data"=>$dt[$i],"type"=>"spline", "dataLabels"=>array("enabled"=>true)
                        
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
    
}
