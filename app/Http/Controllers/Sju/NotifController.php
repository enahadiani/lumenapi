<?php

namespace App\Http\Controllers\Sju;

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
    public $guard = 'sju';
    public $db = 'sqlsrvsju';
	
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
		$apiKey = "AAAAKIaswLQ:APA91bFRtRnik2QoopKJFb_jZjg0lwWdwUs9Zk2pjQEB7jxG9XwgQVZ51pH3-Rzx_mSzAV8BIy7SvmAlbJYE9R6qcFjQSg6JuZX-ExwSNbyfJnAjFJxktbukovL4XjgTFbzqM24nKqqB";
	    //------------------------------
	    // Define URL to GCM endpoint
	    //------------------------------
	
		$url = 'https://fcm.googleapis.com/fcm/send';
	
	    //------------------------------
	    // Set GCM post variables
	    // (Device IDs and push payload)
		//------------------------------
		if(isset($data['click_action'])){

			$post = array(
                'registration_ids'  => $ids,
                'notification'              => array (
                    "body" => $data["message"],
                    "title" => $data["title"]
                ),
                'data'              => $data,
                "android" => array (
                    "ttl" => "86400s",
                    "notification" => array (
                        "click_action" => $data["click_action"]
                        )
                    ),
                    
                );
		}else{

			$post = array(
				'registration_ids'  => $ids,
				'notification'              => array (
					"body" => $data["message"],
					"title" => $data["title"]
				),
				'data'              => $data
			);
		}
	
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
			"token" => 'required|max:300',
			"data" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            $token = $request->token;
            if($token != "-"){
                
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
    
                        $ins[$i] = DB::connection($this->db)->insert("insert into user_message (kode_lokasi,judul,subjudul,pesan,nik,id_device,status,tgl_input,icon,sts_read,sts_read_mob) values ('$kode_lokasi','".$request->data['title']."','-','".$request->data['message']."','".$request->data['nik']."','".$request->token[$i]."','$sts',getdate(),'-','0','0') ");
                    }
                    DB::connection($this->db)->commit();
                    $success['status'] = true;
                    $success['message'] = "Sukses";
                }else{
                    $success['status'] = false;
                    $success['message'] = "Gagal";
                }
            }else{
                $success['status'] = false;
                $success['message'] = "Gagal";
            }
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}
	
	
	
	public function getNotif(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}
		
        try{
            
			$sql = "select id,judul,pesan,tgl_input,status,icon,sts_read_mob 
			from user_message
			where nik='$nik' and status in ('1')
			";

			$get = DB::connection($this->db)->select($sql);
			$get = json_decode(json_encode($get),true);
			if(count($get) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $get;
                $success['message'] = "Success!";     
            }
            else{
                $success['status'] = false;
                $success['data'] = [];
                $success['message'] = "Data Kosong!";
            }
            $success['status'] = true;
            $success['message'] = "Sukses ";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}
	
	public function updateStatusRead(Request $request)
	{
		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            
			
			$upd = DB::connection($this->db)->insert("update user_message set sts_read = '1' where nik='$nik' and kode_lokasi='$kode_lokasi' ");

			DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}

	public function updateStatusReadMobile(Request $request)
	{
		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		$this->validate($request,[
			'id' => 'required|max:300'
		]);

		DB::connection($this->db)->beginTransaction();
        try{
            
			$upd = DB::connection($this->db)->insert("update user_message set sts_read_mob = '1' where nik='$nik' and id='$request->id' and kode_lokasi='$kode_lokasi' ");

			DB::connection($this->db)->commit();
			$success['status'] = true;
			$success['message'] = "Sukses";
            return response()->json($success, 200);
        } catch (\Throwable $e) {
			DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}

	
}
