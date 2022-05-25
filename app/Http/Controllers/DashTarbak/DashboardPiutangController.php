<?php
namespace App\Http\Controllers\DashTarbak;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardPiutangController extends Controller {
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
            
            $periode=$r->periode[1];
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunlalu = intval($tahun) - 1;
            $periodelalu = $tahunlalu.$bulan;
            

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }

            if(isset($r->kode_param) && $r->kode_param != ""){
                $filter_param = " and x.kode_param = '$r->kode_param' ";
            }else{
                $filter_param = "";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == '5'){
                    $filter_bidang = " and p.kode_pp='02' ";
                }else{
                    $kd_bidang = '0'.(intval($r->kode_bidang)+2);
                    $filter_bidang = " and p.kode_pp = '$kd_bidang' ";
                }
            }else{
                $filter_bidang = "";
            }

            // PIUTANG
            $sql = "select a.kode_lokasi,isnull(b.total,0)-isnull(d.total,0) as sak_total
            from lokasi a 
            left join (select y.kode_lokasi,
                                sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') $filter_pp $filter_bidang $filter_param  and p.kode_bidang in ('2','3','4','5') 
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <='$periode') $filter_pp $filter_bidang $filter_param  and p.kode_bidang in ('2','3','4','5')
                        group by y.kode_lokasi	
                        )d on a.kode_lokasi=d.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi'";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $sql2 = "select a.kode_lokasi,isnull(b.total,0)-isnull(d.total,0) as sak_total
            from lokasi a 
            left join (select y.kode_lokasi,
                                sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periodelalu') $filter_pp $filter_bidang $filter_param  and p.kode_bidang in ('2','3','4','5') 		
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <='$periodelalu') $filter_pp $filter_bidang $filter_param  and p.kode_bidang in ('2','3','4','5')
                        group by y.kode_lokasi	
                        )d on a.kode_lokasi=d.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' ";

            $select2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($select2),true);

            $piu_thn_ini = count($res) > 0 ? floatval($res[0]['sak_total']) : 0;
            $piu_thn_lalu = count($res2) > 0 ? floatval($res2[0]['sak_total']) : 0;
            $piu_yoy = ($piu_thn_lalu != 0 ? (($piu_thn_ini - $piu_thn_lalu) / $piu_thn_lalu)*100 : 0);

            // END PIUTANG

            // CADANGAN
            if((isset($r->kode_bidang) && $r->kode_bidang != "") || (isset($r->kode_pp) && $r->kode_pp != "")){
                $sql3 = "
                select a.kode_lokasi,sum(b.n4*-1) as n1
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                inner join pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.kode_grafik='PI10' and a.kode_fs='FS1' and b.periode='$periode' $filter_pp $filter_bidang
                group by a.kode_lokasi
                ";

                $sql4 = "
                select a.kode_lokasi,sum(b.n4*-1) as n1
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca_pp b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                inner join pp p on b.kode_pp=p.kode_pp and b.kode_lokasi=p.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.kode_grafik='PI10' and a.kode_fs='FS1' and b.periode='$periodelalu' $filter_pp $filter_bidang
                group by a.kode_lokasi
                ";
            }else{
                $sql3 = "
                select a.kode_lokasi,sum(b.n4*-1) as n1
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.kode_grafik='PI10' and a.kode_fs='FS1' and b.periode='$periode'
                group by a.kode_lokasi
                ";

                $sql4 = "
                select a.kode_lokasi,sum(b.n4*-1) as n1
                FROM dash_ypt_grafik_d a
                INNER JOIN exs_neraca b ON a.kode_neraca=b.kode_neraca AND a.kode_lokasi=b.kode_lokasi AND a.kode_fs=b.kode_fs
                INNER JOIN dash_ypt_grafik_m c ON a.kode_grafik=c.kode_grafik AND a.kode_lokasi=c.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.kode_grafik='PI10' and a.kode_fs='FS1' and b.periode='$periodelalu'
                group by a.kode_lokasi
                ";
            }
            $success['sql3'] = $sql3;
            $success['sql4'] = $sql4;
            $select3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($select3),true);

            $select4 = DB::connection($this->db)->select($sql4);
            $res4 = json_decode(json_encode($select4),true);

            $piu_cadang_thn_ini = count($res3) > 0 ? floatval($res3[0]['n1']) : 0;
            $piu_cadang_thn_lalu = count($res4) > 0 ? floatval($res4[0]['n1']) : 0;
            $piu_cadang_yoy = ($piu_cadang_thn_lalu != 0 ? (($piu_cadang_thn_ini - $piu_cadang_thn_lalu) / $piu_cadang_thn_lalu)*100 : 0);
            // END CADANGAN

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "piutang" => [
                    'nominal_tahun_ini' => floatval(number_format((float)$piu_thn_ini, 2,'.', '')),
                    'nominal_tahun_lalu' => floatval(number_format((float)$piu_thn_lalu, 2,'.', '')),
                    'yoy_persentase' => floatval(number_format((float)$piu_yoy, 2,'.', '')),
                ],
                "cadangan_piutang" => [
                    'nominal_tahun_ini' => floatval(number_format((float)$piu_cadang_thn_ini, 2,'.', '')),
                    'nominal_tahun_lalu' => floatval(number_format((float)$piu_cadang_thn_lalu, 2,'.', '')),
                    'yoy_persentase' => floatval(number_format((float)$piu_cadang_yoy, 2,'.', '')),
                ],
                "penghapusan_piutang" => [
                    'nominal_tahun_ini' => 0,
                    'nominal_tahun_lalu' => 0,
                    'yoy_persentase' => 0,
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

    public function getTopPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == '5'){
                    $filter_bidang = " and a.kode_pp='02' ";
                }else{
                    $kd_bidang = '0'.(intval($r->kode_bidang)+2);
                    $filter_bidang = " and a.kode_pp = '$kd_bidang' ";
                }
            }else{
                $filter_bidang = "";
            }
            $sort = $r->sort;

            if(isset($r->kode_param) && $r->kode_param != ""){
                $filter_param = " and x.kode_param = '$r->kode_param' ";
            }else{
                $filter_param = "";
            }

            $sql = "select a.kode_pp,a.nama,isnull(b.total,0)-isnull(d.total,0) as sak_total
            from pp a 
            left join (select y.kode_lokasi,y.kode_pp,
                                sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') $filter_param 
                        group by y.kode_lokasi,y.kode_pp			
                        )b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join (select y.kode_lokasi,y.kode_pp,  
                            sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                               sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                               sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                               sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <='$periode') $filter_param 
                        group by y.kode_lokasi,y.kode_pp	
                        )d on a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
            where a.kode_lokasi='$kode_lokasi' and a.kode_pp in ('02','03','04','05','06') $filter_bidang
            order by isnull(b.total,0)-isnull(d.total,0) $sort ";

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
            where a.flag_aktif='1' ";

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

    public function getKomposisiPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode=$r->periode[1];
            $nik_user=$r->nik_user;
            
            $sqlex = " exec sp_sis_saldo_pp_all '$periode','$kode_lokasi','$nik_user' ";

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
                $sqlex = " exec sp_sis_saldo '$periode','$kode_lokasi','$r->kode_pp','$nik_user' ";
            }else{
                $filter_pp = "";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == '5'){
                    $filter_bidang = " and p.kode_pp='02' ";
                    $sqlex = " exec sp_sis_saldo '$periode','$kode_lokasi','02','$nik_user' ";
                }else{
                    $kd_bidang = '0'.(intval($r->kode_bidang)+2);
                    $sqlex = " exec sp_sis_saldo '$periode','$kode_lokasi','$kd_bidang','$nik_user' ";
                    $filter_bidang = " and p.kode_pp = '$kd_bidang' ";
                }
            }else{
                $filter_bidang = "";
            }

            $ex = DB::connection($this->db)->getPdo()->exec($sqlex);
            
            $sql = "select a.kode_lokasi,
            sum(b.n16) as sak_n1,sum(b.n17) as sak_n2,sum(b.n18) as sak_n3,sum(b.n19) as sak_n4,sum(b.n20) as sak_n5
            from sis_siswa a 
            inner join sis_siswa_saldo b on a.nis=b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp and b.nik_user='$nik_user'
            group by a.kode_lokasi ";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            if(count($res) > 0){
                $item = $res[0];
                $dp = floatval($item['sak_n1']);
                if($dp < 0){
                    $value = [
                        'name' => 'DP',
                        'y' => abs($dp),
                        'sliced' =>  true,
                        'selected' => true,
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'DP'
                    ];
                }else{
                    $value = [
                        'name' => 'DP',
                        'y' => $dp,
                        'sliced' =>  true,
                        'selected' => true,
                        'negative' => false,
                        'fillColor' => '#FBBF24',                            
                        'color' => '#FBBF24',
                        'key' => 'DP'
                    ];
                }
                
                array_push($chart, $value);
                $dpp = floatval($item['sak_n2']);
                if($dpp < 0){
                    $value = [
                        'name' => 'DPP',
                        'y' => abs($dpp),
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'DPP'
                    ];
                }else{
                    $value = [
                        'name' => 'DPP',
                        'y' => $dpp,
                        'negative' => false,
                        'key' => 'DPP',
                        'fillColor' => '#008000',                            
                        'color' => '#008000',
                    ];
                }
                array_push($chart, $value);
                $dps = floatval($item['sak_n3']);
                if($dps < 0){
                    $value = [
                        'name' => 'DPS',
                        'y' => abs($dps),
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'DPS'
                    ];
                }else{
                    $value = [
                        'name' => 'DPS',
                        'y' => $dps,
                        'negative' => false,
                        'key' => 'DPS',
                        'fillColor' => '#870202',                            
                        'color' => '#870202',
                    ];
                }
                array_push($chart, $value);
                $spp = floatval($item['sak_n4']);
                if($spp < 0){
                    $value = [
                        'name' => 'SPP',
                        'y' => abs($spp),
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'SPP'
                    ];
                }else{
                    $value = [
                        'name' => 'SPP',
                        'y' => $spp,
                        'negative' => false,
                        'key' => 'SPP',
                        'fillColor' => '#0058e4',                            
                        'color' => '#0058e4',
                    ];
                }
                array_push($chart, $value);
                $denda = floatval($item['sak_n5']);
                if($denda < 0){
                    $value = [
                        'name' => 'DENDA',
                        'y' => abs($denda),
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'DENDA'
                    ];
                }else{
                    $value = [
                        'name' => 'DENDA',
                        'y' => $denda,
                        'negative' => false,
                        'key' => 'DENDA',
                        'fillColor' => '#000744',                            
                        'color' => '#000744',
                    ];
                }
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

    public function getTrendSaldoPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $kode_lokasi = '01';
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == '5'){
                    $filter_bidang = " and p.kode_pp='02' ";
                }else{
                    $kd_bidang = '0'.(intval($r->kode_bidang)+2);
                    $filter_bidang = " and p.kode_pp = '$kd_bidang' ";
                }
            }else{
                $filter_bidang = "";
            }

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = " and p.kode_pp not in ('1')";
            }

            if(isset($r->kode_param) && $r->kode_param != ""){
                $filter_param = " and x.kode_param = '$r->kode_param' ";
            }else{
                $filter_param = "";
            }

            $tahun = substr($r->query('periode')[1],0,4);
            $periode = [];
            for($i=0;$i<5;$i++) {
                if($i == 0) {
                    array_push($periode, $tahun);
                } else {
                    $tahun = $tahun - 1;
                    array_push($periode, $tahun);
                }
            }
            $periode = array_reverse($periode);
            $sql="select a.kode_lokasi,isnull(b.n1,0)-isnull(d.n1,0) as n1,isnull(b.n2,0)-isnull(d.n2,0) as n2,
            isnull(b.n3,0)-isnull(d.n3,0) as n3,isnull(b.n4,0)-isnull(d.n4,0) as n4,isnull(b.n5,0)-isnull(d.n5,0) as n5
            from lokasi a 
            left join (select y.kode_lokasi,
                            sum(case when substring(x.periode,1,4)<='".$periode[0]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1,
                            sum(case when substring(x.periode,1,4)<='".$periode[1]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n2,
                            sum(case when substring(x.periode,1,4)<='".$periode[2]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n3,
                            sum(case when substring(x.periode,1,4)<='".$periode[3]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n4,
                            sum(case when substring(x.periode,1,4)<='".$periode[4]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n5	
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_lokasi=p.kode_lokasi and x.kode_pp=p.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi') $filter_pp $filter_bidang $filter_param and p.kode_bidang in ('2','3','4','5')
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when substring(x.periode,1,4)<='".$periode[0]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1,
                            sum(case when substring(x.periode,1,4)<='".$periode[1]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n2,
                            sum(case when substring(x.periode,1,4)<='".$periode[2]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n3,
                            sum(case when substring(x.periode,1,4)<='".$periode[3]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n4,
                            sum(case when substring(x.periode,1,4)<='".$periode[4]."' then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n5				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_lokasi=p.kode_lokasi and x.kode_pp=p.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi') $filter_pp $filter_bidang $filter_param and p.kode_bidang in ('2','3','4','5')
                        group by y.kode_lokasi	
                        )d on a.kode_lokasi=d.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi'";
           
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $series = array();
            $i=0;
            foreach($res as $dt) {
                $data = array(
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']), 
                floatval($dt['n5']));
                $i++;
            }
            
            $series[0] = array(
                'name' => 'Saldo Piutang',
                'data' => $data,
                'color' => '#830000'
            );
            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = array(
                'kategori' => $periode,
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

    public function getUmurPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $nama = "-";
            $kode_lokasi = '01';
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == '5'){
                    $filter_bidang = " and p.kode_pp='02' ";
                }else{
                    $kd_bidang = '0'.(intval($r->kode_bidang)+2);
                    $filter_bidang = " and p.kode_pp = '$kd_bidang' ";
                }
            }else{
                $filter_bidang = "";
            }
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = " ";
            }
            if(isset($r->kode_param) && $r->kode_param != ""){
                $filter_param = " and x.kode_param = '$r->kode_param' ";
            }else{
                $filter_param = "";
            }
           
            $sql = "select a.nama,a.kode_lokasi,
            b.n1,b.n2,b.n3,b.n4,'Siswa Aktif' as kode
            from lokasi a
            left join (select a.kode_lokasi,
            sum(case when a.umur<=6 then a.n1 else 0 end) as n1,
            sum(case when a.umur between 7 and 12 then a.n1 else 0 end) as n2,
            sum(case when a.umur between 13 and 24 then a.n1 else 0 end) as n3,
            sum(case when a.umur>24 then a.n1 else 0 end) as n4
            from (select a.no_bill,a.kode_lokasi,a.periode,
                    datediff(month,convert(datetime, (case when substring(a.periode,5,2) > 12 then substring(a.periode,1,4)+'12' else a.periode end)+'01'),convert(datetime, '".$periode."01')) as umur,
                    isnull(a.n1,0)-isnull(b.n1,0) as n1
                    from (select x.no_bill,x.kode_lokasi,x.periode,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_bill_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif='1' $filter_bidang $filter_pp $filter_param	
                            group by x.no_bill,x.kode_lokasi,x.periode,x.kode_pp	
                            )a
                    left join (select x.no_bill,x.kode_lokasi,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_rekon_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif='1' $filter_bidang $filter_pp $filter_param
                            group by x.no_bill,x.kode_lokasi,x.kode_pp
                    )b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi = '$kode_lokasi' 
                )a
                group by a.kode_lokasi
            )b on a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' 
            union all
            select a.nama,a.kode_lokasi,
            b.n1,b.n2,b.n3,b.n4,'Siswa Non Aktif' as kode
            from lokasi a
            left join (select a.kode_lokasi,
            sum(case when a.umur<=6 then a.n1 else 0 end) as n1,
            sum(case when a.umur between 7 and 12 then a.n1 else 0 end) as n2,
            sum(case when a.umur between 13 and 24 then a.n1 else 0 end) as n3,
            sum(case when a.umur>24 then a.n1 else 0 end) as n4
            from (select a.no_bill,a.kode_lokasi,a.periode,
            datediff(month,convert(datetime, (case when substring(a.periode,5,2) > 12 then substring(a.periode,1,4)+'12' else a.periode end)+'01'),convert(datetime, '".$periode."01')) as umur,
            isnull(a.n1,0)-isnull(b.n1,0) as n1
                    from (select x.no_bill,x.kode_lokasi,x.periode,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_bill_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif <> '1' $filter_bidang $filter_pp $filter_param	
                            group by x.no_bill,x.kode_lokasi,x.periode,x.kode_pp	
                            )a
                    left join (select x.no_bill,x.kode_lokasi,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_rekon_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif <> '1' $filter_bidang $filter_pp $filter_param
                            group by x.no_bill,x.kode_lokasi,x.kode_pp
                    )b on a.no_bill=b.no_bill and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
                    where a.kode_lokasi = '$kode_lokasi' 
                )a
                group by a.kode_lokasi
            )b on a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' 
            ";
            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $ctg = ['0-6 bln','7-12 bln','13-24 bln','>24 bln'];
            $series = array();
            $i=0;
            $colors = ['#064E3B','#FBBF24'];
            foreach($res as $dt) {
                if(!isset($series[$i])){
                    $series[$i] = array(
                        'name' => $dt['kode'],
                        'data' => [],
                        'color' => $colors[$i]
                    );
                }
                $data = array(
                floatval($dt['n1']), 
                floatval($dt['n2']), 
                floatval($dt['n3']), 
                floatval($dt['n4']));
                $series[$i]['data'] = $data;
                $i++;
            }
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