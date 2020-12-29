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


    public function dataRasio(Request $request) {
        $this->validate($request,[
            'kode_rasio' => 'required',
            'tahun' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.kode_rasio,a.kode_lokasi,a.kode_neraca,b.n4 as nilai2, c.rumus, c.nama as nama_rasio,b.periode,a.nama
            from dash_rasio_d a
            inner join exs_neraca b on a.kode_lokasi=b.kode_lokasi and a.kode_neraca=b.kode_neraca and a.kode_fs=b.kode_fs
            inner join dash_rasio_m c on a.kode_rasio=c.kode_rasio and a.kode_lokasi=c.kode_lokasi
            where a.kode_lokasi='$kode_lokasi' and b.periode like '$request->tahun%' and a.kode_rasio='$request->kode_rasio' order by a.kode_rasio");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $dfr = array();
                $column= array();
                for($i=0; $i<count($res); $i++){
                    if(!ISSET($dfr[$res[$i]['periode']])){
                        $dfr[$res[$i]['periode']] = array('nama_rasio' => $res[$i]['nama_rasio'], 'rumus' => $res[$i]['rumus'], 'par'=>array());
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
                $data[count($data)] = $hasil;
                $column[count($column)] = $kode;
                $success['status'] = true;
                $success['data'] = $data;
                $success['column'] = $column;
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

    public function klpRasio(Request $request) {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select a.kode_rasio,a.nama
            from dash_rasio_m a
            where a.kode_lokasi='$kode_lokasi'");
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
