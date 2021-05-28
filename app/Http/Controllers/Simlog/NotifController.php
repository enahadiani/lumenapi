<?php

namespace App\Http\Controllers\Simlog;

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
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';
	
	public function sendPusher(Request $request)
	{
		$this->validate($request,[
			"title" => 'required',
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

				event(new \App\Events\NotifTelu($request->title,$request->message,$request->id[$i]));
				if($request->sts_insert == '1'){

					$ins[$i] = DB::connection($this->db)->insert("insert into user_message (kode_lokasi,judul,subjudul,pesan,nik,id_device,status,tgl_input,icon,sts_read,sts_read_mob) values ('$kode_lokasi','".$request->title."','-','".$request->message."','".$request->id[$i]."','".$request->id[$i]."','1',getdate(),'-','0','0') ");
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
            
			$sql = "select top 5 id,judul,pesan,tgl_input,status,icon,convert(varchar,tgl_input,105) as tgl, convert(varchar,tgl_input,108) as jam
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
