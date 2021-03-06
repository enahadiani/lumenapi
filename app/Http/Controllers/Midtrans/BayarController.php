<?php

namespace App\Http\Controllers\Midtrans;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;

class BayarController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = "sqlsrvyptkug";
    public $guard = "ts";
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    // public function getSnapTokenv2(Request $request){
    //     $this->validate($request, [
    //         'no_bill' => 'required|array',
    //         'nilai' => 'required',
    //         'periode_bill' => 'required|array'
    //     ]);
    //     try { 
    //         if($data =  Auth::guard($this->guard)->user()){
    //             $nik= $data->nik;
    //             $kode_lokasi= $data->kode_lokasi;
    //             $kode_pp= $data->kode_pp;
    //         }

    //         $no_bill = $request->input('no_bill');  
    //         $this_in = "";
    //         $filter_in = "";
    //         if(count($no_bill) > 0){
    //             for($x=0;$x<count($no_bill);$x++){
    //                 if($x == 0){
    //                     $this_in .= "'".$no_bill[$x]."'";
    //                 }else{
                        
    //                     $this_in .= ","."'".$no_bill[$x]."'";
    //                 }
    //             }
    //             $filter_in = " and a.no_bill in ($this_in) ";
    //         }     

    //         $get = DB::connection($this->db)->select("select a.kode_param,isnull(a.tagihan,0)-isnull(c.bayar,0) as sisa,a.no_bill
    //         from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan 
    //                 from sis_bill_d x 
    //                 inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
    //                 where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
    //                 group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
            
    //         left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar from sis_rekon_d x 
    //                 inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
    //                 where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
    //                 group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_param=c.kode_param
    //         where a.tagihan - isnull(c.bayar,0) > 0 $filter_in
    //         order by a.no_bill,a.kode_param");
    //         $get = json_decode(json_encode($get),true);
    //         $item_details = array();
    //         $total_bayar = intval($request->nilai);
    //         $total_tmp =0;
    //         if(count($get) > 0){
    //             $sisa_bayar = $total_bayar;
    //             for($i=0;$i < count($get); $i++){
    //                 $row = $get[$i];
    //                 if($sisa_bayar > 0){
    //                     if($sisa_bayar >= intval($row['sisa'])){
                            
    //                         $item_details[] = array(
    //                             'id'       => $row['no_bill'],
    //                             'price'    => intval($row['sisa']),
    //                             'quantity' => 1,
    //                             'name'     => $row['kode_param']
    //                         );
    //                     }else{
    //                         $item_details[] = array(
    //                             'id'       => $row['no_bill'],
    //                             'price'    => $sisa_bayar,
    //                             'quantity' => 1,
    //                             'name'     => $row['kode_param']
    //                         );
    //                     }
    //                     $sisa_bayar = $sisa_bayar - intval($row['sisa']);
    //                 }else if($sisa_bayar == 0){
    //                     break;
    //                 }
    //             }
    //         }
            
    //         $client = new Client();

    //         $orderId = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");
    //         date_default_timezone_set('Asia/Jakarta');
    //         $start_time = date( 'Y-m-d H:i:s O', time() );
    //         $payload = [
    //             'transaction_details' => [
    //                 'order_id'      => $orderId,
    //                 'gross_amount'  => $request->nilai,
    //             ],
    //             'customer_details' => [
    //                 'first_name'    => $nik,
    //                 'email' => "tes@gmail.com"
    //             ],
    //             'item_details' => $item_details,
    //             'enabled_payments' => ['echannel'],
    //             'expiry' => [
    //                 'start_time' => $start_time,
    //                 'unit' => 'minutes',
    //                 'duration' => 180
    //             ],
    //             'callbacks'=> [
    //                 'finish'=> 'https://app.simkug.com/ts-auth/finish-trans'
    //             ]
    //         ];

    //         $url = ( !config('services.midtrans.isProduction') ? 'https://app.sandbox.midtrans.com/snap/v1/transactions' : 'https://app.midtrans.com/snap/v1/transactions');

    //         $response = $client->request('POST',  $url,[
    //             'headers' => [
    //                 'Authorization' => 'Basic '.base64_encode(config('services.midtrans.serverKey')),
    //                 'Accept'     => 'application/json',
    //                 'Content-Type' => 'application/json'
    //             ],
    //             'body' => json_encode($payload)
    //         ]);

    //         if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) { // 200 OK
    //             $response_data = $response->getBody()->getContents();
    //             $result = json_decode($response_data,true);
    //             $snap_token = $result['token'];
    //             DB::connection($this->db)->beginTransaction();
                
    //             try {
                    
    //                 $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input,kode_pp,periode_bill,kode_param) values ('$orderId','$nik','".$request->no_bill[0]."','$request->nilai','Pembayaran via midtrans','process','$snap_token','$kode_lokasi','$nik',getdate(),'$kode_pp','".$request->periode_bill[0]."','".$item_details[0]['name']."')");

    //                 for($i=0;$i<count($item_details);$i++){

    //                     $insd[$i] = DB::connection($this->db)->insert("insert into sis_mid_bayar_d (no_bukti,no_bill,nilai,kode_param,kode_pp,kode_lokasi,periode_bill) values ('$orderId','".$request->no_bill[$i]."','".$item_details[$i]['price']."','".$item_details[$i]['name']."','$kode_pp','$kode_lokasi','".$request->periode_bill[0]."')");
    //                 }
                    
    //                 DB::connection($this->db)->commit();
    //                 $result['status'] = true;
    //                 $result['message'] = "Data Pembayaran berhasil disimpan";    
    //             } catch (\Throwable $e) {
    //                 DB::connection($this->db)->rollback();
    //                 $result['status'] = false;
    //                 $result['message'] = "Data Pembayaran gagal disimpan ".$e;
    //             }				
    //         }
    //         return response()->json($result, 200);
    //     } catch (BadResponseException $ex) {
    //         $response = $ex->getResponse();
    //         $res = json_decode($response->getBody(),true);
    //         $result['status'] = false;
    //         $result['message'] = $res;
    //         return response()->json($result, 200);
    //     } 
    // }

    public function getSnapToken(Request $request){
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $item_details = $request->item_details;
            $trans_det = $request->transaction_details;
            $orderId = $trans_det['order_id'];
            $tmp = explode("|",$item_details[0]['name']);
            $kode_param = $tmp[0];
            $periode_bill = $tmp[1];
            
            $tmp2 = explode("|",$request->user_id);
            $nik = $tmp2[0];
            $kode_pp = $tmp2[1];
            
            $kode_lokasi = substr($item_details[0]['id'],0,2);

            $cek = DB::connection($this->db)->select(" select no_bukti from sis_mid_bayar where nis='$nik' and kode_lokasi='$kode_lokasi' and kode_pp='$kode_pp' and status in ('pending','process') ");
            if(count($cek) > 0){

                $result['status'] = false;
                $result['message'] = "Pembayaran tidak dapat dilakukan. Masih ada pembayaran yang belum diselesaikan.";

            }else{
                Log::info('Request dari android :');
                Log::info($request->all());
    
    
                $client = new Client();
    
                $url = ( !config('services.midtrans.isProduction') ? 'https://app.sandbox.midtrans.com/snap/v1/transactions' : 'https://app.midtrans.com/snap/v1/transactions');
    
                // $item_details = $request->item_details;
                // for($i=0; $i < count($item_details); $i++){
                //     $tmp = explode("|",$item_details[$i]['name']);
                //     $kode_param = $tmp[0];
                //     $item_details[$i]['name'] = $kode_param;
                // }
    
                // date_default_timezone_set('Asia/Jakarta');
                // $start_time = date( 'Y-m-d H:i:s O', time() );
                // $payload = [
                //     'transaction_details' => $request->transaction_details,
                //     'customer_details' => $request->customer_details,
                //     'item_details' => $item_details,
                //     'expiry' => [
                //         'start_time' => $start_time,
                //         'unit' => 'minutes',
                //         'duration' => 180
                //     ]
                // ];
    
                $response = $client->request('POST',  $url,[
                    'headers' => [
                        'Authorization' => 'Basic '.base64_encode(config('services.midtrans.serverKey')),
                        'Accept'     => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($request->all())
                ]);
    
                if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) { // 200 OK
                    $response_data = $response->getBody()->getContents();
                    $result = json_decode($response_data,true);
                    $snap_token = $result['token'];
                    DB::connection($this->db)->beginTransaction();
                    
                    try {
                        
    
                        $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input,kode_pp,periode_bill,kode_param) values ('$orderId','$nik','".$item_details[0]['id']."','".floatval($trans_det['gross_amount'])."','Pembayaran via midtrans','process','$snap_token','$kode_lokasi','$nik',getdate(),'$kode_pp','".$periode_bill."','".$kode_param."')");
    
                        for($i=0;$i<count($item_details);$i++){
                            $tmp = explode("|",$item_details[$i]['name']);
                            $kode_param = $tmp[0];
                            $periode_bill = $tmp[1];
                            $insd[$i] = DB::connection($this->db)->insert("insert into sis_mid_bayar_d (no_bukti,no_bill,nilai,kode_param,kode_pp,kode_lokasi,periode_bill) values ('$orderId','".$item_details[$i]['id']."','".floatval($item_details[$i]['price'])."','".$kode_param."','$kode_pp','$kode_lokasi','".$periode_bill."')");
                        }
                        
                        DB::connection($this->db)->commit();
                        // Kirim Notif
                        $result['status'] = true;
                        $result['message'] = "Data Pembayaran berhasil disimpan";    
                    } catch (\Throwable $e) {
                        Log::error($e);
                        DB::connection($this->db)->rollback();
                        $result['status'] = false;
                        $result['message'] = "Data Pembayaran gagal disimpan ".$e;
                    }	
                                
                }

            }
            return response()->json($result, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            Log::error($res);
            $result['status'] = false;
            $result['message'] = $res;
            return response()->json($result, 200);
        } 
    }

    public function getStatusTransaksi(Request $request){
        $this->validate($request, [
            'order_id' => 'required'
        ]);
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $client = new Client();

            $url = ( !config('services.midtrans.isProduction') ? 'https://api.sandbox.midtrans.com/v2/' : 'https://api.midtrans.com/v2/');

            $response = $client->request('GET',  $url.$request->order_id.'/status',[
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(config('services.midtrans.serverKey')),
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $result = json_decode($response_data,true);
            }
            return response()->json($result, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $result['status'] = false;
            $result['message'] = $res;
            return response()->json($result, 200);
        } 
    }

    public function cancelTransaksi(Request $request){
        $this->validate($request, [
            'order_id' => 'required'
        ]);
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $client = new Client();

            $url = ( !config('services.midtrans.isProduction') ? 'https://api.sandbox.midtrans.com/v2/' : 'https://api.midtrans.com/v2/');

            $response = $client->request('POST',  $url.$request->order_id.'/cancel',[
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(config('services.midtrans.serverKey')),
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $result = json_decode($response_data,true);
            }
            return response()->json($result, 200);
        } catch (BadResponseException $ex) {
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $result['status'] = false;
            $result['message'] = $res;
            return response()->json($result, 200);
        } 
    }

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $res = DB::connection($this->db)->select("select no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,tgl_input from sis_mid_bayar where kode_lokasi='$kode_lokasi' and nis='$nik' and kode_pp='$kode_pp' order by no_bukti desc	 
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getKode()
    {
        try{
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }
            $no_bukti = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");
            $success['no_bukti'] = $no_bukti;
            $success['status'] = true;
            $success['message'] = "Success";
            return response()->json($success, $this->successStatus);

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nis' => 'required',
            'no_bill' => 'required',
            'nilai' => 'required',
            'keterangan' => 'required',
            'status' => 'required',
            'snap_token' => 'required',
            'kode_param' => 'required',
            'periode_bill' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $get = DB::connection($this->db)->select("select a.kode_param,isnull(a.tagihan,0)-isnull(c.bayar,0) as sisa,a.no_bill
            from (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as tagihan 
                    from sis_bill_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param )a 
            
            left join (select x.kode_lokasi,x.no_bill,x.kode_param,sum(x.nilai) as bayar from sis_rekon_d x 
                    inner join sis_siswa y on x.nis=y.nis and x.kode_lokasi=y.kode_lokasi and x.kode_pp=y.kode_pp
                    where x.kode_lokasi = '$kode_lokasi' and x.nis='$nik' and x.kode_pp='$kode_pp' and x.nilai<>0 
                    group by x.kode_lokasi,x.no_bill,x.nis,x.kode_param) c on a.no_bill=c.no_bill and a.kode_lokasi=c.kode_lokasi and a.kode_param=c.kode_param
            where a.tagihan - isnull(c.bayar,0) > 0 and a.no_bill='$request->no_bill' and a.kode_param='$request->kode_param'
            order by a.no_bill,a.kode_param");
            $get = json_decode(json_encode($get),true);
            $item_details = array();
            $total_bayar = intval($request->nilai);
            $total_tmp =0;
            if(count($get) > 0){
                $sisa_bayar = $total_bayar;
                for($i=0;$i < count($get); $i++){
                    $row = $get[$i];
                    if($sisa_bayar > 0){
                        if($sisa_bayar >= intval($row['sisa'])){
                            
                            $item_details[] = array(
                                'id'       => $row['no_bill'],
                                'price'    => intval($row['sisa']),
                                'quantity' => 1,
                                'name'     => $row['kode_param']
                            );
                        }else{
                            $item_details[] = array(
                                'id'       => $row['no_bill'],
                                'price'    => $sisa_bayar,
                                'quantity' => 1,
                                'name'     => $row['kode_param']
                            );
                        }
                        $sisa_bayar = $sisa_bayar - intval($row['sisa']);
                    }else if($sisa_bayar == 0){
                        break;
                    }
                }
            }

            $no_bukti = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");

            $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input,kode_pp,periode_bill,kode_param) values ('$no_bukti','$request->nis','$request->no_bill','$request->nilai','$request->keterangan','$request->status','$request->snap_token','$kode_lokasi','$nik',getdate(),'$kode_pp','$request->periode_bill','$request->kode_param')");

            for($i=0;$i<count($item_details);$i++){

                $insd[$i] = DB::connection($this->db)->insert("insert into sis_mid_bayar_d (no_bukti,no_bill,nilai,kode_param,kode_pp,kode_lokasi,periode_bill) values ('$no_bukti','".$request->no_bill."','".$item_details[$i]['price']."','".$item_details[$i]['name']."','$kode_pp','$kode_lokasi','$request->periode_bill')");
            }
            
            DB::connection($this->db)->commit();
            // KIRIM NOTIF
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            Log::error($e);
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }

    public function show($no_bukti)
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bukti,nis,no_bill,nilai,keterangan,status,snap_token from sis_mid_bayar where kode_lokasi='$kode_lokasi' and nik_user='$nik' and no_bukti='$no_bukti'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Tidak ditemukan!";
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function ubahStatus(Request $request,$no_bukti,$sts_bayar)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            
            $upd = DB::connection($this->db)->table('sis_mid_bayar')
            ->where('no_bukti', $no_bukti)      
            ->update(['status' => $sts_bayar]);

            $get = DB::connection($this->db)->select("
            select a.no_bukti,a.nis,a.nilai,a.kode_pp,a.kode_lokasi,a.tgl_input,case when a.status='process' then dbo.fnNamaTanggal2(DATEADD(day, 1, getdate()),2) else dbo.fnNamaTanggal2(DATEADD(day, 1, a.tgl_input),2) end as tgl_expired,a.status,a.snap_token from sis_mid_bayar a
            where a.no_bukti = '$no_bukti' 
            ");
            $nilai = $get[0]->nilai;
            $nis = $get[0]->nis;
            $kode_pp = $get[0]->kode_pp;
            $kode_lokasi = $get[0]->kode_lokasi;
            $tgl_expired = $get[0]->tgl_expired;
            $snap_token = $get[0]->snap_token;

            $judul = "-";
            $pesan = "-";
            if($sts_bayar == "success"){

                
                if(count($get) > 0){

                    // $akun_piu = $get[0]->akun_piutang;

                    $periode = date('Ym');
                    $no_kb = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-BM".substr($periode,2,4), "0001");
                    $akun_kb = "1112126";
    
                    $insm = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values ('".$no_kb."','".$kode_lokasi."','-','-','$akun_kb',getdate(),'Pembayaran via midtrans','$kode_pp','KBBILSIS','BM','$periode','IDR',1,".floatval($nilai).",'midtrans','midtrans',getdate(),'midtrans','F','-','".$no_bukti."','$nis','-')");

                    $getdet = DB::connection($this->db)->select("
                    select a.nilai,a.no_bill,a.periode_bill,a.kode_param, b.akun_piutang 
                    from sis_mid_bayar_d a
                    inner join sis_bill_d b on a.no_bill=b.no_bill and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.kode_param=b.kode_param and b.nis='$nis' and a.periode_bill=b.periode
                    where a.kode_lokasi='$kode_lokasi' and a.kode_pp='$kode_pp' and a.no_bukti = '$no_bukti' ");
                    
                    if(count($getdet) > 0){
                        for($i=0; $i < count($getdet); $i++){
                            $line = $getdet[$i];
                            $akun_piu = $line->akun_piutang;
                            $periode_bill = $line->periode_bill;
                            $no_bill = $line->no_bill;
                            $nilai_det = $line->nilai;
                            $kode_param = $line->kode_param;

                            $insj1[$i] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ('$no_kb','-',getdate(),1,'$akun_kb','Pembayaran via midtrans','D',".floatval($nilai_det).",'$kode_pp','-','-','-','$kode_lokasi','KBBILSIS','KB','$periode','IDR',1,'midtrans',getdate(),'-',".floatval($nilai_det).")");
                            
                            $insj2[$i] = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ('$no_kb','-',getdate(),2,'$akun_piu','Pembayaran via midtrans','C',".floatval($nilai_det).",'$kode_pp','-','-','-','$kode_lokasi','KBBILSIS','PIUT','$periode','IDR',1,'midtrans',getdate(),'-',".floatval($nilai_det).")");
            
                            $insd[$i] = DB::connection($this->db)->insert("insert into sis_rekon_d(no_rekon,nis,no_bill,periode,nilai,kode_lokasi,akun_titip,akun_piutang,kode_param,dc,modul,id_bank,kode_pp, nilai_cd,periode_bill) values ('$no_kb','$nis','$no_bill','$periode',".floatval($nilai_det).",'$kode_lokasi','$akun_kb','$akun_piu','$kode_param','D','REKONCD','-','$kode_pp', 0,'$periode_bill')");
                        }
                    }
                }
                $judul = "Pembayaran untuk transaksi $no_bukti berhasil";
                $pesan = "Pembayaran senilai ".number_format($nilai,0,",",".")." untuk transaksi $no_bukti telah berhasil.";
            }else if($sts_bayar == "expired"){
                $judul = "Pembayaran untuk transaksi $no_bukti sudah tidak berlaku";
                $pesan = "Transaksi $no_bukti telah dibatalkan karena pembayaran tidak diterima dalam jangka waktu yang sudah ditentukan.";
            }else if($sts_bayar == "pending"){
                $judul = "Segera lakukan pembayaran";
                $pesan = "Transaksi $no_bukti senilai ".number_format($nilai,0,",",".")." menunggu pelunasan anda. Batas waktu maksimal pembayaran sampai $tgl_expired ";
            }else if($sts_bayar == "cancel"){
                $judul = "Pembayaran transaksi $no_bukti dibatalkan";
                $pesan = "Transaksi $no_bukti telah dibatalkan oleh penerima. ";
            }else if($sts_bayar == "failed"){
                $judul = "Pembayaran transaksi $no_bukti gagal";
                $pesan = "Pembayaran transaksi $no_bukti senilai ".number_format($nilai,0,",",".")." gagal dilakukan. ";
            }else{
                $judul = "Segera selesaikan proses pembayaran transaksi $no_bukti";
                $pesan = "Selesaikan proses pembayaran transaksi $no_bukti senilai ".number_format($nilai,0,",",".")." untuk melanjutkan pembayaran.";
            }

            if($judul != "-"){

                $request->request->add([
                    'kode_lokasi' => $kode_lokasi,
                    'nik' => 'midtrans',
                    'jenis' => 'Siswa',
                    'judul' => $judul,
                    'kode_pp' => $kode_pp,
                    'kontak' => $nis,
                    'pesan' => $pesan,
                    'kode_matpel' => '-',
                    'ref1' => $no_bukti,
                    'ref2' => $sts_bayar,
                    'ref3' => $snap_token
                ]);
    
                $kirim_pesan = app('App\Http\Controllers\Ts\PesanController')->store($request);
                $kirim_pesan = json_decode(json_encode($kirim_pesan),true);
                Log::info('Status Notif after update midtrans status: ');
                Log::info($kirim_pesan['original']);
                Log::info($request->all());
            }

            DB::connection($this->db)->commit();
        
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            Log::error("Error update from midtrans ".$e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }


}
