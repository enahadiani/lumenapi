<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class PenjualanLangsungController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';
    public $apiKey = 'fcbeeb755353ac41ab2914806d956f26';

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal'=>'required',
            'kode_cust'=>'required',
            'nama_cust'=>'required',
            'notel_cust'=>'required',
            'alamat_cust'=>'required',
            'kota_cust'=>'required',
            'prop_cust'=>'required',
            'catatan'=>'required',
            'kode_kirim'=>'required',
            'no_resi'=>'required',
            'nilai_ongkir'=>'required',
            'nilai_pesan'=>'required',
            'kode_barang' => 'required|array',
            'qty_barang' => 'required|array',
            'harga_barang' => 'required|array',
            'diskon_barang' => 'required|array',
            'sub_barang' => 'required|array',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $str_format="00000";
            $periode=date('Y').date('m');
            $per=date('y').date('m');
            $prefix=$kode_lokasi."-PJL".$per.".";
            $sql="select right(isnull(max(no_bukti),'00000'),".strlen($str_format).")+1 as id from ol_pesan_m where no_bukti like '$prefix%' and kode_lokasi='".$kode_lokasi."' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            if(count($get) > 0){
                $id = $prefix.str_pad($get[0]['id'], strlen($str_format), $str_format, STR_PAD_LEFT);
            }else{
                $id = "-";
            }

            $sqlg="select a.kode_cust from cust a where a.kode_lokasi='$kode_lokasi' and a.kode_cust='$request->kode_cust'  ";

            $get4 = DB::connection($this->sql)->select($sqlg);
            $get4 = json_decode(json_encode($get4),true);
            if(count($get4) > 0){
                $upd = DB::connection($this->sql)->update("update ol_cust set nama = '$request->nama_cust',alamat='$request->alamat_cust',no_tel='$request->notel_cust',kota='$request->kota_cust',provinsi='$request->prop_cust' where kode_cust='$request->kode_cust' and kode_lokasi='$kode_lokasi'  ");
            }else{
                $ins = DB::connection($this->sql)->insert("insert into ol_cust(kode_cust,nama,alamat,no_tel,kode_lokasi,kota,provinsi) values ('$request->kode_cust','$request->nama_cust','$request->alamat_cust','$request->notel_cust','$kode_lokasi','$request->kota_cust','$request->prop_cust') ");
            }

            $ins =DB::connection($this->sql)->insert("insert into ol_pesan_m (no_pesan,kode_lokasi,tanggal,kode_cust,nama_cust,notel_cust,alamat_cust,kota_cust,prop_cust,catatan,status_pesan,kode_kirim,no_resi,nilai_ongkir,nilai_pesan,no_ref1) values ('$id','$kode_lokasi',getdate(),'$request->kode_cust','$request->nama_cust','$request->notel_cust','$request->alamat_cust','$request->kota_cust','$request->prop_cust','$request->catatan','input','$request->kode_kirim','$request->no_resi',$request->nilai_ongkir,$request->nilai_pesan,'-') ");		

            if(isset($request->kode_barang) && count($request->kode_barang) > 0){

                for($a=0; $a<count($request->kode_barang);$a++){
                    $ins2[$a] = DB::connection($this->sql)->insert("insert into brg_trans_d (no_bukti,kode_lokasi,periode,modul,form,nu,kode_gudang,kode_barang,no_batch,tgl_ed,satuan,dc,stok,jumlah,bonus,harga,hpp,p_disk,diskon,tot_diskon,total) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($id,$kode_lokasi,$periode,'BRGJUAL','BRGJUAL',$a,$kodeGudang,$request->kode_barang[$a],'-',date('Y-m-d H:i:s'),'-','C',0,$request->qty_barang[$a],0,$request->harga_barang[$a],0,0,$request->diskon_barang[$a],0,$request->sub_barang[$a]));
                }	
            }
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['no_bukti'] = $id;
            $success['message'] = "Data Penjualan berhasil disimpan";
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Penjualan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
        
    }

    public function getNota(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->nik) && $request->nik != ""){
                $nik= $request->nik;
            }
  
            $success["nik"]=$nik;
            $success["no_bukti"] = $request->no_bukti;

            $sql="select * from ol_pesan_m where no_bukti='$request->no_bukti' ";
            $get = DB::connection($this->sql)->select($sql);
            $get = json_decode(json_encode($get),true);
            

            $sql="select a.kode_barang,a.harga,a.jumlah,a.diskon*-1 as diskon,b.nama,b.sat_kecil,a.total from brg_trans_d a inner join brg_barang b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi where a.no_bukti='$request->no_bukti' and a.kode_lokasi='$kode_lokasi' ";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($get) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $get;
                $success['data_detail'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['data_detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function cekBonus(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format:Y-m-d',
            'kode_barang' => 'required',
            'jumlah' => 'required',
            'harga' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql=" select ref_qty as beli,bonus_qty as bonus from brg_bonus where kode_barang='$request->kode_barang' and kode_lokasi='$kode_lokasi' and '$request->tanggal' between tgl_mulai and tgl_selesai ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            $jumlah_brg=$request->jumlah;
            $harga=$request->harga;
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $bonus=0;
                $diskon=0;
                $jml_bonus=0;
                if(count($res)>0){
                    for($i=0;$i<count($res);$i++){
                        $bonus += (int) floor(abs($jumlah_brg/$res[$i]["beli"]));
                        $jumlah_brg+=($bonus*$res[$i]["bonus"]);
                        $diskon+= $bonus*$harga;
                    }
                }
                $jml_bonus = $jumlah_brg - $request->jumlah;
        
                $success["bonus"] = $jml_bonus;
                $success["jumlah"] = $jumlah_brg;
                $success["diskon"] = $jml_bonus*$harga;
                $success['status'] = true;
                $success['message'] = "Success!";     
            }
            else{
                $success["bonus"] = 0;
                $success["jumlah"] = $jumlah_brg;
                $success["diskon"] = 0;
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getProvinsi(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $query = array();
            if(isset($request->id)){
                $query = array(
                    'id' => $request->id
                );
            }
            $client = new Client();
            $response = $client->request('GET', 'https://api.rajaongkir.com/starter/province',[
                'headers' => [
                    'key' => $this->apiKey,
                    'Accept'     => 'application/json',
                ],
                'query' => $query
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                $res = $data["rajaongkir"];
                $status = $res["status"]["code"];
                $msg = $res["status"]["description"];
                $result = $res["results"];

            }else{
                $status = 500;
            }   
            
            if($status == 200){ //mengecek apakah data kosong atau tidak 
                if(count($result) > 0){
                    $success['status'] = true;
                    $success['data'] = $result;
                    $success['message'] = $msg;
                }else{
                    $success['message'] = "Data Kosong!";
                    $success['data'] = [];
                    $success['status'] = false;
                }
            }else if($status == 400){
                $success['message'] = $msg;
                $success['data'] = [];
                $success['status'] = false;
            }
            else{
                $success['message'] = "Error";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getKota(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $query = array();
            if(isset($request->id)){
                $kota = array(
                    'id' => $request->id
                );
                $query = array_merge($query,$kota);
            }
            if(isset($request->province)){
                $provinsi = array(
                    'province' => $request->province
                );
                $query = array_merge($query,$provinsi);
            }
            $client = new Client();
            $response = $client->request('GET', 'https://api.rajaongkir.com/starter/city',[
                'headers' => [
                    'key' => $this->apiKey,
                    'Accept'     => 'application/json',
                ],
                'query' => $query
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                $res = $data["rajaongkir"];
                $status = $res["status"]["code"];
                $msg = $res["status"]["description"];
                $result = $res["results"];

            }else{
                $status = 500;
            }   
            
            if($status == 200){ //mengecek apakah data kosong atau tidak 
                if(count($result) > 0){
                    $success['status'] = true;
                    $success['data'] = $result;
                    $success['message'] = $msg;
                }else{
                    $success['message'] = "Data Kosong!";
                    $success['data'] = [];
                    $success['status'] = false;
                }
            }else if($status == 400){
                $success['message'] = $msg;
                $success['data'] = [];
                $success['status'] = false;
            }
            else{
                $success['message'] = "Error";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getService(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $query = array(
                'origin' => $request->origin,
                'destination' => $request->destination,
                'weight' => $request->weight,
                'courier' => $request->courier,
            );
            $client = new Client();
            $response = $client->request('POST', 'https://api.rajaongkir.com/starter/cost',[
                'headers' => [
                    'key' => $this->apiKey,
                    'Accept'     => 'application/json',
                ],
                'form_params' => $query
            ]);

            if ($response->getStatusCode() == 200) { // 200 OK
                $response_data = $response->getBody()->getContents();
                $data = json_decode($response_data,true);
                $res = $data["rajaongkir"];
                $status = $res["status"]["code"];
                $msg = $res["status"]["description"];
                $result = $res["results"][0]["costs"];

            }else{
                $status = 500;
            }   
            
            if($status == 200){ //mengecek apakah data kosong atau tidak 
                if(count($result) > 0){
                    for($i=0;$i<count($result);$i++){
                        $cost = $result[$i]['cost'];
                        $arr_data[$i] = array(
                            'service' => $result[$i]['service'],
                            'description' => $result[$i]['description'],
                            'cost' => $cost[0]['value'],
                            'etd' => $cost[0]['etd'],
                            'note' => $cost[0]['note'],
                        );
                    }
                    $success['status'] = true;
                    $success['data'] = $arr_data;
                    $success['message'] = $msg;
                }else{
                    $success['message'] = "Data Kosong!";
                    $success['data'] = [];
                    $success['status'] = false;
                }
            }else if($status == 400){
                $success['message'] = $msg;
                $success['data'] = [];
                $success['status'] = false;
            }
            else{
                $success['message'] = "Error";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}
