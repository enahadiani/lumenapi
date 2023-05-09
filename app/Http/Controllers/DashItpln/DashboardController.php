<?php

namespace App\Http\Controllers\DashItpln;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200; 
    public $db = 'dbpln';
    public $guard = 'itpln';

    public function getPeriode()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select periode,dbo.fnNamaBulan(periode) as nama 
            from periode
            where kode_lokasi=? and substring(periode,5,2) not in ('13','14','15','16')
            order by periode desc
            ",[$kode_lokasi]);
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

    public function getLabaRugiBox(Request $r)
    {
        $this->validate($r,[
            'periode' => 'required|max:6|alpha_num'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select sum(case when a.kode_grafik = 'DB02' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as pdpt,
                sum(case when a.kode_grafik = 'DB03' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as beban,
                sum(case when a.kode_grafik = 'DB04' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as labarugi
            from db_grafik_d a
            inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_grafik in ('DB02','DB03','DB04') and b.periode=? and a.kode_lokasi=?

            ",[$r->input('periode'),$kode_lokasi]);

            $tahun = substr($r->input('periode'),0,4);
            $tahunlalu = intval($tahun) - 1;
            $periodelalu = $tahunlalu.substr($r->input('periode'),4,2);
            $res2 = DB::connection($this->db)->select("
            select sum(case when a.kode_grafik = 'DB02' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as pdpt,
                   sum(case when a.kode_grafik = 'DB03' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as beban,
                   sum(case when a.kode_grafik = 'DB04' then (case when b.jenis_akun ='Pendapatan' then -b.n4 else b.n4 end) else 0 end) as labarugi
            from db_grafik_d a
            inner join exs_neraca b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            where a.kode_grafik in ('DB02','DB03','DB04') and b.periode=? and a.kode_lokasi=?

            ",[$periodelalu,$kode_lokasi]);

            $pdpt = (count($res) > 0) ? floatval($res[0]->pdpt) : 0;
            $beban = (count($res) > 0) ? floatval($res[0]->beban) : 0;
            $labarugi = (count($res) > 0) ? floatval($res[0]->labarugi) : 0;

            $pdptlalu = (count($res2) > 0) ? floatval($res2[0]->pdpt) : 0;
            $bebanlalu = (count($res2) > 0) ? floatval($res2[0]->beban) : 0;
            $labarugilalu = (count($res2) > 0) ? floatval($res2[0]->labarugi) : 0;

            $pdptyoy = ($pdptlalu <> 0) ? (($pdpt - $pdptlalu) / $pdpt) * 100 : 0;
            $bebanyoy = ($bebanlalu <> 0) ? (($beban - $bebanlalu) / $beban) * 100 : 0;
            $labarugiyoy = ($labarugilalu <> 0) ? (($labarugi - $labarugilalu) / $labarugi) * 100 : 0;
            
            $success['status'] = true;
            $success['data'] = [
                'pdpt' => $pdpt,
                'yoy_pdpt' => $pdptyoy,
                'beban' => $beban,
                'yoy_beban' => $bebanyoy,
                'labarugi' => $labarugi,
                'yoy_labarugi' => $labarugiyoy
            ];
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [
                'pdpt' => 0,
                'yoy_pdpt' => 0,
                'beban' => 0,
                'yoy_beban' => 0,
                'labarugi' => 0,
                'yoy_labarugi' => 0
            ];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRasioBox(Request $r)
    {
        $this->validate($r,[
            'periode' => 'required|max:6|alpha_num'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_rasio,a.kode_neraca,a.rumus,a.nama_rasio,a.nama, isnull(b.nilai,0) as nilai
            from (
                    select a.kode_rasio, a.kode_lokasi, a.nama as nama_rasio, b.kode_neraca, c.nama, a.rumus, b.no
                        from db_rasio_m a
                        inner join db_rasio_d b on a.kode_rasio=b.kode_rasio and a.kode_lokasi=b.kode_lokasi
                        inner join neraca c on b.kode_neraca=c.kode_neraca and b.kode_lokasi=c.kode_lokasi 
                        and a.kode_fs=c.kode_fs
                        where a.kode_lokasi=? and c.kode_fs='FS1'
                        group by a.kode_rasio, a.kode_lokasi, a.nama, b.kode_neraca, c.nama, a.rumus, b.no
                ) a
            left join (
                    select a.kode_rasio,a.kode_neraca,a.kode_lokasi,sum(case when b.jenis_akun ='Pendapatan' then -b.n4 else (case when b.modul='P' then -b.n4 else b.n4 end) end) as nilai
                    from db_rasio_d a
                    inner join db_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and c.kode_fs=b.kode_fs
                    where a.kode_lokasi=? and b.periode=? and c.kode_fs='FS1' 
                    group by a.kode_rasio,a.kode_neraca,a.kode_lokasi
            )b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_rasio=b.kode_rasio
            order by a.kode_rasio,a.no
                
            ",[$kode_lokasi,$kode_lokasi,$r->input('periode')]);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();
                $column= array();
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['kode_rasio']])){
                        $dfr[$res[$i]['kode_rasio']] = array('kode_rasio' => $res[$i]['kode_rasio'], 'nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array(), 'hasil' => 0);
                    }
                    $dfr[$res[$i]['kode_rasio']]['par'][] = array(
                        'kode_neraca'=>$res[$i]['kode_neraca'],
                        'nama' => $res[$i]['nama_rasio'],
                        'nilai'=>$res[$i]['nilai']
                    );
                    
                }

                foreach($dfr as $d){
                    $p = '';
                    for($z=0; $z<count($d['par']); $z++){
                        $kode_neraca= str_replace("-","",$d['par'][$z]['kode_neraca']);
                        ${"a" . $kode_neraca} = floatval($d['par'][$z]['nilai']);

                    }
                    $kode=$d['nama_rasio'];
                    $kode_rasio=$d['kode_rasio'];
                    try {
                        $hasil = eval('return '.$d['rumus'].';');
                        $dfr[$kode_rasio]['hasil'] = $hasil;  
                    } catch (\Throwable $e) {
                        $hasil = 0;
                        $dfr[$kode_rasio]['hasil'] = $hasil;
                    }
                }

                $success['status'] = true;
                $success['data'] = $dfr;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getLabaRugiChart(Request $r)
    {
        $this->validate($r,[
            'periode' => 'required|max:6|alpha_num'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_neraca,c.nama,case when c.kode_grafik = 'DB04' then 'spline' else 'column' end as tipe,case when c.kode_grafik = 'DB04' then 1 else 0 end as yAxis,
            sum(case when substring(a.periode,5,2) = '01' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n1,
            sum(case when substring(a.periode,5,2) = '02' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n2,
            sum(case when substring(a.periode,5,2) = '03' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n3,
            sum(case when substring(a.periode,5,2) = '04' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n4,
            sum(case when substring(a.periode,5,2) = '05' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n5,
            sum(case when substring(a.periode,5,2) = '06' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n6,
            sum(case when substring(a.periode,5,2) = '07' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n7,
            sum(case when substring(a.periode,5,2) = '08' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n8,
            sum(case when substring(a.periode,5,2) = '09' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n9,
            sum(case when substring(a.periode,5,2) = '10' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n10,
            sum(case when substring(a.periode,5,2) = '11' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n11,
            sum(case when substring(a.periode,5,2) = '12' then (case when a.jenis_akun='Pendapatan' then -a.n4 else a.n4 end) else 0 end) as n12
            from exs_neraca a
            inner join db_grafik_d b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            inner join db_grafik_m c on b.kode_grafik=c.kode_grafik and b.kode_lokasi=c.kode_lokasi 
            where a.kode_lokasi = ? and substring(a.periode,1,4) = ? and c.kode_grafik in ('DB02','DB03','DB04')
            group by a.kode_neraca,c.nama,case when c.kode_grafik = 'DB04' then 'spline' else 'column' end,case when c.kode_grafik = 'DB04' then 1 else 0 end
            order by a.kode_neraca", [$kode_lokasi, substr($r->input('periode'),0,4)]);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $color = ['#0058E4','#9FC4FF','#FFB703'];
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$i])){
                        $dfr[$i] = array('name' => $res[$i]['nama'], 'data'=>array(), 'color' => $color[$i], 'type' => $res[$i]['tipe'], 'yAxis' => intval($res[$i]['yAxis']));
                    }
                    for($x=1;$x<=12;$x++){
                        $dfr[$i]['data'][] = array("y"=>floatval($res[$i]["n".$x]));   
                    }
                }
                $success['status'] = true;
                $success['data'] = $dfr;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getArusKasChart(Request $r)
    {
        $this->validate($r,[
            'periode' => 'required|max:6|alpha_num'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select 'Uang Masuk' as nama,'column' as tipe,0 as yAxis,
                sum(case when substring(c.periode,5,2)='01' then c.debet else 0 end) as n1,
                sum(case when substring(c.periode,5,2)='02' then c.debet else 0 end) as n2,
                sum(case when substring(c.periode,5,2)='03' then c.debet else 0 end) as n3,
                sum(case when substring(c.periode,5,2)='04' then c.debet else 0 end) as n4,
                sum(case when substring(c.periode,5,2)='05' then c.debet else 0 end) as n5,
                sum(case when substring(c.periode,5,2)='06' then c.debet else 0 end) as n6,
                sum(case when substring(c.periode,5,2)='07' then c.debet else 0 end) as n7,
                sum(case when substring(c.periode,5,2)='08' then c.debet else 0 end) as n8,
                sum(case when substring(c.periode,5,2)='09' then c.debet else 0 end) as n9,
                sum(case when substring(c.periode,5,2)='10' then c.debet else 0 end) as n10,
                sum(case when substring(c.periode,5,2)='11' then c.debet else 0 end) as n11,
                sum(case when substring(c.periode,5,2)='12' then c.debet else 0 end) as n12
            from db_grafik_d a 
            inner join relakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            inner join exs_glma c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            where a.kode_grafik='DB01' and substring(c.periode,1,4)=? and a.kode_lokasi=? 
            union all
            select 'Uang Keluar' as nama,'column' as tipe,0 as yAxis,
                sum(case when substring(c.periode,5,2)='01' then c.kredit else 0 end) as n1,
                sum(case when substring(c.periode,5,2)='02' then c.kredit else 0 end) as n2,
                sum(case when substring(c.periode,5,2)='03' then c.kredit else 0 end) as n3,
                sum(case when substring(c.periode,5,2)='04' then c.kredit else 0 end) as n4,
                sum(case when substring(c.periode,5,2)='05' then c.kredit else 0 end) as n5,
                sum(case when substring(c.periode,5,2)='06' then c.kredit else 0 end) as n6,
                sum(case when substring(c.periode,5,2)='07' then c.kredit else 0 end) as n7,
                sum(case when substring(c.periode,5,2)='08' then c.kredit else 0 end) as n8,
                sum(case when substring(c.periode,5,2)='09' then c.kredit else 0 end) as n9,
                sum(case when substring(c.periode,5,2)='10' then c.kredit else 0 end) as n10,
                sum(case when substring(c.periode,5,2)='11' then c.kredit else 0 end) as n11,
                sum(case when substring(c.periode,5,2)='12' then c.kredit else 0 end) as n12
            from db_grafik_d a 
            inner join relakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            inner join exs_glma c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            where a.kode_grafik='DB01' and substring(c.periode,1,4)=? and a.kode_lokasi=? 
            union all
            select 'Saldo Uang', 'spline' as tipe,1 as yAxis,
                sum(case when substring(c.periode,5,2)='01' then c.so_akhir else 0 end) as n1,
                sum(case when substring(c.periode,5,2)='02' then c.so_akhir else 0 end) as n2,
                sum(case when substring(c.periode,5,2)='03' then c.so_akhir else 0 end) as n3,
                sum(case when substring(c.periode,5,2)='04' then c.so_akhir else 0 end) as n4,
                sum(case when substring(c.periode,5,2)='05' then c.so_akhir else 0 end) as n5,
                sum(case when substring(c.periode,5,2)='06' then c.so_akhir else 0 end) as n6,
                sum(case when substring(c.periode,5,2)='07' then c.so_akhir else 0 end) as n7,
                sum(case when substring(c.periode,5,2)='08' then c.so_akhir else 0 end) as n8,
                sum(case when substring(c.periode,5,2)='09' then c.so_akhir else 0 end) as n9,
                sum(case when substring(c.periode,5,2)='10' then c.so_akhir else 0 end) as n10,
                sum(case when substring(c.periode,5,2)='11' then c.so_akhir else 0 end) as n11,
                sum(case when substring(c.periode,5,2)='12' then c.so_akhir else 0 end) as n12
            from db_grafik_d a 
            inner join relakun b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi and a.kode_fs=b.kode_fs
            inner join exs_glma c on b.kode_akun=c.kode_akun and b.kode_lokasi=c.kode_lokasi
            where a.kode_grafik='DB01' and substring(c.periode,1,4)=? and a.kode_lokasi=? ", 
            [substr($r->input('periode'),0,4), $kode_lokasi, substr($r->input('periode'),0,4), $kode_lokasi,substr($r->input('periode'),0,4), $kode_lokasi]);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                
                $color = ['#0058E4','#9FC4FF','#FFB703'];
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$i])){
                        $dfr[$i] = array('name' => $res[$i]['nama'], 'data'=>array(), 'color' => $color[$i], 'type' => $res[$i]['tipe'], 'yAxis' => intval($res[$i]['yAxis']));
                    }
                    for($x=1;$x<=12;$x++){
                        $dfr[$i]['data'][] = array("y"=>floatval($res[$i]["n".$x]));   
                    }
                }
                $success['status'] = true;
                $success['data'] = $dfr;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
		 

}
