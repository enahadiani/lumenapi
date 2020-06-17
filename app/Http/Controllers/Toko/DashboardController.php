<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;  
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function getTopSelling(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection($this->sql)->select(" select top 5 a.kode_barang,a.nama,isnull(b.jumlah,0) as jumlah
            from brg_barang a
            left join (select a.kode_barang,a.kode_lokasi,count(a.kode_barang) as jumlah
            from brg_trans_dloc a
            where a.kode_lokasi='$kode_lokasi' and a.modul='BRGJUAL'
            group by a.kode_barang,a.kode_lokasi
                    )b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi
            where a.kode_lokasi='$kode_lokasi'
            order by jumlah desc");
            $aju = json_decode(json_encode($aju),true);

            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $aju;
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

    function cek(Request $request){
        $result = $this->getPosisi($request);
        $tmp = json_decode(json_encode($result),true);
        $data = $tmp["original"]["success"];
        dd($data);
    }

}
