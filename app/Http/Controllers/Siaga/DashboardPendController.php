<?php

namespace App\Http\Controllers\Siaga;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardPendController extends Controller
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
            $kode_jenis_pend = array('41','4T','74');
            $sql = "select isnull(sum(nilai),0) as real
            from ds_real 
            where $filter_periode kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_real = (count($q) > 0 ? (in_array(41,$kode_jenis_pend) ? round($q[0]->real)*-1 : round($q[0]->real) ) : 0);

            $sql = "select isnull(sum(nilai),0) as yoy
            from ds_real 
            where $filter_periode_seb kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_yoy = (count($q) > 0 ? (in_array(41,$kode_jenis_pend) ? round($q[0]->yoy)*-1 : round($q[0]->yoy) ) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where $filter_periode kode_neraca='41' $filter";
            $q = DB::connection($this->db)->select($sql);
            $pend_rka = (count($q) > 0 ? round($q[0]->rka) : 0);

            $pend_capai_rka = ($pend_rka <> 0 ? round(($pend_real/$pend_rka)*100,1) : 0);
            $pend_capai_yoy = ($pend_yoy <> 0 ? round((($pend_real-$pend_yoy)/$pend_yoy)*100,1) : 0);
            //END PENDAPATAN

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'revenue' => [
                    'nilai' => $pend_real,
                    'rka' => $pend_rka,
                    'yoy' => $pend_yoy,
                    'capai_rka' => $pend_capai_rka,
                    'capai_yoy' => $pend_capai_yoy,
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
			where $filter_periode a.kode_neraca='41' $filter
			group by a.kode_klp,b.nama";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            $warna = ['#FCE597','#808080','#F7A783','#80B7BF','#1d3f9c','#3dc9cc','#a07aaa'];
            $wklp = [
                'AD' => $warna[0],
                'BS' => $warna[1],
                'RB' => $warna[2],
                'TS' => $warna[3],
                'BL' => $warna[4],
                'PL' => $warna[5],
                'BU' => $warna[6]
            ];
            if(count($res) > 0){
                foreach($res as $row){
                    $nilai = floatval($row['nilai']);
                    if($nilai < 0){
                        $value = [
                            'name' => $row['kode_klp'],
                            'y' => $nilai,
                            'negative' => true,
                            'fillColor' => 'url(#custom-pattern)',                            
                            'color' => 'url(#custom-pattern)',
                            'key' => $row['kode_klp']
                        ];
                    }else{
                        $value = [
                            'name' => $row['kode_klp'],
                            'y' => $nilai,
                            'negative' => false,
                            'fillColor' => $wklp[$row['kode_klp']],                            
                            'color' => $wklp[$row['kode_klp']],
                            'key' => $row['kode_klp']
                        ];
                    }
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

    public function getYTDvsYoY(Request $r) {
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

            $sql = "select a.kode_klp,a.nama, isnull(b.ytd,0) as ytd,  isnull(c.yoy,0) as yoy
            from exs_klp a
            left join (select a.kode_klp,sum(case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) as ytd
                        from ds_real a
                        where $filter_periode a.kode_neraca='41'
                        group by a.kode_klp
                    ) b on a.kode_klp=b.kode_klp 
            left join (select a.kode_klp,sum(case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) as yoy
                        from ds_real a
                        where $filter_periode_seb a.kode_neraca='41'
                        group by a.kode_klp
                    ) c on a.kode_klp=c.kode_klp 
            where (isnull(b.ytd,0) <> 0 or  isnull(c.yoy,0) <> 0)";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $i=0;
            foreach($res as $row){
                $res[$i]['persen'] = count($res) > 0 ? ($row['yoy'] <> 0 ? round((($row['ytd']-$row['yoy'])/$row['yoy'])*100,1) : 0) : 0;
                $i++;
            }
            
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

    public function getPendBulan(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $r->tahun;

            $sql="select a.kode_klp,b.nama,
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
                        inner join exs_klp b on a.kode_klp=b.kode_klp 
                        where substring(a.periode,1,4)='$tahun' and a.kode_neraca='41'
                        group by a.kode_klp,b.nama
                        order by a.kode_klp,b.nama
            ";
            $select = DB::connection($this->db)->select($sql);
            $select = json_decode(json_encode($select),true);
            $series = array();
            $c=0;
            $series = array();
            foreach($select as $row){
                $data = [];
                for($i=1; $i <= 12; $i++) {
                    array_push($data, floatval($row['n'.$i]));
                }
                $series[$c] = array(
                    'name' => $row['nama'],
                    'data' => $data
                );
                $c++;
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $series;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRKAvsReal(Request $r) {
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
            $filter_tahun = " substring(a.periode,1,4)='$tahun' and ";
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $filter_periode = " a.periode='$periode' and ";
                }else{
                    $filter_periode = " a.periode between '$periode_awal' and '$periode' and ";
                }
            }

            $sql = "select a.kode_klp,a.nama, isnull(b.real,0) as real,  isnull(c.rka,0) as rka
            from exs_klp a
            left join (select a.kode_klp,sum(case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) as real
                        from ds_real a
                        where $filter_periode a.kode_neraca='41' 
                        group by a.kode_klp
                    ) b on a.kode_klp=b.kode_klp 
            left join (select a.kode_klp,sum(a.rka) as rka
                        from ds_rka a
                        where $filter_periode a.kode_neraca='41' 
                        group by a.kode_klp
                    ) c on a.kode_klp=c.kode_klp 
            where (isnull(b.real,0) <> 0 or  isnull(c.rka,0) <> 0)
            ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $real_ytd = []; $rka_ytd = []; $kategori= [];
            if(count($res) > 0){
                foreach($res as $row){
                    array_push($kategori,$row['kode_klp']);
                    array_push($rka_ytd,floatval($row['rka']));
                    array_push($real_ytd,floatval($row['real']));
                }
            }

            $sql = "select a.kode_klp,a.nama, isnull(b.real,0) as real,  isnull(c.rka,0) as rka
            from exs_klp a
            left join (select a.kode_klp,sum(case when a.kode_neraca in ('41','4T','74') then -a.nilai else a.nilai end) as real
                        from ds_real a
                        where $filter_tahun a.kode_neraca='41' 
                        group by a.kode_klp
                    ) b on a.kode_klp=b.kode_klp 
            left join (select a.kode_klp,sum(a.rka) as rka
                        from ds_rka a
                        where $filter_tahun a.kode_neraca='41' 
                        group by a.kode_klp
                    ) c on a.kode_klp=c.kode_klp 
            where (isnull(b.real,0) <> 0 or  isnull(c.rka,0) <> 0)";

            $select2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($select2),true);
            $real_fy = []; $rka_fy = []; $kategori2 = [];
            if(count($res2) > 0){
                foreach($res2 as $row){
                    array_push($rka_fy,floatval($row['rka']));
                    array_push($real_fy,floatval($row['real']));
                    array_push($kategori2,$row['kode_klp']);
                }
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['kategori'] = count($kategori) > count($kategori2) ? $kategori : $kategori2;
            $success['rka_ytd'] = $rka_ytd;
            $success['real_ytd'] = $real_ytd;
            $success['rka_fy'] = $rka_fy;
            $success['real_fy'] = $real_fy;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
