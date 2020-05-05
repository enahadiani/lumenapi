<?php

namespace App\Http\Controllers\Apv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use GuzzleHttp\Client;


class NotifikasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    public function register(Request $request)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select nik,kode_lokasi from api_token_auth where nik='".$nik."' and kode_lokasi='".$kode_lokasi."' and token='".$request->token."' ");
            $res = json_decode(json_encode($res),true);

            if(count($res)>0){
                $success['message'] = 'Already registered';
            }else{
                $token_sql = DB::connection('sqlsrv2')->insert('insert into api_token_auth (nik,api_key,token,kode_lokasi,os,ver,model,uuid,tgl_login) values (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$nik,Str::random('alnum',20),$request->token,$kode_lokasi,'BROWSER','',
                '','',date('Y-m-d H:i:s')]);
                if($token_sql){
                    $success['message'] = "ID registered";
                }else{
                    $success['message'] = "Failed to register";
                }
            }
            
            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
        
    }

    function sendNotif(Request $request){ 	

        try {

            $title = $request->title;
            $content = $request->content;
            $token_player = $request->token_player;
            $title = $title;
            $content      = array(
                "en" => $content
            );
            
            $fields = array(
                'app_id' => "5f0781d5-8856-4f3e-a2c7-0f95695def7e", //appid laravelsai
                'include_player_ids' => $token_player,
                'data' => array(
                    "foo" => "bar"
                ),
                'contents' => $content,
                'headings' => array(
                    'en' => $title
                    )
            );
            
            $fields = json_encode($fields);

            $client = new Client();
            $response = $client->request('POST', 'https://onesignal.com/api/v1/notifications',[
                'headers' => [
                    'Authorization' => 'Basic ZmY5ODczYTMtNTgwZS00YmQ4LWFmNTMtMzQxZDY4ODc3MWFh',
                    'Content-Type: application/json; charset=utf-8'
                ],
                'body' => $fields
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                
                $data = json_decode($response_data,true);
            }
            return response()->json(['result' => $data, 'status'=>true], 200); 

        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            return response()->json(['message' => $res["message"], 'status'=>false], 200);
        }

    }

}
