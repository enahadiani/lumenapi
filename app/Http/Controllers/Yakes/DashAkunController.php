<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashAkunController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    public function dataBPCCtotal(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select 
                     sum(isnull(b.rea_now,0)) as rea_now
                    ,sum(isnull(c.rea_bef,0)) as rea_bef
                    ,sum(isnull(d.rka_now,0)) as rka_now

                    from dash_klp_akun a
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_now
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=c.kode_klpakun
                    
                    where a.jenis='".$request->jenis."'";

            $res = DB::connection($this->sql)->select($sql);
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

    public function dataBPCClayanan(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select a.nama
                    ,isnull(b.rea_now,0) as rea_now
                    ,isnull(c.rea_bef,0) as rea_bef
                    ,isnull(d.rka_now,0) as rka_now
                    
                    from dash_klp_akun a
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_now
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=c.kode_klpakun
                    
                    where a.jenis='".$request->jenis."'
                    order by a.idx";

            $res = DB::connection($this->sql)->select($sql);
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

    
    public function dataBebanYtd(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select a.warna,a.nama,a.idx
                    ,isnull(b.rea_now,0) as rea_now
                    ,isnull(c.rea_bef,0) as rea_bef
                    ,isnull(d.rka_now,0) as rka_now
                    
                    from dash_klp_akun a
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_now
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis = 'Beban' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='Beban' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='Beban' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=c.kode_klpakun
                    
                    where a.jenis='Beban'
                    order by a.idx";

            $res = DB::connection($this->sql)->select($sql);
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

    public function dataBPytd(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select a.warna,a.nama,a.idx
                    ,isnull(b.rea_now,0) as rea_now
                    ,isnull(c.rea_bef,0) as rea_bef
                    ,isnull(d.rka_now,0) as rka_now
                    
                    from dash_klp_akun a
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_now
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='BP' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='BP' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='BP' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=c.kode_klpakun
                    
                    where a.jenis='BP'
                    order by a.idx";

            $res = DB::connection($this->sql)->select($sql);
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

    public function dataCCytd(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2);

            $sql = "select a.warna,a.nama,a.idx
                    ,isnull(b.rea_now,0) as rea_now
                    ,isnull(c.rea_bef,0) as rea_bef
                    ,isnull(d.rka_now,0) as rka_now
                    
                    from dash_klp_akun a
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_now
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='CC' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='CC' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun
                    where a.jenis='CC' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=c.kode_klpakun
                    
                    where a.jenis='CC'
                    order by a.idx";

            $res = DB::connection($this->sql)->select($sql);
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

    public function dataBeban(Request $request) {
        $this->validate($request, [    
            'tahun' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select b.warna,b.nama,b.idx
                                                    ,sum(case substring(periode,5,2) when '01' then a.nilai else 0 end) as jan
                                                    ,sum(case substring(periode,5,2) when '02' then a.nilai else 0 end) as feb
                                                    ,sum(case substring(periode,5,2) when '03' then a.nilai else 0 end) as mar
                                                    ,sum(case substring(periode,5,2) when '04' then a.nilai else 0 end) as apr
                                                    ,sum(case substring(periode,5,2) when '05' then a.nilai else 0 end) as mei
                                                    ,sum(case substring(periode,5,2) when '06' then a.nilai else 0 end) as jun
                                                    ,sum(case substring(periode,5,2) when '07' then a.nilai else 0 end) as jul
                                                    ,sum(case substring(periode,5,2) when '08' then a.nilai else 0 end) as agu
                                                    ,sum(case substring(periode,5,2) when '09' then a.nilai else 0 end) as sep
                                                    ,sum(case substring(periode,5,2) when '10' then a.nilai else 0 end) as okt
                                                    ,sum(case substring(periode,5,2) when '11' then a.nilai else 0 end) as nov
                                                    ,sum(case substring(periode,5,2) when '12' then a.nilai else 0 end) as des
                                                    from dash_klpakun_lap a inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun
                                                    where b.jenis='Beban' and substring(a.periode,1,4) = '".$request->tahun."' 
                                                    group by b.warna,b.nama,b.idx
                                                    order by b.idx");

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

    public function dataPdpt(Request $request) {
        $this->validate($request, [    
            'tahun' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select b.warna,b.nama,b.idx
                                                    ,sum(case substring(periode,5,2) when '01' then a.nilai else 0 end) as jan
                                                    ,sum(case substring(periode,5,2) when '02' then a.nilai else 0 end) as feb
                                                    ,sum(case substring(periode,5,2) when '03' then a.nilai else 0 end) as mar
                                                    ,sum(case substring(periode,5,2) when '04' then a.nilai else 0 end) as apr
                                                    ,sum(case substring(periode,5,2) when '05' then a.nilai else 0 end) as mei
                                                    ,sum(case substring(periode,5,2) when '06' then a.nilai else 0 end) as jun
                                                    ,sum(case substring(periode,5,2) when '07' then a.nilai else 0 end) as jul
                                                    ,sum(case substring(periode,5,2) when '08' then a.nilai else 0 end) as agu
                                                    ,sum(case substring(periode,5,2) when '09' then a.nilai else 0 end) as sep
                                                    ,sum(case substring(periode,5,2) when '10' then a.nilai else 0 end) as okt
                                                    ,sum(case substring(periode,5,2) when '11' then a.nilai else 0 end) as nov
                                                    ,sum(case substring(periode,5,2) when '12' then a.nilai else 0 end) as des
                                                    from dash_klpakun_lap a inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun
                                                    where b.jenis='Pendapatan' and substring(a.periode,1,4) = '".$request->tahun."' 
                                                    group by b.warna,b.nama,b.idx
                                                    order by b.idx");

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

    


}
