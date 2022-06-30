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
            $periode_seb = $tahun_seb.$bulan;

            //PENDAPATAN
            $sql = "select isnull(sum(nilai),0) as real
            from ds_real 
            where periode='$periode' and kode_neraca='41'";
            $q = DB::connection($this->db)->select($sql);
            $pend_real = (count($q) > 0 ? round($q[0]->real) : 0);

            $sql = "select isnull(sum(nilai),0) as yoy
            from ds_real 
            where periode='$periode_seb' and kode_neraca='41'";
            $q = DB::connection($this->db)->select($sql);
            $pend_yoy = (count($q) > 0 ? round($q[0]->yoy) : 0);

            $sql = "select isnull(sum(rka),0) as rka
            from ds_rka 
            where periode='$periode' and kode_neraca='41'";
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

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as nilai
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.kode_neraca='41' and d.periode='$periode' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
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

    public function getYTDvsYoY(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            
            $periode = $r->periode;

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as ytd,sum(case when d.jenis_akun ='Pendapatan' then -d.n5 else d.n5 end) as yoy
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.kode_neraca='41' and d.periode='$periode' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
            group by a.kode_klp,b.nama";

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

            $sql="select d.kode_klp,d.nama,
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
						inner join relakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
                        inner join exs_klp_akun c on b.kode_akun=c.kode_akun
                        inner join exs_klp d on d.kode_klp=c.kode_klp 
                        where a.kode_lokasi='$kode_lokasi' and substring(a.periode,1,4)='$tahun' and c.status='Aktif' and a.kode_neraca='41' and a.kode_fs='FS1'
                        group by d.kode_klp,d.nama
                        order by d.kode_klp,d.nama
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

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as real,sum(case when d.jenis_akun ='Pendapatan' then -d.n2 else d.n2 end) as rka
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.kode_neraca='41' and d.periode='$periode' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
            group by a.kode_klp,b.nama";

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

            $sql = "
            select a.kode_klp,b.nama, sum(case when d.jenis_akun ='Pendapatan' then -d.n4 else d.n4 end) as real,sum(case when d.jenis_akun ='Pendapatan' then -d.n2 else d.n2 end) as rka
            from exs_klp_akun a
            inner join exs_klp b on a.kode_klp=b.kode_klp and b.kode_lokasi='$kode_lokasi'
            inner join relakun c on a.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            inner join exs_neraca d on c.kode_neraca=d.kode_neraca and c.kode_lokasi=d.kode_lokasi and c.kode_fs=d.kode_fs
            where d.kode_neraca='41' and substring(d.periode,1,4) = '".$tahun."' and d.kode_lokasi='$kode_lokasi' and a.status='Aktif' and c.kode_fs='FS1'
            group by a.kode_klp,b.nama";

            $select2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($select2),true);
            $real_fy = []; $rka_fy = []; 
            if(count($res2) > 0){
                foreach($res2 as $row){
                    array_push($rka_fy,floatval($row['rka']));
                    array_push($real_fy,floatval($row['real']));
                }
            }

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['kategori'] = $kategori;
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
