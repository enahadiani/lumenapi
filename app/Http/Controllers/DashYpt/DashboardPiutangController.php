<?php
namespace App\Http\Controllers\DashYpt;

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
            $tahun = substr($periode,0,4);
            $bulan = substr($periode,4,2);
            $tahunlalu = intval($tahun) - 1;
            $periodelalu = $tahunlalu.$bulan;
            $kode_lokasi = "12";

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " and p.kode_bidang not in ('1') ";
            }

            $sql = "select a.kode_lokasi,a.nama,isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total
            from lokasi a 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') $filter_bidang $filter_pp
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') $filter_bidang $filter_pp
                        group by y.kode_lokasi			
                        )c on a.kode_lokasi=c.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode') $filter_bidang $filter_pp
                        group by y.kode_lokasi		
                        )d on a.kode_lokasi=d.kode_lokasi 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') $filter_bidang $filter_pp
                        group by y.kode_lokasi		
                        )e on a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi'
            order by a.kode_lokasi";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);

            $sql2 = "select a.kode_lokasi,a.nama,isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total
            from lokasi a 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periodelalu') $filter_bidang $filter_pp
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periodelalu') $filter_bidang $filter_pp
                        group by y.kode_lokasi			
                        )c on a.kode_lokasi=c.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periodelalu') $filter_bidang $filter_pp
                        group by y.kode_lokasi		
                        )d on a.kode_lokasi=d.kode_lokasi 
            left join (select y.kode_lokasi, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periodelalu') $filter_bidang $filter_pp
                        group by y.kode_lokasi		
                        )e on a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi'
            order by a.kode_lokasi";

            $select2 = DB::connection($this->db)->select($sql2);
            $res2 = json_decode(json_encode($select2),true);

            $piu_thn_ini = floatval($res[0]['sak_total']);
            $piu_thn_lalu = floatval($res2[0]['sak_total']);
            $piu_yoy = ($piu_thn_lalu != 0 ? (($piu_thn_ini - $piu_thn_lalu) / $piu_thn_lalu)*100 : 0);

            $success['status'] = true;
            $success['message'] = "Success!";
            $success['data'] = [
                "piutang" => [
                    'nominal_tahun_ini' => floatval(number_format((float)$piu_thn_ini, 2,'.', '')),
                    'nominal_tahun_lalu' => floatval(number_format((float)$piu_thn_lalu, 2,'.', '')),
                    'yoy_persentase' => floatval(number_format((float)$piu_yoy, 2,'.', '')),
                ],
                "cadangan_piutang" => [
                    'nominal_tahun_ini' => 0,
                    'nominal_tahun_lalu' => 0,
                    'yoy_persentase' => 0
                ],
                "penghapusan_piutang" => [
                    'nominal_tahun_ini' => 0,
                    'nominal_tahun_lalu' => 0,
                    'yoy_persentase' => 0
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
            $kode_lokasi = "12";
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and bd.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and bd.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " and bd.kode_bidang not in ('1') ";
            }
            $sort = $r->sort;

            $sql = "select a.kode_lokasi,a.kode_pp,a.nama,isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) as sak_total
            from pp a 
            inner join bidang bd on a.kode_bidang=bd.kode_bidang and bd.kode_lokasi='$kode_lokasi'
            left join (select y.kode_lokasi,y.kode_pp, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') 
                        group by y.kode_lokasi,y.kode_pp			
                        )b on a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join (select y.kode_lokasi,y.kode_pp, 
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total		
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') 
                        group by y.kode_lokasi,y.kode_pp			
                        )c on a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            left join (select y.kode_lokasi,y.kode_pp,  
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total				
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode') 
                        group by y.kode_lokasi,y.kode_pp		
                        )d on a.kode_lokasi=d.kode_lokasi and a.kode_pp=d.kode_pp
            left join (select y.kode_lokasi,y.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') 
                        group by y.kode_lokasi,y.kode_pp		
                        )e on a.kode_lokasi=e.kode_lokasi and a.kode_pp=e.kode_pp
            where a.kode_lokasi='$kode_lokasi' $filter_bidang
            order by isnull(b.total,0)-isnull(d.total,0)+isnull(c.total,0)-isnull(e.total,0) $sort ";

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
            where a.kode_lokasi='12' and a.kode_bidang in ('2','3')
            union all
            select 'GB', 'SMA/SMK' ";

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
            $kode_lokasi = "12";

            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = "";
            }

            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " and p.kode_bidang not in ('1') ";
            }
            
            $sql = "select a.kode_lokasi
            ,isnull(b.n1,0)-isnull(d.n1,0)+isnull(c.n1,0)-isnull(e.n1,0) as sak_n1
            ,isnull(b.n2,0)-isnull(d.n2,0)+isnull(c.n2,0)-isnull(e.n2,0) as sak_n2
            ,isnull(b.n3,0)-isnull(d.n3,0)+isnull(c.n3,0)-isnull(e.n3,0) as sak_n3
            from lokasi a 
            left join (select y.kode_lokasi,
                                sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                                sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                                sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3	
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi $filter_pp $filter_bidang
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode < '$periode') 		
                        group by y.kode_lokasi			
                        )b on a.kode_lokasi=b.kode_lokasi 
            left join (select y.kode_lokasi, 
                            sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                                sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                                sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3	
                        from sis_bill_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi $filter_pp $filter_bidang
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode = '$periode') 		
                        group by y.kode_lokasi			
                        )c on a.kode_lokasi=c.kode_lokasi 
            left join (select y.kode_lokasi,  
                            sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                                sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                                sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3
                        from sis_rekon_d x 	
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi $filter_pp $filter_bidang
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode <'$periode')
                        group by y.kode_lokasi	
                        )d on a.kode_lokasi=d.kode_lokasi
            left join (select y.kode_lokasi,
                        sum(case when x.kode_param in ('DSP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end) as n1, 
                                sum(case when x.kode_param in ('SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n2, 
                                sum(case when x.kode_param not in ('DSP','SPP') then (case when x.dc='D' then x.nilai else -x.nilai end) else 0 end)  as n3,
                                sum(case when x.dc='D' then x.nilai else -x.nilai end) as total			
                        from sis_rekon_d x 			
                        inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                        inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi $filter_pp $filter_bidang
                        where(x.kode_lokasi = '$kode_lokasi')and(x.periode ='$periode') 
                        group by y.kode_lokasi	
                        )e on a.kode_lokasi=e.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi'";

            $select = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($select),true);
            $chart = [];
            if(count($res) > 0){
                $item = $res[0];
                $dsp = floatval($item['sak_n1']);
                if($dsp < 0){
                    $value = [
                        'name' => 'DSP',
                        'y' => abs($dsp),
                        'sliced' =>  true,
                        'selected' => true,
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'DSP'
                    ];
                }else{
                    $value = [
                        'name' => 'DSP',
                        'y' => $dsp,
                        'sliced' =>  true,
                        'selected' => true,
                        'negative' => false,
                        'fillColor' => '#FBBF24',                            
                        'color' => '#FBBF24',
                        'key' => 'DSP'
                    ];
                }
                
                array_push($chart, $value);
                $spp = floatval($item['sak_n2']);
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
                        'fillColor' => '#008000',                            
                        'color' => '#008000',
                    ];
                }
                array_push($chart, $value);
                $lain = floatval($item['sak_n3']);
                if($lain < 0){
                    $value = [
                        'name' => 'Lainnya',
                        'y' => abs($lain),
                        'negative' => true,
                        'fillColor' => 'url(#custom-pattern)',                            
                        'color' => 'url(#custom-pattern)',
                        'key' => 'Lainnya'
                    ];
                }else{
                    $value = [
                        'name' => 'Lainnya',
                        'y' => $lain,
                        'negative' => false,
                        'key' => 'Lainnya',
                        'fillColor' => '#870202',                            
                        'color' => '#870202',
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
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $nama = "-";
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " and p.kode_bidang not in ('1')";
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

    public function getUmurPiutang(Request $r) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $periode=$r->periode[1];
            $tahun=substr($periode,0,4);
            $nama = "-";
            $kode_lokasi = '12';
            if(isset($r->kode_bidang) && $r->kode_bidang != ""){
                if($r->kode_bidang == 'GB'){
                    $filter_bidang = " and p.kode_bidang in ('4','5') ";
                }else{
                    $filter_bidang = " and p.kode_bidang = '$r->kode_bidang' ";
                }
            }else{
                $filter_bidang = " and p.kode_bidang not in ('1')";
            }
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter_pp = " and p.kode_pp = '$r->kode_pp' ";
            }else{
                $filter_pp = " ";
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
                    datediff(month,convert(datetime, a.periode+'01'),convert(datetime, '202109'+'01')) as umur,
                    isnull(a.n1,0)-isnull(b.n1,0) as n1
                    from (select x.no_bill,x.kode_lokasi,x.periode,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_bill_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif='1' $filter_bidang $filter_pp	
                            group by x.no_bill,x.kode_lokasi,x.periode,x.kode_pp	
                            )a
                    left join (select x.no_bill,x.kode_lokasi,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_rekon_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif='1' $filter_bidang $filter_pp
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
                    datediff(month,convert(datetime, a.periode+'01'),convert(datetime, '202109'+'01')) as umur,
                    isnull(a.n1,0)-isnull(b.n1,0) as n1
                    from (select x.no_bill,x.kode_lokasi,x.periode,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_bill_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif <> '1' $filter_bidang $filter_pp	
                            group by x.no_bill,x.kode_lokasi,x.periode,x.kode_pp	
                            )a
                    left join (select x.no_bill,x.kode_lokasi,x.kode_pp,
                            sum(case when x.dc='D' then x.nilai else -x.nilai end) as n1	
                            from sis_rekon_d x 	
                            inner join sis_siswa s on x.nis=s.nis and x.kode_pp=s.kode_pp and x.kode_lokasi=s.kode_lokasi
                            inner join pp p on x.kode_pp=p.kode_pp and x.kode_lokasi=p.kode_lokasi
                            where(x.kode_lokasi = '$kode_lokasi')and(x.periode <= '$periode') and s.flag_aktif <> '1' $filter_bidang $filter_pp
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