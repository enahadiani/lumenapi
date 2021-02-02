<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Storage; 

class WAController extends Controller
{
    
	public $successStatus = 200;
    public $guard = 'toko';
    public $db = 'tokoaws';
    public $api_url = "https://eu179.chat-api.com/instance222737/";
    public $token = "p0wo8m8y3twd36o5";

	
	public function sendMessage(Request $request)
	{
		$this->validate($request,[
			"body" => 'required',
			"phone" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		// DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();
            $response = $client->request('GET',  $this->api_url."sendMessage",[
                'query' => [
                    'token' => $this->token
                ],
                'form_params' => [
                    'body' => $request->body,
                    'phone' => $request->phone
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                return response()->json(['data' => $data], 200);  
            }
			
			// DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json(['data' => $data], 500);
        }
    }
    
    public function Messages(Request $request)
	{

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		// DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();
            $response = $client->request('POST',  $api_url."messages",[
                'headers' => [
                    'Content-type' => 'application/json'
                ],
                'query' => [
                    'token' => $this->token,
                    'chatId' => $request->chatId,
                    'lastMessageNumber' => $request->lastMessageNumber,
                    'last' => $request->last,
                    'limit' => $request->limit,
                    'min_time' => $request->min_time, 	
                    'max_time' => $request->max_time
                ]
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                return response()->json(['data' => $data], 200);  
            }
			
			// DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json(['data' => $data], 500);
        }
	}
}