<?php

namespace App\Jobs;
use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;

class TesEmailJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $r = [];
    public function __construct($r)
    {
        $this->r = $r;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('This request:');
        Log::info($this->r);
        try {
            $client = new Client();
            $credentials = base64_encode('api:'.config('services.mailgun.secret'));
            $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
            $response = $client->request('POST',  $domain,[
                'headers' => [
                    'Authorization' => 'Basic '.$credentials
                ],
                'form_params' => [
                    'from' => $this->r['from'],
                    'to' => $this->r['to'],
                    'subject' => $this->r['subject'],
                    'html' => $this->r['html'],
                    'text' => $this->r['text'],
                ]
            ]);
            // if ($response->getStatusCode() == 200) { // 200 OK
                $response = $response->getBody()->getContents();
                $res = json_decode($response,true);
                Log::info($res);
            //     return response()->json(['data' => $data], 200);  
            // }
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            Log::error($res);
            // $data['message'] = $res;
            // $data['status'] = false;
            // return response()->json(['data' => $data], 500);
        }
    }
}
