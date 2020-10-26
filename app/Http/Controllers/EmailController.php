<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class EmailController extends Controller
{
    function send(Request $request){
        $this->validate($request,[
            'from' => 'required',
            'to' => 'required',
            'subject' => 'required',
            'text' => 'required'
        ]);
        try {
            $client = new Client();
            $credentials = base64_encode('api:'.config('services.mailgun.secret'));
            $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
            $response = $client->request('POST',  $domain,[
                'headers' => [
                    'Authorization' => 'Basic '.$credentials
                ],
                'form_params' => [
                    'from' => $request->from,
                    'to' => $request->to,
                    'subject' => $request->subject,
                    'text' => $request->text,
                ]
            ]);
            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                return response()->json(['data' => $data], 200);  
            }
            dump($domain);

        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json(['data' => $data], 500);
        }
    }
}
