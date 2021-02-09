<?php

namespace App\Http\Controllers\Dago;

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
    public $db = 'sqlsrvdago';
    public $guard = 'dago';

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
		$apiKey = "AAAAzGFLzxc:APA91bGQVIppD4IopsvonNYAyxGibfhAmJW1uIUePU1UqQBAyL-crGXks43HrFoPe-1zukFvydJqttfMI_E-Lx3y65NWcXjzJ9OvO35xdfdUEyrX3TiCYcCKJfo8a-EJnhmOjVEQ3f35";
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
    
                        $ins[$i] = DB::connection($this->db)->insert("insert into user_message (kode_lokasi,title,subtitle,pesan,nik,id_device,status,tgl_input,icon,sts_read,sts_read_mob) values ('$kode_lokasi','".$request->data['title']."','-','".$request->data['message']."','".$request->data['nik']."','".$request->token[$i]."','$sts',getdate(),'-','0','0') ");
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
            
			// $sql = "select id,title,pesan,tgl_input,status,icon,sts_read_mob 
			// from user_message
			// where nik='$nik' and status in ('1')
			// ";
			$sql ="select a.id,a.title,a.pesan,a.tgl_input,a.status,a.icon,a.sts_read_mob,LTRIM(replace(replace(a.pesan,'Pengajuan pembayaran ',''),' menunggu verifikasi anda','')) as no_bukti,b.no_kb,b.flag_ver 
			from user_message a
			inner join dgw_pembayaran b on LTRIM(replace(replace(a.pesan,'Pengajuan pembayaran ',''),' menunggu verifikasi anda',''))=b.no_kwitansi
			where a.nik='$nik' and status in ('1') and b.flag_ver = 0";

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
	
	public function sendPusher(Request $request)
	{
		$this->validate($request,[
			"title" => 'required|max:50',
			"message" => 'required|max:200',
			"id" => 'required|array|max:300',
			"sts_insert" => 'required|max:2'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            
			for($i=0;$i<count($request->id);$i++){

				event(new \App\Events\NotifDago($request->title,$request->message,$request->id[$i]));
				if($request->sts_insert == '1'){

					$ins[$i] = DB::connection($this->db)->insert("insert into user_message (kode_lokasi,title,subtitle,pesan,nik,id_device,status,tgl_input,icon,sts_read,sts_read_mob) values ('$kode_lokasi','".$request->title."','-','".$request->message."','".$request->id[$i]."','".$request->id[$i]."','1',getdate(),'-','0','0') ");
				}

			}

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

	public function getNotifPusher(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}
		
        try{
            
			$sql = "select top 5 id,title,pesan,tgl_input,status,icon,convert(varchar,tgl_input,105) as tgl, convert(varchar,tgl_input,108) as jam
			from user_message
			where nik='$nik' and status in ('1')
			order by id desc
			";

			$get = DB::connection($this->db)->select($sql);
			$get = json_decode(json_encode($get),true);

			$sql = "select count(*) as jumlah
			from user_message
			where nik='$nik' and status in ('1') and sts_read = '0'
			";

			$getjum = DB::connection($this->db)->select($sql);
			if(count($getjum) > 0){
				$success['jumlah'] = $getjum[0]->jumlah;
			}else{
				$success['jumlah'] = 0;
			}

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
