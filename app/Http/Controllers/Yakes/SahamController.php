<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class SahamController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

    function execute($sql){
    
        $res = DB::connection($this->db)->select($sql);
        return $res;
    }

    public function grabCurr(Request $request){
        $this->validate($request,[
            'from_curr' => 'required',
            'to_curr' => 'required'
        ]);
        try { 
            $client = new Client();
            $response = $client->request('GET',  'https://www.alphavantage.co/query',[
                'query' => [
                    'function' => 'CURRENCY_EXCHANGE_RATE',
                    'from_currency' => $request->from_curr,
                    'to_currency' => $request->to_curr,
                    'apikey' => '7IA8UHDVIR495Q36'
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
                $data = $data["Realtime Currency Exchange Rate"];
            }
            return response()->json(['daftar' => $data, 'status'=>true, 'message' => 'success'], 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }

    public function getMultiCurr(Request $request){
        $this->validate($request,[
            'id' => 'required'
        ]);
        try { 
            $client = new Client();
            $response = $client->request('GET',  'https://bloomberg-market-and-financial-news.p.rapidapi.com/market/get-cross-currencies',[
                "headers" => [
                    "x-rapidapi-host" => "bloomberg-market-and-financial-news.p.rapidapi.com",
                    "x-rapidapi-key" => "294c7e585fmshf0d82e87eaf4dfcp100cb1jsnabd7efddbcc2"
                ],
                'query' => [
                    'id' => $request->id
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
                $data = $data["result"];
            }
            return response()->json(['daftar' => $data, 'status'=>true, 'message' => 'success'], 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }

    public function getStatistics(Request $request){
        $this->validate($request,[
            'id' => 'required'
        ]);
        try { 
            $client = new Client();
            $response = $client->request('GET',  'https://bloomberg-market-and-financial-news.p.rapidapi.com/stock/get-statistics',[
                "headers" => [
                    "x-rapidapi-host" => "bloomberg-market-and-financial-news.p.rapidapi.com",
                    "x-rapidapi-key" => "294c7e585fmshf0d82e87eaf4dfcp100cb1jsnabd7efddbcc2"
                ],
                'query' => [
                    'id' => $request->id
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
                $data = $data["result"];
            }
            return response()->json(['daftar' => $data, 'status'=>true, 'message' => 'success'], 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }

    public function getSaham(Request $request){
        $this->validate($request,[
            'id' => 'required'
        ]);
        try { 
            $client = new Client();
            // $response = $client->request('GET',  'https://bloomberg-market-and-financial-news.p.rapidapi.com/market/get-full',[
            //     "headers" => [
            //         "x-rapidapi-host" => "bloomberg-market-and-financial-news.p.rapidapi.com",
            //         "x-rapidapi-key" => "294c7e585fmshf0d82e87eaf4dfcp100cb1jsnabd7efddbcc2"
            //     ],
            //     'query' => [
            //         'id' => $request->id
            //     ]
            // ]);

            // if ($response->getStatusCode() == 200) { // 200 OK
            //     $response_data = $response->getBody()->getContents();
                
            //     $data = json_decode($response_data,true);
            // }
            $data = array('result'=>array());
            return response()->json(['daftar' => $data, 'status'=>true, 'message' => 'success'], 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        } 
    }
    
}
