<?php

namespace App\Http\Controllers\Siaga;

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
    public $guard = 'siaga';
    public $db = 'dbsiaga';
	
	public function sendPusher(Request $request)
	{
		$this->validate($request,[
			"title" => 'required',
			"subtitle" => 'required',
			"message" => 'required',
			"id" => 'required|array',
			"sts_insert" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            
			for($i=0;$i<count($request->id);$i++){

				event(new \App\Events\NotifSiaga($request->title,$request->message,$request->id[$i]));
				if($request->sts_insert == '1'){

					$ins[$i] = DB::connection($this->db)->insert("insert into user_message (kode_lokasi,judul,subjudul,pesan,nik,id_device,status,tgl_input,icon,sts_read,sts_read_mob) values ('$kode_lokasi','".$request->title."','".$request->subtitle."','".$request->message."','".$request->id[$i]."','saisiaga-channel-".$request->id[$i]."','1',getdate(),'-','0','0') ");
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
            
			// $sql = "select top 5 id,judul,subjudul,pesan,tgl_input,status,icon,convert(varchar,tgl_input,103) as tgl, convert(varchar,tgl_input,108) as jam
			// from user_message
			// where id_device='saisiaga-channel-$nik' and status in ('1')
			// order by id desc
			// ";
			$sql = "select top 5 no_bukti as id,judul,subjudul,pesan,tgl_input,sts_kirim as status,icon,convert(varchar,tgl_input,103) as tgl, convert(varchar,tgl_input,108) as jam
			from app_notif_m
			where nik='$nik' and sts_kirim in ('1')
			order by id desc
			";

			$get = DB::connection($this->db)->select($sql);
			$get = json_decode(json_encode($get),true);

			$sql = "select count(*) as jumlah
			from app_notif_m
			where nik='$nik' and sts_kirim in ('1') and sts_read = '0'
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

	public function getNotifFCM(Request $request){
		$this->validate($request,[
			'id_device' => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}
		
        try{
            $id = $request->id_device;
			$sql = "select top 5 id,judul,subjudul,pesan,tgl_input,status,icon,convert(varchar,tgl_input,103) as tgl, convert(varchar,tgl_input,108) as jam
			from user_message
			where id_device='$id' and status in ('1')
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

	function gcm($token,$payload){
		$data = $payload;//array
		$ids = $token;//array
		global $apiKey;
		$apiKey = config('services.gcm.api_siaga');;
	   
		$url = 'https://fcm.googleapis.com/fcm/send';
	
		if(isset($data['click_action'])){

			$post = array(
                'registration_ids'  => $ids,
                'data'              => $data,
                'priority' => "high",
                'android' => array(
                    "priority" => "high",
                    "ttl" => "86400s",
                    "notification" => array (
                      "click_action" =>  $data["click_action"]
                    )
                ),  
            );
		}else{

			$post = array(
				'registration_ids'  => $ids,
				'data'              => $data
			);
		}
	
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

	public function sendNotifApproval(Request $request)
	{
		$this->validate($request,[
			"no_pesan" => 'required'
		]);

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
			$getpsn = DB::connection($this->db)->select("select a.judul,a.pesan,a.nik,a.ref1,a.ref2
			from app_notif_m a
			where a.no_bukti='$request->no_pesan'  and a.kode_lokasi='$kode_lokasi' and a.sts_kirim=0
			");
			if(count($getpsn) > 0){
				$nik_kirim = $getpsn[0]->nik;
				$title = $getpsn[0]->judul;
				$message = $getpsn[0]->pesan;
				$no_bukti = $getpsn[0]->ref1;
				$modul = $getpsn[0]->ref2;

				// NOTIF WEB
				event(new \App\Events\NotifSiaga($title,$message,$nik_kirim));
				
				$insd = DB::connection($this->db)->insert("insert into app_notif_d (kode_lokasi,no_bukti,id_device,nik,no_urut) values (?, ?, ?, ?, ?)",array($kode_lokasi,$request->no_pesan,'saisiaga-channel-'.$nik_kirim,$nik_kirim,0));

				// NOTIF ANDROID

				$getid = DB::connection($this->db)->select("select a.id_device
				from users_device a
				where a.nik='$nik_kirim' and a.kode_lokasi='$kode_lokasi'  ");
				$getid = json_decode(json_encode($getid),true);
				$arr_id = array();
				if(count($getid) > 0){
					$no=1;
					for($i=0;$i<count($getid);$i++){
						$ins[$i] = DB::connection($this->db)->insert("insert into app_notif_d (kode_lokasi,no_bukti,id_device,nik,no_urut) values (?, ?, ?, ?, ?)",array($kode_lokasi,$request->no_pesan,$getid[$i]['id_device'],$nik_kirim,$no));
						array_push($arr_id, $getid[$i]['id_device']);
						$no++;
					}
					$payload = array(
						'title' => $title,
						'message' => $message,
						'click_action' => 'detail_pengajuan',
						'key' => array(
							'no_bukti' => $no_bukti,
							'modul' => $modul
						)
					);
					$res = $this->gcm($arr_id,$payload);
					$hasil= json_decode($res,true);
					$success['hasil'] = $hasil;
					if(isset($hasil['success'])){
						if($hasil['failure'] > 0){
							$sts = 0;
							$msg_n = "Notif FCM gagal dikirim";
						}else{
							$msg_n = "Notif FCM berhasil dikirim";
							$sts = 1;
						}
					}else{
						$msg_n = "Notif FCM gagal dikirim";
						$sts = 0;
					}
				}

				$updpsn = DB::connection($this->db)->table('app_notif_m')
				->where('no_bukti',$request->no_pesan)
				->where('kode_lokasi',$kode_lokasi)
				->update(['sts_kirim'=>1]);
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

	public function updateStatusRead(Request $request)
	{
		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
		}

		DB::connection($this->db)->beginTransaction();
        try{
            
			
			$upd = DB::connection($this->db)->insert("update app_notif_m set sts_read = '1' where nik='$nik' and kode_lokasi='$kode_lokasi' ");

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
			'id' => 'required'
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
