<?php
namespace App\Http\Controllers\DashTarbak;

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
    public $guard = 'tarbak';
    public $db = 'sqlsrvtarbak';

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
            
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "WHERE a.kode_lokasi='$lokasi' AND a.kode_fs='FS1' ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
            
            if(isset($r->kode_neraca) && $r->kode_neraca != ""){
                $filter_neraca = " and a.kode_neraca='$r->kode_neraca'";
            }else{
                $filter_neraca = "";
            }
            $sql = "select a.kode_lokasi,sum(a.n1) as rka_tahun,sum(a.n2) as rka_bulan,sum(a.n6) as real_bulan,sum(a.n7) as real_rev,
            case when sum(a.n3) <> 0 then (sum(a.n4)/sum(a.n3))*100 else 0 end as capai_ytd,
            case when sum(a.n1) <> 0 then (sum(a.n4)/sum(a.n1))*100 else 0 end as capai_tahun,
            case when sum(a.n5) <> 0 then ((sum(a.n4)-sum(a.n5))/sum(a.n5))*100 else 0 end as yoy
            from exs_neraca a
            inner join dash_ypt_neraca_d b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            $where and b.kode_dash='DP02' $filter_neraca
            group by a.kode_lokasi
            ";
            $res = DB::connection($this->db)->select($sql);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'][0] = array(
                'persen_ytd' => (count($res) > 0 ? $res[0]->capai_ytd : 0),
                'rka_ytd' => (count($res) > 0 ? $res[0]->rka_bulan : 0),
                'real_ytd' => (count($res) > 0 ? $res[0]->real_bulan : 0),
                'persen_tahun' => (count($res) > 0 ? $res[0]->capai_tahun : 0),
                'rka_tahun' => (count($res) > 0 ? $res[0]->rka_tahun : 0),
                'real_tahun' => (count($res) > 0 ? $res[0]->real_bulan : 0),
                'persen_ach' => (count($res) > 0 ? $res[0]->yoy : 0),
                'real_now' => (count($res) > 0 ? $res[0]->real_bulan : 0),
                'real_lalu' => (count($res) > 0 ? $res[0]->real_rev : 0)
            );

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

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "WHERE a.kode_lokasi='$lokasi' AND a.kode_fs='FS1' ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");

            $sql = "select a.kode_neraca as kode_aset,b.nama as nama_aset,b.nu,
            sum(case when a.jenis_akun='Pendapatan' then -a.n2 else a.n2 end) as rka,
            sum(case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) as real,
            sum(case when a.jenis_akun='Pendapatan' then -a.n5 else a.n5 end) as n5,
            case when sum(a.n2)<>0 then (sum(a.n4)/sum(a.n2))*100 else 0 end as ach
            from exs_neraca a
            inner join dash_ypt_neraca_d b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            $where and b.kode_dash='DP02' and (a.n2<>0 or a.n4<>0) 
            group by a.kode_neraca,b.nama,b.nu
            order by b.nu
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

    public function getAggPerLembagaChart(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "WHERE a.kode_fs='FS1' ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
            if(isset($r->kode_neraca) && $r->kode_neraca != ""){
                $filter_neraca = " and a.kode_neraca='$r->kode_neraca'";
            }else{
                $filter_neraca = "";
            }
            $sql = "select a.kode_pp,a.nama,a.skode,isnull(b.n1,0) as nilai
            from dash_ypt_pp a
            left join (select a.kode_lokasi,a.kode_pp,sum(a.n2) as n1
                    from exs_neraca_pp a
                    inner join dash_ypt_neraca_d b on a.kode_neraca=b.kode_neraca  and a.kode_fs=b.kode_fs
                    $where and b.kode_dash='DP02' and (a.n2<>0) $filter_neraca
                    group by a.kode_lokasi,a.kode_pp
                    )b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            where a.kode_lokasi<>'$kode_lokasi' ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $chart = [];
            $idx = 0;
            foreach($res as $item) { 
                $name = $item['skode'];
                $nilai = floatval($item['nilai']);
                if($idx == 0) {
                    if($nilai < 0){
                        $value = [
                            'name' => $name,
                            'y' => abs($nilai),
                            'sliced' =>  true,
                            'selected' => true,
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',                            
                            'color' => 'url(#custom-pattern)',
                            'key' => $item['kode_lokasi']
                        ];
                    }else{
                        $value = [
                            'name' => $name,
                            'y' => $nilai,
                            'sliced' =>  true,
                            'selected' => true,
                            'negative' => false,
                            'key' => $item['kode_lokasi']
                        ];
                    }
                } else {
                    if($nilai < 0){
                        $value = [
                            'name' => $name,
                            'y' => abs($nilai),
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',                            
                            'color' => 'url(#custom-pattern)',
                            'key' => $item['kode_lokasi']
                        ];
                    }else{
                        $value = [
                            'name' => $name,
                            'y' => $nilai,
                            'negative' => false,
                            'key' => $item['kode_lokasi']
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

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $lokasi = $r->kode_lokasi;
            }else{
                $lokasi = $kode_lokasi;
            }
            
            $col_array = array('periode');
            $db_col_name = array('a.periode');
            $where = "WHERE a.kode_lokasi='$lokasi' AND a.kode_fs='FS1' ";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
            
            if(isset($r->kode_neraca) && $r->kode_neraca != ""){
                $filter_neraca = " and a.kode_neraca='$r->kode_neraca'";
            }else{
                $filter_neraca = "";
            }
            
            $periode=$r->periode[1];
            $bulan = substr($periode,4,2);
            $tahun=substr($periode,0,4);
            $ctg = array();
            $tahun = intval($tahun)-4;
            $thn = "";
            for($x=0;$x < 5;$x++){
                array_push($ctg,$tahun);
                $tahun++;
            }
           
            /*
            $sql="SELECT a.kode_lokasi, a.nama, a.skode, 1234000000 as n1, 2000000000 as n2, 1567000000 as n3, 3000000000 as n4, 5000000000 as n5
            FROM dash_ypt_lokasi a
            WHERE a.kode_lokasi IN ('11','12','13','14','15')
                ";
            */
            $sql="select a.kode_lokasi, a.nama, a.skode, isnull(b.n1,0) as n1, isnull(b.n2,0) as n2, isnull(b.n3,0) as n3, isnull(b.n4,0) as n4, isnull(b.n5,0) as n5
            from dash_ypt_lokasi a
            left join (select a.kode_lokasi,sum(case when substring(a.periode,1,4)='".$ctg[0]."' then a.n4 else 0 end) as n1,
                           sum(case when substring(a.periode,1,4)='".$ctg[1]."' then a.n4 else 0 end) as n2,
                           sum(case when substring(a.periode,1,4)='".$ctg[2]."' then a.n4 else 0 end) as n3,
                           sum(case when substring(a.periode,1,4)='".$ctg[3]."' then a.n4 else 0 end) as n4,
                           sum(case when substring(a.periode,1,4)='".$ctg[4]."' then a.n4 else 0 end) as n5
                    from exs_neraca a
                    inner join dash_ypt_neraca_d b on a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where b.kode_dash='DP02' $filter_neraca
                    group by a.kode_lokasi
                    ) b on a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi IN ('03','11','12','13','14','15')";
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $series = array();
            $i=0; $pengurang = 0; //untk dummy
            foreach($res as $dt) {
                if(!isset($series[$i])){
                    $series[$i] = array('name' => $dt['nama'], 'data' => array());
                }
                $data = array(
                    floatval($dt['n1']), 
                    floatval($dt['n2']), 
                    floatval($dt['n3']), 
                    floatval($dt['n4']), 
                    floatval($dt['n5'])
                );
                $series[$i]['data'] = $data;
                //$pengurang+= 567891011;
                $i++;
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'kategori' => $ctg,
                'series' => $series,
                'res' => $res
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