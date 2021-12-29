<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class DakemApiController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    function execute($sql){
    
        $res = DB::connection($this->db)->select($sql);
        return $res;
    }

    public function getPesertaDakemByNIK(Request $request){
        $this->validate($request,[
            'nik' => 'required'
        ]);
        try { 
            $client = new Client();
            $response = $client->request('POST',  'https://sika.yakestelkom.or.id/api2/getPesertaDakem',[
                'form_params' => [
                    'key' => 'IeXdn4oCBXN76PlsGXdB5PtuyAK7bqXvS1K4Y4k3s',
                    'nik' => $request->input('nik')
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
            }
            return response()->json($data, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }

    public function getPesertaDakemByNIKES(Request $request){
        $this->validate($request,[
            'nikes' => 'required'
        ]);
        try { 
            $client = new Client();
            $response = $client->request('POST',  'https://sika.yakestelkom.or.id/api2/getPesertaDakem',[
                'form_params' => [
                    'key' => 'IeXdn4oCBXN76PlsGXdB5PtuyAK7bqXvS1K4Y4k3s',
                    'nikes' => $request->input('nik')
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
            }
            return response()->json($data, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }
    
}
