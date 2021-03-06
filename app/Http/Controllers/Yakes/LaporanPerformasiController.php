<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanNrcLajur;

class LaporanPerformasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yakes';
    public $sql = 'dbsapkug';

    public function getKepesertaan(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'                     
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql = "select sum(kk+pas+anak+jd) as total from dash_peserta where periode ='".$request->periode."' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select jenis,sum(kk+pas+anak+jd) as tot_jenis from dash_peserta where periode ='".$request->periode."' group by jenis ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            $sql3 = "select jenis,sum(kk) as kk,sum(pas) as pas,sum(anak) as anak,sum(jd) as jd from dash_peserta where periode ='".$request->periode."' group by jenis";
            $res3 = DB::connection($this->sql)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            $sql4 = "select a.jenis,a.kode_lokasi,b.nama,sum(a.kk)as kk,sum(a.pas) as pas,sum(a.anak) as anak,sum(a.jd) as jd
                    from dash_peserta a inner join pp b on a.kode_lokasi=b.kode_pp and b.kode_lokasi ='00'
                    where periode ='".$request->periode."' group by a.jenis,a.kode_lokasi,b.nama";
            $res4 = DB::connection($this->sql)->select($sql4);
            $res4 = json_decode(json_encode($res4),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['data2'] = $res2;
                $success['data3'] = $res3;
                $success['data4'] = $res4;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data2'] = $res2;
                $success['data3'] = $res3;
                $success['data4'] = $res4;
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getBinaSehat(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'                     
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];

            $sql = "select a.no,a.periode,a.uraian,a.satuan,a.rka,a.real,a.real_before,a.ach,a.yoy 
            from dash_bina_sehat a
            where a.periode ='$periode'
            order by a.no_urut
            ";
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
    
    public function getTopSix(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'                     
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];

            $sql = "select no,jenis,nama,penderita_before,penderita_now,biaya_before,biaya_now,yoy_jiwa_before,yoy_jiwa_now*100 as yoy_jiwa_now,yoy_biaya_before,yoy_biaya_now*100 as yoy_biaya_now,rata2_before,rata2_now 
            from dash_top_icd 
            where jenis='PENSIUN' and periode='$periode'
            order by no_urut
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql2 = "select no,jenis,nama,penderita_before,penderita_now,biaya_before,biaya_now,yoy_jiwa_before,yoy_jiwa_now*100 as yoy_jiwa_now,yoy_biaya_before,yoy_biaya_now*100 as yoy_biaya_now,rata2_before,rata2_now 
            from dash_top_icd 
            where jenis='PEGAWAI' and periode='$periode'
            order by no_urut
            ";
            $res2 = DB::connection($this->sql)->select($sql2);
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
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

    public function getKontrakManage(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'                     
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];

            $sql = "select no,indicator,unit,weight,target,real,formula*100 as formula,ach*100 as ach,score,warna
            from dash_kontrak_manage
            where periode='$periode'
            order by no_urut 
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
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

    public function getSDMCulture(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'                     
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode = $request->periode[1];

            $sql = "select program,kode_pp,role_model,jumlah 
            from dash_sdm_culture 
            where periode='$periode'
            order by no_urut
            ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            if(count($res) > 0 || count($res2) > 0){ //mengecek apakah data kosong atau tidak
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
