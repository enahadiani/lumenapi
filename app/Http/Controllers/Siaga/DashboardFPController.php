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
            $tahun = substr($periode,0,4);
            $tahun_seb = intval($tahun)-1;
            $bulan = substr($periode,4,2);
            $periode_awal = $tahun.'01';
            $periode_seb = $tahun_seb.$bulan;
            $periode_awal_seb = $tahun_seb.'01';
            $filter = "";
            if(isset($r->kode_klp) && $r->kode_klp != ""){
                $filter .= " and kode_klp='$r->kode_klp' ";
            }

            $filter_periode = " periode='$periode' and ";
            $filter_periode_seb = " periode='$periode_seb' and ";
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $filter_periode = " periode='$periode' and ";
                    $filter_periode_seb = " periode='$periode_seb' and ";
                }else{
                    $filter_periode = " periode between '$periode_awal' and '$periode' and ";
                    $filter_periode_seb = " periode between '$periode_awal_seb' and '$periode_seb' and ";
                }
            }

            //PENDAPATAN
            $sql = "select isnull(sum(nilai),0)*-1 as real
            from ds_real 
            where $filter_periode kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0)*-1 as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $pend_capai_rka = ($pend_rka <> 0 ? round(($pend_real/$pend_rka)*100,1) : 0);
            $pend_capai_yoy = ($pend_yoy <> 0 ? round((($pend_real-$pend_yoy)/$pend_yoy)*100,1) : 0);
            //END PENDAPATAN

            //HPP
            $sql = "select isnull(sum(nilai),0) as real
            from ds_real 
            where $filter_periode kode_neraca='42' $filter";
            $q = DB::connection($this->db)->select($sql);
            $cogs_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0) as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='42' $filter";
            $q = DB::connection($this->db)->select($sql);
            $cogs_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='42' $filter";
            $q = DB::connection($this->db)->select($sql);
            $cogs_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $cogs_capai_rka = ($cogs_rka <> 0 ? round(($cogs_real/$cogs_rka)*100,1) : 0);
            $cogs_capai_yoy = ($cogs_yoy <> 0 ? round((($cogs_real-$cogs_yoy)/$cogs_yoy)*100,1) : 0);
            //END HPP

            //GROSS PROFIT
            $sql = "select isnull(sum(nilai),0)*-1 as real
            from ds_real 
            where $filter_periode kode_neraca='4T' $filter";
            $q = DB::connection($this->db)->select($sql);
            $gross_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0)*-1 as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='4T' $filter";
            $q = DB::connection($this->db)->select($sql);
            $gross_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='4T' $filter";
            $q = DB::connection($this->db)->select($sql);
            $gross_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $gross_capai_rka = ($gross_rka <> 0 ? round(($gross_real/$gross_rka)*100,1) : 0);
            $gross_capai_yoy = ($gross_yoy <> 0 ? round((($gross_real-$gross_yoy)/$gross_yoy)*100,1) : 0);
            //END GROSS PROFIT

            //OPEX
            $sql = "select isnull(sum(nilai),0) as real
            from ds_real 
            where $filter_periode kode_neraca='59' $filter";
            $q = DB::connection($this->db)->select($sql);
            $opex_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0) as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='59' $filter";
            $q = DB::connection($this->db)->select($sql);
            $opex_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='59' $filter";
            $q = DB::connection($this->db)->select($sql);
            $opex_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $opex_capai_rka = ($opex_rka <> 0 ? round(($opex_real/$opex_rka)*100,1) : 0);
            $opex_capai_yoy = ($opex_yoy <> 0 ? round((($opex_real-$opex_yoy)/$opex_yoy)*100,1) : 0);
            //END OPEX

            //NET INCOME
            $sql = "select isnull(sum(nilai),0)*-1 as real
            from ds_real 
            where $filter_periode kode_neraca='74' $filter";
            $q = DB::connection($this->db)->select($sql);
            $net_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0)*-1 as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='74' $filter";
            $q = DB::connection($this->db)->select($sql);
            $net_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='74' $filter";
            $q = DB::connection($this->db)->select($sql);
            $net_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $net_capai_rka = ($net_rka <> 0 ? round(($net_real/$net_rka)*100,1) : 0);
            $net_capai_yoy = ($net_yoy <> 0 ? round((($net_real-$net_yoy)/$net_yoy)*100,1) : 0);
            //END NET INCOME

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'revenue' => [
                    'nilai' => $pend_real,
                    'rka' => $pend_rka,
                    'yoy' => $pend_yoy,
                    'capai_rka' => $pend_capai_rka,
                    'capai_yoy' => $pend_capai_yoy,
                ],
                'cogs' => [
                    'nilai' => $cogs_real,
                    'rka' => $cogs_rka,
                    'yoy' => $cogs_yoy,
                    'capai_rka' => $cogs_capai_rka,
                    'capai_yoy' => $cogs_capai_yoy,
                ],
                'gross_profit' => [
                    'nilai' => $gross_real,
                    'rka' => $gross_rka,
                    'yoy' => $gross_yoy,
                    'capai_rka' => $gross_capai_rka,
                    'capai_yoy' => $gross_capai_yoy,
                ],
                'opex' => [
                    'nilai' => $opex_real,
                    'rka' => $opex_rka,
                    'yoy' => $opex_yoy,
                    'capai_rka' => $opex_capai_rka,
                    'capai_yoy' => $opex_capai_yoy,
                ],
                'net_income' => [
                    'nilai' => $net_real,
                    'rka' => $net_rka,
                    'yoy' => $net_yoy,
                    'capai_rka' => $net_capai_rka,
                    'capai_yoy' => $net_capai_yoy,
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
            $tahun = substr($periode,0,4);
            $tahun_seb = intval($tahun)-1;
            $bulan = substr($periode,4,2);
            $periode_awal = $tahun.'01';
            $periode_seb = $tahun_seb.$bulan;
            $periode_awal_seb = $tahun_seb.'01';
            $filter = "";
            if(isset($r->kode_klp) && $r->kode_klp != ""){
                $filter .= " and a.kode_klp='$r->kode_klp' ";
            }

            $filter_periode = " a.periode='$periode' and ";
            $filter_periode_seb = " a.periode='$periode_seb' and ";
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $filter_periode = " a.periode='$periode' and ";
                    $filter_periode_seb = " a.periode='$periode_seb' and ";
                }else{
                    $filter_periode = " a.periode between '$periode_awal' and '$periode' and ";
                    $filter_periode_seb = " a.periode between '$periode_awal_seb' and '$periode_seb' and ";
                }
            }

            $sql = "
			select a.kode_klp,b.nama, sum(case when a.kode_neraca in ('41','4T','74') then a.nilai*-1 else a.nilai end) as nilai
			from ds_real a
			inner join exs_klp b on a.kode_klp=b.kode_klp 
			where $filter_periode a.kode_neraca='$r->kode_neraca' $filter
			group by a.kode_klp,b.nama";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            if(count($res) > 0){
                foreach($res as $row){
                    $value = [
                        'name' => $row['kode_klp'],
                        'y' => floatval($row['nilai'])
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
            $tahun = substr($periode,0,4);
            $tahun_seb = intval($tahun)-1;
            $bulan = substr($periode,4,2);
            $periode_awal = $tahun.'01';
            $periode_seb = $tahun_seb.$bulan;
            $periode_awal_seb = $tahun_seb.'01';
            $filter = "";
            if(isset($r->kode_klp) && $r->kode_klp != ""){
                $filter .= " and a.kode_klp='$r->kode_klp' ";
            }

            $filter_periode = " a.periode='$periode' ";
            $filter_periode_seb = " a.periode='$periode_seb' ";
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $filter_periode = " a.periode='$periode' ";
                    $filter_periode_seb = " a.periode='$periode_seb' ";
                }else{
                    $filter_periode = " a.periode between '$periode_awal' and '$periode' ";
                    $filter_periode_seb = " a.periode between '$periode_awal_seb' and '$periode_seb' ";
                }
            }

            $sql = "with dashCTE(kode_klp,nama,nilai)
            as
            (
            select a.kode_klp,b.nama, sum(case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) as nilai
            from ds_real a
            inner join exs_klp b on a.kode_klp=b.kode_klp 
            where $filter_periode and a.kode_klp in ('AD','BS','TS','RB')
            group by a.kode_klp,b.nama
            )
            SELECT kode_klp,nama,nilai,nilai * 100.0/(select sum(nilai) from dashCTE) as persen
            from dashCTE;";

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
            $filter = "";
            if(isset($r->kode_klp) && $r->kode_klp != ""){
                $filter = " and a.kode_klp='$r->kode_klp' ";
            }
            $sql="select a.kode_neraca,
            sum(case when substring(a.periode,5,2) = '01' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) else 0 end) as n12
                        from ds_real a
                        where substring(a.periode,1,4)='$tahun' and a.kode_neraca in ('41','42','59','74') $filter
                        group by a.kode_neraca
                        order by a.kode_neraca
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

    public function getDefaultFilter(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $periode = '-';
            $sql = "select max(periode) as periode from ds_real";
            $select = DB::connection($this->db)->select($sql);
            if(count($select) > 0){
                $periode = $select[0]->periode;
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['periode'] = $periode;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['periode'] = "-";
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }
    
}
