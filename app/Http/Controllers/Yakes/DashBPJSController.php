<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashBPJSController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';



    public function dataClaimLokasi(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->jenis) == 'PEGAWAI') $filterJenis = " and a.jenis <> 'PENSIUN' ";
            else {
                if (strtoupper($request->jenis) == 'PENSIUN') $filterJenis = " and a.jenis = 'PENSIUN' ";
                else $filterJenis = " ";
            }

            $sql = " select sum(a.claim) as cl_total, 
                    sum(case when a.kode_lokasi='01' then a.claim else 0 end) as cl1, 
                    sum(case when a.kode_lokasi='02' then a.claim else 0 end) as cl2, 
                    sum(case when a.kode_lokasi='03' then a.claim else 0 end) as cl3, 
                    sum(case when a.kode_lokasi='04' then a.claim else 0 end) as cl4, 
                    sum(case when a.kode_lokasi='05' then a.claim else 0 end) as cl5, 
                    sum(case when a.kode_lokasi='06' then a.claim else 0 end) as cl6, 
                    sum(case when a.kode_lokasi='07' then a.claim else 0 end) as cl7,
                    sum(case when a.kode_lokasi='99' then a.claim else 0 end) as cl9
                    from yk_bpjs_cob a 
                    where a.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterJenis;

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

    public function dataKapitasiLokasi(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->jenis) == 'PEGAWAI') $filterJenis = " and a.jenis <> 'PENSIUN' ";
            else {
                if (strtoupper($request->jenis) == 'PENSIUN') $filterJenis = " and a.jenis = 'PENSIUN' ";
                else $filterJenis = " ";
            }

            $sql = "select 
                    sum(a.nilai) as kap_total, 
                    sum(case when a.kode_lokasi='01' then a.nilai else 0 end) as kap1, 
                    sum(case when a.kode_lokasi='02' then a.nilai else 0 end) as kap2, 
                    sum(case when a.kode_lokasi='03' then a.nilai else 0 end) as kap3, 
                    sum(case when a.kode_lokasi='04' then a.nilai else 0 end) as kap4, 
                    sum(case when a.kode_lokasi='05' then a.nilai else 0 end) as kap5, 
                    sum(case when a.kode_lokasi='06' then a.nilai else 0 end) as kap6, 
                    sum(case when a.kode_lokasi='07' then a.nilai else 0 end) as kap7,
                    sum(case when a.kode_lokasi='99' then a.nilai else 0 end) as kap9
                    from yk_bpjs_kapitasi a                     
                    where a.jenis_tpkk='TPKK' and a.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterJenis;

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

    public function dataBPCCLokasi(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if (strtoupper($request->jenis) == 'PEGAWAI') $filterJenis = " and a.jenis <> 'PENSIUN' ";
            else {
                if (strtoupper($request->jenis) == 'PENSIUN') $filterJenis = " and a.jenis = 'PENSIUN' ";
                else $filterJenis = " ";
            }

            $sql = "select 
                    sum(a.nilai) as bpcc_total, 
                    sum(case when a.kode_lokasi='01' then a.nilai else 0 end) as bpcc1, 
                    sum(case when a.kode_lokasi='02' then a.nilai else 0 end) as bpcc2, 
                    sum(case when a.kode_lokasi='03' then a.nilai else 0 end) as bpcc3, 
                    sum(case when a.kode_lokasi='04' then a.nilai else 0 end) as bpcc4, 
                    sum(case when a.kode_lokasi='05' then a.nilai else 0 end) as bpcc5, 
                    sum(case when a.kode_lokasi='06' then a.nilai else 0 end) as bpcc6, 
                    sum(case when a.kode_lokasi='07' then a.nilai else 0 end) as bpcc7,
                    sum(case when a.kode_lokasi='99' then a.nilai else 0 end) as bpcc9
                    from yk_bpjs_bpcc a                     
                    where a.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterJenis;

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

    public function dataPremiLokasi(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',            
            'jenis' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->jenis) == 'PEGAWAI') $filterJenis = " and a.jenis <> 'PENSIUN' ";
            else {
                if (strtoupper($request->jenis) == 'PENSIUN') $filterJenis = " and a.jenis = 'PENSIUN' ";
                else $filterJenis = " ";
            }

            $sql = "select sum(a.nilai) as premi_total, 
                    sum(case when a.kode_lokasi='01' then a.nilai else 0 end) as pr1, 
                    sum(case when a.kode_lokasi='02' then a.nilai else 0 end) as pr2, 
                    sum(case when a.kode_lokasi='03' then a.nilai else 0 end) as pr3, 
                    sum(case when a.kode_lokasi='04' then a.nilai else 0 end) as pr4, 
                    sum(case when a.kode_lokasi='05' then a.nilai else 0 end) as pr5, 
                    sum(case when a.kode_lokasi='06' then a.nilai else 0 end) as pr6, 
                    sum(case when a.kode_lokasi='07' then a.nilai else 0 end) as pr7,
                    sum(case when a.kode_lokasi='99' then a.nilai else 0 end) as pr9                     
                    from yk_bpjs_iuran a 
                    where a.periode between '".substr($request->periode,0,4)."01' and '".$request->periode."' ".$filterJenis;

            //$success['sql'] = $sql; <--- alert

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

    public function dataKapitasi(Request $request) {
        $this->validate($request, [    
            'tahun' => 'required',
            'kode_pp' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->kode_pp) == 'NASIONAL') $filterLokasi = " and a.kode_lokasi like '%' ";
            else $filterLokasi = " and a.kode_lokasi = '".substr($request->kode_pp,2,2)."' ";

            $sql = "select a.kode_bulan,a.nama,isnull(b.pensiun,0) as pensiun,isnull(b.pegawai,0) as pegawai, isnull(c.n1,0) as n1,isnull(c.n2,0) as n2, isnull(d.ni_akun,0) - isnull(c.n1,0) - isnull(c.n2,0) as n3,  isnull(d.ni_akun,0) as n4
                    from yk_bulan a 
                    
                    left join 
                    ( 
                    select substring(a.periode,5,2) as kode_bulan, 
                    sum(case when a.jenis='PENSIUN' then a.nilai else 0 end) as pensiun, 
                    sum(case when a.jenis='PEGAWAI' then a.nilai else 0 end) as pegawai 
                    from yk_bpjs_iuran a 
                    where substring(a.periode,1,4) = '".$request->tahun."' ".$filterLokasi." 
                    group by substring(a.periode,5,2) 
                    ) b on a.kode_bulan=b.kode_bulan 
                    
                    left join ( 
                    select substring(a.periode,5,2) as kode_bulan, 
                    sum(case when a.jenis='PENSIUN' and a.jenis_tpkk='TPKK' then a.nilai else 0 end) as n1, 
                    sum(case when a.jenis='PEGAWAI' and a.jenis_tpkk='TPKK' then a.nilai else 0 end) as n2
                    from yk_bpjs_kapitasi a 
                    where substring(a.periode,1,4) = '".$request->tahun."' ".$filterLokasi." 
                    group by substring(a.periode,5,2) 
                    )c on a.kode_bulan=c.kode_bulan 
                    
                    left join (
                    select substring(a.periode,5,2) as kode_bulan,sum(a.nilai) as ni_akun
                    from gldt a
                    where substring(a.periode,1,4) ='".$request->tahun."' and a.kode_akun='21060103' and a.dc='C' ".$filterLokasi." 
                    group by substring(a.periode,5,2) 
                    ) d on a.kode_bulan=d.kode_bulan 
                    
                    order by a.kode_bulan ";

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


    public function dataClaim(Request $request) {
        $this->validate($request, [    
            'periode' => 'required',
            'jenis' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if (strtoupper($request->jenis) == 'PEGAWAI') $filterJenis = " and a.jenis <> 'PENSIUN' ";
            else {
                if (strtoupper($request->jenis) == 'PENSIUN') $filterJenis = " and a.jenis = 'PENSIUN' ";
                else $filterJenis = " ";
            }

            $sql = "select 'TAGIHAN AWAL' as jenis,b.kode_biaya,b.nama as nama_biaya, 
                    sum(case when a.kode_lokasi='01' then a.total else 0 end) as n1, 
                    sum(case when a.kode_lokasi='02' then a.total else 0 end) as n2,
                    sum(case when a.kode_lokasi='03' then a.total else 0 end) as n3,
                    sum(case when a.kode_lokasi='04' then a.total else 0 end) as n4,
                    sum(case when a.kode_lokasi='05' then a.total else 0 end) as n5,
                    sum(case when a.kode_lokasi='06' then a.total else 0 end) as n6,
                    sum(case when a.kode_lokasi='07' then a.total else 0 end) as n7 
                    from yk_bpjs_biaya b 
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".substr($request->periode,0,4)."01'  
                    and '".$request->periode."' ".$filterJenis." 
                    group by b.kode_biaya,b.nama 
                    
                    union all 
                    
                    select 'CLAIM' as jenis,b.kode_biaya,b.nama as nama_biaya, 
                    sum(case when a.kode_lokasi='01' then a.claim else 0 end) as n1, 
                    sum(case when a.kode_lokasi='02' then a.claim else 0 end) as n2, 
                    sum(case when a.kode_lokasi='03' then a.claim else 0 end) as n3, 
                    sum(case when a.kode_lokasi='04' then a.claim else 0 end) as n4, 
                    sum(case when a.kode_lokasi='05' then a.claim else 0 end) as n5, 
                    sum(case when a.kode_lokasi='06' then a.claim else 0 end) as n6, 
                    sum(case when a.kode_lokasi='07' then a.claim else 0 end) as n7 
                    from yk_bpjs_biaya b 
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".substr($request->periode,0,4)."01' 
                    and '".$request->periode."' ".$filterJenis." 
                    group by b.kode_biaya,b.nama  
                    
                    union all 
                    
                    select 'DIBAYAR' as jenis,b.kode_biaya,b.nama as nama_biaya, 
                    sum(case when a.kode_lokasi='01' then a.selisih else 0 end) as n1, 
                    sum(case when a.kode_lokasi='02' then a.selisih else 0 end) as n2, 
                    sum(case when a.kode_lokasi='03' then a.selisih else 0 end) as n3, 
                    sum(case when a.kode_lokasi='04' then a.selisih else 0 end) as n4, 
                    sum(case when a.kode_lokasi='05' then a.selisih else 0 end) as n5, 
                    sum(case when a.kode_lokasi='06' then a.selisih else 0 end) as n6, 
                    sum(case when a.kode_lokasi='07' then a.selisih else 0 end) as n7 
                    from yk_bpjs_biaya b 
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".substr($request->periode,0,4)."01' 
                    and '".$request->periode."' ".$filterJenis." 
                    group by b.kode_biaya,b.nama ";

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

}
