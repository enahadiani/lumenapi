<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $guard = 'yptkug';
    public $db = 'sqlsrvyptkug';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if(isset($request->no_bukti)){
                if($request->no_bukti == "all"){
                    $filter = "";
                }else{
                    $filter = "where no_bukti='$request->no_bukti' ";
                }
                $sql= "select no_bukti,keterangan,link,convert(varchar,tanggal,103) as tanggal,flag_aktif,flag_rektor from dash_video  $filter";
            }else{
                $sql = "select no_bukti,keterangan,link,convert(varchar,tanggal,103) as tanggal,flag_aktif from dash_video ";
            }

            $res = DB::connection($this->db)->select($sql);
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
            'tanggal' => 'required',
            'keterangan' => 'required',
            'link' => 'required',
            'flag_aktif' => 'required',
            'flag_rektor' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $this->generateKode("dash_video", "no_bukti", "VID".substr($request->tanggal,0,2), "001");
            $ins = DB::connection($this->db)->insert("insert into dash_video(no_bukti,tanggal,keterangan,link,flag_aktif,nik_user,kode_lokasi,flag_rektor) values ('".$no_bukti."','".$request->tanggal."','".$request->keterangan."','".$request->link."','".$request->flag_aktif."','".$nik."','$kode_lokasi','$request->flag_rektor') ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Video berhasil disimpan";
            $success['no_bukti'] = $no_bukti;
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Video gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the from for editing the specified resource.
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
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required',
            'keterangan' => 'required',
            'link' => 'required',
            'flag_aktif' => 'required',
            'flag_rektor' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('dash_video')
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            $ins = DB::connection($this->db)->insert("insert into dash_video(no_bukti,tanggal,keterangan,link,flag_aktif,nik_user,kode_lokasi,flag_rektor) values ('".$request->no_bukti."','".$request->tanggal."','".$request->keterangan."','".$request->link."','".$request->flag_aktif."','".$nik."','$kode_lokasi','$request->flag_rektor') ");

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Video berhasil diubah";
            $success['no_bukti'] = $request->no_bukti;
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Video gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->db)->table('dash_video')
            ->where('no_bukti', $request->no_bukti)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Video berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Video gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
