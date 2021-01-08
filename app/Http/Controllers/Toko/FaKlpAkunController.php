<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class FaKlpAkunController extends Controller
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
        
        $auth = DB::connection($this->sql)->select("select kode_klpakun from fa_klpakun where kode_klpakun ='".$isi."' and kode_lokasi='$kode_lokasi' ");
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

            if(isset($request->kode_klpakun)){
                if($request->kode_klpakun == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_klpakun='".$request->kode_klpakun."' ";
                }
                $sql= "select a.kode_klpakun,a.nama,a.umur/12 as umur,a.persen,a.kode_akun,b.nama as nama_akun,a.akun_bp,isnull(c.nama,'') as nama_bp,a.akun_deprs,isnull(d.nama,'') as nama_deprs,a.flag_susut
                from fa_klpakun a 
                inner join masakun b on a.kode_akun=b.kode_akun and b.kode_lokasi =a.kode_lokasi
                left join masakun c on a.akun_bp=c.kode_akun and c.kode_lokasi =a.kode_lokasi 
                left join masakun d on a.akun_deprs=d.kode_akun and d.kode_lokasi =a.kode_lokasi  
                where a.kode_lokasi='$kode_lokasi' $filter 
                ";
            }
            else {
                $sql = "select a.kode_klpakun,a.nama,a.umur /12 as umur,a.persen,a.kode_akun+' - '+ b.nama as akun,a.akun_bp+' - '+isnull(c.nama,'') as bp,a.akun_deprs+' - '+isnull(d.nama,'') as deprs,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,a.tgl_input 
                from fa_klpakun a 
                inner join masakun b on a.kode_akun=b.kode_akun and b.kode_lokasi =a.kode_lokasi
                left join masakun c on a.akun_bp=c.kode_akun and c.kode_lokasi =a.kode_lokasi 
                left join masakun d on a.akun_deprs=d.kode_akun and d.kode_lokasi =a.kode_lokasi  
                where a.kode_lokasi='$kode_lokasi' 
                order by a.kode_klpakun ";
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
            'kode_klpakun' => 'required|max:10',
            'nama' => 'required|max:50',
            'umur' => 'required',
            'persen' => 'required',
            'kode_akun' => 'required',
            'akun_bp' => 'required',
            'akun_deprs' => 'required',
            'flag_susut' => 'required|in:0,1'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_klpakun,$kode_lokasi)){

                
				$umurBln = floatval($request->umur) * 12;		
                $ins = DB::connection($this->sql)->insert("insert into fa_klpakun(kode_klpakun,nama,umur,persen,kode_akun,akun_bp,akun_deprs,kode_agg,tahun,kode_drk,kode_lokasi, flag_susut) values ('$request->kode_klpakun','$request->nama',$umurBln,".floatval($request->persen).",'$request->kode_akun','$request->akun_bp','$request->akun_deprs','-','-','-','$kode_lokasi','$request->flag_susut')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Akun Aktiva Tetap berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Kelompok Akun Aktiva Tetap sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun Aktiva Tetap gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_klpakun' => 'required|max:10',
            'nama' => 'required|max:50',
            'umur' => 'required',
            'persen' => 'required',
            'kode_akun' => 'required',
            'akun_bp' => 'required',
            'akun_deprs' => 'required',
            'flag_susut' => 'required|in:0,1'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fa_klpakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klpakun', $request->kode_klpakun)
            ->delete();

            $umurBln = floatval($request->umur) * 12;		
            $ins = DB::connection($this->sql)->insert("insert into fa_klpakun(kode_klpakun,nama,umur,persen,kode_akun,akun_bp,akun_deprs,kode_agg,tahun,kode_drk,kode_lokasi, flag_susut) values ('$request->kode_klpakun','$request->nama',$umurBln,".floatval($request->persen).",'$request->kode_akun','$request->akun_bp','$request->akun_deprs','-','-','-','$kode_lokasi','$request->flag_susut')");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akun Aktiva Tetap berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun Aktiva Tetap gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_klpakun' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fa_klpakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_klpakun', $request->kode_klpakun)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akun Aktiva Tetap berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun Aktiva Tetap gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


}
