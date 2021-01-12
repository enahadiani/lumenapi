<?php

namespace App\Http\Controllers\Toko;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PeriodeAktifController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            
            $sql = "select * from periode_aktif where kode_lokasi='".$kode_lokasi."' order by modul";
            
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
     * Show the form for creating a new resource.
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
            'modul' => 'required|array',
            'keterangan' => 'required|array',
            'per_awal1' => 'required|array',
            'per_akhir1' => 'required|array',
            'per_awal2' => 'required|array',
            'per_akhir2' => 'required|array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $del = DB::connection($this->sql)->table('periode_aktif')
            ->where('kode_lokasi', $kode_lokasi)
            ->delete();
           
            for ($i=0;$i < count($request->modul);$i++){

                $ins[$i] = DB::connection($this->sql)->insert("insert into periode_aktif(kode_lokasi,modul,keterangan,per_awal1,per_akhir1,per_awal2,per_akhir2, nik_user,tgl_input) values('$kode_lokasi','".$request->modul[$i]."','".$request->keterangan[$i]."','".$request->per_awal1[$i]."','".$request->per_akhir1[$i]."','".$request->per_awal2[$i]."','".$request->per_akhir2[$i]."','$nik',getdate()) ");
            }
            
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['kode'] = '-';
            $success['message'] = "Data Periode Aktif berhasil disimpan";
           
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Periode Aktif gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }



}
