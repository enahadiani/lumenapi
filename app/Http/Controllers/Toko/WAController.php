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

	function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }  

	// public function sendMessage(Request $request)
	// {
	// 	$this->validate($request,[
	// 		"body" => 'required',
	// 		"phone" => 'required'
	// 	]);

	// 	if($auth =  Auth::guard($this->guard)->user()){
	// 		$nik= $auth->nik;
	// 		$kode_lokasi= $auth->kode_lokasi;
	// 	}

	// 	// DB::connection($this->db)->beginTransaction();
    //     try{
    //         $client = new Client();
    //         $response = $client->request('GET',  $this->api_url."sendMessage?token=".$this->token,[
    //             'form_params' => [
    //                 'body' => $request->body,
    //                 'phone' => $request->phone
    //             ]
    //         ]);

    //         if ($response->getStatusCode() == 200) { // 200 OK
    //             $response_data = $response->getBody()->getContents();
    //             $data = json_decode($response_data,true);
    //             return response()->json(['data' => $data], 200);  
    //         }
			
	// 		// DB::connection($this->db)->commit();
	// 		$success['status'] = true;
	// 		$success['message'] = "Sukses";
    //         return response()->json($success, 200);
    //     } catch (BadResponseException $ex) {
    //         $response = $ex->getResponse();
    //         $res = json_decode($response->getBody(),true);
    //         $data['message'] = $res;
    //         $data['status'] = false;
    //         return response()->json(['data' => $data], 500);
    //     }
    // }

    public function sendMessage(Request $request)
	{
		$this->validate($request,[
			"id_pesan" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();

            $wa = DB::connection($this->db)->select("select no_hp,pesan from pooling where flag_kirim=0 and no_pool ='$request->id_pesan' and jenis='WA' ");
            if(count($wa) > 0){
                $pesan = $wa[0]->pesan;
                $no_hp = $wa[0]->no_hp;
                $response = $client->request('GET',  $this->api_url."sendMessage?token=".$this->token,[
                    'form_params' => [
                        'body' => $pesan,
                        'phone' => $no_hp
                    ]
                ]);
    
                if ($response->getStatusCode() == 200) { // 200 OK
                    $response_data = $response->getBody()->getContents();
                    $data = json_decode($response_data,true);
                }
    
                if($data['sent']){
                    $success['data'] = $data;
                    DB::connection($this->db)->update("update pooling set tgl_kirim=getdate(),flag_kirim=1 where flag_kirim=0 and no_pool ='$request->id_pesan' and jenis='WA'
                    ");
                    DB::connection($this->db)->commit();
                }else{
                    $success['data']['sent'] = false;
                    $success['data']['message'] = $data['message'];
                }
            }else{
                
                DB::connection($this->db)->rollback();
                $success['data']['sent'] = false;
                $success['data']['message'] = 'Data pooling tidak valid.';
            }

            $email = DB::connection($this->db)->select("select email,pesan from pooling where flag_kirim=0 and no_pool ='$request->id_pesan' and jenis='EMAIL' ");
            if(count($email) > 0){

                $credentials = base64_encode('api:'.config('services.mailgun.secret'));
                $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
                $response = $client->request('POST',  $domain,[
                    'headers' => [
                        'Authorization' => 'Basic '.$credentials
                    ],
                    'form_params' => [
                        'from' => 'devsaku5@gmail.com',
                        'to' => $email[0]->email,
                        'subject' => 'Email dari SAI',
                        'html' => $email[0]->pesan
                    ]
                ]);
                if ($response->getStatusCode() == 200) { // 200 OK
                    $response_data = $response->getBody()->getContents();
                    $data = json_decode($response_data,true);
                    if(isset($data['id'])){
                        $success['data2'] = $data;
                        DB::connection($this->db)->update("update pooling set tgl_kirim=getdate(),flag_kirim=1 where flag_kirim=0 and no_pool ='$request->id_pesan' and jenis='EMAIL'
                        ");
                        DB::connection($this->db)->commit();
                    }else{
                        $success['data2']['status'] = false;
                        $success['data2']['message'] = $data['message'];
                    }
                }
            }else{
                
                DB::connection($this->db)->rollback();
                $success['data2']['status'] = false;
                $success['data2']['message'] = 'Data pooling tidak valid.';
            }

            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            
			DB::connection($this->db)->rollback();
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json(['data' => $data], 500);
        }
    }
    
    public function storePooling(Request $request)
	{
		$this->validate($request,[
			"body" => 'required',
            "phone" => 'required',
            "email" => 'required',
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            
            $per = date('Ym');
            $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$per.".", "000001");
            $ins1= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('$request->phone','$request->email','$request->body','0',getdate(),NULL,'WA','$no_pool') ");

            $ins2= DB::connection($this->db)->insert("insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values('$request->phone','$request->email','$request->body','0',getdate(),NULL,'EMAIL','$no_pool') ");

            $success['id_pesan'] = $no_pool;
			DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $er) {
            DB::connection($this->db)->rollback();
            $data['message'] = $er;
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
            $req = $request->all();
            $req['token'] = $this->token;
            $response = $client->request('GET',  $this->api_url."messages",[
                'headers' => [
                    'Content-type' => 'application/json'
                ],
                'query' => $req
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
