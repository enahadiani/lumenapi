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

            $sql = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join ver_m d on a.no_ver=d.no_ver and a.kode_lokasi=d.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select = DB::connection($this->db)->select($sql);

            $sql2 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join fiat_m b on a.no_fiat=b.no_fiat and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select2 = DB::connection($this->db)->select($sql2);

            $sql3 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join it_spb_m b on a.no_spb=b.no_spb and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select3 = DB::connection($this->db)->select($sql3);

            $sql4 = "select a.kode_lokasi,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi";
            $select4 = DB::connection($this->db)->select($sql4);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                'ver_dok' => [
                    'nilai' => count($select) > 0 ? $select[0]->nilai : 0,
                    'jml' => count($select) > 0 ? $select[0]->jml : 0,
                ],
                'ver_akun' => [
                    'nilai' => count($select2) > 0 ? $select2[0]->nilai : 0,
                    'jml' => count($select2) > 0 ? $select2[0]->jml : 0,
                ],
                'spb' => [
                    'nilai' => count($select3) > 0 ? $select3[0]->nilai : 0,
                    'jml' => count($select3) > 0 ? $select3[0]->jml : 0,
                ],
                'spb_bayar' => [
                    'nilai' => count($select4) > 0 ? $select4[0]->nilai : 0,
                    'jml' => count($select4) > 0 ? $select4[0]->jml : 0,
                ],
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

            $sql="select a.kode_lokasi,b.periode,substring(dbo.fnNamaBulan(b.periode),1,3) as nama,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(b.periode,1,4)='$tahun_seb' $filter_pp $filter_bidang
            group by a.kode_lokasi,b.periode
            order by a.kode_lokasi,b.periode
            ";
            $select = DB::connection($this->db)->select($sql);

            $sql2="select a.kode_lokasi,b.periode,substring(dbo.fnNamaBulan(b.periode),1,3) as nama,sum(a.nilai) as nilai,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(b.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi,b.periode
            order by a.kode_lokasi,b.periode
            ";
            $select2 = DB::connection($this->db)->select($sql2);
            $series = array();
            $i=0;
            $data = array();
            foreach($select as $dt) {
                $value = [
                    'name' => $dt->nama,
                    'y' => abs($dt->nilai),
                    'key' => $dt->periode
                ];
                array_push($data, $value);
                $i++;
            }

            $data2 = array();
            foreach($select2 as $dt2) {
                $value = [
                    'name' => $dt2->nama,
                    'y' => abs($dt2->nilai),
                    'key' => $dt2->periode
                ];
                array_push($data2, $value);
                $i++;
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

            $sql="select a.kode_lokasi,a.periode,sum(case when datediff(day,a.tanggal,c.tanggal)<= 2 then 1 else 0 end) as n1,
            sum(case when datediff(day,a.tanggal,c.tanggal)=3 then 1 else 0 end) as n2,
            sum(case when datediff(day,a.tanggal,c.tanggal)=4 then 1 else 0 end) as n3,
            sum(case when datediff(day,a.tanggal,c.tanggal)>4 then 1 else 0 end) as n4
            from it_aju_m a
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            inner join kas_m c on a.no_kas=c.no_kas and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'  and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi,a.periode
            order by a.kode_lokasi,a.periode
            ";
            $select = DB::connection($this->db)->select($sql);

            
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
            foreach($select as $dt) {
                array_push($series[0]['data'], floatval($dt->n1));
                array_push($series[1]['data'], floatval($dt->n2));
                array_push($series[2]['data'], floatval($dt->n3));
                array_push($series[3]['data'], floatval($dt->n4));
                $i++;
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

            $sql="select a.kode_lokasi,b.periode,count(a.no_aju) as jml
            from it_aju_m a
            inner join kas_m b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi
            inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' $filter_pp $filter_bidang
            group by a.kode_lokasi,b.periode
            order by a.kode_lokasi,b.periode
            ";
            $select = DB::connection($this->db)->select($sql);

            $series = array();
            $i=0;
            $data = array();
            foreach($select as $dt) {
                array_push($data, floatval($dt->jml));
                $i++;
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

            $res = DB::connection($this->db)->select("select kode_bidang,nama from bidang where kode_lokasi='".$kode_lokasi."'
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

            $res = DB::connection($this->db)->select("select kode_pp,nama from pp where kode_lokasi='".$kode_lokasi."' $filter
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

}
