<?php

namespace App\Http\Controllers\Ypt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardPBHController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

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

            $tahun = $r->tahun;
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and a.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml,sum(datediff(day,a.tanggal,d.tanggal)) as jml_hari
            from it_aju_m a
            inner join ver_m d on a.no_ver=d.no_ver and a.kode_lokasi=d.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select = DB::connection($this->db)->select($sql);

            $sqli = "select a.kode_lokasi,count(a.no_aju) as jml
            from it_aju_m a
            inner join ver_m d on a.no_ver=d.no_ver and a.kode_lokasi=d.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and d.tanggal=CONVERT(varchar,getdate(),23) $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $selecti = DB::connection($this->db)->select($sqli);

            $sql2 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml,sum(datediff(day,d.tanggal,b.tanggal)) as jml_hari
            from it_aju_m a
            inner join fiat_m b on a.no_fiat=b.no_fiat and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            inner join ver_m d on a.no_ver=d.no_ver and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select2 = DB::connection($this->db)->select($sql2);

            $sql2i = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join fiat_m b on a.no_fiat=b.no_fiat and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.tanggal=CONVERT(varchar,getdate(),23) $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select2i = DB::connection($this->db)->select($sql2i);

            $sql3 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml,sum(datediff(day,d.tanggal,b.tanggal)) as jml_hari
            from it_aju_m a
            inner join it_spb_m b on a.no_spb=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            inner join fiat_m d on a.no_fiat=d.no_fiat and a.kode_lokasi=d.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select3 = DB::connection($this->db)->select($sql3);

            $sql3i = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join it_spb_m b on a.no_spb=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.tanggal=CONVERT(varchar,getdate(),23) $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select3i = DB::connection($this->db)->select($sql3i);

            $sql4 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml,sum(datediff(day,d.tanggal,b.tanggal)) as jml_hari
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join it_spb_m d on a.no_spb=d.no_spb and a.kode_lokasi=d.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select4 = DB::connection($this->db)->select($sql4);

            $sql4i = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join it_spb_m d on a.no_spb=d.no_spb and a.kode_lokasi=d.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.tanggal=CONVERT(varchar,getdate(),23) $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select4i = DB::connection($this->db)->select($sql4i);

            $sql7 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml,sum(datediff(day,a.tanggal,b.tanggal)) as jml_hari
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select7 = DB::connection($this->db)->select($sql7);

            $sql5 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select5 = DB::connection($this->db)->select($sql5);

            $sql5i = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.tanggal=CONVERT(varchar,getdate(),23) $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select5i = DB::connection($this->db)->select($sql5i);

            $sql6 = "select a.kode_lokasi,count(a.no_aju) as jml
            from it_aju_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' and progress='R' $filter_pp $filter_bidang
            group by a.kode_lokasi ";
            $select6 = DB::connection($this->db)->select($sql6);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'ver_dok' => [
                    'nilai' => count($select) > 0 ? $select[0]->nilai : 0,
                    'jml' => count($select) > 0 ? $select[0]->jml : 0,
                    'hari_ini' => count($selecti) > 0 ? $selecti[0]->jml : 0,
                    'jml_hari' => count($select) > 0 ? ($select[0]->jml_hari <> 0 ? round(($select[0]->jml/$select[0]->jml_hari),0) : 0) : 0,
                ],
                'ver_akun' => [
                    'nilai' => count($select2) > 0 ? $select2[0]->nilai : 0,
                    'jml' => count($select2) > 0 ? $select2[0]->jml : 0,
                    'hari_ini' => count($select2i) > 0 ? $select2i[0]->jml : 0,
                    'jml_hari' => count($select2) > 0 ? ($select2[0]->jml_hari <> 0 ? round(($select2[0]->jml/$select2[0]->jml_hari),0) : 0) : 0,
                ],
                'spb' => [
                    'nilai' => count($select3) > 0 ? $select3[0]->nilai : 0,
                    'jml' => count($select3) > 0 ? $select3[0]->jml : 0,
                    'hari_ini' => count($select3i) > 0 ? $select3i[0]->jml : 0,
                    'jml_hari' => count($select3) > 0 ? ($select3[0]->jml_hari <> 0 ? round(($select3[0]->jml/$select3[0]->jml_hari),0) : 0) : 0,
                ],
                'spb_bayar' => [
                    'nilai' => count($select4) > 0 ? $select4[0]->nilai : 0,
                    'jml' => count($select4) > 0 ? $select4[0]->jml : 0,
                    'hari_ini' => count($select4i) > 0 ? $select4i[0]->jml : 0,
                    'jml_hari' => count($select4) > 0 ? ($select4[0]->jml_hari <> 0 ? round(($select4[0]->jml/$select4[0]->jml_hari),0) : 0) : 0,
                ],
                'rata_rata' => [
                    'jml' => count($select7) > 0 ? $select7[0]->jml : 0,
                    'jml_hari' => count($select7) > 0 ? ($select7[0]->jml_hari <> 0 ? ($select7[0]->jml/$select7[0]->jml_hari) : 0) : 0,
                ],
                'aju' => [
                    'nilai' => count($select5) > 0 ? $select5[0]->nilai : 0,
                    'jml' => count($select5) > 0 ? $select5[0]->jml : 0,
                    'hari_ini' => count($select5i) > 0 ? $select5i[0]->jml : 0,
                    'jml_hari' => 0,
                ],
                'revisi' => [
                    'jml' => count($select6) > 0 ? $select6[0]->jml : 0
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

    public function getJenisPengajuan(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $tahun = $r->tahun;
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and a.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql = "select a.kode_lokasi,sum(case when a.jenis='OFFLINE' then a.jml else 0 end) as jml_offline,
            sum(case when a.jenis='ONLINE' then a.jml else 0 end) as jml_online
            from (
            select a.kode_lokasi,b.jenis,count(a.no_aju) as jml
            from it_aju_m a
            inner join it_ajuapp_m b on a.no_aju=b.no_aju and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi,b.jenis
                )a
            group by a.kode_lokasi";
            

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            $success['colors'] = ['#FFCC00','#007AFF'];
            if(count($res) > 0){
                $item = $res[0];
                $value = [
                    'name' => 'Offine',
                    'y' => abs($item['jml_offline']),
                    'key' => 'Offine'
                ];
                array_push($chart, $value);
                $value = [
                    'name' => 'Online',
                    'y' => abs($item['jml_online']),
                    'key' => 'Online'
                ];
                array_push($chart, $value);
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

    public function getNilaiKas(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $r->tahun;
            $tahun_seb = intval($tahun) - 1 ; 
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and a.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql="select a.kode_lokasi,
            sum(case when substring(b.periode,5,2) = '01' then a.nilai else null end) as n1,
            sum(case when substring(b.periode,5,2) = '02' then a.nilai else null end) as n2,
            sum(case when substring(b.periode,5,2) = '03' then a.nilai else null end) as n3,
            sum(case when substring(b.periode,5,2) = '04' then a.nilai else null end) as n4,
            sum(case when substring(b.periode,5,2) = '05' then a.nilai else null end) as n5,
            sum(case when substring(b.periode,5,2) = '06' then a.nilai else null end) as n6,
            sum(case when substring(b.periode,5,2) = '07' then a.nilai else null end) as n7,
            sum(case when substring(b.periode,5,2) = '08' then a.nilai else null end) as n8,
            sum(case when substring(b.periode,5,2) = '09' then a.nilai else null end) as n9,
            sum(case when substring(b.periode,5,2) = '10' then a.nilai else null end) as n10,
            sum(case when substring(b.periode,5,2) = '11' then a.nilai else null end) as n11,
            sum(case when substring(b.periode,5,2) = '12' then a.nilai else null end) as n12
                        from it_aju_m a
                        inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and substring(b.periode,1,4)='$tahun_seb' $filter_pp $filter_bidang
                        group by a.kode_lokasi
                        order by a.kode_lokasi
            ";
            $select = DB::connection($this->db)->select($sql);
            $select = json_decode(json_encode($select),true);

            $sql2="select a.kode_lokasi,
            sum(case when substring(b.periode,5,2) = '01' then a.nilai else null end) as n1,
            sum(case when substring(b.periode,5,2) = '02' then a.nilai else null end) as n2,
            sum(case when substring(b.periode,5,2) = '03' then a.nilai else null end) as n3,
            sum(case when substring(b.periode,5,2) = '04' then a.nilai else null end) as n4,
            sum(case when substring(b.periode,5,2) = '05' then a.nilai else null end) as n5,
            sum(case when substring(b.periode,5,2) = '06' then a.nilai else null end) as n6,
            sum(case when substring(b.periode,5,2) = '07' then a.nilai else null end) as n7,
            sum(case when substring(b.periode,5,2) = '08' then a.nilai else null end) as n8,
            sum(case when substring(b.periode,5,2) = '09' then a.nilai else null end) as n9,
            sum(case when substring(b.periode,5,2) = '10' then a.nilai else null end) as n10,
            sum(case when substring(b.periode,5,2) = '11' then a.nilai else null end) as n11,
            sum(case when substring(b.periode,5,2) = '12' then a.nilai else null end) as n12
                        from it_aju_m a
                        inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='$kode_lokasi' and substring(b.periode,1,4)='$tahun' $filter_pp $filter_bidang
                        group by a.kode_lokasi
                        order by a.kode_lokasi
            ";
            $select2 = DB::connection($this->db)->select($sql2);
            $select2 = json_decode(json_encode($select2),true);
            $series = array();
            $i=0;
            $data = array();
            for($i=1; $i <= 12; $i++) {
                array_push($data, floatval($select[0]["n$i"]));
            }

            $data2 = array();
            for($i=1; $i <= 12; $i++) {
                array_push($data2, floatval($select2[0]["n$i"]));
            }
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'tahun_lalu' => $data,
                'tahun_ini' => $data2,
            );

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getRata2Hari(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $r->tahun;
            $tahun_seb = intval($tahun) - 1 ; 
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and a.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql="select a.kode_lokasi,
            sum(case when substring(a.periode,5,2) = '01' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end ) else 0 end) as n12
                    from it_aju_m a
                    inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                    inner join kas_m c on a.no_kas=c.no_kas and a.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi'  and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
                    group by a.kode_lokasi
                    order by a.kode_lokasi
            ";
            $select = DB::connection($this->db)->select($sql);
            $select = json_decode(json_encode($select),true);

            
            $sql="select a.kode_lokasi,
            sum(case when substring(a.periode,5,2) = '01' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when datediff(day,a.tanggal,c.tanggal) = 3 then 1 else 0 end ) else 0 end) as n12
                    from it_aju_m a
                    inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                    inner join kas_m c on a.no_kas=c.no_kas and a.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi'  and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
                    group by a.kode_lokasi
                    order by a.kode_lokasi
            ";
            $select2 = DB::connection($this->db)->select($sql);
            $select2 = json_decode(json_encode($select2),true);

            $sql="select a.kode_lokasi,
            sum(case when substring(a.periode,5,2) = '01' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when datediff(day,a.tanggal,c.tanggal) = 4 then 1 else 0 end ) else 0 end) as n12
                    from it_aju_m a
                    inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                    inner join kas_m c on a.no_kas=c.no_kas and a.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi'  and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
                    group by a.kode_lokasi
                    order by a.kode_lokasi
            ";
            $select3 = DB::connection($this->db)->select($sql);
            $select3 = json_decode(json_encode($select3),true);

            $sql="select a.kode_lokasi,
            sum(case when substring(a.periode,5,2) = '01' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when datediff(day,a.tanggal,c.tanggal) > 4 then 1 else 0 end ) else 0 end) as n12
                    from it_aju_m a
                    inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                    inner join kas_m c on a.no_kas=c.no_kas and a.kode_lokasi=c.kode_lokasi
                    where a.kode_lokasi='$kode_lokasi'  and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
                    group by a.kode_lokasi
                    order by a.kode_lokasi
            ";
            $select4 = DB::connection($this->db)->select($sql);
            $select4 = json_decode(json_encode($select4),true);

            
            $series[0] = array(
                'name' => '2 hari',
                'data' => [],
                'color' => '#FF9500'
            );
            $series[1] = array(
                'name' => '3 hari',
                'data' => [],
                'color' => '#FFCC00'
            );
            $series[2] = array(
                'name' => '4 hari',
                'data' => [],
                'color' => '#34C759'
            );
            $series[3] = array(
                'name' => '5 hari',
                'data' => [],
                'color' => '#007AFF'
            );
            $i=0;
            $data = array();
            for($i=1; $i <= 12; $i++) {
                array_push($series[0]['data'], floatval($select[0]["n$i"]));
                array_push($series[1]['data'], floatval($select2[0]["n$i"]));
                array_push($series[2]['data'], floatval($select3[0]["n$i"]));
                array_push($series[3]['data'], floatval($select4[0]["n$i"]));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['series'] = $series;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['series'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getJmlSelesai(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $tahun = $r->tahun;
            $tahun_seb = intval($tahun) - 1 ; 
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and a.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang='$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql="select a.kode_lokasi,
            count(case when substring(b.periode,5,2) = '01' then a.no_aju else null end) as n1,
            count(case when substring(b.periode,5,2) = '02' then a.no_aju else null end) as n2,
            count(case when substring(b.periode,5,2) = '03' then a.no_aju else null end) as n3,
            count(case when substring(b.periode,5,2) = '04' then a.no_aju else null end) as n4,
            count(case when substring(b.periode,5,2) = '05' then a.no_aju else null end) as n5,
            count(case when substring(b.periode,5,2) = '06' then a.no_aju else null end) as n6,
            count(case when substring(b.periode,5,2) = '07' then a.no_aju else null end) as n7,
            count(case when substring(b.periode,5,2) = '08' then a.no_aju else null end) as n8,
            count(case when substring(b.periode,5,2) = '09' then a.no_aju else null end) as n9,
            count(case when substring(b.periode,5,2) = '10' then a.no_aju else null end) as n10,
            count(case when substring(b.periode,5,2) = '11' then a.no_aju else null end) as n11,
            count(case when substring(b.periode,5,2) = '12' then a.no_aju else null end) as n12
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang 
            group by a.kode_lokasi
            order by a.kode_lokasi
            ";
            $select = DB::connection($this->db)->select($sql);
            $select = json_decode(json_encode($select),true);

            $series = array();
            $i=0;
            $data = array();
            for($i=1; $i <= 12; $i++) {
                array_push($data, floatval($select[0]["n$i"]));
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = $data;

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getBidang(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select kode_bidang,nama from agg_bidang where kode_lokasi='".$kode_lokasi."' and tahun='".date('Y')."'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = "Error ".$e;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->kode_bidang) && $request->kode_bidang != ""){
                $filter = " and kode_bidang = '$request->kode_bidang' ";
            }else{
                $filter = "";
            }

            $res = DB::connection($this->db)->select("select kode_pp,nama from agg_pp where kode_lokasi='".$kode_lokasi."' and tahun='".date('Y')."' $filter
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPPKaryawan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            if(isset($request->kode_bidang) && $request->kode_bidang != ""){
                $filter = " and a.kode_bidang = '$request->kode_bidang' ";
            }else{
                $filter = "";
            }

            if($status_admin == "A"){
                $res = DB::connection($this->db)->select("select a.kode_pp,a.nama from agg_pp a where a.kode_lokasi='".$kode_lokasi."' and a.tahun='".date('Y')."' $filter
                ");
            }else{

                $res = DB::connection($this->db)->select("select distinct a.kode_pp,a.nama from agg_pp a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik_user'
                where a.kode_lokasi='".$kode_lokasi."' and a.tahun='".date('Y')."' $filter
                ");
            }

            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getBidangKaryawan(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            if($status_admin == "A"){

                $res = DB::connection($this->db)->select("select kode_bidang,nama from agg_bidang where kode_lokasi='".$kode_lokasi."' and tahun='".date('Y')."'
                ");
            }else{

                $res = DB::connection($this->db)->select("select distinct a.kode_bidang,c.nama 
                from agg_pp a
                inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='$nik_user'
                left join agg_bidang c on a.kode_bidang=c.kode_bidang and a.kode_lokasi=c.kode_lokasi and a.tahun=c.tahun
                where a.kode_lokasi='".$kode_lokasi."' and a.tahun='".date('Y')."'
                ");
            }


            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = "Error ".$e;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getFilterDefaultDash(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $gt = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi='$kode_lokasi' ");
            $periode = count($gt) > 0 ? $gt[0]->periode : date('Ym');

            
            $gt2 = DB::connection($this->db)->select("select a.kode_pp, b.nama as nama_pp from karyawan a
            inner join agg_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.nik='$nik_user'
            where a.kode_lokasi='$kode_lokasi' and b.tahun='".substr($periode,0,4)."'");

            $kode_pp = count($gt2) > 0 ? $gt2[0]->kode_pp : '-';
            $nama_pp = count($gt2) > 0 ? $gt2[0]->nama_pp : '-';

            $gt2 = DB::connection($this->db)->select("
            select distinct b.kode_bidang, c.nama as nama_bidang from karyawan a
            inner join agg_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.nik='$nik_user'
            inner join agg_bidang c on b.kode_bidang=c.kode_bidang and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.tahun='".substr($periode,0,4)."'");
            
            $kode_bidang = count($gt2) > 0 ? $gt2[0]->kode_bidang : '-';
            $nama_bidang = count($gt2) > 0 ? $gt2[0]->nama_bidang : '-';
            
            $success['status'] = true;
            $success['periode'] = $periode;
            $success['kode_pp'] = $kode_pp;
            $success['nama_pp'] = $nama_pp;
            $success['kode_bidang'] = $kode_bidang;
            $success['nama_bidang'] = $nama_bidang;
            $success['tahun'] = substr($periode,0,4);
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
