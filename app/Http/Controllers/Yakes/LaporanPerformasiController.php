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
    

}
