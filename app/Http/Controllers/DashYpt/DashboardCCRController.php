<?php
namespace App\Http\Controllers\DashYpt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardCCRController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

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
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $bulan=substr($periode,4,2);
            $periode_awal=$tahun."01";
            $bulanSeb = intval($bulan)-1;
            if(strlen($bulanSeb) == 1){
                $bulanSeb = "0".$bulanSeb;
            }else{
                $bulanSeb = $bulanSeb;
            }
            $periode_rev=$tahun.$bulanSeb;
            $where = " and x.kode_lokasi='12' ";

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and x.kode_pp='$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }

            $sql = "select isnull(b.total,0) as tn1,isnull(c.total,0) as tn2,isnull(b.total,0)+isnull(c.total,0) as tn3,
            isnull(d.total,0) as pn1,isnull(e.total,0) as pn2,isnull(d.total,0)+isnull(e.total,0) as pn3,
            isnull(f.total,0)-isnull(g.total,0) as piutang, 
            isnull(h.total,0) as hn1,isnull(i.total,0) as hn2,isnull(h.total,0)+isnull(i.total,0) as hn3
            from dash_ypt_lokasi a
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where (x.periode between '$periode_awal' and '$periode_rev') $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                    )b on a.kode_lokasi=b.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where x.periode='$periode' $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )c on a.kode_lokasi=c.kode_lokasi
            left join (select x.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where (x.periode between '$periode_awal' and '$periode') and (x.periode_bill between '$periode_awal' and '$periode_rev') $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )d on a.kode_lokasi=d.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where x.periode='$periode' and x.periode_bill = '$periode' $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )e on a.kode_lokasi=e.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where x.periode<'$periode_awal' $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )f on a.kode_lokasi=f.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where x.periode<'$periode_awal' $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )g on a.kode_lokasi=g.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where (x.periode between '$periode_awal' and '$periode_rev') and (x.periode_bill<'$periode_awal') $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )h on a.kode_lokasi=h.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where x.periode='$periode' and (x.periode_bill<'$periode_awal') $where $filter_pp $filter_bidang
                        group by x.kode_lokasi
                        )i on a.kode_lokasi=i.kode_lokasi
            where a.kode_lokasi='12' ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            
            $ccr_total_ar = floatval($res[0]['piutang']) + floatval($res[0]['tn3']);
            $ccr_total_inflow = floatval($res[0]['pn3']) + floatval($res[0]['hn3']);
            $ccr_total = ($ccr_total_ar != 0 ? ($ccr_total_inflow / $ccr_total_ar)*100 : 0);

            $ccr_tahun_lalu_ar = floatval($res[0]['piutang']);
            $ccr_tahun_lalu_inflow = floatval($res[0]['hn3']);
            $ccr_tahun_lalu =($ccr_tahun_lalu_ar != 0 ? ($ccr_tahun_lalu_inflow/$ccr_tahun_lalu_ar)*100 : 0);

            $ccr_tahun_ini_ar = floatval($res[0]['tn3']);
            $ccr_tahun_ini_inflow = floatval($res[0]['pn3']);
            $ccr_tahun_ini =($ccr_tahun_ini_ar != 0 ? ($ccr_tahun_ini_inflow/$ccr_tahun_ini_ar)*100 : 0);

            $ccr_periode_ar = floatval($res[0]['tn2']);
            $ccr_periode_inflow = floatval($res[0]['pn2']);
            $ccr_periode =($ccr_periode_ar != 0 ? ($ccr_periode_inflow/$ccr_periode_ar)*100 : 0);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "ccr_total" => [
                    'ar' => floatval(number_format((float)$ccr_total_ar, 2,'.', '')),
                    'inflow' => floatval(number_format((float)$ccr_total_inflow, 2,'.', '')),
                    'persentase' => floatval(number_format((float)$ccr_total, 2,'.', ''))
                ],
                "ccr_tahun_lalu" => [
                    'ar' => floatval(number_format((float)$ccr_tahun_lalu_ar, 2,'.', '')),
                    'inflow' => floatval(number_format((float)$ccr_tahun_lalu_inflow, 2,'.', '')),
                    'persentase' => floatval(number_format((float)$ccr_tahun_lalu, 2,'.', ''))
                ],
                "ccr_tahun_ini" => [
                    'ar' => floatval(number_format((float)$ccr_tahun_ini_ar, 2,'.', '')),
                    'inflow' => floatval(number_format((float)$ccr_tahun_ini_inflow, 2,'.', '')),
                    'persentase' => floatval(number_format((float)$ccr_tahun_ini, 2,'.', ''))
                ],
                "ccr_periode" => [
                    'ar' => floatval(number_format((float)$ccr_periode_ar, 2,'.', '')),
                    'inflow' => floatval(number_format((float)$ccr_periode_inflow, 2,'.', '')),
                    'persentase' => floatval(number_format((float)$ccr_periode, 2,'.', ''))
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

    public function getTopCCR(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            // $col_array = array('kode_lokasi');
            // $db_col_name = array('x.kode_lokasi');
            // $where = "";
            // $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
            $periode=$r->periode[1];
            // $tahun=substr($periode,0,4);
            // $bulan=substr($periode,4,2);
            // $periode_awal=$tahun."01";
            // $bulanSeb = intval($bulan)-1;
            // if(strlen($bulanSeb) == 1){
            //     $bulanSeb = "0".$bulanSeb;
            // }else{
            //     $bulanSeb = $bulanSeb;
            // }
            // $periode_rev=$tahun.$bulanSeb;
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and bd.kode_bidang = '$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }
            $sort = $r->sort;
            $where = "where x.kode_lokasi='12' ";

            $sql = "select a.kode_pp,a.nama,
            case when isnull(c.total,0) <> 0 then (isnull(e.total,0)/isnull(c.total,0))*100 else 0 end as ccr_berjalan
            from pp a
            inner join bidang bd on a.kode_bidang=bd.kode_bidang and bd.kode_lokasi='12'
            left join (select x.kode_lokasi,x.kode_pp,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        $where and x.periode='$periode'
                        group by x.kode_lokasi,x.kode_pp
                        )c on a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            left join (select x.kode_lokasi,x.kode_pp,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        $where and x.periode='$periode' and x.periode_bill = '$periode' 
                        group by x.kode_lokasi,x.kode_pp
                        )e on a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp
            where a.kode_lokasi='12' and a.kode_bidang in ('1','2','3','4','5') $filter_bidang
            order by (case when isnull(c.total,0) <> 0 then (isnull(e.total,0)/isnull(c.total,0))*100 else 0 end) $sort ";

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

    public function getBidang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "select a.kode_bidang,a.nama
            from bidang a
            where a.kode_lokasi='12' and a.kode_bidang in ('1','2','3','4','5') ";

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

    public function getTrendCCR(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $nama = "-";
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }
            if(isset($r->kode_pp) && $r->kode_pp !=""){
                $get = DB::connection($this->db)->select("select nama from pp where kode_pp='$r->kode_pp' ");
                if(count($get) > 0){
                    $nama = $get[0]->nama;
                }
                $sql="select a.kode_lokasi,isnull(b.n1,0)-isnull(c.n1,0) as n1,isnull(b.n2,0)-isnull(c.n2,0) as n2,isnull(b.n3,0)-isnull(c.n3,0) as n3,
                isnull(b.n4,0)-isnull(c.n4,0) as n4,isnull(b.n5,0)-isnull(c.n5,0) as n5,isnull(b.n6,0)-isnull(c.n6,0) as n6,
                isnull(b.n7,0)-isnull(c.n7,0) as n7,isnull(b.n8,0)-isnull(c.n8,0) as n8,isnull(b.n9,0)-isnull(c.n9,0) as n9,
                isnull(b.n10,0)-isnull(c.n10,0) as n10,isnull(b.n11,0)-isnull(c.n11,0) as n11,isnull(b.n12,0)-isnull(c.n12,0) as n12
                from dash_ypt_lokasi a
                left join (select a.kode_lokasi,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='01' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n1,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='02' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n2,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='03' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n3,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='04' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n4,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='05' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n5,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='06' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n6,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='07' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n7,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='08' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n8,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='09' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n9,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='10' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n10,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='11' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n11,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='12' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n12  
                        from sis_bill_d a
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='12' and SUBSTRING(a.periode,1,4)='$tahun' and a.kode_pp='$r->kode_pp' $filter_bidang
                        group by a.kode_lokasi
                        ) b on a.kode_lokasi=b.kode_lokasi
                left join (select a.kode_lokasi,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='01' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n1,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='02' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n2,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='03' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n3,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='04' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n4,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='05' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n5,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='06' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n6,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='07' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n7,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='08' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n8,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='09' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n9,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='10' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n10,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='11' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n11,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='12' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n12  
                        from sis_rekon_d a
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='12' and SUBSTRING(a.periode,1,4)='$tahun' and a.kode_pp='$r->kode_pp' $filter_bidang
                        group by a.kode_lokasi
                        )c on a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='12' ";
            }else{
                $get = DB::connection($this->db)->select("select nama from dash_ypt_lokasi where kode_lokasi='12' ");
                if(count($get) > 0){
                    $nama = $get[0]->nama;
                }
                $sql="select a.kode_lokasi,isnull(b.n1,0)-isnull(c.n1,0) as n1,isnull(b.n2,0)-isnull(c.n2,0) as n2,isnull(b.n3,0)-isnull(c.n3,0) as n3,
                isnull(b.n4,0)-isnull(c.n4,0) as n4,isnull(b.n5,0)-isnull(c.n5,0) as n5,isnull(b.n6,0)-isnull(c.n6,0) as n6,
                isnull(b.n7,0)-isnull(c.n7,0) as n7,isnull(b.n8,0)-isnull(c.n8,0) as n8,isnull(b.n9,0)-isnull(c.n9,0) as n9,
                isnull(b.n10,0)-isnull(c.n10,0) as n10,isnull(b.n11,0)-isnull(c.n11,0) as n11,isnull(b.n12,0)-isnull(c.n12,0) as n12
                from dash_ypt_lokasi a
                left join (select a.kode_lokasi,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='01' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n1,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='02' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n2,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='03' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n3,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='04' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n4,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='05' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n5,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='06' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n6,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='07' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n7,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='08' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n8,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='09' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n9,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='10' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n10,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='11' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n11,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='12' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n12  
                        from sis_bill_d a
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='12' and SUBSTRING(a.periode,1,4)='$tahun' $filter_bidang
                        group by a.kode_lokasi
                        ) b on a.kode_lokasi=b.kode_lokasi
                left join (select a.kode_lokasi,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='01' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n1,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='02' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n2,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='03' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n3,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='04' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n4,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='05' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n5,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='06' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n6,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='07' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n7,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='08' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n8,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='09' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n9,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='10' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n10,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='11' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n11,
                                sum(CASE WHEN SUBSTRING(a.periode,5,2)='12' then (case when a.dc='D' then a.nilai else -a.nilai end) else 0 end) as n12  
                        from sis_rekon_d a
                        inner join pp p on a.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                        where a.kode_lokasi='12' and SUBSTRING(a.periode,1,4)='$tahun' $filter_bidang
                        group by a.kode_lokasi
                        )c on a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='12'";
            }
           
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $ctg = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGT', 'SEP', 'OKT', 'NOV', 'DES'];
            $series = array();
            $i=0;
            foreach($res as $dt) {
                $data = array(
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']), 
                floatval($dt['n5']), 
                floatval($dt['n6']), 
                floatval($dt['n7']), 
                floatval($dt['n8']), 
                floatval($dt['n9']), 
                floatval($dt['n10']), 
                floatval($dt['n11']), 
                floatval($dt['n12']));
                $i++;
            }
            
            $series[0] = array(
                'name' => 'CCR',
                'data' => $data,
                'color' => '#EEBE00'
            );
            $success['nama'] = $nama;
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'kategori' => $ctg,
                'series' => $series
            );

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getTrendSaldoPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $nama = "-";
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
            }else{
                $filter_bidang = " ";
            }
            if(isset($r->kode_pp) && $r->kode_pp !=""){
                $get = DB::connection($this->db)->select("select nama from pp where kode_pp='$r->kode_pp' ");
                if(count($get) > 0){
                    $nama = $get[0]->nama;
                }
                $sql="SELECT a.kode_lokasi,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n4 ELSE 0 END) AS n1,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n4 ELSE 0 END) AS n2,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n4 ELSE 0 END) AS n3,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n4 ELSE 0 END) AS n4,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n4 ELSE 0 END) AS n5,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n4 ELSE 0 END) AS n6,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n4 ELSE 0 END) AS n7,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n4 ELSE 0 END) AS n8,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n4 ELSE 0 END) AS n9,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n4 ELSE 0 END) AS n10,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n4 ELSE 0 END) AS n11,
                SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n4 ELSE 0 END) AS n12
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                inner join pp p on b.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                WHERE a.kode_lokasi='12' AND a.kode_fs='FS1' and b.kode_pp='$r->kode_pp' AND a.kode_grafik IN ('PI09') AND SUBSTRING(b.periode,1,4)='$tahun' $filter_bidang
                GROUP BY a.kode_lokasi
                ";
            }else{

                if($filter_bidang != " "){
                    $sql="SELECT a.kode_lokasi,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n4 ELSE 0 END) AS n1,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n4 ELSE 0 END) AS n2,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n4 ELSE 0 END) AS n3,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n4 ELSE 0 END) AS n4,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n4 ELSE 0 END) AS n5,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n4 ELSE 0 END) AS n6,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n4 ELSE 0 END) AS n7,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n4 ELSE 0 END) AS n8,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n4 ELSE 0 END) AS n9,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n4 ELSE 0 END) AS n10,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n4 ELSE 0 END) AS n11,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n4 ELSE 0 END) AS n12
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    inner join pp p on b.kode_pp=p.kode_pp and a.kode_lokasi=p.kode_lokasi
                    WHERE a.kode_lokasi='12' AND a.kode_fs='FS1' AND a.kode_grafik IN ('PI09') AND SUBSTRING(b.periode,1,4)='$tahun' $filter_bidang
                    GROUP BY a.kode_lokasi
                    ";
                }else{

                    $get = DB::connection($this->db)->select("select nama from dash_ypt_lokasi where kode_lokasi='12' ");
                    if(count($get) > 0){
                        $nama = $get[0]->nama;
                    }
                    $sql="SELECT a.kode_lokasi,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='01' THEN b.n4 ELSE 0 END) AS n1,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='02' THEN b.n4 ELSE 0 END) AS n2,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='03' THEN b.n4 ELSE 0 END) AS n3,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='04' THEN b.n4 ELSE 0 END) AS n4,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='05' THEN b.n4 ELSE 0 END) AS n5,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='06' THEN b.n4 ELSE 0 END) AS n6,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='07' THEN b.n4 ELSE 0 END) AS n7,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='08' THEN b.n4 ELSE 0 END) AS n8,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='09' THEN b.n4 ELSE 0 END) AS n9,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='10' THEN b.n4 ELSE 0 END) AS n10,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='11' THEN b.n4 ELSE 0 END) AS n11,
                    SUM(CASE WHEN SUBSTRING(b.periode,5,2)='12' THEN b.n4 ELSE 0 END) AS n12
                    FROM dash_ypt_grafik_d a
                    INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                    INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                    WHERE a.kode_lokasi='12' AND a.kode_fs='FS1'  AND a.kode_grafik IN ('PI09') AND SUBSTRING(b.periode,1,4)='$tahun'
                    GROUP BY a.kode_lokasi ";
                }
            }
           
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $ctg = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGT', 'SEP', 'OKT', 'NOV', 'DES'];
            $series = array();
            $i=0;
            foreach($res as $dt) {
                $data = array(
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']), 
                floatval($dt['n5']), 
                floatval($dt['n6']), 
                floatval($dt['n7']), 
                floatval($dt['n8']), 
                floatval($dt['n9']), 
                floatval($dt['n10']), 
                floatval($dt['n11']), 
                floatval($dt['n12']));
                $i++;
            }
            
            $series[0] = array(
                'name' => 'Saldo Piutang',
                'data' => $data,
                'color' => '#830000'
            );
            $success['nama'] = $nama;
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'kategori' => $ctg,
                'series' => $series
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