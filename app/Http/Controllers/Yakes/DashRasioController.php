<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashRasioController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';


    public function dataEBM(Request $request) {
        $this->validate($request, [    
            'periode' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.kode_rasio,a.kode_lokasi,a.kode_neraca,case when b.jenis_akun='Pendapatan' or b.modul='P' then -b.n4 else b.n4 end as nilai2, c.rumus, c.nama as nama_rasio
            from dash_rasio_d a
            inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            inner join dash_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='00' and b.periode='$request->periode'  and a.kode_rasio='EBM' order by a.kode_rasio");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();

                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['kode_rasio']])){
                        $dfr[$res[$i]['kode_rasio']] = array('nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array());
                    }
        
                    $dfr[$res[$i]['kode_rasio']]['par'][] = array(
                        'kode_neraca'=>$res[$i]['kode_neraca'],
                        'nilai2'=>$res[$i]['nilai2']
                    );
                }
        
                $data = array();
                foreach($dfr as $d){
                    $p = '';
                    for($z=0; $z<count($d['par']); $z++){
                        
                        $kode_neraca= str_replace("-","",$d['par'][$z]['kode_neraca']);
                        
                        $p .= $kode_neraca." = ".$d['par'][$z]['nilai2']."<br>";
                        
                        ${"a" . $kode_neraca} = floatval($d['par'][$z]['nilai2']);
                    }
                    $kode=$d['nama_rasio'];
                    $hasil= eval('return '.$d['rumus'].';');  
                    $data[] = array('nama_rasio'=>$kode,'hasil'=>$hasil);

                }
                $success['status'] = true;
                $success['data'] = $data;
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
