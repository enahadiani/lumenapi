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

    public function getSnapToken(Request $request){
        $this->validate($request, [
            'nis' => 'required',
            'no_bill' => 'required',
            'nilai' => 'required',
            'keterangan' => 'required',
            'kode_param' => 'required',
            'periode_bill' => 'required'
        ]);
        try { 
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $client = new Client();

            $orderId = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");
            date_default_timezone_set('Asia/Jakarta');
            $start_time = date( 'Y-m-d H:i:s O', time() );
            $payload = [
                'transaction_details' => [
                    'order_id'      => $orderId,
                    'gross_amount'  => $request->nilai,
                ],
                'customer_details' => [
                    'first_name'    => $request->nis,
                    'email' => "tes@gmail.com"
                ],
                'item_details' => [
                    [
                        'id'       => $request->no_bill,
                        'price'    => $request->nilai,
                        'quantity' => 1,
                        'name'     => $request->keterangan
                    ]
                ],
                'enabled_payments' => ['echannel'],
                'expiry' => [
                    'start_time' => $start_time,
                    'unit' => 'minutes',
                    'duration' => 180
                ],
                'callbacks'=> [
                    'finish'=> 'https://app.simkug.com/ts-auth/finish-trans'
                ]
            ];

            $url = ( !config('services.midtrans.isProduction') ? 'https://app.sandbox.midtrans.com/snap/v1/transactions' : 'https://app.midtrans.com/snap/v1/transactions');

            $response = $client->request('POST',  $url,[
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(config('services.midtrans.serverKey')),
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($payload)
            ]);

            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $result = json_decode($response_data,true);
                $snap_token = $result['token'];
                DB::connection($this->db)->beginTransaction();
                
                try {
                    
                    $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input,kode_pp,periode_bill,kode_param) values ('$orderId','$request->nis','$request->no_bill','$request->nilai','$request->keterangan','process','$snap_token','$kode_lokasi','$nik',getdate(),'$kode_pp','$request->periode_bill','$request->kode_param')");
                    
                    DB::connection($this->db)->commit();
                    $result['status'] = true;
                    $result['message'] = "Data Pembayaran berhasil disimpan";    
                } catch (\Throwable $e) {
                    DB::connection($this->db)->rollback();
                    $result['status'] = false;
                    $result['message'] = "Data Pembayaran gagal disimpan ".$e;
                }				
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

    public function index()
    {
        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $kode_pp= $data->kode_pp;
            }

            $res = DB::connection($this->db)->select("select no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,tgl_input from sis_mid_bayar where kode_lokasi='$kode_lokasi' and nis='$nik' and kode_pp='$kode_pp'	 
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
            $no_bukti = $this->generateKode("sis_mid_bayar", "no_bukti", $kode_pp."-TES.", "0001");

            $ins = DB::connection($this->db)->insert("insert into sis_mid_bayar (no_bukti,nis,no_bill,nilai,keterangan,status,snap_token,kode_lokasi,nik_user,tgl_input,kode_pp,periode_bill,kode_param) values ('$no_bukti','$request->nis','$request->no_bill','$request->nilai','$request->keterangan','$request->status','$request->snap_token','$kode_lokasi','$nik',getdate(),'$kode_pp','$request->periode_bill','$request->kode_param')");
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
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

    public function ubahStatus($no_bukti,$sts_bayar)
    {
        DB::connection($this->db)->beginTransaction();
        
        try {
            
            $upd = DB::connection($this->db)->table('sis_mid_bayar')
            ->where('no_bukti', $no_bukti)      
            ->update(['status' => $sts_bayar]);

            if($sts_bayar == "success"){

                
                $get = DB::connection($this->db)->select("
                select a.no_bukti,a.nis,a.no_bill,a.nilai,a.periode_bill,a.kode_param,b.akun_piutang,a.kode_pp,a.kode_lokasi from sis_mid_bayar a
                inner join sis_bill_d b on a.nis=b.nis and a.no_bill=b.no_bill and a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and a.kode_param=b.kode_param and a.periode_bill=b.periode
                where a.no_bukti = '$no_bukti' 
                ");
                if(count($get) > 0){

                    $akun_piu = $get[0]->akun_piutang;
                    $nilai = $get[0]->nilai;
                    $nis = $get[0]->nis;
                    $no_bill = $get[0]->no_bill;
                    $kode_param = $get[0]->kode_param;
                    $periode_bill = $get[0]->periode_bill;
                    $kode_pp = $get[0]->kode_pp;
                    $kode_lokasi = $get[0]->kode_lokasi;

                    $periode = date('Ym');
                    $no_kb = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-BM".substr($periode,2,4), "0001");
                    $akun_kb = "1112126";
    
                    $insm = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values ('".$no_kb."','".$kode_lokasi."','-','-','$akun_kb',getdate(),'Pembayaran via midtrans','$kode_pp','KBBILSIS','BM','$periode','IDR',1,".floatval($nilai).",'midtrans','midtrans',getdate(),'midtrans','F','-','".$no_bukti."','$nis','-')");
                    
                    $insj1 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ('$no_kb','-',getdate(),1,'$akun_kb','Pembayaran via midtrans','D',".floatval($nilai).",'$kode_pp','-','-','-','$kode_lokasi','KBBILSIS','KB','$periode','IDR',1,'mitrans',getdate(),'-',".floatval($nilai).")");
                    
                    $insj2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank,nilai_curr) values ('$no_kb','-',getdate(),2,'$akun_piu','Pembayaran via midtrans','C',".floatval($nilai).",'$kode_pp','-','-','-','$kode_lokasi','KBBILSIS','PIUT','$periode','IDR',1,'midtrans',getdate(),'-',".floatval($nilai).")");
    
                    $insd = DB::connection($this->db)->insert("insert into sis_rekon_d(no_rekon,nis,no_bill,periode,nilai,kode_lokasi,akun_titip,akun_piutang,kode_param,dc,modul,id_bank,kode_pp, nilai_cd,periode_bill) values ('$no_kb','$nis','$no_bill','$periode',".floatval($nilai).",'$kode_lokasi','$akun_kb','$akun_piu','$kode_param','D','REKONCD','-','$kode_pp', 0,'$periode_bill')");
                }

            }
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pembayaran berhasil disimpan";
            return response()->json(['success'=>$success], $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pembayaran gagal disimpan ".$e;
            Log::error("Error update from midtrans".$e);
            return response()->json(['success'=>$success], $this->successStatus); 
        }				
        
        
    }


}
