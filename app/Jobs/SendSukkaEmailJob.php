<?php

namespace App\Jobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;
use Carbon\Carbon;

class SendSukkaEmailJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $no_pool = [];
    private $dbc = '';
    public function __construct($no_pool,$dbc)
    {
        $this->no_pool= $no_pool;
        $this->dbc = $dbc;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::connection($this->dbc)->beginTransaction();
        try{
            $client = new Client();
            $res = DB::connection($this->dbc)->select("select no_hp,pesan,jenis,email,subject from pooling where flag_kirim=0 and no_pool = ?  ",array($this->no_pool));
            if(count($res) > 0){
                $msg = "";
                $sts = false;
                foreach($res as $row){
                    if($row->jenis == "EMAIL") {
                        $credentials = base64_encode('api:'.config('services.mailgun.secret'));
                        $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
                        $response = $client->request('POST',  $domain,[
                            'headers' => [
                                'Authorization' => 'Basic '.$credentials
                            ],
                            'form_params' => [
                                'from' => 'devsaku5@gmail.com',
                                'to' => $row->email,
                                'subject' => $row->subject,
                                'html' => htmlspecialchars_decode($row->pesan)
                            ]
                        ]);
                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);
                            if(isset($data["id"])){
                                $success['data2'] = $data;

                                $updt =  DB::connection($this->dbc)->table('pooling')
                                ->where('no_pool', $this->no_pool)    
                                ->where('jenis', 'EMAIL')
                                ->where('flag_kirim', 0)
                                ->update(['tgl_kirim' => Carbon::now()->timezone("Asia/Jakarta"), 'flag_kirim' => 1]);

                                
                                DB::connection($this->dbc)->commit();
                                $sts = true;
                                $msg .= $data['message'];
                            }
                        }
                    }
                    
                }

                $success['message'] = $msg;
                $success['status'] = $sts;
            }else{
                $success['message'] = "Data pooling tidak valid";
                $success['status'] = false;
            }
            Log::info($success);
        } catch (BadResponseException $ex) {
            
            DB::connection($this->dbc)->rollback();
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            Log::error($res);
        }
    }
}
