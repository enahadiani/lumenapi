<?php

namespace App\Http\Controllers\Siaga;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardFPController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    private function filterReq($r,$col_array,$db_col_name,$where,$this_in){
        for($i = 0; $i<count($col_array); $i++){
            if(ISSET($r->input($col_array[$i])[0])){
                if($r->input($col_array[$i])[0] == "range" AND ISSET($r->input($col_array[$i])[1]) AND ISSET($r->input($col_array[$i])[2])){
                    $where .= " AND (".$db_col_name[$i]." between '".$r->input($col_array[$i])[1]."' AND '".$r->input($col_array[$i])[2]."') ";
                }elseif($r->input($col_array[$i])[0] == "=" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." = '".$r->input($col_array[$i])[1]."' ";
                }elseif($r->input($col_array[$i])[0] == "in" AND ISSET($r->input($col_array[$i])[1])){
                    $tmp = explode(",",$r->input($col_array[$i])[1]);
                    $this_in = "";
                    for($x=0;$x<count($tmp);$x++){
                        if($x == 0){
                            $this_in .= "'".$tmp[$x]."'";
                        }else{
        
                            $this_in .= ","."'".$tmp[$x]."'";
                        }
                    }
                    $where .= " AND ".$db_col_name[$i]." in ($this_in) ";
                }elseif($r->input($col_array[$i])[0] == "<=" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <= '".$r->input($col_array[$i])[1]."' ";
                }elseif($r->input($col_array[$i])[0] == "<>" AND ISSET($r->input($col_array[$i])[1])){
                    $where .= " AND ".$db_col_name[$i]." <> '".$r->input($col_array[$i])[1]."' ";
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

            $periode = $r->periode;

            $sql = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='41' and kode_fs='FS1' and modul='L'";
            $select = DB::connection($this->db)->select($sql);

            $sql2 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='42' and kode_fs='FS1' and modul='L'";
            $select2 = DB::connection($this->db)->select($sql2);

            $sql3 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='4T' and kode_fs='FS1' and modul='L'";
            $select3 = DB::connection($this->db)->select($sql3);

            $sql4 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='59' and kode_fs='FS1' and modul='L'";
            $select4 = DB::connection($this->db)->select($sql4);

            $sql5 = "select case when jenis_akun='Pendapatan' then -n4 else n4 end as nilai, case when jenis_akun='Pendapatan' then -n2 else n2 end as rka,  case when jenis_akun='Pendapatan' then -n5 else n5 end as yoy, 0 as rkm 
            from exs_neraca 
            where periode='$periode' and kode_lokasi='$kode_lokasi' and kode_neraca='74' and kode_fs='FS1' and modul='L'";
            $select5 = DB::connection($this->db)->select($sql5);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'revenue' => [
                    'nilai' => count($select) > 0 ? round($select[0]->nilai,0) : 0,
                    'rka' => count($select) > 0 ? round($select[0]->rka,0) : 0,
                    'yoy' => count($select) > 0 ? round($select[0]->yoy,0) : 0,
                    'capai_rka' => count($select) > 0 ? ($select[0]->rka <> 0 ? round(($select[0]->nilai/$select[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select) > 0 ? ($select[0]->yoy <> 0 ? round((($select[0]->nilai-$select[0]->yoy)/$select[0]->yoy)*100,1) : 0) : 0,
                ],
                'cogs' => [
                    'nilai' => count($select2) > 0 ? round($select2[0]->nilai,0) : 0,
                    'rka' => count($select2) > 0 ? round($select2[0]->rka,0) : 0,
                    'yoy' => count($select2) > 0 ? round($select2[0]->yoy,0) : 0,
                    'capai_rka' => count($select2) > 0 ? ($select2[0]->rka <> 0 ? round(($select2[0]->nilai/$select2[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select2) > 0 ? ($select2[0]->yoy <> 0 ? round((($select2[0]->nilai-$select2[0]->yoy)/$select2[0]->yoy)*100,1) : 0) : 0,
                ],
                'gross_profit' => [
                    'nilai' => count($select3) > 0 ? round($select3[0]->nilai,0) : 0,
                    'rka' => count($select3) > 0 ? round($select3[0]->rka,0) : 0,
                    'yoy' => count($select3) > 0 ? round($select3[0]->yoy,0) : 0,
                    'capai_rka' => count($select3) > 0 ? ($select3[0]->rka <> 0 ? round(($select3[0]->nilai/$select3[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select3) > 0 ? ($select3[0]->yoy <> 0 ? round((($select3[0]->nilai-$select3[0]->yoy)/$select3[0]->yoy)*100,1) : 0) : 0,
                ],
                'opex' => [
                    'nilai' => count($select4) > 0 ? round($select4[0]->nilai,0) : 0,
                    'rka' => count($select4) > 0 ? round($select4[0]->rka,0) : 0,
                    'yoy' => count($select4) > 0 ? round($select4[0]->yoy,0) : 0,
                    'capai_rka' => count($select4) > 0 ? ($select4[0]->rka <> 0 ? round(($select4[0]->nilai/$select4[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select4) > 0 ? ($select4[0]->yoy <> 0 ? round((($select4[0]->nilai-$select4[0]->yoy)/$select4[0]->yoy)*100,1) : 0) : 0,
                ],
                'net_income' => [
                    'nilai' => count($select5) > 0 ? round($select5[0]->nilai,0) : 0,
                    'rka' => count($select5) > 0 ? round($select5[0]->rka,0) : 0,
                    'yoy' => count($select5) > 0 ? round($select5[0]->yoy,0) : 0,
                    'capai_rka' => count($select5) > 0 ? ($select5[0]->rka <> 0 ? round(($select5[0]->nilai/$select5[0]->rka)*100,1) : 0) : 0,
                    'capai_yoy' => count($select5) > 0 ? ($select5[0]->yoy <> 0 ? round((($select5[0]->nilai-$select5[0]->yoy)/$select5[0]->yoy)*100,1) : 0) : 0,
                ]
            ];

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getKontribusi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $periode = $r->periode;

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as nilai
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.kode_neraca='$r->kode_neraca' and d.periode='$periode' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
            group by a.kode_klp,b.nama";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            if(count($res) > 0){
                foreach($res as $row){
                    $value = [
                        'name' => $row['kode_klp'],
                        'y' => abs($row['nilai'])
                    ];
                    array_push($chart, $value);
                }
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

    public function getMargin(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $periode = $r->periode;

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as nilai
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.periode='$periode' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
            group by a.kode_klp,b.nama";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getFilterKontribusi(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = array(
                0 => array(
                    'kode_neraca' => '41',
                    'nama' => 'Revenue Contribution'
                ),
                1 => array(
                    'kode_neraca' => '42',
                    'nama' => 'COGS'
                ),
                2 => array(
                    'kode_neraca' => '4T',
                    'nama' => 'Gross Profit'
                ),
                3 => array(
                    'kode_neraca' => '59',
                    'nama' => 'OPEX'
                ),
                4 => array(
                    'kode_neraca' => '74',
                    'nama' => 'Net Income'
                )
            );

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $res;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getFPBulan(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $r->tahun;

            $sql="select a.kode_neraca,a.nama,
            sum(case when substring(a.periode,5,2) = '01' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when a.jenis_akun <> 'Pendapatan' then a.n4 else -a.n4 end) else 0 end) as n12
                        from exs_neraca a
                        where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' and a.kode_neraca in ('41','42','59','74') and a.kode_fs='FS1'
                        group by a.kode_neraca,a.nama
                        order by a.kode_neraca,a.nama
            ";
            $select = DB::connection($this->db)->select($sql);
            $select = json_decode(json_encode($select),true);
            $series = array();
            $c=0;
            $series = array();
            $pend = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            $beban = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            $hpp = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            $net_income = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            foreach($select as $row){
                $data = [];
                for($i=1; $i <= 12; $i++) {
                    array_push($data, floatval($row['n'.$i]));
                }
                switch($row['kode_neraca']){
                    case '41' :
                        $pend = $data;
                    break;
                    case '42' :
                        $hpp = $data;
                    break;
                    case '59' :
                        $beban = $data;
                    break;
                    case '74' :
                        $net_income = $data;
                    break;
                }
                $c++;
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'beban' => $beban,
                'hpp' => $hpp,
                'pendapatan' => $pend,
                'net_income' => $net_income
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
