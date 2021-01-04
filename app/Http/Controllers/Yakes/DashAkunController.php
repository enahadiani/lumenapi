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

    public function dataKunjLayanan(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->jenis == 'CC') $jenis = "PENSIUN"; 
            else $jenis = "PEGAWAI"; 
            
            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and kode_lokasi like '%' ";
            else $filterLokasi = " and kode_lokasi = '".$request->kode_pp."' ";

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select a.kode_biaya,a.nama, isnull(c.jumlah,0) as jum_seb, isnull(b.jumlah,0) as jum_now,isnull(b.rka_now,0) as rka_now
                    from yk_bpjs_biaya a 
                    left join 
                    (
                        select kode_biaya,sum(jumlah) as jumlah, sum(rka_kunj) as rka_now                    
                        from dash_kunj                     
                        where jenis ='".$jenis."' and periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterLokasi."
                        group by kode_biaya
                    ) b on a.kode_biaya=b.kode_biaya
                    
                    left join 
                    (
                        select kode_biaya,sum(jumlah) as jumlah                    
                        from dash_kunj                                             
                        where jenis ='".$jenis."' and periode between '".substr($perBef,0,4)."01' and '".$perBef."' ".$filterLokasi."
                        group by kode_biaya
                    ) c on a.kode_biaya=c.kode_biaya ";

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

    public function dataKunjTotal(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->jenis == 'CC') $jenis = "PENSIUN"; 
            else $jenis = "PEGAWAI"; 
            
            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and kode_lokasi like '%' ";
            else $filterLokasi = " and kode_lokasi = '".$request->kode_pp."' ";

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select sum(jumlah) as jum_now, sum(rka_kunj) as rka_now
                    from dash_kunj 
                    where jenis ='".$jenis."' and periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterLokasi;

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2 = "select sum(jumlah) as jum_bef
                    from dash_kunj
                    where jenis ='".$jenis."' and periode between '".substr($perBef,0,4)."01' and '".$perBef."' ".$filterLokasi;

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data2'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function dataClaimant(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if ($request->jenis == 'CC') $jenis = "PENSIUN"; 
            else $jenis = "PEGAWAI"; 
            
            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and kode_lokasi like '%' ";
            else $filterLokasi = " and kode_lokasi = '".$request->kode_pp."' ";

            $tahunBef = intval(substr($request->periode,0,4));
            $tahunBef = $tahunBef - 1;
            $tahunBef = strval($tahunBef);
            $perBef = $tahunBef.substr($request->periode,4,2); 

            $sql = "select sum(case when jenis = 'PENSIUN' then (2 * kk)+jd else kk+pas+anak end) as jum_now, sum(rka_claim) as rka_now
                    from dash_peserta 
                    where jenis ='".$jenis."' and periode = '".$request->periode."' ".$filterLokasi;

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $sql2 = "select sum(case when jenis = 'PENSIUN' then (2 * kk)+jd else kk+pas+anak end) as jum_bef
                    from dash_peserta 
                    where jenis ='".$jenis."' and periode = '".$perBef."' ".$filterLokasi;

            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data2'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function dataBPCCtotal(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required',
            'kode_pp' => 'required'           
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and b.kode_pp like '%' ";
            else $filterLokasi = " and b.kode_pp = '".$request->kode_pp."' ";

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
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=d.kode_klpakun
                    
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
            'jenis' => 'required',       
            'kode_pp' => 'required'        
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and b.kode_pp like '%' ";
            else $filterLokasi = " and b.kode_pp = '".$request->kode_pp."' ";

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
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='".$request->jenis."' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=d.kode_klpakun
                    
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
            'periode' => 'required',
            'kode_pp' => 'required'          

        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') {
                $filterPP = " and b.kode_pp like '%' ";
                $filterLokasi = " ";
            }
            else {
                $filterPP = " and b.kode_pp = '".$request->kode_pp."' ";
                $filterLokasi = " and substring(b.kode_pp,1,2) = '".substr($request->kode_pp,2,2)."' ";
                if (substr($request->kode_pp,2,2) == '00') $filterLokasi = " and substring(b.kode_pp,1,2) = '99' ";                
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
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis = 'Beban' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis='Beban' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='Beban' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=d.kode_klpakun
                    
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
            'periode' => 'required',
            'kode_pp' => 'required'               
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') {
                $filterPP = " and b.kode_pp like '%' ";
                $filterLokasi = " ";
            }
            else {
                $filterPP = " and b.kode_pp = '".$request->kode_pp."' ";
                $filterLokasi = " and substring(b.kode_pp,1,2) = '".substr($request->kode_pp,2,2)."' ";
                if (substr($request->kode_pp,2,2) == '00') $filterLokasi = " and substring(b.kode_pp,1,2) = '99' ";                
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
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis='BP' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis='BP' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='BP' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=d.kode_klpakun
                    
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
            'periode' => 'required',
            'kode_pp' => 'required'         
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') {
                $filterPP = " and b.kode_pp like '%' ";
                $filterLokasi = " ";
            }
            else {
                $filterPP = " and b.kode_pp = '".$request->kode_pp."' ";
                $filterLokasi = " and substring(b.kode_pp,1,2) = '".substr($request->kode_pp,2,2)."' ";
                if (substr($request->kode_pp,2,2) == '00') $filterLokasi = " and substring(b.kode_pp,1,2) = '99' ";                
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
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis='CC' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) b  on a.kode_klpakun=b.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rea_bef
                    from dash_klp_akun a inner join dash_klpakun_lap b on a.kode_klpakun=b.kode_klpakun ".$filterPP."
                    where a.jenis='CC' and b.periode between '".$tahunBef."01' and '".$perBef."'
                    group by a.kode_klpakun
                    ) c  on a.kode_klpakun=c.kode_klpakun
                    
                    left join (
                    select a.kode_klpakun,sum(b.nilai) as rka_now
                    from dash_klp_akun a inner join dash_gar_lap b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
                    where a.jenis='CC' and b.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."'
                    group by a.kode_klpakun
                    ) d  on a.kode_klpakun=d.kode_klpakun
                    
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
            'tahun' => 'required',
            'kode_pp' => 'required'
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and a.kode_pp like '%' ";
            else $filterLokasi = " and a.kode_pp = '".$request->kode_pp."' ";

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
                                                    from dash_klpakun_lap a inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
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
            'tahun' => 'required',
            'kode_pp' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and a.kode_pp like '%' ";
            else $filterLokasi = " and a.kode_pp = '".$request->kode_pp."' ";

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
                                                    from dash_klpakun_lap a inner join dash_klp_akun b on a.kode_klpakun=b.kode_klpakun ".$filterLokasi."
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
