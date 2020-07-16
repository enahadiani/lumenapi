<?php

namespace App\Http\Controllers\Rtrw;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class NotifController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware();
    // }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
	public $successStatus = 200;
    public $sql = 'sqlsrvrtrw';
    public $guard = 'rtrw';
    public $guard2 = 'satpam';
	public $guard3 = 'warga';
	
    function gcm($token,$payload){
		$data = $payload;//array
		
		$ids = $token;//array
		//------------------------------
	    // Replace with real GCM API 
	    // key from Google APIs Console
	    // 
	    // https://code.google.com/apis/console/
	    //------------------------------
		global $apiKey;
		// $apiKey = "AIzaSyARBIGBtlVHp2JlhS3HRaP4IPysLBYwXg8";
		$apiKey = "AAAAC2oDJwE:APA91bF6a5lG4gSH0udRsIMB6SbdG-92CME7Y9wBycdXS4ZseKgSz4G1ghN6j3SPcU338wc8a9ifiO5_nVLU2o-MwSzXxHQRuL8IxsKzwkqJzVaFhDIydXe-E-DqiCUr4LIRU1dDXCns";
	    //------------------------------
	    // Define URL to GCM endpoint
	    //------------------------------
	
		$url = 'https://fcm.googleapis.com/fcm/send';
	
	    //------------------------------
	    // Set GCM post variables
	    // (Device IDs and push payload)
	    //------------------------------
	    $post = array(
						'registration_ids'  => $ids,
						'notification'              => array (
									"body" => $data["message"],
									"title" => $data["title"],
							),
	                    'data'              => $data,
	                    );
	
	    //------------------------------
	    // Set CURL request headers
	    // (Authentication and type)
	    //------------------------------
	
	    $headers = array( 
	                        'Authorization: key=' . $apiKey,
	                        'Content-Type: application/json'
	                    );
	
	    //------------------------------
	    // Initialize curl handle
	    //------------------------------
	
	    $ch = curl_init();
	
	    //------------------------------
	    // Set URL to GCM endpoint
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_URL, $url );
	
	    //------------------------------
	    // Set request method to POST
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_POST, true );
	
	    //------------------------------
	    // Set our custom headers
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	
	    //------------------------------
	    // Get the response back as 
	    // string instead of printing it
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	    //------------------------------
	    // Set post data as JSON
	    //------------------------------
	
	    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
	
	    //------------------------------
	    // Actually send the push!
	    //------------------------------
	
	    $result = curl_exec( $ch );
	
	    //------------------------------
	    // Error? Display it!
	    //------------------------------
	
	    if ( curl_errno( $ch ) )
	    {
	        echo('GCM error: ' . curl_error( $ch ));
	        $status = false;
	    }else $status = true;
	
	    //------------------------------
	    // Close curl handle
	    //------------------------------
	
	    curl_close( $ch );
	
	    //------------------------------
	    // Debug GCM response
	    //------------------------------
		// $rs = error_log($token .":".$status);
	    return $result;
    }
    
	public function sendNotif(Request $request)
	{
		$this->validate($request,[
			"token" => 'required',
			"data" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}else if($auth =  Auth::guard($this->guard2)->user()){
			$nik = $auth->id_satpam;
			$kode_lokasi= $auth->kode_lokasi;
		}else if($auth =  Auth::guard($this->guard3)->user()){
			$nik = $auth->no_hp;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->sql)->beginTransaction();
        try{
            $token = $request->token;
			$payload = $request->data;
			$res = $this->gcm($token,$payload);
			$hasil= json_decode($res,true);
			$success['hasil'] = $hasil;
			if(isset($hasil['success'])){
				if($hasil['failure'] > 0){
					$sts = 0;
				}else{
					$sts = 1;
				}
				for($i=0;$i<count($request->token);$i++){

					$ins[$i] = DB::connection($this->sql)->insert("insert into user_message (kode_lokasi,judul,pesan,tgl_input,status,id_device) values ('$kode_lokasi','".$request->data['title']."','".$request->data['message']."',getdate(),'$sts','".$request->token[$i]."') ");
				}
				DB::connection($this->sql)->commit();
				$success['status'] = true;
				$success['message'] = "Sukses";
			}else{
				$success['status'] = false;
				$success['message'] = "Gagal";
			}
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
    }

}
