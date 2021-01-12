<?php
namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MutasiController extends Controller {
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    function getDetailBarangMutasi(Request $request) {
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $this->validate($request, [            
                'kode_barang' => 'required',                             
                'kode_gudang' => 'required',                             
            ]);

            $kode_barang = $request->kode_barang;
            $kode_gudang = $request->kode_gudang;

            $sql = "select distinct a.nama,a.sat_kecil,b.stok
                from brg_barang a inner join brg_stok b on a.kode_barang=b.kode_barang and a.kode_lokasi=b.kode_lokasi 
                and b.kode_gudang=$kode_gudang
                where a.kode_barang=$kode_barang and a.kode_lokasi=$kode_lokasi";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
                
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, 200);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, 200);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, 500);
        }
    }

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->sql)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function handleNoBukti(Request $request) {
        $this->validate($request, [            
            'tanggal' => 'required',                                    
            'jenis' => 'required',                                    
        ]);
        $tanggal = $request->tanggal;
        $jenis = $request->jenis;
        $kodeAcuan = "";
        if($jenis == 'KRM') {
            $kodeAcuan = "MK";
        } elseif($jenis == 'TRM') {
            $kodeAcuan = "MT";
        }
        $explode = explode("-", $tanggal);
        $lastTahun = substr($explode[0], -2, 2);
        $periode = "$lastTahun$explode[1]"; 

        $no_bukti = $this->generateKode("trans_m", "no_bukti", "$kodeAcuan/$periode/", "0001");

        $success['status'] = true;
        $success['kode'] = $no_bukti;
        
        return response()->json($success, 200);
    }

}

?>