<?php

namespace App\Http\Controllers\Ts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Log;

class NotifBillingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = "ts";
    public $db = "sqlsrvyptkug";

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }  
    
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
		$apiKey = config('services.gcm.api_ts');
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
                // 'notification'              => array (
                //     "body" => $data["message"],
                //     "title" => $data["title"],
                //     "click_action" => $data["click_action"]
                // ),
                'data'              => $data,
                'priority' => "high",
                'android' => array(
                    "priority" => "high",
                    "ttl" => "86400s",
                    "notification" => array (
                      "click_action" =>  $data["click_action"]
                    )
                ),
             

                // "android" => array (
                //     "ttl" => "86400s",
                //     "notification" => array (
                //         "click_action" => $data["click_action"]
                //     )
                // ),
                    
            );
		}else{

			$post = array(
				'registration_ids'  => $ids,
				// 'notification'              => array (
				// 	"body" => $data["message"],
				// 	"title" => $data["title"]
				// ),
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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                $filter .= " and a.kode_pp='$request->kode_pp' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->db)->select("select a.no_bukti,a.judul,a.tgl_input,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.kode_pp
            from sis_pesan_m a
            where a.kode_lokasi='$kode_lokasi' and a.nik_user='$nik' and a.jenis='Billing' $filter");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
            }
            return response()->json(['success'=>$success], $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    
    public function store(Request $request)
    {
        $this->validate($request, [
            'periode' => 'required',
            'no_bill' => 'required',
            'judul' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp = $data->kode_pp;
            }else{
                $nik = $request->nik;
                $kode_lokasi = $request->kode_lokasi;
                $kode_pp = $request->kode_pp;
            }
            
            $per = date('ym');
            $msg = "";
            $no_bukti = $this->generateKode("sis_pesan_m", "no_bukti", $kode_lokasi."-PSN".$per.".", "000001");
            
            $sql = "select a.nik,isnull(c.id_device,'-') as id_device,b.nilai
            from sis_hakakses a
            inner join ( select a.no_bill,a.nis,a.kode_pp,a.kode_lokasi,sum(a.nilai) as nilai
                         from sis_bill_d a 
                         group by a.no_bill,a.nis,a.kode_pp,a.kode_lokasi
                        ) b on a.nik = b.nis and a.kode_lokasi=b.kode_lokasi and a.kode_pp=b.kode_pp
            left join users_device c on a.nik=c.nik and a.kode_lokasi=c.kode_lokasi and a.kode_pp=c.kode_pp
            where a.kode_pp='$kode_pp' and a.kode_lokasi='$kode_lokasi' and b.no_bill='$request->no_bill' ";
            $ck = DB::connection($this->db)->select($sql);
            $ck = json_decode(json_encode($ck),true);
         
            $click_action = "informasi";
            $key = array(
                'no_bukti' => $no_bukti,
                'snap_token' => '-',
                'status' => 'billing',
                'no_bill' => $request->no_bill,
                'nik' => $nik
            );
            
            $ins = DB::connection($this->db)->insert("insert into sis_pesan_m(no_bukti,jenis,nis,kode_akt,kode_kelas,judul,subjudul,pesan,kode_pp,kode_lokasi,ref1,ref2,ref3,link,tipe,tgl_input,nik_user,kode_matpel) values ('$no_bukti','Billing','-','-','-','$request->judul','-','-','$kode_pp','$kode_lokasi','-','-','-','-','info',getdate(),'$nik','-') ");
            
            $arr_id = array();
            $arr_pesan = array();
            $nik = array();
            $msg_notif = array();
            $arr_nis = array();
            if(count($ck) > 0){
                for($i=0;$i<count($ck);$i++){
                    if(!isset($nik[$ck[$i]['nik']])){
                        $ins2[$i] = DB::connection($this->db)->insert("insert into sis_pesan_d(no_bukti,kode_lokasi,kode_pp,sts_read,sts_read_mob,id_device,nik,pesan,ref1,ref2,ref3) values ('$no_bukti','$kode_lokasi','$kode_pp','0','0','".$ck[$i]['id_device']."','".$ck[$i]['nik']."','Tagihan anda sebesar ".$ck[$i]['nilai']."','".$request->no_bill."','".$request->periode."','-') ");
                    }
                    
                    if($ck[$i]['id_device'] != "-"){
                        array_push($arr_id,$ck[$i]['id_device']);
                        if (!in_array($ck[$i]['nik'], $arr_nis))
                        {
                            array_push($arr_nis,$ck[$i]['nik']);
                        }
                        array_push($arr_pesan,'Tagihan anda sebesar '.$ck[$i]['nilai']);
                    }
                    
                    $nik[$ck[$i]['nik']] = $ck[$i]['nik'];
                }  
            }
            
            DB::connection($this->db)->commit();

            if(count($arr_id) > 0){
                for($i=0;count($arr_id);$i++){
                    $payload = array(
                        'title' => $request->judul,
                        'message' => $arr_pesan[$i],
                        'click_action' => $click_action,
                        'key' => $key
                    );
                    $res = $this->gcm($arr_id[$i],$payload);
                    $hasil= json_decode($res,true);
                    $success['hasil'][$i] = $hasil;
                    if(isset($hasil['success'])){
                        if($hasil['failure'] > 0){
                            $sts_n = 0;
                            $msg_n = "Notif gagal dikirim ke".$arr_id[$i];
                        }else{
                            $msg_n = "Notif berhasil dikirim ke".$arr_id[$i];
                            $sts_n = 1;
                        }
                    }else{
                        $msg_n = "Notif gagal dikirim".$arr_id[$i];
                    }
                    $msg_notif[$i] = $msg_n;
                }
            }
            
            $sts = true;
            $msg = "Data Pesan berhasil disimpan. ";
            $success['pesan_notif'] = $msg_notif;
            $success['status'] = $sts;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = $msg;
            $success['arr_id'] = $arr_id;
            $success['arr_pesan'] = $arr_pesan;
            $jml = count($arr_id);
            Log::info('Jumlah Notif Billing:'.$jml);
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            Log::error('Error Notif Billing:'.$e);
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }		
            
            $del = DB::connection($this->db)->table('sis_pesan_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $kode_pp)
            ->where('jenis', 'Billing')
            ->delete();

            $del2 = DB::connection($this->db)->table('sis_pesan_d')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $request->no_bukti)
            ->where('kode_pp', $kode_pp)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pesan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pesan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function getPeriode(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
            $kode_pp = $auth->kode_pp;
		}
		
        try{
            
			$sql = "select distinct periode as periode from sis_bill_d where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp'
            order by periode desc
			";
			$res = DB::connection($this->db)->select($sql);
			$res = json_decode(json_encode($res),true);
			if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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

    public function getNoBill(Request $request){

		if($auth =  Auth::guard($this->guard)->user()){
			$nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
            $kode_pp = $auth->kode_pp;
		}
		
        try{

            $filter = "";
            if(isset($request->periode)){
                if($request->periode != ""){
                    $filter.= " and periode='$request->periode' ";
                }else{
                    
                    $filter.= "";
                }
            }else{
                
                $filter.= "";
            }
			$sql = "select no_bill,keterangan from sis_bill_m where kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp' $filter
			";
			$res = DB::connection($this->db)->select($sql);
			$res = json_decode(json_encode($res),true);
			if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
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
