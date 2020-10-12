<?php

namespace App\Http\Controllers\Barber;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class KunjController extends Controller
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

    public function getNoBukti(Request $request) {
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
            $no_bukti = $this->generateKode("bar_kunj", "no_bukti", $kode_lokasi."-KJ".$periode.".", "0001");
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

    public function store(Request $request)
    {
        $this->validate($request, [            
            'tanggal' => 'required',
            'nik_user' => 'required',
            'kode_cust' => 'required',            
            'nama' => 'required',           
            'alamat' => 'required',  
            'no_hp' => 'required',     
            'kode_paket' => 'required',                              
            'kode_barber' => 'required',            
            'nilai' => 'required|numeric',
            'diskon' => 'required|numeric'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
                     
            $periode = app('App\Http\Controllers\Barber\FilterController')->getPerInput($request);
            $periode = substr($periode,2,2).substr($periode,4,2);
            $no_bukti = $this->generateKode("bar_kunj", "no_bukti", $kode_lokasi."-KJ".$periode.".", "0001");
            $total = floatval($request->nilai) - floatval($request->diskon);

            $ins = DB::connection($this->sql)->insert("insert into bar_kunj(no_bukti,tanggal,nik_user,periode,kode_lokasi,no_closing,kode_cust,nama,alamat,no_hp,kode_paket,kode_barber,nilai,diskon,total,no_del,tgl_del,nik_del) values 
                                                      ('".$no_bukti."','".$request->tanggal."','".$request->nik_user."','".$periode."','".$kode_lokasi."','-','".$request->kode_cust."','".$request->nama."','".$request->alamat."','".$request->no_hp."','".$request->kode_paket."','".$request->kode_barber."',".floatval($request->nilai).",".floatval($request->diskon).",".$total.",'-',null,'-')");

                                                                    
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kunjungan berhasil disimpan";
            $success['no_bukti'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kunjungan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				 
        
    }

    
}
