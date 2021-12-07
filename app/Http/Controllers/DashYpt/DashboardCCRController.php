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
    public $sql = 'sqlsrvyptkug';

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
            
            $col_array = array('kode_lokasi');
            $db_col_name = array('x.kode_lokasi');
            $where = "";
            $where = $this->filterReq($r,$col_array,$db_col_name,$where,"");
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

            $sql = "select sum(isnull(b.total,0)) as tn1,sum(isnull(c.total,0)) as tn2,sum(isnull(b.total,0)+isnull(c.total,0)) as tn3,
            sum(isnull(d.total,0)) as pn1,sum(isnull(e.total,0)) as pn2,sum(isnull(d.total,0)+isnull(e.total,0)) as pn3,
            sum(isnull(f.total,0))-sum(isnull(g.total,0)) as piutang, 
            sum(isnull(h.total,0)) as hn1,sum(isnull(i.total,0)) as hn2,sum(isnull(h.total,0)+isnull(i.total,0)) as hn3
            from dash_ypt_lokasi a
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where (x.periode between '$periode_awal' and '$periode_rev') $where
                        group by x.kode_lokasi
                    )b on a.kode_lokasi=b.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where x.periode='$periode' $where
                        group by x.kode_lokasi
                        )c on a.kode_lokasi=c.kode_lokasi
            left join (select x.kode_lokasi, 
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where (x.periode between '$periode_awal' and '$periode') and (x.periode_bill between '$periode_awal' and '$periode_rev') $where
                        group by x.kode_lokasi
                        )d on a.kode_lokasi=d.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where x.periode='$periode' and x.periode_bill = '$periode' $where
                        group by x.kode_lokasi
                        )e on a.kode_lokasi=e.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_bill_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where x.periode<'$periode_awal' $where
                        group by x.kode_lokasi
                        )f on a.kode_lokasi=f.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where x.periode<'$periode_awal' $where
                        group by x.kode_lokasi
                        )g on a.kode_lokasi=g.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where (x.periode between '$periode_awal' and '$periode_rev') and (x.periode_bill<'$periode_awal') $where
                        group by x.kode_lokasi
                        )h on a.kode_lokasi=h.kode_lokasi 
            left join (select x.kode_lokasi,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total 
                        from sis_rekon_d x 
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp 
                        where x.periode='$periode' and (x.periode_bill<'$periode_awal') $where
                        group by x.kode_lokasi
                        )i on a.kode_lokasi=i.kode_lokasi ";

            $select = DB::connection($this->sql)->select($sql);
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
}
?>