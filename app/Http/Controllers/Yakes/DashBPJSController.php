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
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$request->periode.substr(0,4)."01'  
                    and '".$request->periode."') ".$filterJenis." 
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
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$request->periode.substr(0,4)."01' 
                    and '".$request->periode."') ".$filterJenis." 
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
                    left join yk_bpjs_cob a on a.kode_biaya=b.kode_biaya and a.periode between '".$request->periode.substr(0,4)."01' 
                    and '".$request->periode."') ".$filterJenis." 
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
