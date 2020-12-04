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
