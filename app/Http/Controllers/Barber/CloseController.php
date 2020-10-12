<?php

namespace App\Http\Controllers\Barber;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CloseController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function getNoClose(Request $request) {
        $this->validate($request, [    
            'tanggal' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode = app('App\Http\Controllers\Barber\FilterController')->getPerInput($request);
            $periode = substr($periode,2,2).substr($periode,4,2);
            $no_bukti = $this->generateKode("bar_close", "no_close", $kode_lokasi."-CL".$periode.".", "0001");
            $res = $no_bukti;
            
            $success['status'] = true;
            $success['data'] = $res;
            $success['message'] = "Success!";
            return response()->json(['success'=>$success], $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function getKunj(Request $request) {
        $this->validate($request, [
            'tanggal' => 'required'
        ]);

        try {
           
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res2 = DB::connection($this->sql)->select("select no_bukti,kode_paket,kode_barber,total from bar_kunj 
                                                       where no_close='-' and tanggal = '".$request->tanggal."' and kode_lokasi='".$kode_lokasi."'");						
            $res2= json_decode(json_encode($res2),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;                
                $success['arrkunj'] = $res2;  
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";                 
                $success['arrkunj'] = [];  
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function store(Request $request)
    {
        $this->validate($request, [            
            'tanggal' => 'required',
            'tgl_close' => 'required',
            'nik_user' => 'required'         
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                     
            $periode = app('App\Http\Controllers\Barber\FilterController')->getPerInput($request);
            $periode = substr($periode,2,2).substr($periode,4,2);
            $no_bukti = $this->generateKode("bar_close", "no_close", $kode_lokasi."-CL".$periode.".", "0001");
            $total = floatval($request->nilai) - floatval($request->diskon);

            $ins = DB::connection($this->sql)->insert("insert into bar_close(no_close,tanggal,nik_user,tgl_close,kode_lokasi) values 
                                                      ('".$no_bukti."','".$request->tanggal."','".$request->nik_user."','".$request->tgl_close."','".$kode_lokasi."')");

                                                                    
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Closing berhasil disimpan";
            $success['no_bukti'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Closing gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				 
        
    }

    
}
