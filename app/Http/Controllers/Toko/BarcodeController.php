<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class BarcodeController extends Controller
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
    public $url_api = 'https://pro.rajaongkir.com/api/';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_barang from brg_barang where kode_barang ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function loadData(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter ="";
            if(isset($request->kode_kirim)){
                if($request->kode_kirim == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and kode_kirim='$request->kode_kirim' ";
                }
            }else{
                $filter .="";
            }
            
            if(isset($request->periode)){
                if($request->periode == "all"){
                    $filter .= "";
                }else{
                    $filter .= " and substring(convert(varchar,tanggal,112),1,6) ='$request->periode' ";
                }
            }else{
                $filter .="";
            }
            
            $sql= "select no_pesan,tanggal,nama_cust,nilai_pesan,kode_kirim 
            from ol_pesan_m
            where kode_lokasi='".$kode_lokasi."' and status_pesan='input' $filter";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
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

    /**
     * Show the from for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'no_bukti' => 'required|array',
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            if(isset($request->no_bukti) && count($request->no_bukti) > 0){
                $nb = "";
                for($a=0; $a<count($request->no_bukti);$a++){
                    if(Storage::disk('s3')->exists('toko/barcode-'.$request->no_bukti[$a])){
                        Storage::disk('s3')->delete('toko/barcode-'.$request->no_bukti[$a]);
                    }
                    Storage::disk('s3')->put('toko/barcode-'.$request->no_bukti[$a],base64_decode(DNS1D::getBarcodePNG($request->no_bukti[$a], 'C39')));

                    $update[$a] = DB::connection($this->sql)->update("update ol_pesan_m set status_pesan='barcode' where no_pesan='".$request->no_bukti[$a]."' and kode_lokasi='$kode_lokasi' ");
                    $nbukti = $request->no_bukti[$a];
                    if($i == 0){
                        $nb .= "'$nbukti'";
                    }else{
    
                        $nb .= ","."'$nbukti'";
                    }

                }	
            }
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $res = DB::connection($this->sql)->select("select no_pesan, nama_cust, alamat_cust, kecamatan_cust,kota_cust,prop_cust,berat,kode_kirim,kode_cust from ol_pesan_m
            where kode_lokasi='".$kode_lokasi."' and status_pesan='barcode' and no_pesan in ($nb) ");
            $success['data'] =  json_decode(json_encode($res),true);
            $success['message'] = "Data Barcode berhasil disimpan";
            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barcode gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

}
