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
		if(isset($data['click_action'])){

			$post = array(
							'registration_ids'  => $ids,
							'notification'              => array (
								"body" => $data["message"],
								"title" => $data["title"],
								"subtitle" => $data["subtitle"],
								"icon" => $data["icon"],
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
					"title" => $data["title"],
					"subtitle" => $data["subtitle"],
					"icon" => $data["icon"],
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
			// print_r($payload);
			// print_r($token);
			$res = $this->gcm($token,$payload);
			$hasil= json_decode($res,true);
			$success['hasil'] = $hasil;
			if(isset($hasil['success'])){
				if($hasil['failure'] > 0){
					$sts = 0;
				}else{
					$sts = 1;
				}
				if(isset($request->jenis)){
					$jenis = $request->jenis;
				}else{
					$jenis = "-";
				}
				if(isset($request->no_hp)){
					$no_hp = $request->no_hp;
				}else{
					$no_hp = "-";
				}
				for($i=0;$i<count($request->token);$i++){

					$ins[$i] = DB::connection($this->sql)->insert("insert into user_message (kode_lokasi,judul,pesan,tgl_input,status,id_device,jenis,no_hp,subtitle,icon) values ('$kode_lokasi','".$request->data['title']."','".$request->data['message']."',getdate(),'$sts','".$request->token[$i]."','$jenis','".$no_hp."','".$request->data['subtitle']."','".$request->data['icon']."') ");
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
	
	public function tes(Request $request)
	{
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

		$token = $request->token;
		$payload = $request->data;
		return $request->all();
		
	}

	public function getInfo(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
			$this->validate($request,[
				'no_hp' => 'required',
				'no_rumah' => 'required',
				'kode_pp' => 'required'
			]);
			$no_rumah = $request->no_rumah;
			$kode_pp = $request->kode_pp;
			$no_hp = $request->no_hp;
		}else if($auth =  Auth::guard($this->guard3)->user()){
			$nik = $auth->no_hp;
			$kode_lokasi= $auth->kode_lokasi;
			$no_rumah = $auth->no_rumah;
			$kode_pp = $auth->kode_pp;
			$no_hp = $auth->no_hp;
		}

		DB::connection($this->sql)->beginTransaction(); 
		
        try{

			$periode = date('Ym');
			$tgl = intval(date('d'));
		   
			$strSQL = "select a.periode,a.nilai_rt-isnull(b.nilai_rt,0) as nilai_rt,a.nilai_rw-isnull(b.nilai_rw,0) as nilai_rw,(a.nilai_rt+a.nilai_rw) as bill,a.nilai_rt+a.nilai_rw - (isnull(b.nilai_rt+b.nilai_rw,0)) as bayar
			from rt_bill_d a 
			left join (
				select periode_bill,kode_lokasi,kode_rumah,sum(nilai_rt) as nilai_rt,sum(nilai_rw) as nilai_rw,sum(nilai_rt+nilai_rw) as bayar
				from rt_angs_d 
				where kode_lokasi ='$kode_lokasi' and kode_rumah ='$no_rumah' and kode_jenis='IWAJIB' 
				group by periode_bill,kode_lokasi,kode_rumah
			) b on a.periode=periode_bill and a.kode_lokasi=b.kode_lokasi and a.kode_rumah=b.kode_rumah 
			where a.kode_lokasi ='$kode_lokasi' and a.kode_rumah ='$no_rumah' and a.kode_jenis='IWAJIB' and (a.nilai_rt+a.nilai_rw) - isnull(b.bayar,0) > 0 and a.flag_aktif='1' and a.periode='$periode'
			order by a.periode ";
			$cek_tagihan =  DB::connection($this->sql)->select($strSQL);
			if(count($cek_tagihan) > 0){
				$saldo = $cek_tagihan[0]->bayar;
			}else{
				$saldo = 0;
			}
			$success['saldo'] = $saldo;

			$cek_notif = DB::connection($this->sql)->select("select pesan from user_message where periode='$periode' and no_rumah='$no_rumah' and kode_pp='$kode_pp' ");
			if(count($cek_notif) > 0){
				$insnotif = true;
			}else{
				$insnotif = false;
			}
			
			$success['ins_notif'] = $insnotif;
			
			$success['tgl'] = $tgl;
			if($tgl <= 10){
				if($saldo > 0){
					if(!$insnotif){
						$insert = DB::connection($this->sql)->insert("insert into user_message (kode_lokasi,judul,pesan,tgl_input,status,id_device,periode,kode_pp,no_rumah,jenis,no_hp,subtitle,icon) values ('$kode_lokasi','Tagihan iuran','Tagihan iuran periode $periode sebesar 150.000',getdate(),'P1','-','$periode','$kode_pp','$no_rumah','IURAN','$no_hp','-','ic_iuran')");
					}
				}else{
					if($insnotif){
						$insert = DB::connection($this->sql)->update("update user_message set status ='P0' where periode='$periode' and no_rumah='$no_rumah' and kode_pp='$kode_pp' ");
					}
				}
			}else if($tgl > 10){
				if($saldo > 0){
					if(!$insnotif){
						$insert = DB::connection($this->sql)->insert("insert into user_message (kode_lokasi,judul,pesan,tgl_input,status,id_device,periode,kode_pp,no_rumah,jenis,no_hp,subtitle,icon) values ('$kode_lokasi','Tagihan iuran','Tagihan iuran periode $periode sudah jatuh tempo',getdate(),'P2','-','$periode','$kode_pp','$no_rumah','IURAN','$no_hp','-','ic_iuran')");
					}else{
						$insert = DB::connection($this->sql)->update("update user_message set status ='P2',pesan='Tagihan iuran periode $periode sudah jatuh tempo' where periode='$periode' and no_rumah='$no_rumah' and kode_pp='$kode_pp' ");
					}
				}else{
					if($insnotif){
						$insert = DB::connection($this->sql)->update("update user_message set status ='P0' where periode='$periode' and no_rumah='$no_rumah' and kode_pp='$kode_pp' ");
					}
				}
			}

			
            DB::connection($this->sql)->commit();
		
			$sql = "select id,judul,pesan,tgl_input,status,jenis 
			from user_message
			where no_rumah='$no_rumah' and kode_pp='$kode_pp' and status in ('P1','P2')
			union all
			select id,judul,pesan,tgl_input,status,jenis 
			from user_message
			where status in ('1') and no_hp='$no_hp' and jenis in ('PKT','TM')  ";

			$get = DB::connection($this->sql)->select($sql);
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
			
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 200);
        }
	}
	
	public function getNotif(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
			$kode_lokasi= $auth->kode_lokasi;
			$this->validate($request,[
				'no_hp' => 'required',
				'no_rumah' => 'required',
				'kode_pp' => 'required'
			]);
			$no_rumah = $request->no_rumah;
			$kode_pp = $request->kode_pp;
			$no_hp = $request->no_hp;
		}else if($auth =  Auth::guard($this->guard3)->user()){
			$nik = $auth->no_hp;
			$kode_lokasi= $auth->kode_lokasi;
			$no_rumah = $auth->no_rumah;
			$kode_pp = $auth->kode_pp;
			$no_hp = $auth->no_hp;
		}
		
        try{

			$periode = date('Ym');
			$tgl = intval(date('d'));
		   
			$sql = "select id,judul,pesan,tgl_input,status,jenis,subtitle,icon 
			from user_message
			where no_rumah='$no_rumah' and kode_pp='$kode_pp' and status in ('P1','P2','P0')
			union all
			select id,judul,pesan,tgl_input,status,jenis,subtitle,icon  
			from user_message
			where status in ('1') and no_hp='$no_hp' ";

			$get = DB::connection($this->sql)->select($sql);
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
}
