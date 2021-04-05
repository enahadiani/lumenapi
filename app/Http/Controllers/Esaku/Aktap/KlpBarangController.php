<?php

namespace App\Http\Controllers\Esaku\Aktap;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class KlpBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_klpfa from fa_klp where kode_klpfa ='".$isi."' and kode_lokasi='$kode_lokasi' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_klpfa)){
                if($request->kode_klpfa == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_klpfa='".$request->kode_klpfa."' ";
                }
                $sql= "select a.kode_klpfa,a.nama,a.kode_klpakun,a.kode_lokasi,case a.jenis when 'A' then 'AKTAP' when 'I' then 'INV' else 'NON' end as jenis,b.nama as nama_klpakun
                from fa_klp a
                inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi                
                where a.kode_lokasi='$kode_lokasi' $filter 
                ";
            }
            else {
                if(isset($request->jenis)){
                    $filter .= " and a.jenis='".$request->jenis."' ";
                }else{
                    $filter .= "";
                }
                $sql = "select a.kode_klpfa,a.nama,a.kode_klpakun,a.kode_lokasi,case a.jenis when 'A' then 'AKTAP' when 'I' then 'INV' else 'NON' end as jenis,b.nama as nama_klpakun
                from fa_klp a
                inner join fa_klpakun b on a.kode_klpakun=b.kode_klpakun and a.kode_lokasi=b.kode_lokasi                
                where a.kode_lokasi='$kode_lokasi' $filter
                order by a.kode_klpfa ";
            }

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

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_klpfa' => 'required|max:10',
            'kode_klpakun' => 'required|max:10',
            'nama' => 'required|max:150',
            'jenis' => 'required|in:AKTAP,INV,NON'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_klpfa,$kode_lokasi)){
		
                $ins = DB::connection($this->sql)->insert("insert into fa_klp(kode_klpfa,nama,kode_klpakun,jenis,kode_lokasi) values ('$request->kode_klpfa','$request->nama','$request->kode_klpakun','".substr($request->jenis,0,1)."','$kode_lokasi')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Kelompok Barang berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Kelompok Barang sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_klpfa' => 'required|max:10',
            'kode_klpakun' => 'required|max:10',
            'nama' => 'required|max:150',
            'jenis' => 'required|in:AKTAP,INV,NON'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fa_klp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klpfa', $request->kode_klpfa)
            ->delete();
            
            $ins = DB::connection($this->sql)->insert("insert into fa_klp(kode_klpfa,nama,kode_klpakun,jenis,kode_lokasi) values ('$request->kode_klpfa','$request->nama','$request->kode_klpakun','".substr($request->jenis,0,1)."','$kode_lokasi')");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kelompok Barang berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_klpfa' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fa_klp')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klpfa', $request->kode_klpfa)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Kelompok Barang berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kelompok Barang gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


}
