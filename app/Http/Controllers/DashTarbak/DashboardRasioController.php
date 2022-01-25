<?php

namespace App\Http\Controllers\DashTarbak;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardRasioController extends Controller
{    
    public $successStatus = 200;
    public $guard = 'tarbak';
    public $db = 'sqlsrvtarbak';

    public function getKlpRasio(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "select a.kode_rasio,a.nama from dash_ypt_rasio_m a
            where a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getLokasi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql = "select a.kode_lokasi,a.nama,a.skode from dash_ypt_lokasi a where a.kode_lokasi <> '03'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getRasioYtd(Request $request) {
        $this->validate($request,[
            'jenis_rasio' => 'required',
            'periode' => 'required',
            'lokasi' => 'required'
        ]);
        try {
           
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }
            $sql="select a.kode_rasio,a.kode_lokasi,a.kode_neraca,c.rumus, c.nama as nama_rasio,a.nama,b.periode,
            case when a.jenis='C' then isnull(b.nilai2,0)*-1 else isnull(b.nilai2,0) end  as nilai2
            from dash_ypt_rasio_d a
            inner join dash_ypt_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
            left join (select a.kode_dash as kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_neraca_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and b.periode = '$periode'
                    group by a.kode_dash,a.kode_lokasi,b.periode
                    union all
                    select a.kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_rasio_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and a.kode_rasio='$request->jenis_rasio' and b.periode = '$periode'
                    group by a.kode_neraca,a.kode_lokasi,b.periode
                    )b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1'  and a.kode_rasio='$request->jenis_rasio'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();
                $column= array();
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['periode']])){
                        $dfr[$res[$i]['periode']] = array('nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array(),'periode' => $res[$i]['periode']);
                    }
                    $dfr[$res[$i]['periode']]['par'][] = array(
                        'kode_neraca'=>$res[$i]['kode_neraca'],
                        'nama' => $res[$i]['nama'],
                        'nilai2'=>$res[$i]['nilai2']
                    );
                    
                }
                $data = array();
                $column = array();
                foreach($dfr as $d){
                    $p = '';
                    for($z=0; $z<count($d['par']); $z++){
                        if(!isset($data[$z])){
                            $data[$z] = array();
                        }
                        if(!isset($column[$z])){
                            $column[$z] = '';
                        }
                        
                        $kode_neraca= str_replace("-","",$d['par'][$z]['kode_neraca']);
                        
                        $p .= $kode_neraca." = ".$d['par'][$z]['nilai2']."<br>";
                        
                        ${"a" . $kode_neraca} = floatval($d['par'][$z]['nilai2']);
                        array_push($data[$z],floatval($d['par'][$z]['nilai2']));
                        $column[$z] = $d['par'][$z]['nama'];

                    }
                    $kode=$d['nama_rasio'];
                    try {
                        $hasil[$d['periode']]= eval('return '.$d['rumus'].';')*100;  
                    } catch (\Throwable $e) {
                        $hasil[$d['periode']]=0;
                    }
                }
                $data[count($data)] = $hasil;
                $column[count($column)] = $kode;
                $success['status'] = true;
                $success['data'] = $hasil;
                // $success['column'] = $column;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getRasioYoY(Request $request) {
        $this->validate($request,[
            'jenis_rasio' => 'required',
            'periode' => 'required',
            'lokasi' => 'required'
        ]);
        try {
           
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];
            $tahun = intval(substr($request->periode[1],0,4))-1;
            $bulan = substr($request->periode[1],4,2);
            $perAwal = $tahun.$bulan;
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }
            $sql="select a.kode_rasio,a.kode_lokasi,a.kode_neraca,c.rumus, c.nama as nama_rasio,a.nama,b.periode,
            case when a.jenis='C' then isnull(b.nilai2,0)*-1 else isnull(b.nilai2,0) end  as nilai2
            from dash_ypt_rasio_d a
            inner join dash_ypt_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
            left join (select a.kode_dash as kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_neraca_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and b.periode in ('$perAwal','$periode')
                    group by a.kode_dash,a.kode_lokasi,b.periode
                    union all
                    select a.kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_rasio_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and a.kode_rasio='$request->jenis_rasio' and b.periode in ('$perAwal','$periode')
                    group by a.kode_neraca,a.kode_lokasi,b.periode
                    )b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1'  and a.kode_rasio='$request->jenis_rasio'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();
                $column= array();
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['periode']])){
                        $dfr[$res[$i]['periode']] = array('nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array(),'periode' => $res[$i]['periode']);
                    }
                    $dfr[$res[$i]['periode']]['par'][] = array(
                        'kode_neraca'=>$res[$i]['kode_neraca'],
                        'nama' => $res[$i]['nama'],
                        'nilai2'=>$res[$i]['nilai2']
                    );
                    
                }
                $data = array();
                $column = array();
                foreach($dfr as $d){
                    $p = '';
                    for($z=0; $z<count($d['par']); $z++){
                        if(!isset($data[$z])){
                            $data[$z] = array();
                        }
                        if(!isset($column[$z])){
                            $column[$z] = '';
                        }
                        
                        $kode_neraca= str_replace("-","",$d['par'][$z]['kode_neraca']);
                        
                        $p .= $kode_neraca." = ".$d['par'][$z]['nilai2']."<br>";
                        
                        ${"a" . $kode_neraca} = floatval($d['par'][$z]['nilai2']);
                        array_push($data[$z],floatval($d['par'][$z]['nilai2']));
                        $column[$z] = $d['par'][$z]['nama'];

                    }
                    $kode=$d['nama_rasio'];
                    try {
                        $hasil[$d['periode']]= eval('return '.$d['rumus'].';')*100;  
                    } catch (\Throwable $e) {
                        $hasil[$d['periode']]=0;
                    }
                }
                $data[count($data)] = $hasil;
                $column[count($column)] = $kode;
                $kali = ($hasil[$perAwal] != 0 ?($hasil[$periode] - $hasil[$perAwal])/ $hasil[$perAwal] : 0);
                $success['status'] = true;
                $success['data'] = $hasil;
                $success['kenaikan'] = abs($kali)*100;
                $success['status_rasio'] = ($kali == 0 ? 'Tetap' : ($hasil[$perAwal] > $hasil[$periode] ? 'Turun' : 'Naik'));
                // $success['column'] = $column;
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getRasioTahun(Request $request) {
        $this->validate($request,[
            'jenis_rasio' => 'required',
            'periode' => 'required',
            'lokasi' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];
            $tahun = intval(substr($request->periode[1],0,4));
            $bulan = substr($request->periode[1],4,2);
            $ctg = array();
            $tahun = intval($tahun)-4;
            $thn = "";
            for($x=0;$x < 5;$x++){
                array_push($ctg,$tahun);
                if($x == 0){
                    $thn .= "'".$tahun.$bulan."'";
                }else{
                    
                    $thn .= ","."'".$tahun.$bulan."'";
                }
                $tahun++;
            }
            $success['ctg'] = $ctg;
            if(isset($r->jenis) && $r->jenis != ""){
                if($r->jenis == "PRD"){
                    $n4 = "n6";
                }else{
                    $n4 = "n4";
                }
            }else{
                $n4 = "n4";
            }

            $sql="select a.kode_rasio,a.kode_lokasi,a.kode_neraca,c.rumus, c.nama as nama_rasio,a.nama,b.periode,
            case when a.jenis='C' then isnull(b.nilai2,0)*-1 else isnull(b.nilai2,0) end  as nilai2
            from dash_ypt_rasio_d a
            inner join dash_ypt_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
            left join (select a.kode_dash as kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_neraca_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and b.periode in ($thn)
                    group by a.kode_dash,a.kode_lokasi,b.periode
                    union all
                    select a.kode_neraca,a.kode_lokasi,b.periode,sum(b.$n4) as nilai2
                    from dash_ypt_rasio_d a
                    inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
                    where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1' and a.kode_rasio='$request->jenis_rasio' and b.periode in ($thn)
                    group by a.kode_neraca,a.kode_lokasi,b.periode
                    )b on a.kode_neraca=b.kode_neraca and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$request->lokasi' and a.kode_fs='FS1'  and a.kode_rasio='$request->jenis_rasio'";
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();
                $column= array();
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['periode']])){
                        $dfr[$res[$i]['periode']] = array('nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array(),'periode' => $res[$i]['periode']);
                    }
                    $dfr[$res[$i]['periode']]['par'][] = array(
                        'kode_neraca'=>$res[$i]['kode_neraca'],
                        'nama' => $res[$i]['nama'],
                        'nilai2'=>$res[$i]['nilai2']
                    );
                    
                }
                $data = array();
                $column = array();
                foreach($dfr as $d){
                    $p = '';
                    for($z=0; $z<count($d['par']); $z++){
                        if(!isset($data[$z])){
                            $data[$z] = array();
                        }
                        if(!isset($column[$z])){
                            $column[$z] = '';
                        }
                        
                        $kode_neraca= str_replace("-","",$d['par'][$z]['kode_neraca']);
                        
                        $p .= $kode_neraca." = ".$d['par'][$z]['nilai2']."<br>";
                        
                        ${"a" . $kode_neraca} = floatval($d['par'][$z]['nilai2']);
                        array_push($data[$z],floatval($d['par'][$z]['nilai2']));
                        $column[$z] = $d['par'][$z]['nama'];

                    }
                    $kode=$d['nama_rasio'];
                    try {
                        $hasil[]= eval('return '.$d['rumus'].';')*100;  
                    } catch (\Throwable $e) {
                        $hasil[]=0;
                    }
                }
                $success['status'] = true;
                $success['series'] = $hasil;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['series'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['series'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

   
}
